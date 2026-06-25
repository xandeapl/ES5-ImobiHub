# ImobiHub

# Descrição
O ImobiHub é uma aplicação web de gerenciamento imobiliário desenvolvida para facilitar a administração e divulgação de imóveis disponíveis para venda ou locação. A plataforma permite que administradores gerenciem anúncios por meio de um painel (dashboard), enquanto os usuários podem visualizar os imóveis em um catálogo público atualizado em tempo real. O projeto está alinhado ao **ODS 11 - Cidades e Comunidades Sustentáveis**, contribuindo para a organização e acesso à informação sobre moradia.

# Objetivo
Desenvolver um sistema funcional de gerenciamento imobiliário, permitindo cadastro, edição e visualização de imóveis de forma dinâmica e organizada.

# ODS
ODS 11 - Cidades e Comunidades Sustentáveis

# Tecnologias Utilizadas
- **Backend:** PHP 8.1+
- **Banco de Dados:** SQLite
- **Frontend:** HTML + CSS
- **Servidor local:** PHP built-in server


# Funcionalidades
- Cadastro de anúncio com upload de fotos 
- Edição de anúncio cadastrado  
- Edição rápida de preço  
- Exclusão de anúncio  
- Alternância de status (vendido/disponível)  
- Filtros no catálogo (tipo, busca, ordenação e vendidos)  

## Recuperação de senha por e-mail (SMTP)

O fluxo de recuperação agora envia e-mail real com link de redefinição.

Defina as variáveis de ambiente antes de iniciar o servidor:

- `IMOBIHUB_APP_URL` (ex.: `http://localhost:8000`)
- `IMOBIHUB_SMTP_HOST` (ex.: `smtp.gmail.com`)
- `IMOBIHUB_SMTP_PORT` (ex.: `587` para TLS ou `465` para SSL)
- `IMOBIHUB_SMTP_SECURE` (`tls` ou `ssl`)
- `IMOBIHUB_SMTP_USER`
- `IMOBIHUB_SMTP_PASS`
- `IMOBIHUB_MAIL_FROM`
- `IMOBIHUB_MAIL_FROM_NAME`

Exemplo no PowerShell (sessão atual):

```powershell
$env:IMOBIHUB_APP_URL = 'http://localhost:8000'
$env:IMOBIHUB_SMTP_HOST = 'smtp.gmail.com'
$env:IMOBIHUB_SMTP_PORT = '587'
$env:IMOBIHUB_SMTP_SECURE = 'tls'
$env:IMOBIHUB_SMTP_USER = 'seu-email@gmail.com'
$env:IMOBIHUB_SMTP_PASS = 'sua-senha-ou-app-password'
$env:IMOBIHUB_MAIL_FROM = 'seu-email@gmail.com'
$env:IMOBIHUB_MAIL_FROM_NAME = 'ImobiHub'
```

## Deploy AWS EC2

Guia completo de deploy em EC2: [DEPLOY_EC2.md](DEPLOY_EC2.md)



# Equipe

Alexandre Rodrigues Ramos – 0021171
Fellipe Ferreira Gomes – 0021345
Icaro Kaic Bernardes Rocha – 0021391
Raycca Mell dos Santos – 0020850
Wallyson Freitas Alves – 0020879
