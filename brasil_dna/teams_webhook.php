<?php
// teams_webhook.php
// URLs definidas em includes/config.php (fora do git):
//
//   Canal  (todos veem):
//   define('TEAMS_WEBHOOK_URL',      'https://outlook.office.com/webhook/...');
//
//   Chat privado (mensagem direta ao responsável):
//   Para cada responsável, crie um fluxo "Enviar alertas de webhook para um chat"
//   no Teams e salve a URL correspondente no config.php assim:
//   define('TEAMS_CHAT_WEBHOOKS', [
//       'Gisele'    => 'https://outlook.office.com/webhook/gisele...',
//       'Anna'      => 'https://outlook.office.com/webhook/anna...',
//       'Henrique'  => 'https://outlook.office.com/webhook/henrique...',
//       'Marissol'  => 'https://outlook.office.com/webhook/marissol...',
//       'Dani Lima' => 'https://outlook.office.com/webhook/danilima...',
//       'Isabella'  => 'https://outlook.office.com/webhook/isabella...',
//   ]);
//
// OBS: o array usa o campo "nome" do usuário como chave — deve bater exatamente
//      com o valor retornado da tabela `usuarios`.

// ============================================================
// 1. NOTIFICAÇÃO NO CANAL (visível para toda a equipe)
// ============================================================
function notificarTeams(array $dados): bool {

    if (!defined('TEAMS_WEBHOOK_URL') || empty(TEAMS_WEBHOOK_URL)) return false;

    $emojis = [
        'Gestão & Planejamento' => '📊',
        'Videos Promo'          => '🎬',
        'Webinars'              => '🎙️',
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
                "actions" => [[
                    "type"  => "Action.OpenUrl",
                    "title" => "🔗 Ver no Sistema",
                    "url"   => $dados['link_sistema'] ?? "https://insights.gvacompany.com/brasil_dna/"
                ]]
            ]
        ]]
    ];

    return _enviarWebhook(TEAMS_WEBHOOK_URL, $payload);
}

// ============================================================
// 2. NOTIFICAÇÃO VIA CHAT PRIVADO (somente o responsável vê)
// ============================================================
function notificarTeamsChat(array $dados): bool {

    // Busca a URL do webhook pessoal do responsavel
    if (!defined('TEAMS_CHAT_WEBHOOKS')) return false;
    $mapa = TEAMS_CHAT_WEBHOOKS;
    $nomeResp = $dados['responsavel'] ?? '';

    if (empty($mapa[$nomeResp])) return false; // responsavel sem webhook configurado
    $url = $mapa[$nomeResp];

    $prioridadeEmoji = ['Alta' => '🔴', 'Media' => '🟡', 'Baixa' => '🟢'];
    $priEmoji = $prioridadeEmoji[$dados['prioridade']] ?? '⚪';
    $deadline = !empty($dados['deadline'])
                ? date('d/m/Y', strtotime($dados['deadline']))
                : 'Não definido';

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
                    ],
                    !empty($dados['observacoes']) ? [
                        "type"    => "TextBlock",
                        "text"    => "💬 " . $dados['observacoes'],
                        "wrap"    => true,
                        "spacing" => "Medium",
                        "color"   => "Warning"
                    ] : null
                ],
                "actions" => [[
                    "type"  => "Action.OpenUrl",
                    "title" => "🔗 Ver no Sistema",
                    "url"   => $dados['link_sistema'] ?? "https://insights.gvacompany.com/brasil_dna/"
                ]]
            ]
        ]]
    ];

    // Remove entradas null do body (observacoes vazia)
    $payload['attachments'][0]['content']['body'] = array_values(
        array_filter($payload['attachments'][0]['content']['body'])
    );

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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return in_array($httpCode, [200, 202]);
}
