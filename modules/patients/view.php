<?php
/**
 * Consultation d'un patient
 * Affiche tous les détails d'un patient spécifique avec son historique médical
 */

define('BASE_URL', '/hopital-management/');
$pageTitle = 'Détails du patient';

require_once '../../config/database.php';
require_once '../../includes/header.php';

// Vérification que l'ID du patient est fourni en paramètre
$id_patient = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_patient === 0) {
    die("<div class='alert alert-danger'>Erreur : ID patient manquant.</div>");
}

// Récupération des informations du patient
$patient = fetchOne("
    SELECT p.*, s.nom_salle, s.type_salle 
    FROM patient p 
    LEFT JOIN salle s ON p.id_salle = s.id_salle 
    WHERE p.id_patient = :id
", [':id' => $id_patient]);

// Si le patient n'existe pas, afficher une erreur
if (!$patient) {
    die("<div class='alert alert-danger'>Patient non trouvé.</div>");
}

// Récupération des consultations de ce patient
$consultations = fetchAll("
    SELECT c.*, m.nom as medecin_nom, m.prenom as medecin_prenom, m.specialite
    FROM consultation c
    JOIN medecin m ON c.id_medecin = m.id_medecin
    WHERE c.id_patient = :id_patient
    ORDER BY c.date_consultation DESC
", [':id_patient' => $id_patient]);

// Récupération des dossiers médicaux de ce patient
$dossiers = fetchAll("
    SELECT d.*, m.nom as medecin_nom, m.prenom as medecin_prenom
    FROM dossier_medical d
    JOIN medecin m ON d.id_medecin = m.id_medecin
    WHERE d.id_patient = :id_patient
    ORDER BY d.date_creation DESC
", [':id_patient' => $id_patient]);

// Calcul de l'âge du patient
$age = date_diff(date_create($patient['date_naissance']), date_create('today'))->y;

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>
        <i class="fas fa-user-injured me-2"></i>
        <?php echo htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']); ?>
    </h1>
    <div class="btn-group">
        <a href="edit.php?id=<?php echo $id_patient; ?>" class="btn btn-warning">
            <i class="fas fa-edit me-1"></i>Modifier
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour
        </a>
    </div>
</div>

<!-- Carte d'information patient -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-id-card me-2"></i>Informations personnelles
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p>
                            <strong>Numéro de dossier :</strong><br>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($patient['numero_dossier']); ?></span>
                        </p>
                        <p>
                            <strong>Sexe :</strong><br>
                            <?php echo $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin'; ?>
                        </p>
                        <p>
                            <strong>Date de naissance :</strong><br>
                            <?php echo date('d/m/Y', strtotime($patient['date_naissance'])); ?> (<?php echo $age; ?> ans)
                        </p>
                        <p>
                            <strong>Groupe sanguin :</strong><br>
                            <?php echo !empty($patient['groupe_sanguin']) ? htmlspecialchars($patient['groupe_sanguin']) : '<em>Non renseigné</em>'; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p>
                            <strong>Téléphone :</strong><br>
                            <?php echo !empty($patient['telephone']) ? htmlspecialchars($patient['telephone']) : '<em>Non renseigné</em>'; ?>
                        </p>
                        <p>
                            <strong>Email :</strong><br>
                            <?php echo !empty($patient['email']) ? htmlspecialchars($patient['email']) : '<em>Non renseigné</em>'; ?>
                        </p>
                        <p>
                            <strong>Adresse :</strong><br>
                            <?php echo !empty($patient['adresse']) ? nl2br(htmlspecialchars($patient['adresse'])) : '<em>Non renseignée</em>'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Allergies et statut -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm bg-danger-light">
                    <div class="card-body">
                        <h6>
                            <i class="fas fa-exclamation-triangle me-2 text-warning"></i>Allergies
                        </h6>
                        <p class="mb-0">
                            <?php echo !empty($patient['allergies']) ? nl2br(htmlspecialchars($patient['allergies'])) : '<em class="text-muted">Aucune allergie déclarée</em>'; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6>
                            <i class="fas fa-hospital me-2"></i>Statut
                        </h6>
                        <p class="mb-0">
                            <?php 
                                $badgeClass = [
                                    'En attente' => 'warning',
                                    'En consultation' => 'info',
                                    'Hospitalisé' => 'primary',
                                    'Sorti' => 'success'
                                ][$patient['statut']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $badgeClass; ?> fs-6">
                                <?php echo htmlspecialchars($patient['statut']); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Colonne latérale droite -->
    <div class="col-md-4">
        <!-- Salle actuelle -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="fas fa-bed me-2"></i>Salle actuelle
                </h6>
            </div>
            <div class="card-body">
                <?php if ($patient['nom_salle']): ?>
                    <p class="mb-2">
                        <strong><?php echo htmlspecialchars($patient['nom_salle']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($patient['type_salle']); ?></small>
                    </p>
                <?php else: ?>
                    <p class="text-muted"><em>Aucune salle assignée</em></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Historique d'admission -->
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="fas fa-calendar me-2"></i>Admission
                </h6>
            </div>
            <div class="card-body small">
                <p class="mb-2">
                    <strong>Date d'admission :</strong><br>
                    <?php echo date('d/m/Y à H:i', strtotime($patient['date_admission'])); ?>
                </p>
                <?php if ($patient['date_sortie']): ?>
                    <p class="mb-0">
                        <strong>Date de sortie :</strong><br>
                        <?php echo date('d/m/Y à H:i', strtotime($patient['date_sortie'])); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Onglets pour les consultations et dossiers -->
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="consultations-tab" data-bs-toggle="tab" data-bs-target="#consultations" type="button" role="tab" aria-controls="consultations" aria-selected="true">
            <i class="fas fa-stethoscope me-1"></i>Consultations (<?php echo count($consultations); ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="dossiers-tab" data-bs-toggle="tab" data-bs-target="#dossiers" type="button" role="tab" aria-controls="dossiers" aria-selected="false">
            <i class="fas fa-file-medical me-1"></i>Dossiers médicaux (<?php echo count($dossiers); ?>)
        </button>
    </li>
</ul>

<!-- Contenu des onglets -->
<div class="tab-content">
    <!-- Onglet Consultations -->
    <div class="tab-pane fade show active" id="consultations" role="tabpanel" aria-labelledby="consultations-tab">
        <?php if (!empty($consultations)): ?>
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Médecin</th>
                                <th>Spécialité</th>
                                <th>Motif</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consultations as $consultation): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($consultation['date_consultation'])); ?></td>
                                    <td>Dr. <?php echo htmlspecialchars($consultation['medecin_nom']); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($consultation['specialite']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo substr(htmlspecialchars($consultation['motif']), 0, 40); ?>...</td>
                                    <td>
                                        <?php 
                                            $badgeClass = [
                                                'Planifiée' => 'secondary',
                                                'En cours' => 'warning',
                                                'Terminée' => 'success',
                                                'Annulée' => 'danger'
                                            ][$consultation['statut']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($consultation['statut']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="../../modules/consultations/view.php?id=<?php echo $consultation['id_consultation']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Aucune consultation enregistrée pour ce patient.
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Onglet Dossiers médicaux -->
    <div class="tab-pane fade" id="dossiers" role="tabpanel" aria-labelledby="dossiers-tab">
        <?php if (!empty($dossiers)): ?>
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Médecin</th>
                                <th>Résumé</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dossiers as $dossier): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($dossier['date_creation'])); ?></td>
                                    <td>Dr. <?php echo htmlspecialchars($dossier['medecin_nom'] . ' ' . $dossier['medecin_prenom']); ?></td>
                                    <td><?php echo substr(htmlspecialchars($dossier['resume_consultation']), 0, 50); ?>...</td>
                                    <td>
                                        <a href="../../modules/dossiers/view.php?id=<?php echo $dossier['id_dossier']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-file"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Aucun dossier médical pour ce patient.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
?>
