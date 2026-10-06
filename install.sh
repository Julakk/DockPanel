#!/bin/bash
#
# DockPanel Installer
# Cara pakai:
#   bash <(curl -s https://raw.githubusercontent.com/Julakk/DockPanel/main/install.sh)
#
# Menu: install Panel, install Node/Wings, update Panel, update Wings.
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

DOCKPANEL_DIR="${DOCKPANEL_DIR:-/var/www/dockpanel}"
DOCKWINGS_DIR="${DOCKWINGS_DIR:-/opt/dockwings}"
DOCKWINGS_CONF="${DOCKWINGS_CONF:-/etc/dockwings/config.json}"
DOCKWINGS_BIN="${DOCKWINGS_BIN:-/usr/local/bin/dockwings}"

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

# ── Helper ──────────────────────────────────────────────────────────

# set_env FILE KEY VALUE — ganti nilai kalau KEY sudah ada, tambah kalau belum.
# Pakai awk + ENVIRON, jadi nilai dengan karakter spesial (/, &, |, \) tetap aman.
set_env() {
    local file="$1" key="$2" val="$3"
    if grep -q "^${key}=" "$file"; then
        SE_KEY="$key" SE_VAL="$val" awk 'BEGIN { FS = OFS = "=" }
            $1 == ENVIRON["SE_KEY"] { print ENVIRON["SE_KEY"] "=" ENVIRON["SE_VAL"]; next }
            { print }' "$file" > "${file}.tmp"
        mv "${file}.tmp" "$file"
    else
        printf '%s=%s\n' "$key" "$val" >> "$file"
    fi
}

# stash_if_dirty DIR — amanin perubahan lokal yang belum di-commit sebelum git pull.
stash_if_dirty() {
    if [ -n "$(git -C "$1" status --porcelain)" ]; then
        print_warn "Ada perubahan lokal di $1 yang belum di-commit. Diamankan ke git stash (cek: git stash list)."
        git -C "$1" stash push -u -m "installer-update-$(date +%Y%m%d-%H%M%S)"
    fi
}

# Arsitektur buat download Go: amd64 atau arm64.
detect_go_arch() {
    case "$(uname -m)" in
        x86_64)        echo "amd64" ;;
        aarch64|arm64) echo "arm64" ;;
        *)             return 1 ;;
    esac
}

# Versi Go minimal dari go.mod (baris "go X.Y.Z"), selalu dinormalisasi jadi X.Y.Z.
required_go_version() {
    local v
    v=$(awk '/^go [0-9]/ { print $2; exit }' "$1/go.mod" 2>/dev/null)
    if [ -z "$v" ]; then
        v="1.22.0"
    fi
    case "$v" in
        *.*.*) ;;
        *) v="${v}.0" ;;
    esac
    echo "$v"
}

# version_ge A B — sukses kalau versi A >= B.
version_ge() {
    [ "$(printf '%s\n%s\n' "$1" "$2" | sort -V | head -n1)" = "$2" ]
}

installed_go_version() {
    (cd / && go version 2>/dev/null) | awk '{ sub(/^go/, "", $3); print $3 }'
}

# ensure_go SRC_DIR — pastiin Go terpasang dan versinya cukup buat go.mod di SRC_DIR.
ensure_go() {
    local need have arch
    need=$(required_go_version "$1")
    have=""
    if command -v go &> /dev/null; then
        have=$(installed_go_version)
    fi

    if [ -n "$have" ] && version_ge "$have" "$need"; then
        print_success "Go $have udah terinstall (butuh $need), skip."
        return 0
    fi

    if ! arch=$(detect_go_arch); then
        print_error "Arsitektur $(uname -m) belum didukung installer. Pasang Go $need manual."
        exit 1
    fi

    print_info "Install Go $need ($arch)..."
    # Unduh & cek arsip DULU; Go lama baru dihapus kalau arsipnya sehat.
    # (Fungsi ini bisa jalan di dalam "if !", di mana set -e nggak berlaku.)
    local tarball="/tmp/go-${need}.tar.gz"
    if ! curl -fsSL "https://go.dev/dl/go${need}.linux-${arch}.tar.gz" -o "$tarball"; then
        rm -f "$tarball"
        print_error "Gagal download Go $need. Cek koneksi internet VPS."
        exit 1
    fi
    if ! tar -tzf "$tarball" > /dev/null 2>&1; then
        rm -f "$tarball"
        print_error "Arsip Go yang diunduh rusak."
        exit 1
    fi
    rm -rf /usr/local/go
    tar -C /usr/local -xzf "$tarball"
    ln -sf /usr/local/go/bin/go /usr/local/bin/go
    rm -f "$tarball"
    export PATH="/usr/local/go/bin:$PATH"
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

    local scheme="http"
    if [ "$INSTALL_SSL" = "y" ]; then
        scheme="https"
    fi

    print_info "Update sistem & install dependency dasar..."
    apt update -y
    apt install -y software-properties-common curl gnupg2 ca-certificates lsb-release apt-transport-https unzip git cron

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
    mkdir -p "$(dirname "$DOCKPANEL_DIR")"
    if [ -d "$DOCKPANEL_DIR" ]; then
        print_warn "$DOCKPANEL_DIR udah ada, skip clone. Buat update ke versi terbaru, pilih menu Update Panel."
    else
        git clone "$DOCKPANEL_REPO" "$DOCKPANEL_DIR"
    fi

    cd "$DOCKPANEL_DIR"

    print_info "Install dependency Composer..."
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction

    print_info "Setup environment..."
    cp -n .env.example .env

    php artisan key:generate --force

    set_env .env DB_HOST 127.0.0.1
    set_env .env DB_DATABASE "$DB_NAME"
    set_env .env DB_USERNAME "$DB_USER"
    set_env .env DB_PASSWORD "$DB_PASS"
    set_env .env APP_URL "${scheme}://${PANEL_DOMAIN}"
    set_env .env APP_ENV production
    set_env .env APP_DEBUG false

    print_info "Jalanin migration..."
    php artisan migrate --seed --force

    print_info "Set permission..."
    chown -R www-data:www-data "$DOCKPANEL_DIR"
    chmod -R 755 "$DOCKPANEL_DIR/storage" "$DOCKPANEL_DIR/bootstrap/cache"
    # .env berisi password database: cukup bisa dibaca root dan PHP-FPM (www-data).
    chown root:www-data "$DOCKPANEL_DIR/.env"
    chmod 640 "$DOCKPANEL_DIR/.env"

    print_info "Setup Nginx config..."
    cat > /etc/nginx/sites-available/dockpanel.conf <<NGINX
server {
    listen 80;
    server_name ${PANEL_DOMAIN};
    root ${DOCKPANEL_DIR}/public;
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

    print_info "Pasang cron scheduler (suspend server expired, pengingat, dll)..."
    cat > /etc/cron.d/dockpanel <<CRON
* * * * * www-data cd ${DOCKPANEL_DIR} && php artisan schedule:run >> /dev/null 2>&1
CRON
    chmod 644 /etc/cron.d/dockpanel
    systemctl enable --now cron

    if [ "$INSTALL_SSL" = "y" ]; then
        print_info "Install SSL via Certbot..."
        apt install -y certbot python3-certbot-nginx
        certbot --nginx -d "$PANEL_DOMAIN" --non-interactive --agree-tos -m "admin@${PANEL_DOMAIN}" || \
            print_warn "Certbot gagal — mungkin domain belum ngarah ke IP VPS ini. Bisa dijalankan manual nanti: certbot --nginx -d ${PANEL_DOMAIN}"
    fi

    print_success "DockPanel berhasil diinstall!"
    echo ""
    echo "======================================================"
    echo " Panel URL     : ${scheme}://${PANEL_DOMAIN}"
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
    if [ "$scheme" = "http" ]; then
        print_warn "Panel jalan tanpa SSL. Kalau nanti pasang SSL, ubah APP_URL di ${DOCKPANEL_DIR}/.env ke https://${PANEL_DOMAIN}."
    fi
    print_info "Langkah berikutnya: login, bikin Location dan Node, lalu pasang Wings di VPS node (menu Install Node/Wings)."
}

# ── Update Panel ────────────────────────────────────────────────────
update_panel() {
    if [ ! -d "$DOCKPANEL_DIR/.git" ]; then
        print_error "$DOCKPANEL_DIR bukan repo git. Panel belum terinstall lewat installer ini?"
        exit 1
    fi

    print_info "Update DockPanel di $DOCKPANEL_DIR..."
    cd "$DOCKPANEL_DIR"

    stash_if_dirty "$DOCKPANEL_DIR"

    local old new
    old=$(git rev-parse --short HEAD)
    if ! git pull --ff-only origin main; then
        print_error "git pull gagal (riwayat lokal beda dari GitHub). Panel nggak diubah."
        exit 1
    fi
    new=$(git rev-parse --short HEAD)

    if [ "$old" = "$new" ]; then
        print_success "Udah versi terbaru ($new)."
    else
        print_info "Panel: $old -> $new"
    fi

    print_info "Update dependency Composer..."
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction

    chown -R www-data:www-data "$DOCKPANEL_DIR"
    if [ -f "$DOCKPANEL_DIR/.env" ]; then
        chown root:www-data "$DOCKPANEL_DIR/.env"
        chmod 640 "$DOCKPANEL_DIR/.env"
    fi

    print_info "Jalanin migration & bersihin cache..."
    runuser -u www-data -- php artisan migrate --force
    runuser -u www-data -- php artisan optimize:clear
    systemctl reload php8.3-fpm

    print_success "DockPanel sudah di $new."
    print_info "Kalau ada rilis yang butuh Wings baru, jalankan juga menu Update Wings di tiap node."
}

# ── Instalasi Wings (Go daemon + Docker) ────────────────────────────

# build_wings OUT — build binary dari kode di DOCKWINGS_DIR ke path OUT.
build_wings() {
    ensure_go "$DOCKWINGS_DIR"
    print_info "Build binary Wings..."
    (cd "$DOCKWINGS_DIR" && go build -o "$1" ./cmd/wings)
}

install_wings() {
    print_info "Mulai instalasi DockWings (Node daemon)..."

    read -rp "Masukin daemon_token dari Panel (didapat pas bikin Node di admin panel): " DAEMON_TOKEN
    read -rp "Port buat Wings API [8080]: " WINGS_PORT
    WINGS_PORT=${WINGS_PORT:-8080}
    read -rp "Port buat SFTP [2022]: " SFTP_PORT
    SFTP_PORT=${SFTP_PORT:-2022}

    print_info "Install dependency dasar..."
    apt update -y
    apt install -y curl git ca-certificates

    print_info "Install Docker..."
    if ! command -v docker &> /dev/null; then
        curl -fsSL https://get.docker.com | sh
        systemctl enable --now docker
    else
        print_success "Docker udah terinstall, skip."
    fi

    print_info "Clone DockWings..."
    mkdir -p "$(dirname "$DOCKWINGS_CONF")"
    if [ -d "$DOCKWINGS_DIR" ]; then
        print_warn "$DOCKWINGS_DIR udah ada, skip clone. Buat update ke versi terbaru, pilih menu Update Wings."
    else
        git clone "$DOCKWINGS_REPO" "$DOCKWINGS_DIR"
    fi

    # Build ke file sementara lalu pasang pakai install (aman walau binary lama lagi jalan).
    local tmp_bin
    tmp_bin=$(mktemp /tmp/dockwings-new.XXXXXX)
    build_wings "$tmp_bin"
    install -m 755 "$tmp_bin" "$DOCKWINGS_BIN"
    rm -f "$tmp_bin"

    print_info "Setup config..."
    mkdir -p /var/lib/dockwings/servers /var/lib/dockwings/backups

    local write_conf="y"
    if [ -f "$DOCKWINGS_CONF" ]; then
        print_warn "$DOCKWINGS_CONF udah ada."
        read -rp "Timpa config lama? Backup-nya disimpan di ${DOCKWINGS_CONF}.bak (y/n) [n]: " write_conf
        write_conf=${write_conf:-n}
        if [ "$write_conf" = "y" ]; then
            cp -f "$DOCKWINGS_CONF" "${DOCKWINGS_CONF}.bak"
        fi
    fi

    if [ "$write_conf" = "y" ]; then
        cat > "$DOCKWINGS_CONF" <<CONFIG
{
  "listen_addr": ":${WINGS_PORT}",
  "sftp_addr": ":${SFTP_PORT}",
  "auth_token": "${DAEMON_TOKEN}",
  "docker_socket": "/var/run/docker.sock",
  "data_directory": "/var/lib/dockwings/servers",
  "backup_directory": "/var/lib/dockwings/backups"
}
CONFIG
        chmod 600 "$DOCKWINGS_CONF"
    else
        print_info "Config lama dipertahankan."
    fi

    print_info "Setup systemd service..."
    cat > /etc/systemd/system/dockwings.service <<SERVICE
[Unit]
Description=DockWings Daemon
After=docker.service
Requires=docker.service

[Service]
User=root
WorkingDirectory=$(dirname "$DOCKWINGS_CONF")
ExecStart=${DOCKWINGS_BIN} -config ${DOCKWINGS_CONF}
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
SERVICE

    systemctl daemon-reload
    systemctl enable --now dockwings
    systemctl restart dockwings

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
    echo " Config       : ${DOCKWINGS_CONF}"
    echo " Cek status   : systemctl status dockwings"
    echo " Cek log      : journalctl -u dockwings -f"
    echo "======================================================"
    echo ""
    print_warn "Pastiin firewall/security group buka port ${WINGS_PORT} dan ${SFTP_PORT} biar Panel bisa konek."
    print_info "Di Panel, scheme Node harus cocok: http kalau Wings tanpa SSL, https kalau pakai SSL."
}

# ── Update Wings ────────────────────────────────────────────────────
update_wings() {
    if [ ! -d "$DOCKWINGS_DIR/.git" ]; then
        print_error "$DOCKWINGS_DIR bukan repo git. Wings belum terinstall lewat installer ini?"
        exit 1
    fi

    print_info "Update DockWings di $DOCKWINGS_DIR..."

    stash_if_dirty "$DOCKWINGS_DIR"

    local old new
    old=$(git -C "$DOCKWINGS_DIR" rev-parse --short HEAD)
    if ! git -C "$DOCKWINGS_DIR" pull --ff-only origin main; then
        print_error "git pull gagal (riwayat lokal beda dari GitHub). Wings nggak diubah."
        exit 1
    fi
    new=$(git -C "$DOCKWINGS_DIR" rev-parse --short HEAD)

    if [ "$old" = "$new" ]; then
        print_success "Kode udah versi terbaru ($new). Binary tetap di-build ulang."
    else
        print_info "Wings: $old -> $new"
    fi

    # Build ke file sementara dulu: kalau build gagal, binary yang jalan nggak disentuh.
    local tmp_bin
    tmp_bin=$(mktemp /tmp/dockwings-new.XXXXXX)
    if ! build_wings "$tmp_bin"; then
        rm -f "$tmp_bin"
        print_error "Build gagal. Wings yang lama tetap jalan."
        exit 1
    fi

    if [ -f "$DOCKWINGS_BIN" ]; then
        cp -f "$DOCKWINGS_BIN" "${DOCKWINGS_BIN}.bak"
    fi
    install -m 755 "$tmp_bin" "$DOCKWINGS_BIN"
    rm -f "$tmp_bin"

    systemctl restart dockwings
    sleep 2

    if systemctl is-active --quiet dockwings; then
        print_success "DockWings sudah di $new dan jalan."
        print_info "Binary lama disimpan di ${DOCKWINGS_BIN}.bak."
    else
        print_error "DockWings baru gagal start. Mengembalikan binary lama..."
        if [ -f "${DOCKWINGS_BIN}.bak" ]; then
            cp -f "${DOCKWINGS_BIN}.bak" "$DOCKWINGS_BIN"
            systemctl restart dockwings
            sleep 2
            if systemctl is-active --quiet dockwings; then
                print_warn "Rollback berhasil, Wings lama jalan lagi. Cek log: journalctl -u dockwings -n 50"
            else
                print_error "Rollback juga gagal. Cek log: journalctl -u dockwings -n 50"
            fi
        else
            print_error "Nggak ada binary lama buat rollback. Cek log: journalctl -u dockwings -n 50"
        fi
        exit 1
    fi
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
    echo "  3) Update Panel (git pull, composer, migrate)"
    echo "  4) Update Node/Wings (git pull, build, restart, rollback otomatis)"
    echo "  0) Batal"
    echo ""
    read -rp "Pilih opsi [1/2/3/4/0]: " OPTION

    case $OPTION in
        1) install_panel ;;
        2) install_wings ;;
        3) update_panel ;;
        4) update_wings ;;
        0) print_info "Dibatalin."; exit 0 ;;
        *) print_error "Opsi nggak valid."; exit 1 ;;
    esac
}

# Jalanin menu hanya kalau script dieksekusi langsung (bukan di-source buat tes).
if [ "${BASH_SOURCE[0]}" = "$0" ]; then
    main
fi
