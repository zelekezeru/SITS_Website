-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: sits_website
-- ------------------------------------------------------
-- Server version	11.7.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blogs`
--

DROP TABLE IF EXISTS `blogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blogs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `content` longtext NOT NULL,
  `category` varchar(55) NOT NULL,
  `author` varchar(25) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `views` int(11) NOT NULL DEFAULT 0,
  `comments` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `visibility` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blogs`
--

LOCK TABLES `blogs` WRITE;
/*!40000 ALTER TABLE `blogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `blogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` longtext NOT NULL,
  `reply` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `visibility` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contacts_email_unique` (`email`),
  KEY `contacts_reply_foreign` (`reply`),
  CONSTRAINT `contacts_reply_foreign` FOREIGN KEY (`reply`) REFERENCES `contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts`
--

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `credit_hours` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `instructor` varchar(255) DEFAULT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `visibility` tinyint(1) NOT NULL DEFAULT 1,
  `banner` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `courses_program_id_foreign` (`program_id`),
  CONSTRAINT `courses_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `staff_no` varchar(255) NOT NULL,
  `device_employee_code` varchar(255) DEFAULT NULL,
  `full_name_en` varchar(255) NOT NULL,
  `full_name_am` varchar(255) DEFAULT NULL,
  `position_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `reporting_to_id` bigint(20) unsigned DEFAULT NULL,
  `employment_type` varchar(255) NOT NULL DEFAULT 'full_time',
  `base_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grade` varchar(255) DEFAULT NULL,
  `has_provident_fund` tinyint(1) NOT NULL DEFAULT 0,
  `statutory_exempt` tinyint(1) NOT NULL DEFAULT 0,
  `legal_daily_hour_limit` smallint(5) unsigned NOT NULL DEFAULT 8,
  `hired_at` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `attendance_exempt` tinyint(1) NOT NULL DEFAULT 0,
  `attendance_exempt_reason` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_staff_no_unique` (`staff_no`),
  KEY `employees_user_id_foreign` (`user_id`),
  CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,2,'SITS-2026-0002',NULL,'Endale Sebsebe Mekonnen',NULL,NULL,1,NULL,'full_time',0.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(2,3,'SITS-2026-0003',NULL,'Zerubbabel Zeleke',NULL,NULL,7,NULL,'full_time',15000.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(3,4,'SITS-2026-0004',NULL,'Abate Dejene Lemma',NULL,NULL,2,NULL,'full_time',28461.54,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(4,5,'SITS-2026-0005',NULL,'Abenezer Ayalew Mekonnen',NULL,NULL,2,NULL,'full_time',17000.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(5,6,'SITS-2026-0006',NULL,'Alte Agegnew Tadese',NULL,NULL,5,NULL,'full_time',11700.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(6,7,'SITS-2026-0007',NULL,'Amarech Abrham',NULL,NULL,8,NULL,'full_time',8095.97,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(7,8,'SITS-2026-0008',NULL,'Amarech Otoro',NULL,NULL,7,NULL,'full_time',5972.71,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(8,9,'SITS-2026-0009',NULL,'Azeb Buche',NULL,NULL,4,NULL,'full_time',5972.71,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(9,10,'SITS-2026-0010',NULL,'Birhanu Gelaye',NULL,NULL,4,NULL,'full_time',29671.51,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(10,11,'SITS-2026-0011',NULL,'Elfinesh Yadesa',NULL,NULL,7,NULL,'full_time',10407.97,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(11,12,'SITS-2026-0012',NULL,'Geda Tufule',NULL,NULL,7,NULL,'full_time',27871.51,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(12,13,'SITS-2026-0013',NULL,'Kalkidan Eshetu','ቃልኪዳን እሸቱ',11,8,NULL,'full_time',16257.55,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(13,14,'SITS-2026-0014',NULL,'Mesele Dawit',NULL,NULL,7,NULL,'full_time',7835.92,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(14,15,'SITS-2026-0015',NULL,'Meskerem Aseffa',NULL,NULL,7,NULL,'full_time',22838.10,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(15,16,'SITS-2026-0016',NULL,'Mesganu Petros',NULL,NULL,2,NULL,'full_time',28012.70,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(16,17,'SITS-2026-0017',NULL,'Misale Getu Ayalew',NULL,NULL,6,NULL,'full_time',20000.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(17,18,'SITS-2026-0018',NULL,'Zekariyas Chinasho',NULL,NULL,4,NULL,'full_time',22200.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(18,19,'SITS-2026-0019',NULL,'Selamawit Yared',NULL,NULL,9,NULL,'full_time',16100.61,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(19,20,'SITS-2026-0020',NULL,'Tamiru Lijalem',NULL,NULL,3,NULL,'full_time',29485.79,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(20,21,'SITS-2026-0021',NULL,'Tesfaye Gebre',NULL,NULL,4,NULL,'full_time',0.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(21,22,'SITS-2026-0022',NULL,'Tesfaye Gebre Oke',NULL,NULL,4,NULL,'full_time',21593.34,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(22,23,'SITS-2026-0023',NULL,'Yetnayet Nigatu Entele',NULL,NULL,5,NULL,'full_time',14008.45,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(23,24,'SITS-2026-0024',NULL,'Yilma Gezmu Mengesha',NULL,NULL,2,NULL,'full_time',25103.47,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(24,25,'SITS-2026-0025',NULL,'Zeleke Abisso',NULL,NULL,3,NULL,'full_time',15221.97,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(25,26,'SITS-2026-0026',NULL,'Zewude Zeleke',NULL,NULL,4,NULL,'full_time',40011.12,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location` varchar(50) NOT NULL,
  `remark` varchar(50) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `visibility` tinyint(1) NOT NULL DEFAULT 1,
  `banner` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `galleries`
--

DROP TABLE IF EXISTS `galleries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `galleries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `galleries`
--

LOCK TABLES `galleries` WRITE;
/*!40000 ALTER TABLE `galleries` DISABLE KEYS */;
/*!40000 ALTER TABLE `galleries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `libraries`
--

DROP TABLE IF EXISTS `libraries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `libraries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `description` longtext DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `visibility` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `libraries`
--

LOCK TABLES `libraries` WRITE;
/*!40000 ALTER TABLE `libraries` DISABLE KEYS */;
/*!40000 ALTER TABLE `libraries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_subscriptions`
--

DROP TABLE IF EXISTS `library_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `plan_type` varchar(255) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `start_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `jstore_user_id` bigint(20) unsigned DEFAULT NULL,
  `jstore_subscription_id` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `library_subscriptions_user_id_is_active_index` (`user_id`,`is_active`),
  KEY `library_subscriptions_expiry_date_index` (`expiry_date`),
  CONSTRAINT `library_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_subscriptions`
--

LOCK TABLES `library_subscriptions` WRITE;
/*!40000 ALTER TABLE `library_subscriptions` DISABLE KEYS */;
INSERT INTO `library_subscriptions` VALUES (1,28,'Monthly Access','monthly',150.00,'2026-06-29','2026-07-29',1,'TEST-REF-9999','Telebirr',NULL,NULL,NULL,'2026-06-28 23:36:26','2026-06-28 23:40:31');
/*!40000 ALTER TABLE `library_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000001_create_users_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2024_12_04_115737_create_blogs_table',1),(5,'2024_12_04_115801_create_contacts_table',1),(6,'2024_12_04_115901_create_courses_table',1),(7,'2024_12_04_120443_create_testimonials_table',1),(8,'2024_12_09_105317_create_libraries_table',1),(9,'2024_12_11_131148_create_admins_table',1),(10,'2024_12_12_074531_create_events_table',1),(11,'2024_12_12_125237_create_programs_table',1),(12,'2025_01_01_153258_create_permission_tables',1),(13,'2025_01_06_065244_create_trainers_table',1),(14,'2025_01_14_141542_create_subscriptions_table',1),(15,'2025_03_31_063351_create_galleries_table',1),(16,'2026_06_27_000000_fix_courses_program_id_foreign_key',1),(17,'2026_06_27_000001_create_employees_table',1),(18,'2026_06_28_000001_create_library_subscriptions_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(6,'App\\Models\\User',2),(2,'App\\Models\\User',3),(4,'App\\Models\\User',4),(4,'App\\Models\\User',5),(6,'App\\Models\\User',6),(6,'App\\Models\\User',7),(6,'App\\Models\\User',8),(6,'App\\Models\\User',9),(6,'App\\Models\\User',10),(6,'App\\Models\\User',11),(6,'App\\Models\\User',12),(6,'App\\Models\\User',13),(6,'App\\Models\\User',14),(6,'App\\Models\\User',15),(4,'App\\Models\\User',16),(6,'App\\Models\\User',17),(6,'App\\Models\\User',18),(7,'App\\Models\\User',19),(6,'App\\Models\\User',20),(6,'App\\Models\\User',21),(6,'App\\Models\\User',22),(6,'App\\Models\\User',23),(4,'App\\Models\\User',24),(6,'App\\Models\\User',25),(6,'App\\Models\\User',26),(1,'App\\Models\\User',27),(5,'App\\Models\\User',28);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programs`
--

DROP TABLE IF EXISTS `programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `description` longtext NOT NULL,
  `code` varchar(255) NOT NULL,
  `division` varchar(255) NOT NULL,
  `language` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `visibility` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programs`
--

LOCK TABLES `programs` WRITE;
/*!40000 ALTER TABLE `programs` DISABLE KEYS */;
/*!40000 ALTER TABLE `programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'SUPERADMIN','web','2026-06-28 23:30:01','2026-06-28 23:30:01'),(2,'ADMIN','web','2026-06-28 23:30:01','2026-06-28 23:30:01'),(3,'EDITOR','web','2026-06-28 23:30:01','2026-06-28 23:30:01'),(4,'TRAINER','web','2026-06-28 23:30:01','2026-06-28 23:30:01'),(5,'STUDENT','web','2026-06-28 23:30:01','2026-06-28 23:30:01'),(6,'STAFF','web','2026-06-28 23:30:01','2026-06-28 23:30:01'),(7,'LIBRARIAN','web','2026-06-28 23:30:01','2026-06-28 23:30:01'),(8,'USER','web','2026-06-28 23:30:01','2026-06-28 23:30:01');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('8xFRMnNKX6OR04Fg7oXRBtFWH1VQL0Aiqy2jmudV',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoibjc5R0ZYR1RPVGFSc0V4Z3ltUmdwc2h0cFVPMnN0cVg1RVpGOWNMcyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1782716479),('geodQjv2XI6sEiSgJvTwqPirq8Q8508g9VGJCOeb',28,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVXJhaXplQmRtTnlGTHZnVVRoY2hPaldtQnM3cm5qZE12UGRHanUxMCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9saWJyYXJ5L3BvcnRhbCI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI4O30=',1782702543),('X9wzQdpJ6favtKln4jRrDUjjxcbW2E2ZfYHBWcVn',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoid21CQXJXM09FMDU1bWxBTkdvVzlmU2FzeWtDbWsyWlhQcWZ3QnIxUCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyOToiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2NvdXJzZXMiO31zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czoyNzoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2xvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1782711898),('YYhWyknn1SZ3GLG6qFG9EKlGFiOtuRUMxSmLCLsk',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoidVE5aUhoeFdtNmVRMzJpekZxcllTWGxUTUVDcDlHVnBLU2lQeWlQSyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMToiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2Rhc2hib2FyZCI7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjMxOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1782716480);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriptions_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `testimonials`
--

DROP TABLE IF EXISTS `testimonials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `testimonials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `testimony` longtext NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `visibility` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `testimonials_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `testimonials`
--

LOCK TABLES `testimonials` WRITE;
/*!40000 ALTER TABLE `testimonials` DISABLE KEYS */;
/*!40000 ALTER TABLE `testimonials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainers`
--

DROP TABLE IF EXISTS `trainers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trainers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `position` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainers`
--

LOCK TABLES `trainers` WRITE;
/*!40000 ALTER TABLE `trainers` DISABLE KEYS */;
/*!40000 ALTER TABLE `trainers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'SITS Administrator','admin@sits.edu',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'SUPERADMIN',NULL,NULL,'2026-06-28 23:30:01','2026-06-28 23:30:01'),(2,'Endale Sebsebe Mekonnen','esebsebe@yahoo.com',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(3,'Zerubbabel Zeleke','zelekezeru@gmail.com',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'ADMIN',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(4,'Abate Dejene Lemma','abeyeenatu1980@gmail.com',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'TRAINER',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(5,'Abenezer Ayalew Mekonnen','abensew13@gmail.com',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'TRAINER',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(6,'Alte Agegnew Tadese','agegnehualte@gmail.com',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(7,'Amarech Abrham','amarech.abrham@sits.edu.et',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(8,'Amarech Otoro','amarech.otoro@sits.edu.et',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(9,'Azeb Buche','azeb.buche@sits.edu.et',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(10,'Birhanu Gelaye','birhanu.gelaye@sits.edu.et',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(11,'Elfinesh Yadesa','elfinesh.yadesa@sits.edu.et',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(12,'Geda Tufule','gedatufule9@gmail.com',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(13,'Kalkidan Eshetu','eshetukalkidan704@gmail.com',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(14,'Mesele Dawit','mesele.dawit@sits.edu.et',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(15,'Meskerem Aseffa','lebamesew@gmail.com',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(16,'Mesganu Petros','pmesge@gmail.com',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'TRAINER',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(17,'Misale Getu Ayalew','mesalegetu@yahoo.com',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(18,'Pastor Zekariyas Chinasho','zekariyas.chinasho@sits.edu.et',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(19,'Selamawit Yared','yaredselamawit@yahoo.com',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'LIBRARIAN',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(20,'Tamiru Lijalem','lijalem.tamiru@gmail.com',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(21,'Tesfaye Gebre','tesfaye.gebre@sits.edu.et',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(22,'Tesfaye Gebre Oke','tesfayegebre18@gmail.com',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(23,'Yetnayet Nigatu Entele','yetnayet.nigatu@sits.edu.et',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(24,'Yilma Gezmu Mengesha','yilmagezmu@yahoo.com',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'TRAINER',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(25,'Zeleke Abisso','abissozeleke@gmail.com',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(26,'Zewude Zeleke','zewude.zeleke@sits.edu.et',NULL,'$2y$12$JSCb2UtVCApyQLkkMNGEQOVWzbu3XGQ/YbgcZwHJkpE6iX7qs1Xfm',NULL,'STAFF',NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(27,'SITS System Admin','admin@sits.edu.et',NULL,'$2y$12$RFHQciVLw27oQBWZD6ci/.nBxS971vsloefd4wdmBL3GbI5uYww2K',NULL,NULL,NULL,NULL,'2026-06-28 23:30:02','2026-06-28 23:30:02'),(28,'Test Student','teststudent@sits.edu.et',NULL,'$2y$12$MV7YHB/umolnW6CvARbzsek.saHlmCsvYYyhQa4.9C2/QUMQaTBdy',NULL,NULL,NULL,NULL,'2026-06-28 23:35:03','2026-06-28 23:35:03');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'sits_website'
--

--
-- Dumping routines for database 'sits_website'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-30 21:22:53
