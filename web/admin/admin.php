<?php
require_once('../config.php');

// Kullanıcı login olmamışsa login sayfasına yönlendirme
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Eğer kullanıcı admin değilse, kullanıcı paneline yönlendir
if ($_SESSION['role'] !== 'admin') {
    header("Location: user.php"); // Normal kullanıcılar user.php sayfasına yönlendirilir
    exit();
}

// Kullanıcı ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // Şifreyi md5 ile hashleyelim
    $role = $_POST['role'];

    $sql = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
    if ($conn->query($sql) === TRUE) {
        echo "Yeni kullanıcı başarıyla eklendi!";
    } else {
        echo "Kullanıcı eklenirken hata oluştu: " . $conn->error;
    }
}

// Kullanıcıları listeleme
$sql = "SELECT * FROM users";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link rel="stylesheet" href="/adminlte/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/adminlte/plugins/fontawesome-free/css/all.min.css">

</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav ml-auto"> <!-- Navbar öğelerini sağa yaslamak için ml-auto ekledik -->
            <li class="nav-item d-none d-sm-inline-block">
                <a href="admin.php" class="nav-link">Ana Sayfa</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="logout.php" class="nav-link">Çıkış Yap</a> <!-- Logout butonu sağda olacak -->
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="#" class="brand-link">
            <span class="brand-text font-weight-light">VortexIP Admin</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="admin.php" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Kullanıcı Yönetimi</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <section class="content">
            <div class="container-fluid">
                <h1>Kullanıcı Yönetimi</h1>
                <h2>Yeni Kullanıcı Ekle</h2>
                <form method="POST" action="admin.php">
                    <div class="form-group">
                        <label for="username">Kullanıcı Adı:</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Şifre:</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Rol:</label>
                        <select name="role" class="form-control">
                            <option value="user">Kullanıcı</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Ekle</button>
                </form>

                <h2>Mevcut Kullanıcılar</h2>
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kullanıcı Adı</th>
                        <th>Rol</th>
                        <th>Durum</th>
                        <th>Oluşturulma Tarihi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['id'] . "</td>";
                            echo "<td>" . $row['username'] . "</td>";
                            echo "<td>" . $row['role'] . "</td>";
                            echo "<td>" . $row['status'] . "</td>";
                            echo "<td>" . $row['created_at'] . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>Kullanıcı bulunamadı</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- AdminLTE Scripts -->
    <script src="/adminlte/plugins/jquery/jquery.min.js"></script>
    <script src="/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/adminlte/dist/js/adminlte.min.js"></script>
</div>
</body>
</html>
