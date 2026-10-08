<?php
/**
 * Liste des médecins
 * Gestion complète des médecins (création, modification, suppression)
 */

define('BASE_URL', '/hopital-management/');
$pageTitle = 'Gestion des Médecins';

require_once '../../config/database.php';
require_once '../../includes/header.php';

// Filtres et pagination
$filters = [];
$whereClause = "WHERE 1=1";
$params = [];

// Filtre par spécialité
if (isset($_GET['specialite']) && !empty($_GET['specialite'])) {
    $whereClause .= " AND specialite = :specialite";
    $params[':specialite'] = $_GET['specialite'];
    $filters['specialite'] = $_GET['specialite'];
}

// Filtre par statut
if (isset($_GET['statut']) && !empty($_GET['statut'])) {
    $whereClause .= " AND statut = :statut";
    $params[':statut'] = $_GET['statut'];
    $filters['statut'] = $_GET['statut'];
}

// Filtre par nom/prénom
if (isset($_GET['nom']) && !empty($_GET['nom'])) {
    $whereClause .= " AND (nom LIKE :nom OR prenom LIKE :nom)";
    $params[':nom'] = '%' . $_GET['nom'] . '%';
    $filters['nom'] = $_GET['nom'];
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Récupération du nombre total
$totalResult = fetchOne("SELECT COUNT(*) as total FROM medecin m $whereClause", $params);
$totalMedecins = $totalResult['total'];
$totalPages = ceil($totalMedecins / $limit);

// Récupération des médecins
$query = "
    SELECT * FROM medecin $whereClause
    ORDER BY nom ASC, prenom ASC
    LIMIT :limit OFFSET :offset
";
$params[':limit'] = $limit;
$params[':offset'] = $offset;
$medecins = fetchAll($query, $params);

// Récupération des spécialités uniques pour le filtre
$specialites = fetchAll("SELECT DISTINCT specialite FROM medecin ORDER BY specialite ASC");

// Récupération des statistiques
$stats = [
    'total' => fetchOne("SELECT COUNT(*) as total FROM medecin")['total'],
    'actifs' => fetchOne("SELECT COUNT(*) as total FROM medecin WHERE statut = 'Actif'")['total'],
    'inactifs' => fetchOne("SELECT COUNT(*) as total FROM medecin WHERE statut = 'Inactif'")['total'],
    'conges' => fetchOne("SELECT COUNT(*) as total FROM medecin WHERE statut = 'Congé'")['total']
];

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>
        <i class="fas fa-user-md me-2"></i>Gestion des Médecins
    </h1>
    <a href="add.php" class="btn btn-hospital">
        <i class="fas fa-plus me-1"></i>Ajouter un médecin
    </a>
</div>

<!-- Statistiques rapides -->
<div class="row mb-4">
    <div class="col-md-3 col-6">
        <div class="card text-center">
            <div class="card-body py-2">
                <h6 class="mb-1">Total</h6>
                <h4 class="mb-0"><?php echo $stats['total']; ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card text-center bg-success-light">
            <div class="card-body py-2">
                <h6 class="mb-1">Actifs</h6>
                <h4 class="mb-0"><?php echo $stats['actifs']; ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card text-center bg-warning-light">
            <div class="card-body py-2">
                <h6 class="mb-1">En congé</h6>
                <h4 class="mb-0"><?php echo $stats['conges']; ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card text-center bg-danger-light">
            <div class="card-body py-2">
                <h6 class="mb-1">Inactifs</h6>
                <h4 class="mb-0"><?php echo $stats['inactifs']; ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="nom" class="form-label">Nom ou Prénom</label>
                <input type="text" class="form-control" id="nom" name="nom" 
                       placeholder="Rechercher..." value="<?php echo $filters['nom'] ?? ''; ?>">
            </div>
            <div class="col-md-4">
                <label for="specialite" class="form-label">Spécialité</label>
                <select class="form-select" id="specialite" name="specialite">
                    <option value="">-- Toutes les spécialités --</option>
                    <?php foreach ($specialites as $spec): ?>
                        <option value="<?php echo htmlspecialchars($spec['specialite']); ?>"
                                <?php echo (isset($filters['specialite']) && $filters['specialite'] === $spec['specialite']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($spec['specialite']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-hospital flex-grow-1">
                    <i class="fas fa-filter me-1"></i>Filtrer
                </button>
                <a href="index.php" class="btn btn-outline-secondary flex-grow-1">
                    <i class="fas fa-times me-1"></i>Réinitialiser
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tableau des médecins -->
<div class="card shadow-sm">
    <div class="card-body">
        <?php if (!empty($medecins)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Matricule</th>
                            <th>Nom & Prénom</th>
                            <th>Spécialité</th>
                            <th>Téléphone</th>
                            <th>Email</th>
                            <th>Statut</th>
                            <th>Date embauche</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medecins as $medecin): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($medecin['matricule']); ?></span>
                                </td>
                                <td>
                                    <strong>Dr. <?php echo htmlspecialchars($medecin['nom'] . ' ' . $medecin['prenom']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($medecin['specialite']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($medecin['telephone']); ?></td>
                                <td>
                                    <small><?php echo htmlspecialchars($medecin['email']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $badgeClass = [
                                            'Actif' => 'success',
                                            'Inactif' => 'danger',
                                            'Congé' => 'warning'
                                        ][$medecin['statut']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badgeClass; ?>">
                                        <?php echo $medecin['statut']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($medecin['date_embauche'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $medecin['id_medecin']; ?>" 
                                           class="btn btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $medecin['id_medecin']; ?>" 
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Êtes-vous sûr ?')">
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
                <i class="fas fa-user-md fa-4x text-muted mb-3"></i>
                <h4>Aucun médecin trouvé</h4>
                <p class="text-muted">Ajoutez le premier médecin de l'équipe.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
