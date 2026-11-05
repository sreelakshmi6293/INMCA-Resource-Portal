<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['student', 'faculty'], true)
) {
    header('Location: login.php');
    exit;
}
/**
 * INMCA Academic Resources Portal: AI Exam Assistant interface.
 * This page sends questions through chat-proxy.php to the local Flask assistant.
 */
$semesters = range(1, 10);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f5f8fc">
  <title>AI Exam Assistant | INMCA Resources Portal</title>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <style>
:root{font-family:Inter,"Segoe UI",Arial,sans-serif;color:#17243a;background:#f5f8fc;font-synthesis:none;text-rendering:optimizeLegibility;--blue:#2864dc;--blue-dark:#1f54c5;--ink:#17243a;--muted:#738098;--line:#e5eaf1;--panel:#fff}*{box-sizing:border-box}body{margin:0;min-width:320px;min-height:100vh;background:#f5f8fc}.topbar{height:68px;background:#fff;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 34px;position:relative;z-index:5}.brand{display:flex;align-items:center;gap:11px;font-size:15px;font-weight:700;color:#1b2c46;text-decoration:none;white-space:nowrap}.brand-mark{width:34px;height:34px;display:grid;place-items:center;background:#eaf1ff;color:var(--blue);border-radius:11px;font-weight:800;font-size:18px}.brand-light{font-weight:500;color:#61708a}.topnav{height:100%;display:flex;align-items:center;gap:30px}.topnav a{height:100%;display:flex;align-items:center;position:relative;text-decoration:none;color:#65728a;font-size:13px;font-weight:550}.topnav a:hover,.topnav a.active{color:var(--blue)}.topnav a.active:after{content:"";height:2px;background:var(--blue);position:absolute;bottom:0;left:0;right:0;border-radius:4px}.mobile-menu{display:none;border:0;background:transparent;font-size:23px;color:#42516a}.app-layout{display:grid;grid-template-columns:270px minmax(0,1fr);min-height:calc(100vh - 68px)}.sidebar{background:#fff;border-right:1px solid var(--line);padding:23px 16px 17px;display:flex;flex-direction:column;min-height:calc(100vh - 68px)}.new-chat{height:43px;border:0;border-radius:9px;background:var(--blue);color:#fff;font-size:13px;font-weight:650;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:9px;box-shadow:0 3px 9px #2864dc2a}.new-chat:hover,.send-button:not(:disabled):hover{background:var(--blue-dark)}.new-chat span{font-size:20px;font-weight:400;line-height:1}.search-wrap{height:39px;display:flex;align-items:center;gap:8px;margin:19px 0 24px;padding:0 11px;border:1px solid var(--line);border-radius:8px;background:#fbfcfe;color:#8995a8}.search-icon{font-size:22px;line-height:1}.search-wrap input{width:100%;border:0;outline:0;background:transparent;color:var(--ink);font:inherit;font-size:12px}.search-wrap input::placeholder{color:#929db0}.history-heading{font-size:10px;letter-spacing:.1em;font-weight:700;color:#98a3b5;padding:0 5px 10px}.history-list{display:flex;flex-direction:column;gap:4px;overflow-y:auto;max-height:calc(100vh - 300px)}.history-empty{font-size:12px;line-height:1.6;color:#98a3b5;margin:2px 5px}.history-item{display:flex;align-items:center;gap:6px;padding:10px 9px;border-radius:8px;color:#495873;font-size:12px;cursor:pointer}.history-item:hover,.history-item.selected{background:#f0f5ff;color:#2858b9}.history-title{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1}.delete-chat{border:0;background:transparent;color:#99a4b5;cursor:pointer;font-size:16px;padding:2px 4px;border-radius:5px;opacity:0}.history-item:hover .delete-chat,.history-item:focus-within .delete-chat{opacity:1}.delete-chat:hover{background:#fee;color:#c43d4d}.sidebar-note{margin-top:auto;padding:12px 10px;border-radius:9px;background:#f6f8fb;color:#8995a8;font-size:10px;line-height:1.5;display:flex;gap:8px}.sidebar-note span:first-child{font-size:14px;color:#70809a}.main-panel{min-width:0;min-height:calc(100vh - 68px);padding:30px 32px 20px;display:flex;justify-content:center}.chat-shell{width:min(100%,970px);min-height:calc(100vh - 118px);display:flex;flex-direction:column}.chat-header{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;padding:4px 0 22px;border-bottom:1px solid var(--line)}.eyebrow{font-size:9px;letter-spacing:.13em;font-weight:750;color:#8a97aa;margin:0 0 6px}.chat-header h1{font-size:24px;letter-spacing:-.035em;margin:0;color:#182741}.selectors{display:flex;gap:10px}.selectors label{font-size:10px;color:#77849a;font-weight:650;display:flex;flex-direction:column;gap:6px}.selectors select{height:36px;min-width:144px;border:1px solid #e1e7ef;border-radius:8px;background:#fff;padding:0 28px 0 10px;color:#43516a;font:inherit;font-size:12px;outline:none}.selectors select:focus,.search-wrap:focus-within{border-color:#9bb9f7;box-shadow:0 0 0 3px #dce8ff}.selectors select:disabled{background:#f7f9fc;color:#9da7b6}.conversation{flex:1;min-height:260px;display:flex;flex-direction:column;overflow-y:auto;padding:25px 5px 20px}.welcome{margin:auto;max-width:690px;padding:28px 10px 20px;text-align:center}.welcome-icon{width:55px;height:55px;border-radius:18px;display:grid;place-items:center;margin:0 auto 17px;background:linear-gradient(145deg,#e8f0ff,#f3f6ff);color:#3674e8;font-size:25px}.welcome h2{font-size:22px;letter-spacing:-.035em;margin:0 0 9px;color:#1c2b44}.welcome>p{max-width:510px;margin:0 auto;color:#7d899d;font-size:13px;line-height:1.65}.quick-actions{display:flex;justify-content:center;flex-wrap:wrap;gap:9px;margin:26px auto 0;max-width:600px}.quick-actions button{border:1px solid #e4eaf3;border-radius:9px;background:#fff;color:#52627d;padding:10px 12px;display:flex;align-items:center;gap:7px;font-size:11px;font-weight:600;cursor:pointer;transition:.15s}.quick-actions button span{color:#5180dc;font-size:14px}.quick-actions button:hover{border-color:#b7cbf4;background:#f8faff;color:#2d5db9}.messages{display:flex;flex-direction:column;gap:22px}.message-row{display:flex;gap:10px;align-items:flex-start;max-width:86%}.message-row.user{align-self:flex-end;flex-direction:row-reverse}.avatar{flex:none;width:29px;height:29px;border-radius:10px;background:#eaf1ff;color:#3971d8;font-weight:750;font-size:12px;display:grid;place-items:center}.message-row.user .avatar{background:#eaf4f3;color:#408279}.message-content{min-width:0}.message-label{font-size:10px;font-weight:700;color:#8793a6;margin:2px 0 6px}.message-bubble{white-space:pre-wrap;overflow-wrap:anywhere;font-size:16px;line-height:1.8;color:#394862;background:#fff;border:1px solid #e8edf4;border-radius:4px 13px 13px 13px;padding:11px 14px;box-shadow:0 2px 8px #24334b06}
.resource-name {
  color: #2864dc;
  font-size: 13px;
  font-weight: 600;
}.message-row.user .message-bubble{background:#edf3ff;border-color:#e3ecff;border-radius:13px 4px 13px 13px;color:#254477}.typing{display:flex;align-items:center;gap:5px;padding:5px 2px;color:#8895a9;font-size:11px}.typing i{width:5px;height:5px;border-radius:50%;background:#7596d8;animation:pulse 1s infinite}.typing i:nth-child(2){animation-delay:.15s}.typing i:nth-child(3){animation-delay:.3s}@keyframes pulse{0%,60%,100%{opacity:.35;transform:translateY(0)}30%{opacity:1;transform:translateY(-3px)}}.composer-area{width:min(100%,790px);margin:0 auto}.composer{display:flex;align-items:flex-end;gap:10px;padding:10px 10px 10px 16px;background:#fff;border:1px solid #e0e7f0;border-radius:14px;box-shadow:0 5px 20px #2d456810}.composer:focus-within{border-color:#a8c0f3;box-shadow:0 0 0 3px #e0eaff}.composer textarea{flex:1;min-width:0;resize:none;max-height:150px;border:0;outline:0;background:transparent;color:#263650;font:inherit;line-height:1.55;font-size:13px;padding:6px 0}.composer textarea::placeholder{color:#9aa5b6}.send-button{width:34px;height:34px;flex:none;display:grid;place-items:center;border:0;border-radius:10px;background:var(--blue);color:#fff;font-size:23px;line-height:1;cursor:pointer}.send-button:disabled{background:#e9edf4;color:#a0aabc;cursor:not-allowed}.composer-hint{text-align:center;margin:8px 0 0;color:#9aa5b5;font-size:10px}.composer-hint span{padding:0 4px}.notice{text-align:center;color:#8a6a2b;background:#fff9e9;border:1px solid #f0e4bf;border-radius:8px;padding:9px 12px;margin:9px auto 0;font-size:11px;line-height:1.5;max-width:790px}.notice[hidden]{display:none}@media(max-width:900px){.topbar{padding:0 22px}.topnav{gap:19px}.app-layout{grid-template-columns:235px minmax(0,1fr)}.sidebar{padding-left:13px;padding-right:13px}.main-panel{padding:25px 22px 18px}.chat-header{flex-direction:column}.selectors{width:100%}.selectors label{flex:1}.selectors select{width:100%}}@media(max-width:640px){.topbar{height:60px;padding:0 16px}.brand{font-size:13px;gap:8px}

.brand-mark{width:31px;height:31px}.mobile-menu{display:block}.topnav{display:none;position:absolute;top:59px;left:0;right:0;height:auto;padding:8px 16px 14px;background:#fff;border-bottom:1px solid var(--line);box-shadow:0 8px 16px #1a2b4610;flex-direction:column;align-items:stretch;gap:0}.topnav.open{display:flex}.topnav a{height:39px}.topnav a.active:after{display:none}.app-layout{display:block;min-height:calc(100vh - 60px)}.sidebar{display:none;position:absolute;z-index:4;top:60px;left:0;width:min(82vw,290px);height:calc(100vh - 60px);min-height:0;box-shadow:10px 0 30px #18274118}.sidebar.open{display:flex}.main-panel{min-height:calc(100vh - 60px);padding:20px 14px 12px}.chat-shell{min-height:calc(100vh - 92px)}.chat-header{padding:3px 0 16px;gap:15px}.chat-header h1{font-size:21px}.selectors{gap:8px}.selectors select{min-width:0;font-size:11px;padding-left:8px}.conversation{padding:20px 0 14px}.welcome{padding:20px 4px}.welcome h2{font-size:19px}.welcome>p{font-size:12px}.quick-actions{gap:7px;margin-top:20px}.quick-actions button{padding:9px 10px;font-size:10px}.message-row{max-width:94%}.message-bubble{font-size:15px;line-height: 1.75;}.composer{border-radius:12px;padding-left:12px}.history-list{max-height:calc(100vh - 245px)}}@media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}}
@media (max-width: 640px) {
    .topbar {
        height: 98px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 44px 38px;
        padding: 8px 16px;
    }

    .brand {
        grid-column: 1 / -1;
        grid-row: 1;
    }

    .mobile-menu {
        grid-column: 2;
        grid-row: 2;
        justify-self: end;
    }

    .history-menu {
        grid-column: 1;
        grid-row: 2;
        justify-self: start;
    }

    .topnav {
        top: 97px;
    }

    .sidebar {
        top: 98px;
        height: calc(100vh - 98px);
    }
}
.welcome-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.message-row.assistant .avatar {
    color: #2864dc;
}
.message-row.assistant .material-symbols-outlined {
    font-size: 20px;
    font-variation-settings: 'FILL' 1;
}
@media (max-width: 640px) {
  .delete-chat {
    opacity: 1;
  }
}
.message-row.assistant .message-bubble {
    position: relative;
    padding-right: 36px;
}
.copy-reply {
  border: 0;
  background: transparent;
  color: #738098;
  cursor: pointer;
  position: absolute;
  padding: 4px; 
  right: 8px;
  top: 6px;
}
.copy-reply .material-symbols-outlined {
  font-size: 18px;
}
@media (min-width: 641px) {
  .sidebar {
    position: sticky;
    top: 0;
    height: calc(100vh - 68px);
    align-self: start;
  }
}
.answer-heading {
  display: block;
  margin: 18px 0 8px;
}

.answer-list-item {
  display: block;
  margin: 6px 0;
}

.answer-list-item b {
  margin-right: 5px;
}

.resource-source {
  color: #2864dc;
  font-size: 13px;
  font-style: normal;
}

.answer-divider {
  border: 0;
  border-top: 1px solid #e5eaf1;
  margin: 18px 0;
}
  </style>

</head>
<body>
  <header class="topbar">
    
  
<a class="brand" href="<?php echo ($_SESSION['role'] ?? '') === 'student' ? 'studentdash.php' : 'facultydash.php'; ?>"
   aria-label="INMCA Resources Portal home"
   style="display:flex; align-items:center; gap:11px; font-size:15px; font-weight:700; color:#1b2c46; text-decoration:none; white-space:nowrap;">

    <img src="images/logo.svg"
         alt="INMCA Logo"
         style="width:34px; height:34px; object-fit:contain; border-radius:8px; display:block; flex-shrink:0;">

    <span>INMCA <span class="brand-light" style="font-weight:500; color:#61708a;">Resources Portal</span></span>

</a>

     <button class="mobile-menu history-menu" id="history-menu" aria-label="Toggle chat history">☷</button>
    <button class="mobile-menu" id="mobile-menu" aria-label="Toggle navigation" aria-expanded="false">☰</button>
    <nav class="topnav" id="topnav" aria-label="Main navigation">
      <a href="index.html">Home</a>
<a href="<?= $_SESSION['role'] === 'student' ? 'student-dashboard.html' : 'faculty_dashboard.php' ?>">
    Dashboard
</a>

      <a href="ai-assistant.php" class="active" aria-current="page">AI Assistant</a><a href="logout.php">Logout</a>
    </nav>
  </header>

  <div class="app-layout">
    <aside class="sidebar" id="sidebar" aria-label="Chat history">
      <button class="new-chat" id="new-chat"><span aria-hidden="true">＋</span> New Chat</button>
      <label class="search-wrap"><span class="search-icon" aria-hidden="true">⌕</span><input id="history-search" type="search" placeholder="Search chat history" autocomplete="off"></label>
      <div class="history-heading">YOUR CHATS</div>
      <div class="history-list" id="history-list" aria-live="polite">
        <p class="history-empty" id="history-empty">Chat history will be added in a future update.</p>
      </div>
      
    </aside>

    <main class="main-panel">
      <section class="chat-shell" aria-labelledby="page-title">
        <div class="chat-header">
          <div><p class="eyebrow">YOUR STUDY COMPANION</p><h1 id="page-title">AI Exam Assistant</h1></div>
          <div class="selectors">
            <label for="semester-select">Semester<select id="semester-select"><option value="">Select semester</option><?php foreach ($semesters as $semester): ?><option value="<?= $semester ?>">Semester <?= $semester ?></option><?php endforeach; ?></select></label>
            <label for="subject-select">Subject<select id="subject-select" disabled><option value="">Choose semester first</option></select></label>
          </div>
        </div>

        <div class="conversation" id="conversation" aria-live="polite" aria-relevant="additions text">
          <div class="welcome" id="welcome">
           
            <div class="welcome-icon">
            <span class="material-symbols-outlined">network_intelligence</span>
           </div>
            <h2>How can I help you with your studies?</h2>
            <p>Ask a question, explore a topic, or get ready for your next exam. Choose your semester and subject to get started.</p>
            <div class="quick-actions" aria-label="Suggested prompts">
              <button type="button" data-prompt="Explain a topic from my subject in simple terms."><span>◉</span> Explain Topic</button>
              <button type="button" data-prompt="Give me important exam questions for my subject."><span>✧</span> Important Questions</button>
              <button type="button" data-prompt="Give me a quick revision summary for my subject."><span>↻</span> Quick Revision</button>
              <button type="button" data-prompt="Give me practice 3 mark questions for my subject."><span>☷</span> 3 Mark Questions</button>
              <button type="button" data-prompt="Give me practice 6 mark questions for my subject."><span>☰</span> 6 Mark Questions</button>
            </div>
          </div>
          <div class="messages" id="messages" role="log" aria-label="Conversation messages"></div>
        </div>

        <div class="composer-area">
          <form class="composer" id="message-form">
            <textarea id="message-input" rows="1" maxlength="6000" placeholder="Ask something about your subject..." aria-label="Your message"></textarea>
            <button class="send-button" id="send-button" type="submit" aria-label="Send message" disabled><span aria-hidden="true">↑</span></button>
          </form>
          <p class="composer-hint">Enter to send <span>·</span> Shift + Enter for a new line</p>
          <div class="notice" id="status-notice" role="status" hidden></div>
        </div>
      </section>
    </main>
  </div>
  <script>
(() => {
  "use strict";

  
const subjectsBySemester = {
    1: [
        "ENGLISH",
        "BASIC MATHEMATICS",
        "INTRODUCTION TO PROGRAMMING",
        "INTRODUCTION TO COMPUTERS & PC HARDWARE",
        "FUNDAMENTALS OF ACCOUNTANCY"
    ],
    2: [
        "TECHNICAL COMMUNICATION",
        "INTRODUCTION TO DISCRETE MATHEMATICS",
        "INTRODUCTION TO DIGITAL SYSTEMS & LOGIC DESIGN",
        "PROBLEM SOLVING AND STRUCTURED PROGRAMMING",
        "PERSONALITY DEVELOPMENT AND SOFT SKILLS"
    ],
    3: [
        "COMPUTER ORGANIZATION",
        "PROBABILITY AND STATISTICS",
        "INTRODUCTION TO OBJECT ORIENTED PROGRAMMING",
        "ACCOUNTING AND FINANCIAL MANAGEMENT",
        "DATA STRUCTURES"
    ],
    4: [
        "LINUX/UNIX FUNDAMENTALS",
        "STATISTICAL APPLICATIONS",
        "OPERATING SYSTEMS",
        "ELEMENTS OF BUSINESS MANAGEMENT",
        "INTERNET CONCEPTS AND WEB TECHNOLOGY"
    ],
    5: [
        "NUMERICAL METHODS",
        "USER INTERFACE DESIGN",
        "INTRODUCTION TO RDBMS AND SQL",
        "FUNDAMENTALS OF INFORMATION SYSTEMS SECURITY",
        "INTRODUCTION TO OPERATIONS RESEARCH"
    ],
    6: [
        "INTRODUCTION TO SOFTWARE ENGINEERING",
        "FUNDAMENTALS OF DATA SCIENCE",
        "VISUAL PROGRAMMING",
        "DESIGN AND ANALYSIS OF ALGORITHMS",
        "MINI PROJECT 1"
    ],
    7: [
        "M-COMMERCE",
        "JAVA PROGRAMMING",
        "ADVANCED WEB PROGRAMMING",
        "ADVANCED SOFTWARE ENGINEERING",
        "DISTRIBUTED COMPUTING"
    ],
    8: [
        "ADVANCED DATABASE MANAGEMENT SYSTEMS",
        "ADVANCED COMPUTER NETWORKS",
        "RESEARCH METHODOLOGY",
        "ORGANIZATIONAL BEHAVIOUR",
        "ARTIFICIAL INTELLIGENCE"
    ],
    9: [
        "DATA SCIENCE & MACHINE LEARNING",
        "MOBILE COMPUTING",
        "CYBER FORENSICS",
        "SOCIAL NETWORK ANALYSIS",
        "MINI PROJECT 2"
    ],
    10: [
        "COMPREHENSIVE VIVA",
        "SEMINAR",
        "MAIN PROJECT"
    ]
};


  const semesterSelect = document.querySelector("#semester-select");
  const subjectSelect = document.querySelector("#subject-select");
  const input = document.querySelector("#message-input");
  const sendButton = document.querySelector("#send-button");
  const form = document.querySelector("#message-form");
  const messages = document.querySelector("#messages");
  const welcome = document.querySelector("#welcome");
  const notice = document.querySelector("#status-notice");
  const conversation = document.querySelector("#conversation");
  const historyList = document.querySelector("#history-list");
  const historySearch = document.querySelector("#history-search");
  let waitingForAnswer = false;
  let currentSessionId = null;

  
function showMessage(sender, text) {
    const row = document.createElement("article");
    row.className = sender === "student"
        ? "message-row user"
        : "message-row assistant";

    const avatar = document.createElement("div");
    avatar.className = "avatar";
    avatar.setAttribute("aria-hidden", "true");
    if (sender === "student") {
    avatar.textContent = "👤";
} else {
    avatar.innerHTML = '<span class="material-symbols-outlined">network_intelligence</span>';
}

    const content = document.createElement("div");
    content.className = "message-content";

    const label = document.createElement("div");
    label.className = "message-label";
    label.textContent = sender === "student"
        ? "You"
        : "AI Exam Assistant";

    const bubble = document.createElement("div");
    bubble.className = "message-bubble";

    const cleanText = String(text)
        .replace(/&#x20;|&#32;|&nbsp;/gi, " ")
        .replace(/&amp;#x20;/gi, " ")
        .replace(/\\([`*_#])/g, "$1")
        .replace(/\\[ \t]+/g, " ")
        .replace(/\r\n/g, "\n");

    bubble.innerHTML = cleanText
  .replace(/&/g, "&amp;")
  .replace(/</g, "&lt;")
  .replace(/>/g, "&gt;")
  .replace(/\*\(Source:\s*([^*]+?)\)\*/gi,
    '<span class="resource-source">(Source: $1)</span>')
  .replace(/^#{1,6}\s+(.+)$/gm,
    '<strong class="answer-heading">$1</strong>')
  .replace(/^---+\s*$/gm, '<hr class="answer-divider">')
  .replace(/^\s*(\d+)\.\s+(.+)$/gm,
    '<span class="answer-list-item"><b>$1.</b> $2</span>')
  .replace(/\*\*(.+?)\*\*/g, "<strong>$1</strong>")
  .replace(/`([^`]+)`/g, "<code>$1</code>")
  .replace(/^\s*[-*]\s+/gm, "• ")
  .replace(/\n/g, "<br>");

    if (sender !== "student") {
  const copyButton = document.createElement("button");
  copyButton.type = "button";
  copyButton.textContent = "Copy";
  copyButton.className = "copy-reply";
  copyButton.setAttribute("aria-label", "Copy reply");
  copyButton.innerHTML =
    '<span class="material-symbols-outlined">content_copy</span>';
  copyButton.addEventListener("click", async () => {
    await navigator.clipboard.writeText(text);
    copyButton.innerHTML = '<span class="material-symbols-outlined">check</span>';
    setTimeout(() => {
      copyButton.innerHTML = '<span class="material-symbols-outlined">content_copy</span>';
    }, 1500);
  });
  bubble.append(copyButton);
            }
  content.append(label,  bubble);
 

    row.append(avatar, content);
    messages.append(row);

    welcome.hidden = true;
    conversation.scrollTop = conversation.scrollHeight;
}


  function updateSubjects() {
    subjectSelect.replaceChildren();
    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.textContent = semesterSelect.value ? "Select subject" : "Choose semester first";
    subjectSelect.append(placeholder);
    subjectSelect.disabled = !semesterSelect.value;

    (subjectsBySemester[semesterSelect.value] || []).forEach((subject) => {
      const option = document.createElement("option");
      option.value = subject;
      option.textContent = subject;
      subjectSelect.append(option);
    });
  }

  function updateSendButton() {
    sendButton.disabled = waitingForAnswer || input.value.trim() === "";
    input.style.height = "auto";
    input.style.height = `${Math.min(input.scrollHeight, 150)}px`;
  }

  function showError(message) {
    notice.textContent = message;
    notice.hidden = false;
  }
  
async function loadChatHistory() {
  try {
    const response = await fetch("chat-proxy.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "list_sessions" })
    });

    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.error || "Could not load chat history.");
    }

    historyList.replaceChildren();

    if (!result.sessions || result.sessions.length === 0) {
      const empty = document.createElement("p");
      empty.className = "history-empty";
      empty.textContent = "No chats yet.";
      historyList.append(empty);
      return;
    }

    result.sessions.forEach((session) => {
      const item = document.createElement("div");
      item.className = "history-item";
      item.dataset.sessionId = session.id;

      const title = document.createElement("span");
      title.className = "history-title";
      title.textContent = session.title;

      const deleteButton = document.createElement("button");
      deleteButton.type = "button";
      deleteButton.className = "delete-chat";
      deleteButton.setAttribute("aria-label", "Delete this chat");
      deleteButton.textContent = "🗑";

     item.append(title, deleteButton);
      historyList.append(item);
    });
  } catch (error) {
    console.error("Chat history error:", error);
  }
}

async function loadChat(sessionId) {
  try {
    const response = await fetch("chat-proxy.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "load_session",
        session_id: sessionId
      })
    });

    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.error || "Could not load chat.");
    }

    currentSessionId = sessionId;
    messages.replaceChildren();
    welcome.hidden = true;

    result.messages.forEach((msg) => {
      showMessage(
        msg.sender === "user" ? "student" : "assistant",
        msg.message
      );
    });

  } catch (error) {
    console.error("Load chat error:", error);
    showError(error.message);
  }
}
historyList.addEventListener("click", async (event) => {
  const deleteButton = event.target.closest(".delete-chat");

  if (deleteButton) {
    event.stopPropagation();

    const item = deleteButton.closest(".history-item");
    const sessionId = item.dataset.sessionId;

    if (!confirm("Are you sure you want to delete this chat?")) return;

    try {
      const response = await fetch("chat-proxy.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "delete_session",
          session_id: sessionId
        })
      });

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.error || "Could not delete this chat.");
      }

      if (String(currentSessionId) === String(sessionId)) {
        currentSessionId = null;
        messages.replaceChildren();
        welcome.hidden = false;
      }

      await loadChatHistory();
    } catch (error) {
      showError(error.message);
    }

    return;
  }

  const item = event.target.closest(".history-item");
  if (item) {
    loadChat(item.dataset.sessionId);
  }
});


historySearch.addEventListener("input", function () {
    const searchText = this.value.toLowerCase().trim();
    const chatItems = historyList.querySelectorAll(".history-item");

    chatItems.forEach(function (item) {
        const titleElement = item.querySelector(".history-title");
        const title = titleElement
            ? titleElement.textContent.toLowerCase()
            : "";

        if (title.includes(searchText)) {
            item.style.display = "";
        } else {
            item.style.display = "none";
        }
    });
});


  async function sendQuestion() {
    const question = input.value.trim();
    if (!question || waitingForAnswer) return;

    if (!semesterSelect.value || !subjectSelect.value) {
      showError("Please choose a semester and subject first.");
      return;
    }

    notice.hidden = true;
    showMessage("student", question);
    input.value = "";
    waitingForAnswer = true;
    updateSendButton();

    const loading = document.createElement("div");
    loading.className = "typing";
    loading.textContent = "Getting your answer...";
    messages.append(loading);
    conversation.scrollTop = conversation.scrollHeight;

    try {
      const response = await fetch("chat-proxy.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          message: question,
          semester: semesterSelect.value,
          subject: subjectSelect.value,
          session_id: currentSessionId


        })
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || "The assistant could not answer right now.");
      if (typeof result.answer !== "string" || result.answer.trim() === "") {
        throw new Error("The assistant returned an empty answer. Please try again.");
      }
      currentSessionId = result.session_id;
      showMessage("assistant", result.answer);
      await loadChatHistory();
    } catch (error) {
      showError(error.message || "Could not connect to the assistant. Check that WAMP and Flask are running.");
    } finally {
      loading.remove();
      waitingForAnswer = false;
      updateSendButton();
    }
  }

  semesterSelect.addEventListener("change", updateSubjects);
  input.addEventListener("input", updateSendButton);
  input.addEventListener("keydown", (event) => {
    if (event.key === "Enter" && !event.shiftKey) {
      event.preventDefault();
      form.requestSubmit();
    }
  });
  form.addEventListener("submit", (event) => {
    event.preventDefault();
    sendQuestion();
  });

  document.querySelectorAll(".quick-actions [data-prompt]").forEach((button) => {
    button.addEventListener("click", () => {
      input.value = button.dataset.prompt;
      input.focus();
      updateSendButton();
    });
  });

  document.querySelector("#new-chat").addEventListener("click", () => {
    currentSessionId = null;
    messages.replaceChildren();
    welcome.hidden = false;
    notice.hidden = true;
    input.value = "";
    updateSendButton();
  });

  const sidebar = document.querySelector("#sidebar");
const topnav = document.querySelector("#topnav");

document.querySelector("#mobile-menu").addEventListener("click", () => {
    topnav.classList.toggle("open");
    sidebar.classList.remove("open");
});

document.querySelector("#history-menu").addEventListener("click", () => {
    sidebar.classList.toggle("open");
    topnav.classList.remove("open");
});

  loadChatHistory();
  updateSubjects();
  updateSendButton();
})();
</script>
</body>
</html>



