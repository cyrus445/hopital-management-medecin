<?php
/**
 * Suppression d'un médecin
 */

define('BASE_URL', '/hopital-management/');

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    $_SESSION['flash_message'] = "Erreur: ID manquant.";
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$medecin = fetchOne("SELECT * FROM medecin WHERE id_medecin = :id", [':id' => $id]);

if (!$medecin) {
    $_SESSION['flash_message'] = "Médecin non trouvé.";
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

// Confirmação de suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        journaliserAction('medecin', $id, 'DELETE', 'Suppression médecin', 
            ['nom' => $medecin['nom']], null);
        
        $pdo->prepare("DELETE FROM medecin WHERE id_medecin = :id")->execute([':id' => $id]);
        
        $_SESSION['flash_message'] = "Médecin supprimé avec succès.";
        $_SESSION['flash_type'] = 'success';
        header('Location: index.php');
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Erreur: " . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
        error_log("Erreur suppression médecin: " . $e->getMessage());
        header('Location: index.php');
        exit;
    }
}

session_start();
require_once '../../includes/header.php';
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-trash me-2"></i>Suppression du médecin</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Cette action est irréversible.</div>
                    
                    <p>Êtes-vous sûr de vouloir supprimer :</p>
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <p><strong>Nom :</strong> <?php echo htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']); ?></p>
                            <p><strong>Matricule :</strong> <?php echo htmlspecialchars($medecin['matricule']); ?></p>
                            <p class="mb-0"><strong>Spécialité :</strong> <?php echo htmlspecialchars($medecin['specialite']); ?></p>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <div class="d-flex gap-2">
                            <button type="submit" name="confirm_delete" value="1" class="btn btn-danger flex-grow-1">
                                <i class="fas fa-check me-1"></i>Confirmer
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary flex-grow-1">
                                <i class="fas fa-times me-1"></i>Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
