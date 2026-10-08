<?php
/**
 * Module Caisse - Affichage des prescriptions et examens pour facturation
 */

define('BASE_URL', '/hopital-management/');
$pageTitle = 'Caisse - Facturation';

require_once '../../config/database.php';
require_once '../../includes/header.php';

// Récupère les dossiers avec prescriptions/examens
$dossiers = fetchAll("
    SELECT 
        d.id_dossier,
        d.id_patient,
        d.id_medecin,
        d.prescription,
        d.examens_demandes,
        d.date_creation,
        p.nom as patient_nom,
        p.prenom as patient_prenom,
        p.numero_dossier,
        m.nom as medecin_nom,
        m.specialite
    FROM dossier_medical d
    JOIN patient p ON d.id_patient = p.id_patient
    JOIN medecin m ON d.id_medecin = m.id_medecin
    WHERE d.prescription IS NOT NULL OR d.examens_demandes IS NOT NULL
    ORDER BY d.date_creation DESC
    LIMIT 100
");

// Calcule les statistiques
$stats = fetchOne("
    SELECT 
        COUNT(DISTINCT d.id_dossier) as total_dossiers,
        COUNT(CASE WHEN d.prescription IS NOT NULL THEN 1 END) as avec_prescription,
        COUNT(CASE WHEN d.examens_demandes IS NOT NULL THEN 1 END) as avec_examens
    FROM dossier_medical d
");

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-cash-register me-2"></i>Module Caisse - Facturation</h1>
    <button class="btn btn-success" onclick="window.print()"><i class="fas fa-print me-1"></i>Imprimer</button>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center bg-primary text-white">
            <div class="card-body">
                <h6>À facturer</h6>
                <h4><?php echo $stats['total_dossiers']; ?></h4>
                <small>dossiers</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center bg-warning text-white">
            <div class="card-body">
                <h6>Avec examens</h6>
                <h4><?php echo $stats['avec_examens']; ?></h4>
                <small>examens à facturer</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center bg-info text-white">
            <div class="card-body">
                <h6>Avec prescription</h6>
                <h4><?php echo $stats['avec_prescription']; ?></h4>
                <small>prescriptions</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center bg-success text-white">
            <div class="card-body">
                <h6>Total dossiers</h6>
                <h4><?php echo count($dossiers); ?></h4>
                <small>en attente</small>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Détail des facturations</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($dossiers)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Patient</th>
                            <th>Dossier #</th>
                            <th>Médecin</th>
                            <th>Examens</th>
                            <th>Prescription</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dossiers as $d): 
                            $nb_examens = !empty($d['examens_demandes']) ? count(explode("\n", trim($d['examens_demandes']))) : 0;
                            $nb_medocs = !empty($d['prescription']) ? count(explode("\n", trim($d['prescription']))) : 0;
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($d['patient_nom'] . ' ' . $d['patient_prenom']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($d['numero_dossier']); ?></small>
                                </td>
                                <td><?php echo $d['id_dossier']; ?></td>
                                <td>
                                    Dr. <?php echo htmlspecialchars($d['medecin_nom']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($d['specialite']); ?></small>
                                </td>
                                <td>
                                    <?php if ($nb_examens > 0): ?>
                                        <span class="badge bg-warning"><?php echo $nb_examens; ?> examen(s)</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($nb_medocs > 0): ?>
                                        <span class="badge bg-info"><?php echo $nb_medocs; ?> médicament(s)</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($d['date_creation'], true); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#detailModal<?php echo $d['id_dossier']; ?>">
                                        <i class="fas fa-eye"></i> Voir
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Modal Détail -->
                            <div class="modal fade" id="detailModal<?php echo $d['id_dossier']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                Facturation - <?php echo htmlspecialchars($d['patient_nom'] . ' ' . $d['patient_prenom']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-4">
                                                <div class="col-md-6">
                                                    <h6>Patient</h6>
                                                    <p><strong><?php echo htmlspecialchars($d['patient_nom'] . ' ' . $d['patient_prenom']); ?></strong></p>
                                                    <p>Dossier: <?php echo htmlspecialchars($d['numero_dossier']); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Médecin</h6>
                                                    <p><strong>Dr. <?php echo htmlspecialchars($d['medecin_nom']); ?></strong></p>
                                                    <p><?php echo htmlspecialchars($d['specialite']); ?></p>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($d['examens_demandes'])): ?>
                                                <div class="alert alert-warning">
                                                    <h6><i class="fas fa-microscope me-2"></i>Examens demandés (<?php echo $nb_examens; ?>)</h6>
                                                    <pre><?php echo htmlspecialchars($d['examens_demandes']); ?></pre>
                                                    <p class="mb-0">
                                                        <strong>Estimé:</strong> <?php echo ($nb_examens * 50000); ?> FCFA
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($d['prescription'])): ?>
                                                <div class="alert alert-info">
                                                    <h6><i class="fas fa-pills me-2"></i>Prescription médicale (<?php echo $nb_medocs; ?>)</h6>
                                                    <pre><?php echo htmlspecialchars($d['prescription']); ?></pre>
                                                    <p class="mb-0">
                                                        <strong>Estimé:</strong> <?php echo ($nb_medocs * 10000); ?> FCFA
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="alert alert-success">
                                                <h6>Total estimé</h6>
                                                <h4><?php echo (($nb_examens * 50000) + ($nb_medocs * 10000)); ?> FCFA</h4>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                            <button type="button" class="btn btn-success">
                                                <i class="fas fa-check-circle me-1"></i>Facturer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Aucune facturation en attente.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
