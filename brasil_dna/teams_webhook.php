<?php
// teams_webhook.php
// URLs definidas em includes/db_connect.php (fora do git):
//
//   Canal (toda a equipe vê):
//   define('TEAMS_WEBHOOK_URL', 'https://...');
//
//   Chat privado — mapeado por ID do usuário na tabela `usuarios`:
//   define('TEAMS_CHAT_WEBHOOKS', [
//       1 => 'https://... fluxo Daniela',
//       4 => 'https://... fluxo Anna',
//   ]);

// ============================================================
// HELPER — gera link do Teams Calendar (abre no app Teams)
// Formato: https://teams.microsoft.com/l/meeting/new?...
// O Teams detecta se o app está instalado e abre nele;
// caso contrário, cai no Teams Web.
// ============================================================
function _teamsCalendarLink(string $titulo, string $deadlineYmd, string $descricao = ''): string {
    if (empty($deadlineYmd)) return '';

    $dt = DateTime::createFromFormat('Y-m-d', $deadlineYmd);
    if (!$dt) return '';

    // Teams espera formato ISO 8601 sem espaços
    $startDt = $dt->format('Y-m-d') . 'T09:00:00';  // 09h
    $endDt   = $dt->format('Y-m-d') . 'T10:00:00';  // 10h

    $params = http_build_query([
        'subject'   => $titulo,
        'startTime' => $startDt,
        'endTime'   => $endDt,
        'content'   => $descricao,
    ]);

    return 'https://teams.microsoft.com/l/meeting/new?' . $params;
}

// ============================================================
// 1. NOTIFICAÇÃO NO CANAL (visível para toda a equipe)
// ============================================================
function notificarTeams(array $dados): bool {

    if (!defined('TEAMS_WEBHOOK_URL') || empty(TEAMS_WEBHOOK_URL)) return false;

    $emojis = [
        'Gestão & Planejamento' => '📊',
        'Videos Promo'          => '🎬',
        'Webinars'              => '🎤',
        'News & Releases'       => '📰',
        'Posts SoMe'            => '📱',
        'Roadshow Presencial'   => '🗺️',
        'Roadshow Virtual'      => '💻',
        'Eventos Especiais'     => '⭐',
    ];

    $prioridadeEmoji = ['Alta' => '🔴', 'Media' => '🟡', 'Baixa' => '🟢'];

    $emoji    = $emojis[$dados['categoria']]           ?? '📋';
    $priEmoji = $prioridadeEmoji[$dados['prioridade']] ?? '⚪';
    $deadline = !empty($dados['deadline'])
                ? date('d/m/Y', strtotime($dados['deadline']))
                : 'Não definido';

    $descCal = 'Tarefa Brasil DNA 2026 | Categoria: ' . ($dados['categoria'] ?? '')
             . ' | Responsável: ' . ($dados['responsavel'] ?? '')
             . ' | Prioridade: ' . ($dados['prioridade'] ?? '')
             . ' | ' . ($dados['link_sistema'] ?? '');

    $calLink = _teamsCalendarLink(
        'Brasil DNA 2026: ' . $dados['tarefa'],
        $dados['deadline'] ?? '',
        $descCal
    );

    $actions = [[
        "type"  => "Action.OpenUrl",
        "title" => "🔗 Ver no Sistema",
        "url"   => $dados['link_sistema'] ?? "https://insights.gvacompany.com/brasil_dna/"
    ]];
    if (!empty($calLink)) {
        $actions[] = [
            "type"  => "Action.OpenUrl",
            "title" => "📅 Adicionar ao Teams Calendar",
            "url"   => $calLink
        ];
    }

    $payload = [
        "type"        => "message",
        "attachments" => [[
            "contentType" => "application/vnd.microsoft.card.adaptive",
            "content"     => [
                "\$schema" => "http://adaptivecards.io/schemas/adaptive-card.json",
                "type"    => "AdaptiveCard",
                "version" => "1.4",
                "body"    => [
                    [
                        "type"   => "TextBlock",
                        "size"   => "Large",
                        "weight" => "Bolder",
                        "text"   => "{$emoji} Nova Tarefa — Brasil DNA 2026",
                        "color"  => "Accent",
                        "wrap"   => true
                    ],
                    [
                        "type"     => "TextBlock",
                        "text"     => $dados['categoria'],
                        "isSubtle" => true,
                        "spacing"  => "None"
                    ],
                    [
                        "type"    => "FactSet",
                        "spacing" => "Medium",
                        "facts"   => [
                            ["title" => "👤 Responsável", "value" => $dados['responsavel']],
                            ["title" => "📋 Tarefa",      "value" => $dados['tarefa']],
                            ["title" => "📅 Mês",         "value" => $dados['mes'] ?? '—'],
                            ["title" => "⏰ Deadline",     "value" => $deadline],
                            ["title" => "{$priEmoji} Prioridade", "value" => $dados['prioridade']],
                            ["title" => "📌 Status",      "value" => $dados['status']],
                        ]
                    ]
                ],
                "actions" => $actions
            ]
        ]]
    ];

    return _enviarWebhook(TEAMS_WEBHOOK_URL, $payload);
}

// ============================================================
// 2. NOTIFICAÇÃO VIA CHAT PRIVADO (somente o responsável vê)
// ============================================================
function notificarTeamsChat(array $dados): bool {

    if (!defined('TEAMS_CHAT_WEBHOOKS')) return false;

    $idUsuario = intval($dados['id_usuario'] ?? 0);
    if (!$idUsuario) return false;

    $mapa = TEAMS_CHAT_WEBHOOKS;
    if (empty($mapa[$idUsuario])) return false;

    $url      = $mapa[$idUsuario];
    $nomeResp = $dados['responsavel'] ?? 'Responsável';

    $prioridadeEmoji = ['Alta' => '🔴', 'Media' => '🟡', 'Baixa' => '🟢'];
    $priEmoji = $prioridadeEmoji[$dados['prioridade']] ?? '⚪';
    $deadline = !empty($dados['deadline'])
                ? date('d/m/Y', strtotime($dados['deadline']))
                : 'Não definido';

    $descCal = 'Tarefa Brasil DNA 2026 | Categoria: ' . ($dados['categoria'] ?? '')
             . ' | Prioridade: ' . ($dados['prioridade'] ?? '')
             . ' | ' . ($dados['link_sistema'] ?? '');

    $calLink = _teamsCalendarLink(
        'Brasil DNA 2026: ' . $dados['tarefa'],
        $dados['deadline'] ?? '',
        $descCal
    );

    $actions = [[
        "type"  => "Action.OpenUrl",
        "title" => "🔗 Ver no Sistema",
        "url"   => $dados['link_sistema'] ?? "https://insights.gvacompany.com/brasil_dna/"
    ]];
    if (!empty($calLink)) {
        $actions[] = [
            "type"  => "Action.OpenUrl",
            "title" => "📅 Adicionar ao Teams Calendar",
            "url"   => $calLink
        ];
    }

    $body = [
        [
            "type"   => "TextBlock",
            "size"   => "Large",
            "weight" => "Bolder",
            "text"   => "👋 Olá, {$nomeResp}! Você tem uma nova tarefa.",
            "color"  => "Accent",
            "wrap"   => true
        ],
        [
            "type"     => "TextBlock",
            "text"     => "Brasil DNA 2026 — " . ($dados['categoria'] ?? ''),
            "isSubtle" => true,
            "spacing"  => "None"
        ],
        [
            "type"    => "FactSet",
            "spacing" => "Medium",
            "facts"   => [
                ["title" => "📋 Tarefa",   "value" => $dados['tarefa']],
                ["title" => "📅 Mês",      "value" => $dados['mes'] ?? '—'],
                ["title" => "⏰ Deadline",  "value" => $deadline],
                ["title" => "{$priEmoji} Prioridade", "value" => $dados['prioridade']],
                ["title" => "📌 Status",   "value" => $dados['status']],
            ]
        ]
    ];

    if (!empty($dados['observacoes'])) {
        $body[] = [
            "type"    => "TextBlock",
            "text"    => "💬 " . $dados['observacoes'],
            "wrap"    => true,
            "spacing" => "Medium",
            "color"   => "Warning"
        ];
    }

    $payload = [
        "type"        => "message",
        "attachments" => [[
            "contentType" => "application/vnd.microsoft.card.adaptive",
            "content"     => [
                "\$schema" => "http://adaptivecards.io/schemas/adaptive-card.json",
                "type"    => "AdaptiveCard",
                "version" => "1.4",
                "body"    => $body,
                "actions" => $actions
            ]
        ]]
    ];

    return _enviarWebhook($url, $payload);
}

// ============================================================
// HELPER — envia o payload via cURL
// ============================================================
function _enviarWebhook(string $url, array $payload): bool {
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return in_array($httpCode, [200, 202]);
}
