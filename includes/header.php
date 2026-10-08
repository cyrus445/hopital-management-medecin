<?php
/**
 * En-tête commune pour toutes les pages
 * Contient la déclaration HTML, les métadonnées et les liens CSS
 */

// Démarrage de la session si non déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification de l'authentification (exemple basique)
$isAuthenticated = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Système de Gestion Hospitalière - <?php echo $pageTitle ?? 'Accueil'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>assets/img/favicon.ico">
    
    <!-- Métadonnées SEO -->
    <meta name="description" content="Système de gestion hospitalière complet pour la gestion des patients, médecins, consultations et dossiers médicaux">
    <meta name="keywords" content="hôpital, gestion, patients, médecins, consultations, dossiers médicaux">
    <meta name="author" content="Équipe de développement hospitalier">
    
    <style>
        /* Styles complémentaires */
        .hospital-header {
            background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .hospital-logo {
            font-size: 1.8rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .hospital-logo i {
            font-size: 2rem;
        }
        
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(26, 115, 232, 0.05);
        }
        
        .btn-hospital {
            background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-hospital:hover {
            background: linear-gradient(135deg, #0d47a1 0%, #1a73e8 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .alert-hospital {
            border-left: 4px solid #1a73e8;
            background-color: #f8f9fa;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .hospital-logo {
                font-size: 1.4rem;
            }
            
            .stat-card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- En-tête principal -->
    <header class="hospital-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="hospital-logo">
                        <i class="fas fa-hospital"></i>
                        <span>Hôpital Général - Système de Gestion</span>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <?php if ($isAuthenticated): ?>
                        <span class="me-3">
                            <i class="fas fa-user-md"></i>
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur'); ?>
                        </span>
                        <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-light btn-sm">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-light btn-sm">
                            <i class="fas fa-sign-in-alt"></i> Connexion
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Inclusion de la barre de navigation -->
    <?php include_once __DIR__ . '/navbar.php'; ?>
    
    <!-- Contenu principal -->
    <main class="container my-4">
        <!-- Messages flash -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show alert-hospital" role="alert">
                <?php echo $_SESSION['flash_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>