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

echo "[1/7] Instalando pacotes..."
sudo apt update
sudo apt install -y nginx php-fpm php-cli php-sqlite3 rsync unzip

echo "[2/7] Publicando codigo..."
sudo mkdir -p "$APP_DIR"
sudo rsync -av --delete \
  --exclude '.git' \
  --exclude 'node_modules' \
  "$REPO_DIR/" "$APP_DIR/"

echo "[3/7] Ajustando permissoes de escrita..."
sudo mkdir -p "$APP_DIR/php-app/data" "$APP_DIR/php-app/public/uploads"
sudo chown -R www-data:www-data "$APP_DIR/php-app/data" "$APP_DIR/php-app/public/uploads"
sudo chmod -R 775 "$APP_DIR/php-app/data" "$APP_DIR/php-app/public/uploads"

echo "[4/7] Preparando .env..."
if [ ! -f "$APP_DIR/php-app/.env" ]; then
  sudo cp "$APP_DIR/php-app/.env.example" "$APP_DIR/php-app/.env"
  sudo chown www-data:www-data "$APP_DIR/php-app/.env"
  echo "Arquivo .env criado em $APP_DIR/php-app/.env"
  echo "Edite com seus dados SMTP e URL publica antes de testar recuperacao de senha."
fi

echo "[5/7] Configurando Nginx..."
sudo cp "$APP_DIR/deploy/ec2/nginx-imobihub.conf" /etc/nginx/sites-available/imobihub
sudo ln -sf /etc/nginx/sites-available/imobihub /etc/nginx/sites-enabled/imobihub
sudo rm -f /etc/nginx/sites-enabled/default

# Ajusta socket do PHP-FPM de forma dinamica
PHP_FPM_SOCK="$(ls /run/php/php*-fpm.sock 2>/dev/null | head -n 1 || true)"
if [ -z "$PHP_FPM_SOCK" ]; then
  echo "Nao encontrei socket do php-fpm em /run/php/."
  exit 1
fi
PHP_FPM_SOCK_BASENAME="$(basename "$PHP_FPM_SOCK")"
sudo sed -i "s#php8.3-fpm.sock#$PHP_FPM_SOCK_BASENAME#g" /etc/nginx/sites-available/imobihub

echo "[6/7] Validando e reiniciando servicos..."
sudo nginx -t
PHP_FPM_SERVICE="$(systemctl list-unit-files --type=service | awk '/^php[0-9.]+-fpm.service/ {print $1; exit}')"
if [ -z "$PHP_FPM_SERVICE" ]; then
  PHP_FPM_SERVICE="php-fpm.service"
fi

sudo systemctl enable nginx "$PHP_FPM_SERVICE"
sudo systemctl restart "$PHP_FPM_SERVICE"
sudo systemctl restart nginx

echo "[7/7] Liberando firewall (se UFW estiver ativo)..."
if sudo ufw status | grep -qi active; then
  sudo ufw allow 'Nginx Full'
fi

echo "Deploy concluido."
echo "Acesse: http://IP_PUBLICO_EC2"
echo "Para HTTPS, configure dominio e rode: sudo apt install -y certbot python3-certbot-nginx && sudo certbot --nginx"
