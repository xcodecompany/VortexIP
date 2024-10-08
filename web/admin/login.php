<?php
require_once('../config.php');

// Eğer POST isteği varsa login işlemi yapılacak
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Kullanıcının girdiği şifreyi md5 ile hashleyelim
    $hashed_password = md5($password);

    // Kullanıcıyı bul ve durumu aktif mi kontrol et
    $sql = "SELECT * FROM users WHERE username='$username' AND password='$hashed_password' AND status='active'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Şifre doğruysa oturum başlat
        $_SESSION['user'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: admin.php");
        exit();
    } else {
        $error = "Kullanıcı adı veya şifre geçersiz!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <link rel="stylesheet" href="/adminlte/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        <a href="#"><b>Admin</b>Paneli</a>
    </div>

    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Giriş yapmak için bilgilerinizi girin</p>
            <?php if (isset($error)) { echo "<p class='text-danger'>$error</p>"; } ?>
            <form action="login.php" method="post">
                <div class="input-group mb-3">
                    <input type="text" name="username" class="form-control" placeholder="Kullanıcı Adı" required>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Şifre" required>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block">Giriş Yap</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
