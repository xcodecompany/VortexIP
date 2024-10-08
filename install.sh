#!/bin/bash

# /home/VortexIP dizinini oluşturma ve yetkilendirme
VORTEX_HOME="/home/VortexIP"
if [ ! -d "$VORTEX_HOME" ]; then
    mkdir -p "$VORTEX_HOME"
fi
chmod 755 "$VORTEX_HOME"

# Menü tabanlı kurulum işlemi
function menu {
    echo "---------------------"
    echo "VortexIP Kurulum Menüsü"
    echo "---------------------"
    echo "1) Kur"
    echo "2) Kaldır"
    echo "3) Çıkış"
    echo "Seçiminizi yapın:"
    read choice

    case $choice in
        1) kur ;;
        2) kaldir ;;
        3) cikis ;;
        *) echo "Geçersiz seçim. Tekrar deneyin."; menu ;;
    esac
}

# Kurulum Fonksiyonu
function kur {
    echo "VortexIP Sistem Kurulumu Başlıyor..."

    # Sunucu güncellemesi
    sudo apt update -y && sudo apt upgrade -y

    # Gerekli dizinlerin oluşturulması
    mkdir -p $VORTEX_HOME/logs
    mkdir -p $VORTEX_HOME/config
    mkdir -p $VORTEX_HOME/web
    chmod 755 $VORTEX_HOME/logs
    chmod 755 $VORTEX_HOME/config
    chmod 755 $VORTEX_HOME/web

    # Nginx kurulumu ve yapılandırılması
    sudo apt install nginx -y
    sudo systemctl start nginx
    sudo systemctl enable nginx

    # PHP ve gerekli modüllerin kurulumu
    sudo apt install php-fpm php-mysql -y

    # Nginx için PHP-FPM yapılandırması
    sudo sed -i 's/index index.html/index index.php index.html/' /etc/nginx/sites-available/default
    sudo sed -i '/location \/ {/a\        try_files $uri $uri/ =404;' /etc/nginx/sites-available/default
    sudo sed -i '/location ~ \\.php$ {/a\        include snippets/fastcgi-php.conf;\n        fastcgi_pass unix:/var/run/php/php-fpm.sock;\n        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n        include fastcgi_params;' /etc/nginx/sites-available/default
    sudo systemctl restart nginx
    sudo systemctl restart php-fpm

    # MariaDB kurulumu ve root erişimi yapılandırması
    sudo apt install mariadb-server mariadb-client -y
    sudo systemctl start mariadb
    sudo systemctl enable mariadb

    # MariaDB root için native_password ayarı (root erişimi phpMyAdmin üzerinden sağlanacak)
    sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('YourRootPassword');"
    sudo mysql -e "FLUSH PRIVILEGES;"

    # PHPMyAdmin kurulumu ve sembolik link oluşturma
    sudo apt install phpmyadmin -y
    sudo ln -s /usr/share/phpmyadmin /home/VortexIP/web/phpmyadmin

    # Squid proxy kurulumu
    sudo apt install squid -y
    sudo systemctl enable squid
    sudo systemctl start squid

    # HAProxy kurulumu (Rotating proxy için)
    sudo apt install haproxy -y
    sudo systemctl enable haproxy
    sudo systemctl start haproxy

    # UFW kurulumu ve ayarları (MariaDB dışarıya açılmadı, sadece local)
    sudo apt install ufw -y
    sudo ufw allow OpenSSH   # SSH (22)
    sudo ufw allow 'Nginx Full'  # HTTP (80) ve HTTPS (443)
    sudo ufw allow 3128  # Squid default port (3128)
    sudo ufw enable

    echo "Kurulum tamamlandı. Tüm servisler aktif."
}

# Kaldırma Fonksiyonu
function kaldir {
    echo "VortexIP Sistem Kaldırma İşlemi Başlıyor..."

    # Servisleri durdur ve devre dışı bırak
    sudo systemctl stop nginx
    sudo systemctl disable nginx

    sudo systemctl stop mariadb
    sudo systemctl disable mariadb

    sudo systemctl stop squid
    sudo systemctl disable squid

    sudo systemctl stop haproxy
    sudo systemctl disable haproxy

    # Paketleri kaldırma
    sudo apt remove --purge nginx php-fpm php-mysql mariadb-server mariadb-client phpmyadmin squid haproxy -y

    # Kalan yapılandırma dosyalarını temizleme
    sudo apt autoremove -y
    sudo apt autoclean -y

    # VortexIP dizinini ve tüm alt dizinlerini kaldır
    sudo rm -rf $VORTEX_HOME

    echo "VortexIP sistem ve dosyalar kaldırıldı."
}

# Çıkış Fonksiyonu
function cikis {
    echo "Çıkış yapılıyor..."
    exit 0
}

# Menüye yönlendirme
menu
