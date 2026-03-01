#!/bin/bash

# --- KONFIGURASI AWAL ---
# Mendefinisikan nama container Docker di mana aplikasi Laravel berjalan.
CONTAINER_NAME="inventra"

# Menentukan lokasi direktori (folder) aplikasi berada di dalam OS Host / Server.
APP_DIR="/DATA/www/inventra"

# Mendapatkan nama user OS yang saat ini sedang login dan mengeksekusi script.
CURRENT_USER=$(whoami)       # User Anda (misal: user_casaos)

# Nama user dan grup milik Web Server (seperti Nginx atau Apache), standarnya adalah www-data.
WEB_USER="www-data"          

echo "🚀 MEMULAI DEPLOYMENT (MODE SWITCH-OWNER)..."

# ==========================================
# 1. PERSIAPAN FOLDER (Pindah ke direktori project)
# ==========================================
# Berpindah dari lokasi terminal saat ini ke dalam folder aplikasi.
cd $APP_DIR

# ==========================================
# 2. PENGATURAN PERMISSION SEMENTARA
# ==========================================
# Karena struktur file dimiliki web server (www-data) namun kita login sebagai user biasa (CURRENT_USER),
# Git akan menolak untuk merubah/menarik file jika kita tidak punya akses atau kepemilikannya berbeda.
# Oleh karena itu, kita mengambil alih hak milik (chown) untuk folder dan file krusial 
# agar Git bisa menimpa file tanpa pesan 'permission denied'.
sudo chown -R $CURRENT_USER:$CURRENT_USER storage bootstrap/cache database/database.sqlite

# ==========================================
# 3. PROSES PEMBAHARUAN SOURCE CODE (GIT)
# ==========================================
echo "⬇️  Pulling Source Code..."
# Berusaha menarik (menggabungkan) update dari GitHub (branch main).
git pull origin main

echo "⬇️  Fetching latest changes..."
# Sinkronisasi history & meta-data git terbaru dari repositori GitHub secara menyeluruh 
# ke repositori lokal (agar data lokal dan server GitHub setara).
git fetch --all

echo "🔥 Force Resetting to match Remote..."
# TIMPA Keras (Hard Reset). Jika lokal ada perubahan kode, semuanya akan dibuang.
# Ini memaksa folder di server untuk SAMA PERSIS dengan apa yang ada di branch 'main' GitHub.
git reset --hard origin/main

# ==========================================
# 4. INSTALASI & MIGRASI DATABASE (via DOCKER)
# ==========================================
echo "📦 Running Composer & Migrations..."
# Masuk ke container Docker PHP ("inventra") sebagai user root (-u 0) lalu jalankan Composer.
# Flag yang dipakai fungsinya untuk mempercepat load (optimize-autoloader), membuang package dev (no-dev),
# dan mengabaikan pengecekan ekstensi zip & gd di versi PHP saat instalasi package.
sudo docker exec -u 0 -i $CONTAINER_NAME composer install --optimize-autoloader --no-dev --ignore-platform-req=ext-gd --ignore-platform-req=ext-zip

# Menjalankan perintah migrasi Laravel di dalam docker untuk menerapkan perubahan struktur database.
# "--force" dipakai karena aplikasi di mode production, sehingga tidak meminta konfirmasi ketik 'yes'.
sudo docker exec -u 0 -i $CONTAINER_NAME php artisan migrate --force

# ==========================================
# 5. OPTIMASI & CACHE LARAVEL (PRODUCTION)
# ==========================================
echo "🧹 Optimizing Laravel Cache for Production..."
# Membersihkan seluruh cache lama (Konfigurasi, Routing, dan View).
sudo docker exec -u 0 -i $CONTAINER_NAME php artisan optimize:clear

# Meng-compile file konfigurasi dan routing (optimize otomatis melakukan config:cache & route:cache)
sudo docker exec -u 0 -i $CONTAINER_NAME php artisan optimize

# Meng-compile semua file .blade.php menjadi native PHP agar proses render halaman tak butuh waktu lama.
sudo docker exec -u 0 -i $CONTAINER_NAME php artisan view:cache

# Me-load Event Listeners ke dalam cache agar tidak diload dari awal setiap ada event (Khusus Production)
sudo docker exec -u 0 -i $CONTAINER_NAME php artisan event:cache

# ==========================================
# 6. RESET CONTAINER (Clear OPcache & Refresh)
# ==========================================
echo "🔄 Mereset Container (Down/Up) untuk membersihkan OPcache..."
# Jika Anda menggunakan Docker Compose (umumnya di Root Folder):
# sudo docker compose down
# sudo docker compose up -d

# Atau Opsi Alternatif (Restart Native Docker):
sudo docker stop $CONTAINER_NAME
sudo docker start $CONTAINER_NAME

# ==========================================
# 7. PENGATURAN PERMISSION FINAL (Akses Web Server)
# ==========================================
# Skrip deploy agar selalu bisa dieksekusi sebagai program ('+x' = executable)
sudo chmod +x $APP_DIR/deploy.sh

# Mengembalikan kepemilikan folder yang rawan disunting oleh Laravel (Logs, Cache, Database SQLite) 
# kembali ke user web server (33:33 adalah representasi angka ID untuk www-data di Linux).
sudo chown -R 33:33 storage bootstrap/cache database/database.sqlite

# Memberikan hak akses baca, tulis, eksekusi (rwx) untuk Owner (u) dan Group (g)
# Memastikan web server bisa menulis file log, session framework, dan operasi database SQLite tanpa error.
sudo chmod -R ug+rwx storage bootstrap/cache database/database.sqlite

echo "✅ DEPLOY SELESAI!"
