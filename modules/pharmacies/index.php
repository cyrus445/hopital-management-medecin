<?php
/**
 * Module Pharmacie - Affichage des prescriptions pour la pharmacie
 */

define('BASE_URL', '/hopital-management/');
$pageTitle = 'Pharmacie - Prescriptions';

require_once '../../config/database.php';
require_once '../../includes/header.php';

// Détection des colonnes optionnelles
$hasStatutDelivrance = function_exists('dbColumnExists') ? dbColumnExists('dossier_medical', 'statut_delivrance') : false;

// Filtres et recherche
$filters = [];
$whereClause = "WHERE d.prescription IS NOT NULL AND d.prescription != ''";
$params = [];

// Filtre par date
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $whereClause .= " AND DATE(c.date_consultation) = :date";
    $params[':date'] = $_GET['date'];
    $filters['date'] = $_GET['date'];
}

// Filtre par statut de délivrance (si la colonne existe)
if ($hasStatutDelivrance) {
    if (isset($_GET['statut']) && !empty($_GET['statut'])) {
        $whereClause .= " AND d.statut_delivrance = :statut";
        $params[':statut'] = $_GET['statut'];
        $filters['statut'] = $_GET['statut'];
    }
} else {
    // si la colonne n'existe pas, on ignore le filtre et on fournira un placeholder plus bas
}

// Recherche par patient
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $whereClause .= " AND (p.nom LIKE :search OR p.prenom LIKE :search OR p.numero_dossier LIKE :search)";
    $params[':search'] = '%' . $_GET['search'] . '%';
    $filters['search'] = $_GET['search'];
}

// Récupération des prescriptions
$statutSelect = $hasStatutDelivrance ? 'd.statut_delivrance,' : "NULL as statut_delivrance,";

$query = "
    SELECT 
        d.id_dossier,
        d.prescription,
        d.date_creation as date_prescription,
        " . $statutSelect . "
        p.id_patient,
        p.nom as patient_nom,
        p.prenom as patient_prenom,
        p.numero_dossier,
        p.date_naissance,
        m.nom as medecin_nom,
        m.prenom as medecin_prenom,
        m.specialite,
        c.date_consultation
    FROM dossier_medical d
    JOIN patient p ON d.id_patient = p.id_patient
    JOIN medecin m ON d.id_medecin = m.id_medecin
    JOIN consultation c ON d.id_consultation = c.id_consultation
    $whereClause
    ORDER BY c.date_consultation DESC
    LIMIT 50
";

$prescriptions = fetchAll($query, $params);

// Statistiques
$statsQuery = null;
if ($hasStatutDelivrance) {
    $statsQuery = "
        SELECT 
            COUNT(*) as total_prescriptions,
            SUM(CASE WHEN d.statut_delivrance = 'Délivrée' THEN 1 ELSE 0 END) as delivrees,
            SUM(CASE WHEN d.statut_delivrance = 'En attente' THEN 1 ELSE 0 END) as en_attente,
            SUM(CASE WHEN d.statut_delivrance IS NULL THEN 1 ELSE 0 END) as non_traitees
        FROM dossier_medical d
        WHERE d.prescription IS NOT NULL AND d.prescription != ''
    ";
} else {
    // Colonne absente : fournir des statistiques basiques
    $statsQuery = "
        SELECT 
            COUNT(*) as total_prescriptions,
            0 as delivrees,
            0 as en_attente,
            COUNT(*) as non_traitees
        FROM dossier_medical d
        WHERE d.prescription IS NOT NULL AND d.prescription != ''
    ";
}

$statistiques = fetchOne($statsQuery);
?>

<div class="container-fluid">
    <!-- En-tête avec titre et statistiques -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="display-6">
                <i class="fas fa-pills text-primary me-2"></i>Service Pharmacie
            </h1>
            <p class="lead text-muted">
                Gestion et suivi des prescriptions médicales
            </p>
        </div>
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body py-3">
                    <div class="row text-center">
                        <div class="col-4">
                            <h5 class="mb-1"><?php echo $statistiques['total_prescriptions']; ?></h5>
                            <small class="text-muted">Total</small>
                        </div>
                        <div class="col-4">
                            <h5 class="mb-1 text-success"><?php echo $statistiques['delivrees']; ?></h5>
                            <small class="text-muted">Délivrées</small>
                        </div>
                        <div class="col-4">
                            <h5 class="mb-1 text-warning"><?php echo $statistiques['en_attente']; ?></h5>
                            <small class="text-muted">En attente</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="date" class="form-label">Date de prescription</label>
                    <input type="date" 
                           name="date" 
                           id="date" 
                           class="form-control"
                           value="<?php echo $filters['date'] ?? ''; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="statut" class="form-label">Statut de délivrance</label>
                    <select name="statut" id="statut" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="Délivrée" <?php echo ($filters['statut'] ?? '') == 'Délivrée' ? 'selected' : ''; ?>>Délivrée</option>
                        <option value="En attente" <?php echo ($filters['statut'] ?? '') == 'En attente' ? 'selected' : ''; ?>>En attente</option>
                        <option value="Partielle" <?php echo ($filters['statut'] ?? '') == 'Partielle' ? 'selected' : ''; ?>>Partielle</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="search" class="form-label">Rechercher un patient</label>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           class="form-control"
                           placeholder="Nom, prénom ou numéro dossier..."
                           value="<?php echo $filters['search'] ?? ''; ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <div class="btn-group w-100">
                        <button type="submit" class="btn btn-primary">
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

    <!-- Liste des prescriptions -->
    <div class="row">
        <div class="col-12">
            <?php if (!empty($prescriptions)): ?>
                <div class="accordion" id="prescriptionsAccordion">
                    <?php foreach ($prescriptions as $index => $prescription): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" 
                                        type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#collapse<?php echo $index; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-<?php 
                                                echo $prescription['statut_delivrance'] == 'Délivrée' ? 'success' : 
                                                       ($prescription['statut_delivrance'] == 'En attente' ? 'warning' : 
                                                       ($prescription['statut_delivrance'] == 'Partielle' ? 'info' : 'secondary')); 
                                            ?> me-2">
                                                <?php echo $prescription['statut_delivrance'] ?? 'Non traitée'; ?>
                                            </span>
                                            <strong><?php echo htmlspecialchars($prescription['patient_nom'] . ' ' . $prescription['patient_prenom']); ?></strong>
                                            <small class="text-muted ms-2"><?php echo htmlspecialchars($prescription['numero_dossier']); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">
                                                Prescrit le: <?php echo date('d/m/Y', strtotime($prescription['date_consultation'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            
                            <div id="collapse<?php echo $index; ?>" 
                                 class="accordion-collapse collapse <?php echo $index == 0 ? 'show' : ''; ?>" 
                                 data-bs-parent="#prescriptionsAccordion">
                                <div class="accordion-body">
                                    <div class="row">
                                        <!-- Informations patient -->
                                        <div class="col-md-3">
                                            <div class="card bg-light h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <i class="fas fa-user-injured me-1"></i>Informations Patient
                                                    </h6>
                                                    <p class="mb-1">
                                                        <strong>Nom:</strong> <?php echo htmlspecialchars($prescription['patient_nom'] . ' ' . $prescription['patient_prenom']); ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <strong>N° Dossier:</strong> <?php echo htmlspecialchars($prescription['numero_dossier']); ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <strong>Date naissance:</strong> <?php echo date('d/m/Y', strtotime($prescription['date_naissance'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Informations médecin -->
                                        <div class="col-md-3">
                                            <div class="card bg-light h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <i class="fas fa-user-md me-1"></i>Prescripteur
                                                    </h6>
                                                    <p class="mb-1">
                                                        <strong>Médecin:</strong> Dr. <?php echo htmlspecialchars($prescription['medecin_nom'] . ' ' . $prescription['medecin_prenom']); ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <strong>Spécialité:</strong> <?php echo htmlspecialchars($prescription['specialite']); ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <strong>Date prescription:</strong> <?php echo date('d/m/Y H:i', strtotime($prescription['date_prescription'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Prescription -->
                                        <div class="col-md-6">
                                            <div class="card bg-light h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <i class="fas fa-prescription me-1"></i>Ordonnance
                                                    </h6>
                                                    <div class="prescription-content bg-white p-3 rounded border">
                                                        <?php 
                                                        // Formatage de la prescription
                                                        $lines = explode("\n", $prescription['prescription']);
                                                        foreach ($lines as $line):
                                                            if (trim($line)):
                                                        ?>
                                                            <div class="prescription-line mb-2">
                                                                <?php echo nl2br(htmlspecialchars($line)); ?>
                                                            </div>
                                                        <?php 
                                                            endif;
                                                        endforeach; 
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Actions pharmacie -->
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-body py-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong>Suivi pharmacie:</strong>
                                                            <span class="ms-2">
                                                                <?php if ($prescription['statut_delivrance']): ?>
                                                                    <span class="badge bg-<?php 
                                                                        echo $prescription['statut_delivrance'] == 'Délivrée' ? 'success' : 
                                                                               ($prescription['statut_delivrance'] == 'En attente' ? 'warning' : 'info'); 
                                                                    ?>">
                                                                        <?php echo $prescription['statut_delivrance']; ?>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Non traitée</span>
                                                                <?php endif; ?>
                                                            </span>
                                                        </div>
                                                        
                                                        <div class="btn-group">
                                                            <!-- Boutons d'action (lecture seule) -->
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-success"
                                                                    onclick="markAsDelivered(<?php echo $prescription['id_dossier']; ?>)"
                                                                    <?php echo $prescription['statut_delivrance'] == 'Délivrée' ? 'disabled' : ''; ?>>
                                                                <i class="fas fa-check me-1"></i>Marquer comme délivrée
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-info"
                                                                    onclick="printPrescription(<?php echo $prescription['id_dossier']; ?>)">
                                                                <i class="fas fa-print me-1"></i>Imprimer
                                                            </button>
                                                            <a href="../../modules/dossiers/view.php?id=<?php echo $prescription['id_dossier']; ?>" 
                                                               class="btn btn-sm btn-outline-primary"
                                                               target="_blank">
                                                                <i class="fas fa-file-medical me-1"></i>Voir dossier complet
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-prescription-bottle-alt fa-4x text-muted mb-3"></i>
                    <h4>Aucune prescription trouvée</h4>
                    <p class="text-muted">
                        <?php echo empty($filters) ? 'Aucune prescription disponible pour le moment.' : 'Aucune prescription ne correspond aux critères de recherche.'; ?>
                    </p>
                    <?php if (!empty($filters)): ?>
                        <a href="index.php" class="btn btn-primary mt-2">
                            <i class="fas fa-times me-1"></i>Réinitialiser les filtres
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Panneau d'information -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle me-2"></i>Informations importantes</h6>
                <ul class="mb-0">
                    <li>Ce module est en <strong>lecture seule</strong> - Les prescriptions sont générées automatiquement lors des consultations.</li>
                    <li>Les médicaments doivent être délivrés uniquement sur présentation de la carte du patient.</li>
                    <li>Vérifiez toujours les allergies du patient avant toute délivrance.</li>
                    <li>Les prescriptions sont conservées pendant 3 ans conformément à la réglementation.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Scripts JavaScript -->
<script>
// Fonction pour marquer une prescription comme délivrée
function markAsDelivered(dossierId) {
    if (confirm('Confirmez-vous la délivrance complète de cette ordonnance ?')) {
        // Envoyer une requête AJAX pour mettre à jour le statut
        fetch('update_delivery.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id_dossier=${dossierId}&statut=Délivrée`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharger la page
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            alert('Erreur réseau: ' + error.message);
        });
    }
}

// Fonction pour imprimer une prescription
function printPrescription(dossierId) {
    // Ouvrir une nouvelle fenêtre avec la prescription formatée pour impression
    const printWindow = window.open(`print_prescription.php?id=${dossierId}`, '_blank');
    printWindow.focus();
}

// Gestion des raccourcis clavier
document.addEventListener('keydown', function(event) {
    // Ctrl+F pour rechercher
    if (event.ctrlKey && event.key === 'f') {
        event.preventDefault();
        document.getElementById('search').focus();
    }
    
    // Ctrl+R pour réinitialiser
    if (event.ctrlKey && event.key === 'r') {
        event.preventDefault();
        window.location.href = 'index.php';
    }
});

// Auto-refresh toutes les 5 minutes
setTimeout(function() {
    location.reload();
}, 5 * 60 * 1000);
</script>

<?php
// Inclusion du pied de page
require_once '../../includes/footer.php';
?>