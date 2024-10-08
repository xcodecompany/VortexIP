<?php
require_once('../config.php');

// Kullanıcı login olmamışsa login sayfasına yönlendirme
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Eğer kullanıcı admin ise, admin paneline yönlendir
if ($_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit();
}

// Kullanıcı bilgilerini çek
$username = $_SESSION['user'];
$sql = "SELECT * FROM users WHERE username='$username'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Paneli</title>
    <link rel="stylesheet" href="/adminlte/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/adminlte/plugins/fontawesome-free/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item d-none d-sm-inline-block">
                <a href="logout.php" class="nav-link">Çıkış Yap</a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar for users -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="#" class="brand-link">
            <span class="brand-text font-weight-light">Kullanıcı Paneli</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="user.php" class="nav-link">
                            <i class="nav-icon fas fa-user"></i>
                            <p>Profil Bilgileri</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- User Panel Content -->
    <div class="content-wrapper">
        <section class="content">
            <div class="container-fluid">
                <h1>Kullanıcı Paneli</h1>
                <p>Hoş geldiniz, <?php echo $user['username']; ?>!</p>
                <p>Bu panelde sadece kendi bilgilerinizi görebilirsiniz.</p>
            </div>
        </section>
    </div>
</div>

<script src="/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/adminlte/dist/js/adminlte.min.js"></script>
</body>
</html>
