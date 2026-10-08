<?php
/**
 * Modification d'un médecin
 */

define('BASE_URL', '/hopital-management/');
$pageTitle = 'Modifier un médecin';

require_once '../../config/database.php';
require_once '../../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) die("<div class='alert alert-danger'>Erreur: ID manquant.</div>");

$medecin = fetchOne("SELECT * FROM medecin WHERE id_medecin = :id", [':id' => $id]);
if (!$medecin) die("<div class='alert alert-danger'>Médecin non trouvé.</div>");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $statut = trim($_POST['statut'] ?? '');
    
    if (empty($nom)) $errors[] = "Le nom est obligatoire.";
    if (empty($prenom)) $errors[] = "Le prénom est obligatoire.";
    if (empty($specialite)) $errors[] = "La spécialité est obligatoire.";
    if (!empty($telephone) && !validerTelephone($telephone)) $errors[] = "Téléphone invalide.";
    if (!empty($email) && !validerEmail($email)) $errors[] = "Email invalide.";
    if (!in_array($statut, ['Actif', 'Inactif', 'Congé'])) $errors[] = "Statut invalide.";
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE medecin SET nom=:nom, prenom=:prenom, specialite=:specialite, 
                    telephone=:telephone, email=:email, statut=:statut WHERE id_medecin=:id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':specialite' => $specialite,
                ':telephone' => $telephone,
                ':email' => $email,
                ':statut' => $statut,
                ':id' => $id
            ]);
            
            journaliserAction('medecin', $id, 'UPDATE', 'Modification médecin', 
                ['nom' => $medecin['nom']], ['nom' => $nom]);
            
            $_SESSION['flash_message'] = "Médecin modifié avec succès.";
            $_SESSION['flash_type'] = 'success';
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur: " . $e->getMessage();
        }
    }
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-edit me-2"></i>Modifier un médecin</h1>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i><strong>Erreurs :</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Matricule</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($medecin['matricule']); ?>" disabled>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="specialite" class="form-label">Spécialité <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="specialite" name="specialite" 
                           value="<?php echo htmlspecialchars($medecin['specialite']); ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nom" name="nom" 
                           value="<?php echo htmlspecialchars($medecin['nom']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="prenom" name="prenom" 
                           value="<?php echo htmlspecialchars($medecin['prenom']); ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" id="telephone" name="telephone" 
                           value="<?php echo htmlspecialchars($medecin['telephone']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($medecin['email']); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                <select class="form-select" id="statut" name="statut" required>
                    <option value="Actif" <?php echo $medecin['statut'] === 'Actif' ? 'selected' : ''; ?>>Actif</option>
                    <option value="Inactif" <?php echo $medecin['statut'] === 'Inactif' ? 'selected' : ''; ?>>Inactif</option>
                    <option value="Congé" <?php echo $medecin['statut'] === 'Congé' ? 'selected' : ''; ?>>En congé</option>
                </select>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-hospital"><i class="fas fa-save me-1"></i>Enregistrer</button>
                <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i>Annuler</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
