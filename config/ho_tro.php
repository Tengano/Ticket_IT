<?php

return [
    'tep_dinh_kem' => [
        'kich_thuoc_toi_da_kb' => (int) env('HOTRO_TEP_DINH_KEM_KB', 5120),
        'dinh_dang' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar',
        ],
    ],

    'phieu' => [
        'so_ban_ghi_moi_trang' => 15,
    ],
];
