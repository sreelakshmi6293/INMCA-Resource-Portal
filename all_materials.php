<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Select Semester - INMCA Resources</title>

    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
</head>

<body>

    
<header class="navbar">

    <a href="faculty_dashboard.php" class="brand">

        <img src="images/logo.svg" alt="INMCA Logo">

        <div>
            <span class="brand-name">
                INMCA Resources Portal
            </span>

            
        </div>

    </a>

    <nav class="nav-links">

        <a href="faculty_dashboard.php" class="back" style="margin-left: 8px; color: #2563eb; background: #eff6ff; border: 1px solid #dbeafe; padding: 8px 14px; border-radius: 6px; text-decoration: none;">
            Back
        </a>

       
        <a href="logout.php" class="logout">
            Logout
        </a>

    </nav>

</header>




    <main class="semester-page">

        <div class="page-heading">
            <span>ACADEMIC RESOURCES</span>

            <h1>Select Your Semester</h1>

            <p>
                Choose a semester to explore its subjects and resources.
            </p>
        </div>
<div class="semester-search">
    <input type="text" id="subjectSearch" placeholder="Search subject...">
    <button onclick="searchSubject()">Search</button>
</div>

<p id="searchMessage"></p>

        <div class="semester-grid">

            <a href="semester1.html?from=all" class="semester-card">
                <div class="semester-icon">
                    <span class="material-symbols-outlined">menu_book </span>
                </div>
                <h2>Semester 1</h2>
            </a>

            <a href="semester2.html?from=all" class="semester-card">
                <div class="semester-icon">
                    <span class="material-symbols-outlined"> school</span>

                </div>
                <h2>Semester 2</h2>
            </a>

            <a href="semester3.html?from=all" class="semester-card">
                <div class="semester-icon">
                    <span class="material-symbols-outlined">code </span>
                </div>
                <h2>Semester 3</h2>
            </a>

            <a href="semester4.html?from=all" class="semester-card">
                <div class="semester-icon">
                    <span class="material-symbols-outlined">computer </span>
                </div>
                <h2>Semester 4</h2>
            </a>

            <a href="semester5.html?from=all" class="semester-card">
                <div class="semester-icon">
                    <span class="material-symbols-outlined">database </span>
                </div>
                <h2>Semester 5</h2>
            </a>

            <a href="semester6.html?from=all" class="semester-card">
                <div class="semester-icon">
                    <span class="material-symbols-outlined">analytics </span>
                </div>
                <h2>Semester 6</h2>
            </a>

            <a href="semester7.html?from=all" class="semester-card">
                <div class="semester-icon">
                    <span class="material-symbols-outlined">insights </span>
                </div>
                <h2>Semester 7</h2>
            </a>

            <a href="semester8.html?from=all" class="semester-card">
                <div class="semester-icon">
                    <span class="material-symbols-outlined">auto_stories </span>
                </div>
                <h2>Semester 8</h2>
            </a>

            <a href="semester9.html?from=all" class="semester-card">
                <div class="semester-icon">
                    <span class="material-symbols-outlined">developer_mode </span>
                </div>
                <h2>Semester 9</h2>
            </a>

            <a href="semester10.html?from=all" class="semester-card">
                <div class="semester-icon">
                    <span class="material-symbols-outlined">workspace_premium </span>
                </div>
                <h2>Semester 10</h2>
            </a>

        </div>

    </main>
<script>
function searchSubject() {

    const search = document.getElementById("subjectSearch")
        .value.trim().toLowerCase();

    const subjects = {

        "1": [
            "english",
            "basic mathematics",
            "introduction to programming",
            "introduction to computers & pc hardware",
            "fundamentals of accountancy"
        ],

        "2": [
            // Semester 2 subjects
        ],

        "3": [
            "computer organization",
            "probability and statistics",
            "introduction to object oriented programming",
            "accounting and financial management",
            "data structures"
        ],

        "4": [
            "linux/unix fundamentals",
            "statistical applications",
            "operating systems",
            "elements of bussiness management",
            "internet concepts and web technology"
        ],

        "5": [
            "numerical methods",
            "user interface design",
            "introduction to rdbms and sql",
            "fundamentals of information systems security",
            "introduction to operations research"
        ],

        "6": [
            "introduction to software engineering",
            "fundamentals of data science",
            "visual programming",
            "design and analysis of algorithms",
            "mini project 1"
        ],

        "7": [
            "m-commerce",
            "java programming",
            "advanced web programming",
            "advanced software engineering",
            "distributed computing"
        ],

        "8": [
            "advanced database management systems",
            "advanced computer networks",
            "research methodology",
            "organizational behaviour",
            "artificial intelligence"
        ],

        "9": [],

        "10": []
    };

    if (search === "") {
        document.getElementById("searchMessage").innerText =
            "Please enter a subject name.";
        return;
    }

    for (const semester in subjects) {

        const found = subjects[semester].some(subject =>
            subject.includes(search)
        );

        if (found) {
            window.location.href =
                "semester" + semester + ".html?from=all";
            return;
        }
    }

    const message = document.getElementById("searchMessage");
    message.innerText = "Subject not found.";
    message.style.color = "red";
    message.style.textAlign = "center";
}
</script>
<style>
.semester-search {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin: 25px auto;
}

.semester-search input {
    width: 300px;
    padding: 12px 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
}

.semester-search input:focus {
    border-color: #555;
}

.semester-search button {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
}

.searchMessage {
    text-align: center;
    font-size: 14px;
    margin-top: -10px;
    
}
</style>

</body>
</html>