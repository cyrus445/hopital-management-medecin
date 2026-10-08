<?php
/**
 * Consultation d'un dossier médical
 */

define('BASE_URL', '/hopital-management/');
$pageTitle = 'Dossier Médical';

require_once '../../config/database.php';
require_once '../../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) die("<div class='alert alert-danger'>ID manquant.</div>");

$dossier = fetchOne("
    SELECT d.*, 
           p.nom as patient_nom, p.prenom as patient_prenom, p.numero_dossier,
           m.nom as medecin_nom, m.prenom as medecin_prenom, m.specialite,
           c.date_consultation
    FROM dossier_medical d
    JOIN patient p ON d.id_patient = p.id_patient
    JOIN medecin m ON d.id_medecin = m.id_medecin
    LEFT JOIN consultation c ON d.id_consultation = c.id_consultation
    WHERE d.id_dossier = :id
", [':id' => $id]);

if (!$dossier) die("<div class='alert alert-danger'>Dossier non trouvé.</div>");

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-file-medical me-2"></i>Dossier Médical</h1>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Retour
    </a>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-user-injured me-2"></i>Patient</h6>
            </div>
            <div class="card-body">
                <p><strong><?php echo htmlspecialchars($dossier['patient_prenom'] . ' ' . $dossier['patient_nom']); ?></strong></p>
                <p class="text-muted"><?php echo htmlspecialchars($dossier['numero_dossier']); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-user-md me-2"></i>Médecin</h6>
            </div>
            <div class="card-body">
                <p><strong>Dr. <?php echo htmlspecialchars($dossier['medecin_nom'] . ' ' . $dossier['medecin_prenom']); ?></strong></p>
                <p class="text-muted"><?php echo htmlspecialchars($dossier['specialite']); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <p class="text-muted"><i class="fas fa-calendar me-1"></i>Date: <?php echo date('d/m/Y à H:i', strtotime($dossier['date_creation'])); ?></p>
        <?php if ($dossier['date_consultation']): ?>
            <p class="text-muted"><i class="fas fa-clock me-1"></i>Consultation: <?php echo date('d/m/Y à H:i', strtotime($dossier['date_consultation'])); ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Contenu du dossier -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h6 class="mb-0"><i class="fas fa-align-left me-2"></i>Résumé de la consultation</h6>
    </div>
    <div class="card-body">
        <div class="bg-light p-3 rounded">
            <?php echo nl2br(htmlspecialchars($dossier['resume_consultation'])); ?>
        </div>
    </div>
</div>

<?php if (!empty($dossier['prescription'])): ?>
    <div class="card shadow-sm mb-4 border-info">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0"><i class="fas fa-pills me-2"></i>Prescription (Pour la Pharmacie)</h6>
        </div>
        <div class="card-body">
            <div class="bg-light p-3 rounded">
                <?php echo nl2br(htmlspecialchars($dossier['prescription'])); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($dossier['examens_demandes'])): ?>
    <div class="card shadow-sm mb-4 border-warning">
        <div class="card-header bg-warning">
            <h6 class="mb-0"><i class="fas fa-microscope me-2"></i>Examens demandés (Pour la Caisse)</h6>
        </div>
        <div class="card-body">
            <div class="bg-light p-3 rounded">
                <?php echo nl2br(htmlspecialchars($dossier['examens_demandes'])); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($dossier['notes_medecin'])): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Notes du médecin</h6>
        </div>
        <div class="card-body">
            <div class="bg-light p-3 rounded">
                <?php echo nl2br(htmlspecialchars($dossier['notes_medecin'])); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
