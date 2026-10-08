<?php
/**
 * Barre de navigation principale
 * Affiche les liens vers les différents modules du système
 */

// Définition des modules disponibles
$modules = [
    'dashboard' => [
        'icon' => 'fas fa-home',
        'title' => 'Tableau de bord',
        'url' => BASE_URL . 'index.php'
    ],
    'patients' => [
        'icon' => 'fas fa-user-injured',
        'title' => 'Patients',
        'url' => BASE_URL . 'modules/patients/',
        'submenu' => [
            'liste' => ['icon' => 'fas fa-list', 'title' => 'Liste', 'url' => BASE_URL . 'modules/patients/'],
            'ajouter' => ['icon' => 'fas fa-plus', 'title' => 'Nouveau patient', 'url' => BASE_URL . 'modules/patients/add.php']
        ]
    ],
    'medecins' => [
        'icon' => 'fas fa-user-md',
        'title' => 'Médecins',
        'url' => BASE_URL . 'modules/medecins/',
        'submenu' => [
            'liste' => ['icon' => 'fas fa-list', 'title' => 'Liste', 'url' => BASE_URL . 'modules/medecins/'],
            'ajouter' => ['icon' => 'fas fa-plus', 'title' => 'Nouveau médecin', 'url' => BASE_URL . 'modules/medecins/add.php']
        ]
    ],
    'salles' => [
        'icon' => 'fas fa-bed',
        'title' => 'Salles',
        'url' => BASE_URL . 'modules/salles/',
        'submenu' => [
            'liste' => ['icon' => 'fas fa-list', 'title' => 'Liste', 'url' => BASE_URL . 'modules/salles/'],
            'ajouter' => ['icon' => 'fas fa-plus', 'title' => 'Nouvelle salle', 'url' => BASE_URL . 'modules/salles/add.php']
        ]
    ],
    'consultations' => [
        'icon' => 'fas fa-stethoscope',
        'title' => 'Consultations',
        'url' => BASE_URL . 'modules/consultations/',
        'submenu' => [
            'liste' => ['icon' => 'fas fa-list', 'title' => 'Liste', 'url' => BASE_URL . 'modules/consultations/'],
            'nouvelle' => ['icon' => 'fas fa-plus', 'title' => 'Nouvelle consultation', 'url' => BASE_URL . 'modules/consultations/create.php']
        ]
    ],
    'dossiers' => [
        'icon' => 'fas fa-folder',
        'title' => 'Dossiers Médicaux',
        'url' => BASE_URL . 'modules/dossiers/'
    ],
    'pharmacie' => [
        'icon' => 'fas fa-pills',
        'title' => 'Pharmacie',
        'url' => BASE_URL . 'modules/pharmacies/'
    ],
    'caisse' => [
        'icon' => 'fas fa-calculator',
        'title' => 'Caisse',
        'url' => BASE_URL . 'modules/caisse/'
    ]
];

// Déterminer le module actif
$currentModule = basename(dirname($_SERVER['PHP_SELF']));
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 shadow-sm">
    <div class="container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php foreach ($modules as $key => $module): ?>
                    <?php if (isset($module['submenu'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo ($currentModule == $key) ? 'active' : ''; ?>" 
                               href="#" 
                               id="dropdown-<?php echo $key; ?>" 
                               role="button" 
                               data-bs-toggle="dropdown">
                                <i class="<?php echo $module['icon']; ?> me-1"></i>
                                <?php echo $module['title']; ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="dropdown-<?php echo $key; ?>">
                                <?php foreach ($module['submenu'] as $subkey => $submenu): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo $submenu['url']; ?>">
                                            <i class="<?php echo $submenu['icon']; ?> me-2"></i>
                                            <?php echo $submenu['title']; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentModule == $key) ? 'active' : ''; ?>" 
                               href="<?php echo $module['url']; ?>">
                                <i class="<?php echo $module['icon']; ?> me-1"></i>
                                <?php echo $module['title']; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            
            <!-- Recherche rapide -->
            <form class="d-flex" action="<?php echo BASE_URL; ?>search.php" method="GET">
                <div class="input-group">
                    <input type="text" 
                           class="form-control" 
                           placeholder="Rechercher un patient..." 
                           name="q"
                           aria-label="Recherche">
                    <button class="btn btn-hospital" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</nav>