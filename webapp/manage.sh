#!/bin/bash

APP_DIR="/home/VortexIP/webapp"
VENV_DIR="/home/VortexIP/myvenv"
APP_FILE="app.py"

start_app() {
    echo "Flask uygulaması başlatılıyor..."
    # Sanal ortamı aktif et
    source ${VENV_DIR}/bin/activate
    # Flask uygulamasını arka planda başlat
    nohup python ${APP_DIR}/${APP_FILE} --host=0.0.0.0 > flask.log 2>&1 &
    echo "Flask uygulaması başlatıldı."
}

stop_app() {
    echo "Flask uygulaması durduruluyor..."
    # Flask uygulamasını çalıştıran süreci bul ve öldür
    pkill -f "python ${APP_DIR}/${APP_FILE}"
    echo "Flask uygulaması durduruldu."
}

case "$1" in
    start)
        start_app
        ;;
    stop)
        stop_app
        ;;
    restart)
        stop_app
        start_app
        ;;
    *)
        echo "Kullanım: $0 {start|stop|restart}"
        exit 1
esac
