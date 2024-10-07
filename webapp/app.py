from flask import Flask, render_template, request, redirect, url_for, session
from flask_sqlalchemy import SQLAlchemy
import os
import socket
import random

app = Flask(__name__)
app.secret_key = 'supersecretkey'

# Veritabanı ayarları
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:////home/VortexIP/webapp/users.db'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
db = SQLAlchemy(app)

# Kullanıcı modeli
class User(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(150), nullable=False, unique=True)
    password = db.Column(db.String(150), nullable=False)

# Veritabanı ve tabloları oluşturma fonksiyonu
def create_tables():
    with app.app_context():
        db.create_all()

# Kullanıcı girişi
@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']

        # Veritabanında kullanıcıyı bul
        user = User.query.filter_by(username=username, password=password).first()

        if user:
            session['username'] = username
            return redirect(url_for('dashboard'))
        else:
            return "Login failed. Invalid credentials."

    return render_template('login.html')

# Dashboard (menü ve yönlendirme sayfası)
@app.route('/dashboard')
def dashboard():
    if 'username' in session:
        return render_template('dashboard.html')
    else:
        return redirect(url_for('login'))

@app.route('/add_user', methods=['GET', 'POST'])
def add_user():
    if 'username' in session and session['username'] == 'admin':
        if request.method == 'POST':
            new_username = request.form['username']
            new_password = request.form['password']

            # Veritabanına kullanıcı ekleme
            existing_user = User.query.filter_by(username=new_username).first()
            if existing_user:
                return "User already exists!"
            else:
                new_user = User(username=new_username, password=new_password)
                db.session.add(new_user)
                db.session.commit()
                return redirect(url_for('dashboard'))

        return render_template('add_user.html')
    else:
        return redirect(url_for('login'))

# Kullanıcı listesi
@app.route('/user_list')
def user_list():
    if 'username' in session and session['username'] == 'admin':
        users = User.query.all()  # Tüm kullanıcıları al
        return render_template('user_list.html', users=users)
    else:
        return redirect(url_for('login'))

# Kullanıcı oturumunu kapatma
@app.route('/logout')
def logout():
    session.pop('username', None)
    return redirect(url_for('login'))

# Rastgele boş port bulma
def find_available_port():
    while True:
        port = random.randint(5001, 6000)  # 5001 ile 6000 arasında rastgele bir port seç
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
            if s.connect_ex(('localhost', port)) != 0:
                return port  # Port kullanılmıyorsa döndür

# Firewall kuralı ekleme
def add_firewall_rule(port):
    os.system(f"iptables -A INPUT -p tcp --dport {port} -j ACCEPT")  # Portu aç
    os.system("iptables-save")  # Firewall kurallarını kaydet

# Kullanıcı adı/şifre ya da IP bazlı erişim yapılandırması
def configure_access(port, ip=None, username=None, password=None):
    if ip:
        # İzin verilen IP'yi firewall kuralına ekle
        os.system(f"iptables -A INPUT -p tcp --dport {port} -s {ip} -j ACCEPT")
    elif username and password:
        # Kullanıcı adı ve şifre ile giriş yapılabilmesi için yapılandırma
        pass  # Bu kısım, proxy veya squid'e göre özelleştirilmeli
    os.system("iptables-save")

# Servisleri yeniden başlatma (örn. squid)
def restart_services():
    os.system("systemctl restart squid")

# Port ekleme işlemi
@app.route('/add_port', methods=['GET', 'POST'])
def add_port():
    if request.method == 'POST':
        ip = request.form.get('ip')
        username = request.form.get('username')
        password = request.form.get('password')

        # Random boş port bul
        port = find_available_port()

        # Firewall kuralını ekle
        add_firewall_rule(port)

        # Erişim yapılandırması
        configure_access(port, ip, username, password)

        # Servisleri yeniden başlat
        restart_services()

        return f"Port {port} başarıyla eklendi!"

    return render_template('add_port.html')

# Veritabanı tablolarını oluştur
if __name__ == '__main__':
    create_tables()  # Veritabanı tablolarını oluştur
    app.run(debug=True, host='0.0.0.0', port=5000)
