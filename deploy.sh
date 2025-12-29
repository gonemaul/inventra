#!/bin/bash

# --- KONFIGURASI ---
CONTAINER_NAME="inventra"
APP_DIR="/DATA/www/inventra"
CURRENT_USER=$(whoami)       # User Anda (misal: user_casaos)
WEB_USER="www-data"          # User Web Server

echo "🚀 MEMULAI DEPLOYMENT (MODE SWITCH-OWNER)..."

# ==========================================
# 1. AMBIL ALIH KEPEMILIKAN (Agar bisa Git Pull)
# ==========================================
#echo "👮 Mengambil alih folder ke user: $CURRENT_USER ..."
#sudo chown -R $CURRENT_USER:$CURRENT_USER $APP_DIR

# ==========================================
# 2. PROSES UPDATE
# ==========================================
cd $APP_DIR
sudo chown -R $CURRENT_USER:$CURRENT_USER storage bootstrap/cache database/database.sqlite

echo "⬇️  Pulling Source Code..."
git pull origin main

echo "⬇️  Fetching latest changes..."
# Ambil data terbaru dari GitHub
git fetch --all

echo "🔥 Force Resetting to match Remote..."
# TIMPA semua perubahan lokal dengan versi GitHub
git reset --hard origin/main

echo "📦 Running Composer & Migrations..."
# Jalankan sebagai root di dalam container untuk instalasi
sudo docker exec -u 0 -i $CONTAINER_NAME composer install --optimize-autoloader --no-dev --ignore-platform-req=ext-gd --ignore-platform-req=ext-zip
sudo docker exec -u 0 -i $CONTAINER_NAME php artisan migrate --force

echo "🧹 Optimizing Laravel Cache..."
sudo docker exec -u 0 -i $CONTAINER_NAME php artisan optimize:clear
sudo docker exec -u 0 -i $CONTAINER_NAME php artisan config:cache
sudo docker exec -u 0 -i $CONTAINER_NAME php artisan view:cache

# ==========================================
# Restart Queue
# ==========================================
#echo "🔄 Restarting Queue..."
#sudo supervisor restart inventra-worker:*


# ==========================================
# 4. KEMBALIKAN KEPEMILIKAN (Ke www-data)
# ==========================================
#echo "🤝 Mengembalikan folder ke: $WEB_USER ..."

# Set pemilik ke www-data (User ID 33)
#sudo chown -R $WEB_USER:$WEB_USER $APP_DIR

# Set Permission agar User biasa masih bisa baca/eksekusi script ini nanti
# Folder 755, File 644
#sudo find $APP_DIR -type d -exec chmod 755 {} \;
#sudo find $APP_DIR -type f -exec chmod 644 {} \;

# Khusus Script Deploy harus Executable
sudo chmod +x $APP_DIR/deploy.sh

# Khusus Folder Storage & Database butuh akses tulis penuh dari web server
# (Sebenarnya sudah aman karena pemiliknya www-data, tapi kita pastikan 775)
#sudo chmod -R 775 $APP_DIR/storage
#sudo chmod -R 775 $APP_DIR/database
#sudo chmod -R 775 $APP_DIR/bootstrap/cache
sudo chown -R 33:33 storage bootstrap/cache database/database.sqlite
sudo chmod -R ug+rwx storage bootstrap/cache database/database.sqlite
echo "✅ DEPLOY SELESAI!"
