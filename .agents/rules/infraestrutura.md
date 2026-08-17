# Regras de Infraestrutura e Ambiente (Dinovatech)

1. **Topologia dos Servidores**:
   - **VPS**: Oracle Cloud Free Tier (Ubuntu, 1 vCPU, 1 GB RAM, 100 GB SSD) rodando Docker (`ilunne/php7.4-mysqli`, Caddy e Portainer).
   - **Banco de Dados**: Instância remota MariaDB na Oracle (opera em UTC). A conexão no `database.php` sempre executa `SET time_zone = '-03:00'`.
   - **Storage (S3 / Oracle Object Storage)**: Uploads via URL Pré-Autenticada (PAR) de longa duração. Não há exclusão direta de arquivos no storage via PAR.

2. **Compatibilidade de Código**:
   - Código PHP compatível com **PHP 7.4**.
   - Tratar datas sempre com base no fuso `America/Sao_Paulo` (GMT-3).
