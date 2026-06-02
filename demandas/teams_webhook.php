<?php
// teams_webhook.php
//
// Defina no includes/db_connect.php (fora do git):
//
//   Canal (toda a equipe vê):
//   define('TEAMS_WEBHOOK_URL', 'https://...');
//
//   Chat privado por ID do usuário:
//   define('TEAMS_CHAT_WEBHOOKS', [
//       1 => 'https://... fluxo Daniela',
//       4 => 'https://... fluxo Anna',
//   ]);

// ============================================================
// HELPER — gera link do Outlook Web para adicionar evento
// ============================================================
function _outlookCalendarLink(string $titulo, string $deadlineYmd, string $descricao = ''): string
{
    if (empty($deadlineYmd)) return '';

    $dt = DateTime::createFromFormat('Y-m-d', $deadlineYmd);
    if (!$dt) return '';

    $params = http_build_query([
        'path'    => '/calendar/action/compose',
        'rru'     => 'addevent',
        'subject' => $titulo,
        'startdt' => $dt->format('Y-m-d') . 'T09:00:00',
        'enddt'   => $dt->format('Y-m-d') . 'T10:00:00',
        'body'    => $descricao,
    ]);

    return 'https://outlook.office.com/calendar/0/deeplink/compose?' . $params;
}

// ============================================================
// HELPER — monta array de facts para o FactSet
// ============================================================
function _buildFacts(array $dados, string $priEmoji, string $deadline): array
{
    $facts = [
        ['title' => '📋 Tarefa',               'value' => $dados['tarefa']],
        ['title' => '🗂️ Categoria',            'value' => $dados['categoria']],
        ['title' => '📅 Mês de Referência',    'value' => $dados['mes']       ?? '—'],
        ['title' => '⏰ Deadline',              'value' => $deadline],
        ['title' => $priEmoji . ' Prioridade', 'value' => $dados['prioridade']],
        ['title' => '📌 Status',               'value' => $dados['status']],
    ];

    if (!empty($dados['empresas'])) {
        $facts[] = ['title' => '🏢 Empresas',  'value' => $dados['empresas']];
    }
    if (!empty($dados['clientes'])) {
        $facts[] = ['title' => '👤 Clientes',  'value' => $dados['clientes']];
    }
    if (!empty($dados['observacoes'])) {
        $facts[] = ['title' => '💬 Observações', 'value' => $dados['observacoes']];
    }

    return $facts;
}

// ============================================================
// HELPER — monta array de actions (Ver sistema + Agenda)
// ============================================================
function _buildActions(array $dados, string $calLink): array
{
    $actions = [[
        'type'  => 'Action.OpenUrl',
        'title' => '🔗 Ver no Sistema',
        'url'   => $dados['link_sistema'] ?? 'https://insights.gvacompany.com/demandas/',
    ]];

    if (!empty($calLink)) {
        $actions[] = [
            'type'  => 'Action.OpenUrl',
            'title' => '📅 Adicionar à Agenda',
            'url'   => $calLink,
        ];
    }

    return $actions;
}

// ============================================================
// HELPER — monta payload Adaptive Card completo
// ============================================================
function _buildPayload(string $titulo, string $subtitulo, array $facts, array $actions): array
{
    return [
        'type'        => 'message',
        'attachments' => [[
            'contentType' => 'application/vnd.microsoft.card.adaptive',
            'content'     => [
                '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                'type'    => 'AdaptiveCard',
                'version' => '1.4',
                'body'    => [
                    [
                        'type'   => 'TextBlock',
                        'size'   => 'Large',
                        'weight' => 'Bolder',
                        'text'   => $titulo,
                        'color'  => 'Accent',
                        'wrap'   => true,
                    ],
                    [
                        'type'     => 'TextBlock',
                        'text'     => $subtitulo,
                        'isSubtle' => true,
                        'spacing'  => 'None',
                        'wrap'     => true,
                    ],
                    [
                        'type'    => 'FactSet',
                        'spacing' => 'Medium',
                        'facts'   => $facts,
                    ],
                ],
                'actions' => $actions,
            ],
        ]],
    ];
}

// ============================================================
// 1. NOTIFICAÇÃO NO CANAL (visível para toda a equipe)
// ============================================================
function notificarTeams(array $dados): bool
{
    if (!defined('TEAMS_WEBHOOK_URL') || empty(TEAMS_WEBHOOK_URL)) return false;

    $emojis = [
        'Gestão & Planejamento'                => '📊',
        'Videos Promo'                         => '🎬',
        'Webinars'                             => '🎤',
        'News & Releases'                      => '📰',
        'Posts SoMe'                           => '📱',
        'Roadshow Presencial'                  => '🗺️',
        'Roadshow Virtual / Eventos Especiais' => '💻',
    ];
    $prioridadeEmoji = ['Alta' => '🔴', 'Media' => '🟡', 'Média' => '🟡', 'Baixa' => '🟢'];

    $emoji    = $emojis[$dados['categoria']] ?? '📋';
    $priEmoji = $prioridadeEmoji[$dados['prioridade']] ?? '⚪';
    $deadline = !empty($dados['deadline'])
                ? date('d/m/Y', strtotime($dados['deadline']))
                : 'Não definido';

    $descCal = implode('\n', array_filter([
        'Responsável: '  . ($dados['responsavel'] ?? ''),
        'Categoria: '    . ($dados['categoria']   ?? ''),
        'Prioridade: '   . ($dados['prioridade']  ?? ''),
        !empty($dados['empresas']) ? 'Empresas: '  . $dados['empresas'] : '',
        !empty($dados['clientes']) ? 'Clientes: '  . $dados['clientes'] : '',
        'Sistema: '      . ($dados['link_sistema'] ?? ''),
    ]));

    $calLink = _outlookCalendarLink(
        'Demanda: ' . ($dados['tarefa'] ?? ''),
        $dados['deadline'] ?? '',
        $descCal
    );

    $titulo    = "{$emoji} Nova Demanda — " . ($dados['categoria'] ?? '');
    $subtitulo = '👤 Responsável: ' . ($dados['responsavel'] ?? '');
    $facts     = _buildFacts($dados, $priEmoji, $deadline);
    $actions   = _buildActions($dados, $calLink);

    return _enviarWebhook(TEAMS_WEBHOOK_URL, _buildPayload($titulo, $subtitulo, $facts, $actions));
}

// ============================================================
// 2. NOTIFICAÇÃO VIA CHAT PRIVADO (somente o responsável vê)
// ============================================================
function notificarTeamsChat(array $dados): bool
{
    if (!defined('TEAMS_CHAT_WEBHOOKS')) return false;

    $idUsuario = (int)($dados['id_usuario'] ?? 0);
    if (!$idUsuario) return false;

    $mapa = TEAMS_CHAT_WEBHOOKS;
    if (empty($mapa[$idUsuario])) return false;

    $prioridadeEmoji = ['Alta' => '🔴', 'Media' => '🟡', 'Média' => '🟡', 'Baixa' => '🟢'];

    $priEmoji  = $prioridadeEmoji[$dados['prioridade']] ?? '⚪';
    $nomeResp  = $dados['responsavel'] ?? 'Responsável';
    $deadline  = !empty($dados['deadline'])
                 ? date('d/m/Y', strtotime($dados['deadline']))
                 : 'Não definido';

    $descCal = implode('\n', array_filter([
        'Categoria: '   . ($dados['categoria']  ?? ''),
        'Prioridade: '  . ($dados['prioridade'] ?? ''),
        !empty($dados['empresas']) ? 'Empresas: ' . $dados['empresas'] : '',
        !empty($dados['clientes']) ? 'Clientes: ' . $dados['clientes'] : '',
        'Sistema: '     . ($dados['link_sistema'] ?? ''),
    ]));

    $calLink = _outlookCalendarLink(
        'Demanda: ' . ($dados['tarefa'] ?? ''),
        $dados['deadline'] ?? '',
        $descCal
    );

    $titulo    = "👋 Olá, {$nomeResp}! Você tem uma nova demanda.";
    $subtitulo = '🗂️ ' . ($dados['categoria'] ?? '');
    $facts     = _buildFacts($dados, $priEmoji, $deadline);
    $actions   = _buildActions($dados, $calLink);

    return _enviarWebhook($mapa[$idUsuario], _buildPayload($titulo, $subtitulo, $facts, $actions));
}

// ============================================================
// HELPER — envia o payload via cURL
// ============================================================
function _enviarWebhook(string $url, array $payload): bool
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 10,
    ]);
    curl_exec($ch);
    $ok = in_array(curl_getinfo($ch, CURLINFO_HTTP_CODE), [200, 202]);
    curl_close($ch);
    return $ok;
}
