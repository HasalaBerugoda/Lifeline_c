<?php require_once __DIR__ . '/env.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . " | LifeLine Blood Bank" : "LifeLine Blood Bank"; ?></title>
    
    <script>
        // Immediately set theme from localStorage to avoid flashing
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
    </script>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo APP_URL; ?>/images/favicon.ico?v=2.0" sizes="any">
    <link rel="icon" href="<?php echo APP_URL; ?>/images/favicon.png?v=2.0" type="image/png" sizes="32x32">
    <link rel="icon" href="<?php echo APP_URL; ?>/images/favicon-192.png?v=2.0" type="image/png" sizes="192x192">
    <link rel="apple-touch-icon" href="<?php echo APP_URL; ?>/images/apple-touch-icon.png?v=2.0">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Chart.js 4 (via CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <!-- Bootstrap Icons (Optional but great for visual design) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom Style CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/style.css?v=1.5">
    
    <!-- Session Script (must be loaded early) -->
    <script>const APP_URL = "<?php echo APP_URL; ?>";</script>
    <script src="<?php echo APP_URL; ?>/js/auth.js"></script>
</head>
<body>
