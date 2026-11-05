<?php
session_start();
include 'db.php';

// Allow only logged-in faculty
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'faculty') {
    header("Location: login.php");
    exit();
}

// Get faculty name from session
$faculty_name = $_SESSION['name']
    ?? $_SESSION['user_name']
    ?? 'Faculty Member';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    

    <title>Faculty Dashboard - INMCA Resources Portal</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #ecedf9;
            color: #1e293b;
           
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ================= NAVBAR ================= */

        .navbar {
            height: 74px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 45px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;
            text-decoration: none;
        }

        .brand img {
            width: 44px;
            height: 44px;
            object-fit: contain;
            border-radius: 8px;
        }

        .brand-name {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .faculty-badge {
            margin-left: 8px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            color: #2563eb;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            text-transform: uppercase;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .nav-links a {
            text-decoration: none;
            color: #475569;
            font-size: 14px;
            font-weight: 500;
            padding: 9px 15px;
            border-radius: 9px;
            transition: 0.2s;
        }

        .nav-links a:hover {
            color: #2563eb;
            background: #f1f5f9;
        }

        .nav-links a.active {
            color: #2563eb;
            background: #eff6ff;
            font-weight: 600;
        }
        .ai-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    padding: 12px 24px;
    background: #a9c3f9;
    color: white;

    text-decoration: none;
    font-size: 15px;
    font-weight: 600;

    border-radius: 8px;
    border: none;

    cursor: pointer;
    transition: 0.2s ease;
}

.ai-button:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}    

        .logout {
            margin-left: 8px;
            color: #dc2626 !important;
            background: #fef2f2;
            border: 1px solid #fee2e2;
        }

        .logout:hover {
            background: #fee2e2 !important;
            color: #b91c1c !important;
        }

        /* ================= MAIN ================= */

        .main {
            flex: 1;
            width: 100%;
            max-width: 1050px;
            margin: auto;
            padding: 70px 30px 80px;
        }

        /* ================= WELCOME ================= */

        .welcome {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 55px;
        }

        .welcome-label {
            display: inline-block;
            padding: 6px 14px;
            margin-bottom: 15px;
            border-radius: 20px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .welcome h1 {
            font-size: 36px;
            line-height: 1.2;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 15px;
        }

        .welcome h1 span {
            color: #4b60fb;
            font-style: italic;
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            font-size: 40px;
        }

        .welcome p {
            font-size: 15px;
            line-height: 1.7;
            color: #64748b;
        }

        /* ================= CARDS ================= */

        .cards {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: center;
            max-width: 850px;
            margin: auto;
        }

        .card {
            width: 400px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 38px 34px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(15, 23, 42, 0.04);
            transition: 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: #2563eb;
            opacity: 0;
            transition: 0.25s;
        }

        .card:hover {
            transform: translateY(-6px);
            border-color: #bfdbfe;
            box-shadow: 0 18px 35px rgba(37, 99, 235, 0.10);
        }

        .card:hover::before {
            opacity: 1;
        }

        .icon {
            width: 75px;
            height: 75px;
            margin: 0 auto 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            font-size: 32px;
        }

        .card h2 {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }

        .card p {
            font-size: 14px;
            line-height: 1.6;
            color: #64748b;
            min-height: 68px;
            margin-bottom: 28px;
        }

        .card-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            width: 100%;
            padding: 13px 20px;
            border-radius: 10px;
            text-decoration: none;
            color: #ffffff;
            background: #2563eb;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
        }

        .card-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .arrow {
            font-size: 18px;
            transition: 0.2s;
        }

        .card-btn:hover .arrow {
            transform: translateX(4px);
        }

        /* ================= FOOTER ================= */

        .footer {
            text-align: left;
            padding: 22px;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
            color: #94a3b8;
            font-size: 12px;
        }
      @media (max-width: 600px) {
    .navbar {
        height: auto;
        min-height: 74px;
        padding: 12px 16px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .brand-name {
        font-size: 15px;
    }

    .nav-links {
        width: 100%;
        display: grid;
        flex-wrap: wrap;
        justify-content: flex-start;
        gap: 6px;
        grid-template-columns: repeat(4, max-content);
    }

    .nav-links a {
        padding: 7px 10px;
        font-size: 12px;
        border-radius: 8px;
        background: #f1f5f9;
    }
    .nav-links a.active {
    background: #eff6ff;
    color: #2563eb;
}

.nav-links a.ai-button {
    background: #2563eb;
    color: white;
}

.nav-links a.logout {
    background: #fef2f2;
    color: #dc2626;
}
    .main {
        padding: 36px 16px 48px;
    }

    .welcome {
        margin-bottom: 32px;
    }

    .welcome h1 {
        font-size: 28px;
    }

    .welcome h1 span {
        font-size: 30px;
    }

    .card {
        width: 100%;
        padding: 28px 22px;
    }

    .footer {
        text-align: center;
    }
}  
    </style>
</head>

<body>

    <!-- ================= NAVBAR ================= -->

    <header class="navbar">

        <a href="faculty_dashboard.php" class="brand">

            <img src="images/logo.svg" alt="INMCA Logo">

            <div>
                <span class="brand-name">
                    INMCA Resources Portal
                </span>

                <span class="faculty-badge">
                    Faculty
                </span>
            </div>

        </a>


        <nav class="nav-links">

            

            <a href="my_uploads.php">
                My Uploads
            </a>

            <a href="all_materials.php">
                All Materials
            </a>
             
            <a href="ai-assistant.php" class="ai-button">
            🤖 AI Assistant
        </a>

            <a href="logout.php" class="logout">
                Logout
            </a>

        </nav>

    </header>


    <!-- ================= MAIN CONTENT ================= -->

    <main class="main">

        <!-- Welcome -->

        <section class="welcome">

            <div class="welcome-label">
                Faculty Workspace
            </div>

            <h1>
                Welcome,
                <span>
                    <?php echo htmlspecialchars($faculty_name); ?>
                </span>
                👋
            </h1>

            <p>
                Welcome to the INMCA Resources Portal.
                Manage and share academic resources with students
                through a simple and organized platform.
            </p>

        </section>


        <!-- Dashboard Cards -->

        <section class="cards">

            <!-- Upload Material -->

            <div class="card">

                <div class="icon">
                    📤
                </div>

                <h2>
                    Upload Material
                </h2>

                <p>
                    Upload notes, syllabus, question papers,
                    lecture slides, and other academic resources
                    for your students.
                </p>

                <a href="upload_material.php" class="card-btn">

                    Upload Material

                    <span class="arrow">
                        →
                    </span>

                </a>

            </div>


            

        </section>

    </main>


    <!-- ================= FOOTER ================= -->

    <footer class="footer">

        © <?php echo date('Y'); ?>
        INMCA Resources Portal. All rights reserved.

    </footer>

</body>

</html>