<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . " | LifeLine Blood Bank" : "LifeLine Blood Bank"; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><path fill='%23e63946' d='M50,5 C50,5 90,45 90,65 C90,85 70,95 50,95 C30,95 10,85 10,65 C10,45 50,5 50,5 Z'/></svg>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Chart.js 4 (via CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <!-- Bootstrap Icons (Optional but great for visual design) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom Style CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Session Script (must be loaded early) -->
    <script src="js/auth.js"></script>
</head>
<body>
