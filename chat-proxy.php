
<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function sendJson($statusCode, $data)
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Only logged-in students and faculty may use chat history.
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    sendJson(401, ['error' => 'Please log in to use the AI assistant.']);
}

$role = $_SESSION['role'];
$userId = (int) $_SESSION['user_id'];

if ($userId < 1 || !in_array($role, ['student', 'faculty'], true)) {
    sendJson(403, ['error' => 'You do not have permission to use the AI assistant.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, ['error' => 'Use POST to send a request.']);
}

require_once __DIR__ . '/db.php';
$conn->set_charset('utf8mb4');

$body = json_decode(file_get_contents('php://input'), true);

if (!is_array($body)) {
    sendJson(400, ['error' => 'Invalid JSON request.']);
}

$action = $body['action'] ?? 'send';

function getOwnedSession($conn, $sessionId, $userId, $role)
{
    $stmt = $conn->prepare(
        'SELECT id, title, semester, subject
         FROM chat_sessions
         WHERE id = ? AND user_id = ? AND user_role = ?'
    );
    $stmt->bind_param('iis', $sessionId, $userId, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $session = $result->fetch_assoc();
    $stmt->close();

    return $session ?: null;
}

function createSession($conn, $userId, $role, $title, $semester, $subject)
{
    $stmt = $conn->prepare(
        'INSERT INTO chat_sessions
         (user_id, user_role, title, semester, subject)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('issis', $userId, $role, $title, $semester, $subject);
    $stmt->execute();
    $id = $conn->insert_id;
    $stmt->close();

    return $id;
}

function saveMessage($conn, $sessionId, $sender, $message)
{
    $stmt = $conn->prepare(
        'INSERT INTO chat_messages (session_id, sender, message)
         VALUES (?, ?, ?)'
    );
    $stmt->bind_param('iss', $sessionId, $sender, $message);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare(
        'UPDATE chat_sessions SET updated_at = CURRENT_TIMESTAMP WHERE id = ?'
    );
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    $stmt->close();
}

// Create a new empty chat.
if ($action === 'create_session') {
    $semester = filter_var($body['semester'] ?? null, FILTER_VALIDATE_INT);
    $subject = trim($body['subject'] ?? '');

    if (!$semester || $semester < 1 || $semester > 10) {
        $semester = null;
    }
    if (strlen($subject) > 150) {
        sendJson(400, ['error' => 'Subject name is too long.']);
    }

    $id = createSession(
        $conn, $userId, $role, 'New Chat', $semester, $subject
    );

    sendJson(200, ['session_id' => $id, 'title' => 'New Chat']);
}

// List only the current user's sessions.
if ($action === 'list_sessions') {
    $stmt = $conn->prepare(
        'SELECT id, title, semester, subject, created_at, updated_at
         FROM chat_sessions
         WHERE user_id = ? AND user_role = ?
         ORDER BY updated_at DESC, id DESC'
    );
    $stmt->bind_param('is', $userId, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $sessions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    sendJson(200, ['sessions' => $sessions]);
}

// Load messages only after verifying ownership.
if ($action === 'load_session') {
    $sessionId = filter_var($body['session_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$sessionId || !getOwnedSession($conn, $sessionId, $userId, $role)) {
        sendJson(404, ['error' => 'Chat not found.']);
    }

    $stmt = $conn->prepare(
        'SELECT sender, message, created_at
         FROM chat_messages
         WHERE session_id = ?
         ORDER BY id ASC'
    );
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    sendJson(200, ['session_id' => $sessionId, 'messages' => $messages]);
}

// Delete only the current user's chat and its messages.
if ($action === 'delete_session') {
    $sessionId = filter_var($body['session_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$sessionId || !getOwnedSession($conn, $sessionId, $userId, $role)) {
        sendJson(404, ['error' => 'Chat not found.']);
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare(
            'DELETE FROM chat_messages WHERE session_id = ?'
        );
        $stmt->bind_param('i', $sessionId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            'DELETE FROM chat_sessions
             WHERE id = ? AND user_id = ? AND user_role = ?'
        );
        $stmt->bind_param('iis', $sessionId, $userId, $role);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        sendJson(200, ['success' => true]);
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('Chat deletion failed: ' . $e->getMessage());
        sendJson(500, ['error' => 'Could not delete this chat.']);
    }
}

// Send a question to the existing Flask AI backend.
if ($action !== 'send') {
    sendJson(400, ['error' => 'Unknown action.']);
}

$message = trim($body['message'] ?? '');
$semester = filter_var($body['semester'] ?? null, FILTER_VALIDATE_INT);
$subject = trim($body['subject'] ?? '');
$sessionId = filter_var($body['session_id'] ?? null, FILTER_VALIDATE_INT);

if (
    $message === '' || strlen($message) > 6000 ||
    !$semester || $semester < 1 || $semester > 10 ||
    $subject === '' || strlen($subject) > 150
) {
    sendJson(400, ['error' => 'Enter a question and choose a semester and subject.']);
}

if (!function_exists('curl_init')) {
    sendJson(500, ['error' => 'PHP cURL is not enabled.']);
}

if ($sessionId) {
    $session = getOwnedSession($conn, $sessionId, $userId, $role);

    if (!$session) {
        sendJson(404, ['error' => 'Chat not found.']);
    }

    $sessionSemester = (int) $session['semester'];
    $sessionSubject = (string) $session['subject'];

    if ($sessionSemester !== $semester || $sessionSubject !== $subject) {
        sendJson(400, ['error' => 'The selected semester or subject does not match this chat.']);
    }
} else {
    $title = function_exists('mb_substr')
        ? mb_substr($message, 0, 70)
        : substr($message, 0, 70);

    $sessionId = createSession(
        $conn, $userId, $role, $title, $semester, $subject
    );
}

// Save the user's question before requesting the AI answer.
try {
    saveMessage($conn, $sessionId, 'user', $message);
} catch (Throwable $e) {
    error_log('Saving user chat message failed: ' . $e->getMessage());
    sendJson(500, ['error' => 'Could not save your question.']);
}

$flaskRequest = json_encode([
    'message' => $message,
    'semester' => $semester,
    'subject' => $subject
], JSON_UNESCAPED_UNICODE);

$curl = curl_init('http://127.0.0.1:5000/chat');
curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $flaskRequest,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 90
]);

$flaskResponse = curl_exec($curl);
$curlError = curl_errno($curl);
$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($flaskResponse === false || $curlError !== 0) {
    sendJson(502, [
        'error' => 'Could not reach Flask. Your question was saved; please try again.'
    ]);
}

$responseData = json_decode($flaskResponse, true);

if ($statusCode < 200 || $statusCode >= 300 || !is_array($responseData)) {
    sendJson(502, ['error' => 'The AI assistant returned an error. Your question was saved.']);
}

$answer = $responseData['answer']
    ?? $responseData['response']
    ?? $responseData['reply']
    ?? null;

if (!is_string($answer) || trim($answer) === '') {
    sendJson(502, ['error' => 'The AI returned an empty answer. Your question was saved.']);
}

try {
    saveMessage($conn, $sessionId, 'ai', $answer);
} catch (Throwable $e) {
    error_log('Saving AI chat message failed: ' . $e->getMessage());
    sendJson(500, [
        'error' => 'The AI answered, but saving the answer failed. Please try again.'
    ]);
}

sendJson(200, [
    'answer' => $answer,
    'session_id' => $sessionId
]);
?>
