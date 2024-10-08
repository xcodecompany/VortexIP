<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<h1>VortexIP Proxy Management System</h1>

<p>VortexIP, kullanıcılar için rotating proxy yönetimi sağlayan bir sistemdir. Bu proje, kullanıcı yönetimi, IPv4 port atamaları ve arkada çalışan rotating IPv6 proxy altyapısını içerir.</p>

<h2>Kurulum Adımları</h2>

<p>Bu projeyi kurmak için aşağıdaki adımları izleyin:</p>

<h3>1. Gerekli Bağımlılıkları Kurun</h3>

<p>Kurulumu başlatmak için, <code>install.sh</code> dosyasını çalıştırarak gerekli servisleri kurabilirsiniz:</p>

<pre><code>bash install.sh</code></pre>

<p>Bu komut, aşağıdaki servislerin kurulumunu yapacaktır:</p>
<ul>
    <li><strong>Nginx</strong>: Web sunucusu</li>
    <li><strong>PHP</strong>: Web uygulamaları için gerekli</li>
    <li><strong>MariaDB</strong>: Veritabanı yönetimi</li>
    <li><strong>Squid</strong>: Proxy sunucusu</li>
    <li><strong>HAProxy</strong>: Rotating proxy yönetimi</li>
</ul>

<h3>2. Servisleri Kontrol Edin</h3>

<p>Kurulumdan sonra aşağıdaki komutlar ile servislerin doğru çalıştığını kontrol edin:</p>

<ul>
    <li><strong>Nginx</strong>:
        <pre><code>systemctl status nginx</code></pre>
    </li>
    <li><strong>MariaDB</strong>:
        <pre><code>systemctl status mariadb</code></pre>
    </li>
    <li><strong>Squid</strong>:
        <pre><code>systemctl status squid</code></pre>
    </li>
    <li><strong>HAProxy</strong>:
        <pre><code>systemctl status haproxy</code></pre>
    </li>
</ul>

<h3>3. Dosya ve Dizin Yapısı</h3>

<p>Kurulum tamamlandıktan sonra, proje dizin yapısı şu şekildedir:</p>

<pre><code>/home/VortexIP
├── logs/           # Log dosyaları
├── config/         # Yapılandırma dosyaları
├── web/            # Web arayüz dosyaları
├── install.sh      # Kurulum script'i
└── README.md       # Proje dokümantasyonu
</code></pre>

<h3>4. Geliştirme Adımları</h3>

<ul>
    <li><strong>Install.sh Dosyasının Oluşturulması</strong>: <code>install.sh</code> dosyası, tüm gerekli servislerin kurulumu için hazırlandı.
        <ul>
            <li>Nginx, PHP, MariaDB, Squid ve HAProxy kurulumları yapılmaktadır.</li>
            <li>Web dizini <code>/home/VortexIP/web</code> olarak ayarlandı.</li>
        </ul>
    </li>
    <li><strong>GitHub ile Senkronizasyon</strong>:
        <ul>
            <li>Proje, GitHub üzerinden versiyonlanmaktadır.</li>
            <li>Her aşama için düzenli olarak commit ve push işlemleri yapılmaktadır.</li>
        </ul>
    </li>
</ul>

<h3>5. Yardım ve Katkılar</h3>

<p>Bu projeye katkıda bulunmak isterseniz, GitHub üzerinde pull request oluşturabilirsiniz. Herhangi bir sorunla karşılaşırsanız, <code>Issues</code> kısmında yeni bir başlık açarak yardım alabilirsiniz.</p>

</body>
</html>

