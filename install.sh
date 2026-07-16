#!/bin/bash
#
# DockPanel Installer
# Cara pakai (setelah script ini ada di repo):
#   bash <(curl -s https://raw.githubusercontent.com/Julakk/DockPanel/main/install.sh)
#
# Wajib: Ubuntu 24.04, dijalankan sebagai root, VPS beneran (bukan Termux/Android).
#

set -e

# ── Warna buat output biar enak dibaca ─────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_info()    { echo -e "${BLUE}[INFO]${NC} $1"; }
print_success() { echo -e "${GREEN}[OK]${NC} $1"; }
print_warn()    { echo -e "${YELLOW}[WARN]${NC} $1"; }
print_error()   { echo -e "${RED}[ERROR]${NC} $1"; }

DOCKPANEL_REPO="https://github.com/Julakk/DockPanel.git"
DOCKWINGS_REPO="https://github.com/Julakk/DockWings.git"

# ── Cek prasyarat dasar ─────────────────────────────────────────────
check_root() {
    if [ "$(id -u)" -ne 0 ]; then
        print_error "Script ini harus dijalankan sebagai root. Coba: sudo bash install.sh"
        exit 1
    fi
}

check_os() {
    if [ ! -f /etc/os-release ]; then
        print_error "Nggak bisa deteksi OS. Script ini didesain buat Ubuntu 24.04."
        exit 1
    fi

    . /etc/os-release

    if [ "$ID" != "ubuntu" ]; then
        print_warn "OS terdeteksi: $PRETTY_NAME. Script ini didesain buat Ubuntu 24.04, mungkin ada yang nggak jalan sempurna di OS lain."
        read -rp "Lanjut aja? (y/n): " confirm
        [ "$confirm" != "y" ] && exit 1
    elif [ "${VERSION_ID}" != "24.04" ]; then
        print_warn "Ubuntu terdeteksi versi $VERSION_ID, script ini ditest buat 24.04."
        read -rp "Lanjut aja? (y/n): " confirm
        [ "$confirm" != "y" ] && exit 1
    fi
}

check_not_termux() {
    if [ -n "$TERMUX_VERSION" ] || [ -d /data/data/com.termux ]; then
        print_error "Kedetect jalan di Termux/Android. Installer ini WAJIB di VPS Linux beneran, Docker nggak bisa jalan di Termux."
        exit 1
    fi
}

# ── Instalasi Panel (Laravel) ───────────────────────────────────────
install_panel() {
    print_info "Mulai instalasi DockPanel (Panel)..."

    read -rp "Masukin domain/FQDN buat panel (ex: panel.ahmadstore.id): " PANEL_DOMAIN
    read -rp "Install SSL pakai Let's Encrypt? (y/n): " INSTALL_SSL
    read -rp "Nama database [dockpanel]: " DB_NAME
    DB_NAME=${DB_NAME:-dockpanel}
    read -rp "Username database [dockpanel]: " DB_USER
    DB_USER=${DB_USER:-dockpanel}
    DB_PASS=$(openssl rand -base64 24)

    print_info "Update sistem & install dependency dasar..."
    apt update -y
    apt install -y software-properties-common curl gnupg2 ca-certificates lsb-release apt-transport-https unzip git

    print_info "Install PHP 8.3 + extensions..."
    add-apt-repository -y ppa:ondrej/php
    apt update -y
    apt install -y php8.3 php8.3-{common,cli,gd,mysql,mbstring,bcmath,xml,fpm,curl,zip,intl,sqlite3}

    print_info "Install Composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

    print_info "Install MariaDB..."
    apt install -y mariadb-server mariadb-client

    print_info "Setup database..."
    mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME};
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

    print_info "Install Nginx..."
    apt install -y nginx

    print_info "Clone DockPanel..."
    mkdir -p /var/www
    if [ -d /var/www/dockpanel ]; then
        print_warn "/var/www/dockpanel udah ada, skip clone. Pastiin ini repo yang bener."
    else
        git clone "$DOCKPANEL_REPO" /var/www/dockpanel
    fi

    cd /var/www/dockpanel

    print_info "Install dependency Composer..."
    composer install --no-dev --optimize-autoloader --no-interaction

    print_info "Setup environment..."
    cp -n .env.example .env

    php artisan key:generate --force

    sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
    sed -i "s|DB_HOST=.*|DB_HOST=127.0.0.1|" .env
    sed -i "s|APP_URL=.*|APP_URL=https://${PANEL_DOMAIN}|" .env

    print_info "Jalanin migration..."
    php artisan migrate --seed --force

    print_info "Set permission..."
    chown -R www-data:www-data /var/www/dockpanel
    chmod -R 755 /var/www/dockpanel/storage /var/www/dockpanel/bootstrap/cache

    print_info "Setup Nginx config..."
    cat > /etc/nginx/sites-available/dockpanel.conf <<NGINX
server {
    listen 80;
    server_name ${PANEL_DOMAIN};
    root /var/www/dockpanel/public;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
NGINX

    ln -sf /etc/nginx/sites-available/dockpanel.conf /etc/nginx/sites-enabled/dockpanel.conf
    rm -f /etc/nginx/sites-enabled/default
    nginx -t && systemctl reload nginx
    systemctl enable --now php8.3-fpm

    if [ "$INSTALL_SSL" = "y" ]; then
        print_info "Install SSL via Certbot..."
        apt install -y certbot python3-certbot-nginx
        certbot --nginx -d "$PANEL_DOMAIN" --non-interactive --agree-tos -m "admin@${PANEL_DOMAIN}" || \
            print_warn "Certbot gagal — mungkin domain belum ngarah ke IP VPS ini. Bisa dijalankan manual nanti: certbot --nginx -d ${PANEL_DOMAIN}"
    fi

    print_success "DockPanel berhasil diinstall!"
    echo ""
    echo "======================================================"
    echo " Panel URL     : http://${PANEL_DOMAIN}"
    echo " Database      : ${DB_NAME}"
    echo " DB User       : ${DB_USER}"
    echo " DB Password   : ${DB_PASS}"
    echo ""
    echo " Login admin default (GANTI SEGERA):"
    echo "   Email    : admin@ahmadstore.id"
    echo "   Password : changeme123"
    echo "======================================================"
    echo ""
    print_warn "Simpan info database di atas di tempat aman, nggak bakal ditampilin lagi."
}

# ── Instalasi Wings (Go daemon + Docker) ────────────────────────────
install_wings() {
    print_info "Mulai instalasi DockWings (Node daemon)..."

    read -rp "Masukin daemon_token dari Panel (didapat pas bikin Node di admin panel): " DAEMON_TOKEN
    read -rp "Port buat Wings API [8080]: " WINGS_PORT
    WINGS_PORT=${WINGS_PORT:-8080}
    read -rp "Port buat SFTP [2022]: " SFTP_PORT
    SFTP_PORT=${SFTP_PORT:-2022}

    print_info "Install Docker..."
    if ! command -v docker &> /dev/null; then
        curl -fsSL https://get.docker.com | sh
        systemctl enable --now docker
    else
        print_success "Docker udah terinstall, skip."
    fi

    print_info "Install Go 1.22..."
    if ! command -v go &> /dev/null; then
        curl -fsSL https://go.dev/dl/go1.22.0.linux-amd64.tar.gz -o /tmp/go.tar.gz
        rm -rf /usr/local/go
        tar -C /usr/local -xzf /tmp/go.tar.gz
        ln -sf /usr/local/go/bin/go /usr/local/bin/go
        rm -f /tmp/go.tar.gz
    else
        print_success "Go udah terinstall, skip."
    fi

    print_info "Clone DockWings..."
    mkdir -p /etc/dockwings
    if [ -d /opt/dockwings ]; then
        print_warn "/opt/dockwings udah ada, skip clone."
    else
        git clone "$DOCKWINGS_REPO" /opt/dockwings
    fi

    cd /opt/dockwings

    print_info "Build binary Wings..."
    go build -o /usr/local/bin/dockwings ./cmd/wings

    print_info "Setup config..."
    mkdir -p /var/lib/dockwings/servers

    cat > /etc/dockwings/config.json <<CONFIG
{
  "listen_addr": ":${WINGS_PORT}",
  "sftp_addr": ":${SFTP_PORT}",
  "auth_token": "${DAEMON_TOKEN}",
  "docker_socket": "/var/run/docker.sock",
  "data_directory": "/var/lib/dockwings/servers"
}
CONFIG

    print_info "Setup systemd service..."
    cat > /etc/systemd/system/dockwings.service <<SERVICE
[Unit]
Description=DockWings Daemon
After=docker.service
Requires=docker.service

[Service]
User=root
WorkingDirectory=/etc/dockwings
ExecStart=/usr/local/bin/dockwings -config /etc/dockwings/config.json
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
SERVICE

    systemctl daemon-reload
    systemctl enable --now dockwings

    sleep 2

    if systemctl is-active --quiet dockwings; then
        print_success "DockWings berhasil jalan!"
    else
        print_error "DockWings gagal start. Cek log: journalctl -u dockwings -n 50"
    fi

    echo ""
    echo "======================================================"
    echo " Wings API    : http://$(hostname -I | awk '{print $1}'):${WINGS_PORT}"
    echo " SFTP         : port ${SFTP_PORT}"
    echo " Config       : /etc/dockwings/config.json"
    echo " Cek status   : systemctl status dockwings"
    echo " Cek log      : journalctl -u dockwings -f"
    echo "======================================================"
    echo ""
    print_warn "Pastiin firewall/security group buka port ${WINGS_PORT} dan ${SFTP_PORT} biar Panel bisa konek."
}

# ── Menu utama ───────────────────────────────────────────────────────
main() {
    check_root
    check_not_termux
    check_os

    echo ""
    echo "🐧 =============================================="
    echo "     DockPanel Installer"
    echo "=================================================="
    echo ""
    echo "  1) Install Panel (Laravel — web UI, database, auth)"
    echo "  2) Install Node/Wings (Go daemon — kontrol Docker)"
    echo "  0) Batal"
    echo ""
    read -rp "Pilih opsi [1/2/0]: " OPTION

    case $OPTION in
        1) install_panel ;;
        2) install_wings ;;
        0) print_info "Dibatalin."; exit 0 ;;
        *) print_error "Opsi nggak valid."; exit 1 ;;
    esac
}

main
