-- ================================================
-- Módulo Demandas — Brasil DNA 2026
-- GVA Insights
-- ================================================

CREATE TABLE IF NOT EXISTS `demandas` (
  `id`               INT           NOT NULL AUTO_INCREMENT,
  `id_usuario`       INT           NOT NULL,
  `categoria`        VARCHAR(100)  NOT NULL,
  `mes`              VARCHAR(20)   DEFAULT NULL,
  `acao`             VARCHAR(255)  DEFAULT NULL,
  `tarefa`           TEXT          NOT NULL,
  `deadline`         DATE          DEFAULT NULL,
  `data_publicacao`  DATE          DEFAULT NULL,
  `parceiros`        VARCHAR(500)  DEFAULT NULL,
  `detalhes`         TEXT          DEFAULT NULL,
  `tipo_conteudo`    VARCHAR(255)  DEFAULT NULL,
  `link_externo`     VARCHAR(500)  DEFAULT NULL,
  `status`           ENUM('Done','Em andamento','Produzindo','Enviado','Publicado','Aguardando','Pendente','Atrasado')
                                   NOT NULL DEFAULT 'Pendente',
  `prioridade`       ENUM('Alta','Média','Baixa') NOT NULL DEFAULT 'Média',
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario`  (`id_usuario`),
  KEY `idx_status`   (`status`),
  KEY `idx_categoria`(`categoria`),
  KEY `idx_deadline` (`deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `demandas_historico` (
  `id`               INT           NOT NULL AUTO_INCREMENT,
  `id_demanda`       INT           NOT NULL,
  `id_usuario`       INT           NOT NULL,
  `status_anterior`  VARCHAR(50)   DEFAULT '',
  `status_novo`      VARCHAR(50)   NOT NULL,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_demanda`  (`id_demanda`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- View consolidada para o Power BI
CREATE OR REPLACE VIEW vw_demandas_powerbi AS
SELECT
    d.id,
    u.nome                                        AS responsavel,
    d.categoria,
    d.mes,
    d.acao,
    d.tarefa,
    d.deadline,
    d.data_publicacao,
    d.parceiros,
    d.tipo_conteudo,
    d.link_externo,
    d.status,
    d.prioridade,
    CASE WHEN d.deadline < CURDATE() AND d.status <> 'Done' THEN 'Sim' ELSE 'Não' END AS atrasada,
    DATEDIFF(d.deadline, CURDATE())               AS dias_para_deadline,
    DATE_FORMAT(d.created_at, '%Y-%m-%d')         AS data_criacao,
    DATE_FORMAT(d.updated_at, '%Y-%m-%d')         AS ultima_atualizacao,
    YEAR(d.deadline)                              AS ano_deadline,
    MONTH(d.deadline)                             AS mes_num_deadline,
    MONTHNAME(d.deadline)                         AS mes_nome_deadline
FROM demandas d
JOIN usuarios u ON d.id_usuario = u.id;
