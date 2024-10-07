#!/bin/bash

# Proje ismi ve ana dizin
PROJECT_NAME="VortexIP"
BASE_DIR="/home/${PROJECT_NAME}"
CONF_FILE="${BASE_DIR}/config.conf"
USER_CONF_DIR="${BASE_DIR}/users"
SQUID_DIR="${BASE_DIR}/squid"
SQUID_LOG="${SQUID_DIR}/log"
NGINX_DIR="${BASE_DIR}/nginx"
NGINX_LOG="${NGINX_DIR}/log"

# Gerekli dizinleri oluşturma
setup_directories() {
    echo "Dizinler oluşturuluyor..."
    mkdir -p ${BASE_DIR}
    mkdir -p ${USER_CONF_DIR}
    mkdir -p ${SQUID_DIR}
    mkdir -p ${SQUID_LOG}
    mkdir -p ${NGINX_DIR}
    mkdir -p ${NGINX_LOG}
}

# Sunucunun V4 ve V6 IP'lerini otomatik bul ve config.conf'a yaz
create_config_file() {
    echo "Config dosyası oluşturuluyor..."

    # Sunucunun V4 IP'sini bul
    v4_ip=$(ip -4 addr show scope global | grep inet | awk '{print $2}' | cut -d '/' -f1 | head -n 1)

    # Sunucunun V6 IP'sini bul
    v6_ip=$(ip -6 addr show scope global | grep inet6 | awk '{print $2}' | cut -d '/' -f1 | head -n 1)

    if [ -z "${v4_ip}" ] || [ -z "${v6_ip}" ]; then
        echo "V4 veya V6 IP adresi bulunamadı!"
        exit 1
    fi

    # config.conf dosyasına yaz
    echo "v4_ip=${v4_ip}" > ${CONF_FILE}
    echo "v6_ip=${v6_ip}" >> ${CONF_FILE}
    echo "IP adresleri kaydedildi: V4=${v4_ip}, V6=${v6_ip}"
}

# Squid Proxy kurulumu ve yapılandırması
install_squid() {
    echo "Squid Proxy kuruluyor..."
    apt-get update
    apt-get install squid ufw -y

    # Squid yapılandırma dosyasını yeni dizine taşı
    echo "Squid yapılandırması yapılıyor..."
    mv /etc/squid/squid.conf ${SQUID_DIR}/squid.conf

    # Squid yapılandırmasını güncelle
    cat <<EOL > ${SQUID_DIR}/squid.conf
http_port 3128

# İç network için erişim izinleri
acl localnet src 10.0.0.0/8    # RFC1918 possible internal network
acl localnet src 172.16.0.0/12 # RFC1918 possible internal network
acl localnet src 192.168.0.0/16 # RFC1918 possible internal network
acl localnet src fc00::/7       # RFC4193 local private network range
acl localnet src fe80::/10      # RFC4291 link-local (directly plugged) machines

# HTTPS/SSL ve güvenli bağlantı portları
acl SSL_ports port 443
acl Safe_ports port 80         # http
acl Safe_ports port 21         # ftp
acl Safe_ports port 443        # https
acl Safe_ports port 70         # gopher
acl Safe_ports port 210        # wais
acl Safe_ports port 1025-65535 # unregistered ports
acl Safe_ports port 280        # http-mgmt
acl Safe_ports port 488        # gss-http
acl Safe_ports port 591        # filemaker
acl Safe_ports port 777        # multiling http
acl CONNECT method CONNECT

# Erişim kuralları
http_access allow localnet
http_access allow localhost
http_access deny all

# Cache log ve diğer log formatları
cache_log ${SQUID_LOG}/cache.log
logformat custom_format %>a %ui %ul %un %>Hs %rm %ru %Sh %<st %Ss:%<st %mt %>A %<A
access_log ${SQUID_LOG}/access.log custom_format

# Kullanıcı yapılandırma dosyalarını include et
include ${USER_CONF_DIR}/*.conf

# DNS ayarları
dns_nameservers 8.8.8.8 8.8.4.4

# Squid cache dizini ve disk boyutu
cache_dir ufs /var/spool/squid 100 16 256
EOL

    # Squid log dizini izinlerini ayarla
    chown -R proxy:proxy ${SQUID_LOG}
    chmod -R 755 ${SQUID_LOG}

    # Squid servisini konfigüre et
    systemctl stop squid
    ln -sf ${SQUID_DIR}/squid.conf /etc/squid/squid.conf  # Squid için sembolik link oluştur
    chown proxy:proxy ${SQUID_DIR}/squid.conf
    chmod 644 ${SQUID_DIR}/squid.conf

    # Cache dizinini oluştur ve izinlerini ayarla
    mkdir -p /var/spool/squid
    chown -R proxy:proxy /var/spool/squid
    chmod -R 755 /var/spool/squid
    squid -z  # Cache dizinini yeniden oluştur

    # Squid'i başlat
    systemctl start squid
}

# UFW yapılandırması
setup_ufw() {
    echo "UFW ayarları yapılıyor..."
    ufw default deny incoming
    ufw default allow outgoing
    ufw allow 22/tcp  # SSH portu her zaman açık kalacak
    ufw enable
}

# Web arayüz kurulumu (NGINX)
install_web_interface() {
    echo "Web arayüz kuruluyor..."
    apt-get install nginx -y

    # NGINX yapılandırma ve log dosyalarını yeni dizine taşı
    mv /etc/nginx/nginx.conf ${NGINX_DIR}/nginx.conf
    sed -i "s@/var/log/nginx@${NGINX_LOG}@" ${NGINX_DIR}/nginx.conf

    ln -sf ${NGINX_DIR}/nginx.conf /etc/nginx/nginx.conf  # NGINX için sembolik link oluştur
    systemctl enable nginx
    systemctl start nginx

    echo "Web arayüzü için NGINX kuruldu."
}

# Dosya izinlerini ayarlama
set_permissions() {
    echo "Dosya izinleri ayarlanıyor..."
    chown -R $(whoami):$(whoami) ${BASE_DIR}
    chown -R proxy:proxy ${SQUID_DIR}
    chmod -R 755 ${BASE_DIR}
    chmod 600 ${CONF_FILE}
}

# Squid Proxy ve diğer servisleri başlatma
restart_services() {
    echo "Servisler başlatılıyor..."
    systemctl restart squid
    systemctl restart nginx
}

# Sistemi temizleme ve eski kurulumları kaldırma
clean_system() {
    echo "Eski kurulum ve servisler kaldırılıyor..."

    # Squid'i kaldır
    apt-get purge --auto-remove squid -y
    rm -rf /etc/squid
    rm -rf /var/log/squid
    rm -rf /var/spool/squid

    # NGINX'i kaldır
    apt-get purge --auto-remove nginx -y
    rm -rf /etc/nginx
    rm -rf /var/log/nginx

    # UFW firewall ayarlarını sıfırla
    ufw --force reset
    ufw allow 22/tcp  # SSH portunu yeniden açık bırak

    # Ek proxy yazılımları ve yapılandırmaları kaldır
    echo "Diğer proxy servisleri kaldırılıyor..."
    rm -rf /etc/systemd/system/squid*
    rm -rf ${BASE_DIR}/squid
    rm -rf ${BASE_DIR}/nginx

    echo "Sistem temizlendi, ancak ${BASE_DIR} ve install.sh dosyası tutuluyor."
}

# Ana kurulum işlemi
install_system() {
    setup_directories
    create_config_file
    install_squid
    setup_ufw
    install_web_interface
    set_permissions
    restart_services
    echo "Kurulum tamamlandı. Sistem hazır!"
}

# Ana menü
main_menu() {
    echo "VortexIP Kurulum ve Yönetim Scripti"
    echo "1. Sistemi Kur"
    echo "2. Sistemi Temizle (Varsayılan Ubuntu Haline Döner)"
    echo "3. Çıkış"
    read -p "Bir işlem seçin: " action

    case $action in
        1)
            install_system
            ;;
        2)
            clean_system
            ;;
        3)
            echo "Çıkış yapılıyor..."
            exit 0
            ;;
        *)
            echo "Geçersiz seçim!"
            ;;
    esac
}

# Script'i başlat
main_menu
