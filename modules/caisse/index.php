<?php
/**
 * Module Caisse - Affichage des prescriptions et examens pour facturation
 */

define('BASE_URL', '/hopital-management/');
$pageTitle = 'Caisse';

require_once '../../config/database.php';
require_once '../../includes/header.php';

// Détection des colonnes optionnelles en base
$hasStatutPaiement = function_exists('dbColumnExists') ? dbColumnExists('dossier_medical', 'statut_paiement') : false;
$hasMontantTotal = function_exists('dbColumnExists') ? dbColumnExists('dossier_medical', 'montant_total') : false;
$hasMontantPaye = function_exists('dbColumnExists') ? dbColumnExists('dossier_medical', 'montant_paye') : false;

// Récupération des tarifs (exemple simplifié)
$tarifs = [
    'consultation_generale' => 5000,
    'consultation_specialiste' => 10000,
    'radio_poumon' => 15000,
    'scanner' => 50000,
    'analyse_sang' => 8000,
    'echographie' => 25000,
    'hospitalisation_jour' => 30000,
    'medicament' => 2000 // par médicament
];

// Filtres
$filters = [];
$whereClause = "WHERE (d.examens_demandes IS NOT NULL AND d.examens_demandes != '') 
                 OR (d.prescription IS NOT NULL AND d.prescription != '')";
$params = [];

// Filtre par statut de paiement (si la colonne existe)
if ($hasStatutPaiement) {
    if (isset($_GET['statut_paiement']) && !empty($_GET['statut_paiement'])) {
        $whereClause .= " AND d.statut_paiement = :statut_paiement";
        $params[':statut_paiement'] = $_GET['statut_paiement'];
        $filters['statut_paiement'] = $_GET['statut_paiement'];
    }
}

// Filtre par date
if (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) {
    $whereClause .= " AND DATE(c.date_consultation) >= :date_debut";
    $params[':date_debut'] = $_GET['date_debut'];
    $filters['date_debut'] = $_GET['date_debut'];
}

if (isset($_GET['date_fin']) && !empty($_GET['date_fin'])) {
    $whereClause .= " AND DATE(c.date_consultation) <= :date_fin";
    $params[':date_fin'] = $_GET['date_fin'];
    $filters['date_fin'] = $_GET['date_fin'];
}

// Récupération des données pour facturation
$statutSelect = $hasStatutPaiement ? 'd.statut_paiement,' : "NULL as statut_paiement,";
$montantTotalSelect = $hasMontantTotal ? 'd.montant_total,' : "NULL as montant_total,";
$montantPayeSelect = $hasMontantPaye ? 'd.montant_paye,' : "NULL as montant_paye,";

$query = "
    SELECT 
        d.id_dossier,
        d.prescription,
        d.examens_demandes,
        d.resultats_examens,
        " . $statutSelect . "
        " . $montantTotalSelect . "
        " . $montantPayeSelect . "
        d.date_creation,
        p.id_patient,
        p.nom as patient_nom,
        p.prenom as patient_prenom,
        p.numero_dossier,
        p.telephone,
        p.adresse,
        m.nom as medecin_nom,
        m.prenom as medecin_prenom,
        m.specialite,
        c.date_consultation,
        c.motif,
        s.type_salle,
        s.nom_salle
    FROM dossier_medical d
    JOIN patient p ON d.id_patient = p.id_patient
    JOIN medecin m ON d.id_medecin = m.id_medecin
    JOIN consultation c ON d.id_consultation = c.id_consultation
    LEFT JOIN salle s ON p.id_salle = s.id_salle
    $whereClause
    ORDER BY c.date_consultation DESC
    LIMIT 50
";

$dossiers = fetchAll($query, $params);

// Calcul des statistiques financières
$statsQuery = null;
// Construire une requête de statistiques adaptée selon les colonnes disponibles
if ($hasMontantTotal || $hasMontantPaye || $hasStatutPaiement) {
    $selectTotal = $hasMontantTotal ? 'SUM(d.montant_total) as total_a_payer,' : '0 as total_a_payer,';
    $selectPaye = $hasMontantPaye ? 'SUM(d.montant_paye) as total_paye,' : '0 as total_paye,';
    $selectPayStatus = $hasStatutPaiement ? "SUM(CASE WHEN d.statut_paiement = 'Payé' THEN 1 ELSE 0 END) as factures_payees,\n        SUM(CASE WHEN d.statut_paiement = 'Partiel' THEN 1 ELSE 0 END) as factures_partielles,\n        SUM(CASE WHEN d.statut_paiement = 'Impayé' THEN 1 ELSE 0 END) as factures_impayees," :
        "0 as factures_payees, 0 as factures_partielles, 0 as factures_impayees,";

    $statsQuery = "
        SELECT 
            COUNT(*) as total_factures,
            " . $selectTotal . "
            " . $selectPaye . "
            " . $selectPayStatus . "
            1 as dummy_col
        FROM dossier_medical d
        WHERE d.montant_total IS NOT NULL OR d.prescription IS NOT NULL OR d.examens_demandes IS NOT NULL
    ";

    // Les colonnes additionnelles renverront 0 si non disponibles
    // On supprimera la colonne dummy_col après fetch
    $statistiques = fetchOne($statsQuery);
    if ($statistiques && isset($statistiques['dummy_col'])) unset($statistiques['dummy_col']);
} else {
    // Pas de colonnes financières : fournir des zéros propres
    $statistiques = [
        'total_factures' => 0,
        'total_a_payer' => 0,
        'total_paye' => 0,
        'factures_payees' => 0,
        'factures_partielles' => 0,
        'factures_impayees' => 0
    ];
}
?>

<div class="container-fluid">
    <!-- En-tête avec statistiques financières -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="display-6 mb-3">
                <i class="fas fa-calculator text-warning me-2"></i>Service Caisse
            </h1>
        </div>
        
        <!-- Cartes statistiques -->
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total à payer</h6>
                    <h3 class="mb-0"><?php echo number_format($statistiques['total_a_payer'] ?? 0, 0, ',', ' '); ?> FCFA</h3>
                    <small><?php echo $statistiques['total_factures'] ?? 0; ?> factures</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Total payé</h6>
                    <h3 class="mb-0"><?php echo number_format($statistiques['total_paye'] ?? 0, 0, ',', ' '); ?> FCFA</h3>
                    <small><?php echo $statistiques['factures_payees'] ?? 0; ?> factures payées</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">En attente</h6>
                    <h3 class="mb-0"><?php echo number_format(($statistiques['total_a_payer'] ?? 0) - ($statistiques['total_paye'] ?? 0), 0, ',', ' '); ?> FCFA</h3>
                    <small><?php echo ($statistiques['factures_impayees'] ?? 0) + ($statistiques['factures_partielles'] ?? 0); ?> factures</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Paiements partiels</h6>
                    <h3 class="mb-0"><?php echo $statistiques['factures_partielles'] ?? 0; ?></h3>
                    <small>Factures</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="date_debut" class="form-label">Date début</label>
                    <input type="date" 
                           name="date_debut" 
                           id="date_debut" 
                           class="form-control"
                           value="<?php echo $filters['date_debut'] ?? ''; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="date_fin" class="form-label">Date fin</label>
                    <input type="date" 
                           name="date_fin" 
                           id="date_fin" 
                           class="form-control"
                           value="<?php echo $filters['date_fin'] ?? ''; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="statut_paiement" class="form-label">Statut paiement</label>
                    <select name="statut_paiement" id="statut_paiement" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="Payé" <?php echo ($filters['statut_paiement'] ?? '') == 'Payé' ? 'selected' : ''; ?>>Payé</option>
                        <option value="Partiel" <?php echo ($filters['statut_paiement'] ?? '') == 'Partiel' ? 'selected' : ''; ?>>Partiel</option>
                        <option value="Impayé" <?php echo ($filters['statut_paiement'] ?? '') == 'Impayé' ? 'selected' : ''; ?>>Impayé</option>
                    </select>
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <div class="btn-group w-100">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-filter me-1"></i>Filtrer
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Réinitialiser
                        </a>
                        <button type="button" class="btn btn-success" onclick="genererRapportJournalier()">
                            <i class="fas fa-file-excel me-1"></i>Rapport
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des factures -->
    <div class="row">
        <div class="col-12">
            <?php if (!empty($dossiers)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Médecin</th>
                                <th>Examens</th>
                                <th>Prescriptions</th>
                                <th>Hospitalisation</th>
                                <th>Total</th>
                                <th>Payé</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dossiers as $dossier): 
                                // Calcul du montant (simplifié)
                                $montantConsultation = $dossier['specialite'] == 'Généraliste' ? 
                                                      $tarifs['consultation_generale'] : $tarifs['consultation_specialiste'];
                                
                                // Estimation du coût des examens
                                $examens = explode("\n", $dossier['examens_demandes'] ?? '');
                                $coutExamens = 0;
                                foreach ($examens as $examen) {
                                    if (stripos($examen, 'radio') !== false) {
                                        $coutExamens += $tarifs['radio_poumon'];
                                    } elseif (stripos($examen, 'scanner') !== false) {
                                        $coutExamens += $tarifs['scanner'];
                                    } elseif (stripos($examen, 'analyse') !== false) {
                                        $coutExamens += $tarifs['analyse_sang'];
                                    } elseif (stripos($examen, 'échographie') !== false) {
                                        $coutExamens += $tarifs['echographie'];
                                    }
                                }
                                
                                // Estimation du coût des médicaments
                                $prescriptions = explode("\n", $dossier['prescription'] ?? '');
                                $coutMedicaments = count($prescriptions) * $tarifs['medicament'];
                                
                                // Coût d'hospitalisation
                                $coutHospitalisation = $dossier['nom_salle'] ? $tarifs['hospitalisation_jour'] : 0;
                                
                                // Total
                                $totalEstime = $montantConsultation + $coutExamens + $coutMedicaments + $coutHospitalisation;
                                $totalReel = $dossier['montant_total'] ?? $totalEstime;
                                $paye = $dossier['montant_paye'] ?? 0;
                                $reste = $totalReel - $paye;
                            ?>
                                <tr>
                                    <td>
                                        <small><?php echo date('d/m/Y', strtotime($dossier['date_consultation'])); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($dossier['patient_nom'] . ' ' . $dossier['patient_prenom']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($dossier['numero_dossier']); ?></small>
                                    </td>
                                    <td>
                                        Dr. <?php echo htmlspecialchars($dossier['medecin_nom']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($dossier['specialite']); ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($dossier['examens_demandes'])): ?>
                                            <span class="badge bg-info"><?php echo count($examens); ?> examens</span>
                                            <br>
                                            <small class="text-muted"><?php echo number_format($coutExamens, 0, ',', ' '); ?> FCFA</small>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Aucun</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($dossier['prescription'])): ?>
                                            <span class="badge bg-primary"><?php echo count($prescriptions); ?> médicaments</span>
                                            <br>
                                            <small class="text-muted"><?php echo number_format($coutMedicaments, 0, ',', ' '); ?> FCFA</small>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Aucune</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($dossier['nom_salle']): ?>
                                            <span class="badge bg-warning">Oui</span>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($dossier['nom_salle']); ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Non</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($totalReel, 0, ',', ' '); ?> FCFA</strong>
                                    </td>
                                    <td>
                                        <?php echo number_format($paye, 0, ',', ' '); ?> FCFA
                                    </td>
                                    <td>
                                        <?php 
                                            $statut = $dossier['statut_paiement'] ?? 'À calculer';
                                            $badgeClass = [
                                                'Payé' => 'success',
                                                'Partiel' => 'warning',
                                                'Impayé' => 'danger',
                                                'À calculer' => 'secondary'
                                            ][$statut] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?>">
                                            <?php echo $statut; ?>
                                        </span>
                                        <?php if ($reste > 0): ?>
                                            <br>
                                            <small class="text-muted">Reste: <?php echo number_format($reste, 0, ',', ' '); ?> FCFA</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" 
                                                    class="btn btn-outline-primary"
                                                    onclick="afficherDetails(<?php echo $dossier['id_dossier']; ?>)"
                                                    title="Détails">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-success"
                                                    onclick="enregistrerPaiement(<?php echo $dossier['id_dossier']; ?>, <?php echo $reste; ?>)"
                                                    <?php echo $reste <= 0 ? 'disabled' : ''; ?>
                                                    title="Enregistrer paiement">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-info"
                                                    onclick="genererFacture(<?php echo $dossier['id_dossier']; ?>)"
                                                    title="Générer facture">
                                                <i class="fas fa-file-invoice"></i>
                                            </button>
                                            <a href="../../modules/dossiers/view.php?id=<?php echo $dossier['id_dossier']; ?>" 
                                               class="btn btn-outline-warning"
                                               target="_blank"
                                               title="Voir dossier">
                                                <i class="fas fa-file-medical"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="6" class="text-end">Totaux:</th>
                                <th>
                                    <?php 
                                        $totalGeneral = array_sum(array_column($dossiers, 'montant_total'));
                                        echo number_format($totalGeneral, 0, ',', ' ') . ' FCFA';
                                    ?>
                                </th>
                                <th>
                                    <?php 
                                        $totalPaye = array_sum(array_column($dossiers, 'montant_paye'));
                                        echo number_format($totalPaye, 0, ',', ' ') . ' FCFA';
                                    ?>
                                </th>
                                <th>
                                    <?php 
                                        $totalReste = $totalGeneral - $totalPaye;
                                        echo number_format($totalReste, 0, ',', ' ') . ' FCFA restants';
                                    ?>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-file-invoice-dollar fa-4x text-muted mb-3"></i>
                    <h4>Aucune facture trouvée</h4>
                    <p class="text-muted">
                        <?php echo empty($filters) ? 'Aucune facture disponible pour le moment.' : 'Aucune facture ne correspond aux critères de recherche.'; ?>
                    </p>
                    <?php if (!empty($filters)): ?>
                        <a href="index.php" class="btn btn-warning mt-2">
                            <i class="fas fa-times me-1"></i>Réinitialiser les filtres
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Panneau de caisse -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-cash-register me-2"></i>Encaissement
                    </h6>
                </div>
                <div class="card-body">
                    <form id="encaissementForm">
                        <div class="mb-3">
                            <label for="montant" class="form-label">Montant à encaisser</label>
                            <div class="input-group">
                                <input type="number" 
                                       id="montant" 
                                       class="form-control" 
                                       placeholder="Montant en FCFA"
                                       min="0"
                                       step="100">
                                <span class="input-group-text">FCFA</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="mode_paiement" class="form-label">Mode de paiement</label>
                            <select id="mode_paiement" class="form-select">
                                <option value="Espèces">Espèces</option>
                                <option value="Carte bancaire">Carte bancaire</option>
                                <option value="Chèque">Chèque</option>
                                <option value="Virement">Virement</option>
                                <option value="Assurance">Assurance</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reference" class="form-label">Référence</label>
                            <input type="text" 
                                   id="reference" 
                                   class="form-control" 
                                   placeholder="Numéro de chèque/transaction...">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success" onclick="encaisser()">
                                <i class="fas fa-check-circle me-1"></i>Encaisser
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="imprimerRecu()">
                                <i class="fas fa-print me-1"></i>Imprimer reçu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Résumé du jour
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <td>Total encaissé:</td>
                                        <td class="text-end">
                                            <strong id="totalEncaisse">0 FCFA</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Nombre de transactions:</td>
                                        <td class="text-end">
                                            <strong id="nbTransactions">0</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Moyenne par transaction:</td>
                                        <td class="text-end">
                                            <strong id="moyenneTransaction">0 FCFA</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="modePaiementChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dernières transactions -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-history me-2"></i>Dernières transactions
                    </h6>
                </div>
                <div class="card-body">
                    <div id="transactionsList">
                        <!-- Les transactions seront chargées ici -->
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-clock fa-2x mb-2"></i>
                            <p>Aucune transaction aujourd'hui</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour les détails -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la facture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

<!-- Modal pour paiement -->
<div class="modal fade" id="paiementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enregistrement du paiement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="paiementForm">
                    <input type="hidden" id="dossierId">
                    <input type="hidden" id="montantRestant">
                    
                    <div class="mb-3">
                        <label for="montantPaiement" class="form-label">Montant payé</label>
                        <div class="input-group">
                            <input type="number" 
                                   id="montantPaiement" 
                                   class="form-control" 
                                   min="0"
                                   step="100"
                                   required>
                            <span class="input-group-text">FCFA</span>
                        </div>
                        <div class="form-text">
                            Reste à payer: <span id="resteAPayer">0</span> FCFA
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modalModePaiement" class="form-label">Mode de paiement</label>
                        <select id="modalModePaiement" class="form-select" required>
                            <option value="Espèces">Espèces</option>
                            <option value="Carte bancaire">Carte bancaire</option>
                            <option value="Chèque">Chèque</option>
                            <option value="Virement">Virement</option>
                            <option value="Assurance">Assurance</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modalReference" class="form-label">Référence</label>
                        <input type="text" 
                               id="modalReference" 
                               class="form-control" 
                               placeholder="Optionnel">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" onclick="validerPaiement()">Valider le paiement</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Variables globales
let transactions = [];
let totalEncaisse = 0;

// Initialisation du graphique
let paiementChart;
function initChart() {
    const ctx = document.getElementById('modePaiementChart').getContext('2d');
    paiementChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Espèces', 'Carte', 'Chèque', 'Virement', 'Assurance'],
            datasets: [{
                data: [0, 0, 0, 0, 0],
                backgroundColor: [
                    '#4CAF50',
                    '#2196F3',
                    '#FF9800',
                    '#9C27B0',
                    '#F44336'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Afficher les détails d'une facture
function afficherDetails(dossierId) {
    fetch(`get_details.php?id=${dossierId}`)
        .then(response => response.json())
        .then(data => {
            const modalContent = document.getElementById('detailsContent');
            modalContent.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informations patient</h6>
                        <p><strong>Nom:</strong> ${data.patient_nom} ${data.patient_prenom}</p>
                        <p><strong>N° Dossier:</strong> ${data.numero_dossier}</p>
                        <p><strong>Téléphone:</strong> ${data.telephone}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Informations médicales</h6>
                        <p><strong>Médecin:</strong> Dr. ${data.medecin_nom}</p>
                        <p><strong>Date:</strong> ${new Date(data.date_consultation).toLocaleDateString('fr-FR')}</p>
                        <p><strong>Motif:</strong> ${data.motif}</p>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Détail des coûts</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>Consultation:</td>
                                <td class="text-end">${data.cout_consultation.toLocaleString('fr-FR')} FCFA</td>
                            </tr>
                            <tr>
                                <td>Examens:</td>
                                <td class="text-end">${data.cout_examens.toLocaleString('fr-FR')} FCFA</td>
                            </tr>
                            <tr>
                                <td>Médicaments:</td>
                                <td class="text-end">${data.cout_medicaments.toLocaleString('fr-FR')} FCFA</td>
                            </tr>
                            <tr>
                                <td>Hospitalisation:</td>
                                <td class="text-end">${data.cout_hospitalisation.toLocaleString('fr-FR')} FCFA</td>
                            </tr>
                            <tr class="table-primary">
                                <td><strong>Total:</strong></td>
                                <td class="text-end"><strong>${data.total.toLocaleString('fr-FR')} FCFA</strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Historique des paiements</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Montant</th>
                                        <th>Mode</th>
                                        <th>Référence</th>
                                    </tr>
                                </thead>
                                <tbody id="historiquePaiements">
                                    <!-- Les paiements seront ajoutés ici -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    ${data.statut_paiement === 'Payé' ? 
                      'Facture entièrement réglée.' : 
                      `Reste à payer: ${(data.total - data.montant_paye).toLocaleString('fr-FR')} FCFA`}
                </div>
            `;
            
            // Charger l'historique des paiements
            chargerHistoriquePaiements(dossierId);
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('detailsContent').innerHTML = 
                '<div class="alert alert-danger">Erreur lors du chargement des détails.</div>';
        });
    
    // Afficher la modal
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}

// Charger l'historique des paiements
function chargerHistoriquePaiements(dossierId) {
    fetch(`get_paiements.php?id=${dossierId}`)
        .then(response => response.json())
        .then(paiements => {
            const tbody = document.getElementById('historiquePaiements');
            tbody.innerHTML = paiements.map(p => `
                <tr>
                    <td>${new Date(p.date_paiement).toLocaleDateString('fr-FR')}</td>
                    <td>${parseInt(p.montant).toLocaleString('fr-FR')} FCFA</td>
                    <td><span class="badge bg-secondary">${p.mode_paiement}</span></td>
                    <td>${p.reference || '-'}</td>
                </tr>
            `).join('');
        });
}

// Enregistrer un paiement
function enregistrerPaiement(dossierId, reste) {
    document.getElementById('dossierId').value = dossierId;
    document.getElementById('montantRestant').value = reste;
    document.getElementById('resteAPayer').textContent = reste.toLocaleString('fr-FR');
    document.getElementById('montantPaiement').value = reste;
    document.getElementById('montantPaiement').max = reste;
    
    new bootstrap.Modal(document.getElementById('paiementModal')).show();
}

// Valider un paiement
function validerPaiement() {
    const dossierId = document.getElementById('dossierId').value;
    const montant = document.getElementById('montantPaiement').value;
    const mode = document.getElementById('modalModePaiement').value;
    const reference = document.getElementById('modalReference').value;
    
    fetch('enregistrer_paiement.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `dossier_id=${dossierId}&montant=${montant}&mode=${mode}&reference=${reference}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour la transaction
            ajouterTransaction({
                montant: parseFloat(montant),
                mode: mode,
                reference: reference,
                dossier_id: dossierId
            });
            
            // Fermer la modal et recharger la page
            bootstrap.Modal.getInstance(document.getElementById('paiementModal')).hide();
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        alert('Erreur réseau: ' + error.message);
    });
}

// Ajouter une transaction
function ajouterTransaction(transaction) {
    transactions.push({
        ...transaction,
        date: new Date().toISOString()
    });
    
    // Mettre à jour les statistiques
    totalEncaisse += transaction.montant;
    document.getElementById('totalEncaisse').textContent = totalEncaisse.toLocaleString('fr-FR') + ' FCFA';
    document.getElementById('nbTransactions').textContent = transactions.length;
    document.getElementById('moyenneTransaction').textContent = 
        (totalEncaisse / transactions.length).toLocaleString('fr-FR', {maximumFractionDigits: 0}) + ' FCFA';
    
    // Mettre à jour le graphique
    updateChart();
    
    // Mettre à jour la liste des transactions
    updateTransactionsList();
}

// Mettre à jour le graphique
function updateChart() {
    const modes = ['Espèces', 'Carte bancaire', 'Chèque', 'Virement', 'Assurance'];
    const data = modes.map(mode => {
        return transactions.filter(t => t.mode === mode)
                          .reduce((sum, t) => sum + t.montant, 0);
    });
    
    paiementChart.data.datasets[0].data = data;
    paiementChart.update();
}

// Mettre à jour la liste des transactions
function updateTransactionsList() {
    const container = document.getElementById('transactionsList');
    const lastTransactions = transactions.slice(-5).reverse();
    
    container.innerHTML = lastTransactions.map(t => `
        <div class="transaction-item border-bottom py-2">
            <div class="d-flex justify-content-between">
                <div>
                    <strong>${t.montant.toLocaleString('fr-FR')} FCFA</strong>
                    <small class="text-muted ms-2">${t.mode}</small>
                </div>
                <div>
                    <small class="text-muted">${new Date(t.date).toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'})}</small>
                </div>
            </div>
            ${t.reference ? `<small class="text-muted">Réf: ${t.reference}</small>` : ''}
        </div>
    `).join('');
}

// Encaisser une transaction directe
function encaisser() {
    const montant = parseFloat(document.getElementById('montant').value);
    const mode = document.getElementById('mode_paiement').value;
    const reference = document.getElementById('reference').value;
    
    if (!montant || montant <= 0) {
        alert('Veuillez saisir un montant valide');
        return;
    }
    
    ajouterTransaction({
        montant: montant,
        mode: mode,
        reference: reference
    });
    
    // Réinitialiser le formulaire
    document.getElementById('montant').value = '';
    document.getElementById('reference').value = '';
    
    alert(`Encaissement de ${montant.toLocaleString('fr-FR')} FCFA enregistré !`);
}

// Générer une facture
function genererFacture(dossierId) {
    window.open(`facture.php?id=${dossierId}`, '_blank');
}

// Générer un rapport journalier
function genererRapportJournalier() {
    const dateDebut = document.getElementById('date_debut').value || new Date().toISOString().split('T')[0];
    const dateFin = document.getElementById('date_fin').value || new Date().toISOString().split('T')[0];
    
    window.open(`rapport_journalier.php?debut=${dateDebut}&fin=${dateFin}`, '_blank');
}

// Imprimer un reçu
function imprimerRecu() {
    if (transactions.length === 0) {
        alert('Aucune transaction à imprimer');
        return;
    }
    
    const lastTransaction = transactions[transactions.length - 1];
    window.open(`recu.php?montant=${lastTransaction.montant}&mode=${lastTransaction.mode}&reference=${lastTransaction.reference || ''}`, '_blank');
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initChart();
    
    // Charger les transactions du jour depuis le localStorage
    const today = new Date().toDateString();
    const savedTransactions = localStorage.getItem(`transactions_${today}`);
    if (savedTransactions) {
        transactions = JSON.parse(savedTransactions);
        transactions.forEach(t => totalEncaisse += t.montant);
        updateTransactionsList();
        updateChart();
        
        document.getElementById('totalEncaisse').textContent = totalEncaisse.toLocaleString('fr-FR') + ' FCFA';
        document.getElementById('nbTransactions').textContent = transactions.length;
        document.getElementById('moyenneTransaction').textContent = 
            (totalEncaisse / transactions.length).toLocaleString('fr-FR', {maximumFractionDigits: 0}) + ' FCFA';
    }
    
    // Sauvegarder les transactions dans le localStorage
    setInterval(() => {
        localStorage.setItem(`transactions_${today}`, JSON.stringify(transactions));
    }, 5000);
    
    // Raccourcis clavier
    document.addEventListener('keydown', function(event) {
        // Ctrl+E pour encaisser
        if (event.ctrlKey && event.key === 'e') {
            event.preventDefault();
            document.getElementById('montant').focus();
        }
        
        // Ctrl+P pour imprimer
        if (event.ctrlKey && event.key === 'p') {
            event.preventDefault();
            imprimerRecu();
        }
        
        // Ctrl+R pour rapport
        if (event.ctrlKey && event.key === 'r') {
            event.preventDefault();
            genererRapportJournalier();
        }
    });
});

// Calcul automatique de la monnaie
document.getElementById('montant').addEventListener('input', function() {
    const montant = parseFloat(this.value) || 0;
    const montantRecu = 100000; // Exemple: reçu 100,000 FCFA
    const monnaie = montantRecu - montant;
    
    if (monnaie > 0) {
        document.getElementById('monnaieInfo').innerHTML = `
            <small class="text-success">
                Monnaie à rendre: ${monnaie.toLocaleString('fr-FR')} FCFA
            </small>
        `;
    } else if (monnaie < 0) {
        document.getElementById('monnaieInfo').innerHTML = `
            <small class="text-danger">
                Montant insuffisant: ${Math.abs(monnaie).toLocaleString('fr-FR')} FCFA
            </small>
        `;
    } else {
        document.getElementById('monnaieInfo').innerHTML = '';
    }
});
</script>

<?php
// Inclusion du pied de page
require_once '../../includes/footer.php';
?>