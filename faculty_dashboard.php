
<?php
session_start();

// Faculty name from session
$faculty_name = $_SESSION['name'] ?? 'Faculty';
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Faculty Dashboard - INMCA Resource Portal</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5f7fb;
            min-height: 100vh;
        }

        /* Navbar */

        .navbar {
            height: 70px;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
        }

        .logo img {
            width: 45px;
            height: 45px;
            object-fit: contain;
        }

        .nav-links {
            display: flex;
            gap: 25px;
        }

        .nav-links a {
            text-decoration: none;
            color: #374151;
            font-weight: 500;
        }

        .nav-links a:hover {
            color: #1a73e8;
        }

        /* Main */

        .container {
            max-width: 1100px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .welcome {
            margin-bottom: 35px;
        }

        .welcome h1 {
            color: #1f2937;
            margin-bottom: 8px;
        }

        .welcome p {
            color: #6b7280;
        }

        /* Cards */

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .dashboard-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            text-decoration: none;
            color: #1f2937;
            box-shadow: 0 5px 18px rgba(0,0,0,0.08);
            transition: 0.2s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .icon {
            font-size: 42px;
            margin-bottom: 15px;
        }

        .dashboard-card h2 {
            font-size: 19px;
            margin-bottom: 8px;
        }

        .dashboard-card p {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.5;
        }

        /* Footer */

        footer {
            text-align: center;
            padding: 25px;
            margin-top: 60px;
            color: #6b7280;
            font-size: 14px;
        }

        /* Responsive */

        @media (max-width: 800px) {

            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .navbar {
                padding: 0 20px;
            }

        }

        @media (max-width: 550px) {

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .nav-links {
                gap: 10px;
            }

        }

    </style>

</head>

<body>


<!-- Navbar -->

<header>

    <nav class="navbar">

        <div class="logo">

            <img src="images/logo (3).png"
                 alt="INMCA Resources Logo">

            <span>INMCA Resource Portal</span>

        </div>


        <div class="nav-links">

            <a href="faculty-dashboard.php">
                Dashboard
            </a>

            <a href="logout.php">
                Logout
            </a>

        </div>

    </nav>

</header>


<!-- Main Content -->

<main class="container">

    <div class="welcome">

        <h1>
            Welcome, <?php echo htmlspecialchars($faculty_name); ?> 👋
        </h1>

        <p>
            Manage and share academic resources with students.
        </p>

    </div>


    <!-- Dashboard Cards -->

    <div class="dashboard-grid">


        <!-- Upload Material -->

        <a href="upload_material.php"
           class="dashboard-card">

            <div class="icon">
                📤
            </div>

            <h2>
                Upload Material
            </h2>

            <p>
                Upload notes, syllabus,
                question papers and study materials.
            </p>

        </a>


        <!-- My Uploads -->

        <a href="my_uploads.php"
           class="dashboard-card">

            <div class="icon">
                📚
            </div>

            <h2>
                My Uploads
            </h2>

            <p>
                View the materials uploaded
                by you.
            </p>

        </a>


        <!-- All Resources -->

        <a href="all_resources.php"
           class="dashboard-card">

            <div class="icon">
                📖
            </div>

            <h2>
                All Resources
            </h2>

            <p>
                View resources uploaded
                by all faculty members.
            </p>

        </a>


        <!-- Search -->

        <a href="search_resources.php"
           class="dashboard-card">

            <div class="icon">
                🔍
            </div>

            <h2>
                Search Resources
            </h2>

            <p>
                Search resources by semester,
                subject or resource type.
            </p>

        </a>


        <!-- Profile -->

        <a href="faculty_profile.php"
           class="dashboard-card">

            <div class="icon">
                👤
            </div>

            <h2>
                My Profile
            </h2>

            <p>
                View your faculty account
                information.
            </p>

        </a>


        <!-- Logout -->

        <a href="logout.php"
           class="dashboard-card">

            <div class="icon">
                🚪
            </div>

            <h2>
                Logout
            </h2>

            <p>
                Sign out from your
                faculty account.
            </p>

        </a>


    </div>

</main>


<!-- Footer -->

<footer>

    © 2026 INMCA Resources. Empowering Academic Excellence.

</footer>


</body>

</html>

