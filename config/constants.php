<?php
/**
 * Configuration de la connexion à la base de données
 * 
 * Ce fichier contient les paramètres de connexion à la base de données MySQL.
 * Les informations de connexion sont stockées dans des constantes pour plus de sécurité.
 */

// Définition des constantes de connexion
define('DB_HOST', 'localhost');
define('DB_USER', 'cyrus');
define('DB_PASS', 'avenircyrus7@');
define('DB_NAME', 'gestion_hopital');
define('DB_CHARSET', 'utf8mb4');

/**
 * Établit une connexion à la base de données
 * 
 * @return PDO L'objet de connexion PDO
 * @throws PDOException En cas d'erreur de connexion
 */
function getDBConnection() {
    try {
        // Construction du DSN (Data Source Name)
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        // Options de configuration PDO
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        // Création de la connexion PDO
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Journalisation de l'erreur (dans un environnement de production, utiliser un système de logging)
        error_log("Erreur de connexion à la base de données: " . $e->getMessage());
        
        // Affichage d'un message d'erreur générique
        die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
    }
}

/**
 * Exécute une requête SQL avec des paramètres sécurisés
 * 
 * @param string $sql La requête SQL à exécuter
 * @param array $params Les paramètres à binder (optionnel)
 * @return PDOStatement Le statement PDO exécuté
 */
function executeQuery($sql, $params = []) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare($sql);
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    return $stmt;
}

/**
 * Récupère tous les résultats d'une requête
 * 
 * @param string $sql La requête SQL
 * @param array $params Les paramètres à binder
 * @return array Les résultats sous forme de tableau associatif
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Récupère un seul résultat d'une requête
 * 
 * @param string $sql La requête SQL
 * @param array $params Les paramètres à binder
 * @return array|null Le résultat ou null si non trouvé
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Vérifie si une valeur existe déjà dans une table
 * 
 * @param string $table La table à vérifier
 * @param string $column La colonne à vérifier
 * @param mixed $value La valeur à rechercher
 * @param int $excludeId ID à exclure (pour les updates)
 * @return bool True si la valeur existe
 */
function valueExists($table, $column, $value, $excludeId = null) {
    $sql = "SELECT COUNT(*) as count FROM $table WHERE $column = :value";
    $params = [':value' => $value];
    
    if ($excludeId !== null) {
        $sql .= " AND id != :excludeId";
        $params[':excludeId'] = $excludeId;
    }
    
    $result = fetchOne($sql, $params);
    return $result['count'] > 0;
}
?>