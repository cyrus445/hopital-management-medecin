<?php
/**
 * Liste des patients
 * Affiche tous les patients avec possibilité de filtrage et de tri
 */

// Configuration
define('BASE_URL', '/hopital-management/');
$pageTitle = 'Gestion des Patients';

// Inclusion des fichiers requis
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Traitement des filtres
$filters = [];
$whereClause = "WHERE 1=1";
$params = [];

// Filtre par statut
if (isset($_GET['statut']) && !empty($_GET['statut'])) {
    $whereClause .= " AND p.statut = :statut";
    $params[':statut'] = $_GET['statut'];
    $filters['statut'] = $_GET['statut'];
}

// Filtre par nom
if (isset($_GET['nom']) && !empty($_GET['nom'])) {
    $whereClause .= " AND (p.nom LIKE :nom OR p.prenom LIKE :nom)";
    $params[':nom'] = '%' . $_GET['nom'] . '%';
    $filters['nom'] = $_GET['nom'];
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Récupération du nombre total de patients
$countQuery = "SELECT COUNT(*) as total FROM patient p $whereClause";
$totalResult = fetchOne($countQuery, $params);
$totalPatients = $totalResult['total'];
$totalPages = ceil($totalPatients / $limit);

// Récupération des patients avec leurs salles
$query = "
    SELECT p.*, s.nom_salle, s.type_salle 
    FROM patient p 
    LEFT JOIN salle s ON p.id_salle = s.id_salle 
    $whereClause 
    ORDER BY p.date_admission DESC 
    LIMIT :limit OFFSET :offset
";

$params[':limit'] = $limit;
$params[':offset'] = $offset;

$patients = fetchAll($query, $params);

// Récupération des statistiques par statut
$statsQuery = "
    SELECT statut, COUNT(*) as count 
    FROM patient 
    GROUP BY statut
";
$statistiques = fetchAll($statsQuery);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>
        <i class="fas fa-user-injured me-2"></i>Gestion des Patients
    </h1>
    <a href="add.php" class="btn btn-hospital">
        <i class="fas fa-plus me-1"></i>Nouveau Patient
    </a>
</div>

<!-- Statistiques rapides -->
<div class="row mb-4">
    <?php foreach ($statistiques as $stat): ?>
        <div class="col-md-2 col-6">
            <div class="card text-center">
                <div class="card-body py-2">
                    <h6 class="mb-1"><?php echo $stat['statut']; ?></h6>
                    <h4 class="mb-0"><?php echo $stat['count']; ?></h4>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Filtres -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="statut" class="form-label">Statut</label>
                <select name="statut" id="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="En attente" <?php echo (isset($filters['statut']) && $filters['statut'] == 'En attente') ? 'selected' : ''; ?>>En attente</option>
                    <option value="En consultation" <?php echo (isset($filters['statut']) && $filters['statut'] == 'En consultation') ? 'selected' : ''; ?>>En consultation</option>
                    <option value="Hospitalisé" <?php echo (isset($filters['statut']) && $filters['statut'] == 'Hospitalisé') ? 'selected' : ''; ?>>Hospitalisé</option>
                    <option value="Sorti" <?php echo (isset($filters['statut']) && $filters['statut'] == 'Sorti') ? 'selected' : ''; ?>>Sorti</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="nom" class="form-label">Nom ou prénom</label>
                <input type="text" 
                       name="nom" 
                       id="nom" 
                       class="form-control" 
                       placeholder="Rechercher..."
                       value="<?php echo $filters['nom'] ?? ''; ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-hospital">
                        <i class="fas fa-filter me-1"></i>Filtrer
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Réinitialiser
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tableau des patients -->
<div class="card shadow-sm">
    <div class="card-body">
        <?php if (!empty($patients)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nom & Prénom</th>
                            <th>Date Naissance</th>
                            <th>Téléphone</th>
                            <th>Salle</th>
                            <th>Statut</th>
                            <th>Date Admission</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary">#<?php echo str_pad($patient['id_patient'], 6, '0', STR_PAD_LEFT); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($patient['numero_dossier']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $age = date_diff(date_create($patient['date_naissance']), date_create('today'))->y;
                                        echo date('d/m/Y', strtotime($patient['date_naissance'])) . " ($age ans)";
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($patient['telephone']); ?></td>
                                <td>
                                    <?php if ($patient['nom_salle']): ?>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($patient['nom_salle']); ?>
                                        </span>
                                        <br>
                                        <small class="text-muted"><?php echo $patient['type_salle']; ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Non attribuée</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $badgeClass = [
                                            'En attente' => 'warning',
                                            'En consultation' => 'info',
                                            'Hospitalisé' => 'primary',
                                            'Sorti' => 'success'
                                        ][$patient['statut']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badgeClass; ?>">
                                        <?php echo $patient['statut']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y H:i', strtotime($patient['date_admission'])); ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo $patient['id_patient']; ?>" 
                                           class="btn btn-outline-primary" 
                                           title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $patient['id_patient']; ?>" 
                                           class="btn btn-outline-warning" 
                                           title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $patient['id_patient']; ?>" 
                                           class="btn btn-outline-danger" 
                                           title="Supprimer"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce patient ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-user-slash fa-4x text-muted mb-3"></i>
                <h4>Aucun patient trouvé</h4>
                <p class="text-muted">
                    <?php echo empty($filters) ? 'Commencez par ajouter un patient.' : 'Aucun patient ne correspond aux critères de recherche.'; ?>
                </p>
                <?php if (!empty($filters)): ?>
                    <a href="index.php" class="btn btn-hospital mt-2">
                        <i class="fas fa-times me-1"></i>Réinitialiser les filtres
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Export des données -->
<div class="card shadow-sm mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0">
                    <i class="fas fa-download me-2"></i>Export des données
                </h6>
                <small class="text-muted">Exportez la liste des patients dans différents formats</small>
            </div>
            <div class="btn-group">
                <a href="export.php?format=csv&<?php echo http_build_query($filters); ?>" 
                   class="btn btn-outline-success">
                    <i class="fas fa-file-csv me-1"></i>CSV
                </a>
                <a href="export.php?format=pdf&<?php echo http_build_query($filters); ?>" 
                   class="btn btn-outline-danger">
                    <i class="fas fa-file-pdf me-1"></i>PDF
                </a>
                <a href="export.php?format=excel&<?php echo http_build_query($filters); ?>" 
                   class="btn btn-outline-primary">
                    <i class="fas fa-file-excel me-1"></i>Excel
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Inclusion du pied de page
require_once '../../includes/footer.php';
?>