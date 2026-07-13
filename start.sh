#!/data/data/com.termux/files/usr/bin/bash

# start.sh — nyalain MySQL (kalau belum jalan) + php artisan serve
# Cara pakai: bash start.sh

echo "🔍 Cek status MySQL..."

if mysqladmin -u root status > /dev/null 2>&1; then
    echo "✅ MySQL udah jalan."
else
    echo "🚀 MySQL belum jalan, nyalain sekarang..."
    mariadbd-safe --datadir="$PREFIX/var/lib/mysql" &
    sleep 3

    if mysqladmin -u root status > /dev/null 2>&1; then
        echo "✅ MySQL berhasil dinyalain."
    else
        echo "❌ Gagal nyalain MySQL. Cek manual: mariadbd-safe --datadir=\$PREFIX/var/lib/mysql &"
        exit 1
    fi
fi

echo "🐧 Nyalain DockPanel..."
php artisan serve
