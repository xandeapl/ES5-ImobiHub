#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/imobihub"
REPO_DIR_DEFAULT="$HOME/ES5-ImobiHub-main"
REPO_DIR="${1:-$REPO_DIR_DEFAULT}"

if [ ! -d "$REPO_DIR" ]; then
  echo "Diretorio do projeto nao encontrado: $REPO_DIR"
  echo "Uso: bash deploy/ec2/setup-ec2.sh /caminho/do/projeto"
  exit 1
fi

echo "[1/8] Instalando pacotes..."
if command -v dnf >/dev/null 2>&1; then
  PKG_MGR="dnf"
  sudo dnf update -y
  PHP_BASE_PKGS=(nginx php-fpm php-cli rsync unzip)
  PHP_SQLITE_PKGS=()

  while IFS= read -r pkg; do
    [ -n "$pkg" ] && PHP_SQLITE_PKGS+=("$pkg")
  done < <(dnf list available 2>/dev/null | awk '/^php([0-9.]+)?-(pdo_sqlite|sqlite3)\./ {print $1}')

  if [ ${#PHP_SQLITE_PKGS[@]} -eq 0 ]; then
    while IFS= read -r pkg; do
      [ -n "$pkg" ] && PHP_SQLITE_PKGS+=("$pkg")
    done < <(dnf list available 2>/dev/null | awk '/^php-(pdo_sqlite|sqlite3)\./ {print $1}')
  fi

  if [ ${#PHP_SQLITE_PKGS[@]} -eq 0 ]; then
    echo "Nao foi possivel localizar pacotes PHP SQLite via dnf."
    echo "Execute: dnf list available | grep -Ei 'php.*(sqlite|pdo_sqlite)'"
    exit 1
  fi

  sudo dnf install -y "${PHP_BASE_PKGS[@]}" "${PHP_SQLITE_PKGS[@]}"
else
  PKG_MGR="apt"
  sudo apt update
  sudo apt install -y nginx php-fpm php-cli php-sqlite3 rsync unzip
fi

echo "[2/8] Ativando servicos..."
sudo systemctl enable nginx php-fpm 2>/dev/null || true
sudo systemctl enable nginx php8.5-fpm 2>/dev/null || true

echo "[3/8] Publicando codigo..."
sudo mkdir -p "$APP_DIR"
sudo rsync -av --delete \
  --exclude '.git' \
  --exclude 'node_modules' \
  "$REPO_DIR/" "$APP_DIR/"

echo "[4/8] Ajustando permissoes de escrita..."
sudo mkdir -p "$APP_DIR/php-app/data" "$APP_DIR/php-app/public/uploads"
WEB_USER="www-data"
if id apache >/dev/null 2>&1; then
  WEB_USER="apache"
elif id nginx >/dev/null 2>&1; then
  WEB_USER="nginx"
fi
sudo chown -R "$WEB_USER:$WEB_USER" "$APP_DIR/php-app/data" "$APP_DIR/php-app/public/uploads"
sudo chmod -R 775 "$APP_DIR/php-app/data" "$APP_DIR/php-app/public/uploads"

echo "[5/8] Preparando .env..."
if [ ! -f "$APP_DIR/php-app/.env" ]; then
  sudo cp "$APP_DIR/php-app/.env.example" "$APP_DIR/php-app/.env"
  sudo chown "$WEB_USER:$WEB_USER" "$APP_DIR/php-app/.env"
  echo "Arquivo .env criado em $APP_DIR/php-app/.env"
  echo "Edite com seus dados SMTP e URL publica antes de testar recuperacao de senha."
fi

echo "[6/8] Configurando Nginx..."
if [ -d /etc/nginx/conf.d ]; then
  sudo rm -f /etc/nginx/conf.d/default.conf
  sudo cp "$APP_DIR/deploy/ec2/nginx-imobihub.conf" /etc/nginx/conf.d/imobihub.conf
else
  sudo cp "$APP_DIR/deploy/ec2/nginx-imobihub.conf" /etc/nginx/sites-available/imobihub
  sudo ln -sf /etc/nginx/sites-available/imobihub /etc/nginx/sites-enabled/imobihub
  sudo rm -f /etc/nginx/sites-enabled/default
fi

# Ajusta socket do PHP-FPM de forma dinamica
PHP_FPM_SOCK="$(find /run -name '*fpm*.sock' 2>/dev/null | head -n 1 || true)"
if [ -z "$PHP_FPM_SOCK" ]; then
  echo "Nao encontrei socket do php-fpm em /run/php/."
  exit 1
fi
PHP_FPM_SOCK_BASENAME="$(basename "$PHP_FPM_SOCK")"
if [ -f /etc/nginx/conf.d/imobihub.conf ]; then
  sudo sed -i "s#php8.3-fpm.sock#$PHP_FPM_SOCK_BASENAME#g" /etc/nginx/conf.d/imobihub.conf
else
  sudo sed -i "s#php8.3-fpm.sock#$PHP_FPM_SOCK_BASENAME#g" /etc/nginx/sites-available/imobihub
fi

echo "[7/8] Validando e reiniciando servicos..."
sudo nginx -t
PHP_FPM_SERVICE="$(systemctl list-unit-files --type=service | awk '/^php([0-9.]+-)?fpm\.service/ {print $1; exit}')"
if [ -z "$PHP_FPM_SERVICE" ]; then
  PHP_FPM_SERVICE="php-fpm.service"
fi

sudo systemctl enable nginx "$PHP_FPM_SERVICE"
sudo systemctl restart "$PHP_FPM_SERVICE"
sudo systemctl restart nginx

if ! php -m 2>/dev/null | grep -Eqi '^(pdo_sqlite|sqlite3)$'; then
  echo "SQLite/PDO nao apareceu no php -m. Verifique o service do PHP-FPM e os pacotes instalados."
fi

echo "[8/8] Liberando firewall (se UFW estiver ativo)..."
if sudo ufw status | grep -qi active; then
  sudo ufw allow 'Nginx Full'
fi

echo "Deploy concluido."
echo "Acesse: http://IP_PUBLICO_EC2"
echo "Para HTTPS, instale certbot conforme a distro e rode a configuracao do nginx."
