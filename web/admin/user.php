<?php
require_once('../config.php');

// Kullanıcı login değilse login sayfasına yönlendirme
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Eğer kullanıcı admin ise, admin paneline yönlendirme
if ($_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit();
}

// Kullanıcı bilgilerini çek
$username = $_SESSION['user'];
$sql = "SELECT * FROM users WHERE username='$username'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

// Kullanıcının mevcut beyaz liste IP'sini kontrol et
$user_id = $user['id'];
$whitelist_sql = "SELECT * FROM whitelist_ips WHERE user_id='$user_id'";
$whitelist_result = $conn->query($whitelist_sql);
$has_whitelist_ip = $whitelist_result->num_rows > 0;

// Proxy bilgilerini çek
$sql = "SELECT ipv4_ip, port, created_at, end_date FROM proxies WHERE user_id='$user_id'";
$proxy_result = $conn->query($sql);

// Kalan gün ve saat hesaplama fonksiyonu
function calculate_remaining_time($end_date) {
    $now = new DateTime();
    $end_datetime = new DateTime($end_date);
    if ($end_datetime > $now) {
        $interval = $now->diff($end_datetime);
        return $interval->format('%d gün %h saat');
    } else {
        return "Süre doldu";
    }
}

// Info bar için kalan gün hesaplama
$info_list = [];
while ($proxy = $proxy_result->fetch_assoc()) {
    $days_left = calculate_remaining_time($proxy['end_date']);
    $info_list[] = [
        'ipv4_ip' => $proxy['ipv4_ip'],
        'port' => $proxy['port'],
        'days_left' => $days_left
    ];
}

// Yeni IP ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$has_whitelist_ip) {
    $ip_address = $_POST['ip_address'];
    $sql = "INSERT INTO whitelist_ips (user_id, ip_address) VALUES ('$user_id', '$ip_address')";
    if ($conn->query($sql) === TRUE) {
        header("Location: user.php");
        exit();
    } else {
        echo "IP eklenirken hata oluştu: " . $conn->error;
    }
}

// Beyaz liste IP silme işlemi
if (isset($_GET['delete_ip'])) {
    $ip_id = $_GET['delete_ip'];
    $sql = "DELETE FROM whitelist_ips WHERE id='$ip_id'";
    if ($conn->query($sql) === TRUE) {
        header("Location: user.php");
        exit();
    } else {
        echo "IP silinirken hata oluştu: " . $conn->error;
    }
}
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

    <!-- Sidebar -->
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

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <section class="content">
            <div class="container-fluid">
                <h1>Kullanıcı Paneli</h1>
                <p>Hoş geldiniz, <?php echo $user['username']; ?>!</p>

                <!-- Info Bar for Proxy Usage -->
                <h2>Paket Bilgileri</h2>
                <div class="alert alert-info">
                    Kalan Süre: <?php echo $info_list[0]['days_left']; ?>
                </div>

                <!-- Whitelist IP Yönetimi -->
                <h2>Whitelist IP Yönetimi</h2>
                <?php if ($has_whitelist_ip): ?>
                    <p>Mevcut Whitelist IP Adresiniz: <?php $whitelist = $whitelist_result->fetch_assoc(); echo $whitelist['ip_address']; ?></p>
                    <a href="user.php?delete_ip=<?php echo $whitelist['id']; ?>" class="btn btn-danger">IP'yi Sil</a>
                <?php else: ?>
                    <form method="POST">
                        <label for="ip_address">Yeni IP Adresi Ekle:</label>
                        <input type="text" name="ip_address" placeholder="IP adresi" required>
                        <button type="submit" class="btn btn-primary">IP Ekle</button>
                    </form>
                <?php endif; ?>

                <!-- Proxy Listesi -->
                <h2>Proxy Listesi</h2>
                <pre style="background-color: #f8f9fa; border: 1px solid #ced4da; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; white-space: pre-wrap;">
<?php
if ($proxy_result->num_rows > 0) {
    foreach ($info_list as $info) {
        echo htmlspecialchars($info['ipv4_ip'] . ':' . $info['port']) . "\n";
    }
} else {
    echo "Henüz proxy atanmadı.\n";
}
?>
                </pre>

            </div>
        </section>
    </div>
</div>

<script src="/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/adminlte/dist/js/adminlte.min.js"></script>
</body>
</html>
