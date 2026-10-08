-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: localhost    Database: gestion_hopital
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_log` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `table_modifiee` varchar(50) NOT NULL,
  `id_enregistrement` int NOT NULL,
  `action` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `anciennes_valeurs` text,
  `nouvelles_valeurs` text,
  `utilisateur` varchar(100) DEFAULT NULL,
  `date_action` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_table` (`table_modifiee`),
  KEY `idx_date` (`date_action`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,'patient',1,'INSERT',NULL,'{\"numero_dossier\":\"PAT-202512-6646\",\"nom\":\"Zmt\",\"prenom\":\"Cyrus\"}','Admission du patient','2025-12-03 23:53:08'),(2,'patient',2,'INSERT',NULL,'{\"numero_dossier\":\"PAT-202512-7069\",\"nom\":\"Zmt\",\"prenom\":\"Cyrus\"}','Admission du patient','2025-12-03 23:57:42'),(3,'consultation',1,'INSERT',NULL,'{\"id_dossier\":\"1\"}','Consultation + dossier cr├®├®s','2025-12-04 00:01:13');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consultation`
--

DROP TABLE IF EXISTS `consultation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consultation` (
  `id_consultation` int NOT NULL AUTO_INCREMENT,
  `id_patient` int NOT NULL,
  `id_medecin` int NOT NULL,
  `date_consultation` datetime DEFAULT CURRENT_TIMESTAMP,
  `motif` text NOT NULL,
  `symptomes` text,
  `diagnostique` text,
  `recommandations` text,
  `statut` enum('Planifi├®e','En cours','Termin├®e','Annul├®e') DEFAULT 'Planifi├®e',
  `prochaine_visite` date DEFAULT NULL,
  PRIMARY KEY (`id_consultation`),
  KEY `id_medecin` (`id_medecin`),
  KEY `idx_date` (`date_consultation`),
  KEY `idx_patient` (`id_patient`),
  CONSTRAINT `consultation_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`) ON DELETE CASCADE,
  CONSTRAINT `consultation_ibfk_2` FOREIGN KEY (`id_medecin`) REFERENCES `medecin` (`id_medecin`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consultation`
--

LOCK TABLES `consultation` WRITE;
/*!40000 ALTER TABLE `consultation` DISABLE KEYS */;
INSERT INTO `consultation` VALUES (1,1,2,'2025-12-04 01:01:13','por palu','fievre et vomissemebts','radiogr','doit eviter les envir salles','Termin├®e','2026-12-05');
/*!40000 ALTER TABLE `consultation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dossier_medical`
--

DROP TABLE IF EXISTS `dossier_medical`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dossier_medical` (
  `id_dossier` int NOT NULL AUTO_INCREMENT,
  `id_consultation` int NOT NULL,
  `id_patient` int NOT NULL,
  `id_medecin` int NOT NULL,
  `resume_consultation` text NOT NULL,
  `prescription` text,
  `statut_delivrance` enum('En attente','D├®livr├®e','Partielle') DEFAULT NULL,
  `statut_paiement` enum('Impay├®','Partiel','Pay├®') DEFAULT 'Impay├®',
  `montant_total` decimal(12,2) DEFAULT NULL,
  `montant_paye` decimal(12,2) DEFAULT '0.00',
  `examens_demandes` text,
  `resultats_examens` text,
  `notes_medecin` text,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_dossier`),
  UNIQUE KEY `unique_consultation` (`id_consultation`),
  KEY `id_medecin` (`id_medecin`),
  KEY `idx_patient` (`id_patient`),
  CONSTRAINT `dossier_medical_ibfk_1` FOREIGN KEY (`id_consultation`) REFERENCES `consultation` (`id_consultation`) ON DELETE CASCADE,
  CONSTRAINT `dossier_medical_ibfk_2` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`) ON DELETE CASCADE,
  CONSTRAINT `dossier_medical_ibfk_3` FOREIGN KEY (`id_medecin`) REFERENCES `medecin` (`id_medecin`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dossier_medical`
--

LOCK TABLES `dossier_medical` WRITE;
/*!40000 ALTER TABLE `dossier_medical` DISABLE KEYS */;
INSERT INTO `dossier_medical` VALUES (1,1,1,2,'Motif: por palu\nSympt├┤mes: fievre et vomissemebts\nDiagnostic: radiogr\nRecommandations: doit eviter les envir salles','doit dormi sous moustiquaire',NULL,'Impay├®',NULL,0.00,'aucun',NULL,'merci','2025-12-04 01:01:13');
/*!40000 ALTER TABLE `dossier_medical` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medecin`
--

DROP TABLE IF EXISTS `medecin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medecin` (
  `id_medecin` int NOT NULL AUTO_INCREMENT,
  `matricule` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `specialite` varchar(100) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `statut` enum('Actif','Inactif','Cong├®') DEFAULT 'Actif',
  `date_embauche` date DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_medecin`),
  UNIQUE KEY `matricule` (`matricule`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medecin`
--

LOCK TABLES `medecin` WRITE;
/*!40000 ALTER TABLE `medecin` DISABLE KEYS */;
INSERT INTO `medecin` VALUES (1,'MED001','Dupont','Jean','Cardiologie','0123456789','j.dupont@hopital.fr','Actif',NULL,'2025-12-03 20:55:29'),(2,'MED002','Martin','Marie','P├®diatrie','0234567890','m.martin@hopital.fr','Actif',NULL,'2025-12-03 20:55:29'),(3,'MED003','Bernard','Pierre','Chirurgie','0345678901','p.bernard@hopital.fr','Actif',NULL,'2025-12-03 20:55:29'),(4,'MED004','Dubois','Sophie','Radiologie','0456789012','s.dubois@hopital.fr','Actif',NULL,'2025-12-03 20:55:29');
/*!40000 ALTER TABLE `medecin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient`
--

DROP TABLE IF EXISTS `patient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient` (
  `id_patient` int NOT NULL AUTO_INCREMENT,
  `numero_dossier` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `date_naissance` date NOT NULL,
  `sexe` enum('M','F') NOT NULL,
  `adresse` text,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `groupe_sanguin` varchar(10) DEFAULT NULL,
  `allergies` text,
  `id_salle` int DEFAULT NULL,
  `statut` enum('En attente','En consultation','Hospitalis├®','Sorti') DEFAULT 'En attente',
  `date_admission` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_sortie` datetime DEFAULT NULL,
  PRIMARY KEY (`id_patient`),
  UNIQUE KEY `numero_dossier` (`numero_dossier`),
  KEY `id_salle` (`id_salle`),
  KEY `idx_nom` (`nom`),
  KEY `idx_numero` (`numero_dossier`),
  CONSTRAINT `patient_ibfk_1` FOREIGN KEY (`id_salle`) REFERENCES `salle` (`id_salle`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient`
--

LOCK TABLES `patient` WRITE;
/*!40000 ALTER TABLE `patient` DISABLE KEYS */;
INSERT INTO `patient` VALUES (1,'PAT-202512-6646','Zmt','Cyrus','2000-12-05','M','Cotonou Cadjehoun International Airport, Ave Jean-Paul II, Cotonou, Benin','0155444245','cyrus@gmail.com','B+','vvvvuuu',3,'En consultation','2025-12-04 00:53:08',NULL),(2,'PAT-202512-7069','Zmt','Cyrus','2000-12-04','M','Maison zounmatoun','0155444245','cyrus@gmail.com','A+','peniciline',1,'En attente','2025-12-04 00:57:42',NULL);
/*!40000 ALTER TABLE `patient` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salle`
--

DROP TABLE IF EXISTS `salle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salle` (
  `id_salle` int NOT NULL AUTO_INCREMENT,
  `nom_salle` varchar(100) NOT NULL,
  `type_salle` enum('Hospitalisation','Urgence','Consultation','Chirurgie') NOT NULL,
  `capacite` int DEFAULT '1',
  `statut` enum('Disponible','Occup├®e','En nettoyage') DEFAULT 'Disponible',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_salle`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salle`
--

LOCK TABLES `salle` WRITE;
/*!40000 ALTER TABLE `salle` DISABLE KEYS */;
INSERT INTO `salle` VALUES (1,'Salle 101','Consultation',1,'Disponible','2025-12-03 20:55:25'),(2,'Salle 102','Consultation',1,'Disponible','2025-12-03 20:55:25'),(3,'Salle 201','Hospitalisation',2,'Disponible','2025-12-03 20:55:25'),(4,'Salle 202','Hospitalisation',2,'Disponible','2025-12-03 20:55:25'),(5,'Urgence 1','Urgence',1,'Disponible','2025-12-03 20:55:25'),(6,'Urgence 2','Urgence',1,'Disponible','2025-12-03 20:55:25'),(7,'Chirurgie A','Chirurgie',1,'Disponible','2025-12-03 20:55:25');
/*!40000 ALTER TABLE `salle` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-04  1:14:11
