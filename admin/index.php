<?php

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

$galleryCount = $pdo
    ->query("SELECT COUNT(*) FROM gallery_items")
    ->fetchColumn();

$projectCount = 0;
$directorCount = 0;
$announcementCount = 0;

try {
    $projectCount = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
} catch (Exception $e) {}

try {
    $directorCount = $pdo->query("SELECT COUNT(*) FROM directors")->fetchColumn();
} catch (Exception $e) {}

try {
    $announcementCount = $pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
} catch (Exception $e) {}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard - Pingaksh Renewables</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f7fb;
    color: #1f2937;
}

/* SIDEBAR */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;

    width: 250px;

    background: #071b3a;
    color: white;

    padding: 25px 15px;
}

.logo {
    padding: 0 15px 30px;
}

.logo h2 {
    margin: 0;
    font-size: 20px;
}

.logo span {
    color: #8fb8ef;
    font-size: 13px;
}

.menu {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.menu a {
    color: #d7e3f5;
    text-decoration: none;

    padding: 13px 15px;
    border-radius: 8px;

    font-size: 14px;
}

.menu a:hover,
.menu a.active {
    background: #0d4f97;
    color: white;
}

.logout {
    position: absolute;
    bottom: 25px;
    left: 15px;
    right: 15px;
}

.logout a {
    display: block;
    background: #b91c1c;
    color: white;
    text-align: center;
    padding: 12px;
    border-radius: 8px;
    text-decoration: none;
}

/* MAIN */

.main {
    margin-left: 250px;
    padding: 30px;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;

    margin-bottom: 30px;
}

.topbar h1 {
    margin: 0;
    font-size: 28px;
}

.user {
    background: white;
    padding: 10px 15px;
    border-radius: 8px;
}

/* CARDS */

.cards {
    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 20px;
}

.card {
    background: white;
    padding: 22px;
    border-radius: 12px;

    box-shadow: 0 3px 15px rgba(0,0,0,.05);
}

.card p {
    color: #6b7280;
    margin: 0 0 10px;
}

.card h2 {
    margin: 0;
    font-size: 30px;
}

/* QUICK */

.quick {
    margin-top: 30px;

    background: white;
    padding: 25px;

    border-radius: 12px;
}

.quick h2 {
    margin-top: 0;
}

.quick-links {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.quick-links a {
    background: #0b4f9c;
    color: white;

    text-decoration: none;

    padding: 12px 18px;

    border-radius: 7px;
}

/* MOBILE */

@media(max-width: 800px) {

    .sidebar {
        width: 70px;
    }

    .logo h2,
    .logo span,
    .menu a span {
        display: none;
    }

    .main {
        margin-left: 70px;
    }

    .cards {
        grid-template-columns: repeat(2, 1fr);
    }
}

</style>

</head>

<body>

<aside class="sidebar">

    <div class="logo">
        <h2>Pingaksh</h2>
        <span>Renewables CMS</span>
    </div>

    <nav class="menu">

  <!-- Sidebar -->

<a href="index.php" class="active">
    🏠 <span>Dashboard</span>
</a>

<a href="gallery/index.php">
    🖼️ <span>Gallery</span>
</a>

<a href="projects/index.php">
    📁 <span>Projects</span>
</a>

<a href="directors/index.php">
    👤 <span>Directors</span>
</a>

<a href="announcements/index.php">
    📢 <span>Announcements</span>
</a>

<a href="documents/index.php">
    📄 <span>Documents</span>
</a>

<a href="#">
    ⚙️ <span>Settings</span>
</a>

<div class="logout">
    <a href="logout.php">
        Logout
    </a>
</div>

    </nav>

    <div class="logout">
        <a href="/admin/logout.php">
            Logout
        </a>
    </div>

</aside>


<main class="main">

    <div class="topbar">

        <div>
            <h1>Dashboard</h1>
            <p>Manage your website content.</p>
        </div>

        <div class="user">
            👋 <?= htmlspecialchars($_SESSION['admin_name']) ?>
        </div>

    </div>


    <div class="cards">

        <div class="card">
            <p>Gallery Items</p>
            <h2><?= $galleryCount ?></h2>
        </div>

        <div class="card">
            <p>Projects</p>
            <h2><?= $projectCount ?></h2>
        </div>

        <div class="card">
            <p>Directors</p>
            <h2><?= $directorCount ?></h2>
        </div>

        <div class="card">
            <p>Announcements</p>
            <h2><?= $announcementCount ?></h2>
        </div>

    </div>


    <div class="quick">

        <h2>Quick Actions</h2>

        <div class="quick-links">

            <a href="/admin/gallery/create.php">
                + Add Gallery Media
            </a>

            <a href="/admin/projects/create.php">
                + Add Project
            </a>

            <a href="/admin/announcements/create.php">
                + Add Announcement
            </a>

        </div>

    </div>

</main>

</body>
</html>