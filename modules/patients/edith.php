<?php
/**
 * Modification d'un patient existant
 * Permet d'éditer tous les détails du dossier patient
 */

define('BASE_URL', '/hopital-management/');
$pageTitle = 'Modification du patient';

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';

// Vérification de l'ID patient
$id_patient = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_patient === 0) {
    die("<div class='alert alert-danger'>Erreur : ID patient manquant.</div>");
}

// Récupération du patient
$patient = fetchOne("SELECT * FROM patient WHERE id_patient = :id", [':id' => $id_patient]);
if (!$patient) {
    die("<div class='alert alert-danger'>Patient non trouvé.</div>");
}

$errors = [];
$salles = fetchAll("SELECT id_salle, nom_salle, type_salle FROM salle ORDER BY nom_salle ASC");

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $groupe_sanguin = trim($_POST['groupe_sanguin'] ?? '');
    $allergies = trim($_POST['allergies'] ?? '');
    $statut = trim($_POST['statut'] ?? '');
    $id_salle = !empty($_POST['id_salle']) ? (int)$_POST['id_salle'] : null;
    
    // Validation
    if (empty($nom)) $errors[] = "Le nom est obligatoire.";
    if (empty($prenom)) $errors[] = "Le prénom est obligatoire.";
    if (!empty($telephone) && !validerTelephone($telephone)) $errors[] = "Téléphone invalide.";
    if (!empty($email) && !validerEmail($email)) $errors[] = "Email invalide.";
    if (empty($statut) || !in_array($statut, ['En attente', 'En consultation', 'Hospitalisé', 'Sorti'])) {
        $errors[] = "Statut invalide.";
    }
    
    if (empty($errors)) {
        try {
            // Enregistrement des anciennes valeurs pour audit
            $anciennesValeurs = [
                'nom' => $patient['nom'],
                'prenom' => $patient['prenom'],
                'telephone' => $patient['telephone'],
                'email' => $patient['email'],
                'statut' => $patient['statut']
            ];
            
            // Mise à jour du patient
            $sql = "
                UPDATE patient SET
                    nom = :nom,
                    prenom = :prenom,
                    telephone = :telephone,
                    email = :email,
                    adresse = :adresse,
                    groupe_sanguin = :groupe_sanguin,
                    allergies = :allergies,
                    statut = :statut,
                    id_salle = :id_salle
                WHERE id_patient = :id
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':telephone' => $telephone,
                ':email' => $email,
                ':adresse' => $adresse,
                ':groupe_sanguin' => $groupe_sanguin,
                ':allergies' => $allergies,
                ':statut' => $statut,
                ':id_salle' => $id_salle,
                ':id' => $id_patient
            ]);
            
            // Journalisation
            $nouvellesValeurs = [
                'nom' => $nom,
                'prenom' => $prenom,
                'telephone' => $telephone,
                'email' => $email,
                'statut' => $statut
            ];
            journaliserAction('patient', $id_patient, 'UPDATE', 'Modification du patient', $anciennesValeurs, $nouvellesValeurs);
            
            $_SESSION['flash_message'] = "Patient $nom $prenom modifié avec succès.";
            $_SESSION['flash_type'] = 'success';
            header('Location: view.php?id=' . $id_patient);
            exit;
            
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la modification: " . $e->getMessage();
            error_log("Erreur update patient: " . $e->getMessage());
        }
    }
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>
        <i class="fas fa-edit me-2"></i>Modification du patient
    </h1>
    <a href="view.php?id=<?php echo $id_patient; ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Retour
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <strong>Erreurs détectées :</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nom" name="nom" 
                           value="<?php echo htmlspecialchars($patient['nom']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="prenom" name="prenom" 
                           value="<?php echo htmlspecialchars($patient['prenom']); ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" id="telephone" name="telephone" 
                           value="<?php echo htmlspecialchars($patient['telephone']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($patient['email']); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="adresse" class="form-label">Adresse</label>
                <textarea class="form-control" id="adresse" name="adresse" rows="2"><?php echo htmlspecialchars($patient['adresse']); ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="groupe_sanguin" class="form-label">Groupe sanguin</label>
                    <select class="form-select" id="groupe_sanguin" name="groupe_sanguin">
                        <option value="">-- Non spécifié --</option>
                        <?php foreach(['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'] as $groupe): ?>
                            <option value="<?php echo $groupe; ?>" <?php echo ($patient['groupe_sanguin'] === $groupe) ? 'selected' : ''; ?>>
                                <?php echo $groupe; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="id_salle" class="form-label">Salle</label>
                    <select class="form-select" id="id_salle" name="id_salle">
                        <option value="">-- Pas d'attribution --</option>
                        <?php foreach ($salles as $salle): ?>
                            <option value="<?php echo $salle['id_salle']; ?>" 
                                    <?php echo ($patient['id_salle'] == $salle['id_salle']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($salle['nom_salle'] . ' (' . $salle['type_salle'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                    <select class="form-select" id="statut" name="statut" required>
                        <option value="En attente" <?php echo ($patient['statut'] === 'En attente') ? 'selected' : ''; ?>>En attente</option>
                        <option value="En consultation" <?php echo ($patient['statut'] === 'En consultation') ? 'selected' : ''; ?>>En consultation</option>
                        <option value="Hospitalisé" <?php echo ($patient['statut'] === 'Hospitalisé') ? 'selected' : ''; ?>>Hospitalisé</option>
                        <option value="Sorti" <?php echo ($patient['statut'] === 'Sorti') ? 'selected' : ''; ?>>Sorti</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="allergies" class="form-label">Allergies</label>
                <textarea class="form-control" id="allergies" name="allergies" rows="2"><?php echo htmlspecialchars($patient['allergies']); ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-hospital">
                    <i class="fas fa-save me-1"></i>Enregistrer les modifications
                </button>
                <a href="view.php?id=<?php echo $id_patient; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
?>
