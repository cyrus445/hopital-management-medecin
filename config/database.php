<?php
// Wrapper de connexion DB — inclut les constantes et expose un PDO global
// Minimal, non intrusif: utilise les fonctions déjà présentes dans `constants.php`
if (!file_exists(__DIR__ . '/constants.php')) {
    error_log('Fichier de configuration manquant: config/constants.php');
    die('Configuration introuvable.');
}

require_once __DIR__ . '/constants.php';

// Créer une instance PDO globale si elle n'existe pas
try {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        $pdo = getDBConnection();
    }
} catch (Exception $e) {
    error_log('Impossible de créer la connexion PDO: ' . $e->getMessage());
    // Message générique pour l'utilisateur
    die('Erreur de configuration de la base de données.');
}

// Rendre disponible via une fonction utilitaire (compatibilité)
if (!function_exists('db')) {
    function db()
    {
        global $pdo;
        return $pdo;
    }
}

?>
