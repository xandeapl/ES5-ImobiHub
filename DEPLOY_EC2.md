# Deploy na AWS EC2 (Ubuntu + Nginx + PHP-FPM)

Este guia publica o ImobiHub em uma instancia EC2.

## 1) Requisitos na AWS

- Instancia EC2 Ubuntu 22.04 ou 24.04
- Security Group com regras:
  - 22 (SSH) liberado para seu IP
  - 80 (HTTP) liberado
  - 443 (HTTPS) liberado (se usar SSL)
- Chave .pem para acesso SSH

## 2) Conectar por SSH

```bash
ssh -i sua-chave.pem ubuntu@SEU_IP_PUBLICO
```

## 3) Enviar o projeto para a EC2

Opcao A: git clone

```bash
git clone <URL_DO_REPOSITORIO> ES5-ImobiHub-main
```

Opcao B: copiar via scp da sua maquina local

```bash
scp -i sua-chave.pem -r ./ES5-ImobiHub-main ubuntu@SEU_IP_PUBLICO:~/ES5-ImobiHub-main
```

## 4) Rodar setup automatico

Dentro da EC2:

```bash
cd ~/ES5-ImobiHub-main
chmod +x deploy/ec2/setup-ec2.sh
bash deploy/ec2/setup-ec2.sh ~/ES5-ImobiHub-main
```

## 5) Configurar .env de producao

```bash
sudo nano /var/www/imobihub/php-app/.env
```

Preencha pelo menos:

```env
IMOBIHUB_APP_URL=http://SEU_IP_OU_DOMINIO
IMOBIHUB_SMTP_HOST=smtp.seuprovedor.com
IMOBIHUB_SMTP_PORT=587
IMOBIHUB_SMTP_SECURE=tls
IMOBIHUB_SMTP_USER=seu_usuario
IMOBIHUB_SMTP_PASS=sua_senha
IMOBIHUB_MAIL_FROM=no-reply@seu-dominio.com
IMOBIHUB_MAIL_FROM_NAME=ImobiHub
```

Depois reinicie servicos:

```bash
sudo systemctl restart nginx
sudo systemctl restart $(systemctl list-unit-files --type=service | awk '/^php[0-9.]+-fpm.service/ {print $1; exit}')
```

## 6) Testar aplicacao

Abra no navegador:

- http://SEU_IP_PUBLICO

## 7) (Opcional) HTTPS com Certbot

Se tiver dominio apontando para a EC2:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d seu-dominio.com -d www.seu-dominio.com
```

## 8) Atualizacao futura

Quando alterar codigo:

```bash
cd ~/ES5-ImobiHub-main
# atualize codigo (git pull ou novo upload)
bash deploy/ec2/setup-ec2.sh ~/ES5-ImobiHub-main
```
