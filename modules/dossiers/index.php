<?php
/**
 * Liste des dossiers médicaux
 * Affiche tous les dossiers avec historique par patient
 */

define('BASE_URL', '/hopital-management/');
$pageTitle = 'Dossiers Médicaux';

require_once '../../config/database.php';
require_once '../../includes/header.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$total = fetchOne("SELECT COUNT(*) as total FROM dossier_medical")['total'];
$totalPages = ceil($total / $limit);

$dossiers = fetchAll("
    SELECT d.*, 
           p.nom as patient_nom, p.prenom as patient_prenom,
           m.nom as medecin_nom, m.prenom as medecin_prenom
    FROM dossier_medical d
    JOIN patient p ON d.id_patient = p.id_patient
    JOIN medecin m ON d.id_medecin = m.id_medecin
    ORDER BY d.date_creation DESC
    LIMIT $limit OFFSET $offset
");

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-file-medical me-2"></i>Dossiers Médicaux</h1>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (!empty($dossiers)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Médecin</th>
                            <th>Résumé</th>
                            <th>Prescription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dossiers as $d): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($d['date_creation'])); ?></td>
                                <td><?php echo htmlspecialchars($d['patient_prenom'] . ' ' . $d['patient_nom']); ?></td>
                                <td>Dr. <?php echo htmlspecialchars($d['medecin_nom']); ?></td>
                                <td><?php echo substr(htmlspecialchars($d['resume_consultation']), 0, 40); ?>...</td>
                                <td><?php echo !empty($d['prescription']) ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'; ?></td>
                                <td>
                                    <a href="view.php?id=<?php echo $d['id_dossier']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-file-medical fa-4x text-muted mb-3"></i>
                <h4>Aucun dossier médical</h4>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
