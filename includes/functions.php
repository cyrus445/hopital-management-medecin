<?php
/**
 * Fonctions utilitaires pour le système de gestion hospitalière
 * Contient des fonctions réutilisables dans tout le projet
 */

/**
 * Formate une date en français
 * 
 * @param string $date La date à formater
 * @param bool $includeTime Inclure l'heure (par défaut: false)
 * @return string La date formatée
 */
function formatDate($date, $includeTime = false) {
    if (empty($date) || $date == '0000-00-00') {
        return 'N/A';
    }
    
    $timestamp = strtotime($date);
    $format = $includeTime ? 'd/m/Y à H:i' : 'd/m/Y';
    
    return date($format, $timestamp);
}

/**
 * Calcule l'âge à partir d'une date de naissance
 * 
 * @param string $dateNaissance Date de naissance (format Y-m-d)
 * @return int Âge en années
 */
function calculerAge($dateNaissance) {
    if (empty($dateNaissance)) {
        return 0;
    }
    
    $date = new DateTime($dateNaissance);
    $aujourdhui = new DateTime();
    $difference = $date->diff($aujourdhui);
    
    return $difference->y;
}

/**
 * Génère un numéro de dossier unique
 * 
 * @param string $prefixe Préfixe pour le numéro (par défaut: 'PAT')
 * @return string Numéro de dossier unique
 */
function genererNumeroDossier($prefixe = 'PAT') {
    $annee = date('Y');
    $mois = date('m');
    $numero = rand(1000, 9999);
    
    return $prefixe . '-' . $annee . $mois . '-' . $numero;
}

/**
 * Valide un numéro de téléphone
 * 
 * @param string $telephone Le numéro à valider
 * @return bool True si valide
 */
function validerTelephone($telephone) {
    // Supprimer les espaces et caractères spéciaux
    $telephone = preg_replace('/[^0-9]/', '', $telephone);
    
    // Validation pour les numéros français
    if (strlen($telephone) == 10 && preg_match('/^0[1-9][0-9]{8}$/', $telephone)) {
        return true;
    }
    
    // Validation pour les numéros internationaux (avec indicatif)
    if (strlen($telephone) > 10 && preg_match('/^\+[0-9]{1,3}[0-9]{4,14}$/', $telephone)) {
        return true;
    }
    
    return false;
}

/**
 * Valide une adresse email
 * 
 * @param string $email L'email à valider
 * @return bool True si valide
 */
function validerEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Nettoie une chaîne de caractères pour éviter les injections XSS
 * 
 * @param string $input La chaîne à nettoyer
 * @return string La chaîne nettoyée
 */
function nettoyerInput($input) {
    if (is_array($input)) {
        return array_map('nettoyerInput', $input);
    }
    
    // Supprimer les espaces en début et fin
    $input = trim($input);
    
    // Échapper les caractères spéciaux HTML
    $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Supprimer les balises PHP potentiellement dangereuses
    $input = str_replace(array('<?php', '<?', '?>'), array('&lt;?php', '&lt;?', '?&gt;'), $input);
    
    return $input;
}

/**
 * Format un montant en devise locale
 * 
 * @param float $montant Le montant à formater
 * @param string $devise La devise (par défaut: 'FCFA')
 * @return string Le montant formaté
 */
function formaterMontant($montant, $devise = 'FCFA') {
    if (!is_numeric($montant)) {
        return '0 ' . $devise;
    }
    
    $montantFormate = number_format(floatval($montant), 0, ',', ' ');
    return $montantFormate . ' ' . $devise;
}

/**
 * Génère une alerte Bootstrap
 * 
 * @param string $message Le message à afficher
 * @param string $type Le type d'alerte (success, danger, warning, info)
 * @return string Le HTML de l'alerte
 */
function genererAlerte($message, $type = 'info') {
    $typesValides = ['success', 'danger', 'warning', 'info'];
    $type = in_array($type, $typesValides) ? $type : 'info';
    
    $html = '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
    $html .= '<i class="fas fa-exclamation-circle me-2"></i>';
    $html .= $message;
    $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Vérifie si un utilisateur est connecté
 * 
 * @return bool True si connecté
 */
function estConnecte() {
    session_start();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Redirige vers une URL
 * 
 * @param string $url L'URL de destination
 */
function rediriger($url) {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    } else {
        echo '<script>window.location.href="' . $url . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . $url . '"></noscript>';
        exit;
    }
}

/**
 * Journalise une action dans la base de données
 * 
 * @param string $table Table modifiée
 * @param int $id ID de l'enregistrement
 * @param string $action Action effectuée (INSERT, UPDATE, DELETE)
 * @param string $utilisateur Nom de l'utilisateur
 * @param array $anciennesValeurs Anciennes valeurs (pour UPDATE)
 * @param array $nouvellesValeurs Nouvelles valeurs (pour UPDATE)
 */
function journaliserAction($table, $id, $action, $utilisateur, $anciennesValeurs = null, $nouvellesValeurs = null) {
    global $pdo;
    
    try {
        $sql = "INSERT INTO audit_log 
                (table_modifiee, id_enregistrement, action, anciennes_valeurs, nouvelles_valeurs, utilisateur) 
                VALUES (:table, :id, :action, :anciennes, :nouvelles, :utilisateur)";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':table' => $table,
            ':id' => $id,
            ':action' => $action,
            ':anciennes' => $anciennesValeurs ? json_encode($anciennesValeurs, JSON_UNESCAPED_UNICODE) : null,
            ':nouvelles' => $nouvellesValeurs ? json_encode($nouvellesValeurs, JSON_UNESCAPED_UNICODE) : null,
            ':utilisateur' => $utilisateur
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erreur de journalisation: " . $e->getMessage());
        return false;
    }
}

/**
 * Génère un mot de passe hashé sécurisé
 * 
 * @param string $motDePasse Mot de passe en clair
 * @return string Mot de passe hashé
 */
function hashMotDePasse($motDePasse) {
    return password_hash($motDePasse, PASSWORD_DEFAULT);
}

/**
 * Vérifie un mot de passe
 * 
 * @param string $motDePasse Mot de passe en clair
 * @param string $hash Hash stocké
 * @return bool True si correspond
 */
function verifierMotDePasse($motDePasse, $hash) {
    return password_verify($motDePasse, $hash);
}

/**
 * Génère un token CSRF
 * 
 * @return string Le token généré
 */
function genererTokenCSRF() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    
    return $token;
}

/**
 * Vérifie un token CSRF
 * 
 * @param string $token Token à vérifier
 * @param int $dureeValidite Durée de validité en secondes (par défaut: 3600)
 * @return bool True si valide
 */
function verifierTokenCSRF($token, $dureeValidite = 3600) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Vérifier la correspondance
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    
    // Vérifier l'expiration
    if (time() - $_SESSION['csrf_token_time'] > $dureeValidite) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    return true;
}

/**
 * Formate une durée en heures/minutes
 * 
 * @param int $minutes Durée en minutes
 * @return string Durée formatée
 */
function formaterDuree($minutes) {
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    
    $heures = floor($minutes / 60);
    $minutesRestantes = $minutes % 60;
    
    if ($minutesRestantes == 0) {
        return $heures . ' h';
    }
    
    return $heures . ' h ' . $minutesRestantes . ' min';
}

/**
 * Vérifie si une date est valide
 * 
 * @param string $date Date à vérifier
 * @param string $format Format attendu (par défaut: 'Y-m-d')
 * @return bool True si valide
 */
function estDateValide($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Vérifie si une colonne existe dans une table (schéma courant)
 *
 * @param string $table Nom de la table
 * @param string $column Nom de la colonne
 * @return bool True si la colonne existe
 */
function dbColumnExists($table, $column) {
    global $pdo;

    try {
        $sql = "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS \
                WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':db' => DB_NAME,
            ':table' => $table,
            ':column' => $column
        ]);
        $res = $stmt->fetch();
        return !empty($res) && intval($res['cnt']) > 0;
    } catch (Exception $e) {
        error_log('dbColumnExists error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Calcule le prochain numéro disponible pour une table
 * 
 * @param string $table Nom de la table
 * @param string $colonne Nom de la colonne
 * @param string $prefixe Préfixe pour le numéro
 * @return string Prochain numéro disponible
 */
function getProchainNumero($table, $colonne, $prefixe = '') {
    global $pdo;
    
    try {
        $sql = "SELECT MAX($colonne) as dernier_numero FROM $table";
        $stmt = $pdo->query($sql);
        $resultat = $stmt->fetch();
        
        $dernierNumero = $resultat['dernier_numero'];
        
        if (empty($dernierNumero)) {
            $numero = 1;
        } else {
            // Extraire le numéro
            if ($prefixe && strpos($dernierNumero, $prefixe) === 0) {
                $numero = intval(str_replace($prefixe, '', $dernierNumero)) + 1;
            } else {
                $numero = intval($dernierNumero) + 1;
            }
        }
        
        // Formater avec des zéros devant
        $numeroFormate = $prefixe . str_pad($numero, 6, '0', STR_PAD_LEFT);
        
        return $numeroFormate;
    } catch (PDOException $e) {
        error_log("Erreur lors de la génération du numéro: " . $e->getMessage());
        return $prefixe . date('YmdHis') . rand(100, 999);
    }
}

/**
 * Envoie un email (fonction simplifiée)
 * 
 * @param string $destinataire Email du destinataire
 * @param string $sujet Sujet de l'email
 * @param string $message Corps du message
 * @param string $from Email de l'expéditeur
 * @return bool True si envoyé avec succès
 */
function envoyerEmail($destinataire, $sujet, $message, $from = 'noreply@hopital.local') {
    // En production, utiliser une bibliothèque comme PHPMailer
    // Ceci est une implémentation simplifiée pour le développement
    
    $headers = "From: $from\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $messageHTML = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>$sujet</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1a73e8; color: white; padding: 20px; text-align: center; }
                .content { background: white; padding: 20px; border: 1px solid #ddd; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Hôpital Général</h2>
                </div>
                <div class='content'>
                    $message
                </div>
                <div class='footer'>
                    <p>Cet email a été envoyé automatiquement par le système de gestion hospitalière.</p>
                    <p>Ne pas répondre à cet email.</p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    return mail($destinataire, $sujet, $messageHTML, $headers);
}

/**
 * Génère un QR code pour un patient
 * 
 * @param int $idPatient ID du patient
 * @param string $nom Nom du patient
 * @param string $prenom Prénom du patient
 * @param string $numeroDossier Numéro de dossier
 * @return string Chemin vers l'image du QR code
 */
function genererQRCodePatient($idPatient, $nom, $prenom, $numeroDossier) {
    // Cette fonction nécessite la bibliothèque phpqrcode
    // Installer avec: composer require endroid/qr-code
    
    try {
        $data = json_encode([
            'id' => $idPatient,
            'nom' => $nom,
            'prenom' => $prenom,
            'dossier' => $numeroDossier,
            'timestamp' => time()
        ]);
        
        $cheminQR = 'assets/qrcodes/patient_' . $idPatient . '.png';
        $cheminComplet = $_SERVER['DOCUMENT_ROOT'] . '/' . $cheminQR;
        
        // Créer le dossier s'il n'existe pas
        if (!file_exists(dirname($cheminComplet))) {
            mkdir(dirname($cheminComplet), 0777, true);
        }
        
        // Générer le QR code (avec la bibliothèque appropriée)
        // Exemple avec Endroid QR Code:
        // $qrCode = new \Endroid\QrCode\QrCode($data);
        // $qrCode->writeFile($cheminComplet);
        
        // Pour l'instant, retourner un chemin fictif
        return $cheminQR;
        
    } catch (Exception $e) {
        error_log("Erreur génération QR code: " . $e->getMessage());
        return '';
    }
}

/**
 * Exporte des données en CSV
 * 
 * @param array $donnees Données à exporter
 * @param string $nomFichier Nom du fichier
 * @param array $colonnes Noms des colonnes
 */
function exporterCSV($donnees, $nomFichier = 'export.csv', $colonnes = []) {
    // Désactiver la mise en cache
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Forcer le téléchargement
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nomFichier . '"');
    
    // Créer le flux de sortie
    $output = fopen('php://output', 'w');
    
    // Ajouter l'en-tête UTF-8 BOM pour Excel
    fwrite($output, "\xEF\xBB\xBF");
    
    // Écrire les en-têtes de colonnes
    if (!empty($colonnes)) {
        fputcsv($output, $colonnes, ';');
    } elseif (!empty($donnees)) {
        fputcsv($output, array_keys($donnees[0]), ';');
    }
    
    // Écrire les données
    foreach ($donnees as $ligne) {
        fputcsv($output, $ligne, ';');
    }
    
    fclose($output);
    exit;
}

/**
 * Calcule les statistiques d'occupation des salles
 * 
 * @return array Statistiques d'occupation
 */
function getStatistiquesOccupation() {
    global $pdo;
    
    $sql = "
        SELECT 
            type_salle,
            COUNT(*) as total_salles,
            SUM(CASE WHEN statut = 'Occupée' THEN 1 ELSE 0 END) as salles_occupees,
            SUM(CASE WHEN statut = 'Disponible' THEN 1 ELSE 0 END) as salles_disponibles,
            SUM(CASE WHEN statut = 'En nettoyage' THEN 1 ELSE 0 END) as salles_nettoyage
        FROM salle
        GROUP BY type_salle
    ";
    
    try {
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur statistiques occupation: " . $e->getMessage());
        return [];
    }
}

/**
 * Formate un texte long avec une limite de caractères
 * 
 * @param string $texte Texte à formater
 * @param int $limite Limite de caractères
 * @param string $suite Caractères de suite (par défaut: '...')
 * @return string Texte formaté
 */
function limiterTexte($texte, $limite = 100, $suite = '...') {
    if (strlen($texte) <= $limite) {
        return $texte;
    }
    
    $texteLimite = substr($texte, 0, $limite);
    $dernierEspace = strrpos($texteLimite, ' ');
    
    if ($dernierEspace !== false) {
        $texteLimite = substr($texteLimite, 0, $dernierEspace);
    }
    
    return $texteLimite . $suite;
}

/**
 * Vérifie les permissions d'un utilisateur
 * 
 * @param string $permission Permission requise
 * @param array $permissionsUtilisateur Permissions de l'utilisateur
 * @return bool True si l'utilisateur a la permission
 */
function verifierPermission($permission, $permissionsUtilisateur) {
    if (in_array('admin', $permissionsUtilisateur)) {
        return true; // L'admin a toutes les permissions
    }
    
    return in_array($permission, $permissionsUtilisateur);
}

/**
 * Génère un mot de passe aléatoire
 * 
 * @param int $longueur Longueur du mot de passe
 * @return string Mot de passe généré
 */
function genererMotDePasseAleatoire($longueur = 12) {
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $motDePasse = '';
    
    for ($i = 0; $i < $longueur; $i++) {
        $motDePasse .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    
    return $motDePasse;
}
?>