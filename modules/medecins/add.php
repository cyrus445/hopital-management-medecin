<?php
/**
 * Ajout d'un nouveau médecin
 * Formulaire de création avec toutes les informations nécessaires
 */

define('BASE_URL', '/hopital-management/');
$pageTitle = 'Ajouter un médecin';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = strtoupper(trim($_POST['matricule'] ?? ''));
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $date_embauche = trim($_POST['date_embauche'] ?? '');
    
    // Validation
    if (empty($matricule)) $errors[] = "Le matricule est obligatoire.";
    elseif (valueExists('medecin', 'matricule', $matricule)) $errors[] = "Ce matricule existe déjà.";
    
    if (empty($nom)) $errors[] = "Le nom est obligatoire.";
    if (empty($prenom)) $errors[] = "Le prénom est obligatoire.";
    if (empty($specialite)) $errors[] = "La spécialité est obligatoire.";
    if (!empty($telephone) && !validerTelephone($telephone)) $errors[] = "Téléphone invalide.";
    if (!empty($email) && !validerEmail($email)) $errors[] = "Email invalide.";
    if (!estDateValide($date_embauche, 'Y-m-d')) $errors[] = "Date d'embauche invalide.";
    
    if (empty($errors)) {
        try {
            $sql = "
                INSERT INTO medecin (matricule, nom, prenom, specialite, telephone, email, date_embauche, statut)
                VALUES (:matricule, :nom, :prenom, :specialite, :telephone, :email, :date_embauche, 'Actif')
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':matricule' => $matricule,
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':specialite' => $specialite,
                ':telephone' => $telephone,
                ':email' => $email,
                ':date_embauche' => $date_embauche
            ]);
            
            $id = $pdo->lastInsertId();
            journaliserAction('medecin', $id, 'INSERT', 'Ajout d\'un médecin', null, ['nom' => $nom, 'prenom' => $prenom]);
            
            $_SESSION['flash_message'] = "Médecin $nom $prenom ajouté avec succès.";
            $_SESSION['flash_type'] = 'success';
            header('Location: index.php');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = "Erreur: " . $e->getMessage();
            error_log("Erreur ajout médecin: " . $e->getMessage());
        }
    }
}

// Inclusion de header après traitement POST
require_once '../../includes/header.php';

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-user-md me-2"></i>Ajouter un médecin</h1>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Retour
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><strong>Erreurs :</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="matricule" class="form-label">Matricule <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="matricule" name="matricule" 
                           placeholder="Ex: MED001" value="<?php echo htmlspecialchars($_POST['matricule'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="specialite" class="form-label">Spécialité <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="specialite" name="specialite" 
                           placeholder="Ex: Cardiologie" value="<?php echo htmlspecialchars($_POST['specialite'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nom" name="nom" 
                           value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="prenom" name="prenom" 
                           value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" id="telephone" name="telephone" 
                           value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="date_embauche" class="form-label">Date d'embauche <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="date_embauche" name="date_embauche" 
                       value="<?php echo htmlspecialchars($_POST['date_embauche'] ?? date('Y-m-d')); ?>" required>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-hospital">
                    <i class="fas fa-save me-1"></i>Enregistrer
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
