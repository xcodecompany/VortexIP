<?php
require_once('../config.php');

// Kullanıcı login olmamışsa login sayfasına yönlendirme
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Eğer kullanıcı admin değilse, kullanıcı paneline yönlendir
if ($_SESSION['role'] !== 'admin') {
    header("Location: user.php");
    exit();
}

// Silme işlemi (GET ile gelen 'delete_port' parametresi varsa)
if (isset($_GET['delete_port']) && isset($_GET['user_id'])) {
    $port_id = $_GET['delete_port'];
    $user_id = $_GET['user_id'];

    // Önce proxies tablosundan portu silelim, bu silme işlemi V6 IP'leri de silecek (ON DELETE CASCADE ile)
    $delete_sql = "DELETE FROM proxies WHERE id='$port_id' AND user_id='$user_id'";
    if ($conn->query($delete_sql) === TRUE) {
        echo "Port ve ilgili V6 IP'ler başarıyla silindi.";
    } else {
        echo "Silme işlemi sırasında hata oluştu: " . $conn->error;
    }
}

// Sunucunun varsayılan IP'sini alalım
$server_ip = $_SERVER['SERVER_ADDR'];

// GET ile gelen user_id'yi al
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Kullanıcı bilgilerini çek
    $sql = "SELECT * FROM users WHERE id='$user_id'";
    $result = $conn->query($sql);
    $user = $result->fetch_assoc();

    // Kullanıcıya atanmış proxyleri çek
    $proxy_sql = "SELECT * FROM proxies WHERE user_id='$user_id'";
    $proxy_result = $conn->query($proxy_sql);

    // Proxy atama işlemi
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $ipv4_ip = $_POST['ipv4_ip'];  // IPv4 IP Adresi
        $v4_port_count = $_POST['v4_port_count'];  // V4 Port Sayısı
        $v6_ip_count = $_POST['v6_ip_count'];  // Her V4 Port için V6 IP Sayısı

        // Son atanan V4 portu bulmak için en son kullanılan portu bulalım
        $last_port_sql = "SELECT MAX(port) as last_port FROM proxies";
        $last_port_result = $conn->query($last_port_sql);
        $last_port_row = $last_port_result->fetch_assoc();
        $last_port = $last_port_row['last_port'] ?? 3000; // Eğer hiç port yoksa 3000'den başlayacak

        // V4 portları ekleyelim
        for ($i = 0; $i < $v4_port_count; $i++) {
            $new_port = $last_port + $i + 1; // Yeni port numarasını hesapla

            // V4 portunu ekleyelim
            $sql = "INSERT INTO proxies (user_id, ipv4_ip, port) VALUES ('$user_id', '$ipv4_ip', '$new_port')";
            if ($conn->query($sql) === TRUE) {
                $proxy_id = $conn->insert_id; // Eklenen proxy'nin ID'sini alalım

                // Her V4 port için V6 IP'leri ekleyelim
                for ($j = 0; $j < $v6_ip_count; $j++) {
                    // Benzersiz bir V6 IP oluşturuyoruz
                    $ipv6_ip = "2001:db8::" . uniqid();

                    // V6 IP'yi V4 port numarasıyla birlikte ekle
                    $sql = "INSERT INTO ipv6_proxies (proxy_id, ipv6_ip) VALUES ('$proxy_id', CONCAT('$ipv6_ip', ':$new_port'))";
                    $conn->query($sql); // V6 IP'sini ekle
                }
            } else {
                echo "Port ataması sırasında hata oluştu: " . $conn->error;
            }
        }

        echo "Proxy başarıyla atandı!";
    }
} else {
    echo "Kullanıcı ID'si bulunamadı!";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proxy Atama</title>
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
                <h1><?php echo $user['username']; ?> için Proxy Atama</h1>

                <!-- Proxy Atama Formu -->
                <form method="POST" action="admin_proxy.php?user_id=<?php echo $user_id; ?>">
                    <div class="form-group">
                        <label for="ipv4_ip">IPv4 IP:</label>
                        <small class="form-text text-muted">Bu alan, kullanıcının atanacağı IPv4 IP'yi temsil eder. Varsayılan olarak sunucunun IP'si gelir.</small>
                        <input type="text" name="ipv4_ip" class="form-control" value="<?php echo $server_ip; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="v4_port_count">Kaç adet V4 Port oluşturulacak:</label>
                        <input type="number" name="v4_port_count" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="v6_ip_count">Her V4 Port için Kaç Adet V6 IP oluşturulacak:</label>
                        <input type="number" name="v6_ip_count" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Proxy Ata</button>
                </form>

                <!-- Kullanıcıya atanmış proxyler -->
                <h2>Atanmış Proxyler</h2>
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>IPv4 Proxy</th>
                        <th>Port</th>
                        <th>Oluşturulma Tarihi</th>
                        <th>İşlem</th> <!-- Silme işlemi için bir sütun ekliyoruz -->
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($proxy_result->num_rows > 0) {
                        while ($proxy = $proxy_result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $proxy['ipv4_ip'] . "</td>";
                            echo "<td>" . $proxy['port'] . "</td>";
                            echo "<td>" . $proxy['created_at'] . "</td>";
                            // Her port için bir "Sil" butonu ekliyoruz
                            echo "<td><a href='admin_proxy.php?delete_port=" . $proxy['id'] . "&user_id=" . $user['id'] . "' class='btn btn-danger'>Sil</a></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>Henüz proxy atanmadı.</td></tr>";
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
