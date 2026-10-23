-- Migration: 20261023_0001_create_saas_tenant_crons.sql
-- Description: Cria tabela relacional saas_tenant_crons para gestão de agendamentos e CronJobs K3s por tenant

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `saas_tenant_crons` (
  `id_cron` INT AUTO_INCREMENT PRIMARY KEY,
  `id_tenant` INT NOT NULL COMMENT 'Vínculo com a instância em saas_tenants',
  `nome_job` VARCHAR(60) NOT NULL COMMENT 'Identificador único do Job no Kubernetes (ex: faturas-recorrentes)',
  `descricao` VARCHAR(150) NULL COMMENT 'Descrição legível da rotina agendada',
  `script_path` VARCHAR(255) NOT NULL COMMENT 'Caminho do script PHP (ex: dinovatech/cron/gerar_faturas_recorrencias.php)',
  `argumentos` VARCHAR(255) NULL COMMENT 'Argumentos CLI adicionais opcionais',
  `cron_expression` VARCHAR(50) NOT NULL DEFAULT '0 6 1 * *' COMMENT 'Expressão Crontab de agendamento',
  `ativo` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Ativo, 0 = Pausado',
  `ultimo_status` VARCHAR(30) NULL DEFAULT NULL COMMENT 'Último status reportado pelo cluster (RUNNING, SUCCESS, FAILED, PENDING)',
  `ultima_execucao` DATETIME NULL DEFAULT NULL COMMENT 'Data/hora da última execução registrada',
  `ultimo_log` TEXT NULL DEFAULT NULL COMMENT 'Resumo ou saída da última execução',
  `data_criacao` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_tenant_job` (`id_tenant`, `nome_job`),
  INDEX `idx_tenant_crons_tenant` (`id_tenant`),
  INDEX `idx_tenant_crons_ativo` (`ativo`),
  CONSTRAINT `fk_saas_tenant_crons_tenant` 
    FOREIGN KEY (`id_tenant`) REFERENCES `saas_tenants` (`id_tenant`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
