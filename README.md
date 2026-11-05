
# INMCA Resource Portal

A PHP and MySQL portal for INMCA students, faculty, and administrators. It also includes a Flask-based AI study assistant.

## Requirements

- WAMP Server with Apache, PHP, and MySQL
- Python 3.11 or later
- Composer
- Gemini API key

## 1. Set Up the Project

1. Copy or clone the project into `C:\wamp64\www\INMCA-Resource-Portal`.
2. Start WAMP. Make sure Apache and MySQL are running.
3. Open `http://localhost/phpmyadmin`.
4. Import `schema.sql`. It creates the `student_portal` database.
5. Select the `student_portal` database and import `admins.sql`, `faculty.sql`, and `forgot_password.sql`.
6. In phpMyAdmin, select `student_portal`, open the **SQL** tab, and run:

   ```sql
   CREATE TABLE IF NOT EXISTS chat_sessions (
       id INT AUTO_INCREMENT PRIMARY KEY,
       user_id INT NOT NULL,
       user_role VARCHAR(20) NOT NULL DEFAULT 'student',
       title VARCHAR(255) NOT NULL,
       semester INT NOT NULL,
       subject VARCHAR(150) NOT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
   );

   CREATE TABLE IF NOT EXISTS chat_messages (
       id INT AUTO_INCREMENT PRIMARY KEY,
       session_id INT NOT NULL,
       sender VARCHAR(20) NOT NULL,
       message LONGTEXT NOT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE
   );
   ```

7. If your MySQL username or password differs from the local defaults, update the database settings in `db.php` and `ai_backend/db_config.py`.
8. Open a terminal in the project folder and install PHP dependencies:

   ```powershell
   composer install
   ```

## 2. Set Up the AI Assistant

1. Create a file named `.env` inside the `ai_backend` folder.
2. Add your own Gemini API key to that file:

   ```text
   GEMINI_API_KEY=your_gemini_api_key
   ```

3. Open a terminal and run:

   ```powershell
   cd C:\wamp64\www\INMCA-Resource-Portal\ai_backend
   py -3.11 -m venv .venv
   .\.venv\Scripts\Activate.ps1
   pip install Flask google-genai python-dotenv pypdf python-docx mysql-connector-python
   python app.py
   ```

4. Keep this terminal open while using the AI Assistant.

## 3. Open the Portal

Open `http://localhost/INMCA-Resource-Portal/` in your browser.

## Security

Never upload `.env`, Gemini API keys, passwords, or private uploaded materials to GitHub. Keep your actual API key in your local `ai_backend/.env` file only.

