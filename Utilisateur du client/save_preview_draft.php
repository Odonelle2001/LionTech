<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['builder_preview'] = [
        'name' => $_POST['business_name'] ?? 'Mon Business',
        'slug' => $_POST['subdomain'] ?? 'preview',
        'description' => $_POST['description'] ?? '',
        'city' => $_POST['city'] ?? '',
        'neighborhood' => $_POST['quarter'] ?? '',
        'whatsapp' => $_POST['whatsapp'] ?? '',
        'primary_color' => $_POST['primary_color'] ?? '#C9A84C',
        'secondary_color' => $_POST['secondary_color'] ?? '#0A0A0A',
        'button_color' => $_POST['button_color'] ?? '#C9A84C',
        'text_color' => $_POST['text_color'] ?? '#222222',
        'background_color' => $_POST['background_color'] ?? '#ffffff',
        'navbar_style' => $_POST['navbar_style'] ?? 'light',
        'footer_style' => $_POST['footer_style'] ?? 'minimal',
        'language' => $_POST['site_language'] ?? 'fr',
    ];

    echo 'OK';
}