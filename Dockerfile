# Gunakan image ServerSideUp (PHP 8.3 + Nginx)
FROM serversideup/php:8.3-fpm-nginx

# Masuk sebagai root
USER root

# === JURUS MAGIC: Install Extension Menggunakan Script Otomatis ===
# 1. Download script installer
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# 2. Beri izin eksekusi & Jalankan install GD
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions gd redis pdo_sqlite

# 2. TAMBAHKAN INI: Install Program SQLite3 (Untuk Backup)
RUN apt-get update && apt-get install -y sqlite3

# Bersihkan cache biar ringan
RUN apt-get clean && rm -rf /var/lib/apt/lists/*


# (Opsional) Kalau butuh yang lain tinggal tambah, misal:
# install-php-extensions gd zip mysql

# Kembali ke user bawaan agar aman
USER www-data

WORKDIR /var/www/html
