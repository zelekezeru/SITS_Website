-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: sits_unified
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
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `causer_type` varchar(255) DEFAULT NULL,
  `causer_id` bigint(20) unsigned DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `batch_uuid` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,'payroll-config','created','App\\Models\\PayrollComponent','created',1,NULL,NULL,'{\"attributes\":{\"name\":\"Transport Allowance\",\"rate\":null,\"calc_type\":\"fixed\",\"side\":null,\"applies_to\":\"all\",\"taxable\":true,\"is_active\":true}}',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(2,'payroll-config','created','App\\Models\\PayrollComponent','created',2,NULL,NULL,'{\"attributes\":{\"name\":\"Housing Allowance\",\"rate\":null,\"calc_type\":\"fixed\",\"side\":null,\"applies_to\":\"all\",\"taxable\":true,\"is_active\":true}}',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(3,'payroll-config','created','App\\Models\\PayrollComponent','created',3,NULL,NULL,'{\"attributes\":{\"name\":\"Mobile Allowance\",\"rate\":null,\"calc_type\":\"fixed\",\"side\":null,\"applies_to\":\"all\",\"taxable\":false,\"is_active\":true}}',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(4,'payroll-config','created','App\\Models\\PayrollComponent','created',4,NULL,NULL,'{\"attributes\":{\"name\":\"Cash Allowance\",\"rate\":null,\"calc_type\":\"fixed\",\"side\":null,\"applies_to\":\"all\",\"taxable\":true,\"is_active\":true}}',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(5,'payroll-config','created','App\\Models\\PayrollComponent','created',5,NULL,NULL,'{\"attributes\":{\"name\":\"Salary Advance\",\"rate\":null,\"calc_type\":\"fixed\",\"side\":null,\"applies_to\":\"all\",\"taxable\":true,\"is_active\":true}}',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(6,'payroll-config','created','App\\Models\\PayrollComponent','created',6,NULL,NULL,'{\"attributes\":{\"name\":\"Kircha (Meat Share)\",\"rate\":null,\"calc_type\":\"fixed\",\"side\":null,\"applies_to\":\"all\",\"taxable\":true,\"is_active\":true}}',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(7,'payroll-config','created','App\\Models\\PayrollComponent','created',7,NULL,NULL,'{\"attributes\":{\"name\":\"Pension (Employee 7%)\",\"rate\":\"7.0000\",\"calc_type\":\"percent\",\"side\":\"employee\",\"applies_to\":\"pension_members\",\"taxable\":true,\"is_active\":true}}',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(8,'payroll-config','created','App\\Models\\PayrollComponent','created',8,NULL,NULL,'{\"attributes\":{\"name\":\"Pension (Employer 11%)\",\"rate\":\"11.0000\",\"calc_type\":\"percent\",\"side\":\"employer\",\"applies_to\":\"pension_members\",\"taxable\":true,\"is_active\":true}}',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(9,'payroll-config','created','App\\Models\\PayrollComponent','created',9,NULL,NULL,'{\"attributes\":{\"name\":\"Provident (Employer 1.5%)\",\"rate\":\"1.5000\",\"calc_type\":\"percent\",\"side\":\"employer\",\"applies_to\":\"pension_members\",\"taxable\":true,\"is_active\":true}}',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(10,'payroll-config','created','App\\Models\\PayrollComponent','created',10,NULL,NULL,'{\"attributes\":{\"name\":\"Provident Fund (Employee 5%)\",\"rate\":\"5.0000\",\"calc_type\":\"percent\",\"side\":\"employee\",\"applies_to\":\"pf_members\",\"taxable\":true,\"is_active\":true}}',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(11,'payroll-config','created','App\\Models\\PayrollComponent','created',11,NULL,NULL,'{\"attributes\":{\"name\":\"Provident Fund (Employer 12.5%)\",\"rate\":\"12.5000\",\"calc_type\":\"percent\",\"side\":\"employer\",\"applies_to\":\"pf_members\",\"taxable\":true,\"is_active\":true}}',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(12,'payroll','created','App\\Models\\PayrollPeriod','created',1,NULL,NULL,'{\"attributes\":{\"status\":\"open\",\"prepared_by\":null,\"approved_by\":null,\"payment_date\":\"2025-07-31T00:00:00.000000Z\"}}',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(13,'payroll','created','App\\Models\\PayrollPeriod','created',2,NULL,NULL,'{\"attributes\":{\"status\":\"open\",\"prepared_by\":null,\"approved_by\":null,\"payment_date\":\"2025-08-31T00:00:00.000000Z\"}}',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(14,'payroll','created','App\\Models\\PayrollPeriod','created',3,NULL,NULL,'{\"attributes\":{\"status\":\"open\",\"prepared_by\":null,\"approved_by\":null,\"payment_date\":\"2025-09-30T00:00:00.000000Z\"}}',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(15,'payroll','created','App\\Models\\PayrollPeriod','created',4,NULL,NULL,'{\"attributes\":{\"status\":\"open\",\"prepared_by\":null,\"approved_by\":null,\"payment_date\":\"2025-10-31T00:00:00.000000Z\"}}',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(16,'payroll','created','App\\Models\\PayrollPeriod','created',5,NULL,NULL,'{\"attributes\":{\"status\":\"open\",\"prepared_by\":null,\"approved_by\":null,\"payment_date\":\"2025-11-30T00:00:00.000000Z\"}}',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(17,'payroll','created','App\\Models\\PayrollPeriod','created',6,NULL,NULL,'{\"attributes\":{\"status\":\"open\",\"prepared_by\":null,\"approved_by\":null,\"payment_date\":\"2025-12-31T00:00:00.000000Z\"}}',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(18,'payroll','created','App\\Models\\PayrollPeriod','created',7,NULL,NULL,'{\"attributes\":{\"status\":\"open\",\"prepared_by\":null,\"approved_by\":null,\"payment_date\":\"2026-01-31T00:00:00.000000Z\"}}',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(19,'payroll','created','App\\Models\\PayrollPeriod','created',8,NULL,NULL,'{\"attributes\":{\"status\":\"open\",\"prepared_by\":null,\"approved_by\":null,\"payment_date\":\"2026-02-28T00:00:00.000000Z\"}}',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(20,'payroll','created','App\\Models\\PayrollPeriod','created',9,NULL,NULL,'{\"attributes\":{\"status\":\"open\",\"prepared_by\":null,\"approved_by\":null,\"payment_date\":\"2026-03-31T00:00:00.000000Z\"}}',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(21,'payroll','created','App\\Models\\PayrollPeriod','created',10,NULL,NULL,'{\"attributes\":{\"status\":\"open\",\"prepared_by\":null,\"approved_by\":null,\"payment_date\":\"2026-04-30T00:00:00.000000Z\"}}',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(22,'payroll','created','App\\Models\\PayrollPeriod','created',11,NULL,NULL,'{\"attributes\":{\"status\":\"open\",\"prepared_by\":null,\"approved_by\":null,\"payment_date\":\"2026-05-31T00:00:00.000000Z\"}}',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(23,'payroll','created','App\\Models\\PayrollPeriod','created',12,NULL,NULL,'{\"attributes\":{\"status\":\"open\",\"prepared_by\":null,\"approved_by\":null,\"payment_date\":\"2026-06-30T00:00:00.000000Z\"}}',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35');
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Table structure for table `ai_analyses`
--

DROP TABLE IF EXISTS `ai_analyses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_analyses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `narrative_report_id` bigint(20) unsigned NOT NULL,
  `provider` varchar(255) NOT NULL,
  `model` varchar(255) DEFAULT NULL,
  `summary_en` text DEFAULT NULL,
  `summary_am` text DEFAULT NULL,
  `kpi_scores_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`kpi_scores_json`)),
  `sentiment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sentiment`)),
  `risk_flags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`risk_flags`)),
  `human_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `confirmed_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_analyses_narrative_report_id_foreign` (`narrative_report_id`),
  KEY `ai_analyses_confirmed_by_id_foreign` (`confirmed_by_id`),
  CONSTRAINT `ai_analyses_confirmed_by_id_foreign` FOREIGN KEY (`confirmed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_analyses_narrative_report_id_foreign` FOREIGN KEY (`narrative_report_id`) REFERENCES `narrative_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_analyses`
--

LOCK TABLES `ai_analyses` WRITE;
/*!40000 ALTER TABLE `ai_analyses` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_analyses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_import_rows`
--

DROP TABLE IF EXISTS `attendance_import_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_import_rows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attendance_import_id` bigint(20) unsigned NOT NULL,
  `device_employee_code` varchar(255) NOT NULL,
  `device_name` varchar(255) NOT NULL,
  `device_department` varchar(255) DEFAULT NULL,
  `work_duration_standard_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `work_duration_actual_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `late_times` int(10) unsigned NOT NULL DEFAULT 0,
  `late_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `leave_early_times` int(10) unsigned NOT NULL DEFAULT 0,
  `leave_early_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `overtime_normal_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `overtime_special_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `lack_times` int(10) unsigned NOT NULL DEFAULT 0,
  `lack_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `attendance_days_standard` int(10) unsigned NOT NULL DEFAULT 0,
  `attendance_days_actual` int(10) unsigned NOT NULL DEFAULT 0,
  `absent_days` int(10) unsigned NOT NULL DEFAULT 0,
  `remarks` varchar(255) DEFAULT NULL,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `match_status` varchar(255) NOT NULL DEFAULT 'unmatched',
  `match_method` varchar(255) DEFAULT NULL,
  `match_confidence` decimal(5,2) DEFAULT NULL,
  `is_excluded` tinyint(1) NOT NULL DEFAULT 0,
  `exclusion_reason` varchar(255) DEFAULT NULL,
  `suggested_permitted_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_import_rows_attendance_import_id_foreign` (`attendance_import_id`),
  KEY `attendance_import_rows_employee_id_foreign` (`employee_id`),
  CONSTRAINT `attendance_import_rows_attendance_import_id_foreign` FOREIGN KEY (`attendance_import_id`) REFERENCES `attendance_imports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_import_rows_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_import_rows`
--

LOCK TABLES `attendance_import_rows` WRITE;
/*!40000 ALTER TABLE `attendance_import_rows` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_import_rows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_imports`
--

DROP TABLE IF EXISTS `attendance_imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_imports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payroll_period_id` bigint(20) unsigned NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending_review',
  `matched_count` int(10) unsigned NOT NULL DEFAULT 0,
  `ambiguous_count` int(10) unsigned NOT NULL DEFAULT 0,
  `unmatched_count` int(10) unsigned NOT NULL DEFAULT 0,
  `excluded_count` int(10) unsigned NOT NULL DEFAULT 0,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_imports_payroll_period_id_foreign` (`payroll_period_id`),
  KEY `attendance_imports_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `attendance_imports_payroll_period_id_foreign` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_imports_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_imports`
--

LOCK TABLES `attendance_imports` WRITE;
/*!40000 ALTER TABLE `attendance_imports` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_imports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_permissions`
--

DROP TABLE IF EXISTS `attendance_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `payroll_period_id` bigint(20) unsigned NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `reason` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `mass_permission_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_permissions_employee_id_foreign` (`employee_id`),
  KEY `attendance_permissions_created_by_foreign` (`created_by`),
  KEY `attendance_permissions_approved_by_foreign` (`approved_by`),
  KEY `attendance_permissions_payroll_period_id_employee_id_index` (`payroll_period_id`,`employee_id`),
  KEY `attendance_permissions_mass_permission_id_foreign` (`mass_permission_id`),
  CONSTRAINT `attendance_permissions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_permissions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_permissions_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_permissions_mass_permission_id_foreign` FOREIGN KEY (`mass_permission_id`) REFERENCES `mass_permissions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_permissions_payroll_period_id_foreign` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_permissions`
--

LOCK TABLES `attendance_permissions` WRITE;
/*!40000 ALTER TABLE `attendance_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_records`
--

DROP TABLE IF EXISTS `attendance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `payroll_period_id` bigint(20) unsigned NOT NULL,
  `source` varchar(255) NOT NULL DEFAULT 'manual',
  `work_hours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `late_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `absent_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `permitted_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `overtime_normal` decimal(8,2) NOT NULL DEFAULT 0.00,
  `ot_night` decimal(8,2) NOT NULL DEFAULT 0.00,
  `ot_rest` decimal(8,2) NOT NULL DEFAULT 0.00,
  `ot_holiday` decimal(8,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'raw',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_records_employee_id_payroll_period_id_unique` (`employee_id`,`payroll_period_id`),
  KEY `attendance_records_payroll_period_id_foreign` (`payroll_period_id`),
  CONSTRAINT `attendance_records_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_records_payroll_period_id_foreign` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_records`
--

LOCK TABLES `attendance_records` WRITE;
/*!40000 ALTER TABLE `attendance_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_records` ENABLE KEYS */;
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
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('admin@farmsmassa.com|127.0.0.1','i:1;',1782843344),('admin@farmsmassa.com|127.0.0.1:timer','i:1782843344;',1782843344),('admin@gmail.com|127.0.0.1','i:2;',1782843349),('admin@gmail.com|127.0.0.1:timer','i:1782843349;',1782843349),('spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:49:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:21:\"manage strategic plan\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:19:\"view strategic plan\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;i:4;i:8;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:16:\"manage employees\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:14:\"view employees\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:5;i:4;i:7;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:25:\"view department employees\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:6;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:23:\"manage job descriptions\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:9:\"crud kpis\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:12:\"approve kpis\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:6;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:12:\"confirm kpis\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:9:\"view kpis\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:12:\"create tasks\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;i:5;i:8;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:16:\"manage all tasks\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:23:\"manage department tasks\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:6;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:16:\"manage own tasks\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:5;i:2;i:8;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:19:\"manage deliverables\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:6;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:13:\"comment tasks\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;i:5;i:7;i:6;i:8;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:15:\"run evaluations\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:17:\"score evaluations\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:3;i:2;i:6;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:20:\"finalize evaluations\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:20:\"view own evaluations\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:5;i:2;i:8;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:14:\"manage payroll\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:5;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:19:\"validate attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:13:\"view payslips\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:5;i:4;i:8;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:15:\"edit tax config\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:15:\"prepare payroll\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:4;i:2;i:5;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:17:\"manage deductions\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:4;i:2;i:5;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:14:\"submit payroll\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:4;i:2;i:5;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:15:\"approve payroll\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:14:\"export payroll\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:4;i:2;i:5;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:17:\"configure payroll\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:17:\"upload attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:4;i:2;i:5;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:18:\"approve attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:28:\"create attendance permission\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:29:\"approve attendance permission\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:20:\"recommend increments\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:6;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:18:\"approve increments\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:12:\"manage users\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:13:\"approve users\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:15:\"reset passwords\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:22:\"view executive reports\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:23:\"view department reports\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:6;i:4;i:7;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:14:\"export reports\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:6;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:21:\"manage conduct issues\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:21:\"create conduct issues\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:6;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:25:\"manage department conduct\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:3;i:2;i:4;i:3;i:6;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:24:\"manage conduct decisions\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:18:\"manage closed days\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:22:\"create mass permission\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:48;a:4:{s:1:\"a\";i:49;s:1:\"b\";s:23:\"approve mass permission\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}}s:5:\"roles\";a:8:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:23:\"President / Super Admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:14:\"Vice President\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:20:\"Dean of the Seminary\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:15:\"Finance Officer\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:8;s:1:\"b\";s:8:\"Employee\";s:1:\"c\";s:3:\"web\";}i:5;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:19:\"Operational Manager\";s:1:\"c\";s:3:\"web\";}i:6;a:3:{s:1:\"a\";i:7;s:1:\"b\";s:9:\"Registrar\";s:1:\"c\";s:3:\"web\";}i:7;a:3:{s:1:\"a\";i:6;s:1:\"b\";s:15:\"Department Head\";s:1:\"c\";s:3:\"web\";}}}',1782815511);
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
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
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
-- Table structure for table `campuses`
--

DROP TABLE IF EXISTS `campuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_en` varchar(255) NOT NULL,
  `name_am` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campuses`
--

LOCK TABLES `campuses` WRITE;
/*!40000 ALTER TABLE `campuses` DISABLE KEYS */;
INSERT INTO `campuses` VALUES (1,'Hawassa Main Campus','ሐዋሳ ዋና ካምፓስ','Hawassa',1,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(2,'Wolayta Campus (SBCE)','ወላይታ ካምፓስ','Wolayta Sodo',1,'2026-06-29 07:29:28','2026-06-29 07:29:28');
/*!40000 ALTER TABLE `campuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `closed_day_mass_permission`
--

DROP TABLE IF EXISTS `closed_day_mass_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `closed_day_mass_permission` (
  `mass_permission_id` bigint(20) unsigned NOT NULL,
  `closed_day_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`mass_permission_id`,`closed_day_id`),
  KEY `closed_day_mass_permission_closed_day_id_foreign` (`closed_day_id`),
  CONSTRAINT `closed_day_mass_permission_closed_day_id_foreign` FOREIGN KEY (`closed_day_id`) REFERENCES `closed_days` (`id`) ON DELETE CASCADE,
  CONSTRAINT `closed_day_mass_permission_mass_permission_id_foreign` FOREIGN KEY (`mass_permission_id`) REFERENCES `mass_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `closed_day_mass_permission`
--

LOCK TABLES `closed_day_mass_permission` WRITE;
/*!40000 ALTER TABLE `closed_day_mass_permission` DISABLE KEYS */;
/*!40000 ALTER TABLE `closed_day_mass_permission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `closed_days`
--

DROP TABLE IF EXISTS `closed_days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `closed_days` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'holiday',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `closed_days_date_unique` (`date`),
  KEY `closed_days_created_by_foreign` (`created_by`),
  KEY `closed_days_is_active_date_index` (`is_active`,`date`),
  CONSTRAINT `closed_days_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `closed_days`
--

LOCK TABLES `closed_days` WRITE;
/*!40000 ALTER TABLE `closed_days` DISABLE KEYS */;
/*!40000 ALTER TABLE `closed_days` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `commentable_type` varchar(255) NOT NULL,
  `commentable_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comments_commentable_type_commentable_id_index` (`commentable_type`,`commentable_id`),
  KEY `comments_user_id_foreign` (`user_id`),
  KEY `comments_parent_id_foreign` (`parent_id`),
  CONSTRAINT `comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conduct_decisions`
--

DROP TABLE IF EXISTS `conduct_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conduct_decisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conduct_issue_id` bigint(20) unsigned NOT NULL,
  `decided_by` bigint(20) unsigned DEFAULT NULL,
  `decision` varchar(255) NOT NULL,
  `decision_notes_en` text DEFAULT NULL,
  `decision_notes_am` text DEFAULT NULL,
  `effective_date` datetime NOT NULL,
  `decided_at` datetime NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `appealed_by` bigint(20) unsigned DEFAULT NULL,
  `appeal_date` datetime DEFAULT NULL,
  `appeal_notes` text DEFAULT NULL,
  `overturned_by` bigint(20) unsigned DEFAULT NULL,
  `overturned_at` datetime DEFAULT NULL,
  `overturn_reason` text DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conduct_decisions_decided_by_foreign` (`decided_by`),
  KEY `conduct_decisions_appealed_by_foreign` (`appealed_by`),
  KEY `conduct_decisions_overturned_by_foreign` (`overturned_by`),
  KEY `conduct_decisions_conduct_issue_id_index` (`conduct_issue_id`),
  KEY `conduct_decisions_decision_index` (`decision`),
  KEY `conduct_decisions_status_index` (`status`),
  CONSTRAINT `conduct_decisions_appealed_by_foreign` FOREIGN KEY (`appealed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conduct_decisions_conduct_issue_id_foreign` FOREIGN KEY (`conduct_issue_id`) REFERENCES `conduct_issues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conduct_decisions_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conduct_decisions_overturned_by_foreign` FOREIGN KEY (`overturned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conduct_decisions`
--

LOCK TABLES `conduct_decisions` WRITE;
/*!40000 ALTER TABLE `conduct_decisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `conduct_decisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conduct_issues`
--

DROP TABLE IF EXISTS `conduct_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conduct_issues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `issue_type` varchar(255) NOT NULL,
  `severity` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `description_en` text NOT NULL,
  `description_am` text DEFAULT NULL,
  `justification` text DEFAULT NULL,
  `incident_date` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `witnesses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`witnesses`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conduct_issues_created_by_foreign` (`created_by`),
  KEY `conduct_issues_approved_by_foreign` (`approved_by`),
  KEY `conduct_issues_rejected_by_foreign` (`rejected_by`),
  KEY `conduct_issues_employee_id_index` (`employee_id`),
  KEY `conduct_issues_status_index` (`status`),
  KEY `conduct_issues_severity_index` (`severity`),
  KEY `conduct_issues_issue_type_index` (`issue_type`),
  CONSTRAINT `conduct_issues_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conduct_issues_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conduct_issues_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conduct_issues_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conduct_issues`
--

LOCK TABLES `conduct_issues` WRITE;
/*!40000 ALTER TABLE `conduct_issues` DISABLE KEYS */;
/*!40000 ALTER TABLE `conduct_issues` ENABLE KEYS */;
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
-- Table structure for table `day_task`
--

DROP TABLE IF EXISTS `day_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `day_task` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `day_id` bigint(20) unsigned NOT NULL,
  `task_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `day_task_day_id_task_id_unique` (`day_id`,`task_id`),
  KEY `day_task_task_id_foreign` (`task_id`),
  CONSTRAINT `day_task_day_id_foreign` FOREIGN KEY (`day_id`) REFERENCES `days` (`id`) ON DELETE CASCADE,
  CONSTRAINT `day_task_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `day_task`
--

LOCK TABLES `day_task` WRITE;
/*!40000 ALTER TABLE `day_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `day_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `days`
--

DROP TABLE IF EXISTS `days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `days` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `fortnight_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `days_date_unique` (`date`),
  KEY `days_fortnight_id_foreign` (`fortnight_id`),
  CONSTRAINT `days_fortnight_id_foreign` FOREIGN KEY (`fortnight_id`) REFERENCES `fortnights` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=366 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `days`
--

LOCK TABLES `days` WRITE;
/*!40000 ALTER TABLE `days` DISABLE KEYS */;
INSERT INTO `days` VALUES (1,'2025-07-01',1,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(2,'2025-07-02',1,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(3,'2025-07-03',1,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(4,'2025-07-04',1,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(5,'2025-07-05',1,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(6,'2025-07-06',1,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(7,'2025-07-07',1,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(8,'2025-07-08',1,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(9,'2025-07-09',1,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(10,'2025-07-10',1,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(11,'2025-07-11',1,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(12,'2025-07-12',1,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(13,'2025-07-13',1,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(14,'2025-07-14',1,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(15,'2025-07-15',2,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(16,'2025-07-16',2,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(17,'2025-07-17',2,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(18,'2025-07-18',2,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(19,'2025-07-19',2,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(20,'2025-07-20',2,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(21,'2025-07-21',2,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(22,'2025-07-22',2,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(23,'2025-07-23',2,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(24,'2025-07-24',2,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(25,'2025-07-25',2,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(26,'2025-07-26',2,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(27,'2025-07-27',2,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(28,'2025-07-28',2,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(29,'2025-07-29',3,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(30,'2025-07-30',3,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(31,'2025-07-31',3,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(32,'2025-08-01',3,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(33,'2025-08-02',3,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(34,'2025-08-03',3,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(35,'2025-08-04',3,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(36,'2025-08-05',3,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(37,'2025-08-06',3,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(38,'2025-08-07',3,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(39,'2025-08-08',3,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(40,'2025-08-09',3,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(41,'2025-08-10',3,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(42,'2025-08-11',3,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(43,'2025-08-12',4,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(44,'2025-08-13',4,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(45,'2025-08-14',4,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(46,'2025-08-15',4,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(47,'2025-08-16',4,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(48,'2025-08-17',4,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(49,'2025-08-18',4,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(50,'2025-08-19',4,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(51,'2025-08-20',4,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(52,'2025-08-21',4,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(53,'2025-08-22',4,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(54,'2025-08-23',4,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(55,'2025-08-24',4,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(56,'2025-08-25',4,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(57,'2025-08-26',5,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(58,'2025-08-27',5,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(59,'2025-08-28',5,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(60,'2025-08-29',5,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(61,'2025-08-30',5,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(62,'2025-08-31',5,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(63,'2025-09-01',5,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(64,'2025-09-02',5,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(65,'2025-09-03',5,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(66,'2025-09-04',5,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(67,'2025-09-05',5,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(68,'2025-09-06',5,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(69,'2025-09-07',5,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(70,'2025-09-08',5,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(71,'2025-09-09',6,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(72,'2025-09-10',6,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(73,'2025-09-11',6,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(74,'2025-09-12',6,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(75,'2025-09-13',6,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(76,'2025-09-14',6,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(77,'2025-09-15',6,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(78,'2025-09-16',6,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(79,'2025-09-17',6,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(80,'2025-09-18',6,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(81,'2025-09-19',6,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(82,'2025-09-20',6,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(83,'2025-09-21',6,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(84,'2025-09-22',6,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(85,'2025-09-23',7,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(86,'2025-09-24',7,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(87,'2025-09-25',7,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(88,'2025-09-26',7,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(89,'2025-09-27',7,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(90,'2025-09-28',7,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(91,'2025-09-29',7,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(92,'2025-09-30',7,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(93,'2025-10-01',8,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(94,'2025-10-02',8,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(95,'2025-10-03',8,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(96,'2025-10-04',8,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(97,'2025-10-05',8,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(98,'2025-10-06',8,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(99,'2025-10-07',8,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(100,'2025-10-08',8,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(101,'2025-10-09',8,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(102,'2025-10-10',8,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(103,'2025-10-11',8,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(104,'2025-10-12',8,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(105,'2025-10-13',8,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(106,'2025-10-14',8,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(107,'2025-10-15',9,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(108,'2025-10-16',9,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(109,'2025-10-17',9,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(110,'2025-10-18',9,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(111,'2025-10-19',9,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(112,'2025-10-20',9,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(113,'2025-10-21',9,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(114,'2025-10-22',9,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(115,'2025-10-23',9,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(116,'2025-10-24',9,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(117,'2025-10-25',9,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(118,'2025-10-26',9,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(119,'2025-10-27',9,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(120,'2025-10-28',9,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(121,'2025-10-29',10,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(122,'2025-10-30',10,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(123,'2025-10-31',10,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(124,'2025-11-01',10,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(125,'2025-11-02',10,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(126,'2025-11-03',10,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(127,'2025-11-04',10,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(128,'2025-11-05',10,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(129,'2025-11-06',10,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(130,'2025-11-07',10,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(131,'2025-11-08',10,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(132,'2025-11-09',10,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(133,'2025-11-10',10,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(134,'2025-11-11',10,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(135,'2025-11-12',11,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(136,'2025-11-13',11,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(137,'2025-11-14',11,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(138,'2025-11-15',11,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(139,'2025-11-16',11,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(140,'2025-11-17',11,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(141,'2025-11-18',11,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(142,'2025-11-19',11,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(143,'2025-11-20',11,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(144,'2025-11-21',11,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(145,'2025-11-22',11,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(146,'2025-11-23',11,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(147,'2025-11-24',11,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(148,'2025-11-25',11,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(149,'2025-11-26',12,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(150,'2025-11-27',12,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(151,'2025-11-28',12,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(152,'2025-11-29',12,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(153,'2025-11-30',12,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(154,'2025-12-01',12,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(155,'2025-12-02',12,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(156,'2025-12-03',12,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(157,'2025-12-04',12,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(158,'2025-12-05',12,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(159,'2025-12-06',12,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(160,'2025-12-07',12,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(161,'2025-12-08',12,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(162,'2025-12-09',12,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(163,'2025-12-10',13,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(164,'2025-12-11',13,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(165,'2025-12-12',13,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(166,'2025-12-13',13,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(167,'2025-12-14',13,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(168,'2025-12-15',13,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(169,'2025-12-16',13,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(170,'2025-12-17',13,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(171,'2025-12-18',13,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(172,'2025-12-19',13,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(173,'2025-12-20',13,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(174,'2025-12-21',13,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(175,'2025-12-22',13,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(176,'2025-12-23',13,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(177,'2025-12-24',14,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(178,'2025-12-25',14,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(179,'2025-12-26',14,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(180,'2025-12-27',14,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(181,'2025-12-28',14,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(182,'2025-12-29',14,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(183,'2025-12-30',14,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(184,'2025-12-31',14,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(185,'2026-01-01',15,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(186,'2026-01-02',15,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(187,'2026-01-03',15,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(188,'2026-01-04',15,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(189,'2026-01-05',15,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(190,'2026-01-06',15,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(191,'2026-01-07',15,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(192,'2026-01-08',15,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(193,'2026-01-09',15,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(194,'2026-01-10',15,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(195,'2026-01-11',15,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(196,'2026-01-12',15,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(197,'2026-01-13',15,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(198,'2026-01-14',15,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(199,'2026-01-15',16,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(200,'2026-01-16',16,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(201,'2026-01-17',16,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(202,'2026-01-18',16,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(203,'2026-01-19',16,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(204,'2026-01-20',16,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(205,'2026-01-21',16,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(206,'2026-01-22',16,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(207,'2026-01-23',16,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(208,'2026-01-24',16,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(209,'2026-01-25',16,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(210,'2026-01-26',16,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(211,'2026-01-27',16,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(212,'2026-01-28',16,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(213,'2026-01-29',17,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(214,'2026-01-30',17,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(215,'2026-01-31',17,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(216,'2026-02-01',17,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(217,'2026-02-02',17,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(218,'2026-02-03',17,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(219,'2026-02-04',17,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(220,'2026-02-05',17,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(221,'2026-02-06',17,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(222,'2026-02-07',17,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(223,'2026-02-08',17,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(224,'2026-02-09',17,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(225,'2026-02-10',17,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(226,'2026-02-11',17,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(227,'2026-02-12',18,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(228,'2026-02-13',18,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(229,'2026-02-14',18,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(230,'2026-02-15',18,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(231,'2026-02-16',18,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(232,'2026-02-17',18,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(233,'2026-02-18',18,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(234,'2026-02-19',18,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(235,'2026-02-20',18,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(236,'2026-02-21',18,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(237,'2026-02-22',18,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(238,'2026-02-23',18,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(239,'2026-02-24',18,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(240,'2026-02-25',18,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(241,'2026-02-26',19,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(242,'2026-02-27',19,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(243,'2026-02-28',19,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(244,'2026-03-01',19,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(245,'2026-03-02',19,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(246,'2026-03-03',19,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(247,'2026-03-04',19,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(248,'2026-03-05',19,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(249,'2026-03-06',19,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(250,'2026-03-07',19,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(251,'2026-03-08',19,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(252,'2026-03-09',19,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(253,'2026-03-10',19,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(254,'2026-03-11',19,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(255,'2026-03-12',20,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(256,'2026-03-13',20,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(257,'2026-03-14',20,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(258,'2026-03-15',20,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(259,'2026-03-16',20,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(260,'2026-03-17',20,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(261,'2026-03-18',20,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(262,'2026-03-19',20,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(263,'2026-03-20',20,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(264,'2026-03-21',20,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(265,'2026-03-22',20,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(266,'2026-03-23',20,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(267,'2026-03-24',20,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(268,'2026-03-25',20,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(269,'2026-03-26',21,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(270,'2026-03-27',21,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(271,'2026-03-28',21,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(272,'2026-03-29',21,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(273,'2026-03-30',21,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(274,'2026-03-31',21,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(275,'2026-04-01',22,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(276,'2026-04-02',22,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(277,'2026-04-03',22,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(278,'2026-04-04',22,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(279,'2026-04-05',22,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(280,'2026-04-06',22,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(281,'2026-04-07',22,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(282,'2026-04-08',22,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(283,'2026-04-09',22,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(284,'2026-04-10',22,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(285,'2026-04-11',22,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(286,'2026-04-12',22,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(287,'2026-04-13',22,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(288,'2026-04-14',22,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(289,'2026-04-15',23,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(290,'2026-04-16',23,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(291,'2026-04-17',23,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(292,'2026-04-18',23,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(293,'2026-04-19',23,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(294,'2026-04-20',23,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(295,'2026-04-21',23,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(296,'2026-04-22',23,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(297,'2026-04-23',23,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(298,'2026-04-24',23,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(299,'2026-04-25',23,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(300,'2026-04-26',23,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(301,'2026-04-27',23,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(302,'2026-04-28',23,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(303,'2026-04-29',24,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(304,'2026-04-30',24,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(305,'2026-05-01',24,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(306,'2026-05-02',24,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(307,'2026-05-03',24,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(308,'2026-05-04',24,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(309,'2026-05-05',24,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(310,'2026-05-06',24,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(311,'2026-05-07',24,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(312,'2026-05-08',24,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(313,'2026-05-09',24,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(314,'2026-05-10',24,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(315,'2026-05-11',24,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(316,'2026-05-12',24,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(317,'2026-05-13',25,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(318,'2026-05-14',25,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(319,'2026-05-15',25,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(320,'2026-05-16',25,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(321,'2026-05-17',25,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(322,'2026-05-18',25,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(323,'2026-05-19',25,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(324,'2026-05-20',25,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(325,'2026-05-21',25,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(326,'2026-05-22',25,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(327,'2026-05-23',25,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(328,'2026-05-24',25,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(329,'2026-05-25',25,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(330,'2026-05-26',25,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(331,'2026-05-27',26,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(332,'2026-05-28',26,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(333,'2026-05-29',26,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(334,'2026-05-30',26,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(335,'2026-05-31',26,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(336,'2026-06-01',26,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(337,'2026-06-02',26,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(338,'2026-06-03',26,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(339,'2026-06-04',26,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(340,'2026-06-05',26,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(341,'2026-06-06',26,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(342,'2026-06-07',26,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(343,'2026-06-08',26,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(344,'2026-06-09',26,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(345,'2026-06-10',27,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(346,'2026-06-11',27,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(347,'2026-06-12',27,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(348,'2026-06-13',27,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(349,'2026-06-14',27,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(350,'2026-06-15',27,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(351,'2026-06-16',27,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(352,'2026-06-17',27,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(353,'2026-06-18',27,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(354,'2026-06-19',27,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(355,'2026-06-20',27,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(356,'2026-06-21',27,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(357,'2026-06-22',27,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(358,'2026-06-23',27,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(359,'2026-06-24',28,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(360,'2026-06-25',28,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(361,'2026-06-26',28,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(362,'2026-06-27',28,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(363,'2026-06-28',28,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(364,'2026-06-29',28,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(365,'2026-06-30',28,'2026-06-29 07:29:35','2026-06-29 07:29:35');
/*!40000 ALTER TABLE `days` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deliverables`
--

DROP TABLE IF EXISTS `deliverables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deliverables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fortnight_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `deadline` date DEFAULT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deliverables_fortnight_id_foreign` (`fortnight_id`),
  KEY `deliverables_user_id_foreign` (`user_id`),
  KEY `deliverables_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `deliverables_fortnight_id_foreign` FOREIGN KEY (`fortnight_id`) REFERENCES `fortnights` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deliverables_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deliverables_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deliverables`
--

LOCK TABLES `deliverables` WRITE;
/*!40000 ALTER TABLE `deliverables` DISABLE KEYS */;
/*!40000 ALTER TABLE `deliverables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_target`
--

DROP TABLE IF EXISTS `department_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department_target` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint(20) unsigned NOT NULL,
  `target_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `department_target_department_id_target_id_unique` (`department_id`,`target_id`),
  KEY `department_target_target_id_foreign` (`target_id`),
  CONSTRAINT `department_target_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `department_target_target_id_foreign` FOREIGN KEY (`target_id`) REFERENCES `targets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_target`
--

LOCK TABLES `department_target` WRITE;
/*!40000 ALTER TABLE `department_target` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_target` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_task`
--

DROP TABLE IF EXISTS `department_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department_task` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint(20) unsigned NOT NULL,
  `task_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `department_task_department_id_task_id_unique` (`department_id`,`task_id`),
  KEY `department_task_task_id_foreign` (`task_id`),
  CONSTRAINT `department_task_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `department_task_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_task`
--

LOCK TABLES `department_task` WRITE;
/*!40000 ALTER TABLE `department_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_en` varchar(255) NOT NULL,
  `name_am` varchar(255) DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `campus_id` bigint(20) unsigned DEFAULT NULL,
  `head_user_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `departments_parent_id_foreign` (`parent_id`),
  KEY `departments_campus_id_foreign` (`campus_id`),
  KEY `departments_head_user_id_foreign` (`head_user_id`),
  CONSTRAINT `departments_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departments_head_user_id_foreign` FOREIGN KEY (`head_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Office of the President','የፕሬዚደንት ጽሕፈት ቤት',NULL,1,NULL,1,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(2,'Academic Affairs','የትምህርት ክፍል',NULL,1,NULL,1,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(3,'Open Distance & eLearning (ODeL)','የክፍት ርቀትና ኢ-ለርኒንግ (ኦዲኤል)',NULL,1,NULL,1,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(4,'Satellite & Learning Sites','የሳተላይትና የመማሪያ ማእከላት',NULL,1,NULL,1,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(5,'Registrar & Alumni',' መዝገብና የቀድሞ ተማሪዎች',NULL,1,NULL,1,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(6,'Student Affairs','የተማሪዎች ጉዳይ',NULL,1,NULL,1,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(7,'Operations & Administration','የአሠራርና አስተዳደር',NULL,1,NULL,1,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(8,'Finance','ፋይናንስ',NULL,1,NULL,1,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(9,'Library','ቤተ-መጻሕፍት',NULL,1,NULL,1,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(10,'Wolayta Campus','ወላይታ ካምፓስ',NULL,2,NULL,1,'2026-06-29 07:29:28','2026-06-29 07:29:28');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `documentable_type` varchar(255) NOT NULL,
  `documentable_id` bigint(20) unsigned NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `mime` varchar(255) DEFAULT NULL,
  `size` bigint(20) unsigned DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_documentable_type_documentable_id_index` (`documentable_type`,`documentable_id`),
  KEY `documents_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_kpi`
--

DROP TABLE IF EXISTS `employee_kpi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_kpi` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `kpi_id` bigint(20) unsigned NOT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_kpi_employee_id_kpi_id_unique` (`employee_id`,`kpi_id`),
  KEY `employee_kpi_kpi_id_foreign` (`kpi_id`),
  CONSTRAINT `employee_kpi_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_kpi_kpi_id_foreign` FOREIGN KEY (`kpi_id`) REFERENCES `kpis` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_kpi`
--

LOCK TABLES `employee_kpi` WRITE;
/*!40000 ALTER TABLE `employee_kpi` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_kpi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_status_changes`
--

DROP TABLE IF EXISTS `employee_status_changes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_status_changes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `from_status` varchar(255) NOT NULL,
  `to_status` varchar(255) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `effective_date` datetime NOT NULL,
  `changed_at` datetime NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_status_changes_changed_by_foreign` (`changed_by`),
  KEY `employee_status_changes_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `employee_status_changes_employee_id_index` (`employee_id`),
  KEY `employee_status_changes_from_status_index` (`from_status`),
  KEY `employee_status_changes_to_status_index` (`to_status`),
  CONSTRAINT `employee_status_changes_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_status_changes_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_status_changes`
--

LOCK TABLES `employee_status_changes` WRITE;
/*!40000 ALTER TABLE `employee_status_changes` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_status_changes` ENABLE KEYS */;
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
  UNIQUE KEY `employees_device_employee_code_unique` (`device_employee_code`),
  KEY `employees_user_id_foreign` (`user_id`),
  KEY `employees_position_id_foreign` (`position_id`),
  KEY `employees_department_id_foreign` (`department_id`),
  KEY `employees_reporting_to_id_foreign` (`reporting_to_id`),
  KEY `employees_status_index` (`status`),
  CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_reporting_to_id_foreign` FOREIGN KEY (`reporting_to_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,2,'SITS-2026-0002',NULL,'Endale Sebsebe Mekonnen',NULL,NULL,1,NULL,'full_time',54934.20,'G13-L5',1,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:29','2026-06-29 07:29:35'),(2,3,'SITS-2026-0003',NULL,'Zerubbabel Zeleke',NULL,NULL,7,NULL,'full_time',15000.00,NULL,0,1,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:29','2026-06-29 07:29:35'),(3,4,'SITS-2026-0004',NULL,'Abate Dejene Lemma',NULL,NULL,2,NULL,'full_time',0.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:29','2026-06-29 07:29:29'),(4,5,'SITS-2026-0005',NULL,'Abenezer Ayalew Mekonnen',NULL,NULL,2,NULL,'full_time',17000.00,NULL,0,1,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:29','2026-06-29 07:29:35'),(5,6,'SITS-2026-0006',NULL,'Alte Agegnew Tadese',NULL,NULL,5,NULL,'full_time',11700.00,NULL,0,1,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:29','2026-06-29 07:29:35'),(6,7,'SITS-2026-0007',NULL,'Amarech Abrham',NULL,NULL,8,NULL,'full_time',0.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:30','2026-06-29 07:29:30'),(7,8,'SITS-2026-0008',NULL,'Amarech Otoro',NULL,NULL,7,NULL,'full_time',0.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:30','2026-06-29 07:29:30'),(8,9,'SITS-2026-0009',NULL,'Azeb Buche',NULL,NULL,4,NULL,'full_time',0.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:30','2026-06-29 07:29:30'),(9,10,'SITS-2026-0010',NULL,'Birhanu Gelaye',NULL,NULL,4,NULL,'full_time',22996.90,'G8-L5',0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:30','2026-06-29 07:29:35'),(10,11,'SITS-2026-0011',NULL,'Elfinesh Yadesa',NULL,NULL,7,NULL,'full_time',0.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:31','2026-06-29 07:29:31'),(11,12,'SITS-2026-0012',NULL,'Geda Tufule',NULL,NULL,7,NULL,'full_time',0.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:31','2026-06-29 07:29:31'),(12,13,'SITS-2026-0013',NULL,'Kalkidan Eshetu',NULL,NULL,8,NULL,'full_time',0.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:31','2026-06-29 07:29:31'),(13,14,'SITS-2026-0014',NULL,'Mesele Dawit',NULL,NULL,7,NULL,'full_time',0.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:31','2026-06-29 07:29:31'),(14,15,'SITS-2026-0015',NULL,'Meskerem Aseffa',NULL,NULL,7,NULL,'full_time',22838.10,'G6-L10',0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:32','2026-06-29 07:29:35'),(15,16,'SITS-2026-0016',NULL,'Mesganu Petros',NULL,NULL,2,NULL,'full_time',22589.07,'G7-L10',0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:32','2026-06-29 07:29:35'),(16,17,'SITS-2026-0017',NULL,'Misale Getu Ayalew',NULL,NULL,6,NULL,'full_time',20000.00,NULL,0,1,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:32','2026-06-29 07:29:35'),(17,18,'SITS-2026-0018',NULL,'Pastor Zekariyas Chinasho',NULL,NULL,4,NULL,'full_time',20000.00,'G7-L6',0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:32','2026-06-29 07:29:35'),(18,19,'SITS-2026-0019',NULL,'Selamawit Yared',NULL,NULL,9,NULL,'full_time',0.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:32','2026-06-29 07:29:32'),(19,20,'SITS-2026-0020',NULL,'Tamiru Lijalem',NULL,NULL,3,NULL,'full_time',24491.70,'G8-L6',0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:33','2026-06-29 07:29:35'),(20,21,'SITS-2026-0021',NULL,'Tesfaye Gebre',NULL,NULL,4,NULL,'full_time',0.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:33','2026-06-29 07:29:33'),(21,22,'SITS-2026-0022',NULL,'Tesfaye Gebre Oke',NULL,NULL,4,NULL,'full_time',0.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:33','2026-06-29 07:29:33'),(22,23,'SITS-2026-0023',NULL,'Yetnayet Nigatu Entele',NULL,NULL,5,NULL,'full_time',0.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:33','2026-06-29 07:29:33'),(23,24,'SITS-2026-0024',NULL,'Yilma Gezmu Mengesha',NULL,NULL,2,NULL,'full_time',25103.47,'G9-L2',0,1,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:34','2026-06-29 07:29:35'),(24,25,'SITS-2026-0025',NULL,'Zeleke Abisso',NULL,NULL,3,NULL,'full_time',15221.97,'G7-L4',0,1,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:34','2026-06-29 07:29:35'),(25,26,'SITS-2026-0026',NULL,'Zewude Zeleke',NULL,NULL,4,NULL,'full_time',0.00,NULL,0,0,8,NULL,1,0,NULL,'active',NULL,'2026-06-29 07:29:34','2026-06-29 07:29:34');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_periods`
--

DROP TABLE IF EXISTS `evaluation_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_periods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `cadence` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `formula_version` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_periods`
--

LOCK TABLES `evaluation_periods` WRITE;
/*!40000 ALTER TABLE `evaluation_periods` DISABLE KEYS */;
INSERT INTO `evaluation_periods` VALUES (1,'July 2025','monthly','2025-07-01','2025-07-31','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(2,'August 2025','monthly','2025-08-01','2025-08-31','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(3,'September 2025','monthly','2025-09-01','2025-09-30','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(4,'October 2025','monthly','2025-10-01','2025-10-31','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(5,'November 2025','monthly','2025-11-01','2025-11-30','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(6,'December 2025','monthly','2025-12-01','2025-12-31','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(7,'January 2026','monthly','2026-01-01','2026-01-31','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(8,'February 2026','monthly','2026-02-01','2026-02-28','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(9,'March 2026','monthly','2026-03-01','2026-03-31','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(10,'April 2026','monthly','2026-04-01','2026-04-30','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(11,'May 2026','monthly','2026-05-01','2026-05-31','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(12,'June 2026','monthly','2026-06-01','2026-06-30','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(13,'Q1-F1','fortnightly','2025-07-01','2025-07-14','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(14,'Q1-F2','fortnightly','2025-07-15','2025-07-28','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(15,'Q1-F3','fortnightly','2025-07-29','2025-08-11','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(16,'Q1-F4','fortnightly','2025-08-12','2025-08-25','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(17,'Q1-F5','fortnightly','2025-08-26','2025-09-08','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(18,'Q1-F6','fortnightly','2025-09-09','2025-09-22','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(19,'Q1-F7','fortnightly','2025-09-23','2025-09-30','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(20,'Q2-F1','fortnightly','2025-10-01','2025-10-14','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(21,'Q2-F2','fortnightly','2025-10-15','2025-10-28','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(22,'Q2-F3','fortnightly','2025-10-29','2025-11-11','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(23,'Q2-F4','fortnightly','2025-11-12','2025-11-25','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(24,'Q2-F5','fortnightly','2025-11-26','2025-12-09','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(25,'Q2-F6','fortnightly','2025-12-10','2025-12-23','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(26,'Q2-F7','fortnightly','2025-12-24','2025-12-31','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(27,'Q3-F1','fortnightly','2026-01-01','2026-01-14','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(28,'Q3-F2','fortnightly','2026-01-15','2026-01-28','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(29,'Q3-F3','fortnightly','2026-01-29','2026-02-11','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(30,'Q3-F4','fortnightly','2026-02-12','2026-02-25','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(31,'Q3-F5','fortnightly','2026-02-26','2026-03-11','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(32,'Q3-F6','fortnightly','2026-03-12','2026-03-25','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(33,'Q3-F7','fortnightly','2026-03-26','2026-03-31','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(34,'Q4-F1','fortnightly','2026-04-01','2026-04-14','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(35,'Q4-F2','fortnightly','2026-04-15','2026-04-28','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(36,'Q4-F3','fortnightly','2026-04-29','2026-05-12','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(37,'Q4-F4','fortnightly','2026-05-13','2026-05-26','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(38,'Q4-F5','fortnightly','2026-05-27','2026-06-09','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(39,'Q4-F6','fortnightly','2026-06-10','2026-06-23','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(40,'Q4-F7','fortnightly','2026-06-24','2026-06-30','open',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35');
/*!40000 ALTER TABLE `evaluation_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_ratings`
--

DROP TABLE IF EXISTS `evaluation_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_ratings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_id` bigint(20) unsigned NOT NULL,
  `rater_user_id` bigint(20) unsigned DEFAULT NULL,
  `rater_type` varchar(255) NOT NULL,
  `kpi_id` bigint(20) unsigned DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `comment_en` text DEFAULT NULL,
  `comment_am` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evaluation_ratings_evaluation_id_foreign` (`evaluation_id`),
  KEY `evaluation_ratings_rater_user_id_foreign` (`rater_user_id`),
  KEY `evaluation_ratings_kpi_id_foreign` (`kpi_id`),
  CONSTRAINT `evaluation_ratings_evaluation_id_foreign` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluation_ratings_kpi_id_foreign` FOREIGN KEY (`kpi_id`) REFERENCES `kpis` (`id`) ON DELETE SET NULL,
  CONSTRAINT `evaluation_ratings_rater_user_id_foreign` FOREIGN KEY (`rater_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_ratings`
--

LOCK TABLES `evaluation_ratings` WRITE;
/*!40000 ALTER TABLE `evaluation_ratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `evaluation_ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluations`
--

DROP TABLE IF EXISTS `evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `evaluation_period_id` bigint(20) unsigned NOT NULL,
  `auto_score` decimal(5,2) DEFAULT NULL,
  `manager_score` decimal(5,2) DEFAULT NULL,
  `executive_score` decimal(5,2) DEFAULT NULL,
  `final_score` decimal(5,2) DEFAULT NULL,
  `grade_band_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `evaluations_employee_id_evaluation_period_id_unique` (`employee_id`,`evaluation_period_id`),
  KEY `evaluations_evaluation_period_id_foreign` (`evaluation_period_id`),
  KEY `evaluations_grade_band_id_index` (`grade_band_id`),
  CONSTRAINT `evaluations_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluations_evaluation_period_id_foreign` FOREIGN KEY (`evaluation_period_id`) REFERENCES `evaluation_periods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluations_grade_band_id_foreign` FOREIGN KEY (`grade_band_id`) REFERENCES `grade_bands` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluations`
--

LOCK TABLES `evaluations` WRITE;
/*!40000 ALTER TABLE `evaluations` DISABLE KEYS */;
/*!40000 ALTER TABLE `evaluations` ENABLE KEYS */;
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
-- Table structure for table `fortnight_task`
--

DROP TABLE IF EXISTS `fortnight_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fortnight_task` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fortnight_id` bigint(20) unsigned NOT NULL,
  `task_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fortnight_task_fortnight_id_task_id_unique` (`fortnight_id`,`task_id`),
  KEY `fortnight_task_task_id_foreign` (`task_id`),
  CONSTRAINT `fortnight_task_fortnight_id_foreign` FOREIGN KEY (`fortnight_id`) REFERENCES `fortnights` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fortnight_task_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fortnight_task`
--

LOCK TABLES `fortnight_task` WRITE;
/*!40000 ALTER TABLE `fortnight_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `fortnight_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fortnights`
--

DROP TABLE IF EXISTS `fortnights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fortnights` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quarter_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fortnights_quarter_id_foreign` (`quarter_id`),
  CONSTRAINT `fortnights_quarter_id_foreign` FOREIGN KEY (`quarter_id`) REFERENCES `quarters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fortnights`
--

LOCK TABLES `fortnights` WRITE;
/*!40000 ALTER TABLE `fortnights` DISABLE KEYS */;
INSERT INTO `fortnights` VALUES (1,1,'Q1-F1','2025-07-01','2025-07-14','2026-06-29 07:29:34','2026-06-29 07:29:34'),(2,1,'Q1-F2','2025-07-15','2025-07-28','2026-06-29 07:29:34','2026-06-29 07:29:34'),(3,1,'Q1-F3','2025-07-29','2025-08-11','2026-06-29 07:29:34','2026-06-29 07:29:34'),(4,1,'Q1-F4','2025-08-12','2025-08-25','2026-06-29 07:29:34','2026-06-29 07:29:34'),(5,1,'Q1-F5','2025-08-26','2025-09-08','2026-06-29 07:29:34','2026-06-29 07:29:34'),(6,1,'Q1-F6','2025-09-09','2025-09-22','2026-06-29 07:29:34','2026-06-29 07:29:34'),(7,1,'Q1-F7','2025-09-23','2025-09-30','2026-06-29 07:29:34','2026-06-29 07:29:34'),(8,2,'Q2-F1','2025-10-01','2025-10-14','2026-06-29 07:29:34','2026-06-29 07:29:34'),(9,2,'Q2-F2','2025-10-15','2025-10-28','2026-06-29 07:29:34','2026-06-29 07:29:34'),(10,2,'Q2-F3','2025-10-29','2025-11-11','2026-06-29 07:29:34','2026-06-29 07:29:34'),(11,2,'Q2-F4','2025-11-12','2025-11-25','2026-06-29 07:29:34','2026-06-29 07:29:34'),(12,2,'Q2-F5','2025-11-26','2025-12-09','2026-06-29 07:29:34','2026-06-29 07:29:34'),(13,2,'Q2-F6','2025-12-10','2025-12-23','2026-06-29 07:29:34','2026-06-29 07:29:34'),(14,2,'Q2-F7','2025-12-24','2025-12-31','2026-06-29 07:29:34','2026-06-29 07:29:34'),(15,3,'Q3-F1','2026-01-01','2026-01-14','2026-06-29 07:29:34','2026-06-29 07:29:34'),(16,3,'Q3-F2','2026-01-15','2026-01-28','2026-06-29 07:29:34','2026-06-29 07:29:34'),(17,3,'Q3-F3','2026-01-29','2026-02-11','2026-06-29 07:29:34','2026-06-29 07:29:34'),(18,3,'Q3-F4','2026-02-12','2026-02-25','2026-06-29 07:29:34','2026-06-29 07:29:34'),(19,3,'Q3-F5','2026-02-26','2026-03-11','2026-06-29 07:29:34','2026-06-29 07:29:34'),(20,3,'Q3-F6','2026-03-12','2026-03-25','2026-06-29 07:29:34','2026-06-29 07:29:34'),(21,3,'Q3-F7','2026-03-26','2026-03-31','2026-06-29 07:29:34','2026-06-29 07:29:34'),(22,4,'Q4-F1','2026-04-01','2026-04-14','2026-06-29 07:29:34','2026-06-29 07:29:34'),(23,4,'Q4-F2','2026-04-15','2026-04-28','2026-06-29 07:29:34','2026-06-29 07:29:34'),(24,4,'Q4-F3','2026-04-29','2026-05-12','2026-06-29 07:29:34','2026-06-29 07:29:34'),(25,4,'Q4-F4','2026-05-13','2026-05-26','2026-06-29 07:29:34','2026-06-29 07:29:34'),(26,4,'Q4-F5','2026-05-27','2026-06-09','2026-06-29 07:29:34','2026-06-29 07:29:34'),(27,4,'Q4-F6','2026-06-10','2026-06-23','2026-06-29 07:29:34','2026-06-29 07:29:34'),(28,4,'Q4-F7','2026-06-24','2026-06-30','2026-06-29 07:29:35','2026-06-29 07:29:35');
/*!40000 ALTER TABLE `fortnights` ENABLE KEYS */;
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
-- Table structure for table `goals`
--

DROP TABLE IF EXISTS `goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `strategy_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goals_strategy_id_foreign` (`strategy_id`),
  CONSTRAINT `goals_strategy_id_foreign` FOREIGN KEY (`strategy_id`) REFERENCES `strategies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goals`
--

LOCK TABLES `goals` WRITE;
/*!40000 ALTER TABLE `goals` DISABLE KEYS */;
/*!40000 ALTER TABLE `goals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade_bands`
--

DROP TABLE IF EXISTS `grade_bands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grade_bands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grade_scale_id` bigint(20) unsigned NOT NULL,
  `label_en` varchar(255) NOT NULL,
  `label_am` varchar(255) DEFAULT NULL,
  `min_score` decimal(5,2) NOT NULL,
  `max_score` decimal(5,2) NOT NULL,
  `triggers_increment` tinyint(1) NOT NULL DEFAULT 0,
  `increment_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grade_bands_grade_scale_id_foreign` (`grade_scale_id`),
  CONSTRAINT `grade_bands_grade_scale_id_foreign` FOREIGN KEY (`grade_scale_id`) REFERENCES `grade_scales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade_bands`
--

LOCK TABLES `grade_bands` WRITE;
/*!40000 ALTER TABLE `grade_bands` DISABLE KEYS */;
INSERT INTO `grade_bands` VALUES (1,1,'Excellent','በጣም ጥሩ',90.00,100.00,1,8.00,1,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(2,1,'Very Good','ጥሩ',75.00,89.99,1,4.00,2,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(3,1,'Satisfactory','አጥጋቢ',60.00,74.99,0,0.00,3,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(4,1,'Needs Improvement','መሻሻል ይፈልጋል',50.00,59.99,0,0.00,4,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(5,1,'Unsatisfactory','አጥጋቢ ያልሆነ',0.00,49.99,0,0.00,5,'2026-06-29 07:29:28','2026-06-29 07:29:28');
/*!40000 ALTER TABLE `grade_bands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade_scales`
--

DROP TABLE IF EXISTS `grade_scales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grade_scales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade_scales`
--

LOCK TABLES `grade_scales` WRITE;
/*!40000 ALTER TABLE `grade_scales` DISABLE KEYS */;
INSERT INTO `grade_scales` VALUES (1,'SITS Standard 5-band',1,'2026-06-29 07:29:28','2026-06-29 07:29:28');
/*!40000 ALTER TABLE `grade_scales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `increment_recommendations`
--

DROP TABLE IF EXISTS `increment_recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `increment_recommendations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_id` bigint(20) unsigned NOT NULL,
  `current_salary` decimal(12,2) NOT NULL,
  `proposed_salary` decimal(12,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `approved_by_id` bigint(20) unsigned DEFAULT NULL,
  `applied_payroll_period_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `increment_recommendations_evaluation_id_foreign` (`evaluation_id`),
  KEY `increment_recommendations_approved_by_id_foreign` (`approved_by_id`),
  KEY `increment_recommendations_applied_payroll_period_id_index` (`applied_payroll_period_id`),
  CONSTRAINT `increment_recommendations_applied_payroll_period_id_foreign` FOREIGN KEY (`applied_payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `increment_recommendations_approved_by_id_foreign` FOREIGN KEY (`approved_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `increment_recommendations_evaluation_id_foreign` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `increment_recommendations`
--

LOCK TABLES `increment_recommendations` WRITE;
/*!40000 ALTER TABLE `increment_recommendations` DISABLE KEYS */;
/*!40000 ALTER TABLE `increment_recommendations` ENABLE KEYS */;
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
-- Table structure for table `job_description_versions`
--

DROP TABLE IF EXISTS `job_description_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_description_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_description_id` bigint(20) unsigned NOT NULL,
  `version_no` int(10) unsigned NOT NULL DEFAULT 1,
  `body` longtext DEFAULT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`items`)),
  `effective_from` date DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_description_versions_job_description_id_foreign` (`job_description_id`),
  KEY `job_description_versions_created_by_foreign` (`created_by`),
  CONSTRAINT `job_description_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `job_description_versions_job_description_id_foreign` FOREIGN KEY (`job_description_id`) REFERENCES `job_descriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_description_versions`
--

LOCK TABLES `job_description_versions` WRITE;
/*!40000 ALTER TABLE `job_description_versions` DISABLE KEYS */;
INSERT INTO `job_description_versions` VALUES (1,1,1,'Reports to: President\n\nSenior administrative and chief academic officer of the seminary; reports to the President. Based at Hawassa.\n\n1. Visionary & Spiritual Leadership — champion the strategic vision and mission; foster a theologically robust, spiritually integrated academic environment; provide formative spiritual oversight.\n2. Academic Leadership & Oversight — supervise all faculty, ODeL, satellite campus managers, the Registrar, the Dean of Students and the Library; manage hiring, professional development, promotion and evaluation of faculty; develop the academic calendar and oversee course schedules; maintain the academic catalogue and official publications.\n3. Strategic & Administrative Oversight — senior administrator for day-to-day and long-term non-academic operations; manage budget; ACTEA accreditation compliance.\n4. Students Recruitment, Expansion & Student Formation — executive oversight of the student lifecycle; align programs with the \"KNOW, DO, BE\" formational philosophy; expand centres and enrolment; uphold student conduct and discipline.\n5. External Engagement & Institutional Advancement — chief ambassador and liaison to churches and partners; represent SITS publicly; cultivate strategic partnerships.',NULL,'2025-04-14',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(2,2,1,'Reports to: President\n\nThe Academic Dean reports directly to the President of the college.\n- Oversees faculty and approves the hiring of all faculty members and the choice of adjunct faculty and guest lecturers.\n- Gives approval to all curriculum and textbooks.\n- Ensures the requirements of ACTEA/SBCE accreditation are met and sustained.\n- Oversees admissions: faculty, Registrar, Admissions, Librarians.\n- Gives oversight to all self-studies undertaken by the college.\n- Approves the admission of all diploma and degree students.\n- Certifies graduation readiness for any applicant for graduation.\n- Approves all course schedules and faculty teaching assignments.',NULL,'2025-04-14',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(3,3,1,'Reports to: President\n\nThe Dean of Students reports directly to the President of the college.\n- Responsible for the spiritual and social life of the students.\n- Primary person working with the character development of students.\n- Oversees the \"Reported Ministry\" program.\n- Provides student counselling as needed.\n- Holds primary responsibility for student discipline.',NULL,'2025-04-14',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(4,4,1,'Reports to: Dean of the Seminary (Director for Academic Program)\n\nOffice of the Registrar and Alumni (Article 34). Accountable to the Director for Academic Program.\n- Publicity, orientation and pre-admission counselling of prospective students; process applications and admissions.\n- Student placement; maintain accurate, confidential student records; run the remedial program.\n- Graduation functions: organise the ceremony; prepare and issue original diplomas and transcripts.\n- Alumni management: maintain records, organise events, disseminate updates.\n- Custodian of the Common Seal of SITS; affix to authorised documents.\n- Coordinate distance-learning registration with the ODeL division; national accreditation compliance.\n- Create and issue secure student ID cards; uphold transcript and certificate integrity.',NULL,'2025-04-14',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(5,5,1,'Reports to: President / Vice President\n\nManager, Open Distance and eLearning (ODeL). Reports to the President / Vice President.\nOffice structure: ODeL Manager; Expansion & Resource Development Officer; Coordination & Supervision Officer; Coordination & Supervision Assistants.\n- Policy development, program planning and coordination for open, distance and eLearning programs.\n- Program implementation across local and international languages; distance and online program delivery.\n- Strategy development with academic and administrative units; ministry advancement programs.\n- Accessibility, flexible program delivery and resource accessibility for all learners.\n- Expansion and resource development; lead program expansion into new areas.\n- Regulatory compliance with SITS regulations; budget management.\n- Student success management; information management; instructor appointments; revenue management.',NULL,'2025-04-14',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(6,6,1,'Reports to: President\n\nSatellite & Learning Sites Manager. Reports to the President. Full-time, on-site / regionally based (South Ethiopia, Central Ethiopia). Senior regional representative.\n1. Strategic Oversight of Satellite & Learning Sites — implement the seminary\'s strategy across assigned territory; balance vision-casting with on-the-ground coordination.\n2. Leadership & Personnel Supervision — directly supervise Satellite & Learning Site Program Managers; evaluate performance; guide academic programs and student services.\n3. Program Implementation & Academic Integrity — faithful delivery of approved curriculum; course scheduling, instructor assignments, exam administration; quality assurance.\n4. Expansion & Site Development — identify, evaluate and open new learning sites in partnership with churches and communities.\n5. Spiritual Oversight & Seminary Mission Advancement — foster a spiritually enriching environment; mentor site leaders.\n6. Resource Management & Reporting — steward facilities and resources; submit fortnightly, monthly, quarterly, semi-annual and annual reports to the President.\nQualifications: Master\'s degree in Theology, Ministry or Christian Education; senior leadership experience.',NULL,'2025-04-14',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(7,7,1,'Reports to: College President\n\nDirector of the Wolayta Campus of SBCE. Supervisor: College President.\n- Direct and administer the operation of the Wolayta campus.\n- Lead and direct campus employees; facilitate campus activities.\n- Hold meetings with staff and faculty; work with the main campus in Hawassa.\n- Represent the college in the Wolayta community to advance its growth.\n- Prepare course schedules and assign teachers for different subjects.\n- Submit quarterly Wolayta staff reports to the main campus (financial, building, grounds, academic).\n- Submit all course scheduling and faculty assignments for review and approval to the Hawassa office.\n- Teach some courses on the Wolayta campus; report monthly to the College President.',NULL,'2025-04-14',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(8,8,1,'Reports to: Vice President\n\nOperational Manager (የአሠራር ሥራ አስኪያጅ). Reports to the Vice President.\n- Track and validate employees\' daily attendance and work hours; submit a formal report to the Vice President and Finance before monthly salaries are cleared.\n- Verify the necessity of any property/asset purchase and propose it to the Vice President for endorsement before the Cashier releases funds (procurement & expense control).\n- Supervisory oversight of security guards (የጥበቃ ሠራተኞች) and cleaners (የጽዳት ሠራተኞች).\n- Asset and facilities management for the seminary.',NULL,'2025-04-14',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(9,9,1,'Reports to: Finance Manager\n\nCashier. Reports to the Finance Manager.\n1. Transaction Handling — process all cash, bank, credit/debit card and other payments; issue receipts, refunds and change accurately; maintain accurate records and daily transaction reports; pay monthly utilities (electricity, internet, water, telephone); prepare cheques for payment; cautiously handle cheques.\n2. Customer Service — answer payment inquiries; resolve complaints politely, referring complex issues to the Finance Manager.\n3. Financial Integrity — secure the cash drawer and contents; follow safe cash-handling procedures; execute transactions only when approved by the Seminary President or authorised personnel.\n4. Supporting the Storekeeper — provide support, record the Good Receiving Voucher and supervise store-keeper performance.',NULL,'2025-04-14',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(10,10,1,'Reports to: Academic Dean / President\n\nTeacher\'s Job Description (signed by the SITS President).\nA teacher should: have formal training in the right areas; be a good role model; be gifted; be willing to grow.\nIn relation to the course — prepare teaching materials before the semester; submit the syllabus on time; finish the course on schedule; keep classroom and office hours; give homework; submit a copy of all tests and exams; submit a plan and schedule for make-up classes; keep attendance; participate in the academic council meeting.\nIn relation to students & the college — uphold the college\'s core values (love for God and neighbour, commitment to the Bible, the gospel of Christ, unity, Pentecostal doctrine); be a pastor to students and know them by name; create a sound learning environment; start the class with prayer (no more than 5–7 minutes).',NULL,'2025-04-14',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(11,11,1,'Reports to: Operational Manager\n\nAdministrative Support Staff (የአስተዳደር ድጋፍ ሰጪ ሠራተኛ). Managed under the administration office; reports to the Operations Manager.\n- Support staff onboarding and orientation.\n- Annual leave tracking in consultation with the worker\'s immediate supervisor before validation.\n- Rigorous accountability for physical resources: book check-outs, library returns and distance-learning materials.\n- General office and administrative support to keep daily operations running.',NULL,'2025-04-14',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(12,12,1,'Reports to: Operational Manager\n\nSecurity Guard (የጥበቃ ሠራተኛ). Reports to the Operational Manager.\n- Control entry and exit at the seminary gate; maintain the staff/visitor in-out log.\n- Safeguard seminary property, buildings and grounds.\n- Monitor the premises and report incidents promptly.\n- Cover assigned shifts, including overnight rotations.',NULL,'2025-04-14',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(13,13,1,'Reports to: Operational Manager\n\nCleaner (የጽዳት ሠራተኛ). Reports to the Operational Manager. Daily working hours limited to the national legal standard of 8 hours/day (Ethiopian labour law).\n- Clean and maintain offices, classrooms, shared spaces and the compound.\n- Prepare rooms for classes, meetings and events.\n- Manage cleaning supplies responsibly and report shortages.\n- Support general upkeep and hygiene across the campus.',NULL,'2025-04-14',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28');
/*!40000 ALTER TABLE `job_description_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_descriptions`
--

DROP TABLE IF EXISTS `job_descriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_descriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `position_id` bigint(20) unsigned NOT NULL,
  `title_en` varchar(255) NOT NULL,
  `title_am` varchar(255) DEFAULT NULL,
  `current_version_id` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_descriptions_position_id_foreign` (`position_id`),
  KEY `job_descriptions_current_version_id_foreign` (`current_version_id`),
  CONSTRAINT `job_descriptions_current_version_id_foreign` FOREIGN KEY (`current_version_id`) REFERENCES `job_description_versions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `job_descriptions_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_descriptions`
--

LOCK TABLES `job_descriptions` WRITE;
/*!40000 ALTER TABLE `job_descriptions` DISABLE KEYS */;
INSERT INTO `job_descriptions` VALUES (1,3,'Dean of the Seminary','የሴሚናሪ ዲን',1,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(2,4,'Academic Dean','የትምህርት ዲን',2,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(3,5,'Dean of Students','የተማሪዎች ዲን',3,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(4,6,'Registrar','መዝጋቢ (ሬጅስትራር)',4,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(5,7,'Manager, Open Distance & eLearning (ODeL)','የኦዲኤል ኃላፊ',5,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(6,8,'Satellite & Learning Sites Manager','የሳተላይት ማእከላት ኃላፊ',6,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(7,9,'Director, Wolayta Campus (SBCE)','የወላይታ ካምፓስ ዳይሬክተር',7,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(8,10,'Operational Manager','የአሠራር ሥራ አስኪያጅ',8,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(9,12,'Cashier','ገንዘብ ያዥ (ቆጣሪ)',9,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(10,13,'Teacher / Faculty','መምህር',10,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(11,18,'Administrative Support Staff','የአስተዳደር ድጋፍ ሰጪ ሠራተኛ',11,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(12,16,'Security Guard','የጥበቃ ሠራተኛ',12,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(13,17,'Cleaner','የጽዳት ሠራተኛ',13,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28');
/*!40000 ALTER TABLE `job_descriptions` ENABLE KEYS */;
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
-- Table structure for table `kpi_task`
--

DROP TABLE IF EXISTS `kpi_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kpi_task` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kpi_id` bigint(20) unsigned NOT NULL,
  `task_id` bigint(20) unsigned NOT NULL,
  `contribution_weight` decimal(5,2) NOT NULL DEFAULT 1.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kpi_task_kpi_id_task_id_unique` (`kpi_id`,`task_id`),
  KEY `kpi_task_task_id_foreign` (`task_id`),
  CONSTRAINT `kpi_task_kpi_id_foreign` FOREIGN KEY (`kpi_id`) REFERENCES `kpis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kpi_task_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kpi_task`
--

LOCK TABLES `kpi_task` WRITE;
/*!40000 ALTER TABLE `kpi_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `kpi_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kpis`
--

DROP TABLE IF EXISTS `kpis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kpis` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kpiable_type` varchar(255) DEFAULT NULL,
  `kpiable_id` bigint(20) unsigned DEFAULT NULL,
  `title_en` varchar(255) NOT NULL,
  `title_am` varchar(255) DEFAULT NULL,
  `measure_type` varchar(255) NOT NULL DEFAULT 'quantitative',
  `target_value` decimal(12,2) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `weight` decimal(5,2) NOT NULL DEFAULT 1.00,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'created',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `confirmed_by` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kpis_kpiable_type_kpiable_id_index` (`kpiable_type`,`kpiable_id`),
  KEY `kpis_approved_by_foreign` (`approved_by`),
  KEY `kpis_confirmed_by_foreign` (`confirmed_by`),
  KEY `kpis_created_by_foreign` (`created_by`),
  CONSTRAINT `kpis_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kpis_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kpis_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kpis`
--

LOCK TABLES `kpis` WRITE;
/*!40000 ALTER TABLE `kpis` DISABLE KEYS */;
/*!40000 ALTER TABLE `kpis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `leave_type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'submitted',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` int(11) NOT NULL,
  `days_approved` int(11) DEFAULT NULL,
  `reason` text NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_requests_approved_by_foreign` (`approved_by`),
  KEY `leave_requests_rejected_by_foreign` (`rejected_by`),
  KEY `leave_requests_cancelled_by_foreign` (`cancelled_by`),
  KEY `leave_requests_created_by_foreign` (`created_by`),
  KEY `leave_requests_employee_id_index` (`employee_id`),
  KEY `leave_requests_status_index` (`status`),
  KEY `leave_requests_start_date_index` (`start_date`),
  KEY `leave_requests_end_date_index` (`end_date`),
  CONSTRAINT `leave_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_subscriptions`
--

LOCK TABLES `library_subscriptions` WRITE;
/*!40000 ALTER TABLE `library_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mass_permissions`
--

DROP TABLE IF EXISTS `mass_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mass_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `reason` text DEFAULT NULL,
  `payroll_period_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `total_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `initiated_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `first_approved_by` bigint(20) unsigned DEFAULT NULL,
  `first_approved_at` timestamp NULL DEFAULT NULL,
  `first_review_notes` text DEFAULT NULL,
  `final_approved_by` bigint(20) unsigned DEFAULT NULL,
  `final_approved_at` timestamp NULL DEFAULT NULL,
  `final_review_notes` text DEFAULT NULL,
  `employees_affected` int(10) unsigned DEFAULT NULL,
  `permissions_spawned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mass_permissions_initiated_by_foreign` (`initiated_by`),
  KEY `mass_permissions_first_approved_by_foreign` (`first_approved_by`),
  KEY `mass_permissions_final_approved_by_foreign` (`final_approved_by`),
  KEY `mass_permissions_payroll_period_id_status_index` (`payroll_period_id`,`status`),
  CONSTRAINT `mass_permissions_final_approved_by_foreign` FOREIGN KEY (`final_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mass_permissions_first_approved_by_foreign` FOREIGN KEY (`first_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mass_permissions_initiated_by_foreign` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mass_permissions_payroll_period_id_foreign` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mass_permissions`
--

LOCK TABLES `mass_permissions` WRITE;
/*!40000 ALTER TABLE `mass_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `mass_permissions` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2024_12_04_115737_create_blogs_table',1),(5,'2024_12_04_115801_create_contacts_table',1),(6,'2024_12_04_115901_create_courses_table',1),(7,'2024_12_04_120443_create_testimonials_table',1),(8,'2024_12_09_105317_create_libraries_table',1),(9,'2024_12_11_131148_create_admins_table',1),(10,'2024_12_12_074531_create_events_table',1),(11,'2024_12_12_125237_create_programs_table',1),(12,'2025_01_06_065244_create_trainers_table',1),(13,'2025_01_14_141542_create_subscriptions_table',1),(14,'2025_03_31_063351_create_galleries_table',1),(15,'2025_06_01_000001_create_org_structure_tables',1),(16,'2025_06_01_000002_add_profile_fields_to_users_table',1),(17,'2025_06_01_000003_create_employees_table',1),(18,'2025_06_01_000004_create_job_description_tables',1),(19,'2025_06_01_000005_create_strategic_plan_tables',1),(20,'2025_06_01_000006_create_calendar_tables',1),(21,'2025_06_01_000007_create_kpi_tables',1),(22,'2025_06_01_000008_create_task_tables',1),(23,'2025_06_01_000009_create_evaluation_tables',1),(24,'2025_06_01_000010_create_ai_tables',1),(25,'2025_06_01_000011_create_grading_tables',1),(26,'2025_06_01_000012_create_payroll_tables',1),(27,'2025_06_01_000013_create_comments_table',1),(28,'2025_06_01_000014_create_documents_table',1),(29,'2025_06_01_000015_create_settings_table',1),(30,'2026_06_18_120208_create_permission_tables',1),(31,'2026_06_18_120211_create_activity_log_table',1),(32,'2026_06_18_120212_add_event_column_to_activity_log_table',1),(33,'2026_06_18_120213_add_batch_uuid_column_to_activity_log_table',1),(34,'2026_06_18_140000_add_items_to_job_description_versions',1),(35,'2026_06_19_090000_create_allowances_table',1),(36,'2026_06_19_100000_create_conduct_tables',1),(37,'2026_06_19_110000_create_employee_lifecycle_tables',1),(38,'2026_06_20_090000_add_attendance_device_fields_to_employees_table',1),(39,'2026_06_20_090001_create_attendance_import_tables',1),(40,'2026_06_22_100000_add_payroll_profile_to_employees',1),(41,'2026_06_22_100001_add_approval_fields_to_payroll_periods',1),(42,'2026_06_22_100002_add_sheet_columns_to_payslips',1),(43,'2026_06_22_100003_create_payroll_adjustments_table',1),(44,'2026_06_22_130445_alter_users_table_for_password_recovery',1),(45,'2026_06_22_130824_create_organizations_table',1),(46,'2026_06_24_100000_create_payroll_components_table',1),(47,'2026_06_24_100001_create_payroll_component_assignments_table',1),(48,'2026_06_24_100002_create_attendance_permissions_table',1),(49,'2026_06_24_100003_drop_legacy_allowance_adjustment_tables',1),(50,'2026_06_24_120000_add_statutory_exempt_to_employees',1),(51,'2026_06_27_000000_fix_courses_program_id_foreign_key',1),(52,'2026_06_27_220800_add_file_path_to_attendance_permissions_table',1),(53,'2026_06_28_000001_create_library_subscriptions_table',1),(54,'2026_06_28_100000_create_closed_days_table',1),(55,'2026_06_28_100001_create_mass_permissions_table',1),(56,'2026_06_29_090000_add_file_path_to_attendance_imports_table',1),(57,'2026_06_30_000000_add_website_fields_to_users_table',1);
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
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(1,'App\\Models\\User',2),(8,'App\\Models\\User',3),(6,'App\\Models\\User',4),(8,'App\\Models\\User',5),(6,'App\\Models\\User',6),(5,'App\\Models\\User',7),(8,'App\\Models\\User',8),(8,'App\\Models\\User',9),(8,'App\\Models\\User',10),(8,'App\\Models\\User',11),(6,'App\\Models\\User',12),(8,'App\\Models\\User',13),(8,'App\\Models\\User',14),(6,'App\\Models\\User',15),(2,'App\\Models\\User',16),(6,'App\\Models\\User',17),(8,'App\\Models\\User',18),(6,'App\\Models\\User',19),(6,'App\\Models\\User',20),(6,'App\\Models\\User',21),(8,'App\\Models\\User',22),(8,'App\\Models\\User',23),(6,'App\\Models\\User',24),(8,'App\\Models\\User',25),(8,'App\\Models\\User',26),(1,'App\\Models\\User',27),(9,'App\\Models\\User',27);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `narrative_reports`
--

DROP TABLE IF EXISTS `narrative_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `narrative_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `evaluation_period_id` bigint(20) unsigned DEFAULT NULL,
  `language` varchar(255) NOT NULL DEFAULT 'en',
  `body` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `narrative_reports_employee_id_foreign` (`employee_id`),
  KEY `narrative_reports_evaluation_period_id_foreign` (`evaluation_period_id`),
  CONSTRAINT `narrative_reports_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `narrative_reports_evaluation_period_id_foreign` FOREIGN KEY (`evaluation_period_id`) REFERENCES `evaluation_periods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `narrative_reports`
--

LOCK TABLES `narrative_reports` WRITE;
/*!40000 ALTER TABLE `narrative_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `narrative_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organizations`
--

DROP TABLE IF EXISTS `organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organizations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organizations`
--

LOCK TABLES `organizations` WRITE;
/*!40000 ALTER TABLE `organizations` DISABLE KEYS */;
INSERT INTO `organizations` VALUES (1,'Shiloh International Theological Seminary','2026-06-29 07:29:27','2026-06-29 07:29:27');
/*!40000 ALTER TABLE `organizations` ENABLE KEYS */;
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
-- Table structure for table `payroll_component_assignments`
--

DROP TABLE IF EXISTS `payroll_component_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_component_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `payroll_component_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `schedule_type` varchar(255) NOT NULL DEFAULT 'monthly',
  `start_period_id` bigint(20) unsigned DEFAULT NULL,
  `end_period_id` bigint(20) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_component_assignments_payroll_component_id_foreign` (`payroll_component_id`),
  KEY `payroll_component_assignments_start_period_id_foreign` (`start_period_id`),
  KEY `payroll_component_assignments_end_period_id_foreign` (`end_period_id`),
  KEY `pca_employee_component_idx` (`employee_id`,`payroll_component_id`),
  CONSTRAINT `payroll_component_assignments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_component_assignments_end_period_id_foreign` FOREIGN KEY (`end_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_component_assignments_payroll_component_id_foreign` FOREIGN KEY (`payroll_component_id`) REFERENCES `payroll_components` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_component_assignments_start_period_id_foreign` FOREIGN KEY (`start_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_component_assignments`
--

LOCK TABLES `payroll_component_assignments` WRITE;
/*!40000 ALTER TABLE `payroll_component_assignments` DISABLE KEYS */;
INSERT INTO `payroll_component_assignments` VALUES (1,14,1,500.00,'monthly',NULL,NULL,NULL,1,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35',NULL),(2,14,2,2000.00,'monthly',NULL,NULL,NULL,1,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35',NULL),(3,9,1,600.00,'monthly',NULL,NULL,NULL,1,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35',NULL),(4,9,4,1200.00,'monthly',NULL,NULL,NULL,1,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35',NULL),(5,9,2,2000.00,'monthly',NULL,NULL,NULL,1,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35',NULL),(6,9,5,2520.34,'one_time',9,9,NULL,1,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35',NULL),(7,23,4,1000.00,'monthly',NULL,NULL,NULL,1,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35',NULL),(8,15,1,600.00,'monthly',NULL,NULL,NULL,1,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35',NULL),(9,15,2,2000.00,'monthly',NULL,NULL,NULL,1,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35',NULL),(10,15,5,2067.29,'one_time',9,9,NULL,1,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35',NULL),(11,24,5,5073.99,'one_time',9,9,NULL,1,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35',NULL),(12,19,2,2000.00,'monthly',NULL,NULL,NULL,1,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35',NULL),(13,5,5,2388.88,'one_time',9,9,NULL,1,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35',NULL),(14,17,4,2200.00,'monthly',NULL,NULL,NULL,1,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35',NULL);
/*!40000 ALTER TABLE `payroll_component_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_components`
--

DROP TABLE IF EXISTS `payroll_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `kind` varchar(255) NOT NULL,
  `calc_type` varchar(255) NOT NULL DEFAULT 'fixed',
  `rate` decimal(8,4) DEFAULT NULL,
  `side` varchar(255) DEFAULT NULL,
  `applies_to` varchar(255) NOT NULL DEFAULT 'all',
  `taxable` tinyint(1) NOT NULL DEFAULT 1,
  `exempt_capped` tinyint(1) NOT NULL DEFAULT 0,
  `sheet_column` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_components`
--

LOCK TABLES `payroll_components` WRITE;
/*!40000 ALTER TABLE `payroll_components` DISABLE KEYS */;
INSERT INTO `payroll_components` VALUES (1,'Transport Allowance','allowance','fixed',NULL,NULL,'all',1,1,'transport_allowance',1,1,10,NULL,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28',NULL),(2,'Housing Allowance','allowance','fixed',NULL,NULL,'all',1,0,'housing_allowance',1,1,11,NULL,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28',NULL),(3,'Mobile Allowance','allowance','fixed',NULL,NULL,'all',0,0,'mobile_allowance',1,1,12,NULL,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28',NULL),(4,'Cash Allowance','allowance','fixed',NULL,NULL,'all',1,0,'cash_allowance',1,1,13,NULL,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28',NULL),(5,'Salary Advance','deduction','fixed',NULL,NULL,'all',1,0,'salary_advance',1,1,20,NULL,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28',NULL),(6,'Kircha (Meat Share)','deduction','fixed',NULL,NULL,'all',1,0,'kircha_deduction',1,1,21,NULL,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28',NULL),(7,'Pension (Employee 7%)','statutory','percent',7.0000,'employee','pension_members',1,0,'employee_pension',1,1,30,NULL,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28',NULL),(8,'Pension (Employer 11%)','statutory','percent',11.0000,'employer','pension_members',1,0,'employer_pension',1,1,31,NULL,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28',NULL),(9,'Provident (Employer 1.5%)','statutory','percent',1.5000,'employer','pension_members',1,0,'provident_fund_employer',1,1,34,NULL,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28',NULL),(10,'Provident Fund (Employee 5%)','statutory','percent',5.0000,'employee','pf_members',1,0,'provident_fund_employee',1,1,32,NULL,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28',NULL),(11,'Provident Fund (Employer 12.5%)','statutory','percent',12.5000,'employer','pf_members',1,0,'provident_fund_employer',1,1,33,NULL,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28',NULL);
/*!40000 ALTER TABLE `payroll_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_periods`
--

DROP TABLE IF EXISTS `payroll_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_periods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `payment_date` date DEFAULT NULL,
  `prepared_by` bigint(20) unsigned DEFAULT NULL,
  `prepared_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_periods_prepared_by_foreign` (`prepared_by`),
  KEY `payroll_periods_approved_by_foreign` (`approved_by`),
  CONSTRAINT `payroll_periods_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_periods_prepared_by_foreign` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_periods`
--

LOCK TABLES `payroll_periods` WRITE;
/*!40000 ALTER TABLE `payroll_periods` DISABLE KEYS */;
INSERT INTO `payroll_periods` VALUES (1,'July 2025','2025-07-01','2025-07-31','open','2025-07-31',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(2,'August 2025','2025-08-01','2025-08-31','open','2025-08-31',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(3,'September 2025','2025-09-01','2025-09-30','open','2025-09-30',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(4,'October 2025','2025-10-01','2025-10-31','open','2025-10-31',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(5,'November 2025','2025-11-01','2025-11-30','open','2025-11-30',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(6,'December 2025','2025-12-01','2025-12-31','open','2025-12-31',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(7,'January 2026','2026-01-01','2026-01-31','open','2026-01-31',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(8,'February 2026','2026-02-01','2026-02-28','open','2026-02-28',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(9,'March 2026','2026-03-01','2026-03-31','open','2026-03-31',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(10,'April 2026','2026-04-01','2026-04-30','open','2026-04-30',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(11,'May 2026','2026-05-01','2026-05-31','open','2026-05-31',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35'),(12,'June 2026','2026-06-01','2026-06-30','open','2026-06-30',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35');
/*!40000 ALTER TABLE `payroll_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslip_lines`
--

DROP TABLE IF EXISTS `payslip_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payslip_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payslip_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payslip_lines_payslip_id_foreign` (`payslip_id`),
  CONSTRAINT `payslip_lines_payslip_id_foreign` FOREIGN KEY (`payslip_id`) REFERENCES `payslips` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslip_lines`
--

LOCK TABLES `payslip_lines` WRITE;
/*!40000 ALTER TABLE `payslip_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `payslip_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslips`
--

DROP TABLE IF EXISTS `payslips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payslips` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `grade` varchar(255) DEFAULT NULL,
  `campus` varchar(255) DEFAULT NULL,
  `working_days` decimal(5,2) NOT NULL DEFAULT 0.00,
  `payroll_period_id` bigint(20) unsigned NOT NULL,
  `gross` decimal(12,2) NOT NULL DEFAULT 0.00,
  `overtime` decimal(12,2) NOT NULL DEFAULT 0.00,
  `mobile_allowance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `transport_allowance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `housing_allowance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cash_allowance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `taxable_income` decimal(12,2) NOT NULL DEFAULT 0.00,
  `income_tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `employee_pension` decimal(12,2) NOT NULL DEFAULT 0.00,
  `employer_pension` decimal(12,2) NOT NULL DEFAULT 0.00,
  `provident_fund_employee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `provident_fund_employer` decimal(12,2) NOT NULL DEFAULT 0.00,
  `salary_advance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `kircha_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `other_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payslips_employee_id_payroll_period_id_unique` (`employee_id`,`payroll_period_id`),
  KEY `payslips_payroll_period_id_foreign` (`payroll_period_id`),
  CONSTRAINT `payslips_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payslips_payroll_period_id_foreign` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslips`
--

LOCK TABLES `payslips` WRITE;
/*!40000 ALTER TABLE `payslips` DISABLE KEYS */;
/*!40000 ALTER TABLE `payslips` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'manage strategic plan','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(2,'view strategic plan','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(3,'manage employees','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(4,'view employees','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(5,'view department employees','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(6,'manage job descriptions','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(7,'crud kpis','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(8,'approve kpis','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(9,'confirm kpis','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(10,'view kpis','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(11,'create tasks','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(12,'manage all tasks','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(13,'manage department tasks','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(14,'manage own tasks','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(15,'manage deliverables','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(16,'comment tasks','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(17,'run evaluations','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(18,'score evaluations','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(19,'finalize evaluations','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(20,'view own evaluations','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(21,'manage payroll','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(22,'validate attendance','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(23,'view payslips','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(24,'edit tax config','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(25,'prepare payroll','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(26,'manage deductions','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(27,'submit payroll','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(28,'approve payroll','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(29,'export payroll','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(30,'configure payroll','web','2026-06-29 07:29:27','2026-06-29 07:29:27'),(31,'upload attendance','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(32,'approve attendance','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(33,'create attendance permission','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(34,'approve attendance permission','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(35,'recommend increments','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(36,'approve increments','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(37,'manage users','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(38,'approve users','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(39,'reset passwords','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(40,'view executive reports','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(41,'view department reports','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(42,'export reports','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(43,'manage conduct issues','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(44,'create conduct issues','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(45,'manage department conduct','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(46,'manage conduct decisions','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(47,'manage closed days','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(48,'create mass permission','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(49,'approve mass permission','web','2026-06-29 07:29:28','2026-06-29 07:29:28');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `positions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title_en` varchar(255) NOT NULL,
  `title_am` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `positions_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
INSERT INTO `positions` VALUES (1,'President','ፕሬዚደንት','PRES','2026-06-29 07:29:28','2026-06-29 07:29:28'),(2,'Vice President','ምክትል ፕሬዚደንት','VP','2026-06-29 07:29:28','2026-06-29 07:29:28'),(3,'Dean of the Seminary','የሴሚናሪ ዲን','DEAN','2026-06-29 07:29:28','2026-06-29 07:29:28'),(4,'Academic Dean','የትምህርት ዲን','ACAD_DEAN','2026-06-29 07:29:28','2026-06-29 07:29:28'),(5,'Dean of Students','የተማሪዎች ዲን','DOS','2026-06-29 07:29:28','2026-06-29 07:29:28'),(6,'Registrar','መዝጋቢ (ሬጅስትራር)','REGISTRAR','2026-06-29 07:29:28','2026-06-29 07:29:28'),(7,'Manager, Open Distance & eLearning (ODeL)','የኦዲኤል ኃላፊ','ODEL_MGR','2026-06-29 07:29:28','2026-06-29 07:29:28'),(8,'Satellite & Learning Sites Manager','የሳተላይት ማእከላት ኃላፊ','SAT_MGR','2026-06-29 07:29:28','2026-06-29 07:29:28'),(9,'Director, Wolayta Campus (SBCE)','የወላይታ ካምፓስ ዳይሬክተር','WOL_DIR','2026-06-29 07:29:28','2026-06-29 07:29:28'),(10,'Operational Manager','የአሠራር ሥራ አስኪያጅ','OPS_MGR','2026-06-29 07:29:28','2026-06-29 07:29:28'),(11,'Finance Manager','የፋይናንስ ኃላፊ','FIN_MGR','2026-06-29 07:29:28','2026-06-29 07:29:28'),(12,'Cashier','ገንዘብ ያዥ (ቆጣሪ)','CASHIER','2026-06-29 07:29:28','2026-06-29 07:29:28'),(13,'Teacher / Faculty','መምህር','FACULTY','2026-06-29 07:29:28','2026-06-29 07:29:28'),(14,'Librarian','የቤተ-መጻሕፍት ኃላፊ','LIBRARIAN','2026-06-29 07:29:28','2026-06-29 07:29:28'),(15,'Storekeeper','ንብረት ክፍል (ስቶር ኪፐር)','STOREKEEPER','2026-06-29 07:29:28','2026-06-29 07:29:28'),(16,'Security Guard','የጥበቃ ሠራተኛ','SECURITY','2026-06-29 07:29:28','2026-06-29 07:29:28'),(17,'Cleaner','የጽዳት ሠራተኛ','CLEANER','2026-06-29 07:29:28','2026-06-29 07:29:28'),(18,'Administrative Support Staff','የአስተዳደር ድጋፍ ሰጪ ሠራተኛ','SUPPORT','2026-06-29 07:29:28','2026-06-29 07:29:28'),(19,'Driver','ሹፌር','DRIVER','2026-06-29 07:29:28','2026-06-29 07:29:28');
/*!40000 ALTER TABLE `positions` ENABLE KEYS */;
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
-- Table structure for table `quarters`
--

DROP TABLE IF EXISTS `quarters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quarters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `year_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quarters_year_id_foreign` (`year_id`),
  CONSTRAINT `quarters_year_id_foreign` FOREIGN KEY (`year_id`) REFERENCES `years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quarters`
--

LOCK TABLES `quarters` WRITE;
/*!40000 ALTER TABLE `quarters` DISABLE KEYS */;
INSERT INTO `quarters` VALUES (1,1,'Q1','2025-07-01','2025-09-30','2026-06-29 07:29:34','2026-06-29 07:29:34'),(2,1,'Q2','2025-10-01','2025-12-31','2026-06-29 07:29:34','2026-06-29 07:29:34'),(3,1,'Q3','2026-01-01','2026-03-31','2026-06-29 07:29:34','2026-06-29 07:29:34'),(4,1,'Q4','2026-04-01','2026-06-30','2026-06-29 07:29:34','2026-06-29 07:29:34');
/*!40000 ALTER TABLE `quarters` ENABLE KEYS */;
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
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(27,1),(28,1),(29,1),(30,1),(31,1),(32,1),(33,1),(34,1),(35,1),(36,1),(37,1),(38,1),(39,1),(40,1),(41,1),(42,1),(43,1),(44,1),(45,1),(46,1),(47,1),(48,1),(49,1),(2,2),(4,2),(10,2),(12,2),(17,2),(19,2),(21,2),(23,2),(34,2),(36,2),(38,2),(40,2),(42,2),(43,2),(46,2),(49,2),(2,3),(5,3),(6,3),(7,3),(8,3),(11,3),(13,3),(15,3),(16,3),(18,3),(41,3),(42,3),(44,3),(45,3),(4,4),(5,4),(8,4),(11,4),(13,4),(15,4),(16,4),(21,4),(22,4),(23,4),(25,4),(26,4),(27,4),(29,4),(31,4),(33,4),(41,4),(42,4),(44,4),(45,4),(48,4),(2,5),(4,5),(11,5),(14,5),(16,5),(20,5),(21,5),(23,5),(25,5),(26,5),(27,5),(29,5),(31,5),(5,6),(8,6),(11,6),(13,6),(15,6),(16,6),(18,6),(35,6),(41,6),(42,6),(44,6),(45,6),(4,7),(16,7),(41,7),(2,8),(11,8),(14,8),(16,8),(20,8),(23,8);
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'President / Super Admin','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(2,'Vice President','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(3,'Dean of the Seminary','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(4,'Operational Manager','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(5,'Finance Officer','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(6,'Department Head','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(7,'Registrar','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(8,'Employee','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(9,'SUPERADMIN','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(10,'ADMIN','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(11,'EDITOR','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(12,'TRAINER','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(13,'STUDENT','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(14,'STAFF','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(15,'LIBRARIAN','web','2026-06-29 07:29:28','2026-06-29 07:29:28'),(16,'USER','web','2026-06-29 07:29:28','2026-06-29 07:29:28');
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
INSERT INTO `sessions` VALUES ('8vdWQWzsD7I1htk3WmZtDjZmrrQtFQfyUbgfmvC7',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiRVlXZW1QNEQxUXdYcnJXWmt3dDM5N0M3eUVobGRYSGZTdjNsaEx6dCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9wYXNzd29yZC9mb3JjZS1jaGFuZ2UiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=',1782843315),('aQkoxopzK1vn8iy9fs6XugXZWMaQxw3xiqtKLpZx',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiR0NjcGN2ZXk3SnpSaXk4TXJ1NGwxTjFUTFRzYUJleDlTWkdLcTZkdSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjY7fQ==',1782729146),('Pnmwx9RWZpFXdj9owdBVyYES3iOjr8KwyVBsu1hV',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoidnZrNkdnTjFlUER4OE16cTZldDBzeW40UDF2dWtEd3hIOHh5dlY2TiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9wYXNzd29yZC9mb3JjZS1jaGFuZ2UiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo2O30=',1782729087),('VmaB0JGCJkZ6DHkZFg09pdUZAwATk07RSXar4QvN',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiT01CQVZTbnY4c2h4YlZrZjZ4TGxrMWtOZWlTZWhuM211YVlNY2tnZyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9wb3J0YWwiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=',1782843769);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `settings_updated_by_foreign` (`updated_by`),
  CONSTRAINT `settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'payroll','working_days_per_month','26','integer','Working days per month (daily/hourly rate basis)',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(2,'payroll','pension_pre_tax','true','boolean','Treat employee pension as pre-tax (statutory). Turn off to match the SITS sheet (pension taxed).',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(3,'payroll','transport_allowance_limit','2200.00','decimal','Non-taxable transport allowance cap (ETB/mo)',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(4,'payroll','food_allowance_limit','0.00','decimal','Non-taxable food allowance cap (ETB/mo)',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(5,'payroll','ot_normal_multiplier','1.50','decimal','Overtime — ordinary',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(6,'payroll','ot_night_multiplier','1.50','decimal','Overtime — night premium',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(7,'payroll','ot_rest_multiplier','2.00','decimal','Overtime — weekly rest day',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(8,'payroll','ot_holiday_multiplier','2.50','decimal','Overtime — public holiday',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(9,'scoring','weight_auto_score','0.40','decimal','Evaluation blend — system/auto',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(10,'scoring','weight_manager_score','0.40','decimal','Evaluation blend — manager/dept head',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(11,'scoring','weight_executive_score','0.20','decimal','Evaluation blend — executive',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(12,'scoring','scoring_formula_version','v1','string','Active scoring formula version',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(13,'scoring','auto_score_weight_tasks','0.40','decimal','Auto-score — task completion weight',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(14,'scoring','auto_score_weight_deliverables','0.25','decimal','Auto-score — deliverable completion weight',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(15,'scoring','auto_score_weight_kpis','0.25','decimal','Auto-score — KPI achievement weight',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(16,'scoring','auto_score_weight_attendance','0.10','decimal','Auto-score — attendance score weight',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(17,'scoring','auto_score_overdue_penalty','10.00','decimal','Auto-score — penalty per overdue task (pts)',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(18,'general','institution_name','SITS Seminary','string','Institution name used across SITS ERP',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(19,'general','default_theme','dark','string','Default UI theme for the application (light/dark)',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(20,'general','default_language','en','string','Default language fallback for translations',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(21,'localization','bilingual_mode','true','boolean','Enable side-by-side English and Amharic translation',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(22,'localization','primary_locale','en','string','Primary system localization setting',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(23,'ai','ai_enabled','true','boolean','Enable AI narrative and performance analysis features',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(24,'ai','ai_default_provider','gemini_pro','string','Active AI service provider (claude_pro, gemini_pro, or mock)',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(25,'ai','gemini_pro_api_key','','string','Google Gemini Pro API key',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(26,'ai','gemini_pro_model','gemini-2.0-flash','string','Active Google Gemini model',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(27,'ai','claude_pro_api_key','','string','Anthropic Claude Pro API key',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(28,'ai','claude_pro_model','claude-opus-4-8','string','Active Anthropic Claude model',0,NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `strategies`
--

DROP TABLE IF EXISTS `strategies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `strategies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `year_id` bigint(20) unsigned NOT NULL,
  `pillar` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `strategies_year_id_foreign` (`year_id`),
  CONSTRAINT `strategies_year_id_foreign` FOREIGN KEY (`year_id`) REFERENCES `years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `strategies`
--

LOCK TABLES `strategies` WRITE;
/*!40000 ALTER TABLE `strategies` DISABLE KEYS */;
INSERT INTO `strategies` VALUES (1,1,'program_delivery','Program Delivery',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(2,1,'enrollment','Enrollment Growth',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(3,1,'finance','Financial Health',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(4,1,'organizational_capacity','Organizational Capacity',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28'),(5,1,'governance','Governance',NULL,'2026-06-29 07:29:28','2026-06-29 07:29:28');
/*!40000 ALTER TABLE `strategies` ENABLE KEYS */;
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
-- Table structure for table `targets`
--

DROP TABLE IF EXISTS `targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `targets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `goal_id` bigint(20) unsigned NOT NULL,
  `year_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `budget` decimal(12,2) NOT NULL DEFAULT 0.00,
  `value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `targets_goal_id_foreign` (`goal_id`),
  KEY `targets_year_id_foreign` (`year_id`),
  CONSTRAINT `targets_goal_id_foreign` FOREIGN KEY (`goal_id`) REFERENCES `goals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `targets_year_id_foreign` FOREIGN KEY (`year_id`) REFERENCES `years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `targets`
--

LOCK TABLES `targets` WRITE;
/*!40000 ALTER TABLE `targets` DISABLE KEYS */;
/*!40000 ALTER TABLE `targets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_progress_reports`
--

DROP TABLE IF EXISTS `task_progress_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_progress_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `completion_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `narrative_report_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_progress_reports_task_id_foreign` (`task_id`),
  KEY `task_progress_reports_narrative_report_id_index` (`narrative_report_id`),
  CONSTRAINT `task_progress_reports_narrative_report_id_foreign` FOREIGN KEY (`narrative_report_id`) REFERENCES `narrative_reports` (`id`) ON DELETE SET NULL,
  CONSTRAINT `task_progress_reports_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_progress_reports`
--

LOCK TABLES `task_progress_reports` WRITE;
/*!40000 ALTER TABLE `task_progress_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_progress_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_user`
--

DROP TABLE IF EXISTS `task_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_user_task_id_user_id_unique` (`task_id`,`user_id`),
  KEY `task_user_user_id_foreign` (`user_id`),
  CONSTRAINT `task_user_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_user`
--

LOCK TABLES `task_user` WRITE;
/*!40000 ALTER TABLE `task_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `target_id` bigint(20) unsigned DEFAULT NULL,
  `parent_task_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `cadence` varchar(255) NOT NULL DEFAULT 'fortnightly',
  `starting_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `weight` decimal(5,2) NOT NULL DEFAULT 1.00,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `completion_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `assigned_by_id` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_employee_id_foreign` (`employee_id`),
  KEY `tasks_target_id_foreign` (`target_id`),
  KEY `tasks_parent_task_id_foreign` (`parent_task_id`),
  KEY `tasks_created_by_foreign` (`created_by`),
  KEY `tasks_assigned_by_id_foreign` (`assigned_by_id`),
  CONSTRAINT `tasks_assigned_by_id_foreign` FOREIGN KEY (`assigned_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_parent_task_id_foreign` FOREIGN KEY (`parent_task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_target_id_foreign` FOREIGN KEY (`target_id`) REFERENCES `targets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_brackets`
--

DROP TABLE IF EXISTS `tax_brackets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tax_brackets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `min_income` decimal(12,2) NOT NULL,
  `max_income` decimal(12,2) DEFAULT NULL,
  `rate` decimal(5,2) NOT NULL,
  `deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `effective_from` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_brackets`
--

LOCK TABLES `tax_brackets` WRITE;
/*!40000 ALTER TABLE `tax_brackets` DISABLE KEYS */;
INSERT INTO `tax_brackets` VALUES (1,0.00,2000.00,0.00,0.00,1,'2025-07-07','2026-06-29 07:29:28','2026-06-29 07:29:28'),(2,2000.01,4000.00,15.00,300.00,1,'2025-07-07','2026-06-29 07:29:28','2026-06-29 07:29:28'),(3,4000.01,7000.00,20.00,500.00,1,'2025-07-07','2026-06-29 07:29:28','2026-06-29 07:29:28'),(4,7000.01,10000.00,25.00,850.00,1,'2025-07-07','2026-06-29 07:29:28','2026-06-29 07:29:28'),(5,10000.01,14000.00,30.00,1350.00,1,'2025-07-07','2026-06-29 07:29:28','2026-06-29 07:29:28'),(6,14000.01,NULL,35.00,2050.00,1,'2025-07-07','2026-06-29 07:29:28','2026-06-29 07:29:28');
/*!40000 ALTER TABLE `tax_brackets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `terminations`
--

DROP TABLE IF EXISTS `terminations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terminations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `reason` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `effective_date` date NOT NULL,
  `initiated_by` bigint(20) unsigned DEFAULT NULL,
  `initiated_at` datetime DEFAULT NULL,
  `finalized_by` bigint(20) unsigned DEFAULT NULL,
  `finalized_at` datetime DEFAULT NULL,
  `severance_amount` decimal(12,2) DEFAULT NULL,
  `severance_notes` text DEFAULT NULL,
  `final_payslip_id` bigint(20) unsigned DEFAULT NULL,
  `handover_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`handover_checklist`)),
  `handover_completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `terminations_initiated_by_foreign` (`initiated_by`),
  KEY `terminations_finalized_by_foreign` (`finalized_by`),
  KEY `terminations_final_payslip_id_foreign` (`final_payslip_id`),
  KEY `terminations_employee_id_index` (`employee_id`),
  KEY `terminations_status_index` (`status`),
  KEY `terminations_effective_date_index` (`effective_date`),
  CONSTRAINT `terminations_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `terminations_final_payslip_id_foreign` FOREIGN KEY (`final_payslip_id`) REFERENCES `payslips` (`id`) ON DELETE SET NULL,
  CONSTRAINT `terminations_finalized_by_foreign` FOREIGN KEY (`finalized_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `terminations_initiated_by_foreign` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terminations`
--

LOCK TABLES `terminations` WRITE;
/*!40000 ALTER TABLE `terminations` DISABLE KEYS */;
/*!40000 ALTER TABLE `terminations` ENABLE KEYS */;
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
  `phone` varchar(255) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `password_changed` tinyint(1) NOT NULL DEFAULT 0,
  `default_password` text DEFAULT NULL,
  `password_reset_requested_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'SITS Administrator','admin@sits.edu',NULL,NULL,NULL,1,1,1,NULL,NULL,'2026-06-29 07:29:28','$2y$12$8.gIJDDWxywS7TveQAJjhOALnxlXMAyg5CT4UtCXdVcbaUpQkbe/a',NULL,'2026-06-29 07:29:28','2026-06-30 15:15:29'),(2,'Endale Sebsebe Mekonnen','esebsebe@yahoo.com',NULL,NULL,NULL,1,1,0,'eyJpdiI6Ijh4cWZNRC9sd1R5Ny84c3hVOS9xQkE9PSIsInZhbHVlIjoib0Fta1lMcFROTU9wdUVGNFZhUy9kZz09IiwibWFjIjoiOWRkYjY0N2U4ZjliZGMzNmZjY2FmN2ZjNThlZDMwMWQ3M2RjMTI5NWI5MzUzMzRmNGQ5ZWM0ZDhjNTNkMmRiMCIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:29','$2y$12$lmwl881PYrfedU0OtUuUV.UQvZYNXHLYZ.3BMYPZXy.f72noywAnS',NULL,'2026-06-29 07:29:29','2026-06-29 07:29:29'),(3,'Zerubbabel Zeleke','zelekezeru@gmail.com',NULL,NULL,NULL,1,1,0,'eyJpdiI6Im9GT3VnekN1MTVDekVyNlBrOXErQXc9PSIsInZhbHVlIjoiTnU3ekNVZWh1U0ZGeHgzWDJFUitsQT09IiwibWFjIjoiNTJlNTUyYjliYzAyZjgwYmVkNjUyMmY3ZGQ5MjBiOTc0MWFkYTBjMjExMGE0OTYwMmYxM2RmYTQ1OTQ4ZTc2ZCIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:29','$2y$12$0ePGkElqjA6pzGN9dCDikumtHCO0UKjVm/jWqwx8k5BQY1JmAcP4q',NULL,'2026-06-29 07:29:29','2026-06-29 07:29:29'),(4,'Abate Dejene Lemma','abeyeenatu1980@gmail.com',NULL,NULL,NULL,1,1,0,'eyJpdiI6IlRFWnk5Q2plN2tLcTZyQkVDYnFlWlE9PSIsInZhbHVlIjoiZUJhK0xEaGhIbDU2QmZ4WG9sWC9ndz09IiwibWFjIjoiYzJiNTE2ZTlhNTk4NzcyYjVhN2IzY2U3M2Y1ZjgyZmJjODcwZGE5MDUyYzA2ZGU4YzZkMDFmMTZhNWY2MWE1MyIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:29','$2y$12$E9DZco0DevyTC/MYpOCcHuVsknqBm9jvFWHRCIhrcLnX7krvRoVI6',NULL,'2026-06-29 07:29:29','2026-06-29 07:29:29'),(5,'Abenezer Ayalew Mekonnen','abensew13@gmail.com',NULL,NULL,NULL,1,1,0,'eyJpdiI6Im1xcXliSjJ4QmYvUXBCSFRldGdpZXc9PSIsInZhbHVlIjoiZzN6V0J3NVNEZmI2dnp6OFVReE14dz09IiwibWFjIjoiZjg3NmE1ODgyNmQ5YzRjMzg3NmY4MDM4MmQxYWVkODBhMWE4NTgxMTEzYjhiNmZjYTFmOGVkNzQ1MDhiZTgxMiIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:29','$2y$12$WwmSqJAKm0noKOIAk0X/Nu/QxxGXUCsm.8f9vHutM6/PZlOxwm9za',NULL,'2026-06-29 07:29:29','2026-06-29 07:29:29'),(6,'Alte Agegnew Tadese','agegnehualte@gmail.com',NULL,NULL,NULL,1,1,1,NULL,NULL,'2026-06-29 07:29:29','$2y$12$Tk48Mk5ex.kZkXFhHuXIB.0fdwasUX1oAN.nzEzfuype0PyuRHSqi',NULL,'2026-06-29 07:29:29','2026-06-29 07:31:40'),(7,'Amarech Abrham','amarech.abrham@sits.edu.et',NULL,NULL,NULL,1,1,0,'eyJpdiI6Im84Y2RkV3cxYmZvdXQyWHM3eUR0QUE9PSIsInZhbHVlIjoiUjc2Ymtta3U1bU52N1lrK0JwS2tVQT09IiwibWFjIjoiZjM5OTAzN2VhNjdjNzA3MTc2YThhODAzOWE5OGM1ZTkxZGUzM2M0Yjk5MWY3NWRiNmFmNTE5MDQ2ZWEwZDg3NCIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:30','$2y$12$UJnk4GN9uFDci25.EeTAguwywvASmwWZk4TEWjn4EslENrjhYAyzq',NULL,'2026-06-29 07:29:30','2026-06-29 07:29:30'),(8,'Amarech Otoro','amarech.otoro@sits.edu.et',NULL,NULL,NULL,1,1,0,'eyJpdiI6InBvZGZiRDhZTzltRStReTdPc25FM0E9PSIsInZhbHVlIjoiR0hyM3ZaVmd3YVNDaU9DMEhuUm9Ddz09IiwibWFjIjoiYzk3YjY4NWY0Mzc4YjYxZmVhMmM2Y2I1MTg4NjA4M2QyYjE2Y2EwNjMzMWZiNzYyMzdmZjYxMTdjZmIxMzg0YyIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:30','$2y$12$s344Nan6PwF3KYFXLUlZruoyQ9WV1d/i1iCTp4enfewtCflabEuQa',NULL,'2026-06-29 07:29:30','2026-06-29 07:29:30'),(9,'Azeb Buche','azeb.buche@sits.edu.et',NULL,NULL,NULL,1,1,0,'eyJpdiI6ImpNU2x6S095L05uSGZvb2RuQVQ2TkE9PSIsInZhbHVlIjoibk5tOG5McWh4Ui9aZGJUWlNleGR6dz09IiwibWFjIjoiNjNkNjU5OGI2OTc1MDM2NDUwNThlOTJjOGViOTQ1OGRlNmU5ZGJkMjJjY2QyODY5YTJhZGNmNzMwMjIzY2I4MyIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:30','$2y$12$3OO0Q9NSGTk64qgkWuuhrOKkUK2PpuLUqM4.vkpL07hHmM05zY6kS',NULL,'2026-06-29 07:29:30','2026-06-29 07:29:30'),(10,'Birhanu Gelaye','birhanu.gelaye@sits.edu.et',NULL,NULL,NULL,1,1,0,'eyJpdiI6IlpBTlBiTmJFYk1XcXFHTDdiUXQ5N0E9PSIsInZhbHVlIjoiVlkzZHBEU09NN3BxeXcvN0FrbW5hZz09IiwibWFjIjoiOTQ2YzQ0ZDhmMTEyMjFhZjhhYWVkMTY2N2M0NGE0YjA2NmY4YTFlNTVhYjMxMDI2ZmE5NGUzYzM0ZTQ3ZDAwYyIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:30','$2y$12$GlOZXrTmG2sQ9No61nWfn.J4e7lr6Py5S9V9ceAdDKazp//6z.Qb.',NULL,'2026-06-29 07:29:30','2026-06-29 07:29:30'),(11,'Elfinesh Yadesa','elfinesh.yadesa@sits.edu.et',NULL,NULL,NULL,1,1,0,'eyJpdiI6IlFpa0FPMjZXbGhqYzRsVm5JZFlCclE9PSIsInZhbHVlIjoibnN5UmxkN3FwUkdNZys4T1A1S3ppdz09IiwibWFjIjoiYzUyMDlhZDBkNDIxNDIzODkzMzc5NGE2YWVkNTE1NDY1YzllOGE0NDkxMWQ1YWVjZDg4OTU4YjliOTY0Njg5ZCIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:31','$2y$12$4vWvai4NmmcGgRFhc1iZOu5N7NyjZUMkbj2OhFj4jrmuhilNmNdcK',NULL,'2026-06-29 07:29:31','2026-06-29 07:29:31'),(12,'Geda Tufule','gedatufule9@gmail.com',NULL,NULL,NULL,1,1,0,'eyJpdiI6ImFuVy9Ed0ltTVFCTExqYVNZc01SVnc9PSIsInZhbHVlIjoiVVFNbG5QQlZTbkl2aGFqUDRvVlRXUT09IiwibWFjIjoiYWIxODFjNTFkM2Y0MTRkNWI4NTcyZGRlMTNjNmRmZmEwOTBiZDU1MjAwZmNkNGY5ZjBkZmZjMDc0MzhlMjk1NCIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:31','$2y$12$gVYy769S8y3J29w9XV2U1OlX0skFWGBHFcWRObMt3/WMtUTRNye92',NULL,'2026-06-29 07:29:31','2026-06-29 07:29:31'),(13,'Kalkidan Eshetu','eshetukalkidan704@gmail.com',NULL,NULL,NULL,1,1,0,'eyJpdiI6IjdlYTFTQmoyWFd0Qkc3Um9CRE56Unc9PSIsInZhbHVlIjoialVRMjVZWUo1eVdLbjhEVnpqaURzQT09IiwibWFjIjoiZGFhYWVlZjNiYTk2ZDU1YzA5OTg3YjBjMDNmODc1ODFiYmUyNjMyNTU5MWVkNDE2YzlhNGMxNzg2MWQ3YzlkOSIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:31','$2y$12$U/uAsc92L9Y6Dne7PJpxbOpB6pN8/2wvDNm056SEc2IxqFhQ210hq',NULL,'2026-06-29 07:29:31','2026-06-29 07:29:31'),(14,'Mesele Dawit','mesele.dawit@sits.edu.et',NULL,NULL,NULL,1,1,0,'eyJpdiI6InpPZFFKTTB3MEE2N0tNcytJcEZDL1E9PSIsInZhbHVlIjoibE1SN2llOW1WbVJQZ3F3TElkVnFWZz09IiwibWFjIjoiYWFlMDVlOGI5MjY5ZTY3YTFhNDE5YTJlMWJiZmNlYmU2NzFkOThmM2IyODM3YzBhNDc2MDJjNDBkYzdkNTZjNSIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:31','$2y$12$H//5fHYWzVbVotyT0ISfs./H0Evj1MlEXo5cXMlVAB3Ase5jKG5qm',NULL,'2026-06-29 07:29:31','2026-06-29 07:29:31'),(15,'Meskerem Aseffa','lebamesew@gmail.com',NULL,NULL,NULL,1,1,0,'eyJpdiI6IjNML1c0dmVBR1MxVjVjV2ozVmNXelE9PSIsInZhbHVlIjoiUGpmY0FweEFla0hDZXNUcVV2Nisxdz09IiwibWFjIjoiMDE0MmY0YTBiMjgzYmJmZjI0MWJmMGYwYzM5N2NmN2ZjOWZkOWJhZGNhODdjY2JmMzJkZDY3NTg1NzZlNDUzYiIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:32','$2y$12$vjsSwyUAPYrS8TzWB0Ac/eSEEO7GgE47kM7QOBAI/IU1Rsya3.6J2',NULL,'2026-06-29 07:29:32','2026-06-29 07:29:32'),(16,'Mesganu Petros','pmesge@gmail.com',NULL,NULL,NULL,1,1,0,'eyJpdiI6Iktic2RUcWJVZmM0WGxsK2xZRU82clE9PSIsInZhbHVlIjoiTzJYOTUwKzhiRmM3NTJPblZrb3MvUT09IiwibWFjIjoiYjkzYWMyODVkZjk1OGEwZTgxMDIxYmVmMzJmNGVmNjk4MmYwNzVlMzMyZmE5YjEzN2Y2MDI2ODVhZDQzMDQ1NyIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:32','$2y$12$P89N6.Qz0wq1FutUL9Hy7.Yx0/O2VCTW8xEaTwGsCf4AHRdrL/ij6',NULL,'2026-06-29 07:29:32','2026-06-29 07:29:32'),(17,'Misale Getu Ayalew','mesalegetu@yahoo.com',NULL,NULL,NULL,1,1,0,'eyJpdiI6IkR1MnVuS2ZJd1NBdFF0djRqdmlpMEE9PSIsInZhbHVlIjoiY1FCME9TWW42SUhRT21Zb0FqOXBrZz09IiwibWFjIjoiMTQ3OGJhMjhiMmM1ZDJiZWY4YjE1ZTkzMWU4ODY4NmFmYzRkOTdhN2UxN2Q3ZmI0NDE4YzRlOGE3YThmNmMyMyIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:32','$2y$12$p2niT8Py.JJUwPqd9.7mte2gjIyhhQPDpv.nYgkRU8rcdDLukhfI2',NULL,'2026-06-29 07:29:32','2026-06-29 07:29:32'),(18,'Pastor Zekariyas Chinasho','zekariyas.chinasho@sits.edu.et',NULL,NULL,NULL,1,1,0,'eyJpdiI6Im1zejZKUjFLT1UybjJxYWNpWjM5L3c9PSIsInZhbHVlIjoia1J0b2k5dk11MEpmZHBXMVhZeGJNdz09IiwibWFjIjoiOWFmMTI2YjQxNjY4MDE3NDc4ZDk0ZGJiNGIyNzI4OGFiMjg4MTZlZmQyMTMwOTZhMTczYzlmNTU3NjRiODRiMCIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:32','$2y$12$c8cah/tt8nN.nM4hrprROu7WNYzeABpEuq99KtMxRv7Mz3sc3gEDS',NULL,'2026-06-29 07:29:32','2026-06-29 07:29:32'),(19,'Selamawit Yared','yaredselamawit@yahoo.com',NULL,NULL,NULL,1,1,0,'eyJpdiI6IlMrdzJocE5vaUtPSWlQNVR0QTdLd0E9PSIsInZhbHVlIjoid2l3ZTd5VEcrRnN3K0pSVHdLaExIdz09IiwibWFjIjoiYzkzNjg4NWQ3NTY2MmU5ZTY2N2U0YmUwN2M2ZmU1OWViNGIyNjc5YWJkMjA3MzBiNGViM2ZlMjZhODQzODkwZiIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:32','$2y$12$pYR3uVeUhQBpwQBkOPBi0.zfTYwX92uskgveWUThXr1CDBNRqQTvW',NULL,'2026-06-29 07:29:32','2026-06-29 07:29:32'),(20,'Tamiru Lijalem','lijalem.tamiru@gmail.com',NULL,NULL,NULL,1,1,0,'eyJpdiI6ImdUbzNkbTEyODJkMHplL280eUVEY3c9PSIsInZhbHVlIjoiMlRKZzNsMTdlSExUZ0tZQk4wcGtsdz09IiwibWFjIjoiOGU3YTQzZGNlZjdjNWM4MzQ1ZWIzZThjMmFmYjg2NjMzMGRmZWM5ZTBhMTg2NjllNzMwMjRlMGNmOTljNGJhOSIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:33','$2y$12$nzJeVmz4v/rRNj6Wm.tQ4eG/4m.hlZg6WfYQtE5gBEGk4vagJO/Fe',NULL,'2026-06-29 07:29:33','2026-06-29 07:29:33'),(21,'Tesfaye Gebre','tesfaye.gebre@sits.edu.et',NULL,NULL,NULL,1,1,0,'eyJpdiI6IjlXdXhoSDQvc05nNW9tb3lvWURVY3c9PSIsInZhbHVlIjoic3ZQK3FETk1wU1dNWnY5R3Z6ZUJvQT09IiwibWFjIjoiMWFmZmU0NmJlNmIwZmUyNmVjMWIzYTcxZTI5ODI4NjhjYzFlMTliZTFlMTllYzlkNTM0MTk2OTIxMDc3ZDA0OSIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:33','$2y$12$7.JYSNG.iWvip3bTZL8drObL/yRtaDqtgJyaUjCjQvLvCe5E2AbTm',NULL,'2026-06-29 07:29:33','2026-06-29 07:29:33'),(22,'Tesfaye Gebre Oke','tesfayegebre18@gmail.com',NULL,NULL,NULL,1,1,0,'eyJpdiI6Imdnc2MyL3lyalhPbStYQVhoYnFKZmc9PSIsInZhbHVlIjoiemhNL2R0ZDd6TE90Yy95ZkZ2MndiZz09IiwibWFjIjoiZGZkYTA3YzJiYzhhYzU4ZDEyNzNkMmNjNWYzYzZkY2FiZWJmOTY0ZjE3ZmJlN2U4YmYxNWUzMjRiMWZhMGU1NiIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:33','$2y$12$RvJrVNfHEf9B6WPFrNi.0.xbJ0IJ2IMEzP.JQXSmJVe7oZ66xFiN6',NULL,'2026-06-29 07:29:33','2026-06-29 07:29:33'),(23,'Yetnayet Nigatu Entele','yetnayet.nigatu@sits.edu.et',NULL,NULL,NULL,1,1,0,'eyJpdiI6IlRkRjRIbnZoNkRrWHBaS3ppaDEwZGc9PSIsInZhbHVlIjoiekppc1lzbzNlTkFsazRSZFJqOW1vdz09IiwibWFjIjoiZDI2ZjQ0MzJjMjdjOTlkYzIxMjQ2M2UyODA1MmNlZTNlNjNmM2ZjY2FiYTBlOWY5MmY0YTg2N2MyYjViZjVkMyIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:33','$2y$12$WR7HfnIj/eLad61hB5MVnuQuBESNHyrhXUKz7wpTWzdUZU1JGKk1q',NULL,'2026-06-29 07:29:33','2026-06-29 07:29:33'),(24,'Yilma Gezmu Mengesha','yilmagezmu@yahoo.com',NULL,NULL,NULL,1,1,0,'eyJpdiI6IkN2Vk5aRGwydEZFemtNNHV6KzhRemc9PSIsInZhbHVlIjoiRkNFTklFYVp0eDVDdi82ZXI2UVUrUT09IiwibWFjIjoiNTAxMzI1MWQ5ZDhkMjk5YjRlYzRjYjkzNTA1M2RmNGU3NDE4MzEzZDEyMTYwN2RjYTYzOTE4OTI0ZTMzMTFiYiIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:34','$2y$12$wRj7c3GYIFmJqp2Dr1tQx..cpPeN3Sc06W9E7EU/Nl/2tmPKW2GCS',NULL,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(25,'Zeleke Abisso','abissozeleke@gmail.com',NULL,NULL,NULL,1,1,0,'eyJpdiI6IkMxeXZIMTdnOFZhdE9nbE5ibHF0ZWc9PSIsInZhbHVlIjoibVFIRjNPZ0tDaDVVbUVTNnVLUTJOQT09IiwibWFjIjoiZTYxZmI4YjM1OWFlMWRkNmJmNjZhMjA2ZjE3N2U2YjFlMjRhNmFhNDFlMmE2NDM1ZGZhYTIyNmNkYWY0YmJhZiIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:34','$2y$12$AWo.Hgn5AgH.QGRqUgYNWeeUg9hHerwYRqGsxhmL7tcWaDGkE./Ue',NULL,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(26,'Zewude Zeleke','zewude.zeleke@sits.edu.et',NULL,NULL,NULL,1,1,0,'eyJpdiI6ImdZWC9tbzJCdnIvbGJwTkRnWXV1L0E9PSIsInZhbHVlIjoiN0IwK0ZscnVOakZQR0dpTUY0cWRtUT09IiwibWFjIjoiOGY0OWQyNzgwNTdlNjdhZWNjOWE1YjMxMTYxNmE4YzRhZDRlZjdhMjEwZmQ4NmVlZTY2OWZlMWZjMmM4ZmQyZCIsInRhZyI6IiJ9',NULL,'2026-06-29 07:29:34','$2y$12$4gEPEnmzBalYUU.p2XOInemw.c7E6PC04iXqlsYWNI/tK3bW4RXb.',NULL,'2026-06-29 07:29:34','2026-06-29 07:29:34'),(27,'SITS System Admin','admin@sits.edu.et',NULL,NULL,NULL,1,1,1,NULL,NULL,'2026-06-29 07:29:35','$2y$12$YCgoCmS.Kdpk0RWdV34gBeAr.b991xD/n7HBQlIXxtf.T7jDFdRx2',NULL,'2026-06-29 07:29:35','2026-06-29 07:29:35');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `years`
--

DROP TABLE IF EXISTS `years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `years` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `years`
--

LOCK TABLES `years` WRITE;
/*!40000 ALTER TABLE `years` DISABLE KEYS */;
INSERT INTO `years` VALUES (1,'FY2026','2025-07-01','2026-06-30',1,'2026-06-29 07:29:28','2026-06-29 07:29:28');
/*!40000 ALTER TABLE `years` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'sits_unified'
--

--
-- Dumping routines for database 'sits_unified'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-30 21:23:10
