-- ============================================================
-- BRASIL DNA 2026 — Migração SQL
-- Banco: globalvisionacce_gva_database
-- Executar uma única vez para criar as tabelas do módulo
-- Prefixo bdna_ para não conflitar com tabelas existentes
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Categorias
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bdna_categorias (
  id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome    VARCHAR(100) NOT NULL,
  icone   VARCHAR(50)  DEFAULT 'bi-check-circle',
  cor_hex VARCHAR(7)   DEFAULT '#0077B6',
  ordem   INT UNSIGNED DEFAULT 0,
  ativo   TINYINT(1)   DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO bdna_categorias (id, nome, icone, cor_hex, ordem) VALUES
  (1, 'Gestao e Planejamento', 'bi-graph-up',      '#0077B6', 1),
  (2, 'Videos Promo',          'bi-camera-video',  '#F4A261', 2),
  (3, 'Webinars',              'bi-display',       '#2A9D8F', 3),
  (4, 'News e Releases',       'bi-newspaper',     '#E76F51', 4),
  (5, 'Posts SoMe',            'bi-instagram',     '#E9C46A', 5),
  (6, 'Roadshow Presencial',   'bi-geo-alt',       '#264653', 6),
  (7, 'Roadshow Virtual',      'bi-laptop',        '#6D6875', 7),
  (8, 'Eventos Especiais',     'bi-star',          '#B5838D', 8);

-- ------------------------------------------------------------
-- 2. Parceiros
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bdna_parceiros (
  id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome  VARCHAR(200) NOT NULL,
  tipo  ENUM('institucional','midia','destino','operadora','outro') DEFAULT 'outro',
  ativo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO bdna_parceiros (nome, tipo) VALUES
  ('Embratur',          'institucional'),
  ('Bureau Mundo',      'institucional'),
  ('TA Connect',        'midia'),
  ('Travel Weekly',     'midia'),
  ('CCRA',              'midia'),
  ('TravMedia',         'midia'),
  ('Hubspot',           'midia'),
  ('TMX',               'midia'),
  ('Foz do Iguacu',     'destino'),
  ('Mato Grosso do Sul','destino'),
  ('Sao Paulo',         'destino'),
  ('Bahia',             'destino'),
  ('Compass',           'operadora'),
  ('IGLTA',             'institucional'),
  ('GVA',               'operadora'),
  ('NEX',               'operadora');

-- ------------------------------------------------------------
-- 3. Tarefas (tabela principal)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bdna_tarefas (
  id                INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
  id_usuario        INT UNSIGNED    NOT NULL       COMMENT 'FK usuarios.id',
  id_categoria      INT UNSIGNED    NOT NULL,
  mes_referencia    VARCHAR(50),
  acao              VARCHAR(255),
  tarefa            VARCHAR(500)    NOT NULL,
  deadline          DATE,
  tema_conteudo     VARCHAR(255),
  data_acao         DATE,
  link_externo      VARCHAR(500),
  detalhes_promocao TEXT,
  observacoes       TEXT,
  notes             TEXT,
  status            ENUM('Pendente','Em andamento','Produzindo','Aguardando','Enviado','Publicado','Done')
                    NOT NULL DEFAULT 'Pendente',
  prioridade        ENUM('Alta','Media','Baixa') NOT NULL DEFAULT 'Media',
  created_by        INT UNSIGNED,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_bdna_cat FOREIGN KEY (id_categoria) REFERENCES bdna_categorias(id) ON UPDATE CASCADE,
  INDEX idx_bdna_status   (status),
  INDEX idx_bdna_deadline (deadline),
  INDEX idx_bdna_usuario  (id_usuario),
  INDEX idx_bdna_cat      (id_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Tabela N:N Tarefas <-> Parceiros
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bdna_tarefas_parceiros (
  id_tarefa   INT UNSIGNED NOT NULL,
  id_parceiro INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_tarefa, id_parceiro),
  CONSTRAINT fk_bdnatp_t FOREIGN KEY (id_tarefa)   REFERENCES bdna_tarefas(id)   ON DELETE CASCADE,
  CONSTRAINT fk_bdnatp_p FOREIGN KEY (id_parceiro) REFERENCES bdna_parceiros(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. Histórico de mudanças de status
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bdna_historico_status (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_tarefa     INT UNSIGNED NOT NULL,
  status_antes  VARCHAR(50),
  status_depois VARCHAR(50)  NOT NULL,
  alterado_por  INT UNSIGNED,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bdna_hs FOREIGN KEY (id_tarefa) REFERENCES bdna_tarefas(id) ON DELETE CASCADE,
  INDEX idx_bdna_hs_tarefa (id_tarefa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 6. Views para Power BI
-- ------------------------------------------------------------

-- View principal: uma linha por tarefa com todos os dados
CREATE OR REPLACE VIEW vw_bdna_powerbi AS
SELECT
  t.id,
  t.mes_referencia,
  t.acao,
  t.tarefa,
  t.tema_conteudo,
  t.deadline,
  t.data_acao,
  t.status,
  t.prioridade,
  t.observacoes,
  t.notes,
  t.detalhes_promocao,
  t.link_externo,
  t.created_at,
  t.updated_at,
  u.nome              AS responsavel,
  c.nome              AS categoria,
  c.cor_hex           AS categoria_cor,
  CASE
    WHEN t.status IN ('Done','Enviado','Publicado') THEN 'Concluida'
    WHEN t.deadline < CURDATE()
         AND t.status NOT IN ('Done','Enviado','Publicado') THEN 'Atrasada'
    WHEN t.status IN ('Em andamento','Produzindo')  THEN 'Em Progresso'
    ELSE 'Pendente'
  END                 AS status_agrupado,
  DATEDIFF(t.deadline, CURDATE()) AS dias_para_deadline,
  MONTH(t.deadline)              AS mes_deadline_num,
  MONTHNAME(t.deadline)          AS mes_deadline_nome,
  YEAR(t.deadline)               AS ano_deadline,
  CASE
    WHEN t.deadline < CURDATE()
         AND t.status NOT IN ('Done','Enviado','Publicado') THEN 1
    ELSE 0
  END                 AS flag_atrasada
FROM bdna_tarefas t
INNER JOIN usuarios        u ON t.id_usuario   = u.id
INNER JOIN bdna_categorias c ON t.id_categoria = c.id;

-- View KPI por responsável e categoria
CREATE OR REPLACE VIEW vw_bdna_kpi_responsavel AS
SELECT
  u.nome              AS responsavel,
  c.nome              AS categoria,
  COUNT(t.id)         AS total,
  SUM(t.status IN ('Done','Enviado','Publicado'))        AS concluidas,
  SUM(t.status IN ('Em andamento','Produzindo'))         AS em_progresso,
  SUM(t.status = 'Pendente')                            AS pendentes,
  SUM(t.deadline < CURDATE()
      AND t.status NOT IN ('Done','Enviado','Publicado')) AS atrasadas,
  ROUND(
    SUM(t.status IN ('Done','Enviado','Publicado')) / COUNT(t.id) * 100
  , 1)                AS perc_concluido
FROM bdna_tarefas t
INNER JOIN usuarios        u ON t.id_usuario   = u.id
INNER JOIN bdna_categorias c ON t.id_categoria = c.id
GROUP BY u.nome, c.nome;

-- View histórico para análise de fluxo no Power BI
CREATE OR REPLACE VIEW vw_bdna_historico AS
SELECT
  h.id,
  h.id_tarefa,
  t.tarefa,
  c.nome         AS categoria,
  u_resp.nome    AS responsavel,
  u_alt.nome     AS alterado_por,
  h.status_antes,
  h.status_depois,
  h.created_at   AS data_alteracao
FROM bdna_historico_status h
INNER JOIN bdna_tarefas    t      ON h.id_tarefa    = t.id
INNER JOIN bdna_categorias c      ON t.id_categoria = c.id
INNER JOIN usuarios        u_resp ON t.id_usuario   = u_resp.id
LEFT  JOIN usuarios        u_alt  ON h.alterado_por = u_alt.id;
