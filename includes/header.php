<?php
/**
 * Shared HTML Head & Asset Inclusions
 * Hotel Event Management System
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_FULL_NAME;
$settings = get_system_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Google Fonts: Playfair Display & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,400&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- FullCalendar CSS (for availability views) -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    
    <!-- Custom Hotel Luxury Theme with Real-Time Cache-Busting -->
    <link href="<?= BASE_URL ?>assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: '3.2.0' ?>" rel="stylesheet">
    
    <script>
        window.BASE_URL = <?= json_encode(BASE_URL) ?>;
    </script>
</head>
<body>
