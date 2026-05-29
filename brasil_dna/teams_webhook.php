<?php
// teams_webhook.php
// ⚠️  A URL do webhook é definida em includes/config.php (fora do git)
// Adicione esta linha no seu includes/config.php no servidor:
// define('TEAMS_WEBHOOK_URL', 'SUA_URL_AQUI');

if (!defined('TEAMS_WEBHOOK_URL')) {
    define('TEAMS_WEBHOOK_URL', '');
}

function notificarTeams(array $dados): bool {

    $url = TEAMS_WEBHOOK_URL;
    if (empty($url)) return false;

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

    $prioridadeEmoji = [
        'Alta'  => '🔴',
        'Media' => '🟡',
        'Baixa' => '🟢',
    ];

    $emoji    = $emojis[$dados['categoria']]           ?? '📋';
    $priEmoji = $prioridadeEmoji[$dados['prioridade']] ?? '⚪';
    $deadline = !empty($dados['deadline'])
                ? date('d/m/Y', strtotime($dados['deadline']))
                : 'Não definido';

    $payload = [
        "type"        => "message",
        "attachments" => [
            [
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
                    "actions" => [
                        [
                            "type"  => "Action.OpenUrl",
                            "title" => "🔗 Ver no Sistema",
                            "url"   => "https://insights.gvacompany.com/brasil_dna/"
                        ]
                    ]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return in_array($httpCode, [200, 202]);
}
