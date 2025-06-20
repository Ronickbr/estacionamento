<?php
// Header component with common styles
// Requires: $page_title variable for the page title

if (!isset($page_title)) {
    $page_title = 'ParkManager';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - RNB ParkManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            border-radius: 10px;
            margin: 2px 0;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
            transform: translateX(5px);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        /* Submenu styles */
        .nav-link[data-bs-toggle="collapse"] {
            position: relative;
        }
        
        .nav-link[data-bs-toggle="collapse"] .bi-chevron-down {
            transition: transform 0.3s ease;
        }
        
        .nav-link[data-bs-toggle="collapse"][aria-expanded="true"] .bi-chevron-down {
            transform: rotate(180deg);
        }
        
        .collapse .nav-link {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            margin-left: 0.5rem;
            border-left: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .collapse .nav-link:hover,
        .collapse .nav-link.active {
            border-left-color: white;
            background: rgba(255, 255, 255, 0.15);
        }
        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
        }
        .badge {
            font-size: 0.75rem;
        }
        .main-content {
            min-height: calc(100vh - 120px);
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .sidebar {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body class="bg-light">