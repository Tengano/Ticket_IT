<?php

return [
    'attachments' => [
        'max_kb' => (int) env('HELPDESK_ATTACHMENT_MAX_KB', 5120),
        'mimes' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar',
        ],
    ],

    'tickets' => [
        'per_page' => 15,
    ],
];
