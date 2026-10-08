<?php
/**
 * Création d'un nouveau patient
 * Formulaire d'admission du patient avec tous les détails de base
 */

// Définition de la base URL pour les liens de navigation
define('BASE_URL', '/hopital-management/');
$pageTitle = 'Admission d\'un nouveau patient';

// Inclusion des fichiers de configuration et en-tête
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Variables pour le contrôle d'erreurs
$errors = [];
$success = false;

// Traitement du formulaire lors de la soumission (POST) - AVANT d'inclure header.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage des données entrées
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $date_naissance = trim($_POST['date_naissance'] ?? '');
    $sexe = trim($_POST['sexe'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $groupe_sanguin = trim($_POST['groupe_sanguin'] ?? '');
    $allergies = trim($_POST['allergies'] ?? '');
    $id_salle = !empty($_POST['id_salle']) ? (int)$_POST['id_salle'] : null;
    
    // Validation des champs obligatoires
    if (empty($nom)) {
        $errors[] = "Le nom du patient est obligatoire.";
    }
    if (empty($prenom)) {
        $errors[] = "Le prénom du patient est obligatoire.";
    }
    if (empty($date_naissance)) {
        $errors[] = "La date de naissance est obligatoire.";
    } elseif (!estDateValide($date_naissance, 'Y-m-d')) {
        $errors[] = "Le format de la date de naissance est invalide (Y-m-d).";
    }
    if (empty($sexe) || !in_array($sexe, ['M', 'F'])) {
        $errors[] = "Le sexe doit être M ou F.";
    }
    if (!empty($telephone) && !validerTelephone($telephone)) {
        $errors[] = "Le numéro de téléphone est invalide.";
    }
    if (!empty($email) && !validerEmail($email)) {
        $errors[] = "L'adresse email est invalide.";
    }
    
    // Si pas d'erreurs, insérer le patient en base de données
    if (empty($errors)) {
        try {
            // Génération d'un numéro de dossier unique
            $numero_dossier = genererNumeroDossier('PAT');
            
            // Préparation de la requête d'insertion
            $sql = "
                INSERT INTO patient (
                    numero_dossier, nom, prenom, date_naissance, sexe, 
                    adresse, telephone, email, groupe_sanguin, allergies, 
                    id_salle, statut, date_admission
                ) VALUES (
                    :numero_dossier, :nom, :prenom, :date_naissance, :sexe,
                    :adresse, :telephone, :email, :groupe_sanguin, :allergies,
                    :id_salle, 'En attente', NOW()
                )
            ";
            
            // Exécution de la requête avec les paramètres liés (protection contre l'injection SQL)
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':numero_dossier' => $numero_dossier,
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':date_naissance' => $date_naissance,
                ':sexe' => $sexe,
                ':adresse' => $adresse,
                ':telephone' => $telephone,
                ':email' => $email,
                ':groupe_sanguin' => $groupe_sanguin,
                ':allergies' => $allergies,
                ':id_salle' => $id_salle
            ]);
            
            // Récupération de l'ID du patient nouvellement créé
            $id_patient = $pdo->lastInsertId();
            
            // Journalisation de l'action pour l'audit
            journaliserAction('patient', $id_patient, 'INSERT', 'Admission du patient', null, [
                'numero_dossier' => $numero_dossier,
                'nom' => $nom,
                'prenom' => $prenom
            ]);
            
            // Message de succès
            $success = true;
            $_SESSION['flash_message'] = "Patient $nom $prenom admis avec succès (Dossier: $numero_dossier)";
            $_SESSION['flash_type'] = 'success';
            
            // Redirection vers la page du patient nouvellement créé
            header('Location: view.php?id=' . $id_patient);
            exit;
            
        } catch (PDOException $e) {
            // En cas d'erreur base de données, afficher un message
            $errors[] = "Erreur lors de l'admission du patient: " . $e->getMessage();
            error_log("Erreur insertion patient: " . $e->getMessage());
        }
    }
}

// Récupération des salles disponibles pour l'attribution (après traitement POST)
$salles = fetchAll("SELECT id_salle, nom_salle, type_salle, capacite FROM salle WHERE statut = 'Disponible' ORDER BY nom_salle ASC");

// Inclusion de header après traitement POST
require_once '../../includes/header.php';

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>
        <i class="fas fa-user-plus me-2"></i>Admission d'un nouveau patient
    </h1>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Retour à la liste
    </a>
</div>

<!-- Affichage des messages d'erreur s'il y en a -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <strong>Erreurs détectées :</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Formulaire de création d'un patient -->
<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="fas fa-file-medical me-2"></i>Informations personnelles
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" class="needs-validation">
            <div class="row">
                <!-- Nom -->
                <div class="col-md-6 mb-3">
                    <label for="nom" class="form-label">
                        Nom du patient <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="nom" 
                           name="nom" 
                           placeholder="Ex: Dupont"
                           value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>"
                           required>
                    <small class="form-text text-muted">Nom de famille du patient</small>
                </div>
                
                <!-- Prénom -->
                <div class="col-md-6 mb-3">
                    <label for="prenom" class="form-label">
                        Prénom du patient <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="prenom" 
                           name="prenom" 
                           placeholder="Ex: Jean"
                           value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>"
                           required>
                    <small class="form-text text-muted">Prénom du patient</small>
                </div>
            </div>
            
            <div class="row">
                <!-- Date de naissance -->
                <div class="col-md-6 mb-3">
                    <label for="date_naissance" class="form-label">
                        Date de naissance <span class="text-danger">*</span>
                    </label>
                    <input type="date" 
                           class="form-control" 
                           id="date_naissance" 
                           name="date_naissance"
                           value="<?php echo htmlspecialchars($_POST['date_naissance'] ?? ''); ?>"
                           required>
                    <small class="form-text text-muted">Format: JJ/MM/AAAA</small>
                </div>
                
                <!-- Sexe -->
                <div class="col-md-6 mb-3">
                    <label for="sexe" class="form-label">
                        Sexe <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="sexe" name="sexe" required>
                        <option value="">-- Sélectionnez --</option>
                        <option value="M" <?php echo (isset($_POST['sexe']) && $_POST['sexe'] === 'M') ? 'selected' : ''; ?>>Masculin</option>
                        <option value="F" <?php echo (isset($_POST['sexe']) && $_POST['sexe'] === 'F') ? 'selected' : ''; ?>>Féminin</option>
                    </select>
                </div>
            </div>
            
            <hr>
            
            <h6 class="mb-3">
                <i class="fas fa-phone me-2"></i>Informations de contact
            </h6>
            
            <div class="row">
                <!-- Téléphone -->
                <div class="col-md-6 mb-3">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="tel" 
                           class="form-control" 
                           id="telephone" 
                           name="telephone" 
                           placeholder="Ex: 0123456789"
                           value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>">
                    <small class="form-text text-muted">Numéro de téléphone du patient</small>
                </div>
                
                <!-- Email -->
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" 
                           class="form-control" 
                           id="email" 
                           name="email" 
                           placeholder="Ex: patient@example.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    <small class="form-text text-muted">Adresse email du patient</small>
                </div>
            </div>
            
            <!-- Adresse -->
            <div class="mb-3">
                <label for="adresse" class="form-label">Adresse</label>
                <textarea class="form-control" 
                          id="adresse" 
                          name="adresse" 
                          rows="2"
                          placeholder="Ex: 123 rue de la Paix, 75000 Paris"><?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?></textarea>
                <small class="form-text text-muted">Adresse complète du patient</small>
            </div>
            
            <hr>
            
            <h6 class="mb-3">
                <i class="fas fa-heartbeat me-2"></i>Informations médicales
            </h6>
            
            <div class="row">
                <!-- Groupe sanguin -->
                <div class="col-md-6 mb-3">
                    <label for="groupe_sanguin" class="form-label">Groupe sanguin</label>
                    <select class="form-select" id="groupe_sanguin" name="groupe_sanguin">
                        <option value="">-- Non spécifié --</option>
                        <option value="O+" <?php echo (isset($_POST['groupe_sanguin']) && $_POST['groupe_sanguin'] === 'O+') ? 'selected' : ''; ?>>O+</option>
                        <option value="O-" <?php echo (isset($_POST['groupe_sanguin']) && $_POST['groupe_sanguin'] === 'O-') ? 'selected' : ''; ?>>O-</option>
                        <option value="A+" <?php echo (isset($_POST['groupe_sanguin']) && $_POST['groupe_sanguin'] === 'A+') ? 'selected' : ''; ?>>A+</option>
                        <option value="A-" <?php echo (isset($_POST['groupe_sanguin']) && $_POST['groupe_sanguin'] === 'A-') ? 'selected' : ''; ?>>A-</option>
                        <option value="B+" <?php echo (isset($_POST['groupe_sanguin']) && $_POST['groupe_sanguin'] === 'B+') ? 'selected' : ''; ?>>B+</option>
                        <option value="B-" <?php echo (isset($_POST['groupe_sanguin']) && $_POST['groupe_sanguin'] === 'B-') ? 'selected' : ''; ?>>B-</option>
                        <option value="AB+" <?php echo (isset($_POST['groupe_sanguin']) && $_POST['groupe_sanguin'] === 'AB+') ? 'selected' : ''; ?>>AB+</option>
                        <option value="AB-" <?php echo (isset($_POST['groupe_sanguin']) && $_POST['groupe_sanguin'] === 'AB-') ? 'selected' : ''; ?>>AB-</option>
                    </select>
                </div>
                
                <!-- Salle d'accueil -->
                <div class="col-md-6 mb-3">
                    <label for="id_salle" class="form-label">Salle d'accueil (optionnel)</label>
                    <select class="form-select" id="id_salle" name="id_salle">
                        <option value="">-- Pas d'attribution pour le moment --</option>
                        <?php foreach ($salles as $salle): ?>
                            <option value="<?php echo $salle['id_salle']; ?>" 
                                    <?php echo (isset($_POST['id_salle']) && $_POST['id_salle'] == $salle['id_salle']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($salle['nom_salle'] . ' (' . $salle['type_salle'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Salle disponible à attribuer au patient</small>
                </div>
            </div>
            
            <!-- Allergies -->
            <div class="mb-3">
                <label for="allergies" class="form-label">Allergies connues</label>
                <textarea class="form-control" 
                          id="allergies" 
                          name="allergies" 
                          rows="2"
                          placeholder="Ex: Pénicilline, Arachides"><?php echo htmlspecialchars($_POST['allergies'] ?? ''); ?></textarea>
                <small class="form-text text-muted">Listez les allergies connues du patient (séparées par des virgules)</small>
            </div>
            
            <hr>
            
            <!-- Boutons d'action -->
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-hospital">
                    <i class="fas fa-save me-1"></i>Enregistrer l'admission
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<?php
// Inclusion du pied de page
require_once '../../includes/footer.php';
?>
