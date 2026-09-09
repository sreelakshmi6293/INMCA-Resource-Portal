<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f4f6f9;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Header Navigation */
        .navbar {
            background-color: #1e293b;
            color: #ffffff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar h1 {
            font-size: 20px;
            font-weight: 600;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-logout {
            background-color: #ef4444;
            color: #ffffff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
        }

        .btn-logout:hover {
            background-color: #dc2626;
        }

        /* Container & Layout */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            width: 100%;
            flex: 1;
        }

        .welcome-card {
            background: #ffffff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            border-left: 5px solid #2563eb;
        }

        .welcome-card h2 {
            color: #1e293b;
            font-size: 24px;
            margin-bottom: 6px;
        }

        .welcome-card p {
            color: #64748b;
            font-size: 14px;
        }

        /* Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h3 {
            font-size: 32px;
            color: #2563eb;
            margin-bottom: 4px;
        }

        .stat-card p {
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Dashboard Grid Layout */
        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Content Blocks & Tables */
        .card {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .card h3 {
            color: #1e293b;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 600;
        }

        /* Action Buttons */
        .action-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .action-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #f1f5f9;
            color: #1e293b;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background-color: #2563eb;
            color: #ffffff;
        }

        /* Badges */
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef9c3; color: #854d0e; }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="navbar">
        <h1>Faculty Portal</h1>
        <div class="user-profile">
            <span>Welcome, <strong>Dr. Sarah Connor</strong></span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="container">
        
        <!-- Welcome Card -->
        <div class="welcome-card">
            <h2>Dashboard Overview</h2>
            <p>Welcome back! Here is a summary of your assigned classes, daily schedule, and student tasks.</p>
        </div>

        <!-- Stat Indicators -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>4</h3>
                <p>Assigned Courses</p>
            </div>
            <div class="stat-card">
                <h3>128</h3>
                <p>Total Students</p>
            </div>
            <div class="stat-card">
                <h3 style="color: #d97706;">8</h3>
                <p>Pending Grades</p>
            </div>
            <div class="stat-card">
                <h3 style="color: #16a34a;">2</h3>
                <p>Lectures Today</p>
            </div>
        </div>

        <!-- Main Grid Layout -->
        <div class="main-grid">
            
            <!-- Left Column: Tables -->
            <div>
                <!-- Lecture Schedule -->
                <div class="card">
                    <h3>Today's Schedule</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Course Title</th>
                                <th>Room</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>09:00 AM - 10:30 AM</td>
                                <td>Data Structures & Algorithms</td>
                                <td>Lab 302</td>
                                <td><span class="badge badge-success">Completed</span></td>
                            </tr>
                            <tr>
                                <td>02:00 PM - 03:30 PM</td>
                                <td>Database Management Systems</td>
                                <td>Hall B</td>
                                <td><span class="badge badge-warning">Upcoming</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Submissions -->
                <div class="card">
                    <h3>Recent Submissions</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Assignment</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Alice Johnson</td>
                                <td>SQL Optimization Project</td>
                                <td>Today, 08:30 AM</td>
                                <td><a href="#" style="color: #2563eb; text-decoration: none; font-weight: 600;">Grade</a></td>
                            </tr>
                            <tr>
                                <td>Bob Smith</td>
                                <td>Binary Trees Practice</td>
                                <td>Yesterday</td>
                                <td><a href="#" style="color: #2563eb; text-decoration: none; font-weight: 600;">Grade</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Column: Quick Links -->
            <div>
                <div class="card">
                    <h3>Quick Actions</h3>
                    <div class="action-list">
                        <a href="#" class="action-btn">Take Attendance</a>
                        <a href="#" class="action-btn">Upload Marks</a>
                        <a href="#" class="action-btn">Post Announcement</a>
                        <a href="#" class="action-btn">Create Assignment</a>
                    </div>
                </div>
            </div>

        </div>

    </div>

</body>
</html>