<?php
/**
 * Suppression d'un patient
 * Permet de supprimer définitivement un patient de la base (avec confirmation)
 */

define('BASE_URL', '/hopital-management/');

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Vérification de l'ID
$id_patient = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_patient === 0) {
    $_SESSION['flash_message'] = "Erreur : ID patient manquant.";
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

// Récupération du patient
$patient = fetchOne("SELECT * FROM patient WHERE id_patient = :id", [':id' => $id_patient]);

if (!$patient) {
    $_SESSION['flash_message'] = "Patient non trouvé.";
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

// Si confirmation de suppression (POST avec clé de confirmation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Journalisation avant suppression
        journaliserAction('patient', $id_patient, 'DELETE', 'Suppression du patient', [
            'nom' => $patient['nom'],
            'prenom' => $patient['prenom'],
            'numero_dossier' => $patient['numero_dossier']
        ], null);
        
        // Suppression du patient (les dossiers médicaux et consultations seront supprimés en cascade selon les contraintes DB)
        $sql = "DELETE FROM patient WHERE id_patient = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id_patient]);
        
        $_SESSION['flash_message'] = "Patient " . htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) . " supprimé avec succès.";
        $_SESSION['flash_type'] = 'success';
        header('Location: index.php');
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Erreur lors de la suppression: " . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
        error_log("Erreur suppression patient: " . $e->getMessage());
        header('Location: view.php?id=' . $id_patient);
        exit;
    }
}

// Sinon, afficher la page de confirmation
session_start();
require_once '../../includes/header.php';

?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-trash me-2"></i>Suppression du patient
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Attention !</strong> Cette action est irréversible.
                    </div>
                    
                    <p>Vous êtes sur le point de supprimer définitivement le patient :</p>
                    
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <p class="mb-2">
                                <strong>Nom :</strong> <?php echo htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']); ?>
                            </p>
                            <p class="mb-2">
                                <strong>Dossier :</strong> <?php echo htmlspecialchars($patient['numero_dossier']); ?>
                            </p>
                            <p class="mb-0">
                                <strong>Admission :</strong> <?php echo date('d/m/Y à H:i', strtotime($patient['date_admission'])); ?>
                            </p>
                        </div>
                    </div>
                    
                    <p class="text-danger">
                        <i class="fas fa-info-circle me-1"></i>
                        Tous les dossiers médicaux et consultations liés seront également supprimés.
                    </p>
                    
                    <form method="POST" action="">
                        <div class="d-flex gap-2">
                            <button type="submit" name="confirm_delete" value="1" class="btn btn-danger flex-grow-1">
                                <i class="fas fa-check me-1"></i>Confirmer la suppression
                            </button>
                            <a href="view.php?id=<?php echo $id_patient; ?>" class="btn btn-outline-secondary flex-grow-1">
                                <i class="fas fa-times me-1"></i>Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
?>
