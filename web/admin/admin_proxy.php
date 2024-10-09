<?php
require_once('../config.php');

// Admin kontrolü
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Silme işlemi
if (isset($_POST['delete_selected']) && isset($_POST['selected_proxies'])) {
    $selected_proxies = $_POST['selected_proxies'];
    $proxy_ids = implode(",", $selected_proxies);
    $delete_sql = "DELETE FROM proxies WHERE id IN ($proxy_ids)";
    if ($conn->query($delete_sql) === TRUE) {
        echo "Seçilen portlar başarıyla silindi.";
    } else {
        echo "Silme işlemi sırasında hata oluştu: " . $conn->error;
    }
}

// Toplu tarih güncelleme işlemi
if (isset($_POST['update_selected']) && isset($_POST['selected_proxies']) && isset($_POST['new_end_date'])) {
    $selected_proxies = $_POST['selected_proxies'];
    $new_end_date = $_POST['new_end_date'] . ' ' . date('H:i:s'); // Bitiş tarihine saat ekliyoruz
    $proxy_ids = implode(",", $selected_proxies);
    $update_sql = "UPDATE proxies SET end_date='$new_end_date' WHERE id IN ($proxy_ids)";
    if ($conn->query($update_sql) === TRUE) {
        echo "Seçilen portların bitiş tarihi başarıyla güncellendi.";
    } else {
        echo "Tarih güncelleme işlemi sırasında hata oluştu: " . $conn->error;
    }
}

// Sunucu IP'si ve kullanıcı ID'si
$server_ip = $_SERVER['SERVER_ADDR'];
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $sql = "SELECT * FROM users WHERE id='$user_id'";
    $result = $conn->query($sql);
    $user = $result->fetch_assoc();
    $proxy_sql = "SELECT * FROM proxies WHERE user_id='$user_id'";
    $proxy_result = $conn->query($proxy_sql);

    // Proxy atama işlemi
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['delete_selected']) && !isset($_POST['update_selected'])) {
        $ipv4_ip = $_POST['ipv4_ip'];
        $v4_port_count = $_POST['v4_port_count'];
        $v6_ip_count = $_POST['v6_ip_count'];
        $end_date = $_POST['end_date'];

        // Bitiş tarihine otomatik saat ekleyelim
        $end_datetime = $end_date . ' ' . date('H:i:s');

        $last_port_sql = "SELECT MAX(port) as last_port FROM proxies";
        $last_port_result = $conn->query($last_port_sql);
        $last_port_row = $last_port_result->fetch_assoc();
        $last_port = $last_port_row['last_port'] ?? 3000;

        // V4 ve V6 IP'ler ile birlikte proxy ekleme
        for ($i = 0; $i < $v4_port_count; $i++) {
            $new_port = $last_port + $i + 1;
            $sql = "INSERT INTO proxies (user_id, ipv4_ip, port, end_date) VALUES ('$user_id', '$ipv4_ip', '$new_port', '$end_datetime')";
            if ($conn->query($sql) === TRUE) {
                $proxy_id = $conn->insert_id;
                for ($j = 0; $j < $v6_ip_count; $j++) {
                    $ipv6_ip = "2001:db8::" . uniqid();
                    $sql = "INSERT INTO ipv6_proxies (proxy_id, ipv6_ip) VALUES ('$proxy_id', CONCAT('$ipv6_ip', ':$new_port'))";
                    $conn->query($sql);
                }
            } else {
                echo "Port ataması sırasında hata oluştu: " . $conn->error;
            }
        }

        // Form işlemi tamamlandıktan sonra yeniden yönlendir
        header("Location: admin_proxy.php?user_id=$user_id");
        exit();
    }
} else {
    echo "Kullanıcı ID'si bulunamadı!";
    exit();
}

// Kalan gün ve saat hesaplama
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proxy Atama</title>
    <link rel="stylesheet" href="/adminlte/dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a href="logout.php" class="nav-link">Çıkış Yap</a>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="#" class="brand-link">
            <span class="brand-text font-weight-light">VortexIP Admin</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" role="menu" data-accordion="false">
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

    <div class="content-wrapper">
        <section class="content">
            <div class="container-fluid">
                <h1><?php echo $user['username']; ?> için Proxy Atama</h1>

                <!-- Proxy Atama Formu -->
                <form method="POST" action="admin_proxy.php?user_id=<?php echo $user_id; ?>">
                    <div class="form-group">
                        <label for="ipv4_ip">IPv4 IP:</label>
                        <small class="form-text text-muted">Varsayılan olarak sunucunun IP'si gelir.</small>
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
                    <div class="form-group">
                        <label for="end_date">Bitiş Tarihi:</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Proxy Ata</button>
                </form>

                <!-- Kullanıcıya atanmış proxyler ve toplu işlemler -->
                <h2>Atanmış Proxyler</h2>
                <form method="POST" action="admin_proxy.php?user_id=<?php echo $user_id; ?>">
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th><input type="checkbox" id="select_all" /> Tümü Seç</th>
                            <th>IPv4 Proxy</th>
                            <th>Port</th>
                            <th>Oluşturulma Tarihi</th>
                            <th>Bitiş Tarihi</th>
                            <th>Kalan Süre</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($proxy_result->num_rows > 0) {
                            while ($proxy = $proxy_result->fetch_assoc()) {
                                $remaining_time = calculate_remaining_time($proxy['end_date']);
                                echo "<tr>";
                                echo "<td><input type='checkbox' name='selected_proxies[]' value='" . $proxy['id'] . "' /></td>";
                                echo "<td>" . $proxy['ipv4_ip'] . "</td>";
                                echo "<td>" . $proxy['port'] . "</td>";
                                echo "<td>" . $proxy['created_at'] . "</td>";
                                echo "<td>" . $proxy['end_date'] . "</td>";
                                echo "<td>" . $remaining_time . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>Henüz proxy atanmadı.</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>

                    <!-- Toplu Silme ve Toplu Güncelleme -->
                    <div class="form-group">
                        <label for="new_end_date">Yeni Bitiş Tarihi (tarih güncelleme için):</label>
                        <input type="date" name="new_end_date" class="form-control">
                    </div>
                    <button type="submit" name="delete_selected" class="btn btn-danger">Seçilenleri Sil</button>
                    <button type="submit" name="update_selected" class="btn btn-warning">Tarih Güncelle</button>
                </form>
            </div>
        </section>
    </div>

    <script src="/adminlte/plugins/jquery/jquery.min.js"></script>
    <script src="/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/adminlte/dist/js/adminlte.min.js"></script>

    <script>
        // Tüm checkbox'ları seçme/deselect etme
        document.getElementById('select_all').addEventListener('click', function(event) {
            const checkboxes = document.querySelectorAll('input[name="selected_proxies[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = event.target.checked;
            });
        });
    </script>
</div>
</body>
</html>
