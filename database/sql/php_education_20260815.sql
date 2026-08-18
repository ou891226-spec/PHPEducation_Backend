CREATE DATABASE  IF NOT EXISTS `php_education` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `php_education`;
-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: php_education
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '管理員 ID',
  `account` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '登入帳號',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '加密後的密碼',
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_account_unique` (`account`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin@school.edu.tw','$2y$12$BdjKBDpHtL0VF.1RysbDM.VGFDaLYmzvdMKNbmT4AwrPEPRs51JSO');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chapters`
--

DROP TABLE IF EXISTS `chapters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chapters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '章節 ID',
  `topic_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '章節名稱',
  `sort_order` int NOT NULL COMMENT '排序順序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `chapters_topic_id_foreign` (`topic_id`),
  CONSTRAINT `chapters_topic_id_foreign` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chapters`
--

LOCK TABLES `chapters` WRITE;
/*!40000 ALTER TABLE `chapters` DISABLE KEYS */;
/*!40000 ALTER TABLE `chapters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '課程 ID',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '課程名稱',
  `semester` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '開課學期',
  `teacher_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `courses_teacher_id_foreign` (`teacher_id`),
  CONSTRAINT `courses_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,'陳老師的課程','115-1',2,'2026-08-14 16:28:18','2026-08-14 16:28:18');
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enrollments` (
  `student_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`student_id`,`course_id`),
  KEY `enrollments_course_id_foreign` (`course_id`),
  CONSTRAINT `enrollments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollments`
--

LOCK TABLES `enrollments` WRITE;
/*!40000 ALTER TABLE `enrollments` DISABLE KEYS */;
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knowledge_cards`
--

DROP TABLE IF EXISTS `knowledge_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_cards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '知識卡 ID',
  `unit_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '知識卡標題',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '知識卡內容',
  `sort_order` int NOT NULL COMMENT '排序順序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `knowledge_cards_unit_id_foreign` (`unit_id`),
  CONSTRAINT `knowledge_cards_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_cards`
--

LOCK TABLES `knowledge_cards` WRITE;
/*!40000 ALTER TABLE `knowledge_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `knowledge_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_08_14_000001_create_admins_table',1),(2,'2026_08_14_000002_create_teachers_table',1),(3,'2026_08_14_000003_create_students_table',1),(4,'2026_08_14_000005_create_courses_table',1),(5,'2026_08_14_000006_create_personal_access_tokens_table',1),(6,'2026_08_14_000007_create_enrollments_table',1),(7,'2026_08_14_000008_create_topics_table',1),(8,'2026_08_14_000009_create_chapters_table',1),(9,'2026_08_14_000010_create_units_table',1),(10,'2026_08_14_000011_create_knowledge_cards_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Token ID',
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Token 所屬模型類型',
  `tokenable_id` bigint unsigned NOT NULL COMMENT 'Token 所屬模型 ID',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Token 名稱',
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Token 雜湊值',
  `abilities` text COLLATE utf8mb4_unicode_ci COMMENT '權限範圍',
  `last_used_at` timestamp NULL DEFAULT NULL COMMENT '最後使用時間',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT '過期時間',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '學生 ID',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '加密後的密碼',
  `student_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '學號（同時作為登入帳號）',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '學生姓名',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Email',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_student_no_unique` (`student_no`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'$2y$12$BdjKBDpHtL0VF.1RysbDM.VGFDaLYmzvdMKNbmT4AwrPEPRs51JSO','1411131000','王小明','1411131000@student.school.edu.tw','2026-08-14 16:28:18','2026-08-14 16:28:18');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teachers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '教師 ID',
  `account` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '登入帳號',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '加密後的密碼',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '教師姓名',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Email',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `teachers_account_unique` (`account`),
  UNIQUE KEY `teachers_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT INTO `teachers` VALUES (1,'teacher@school.edu.tw','$2y$12$BdjKBDpHtL0VF.1RysbDM.VGFDaLYmzvdMKNbmT4AwrPEPRs51JSO','許老師','teacher@school.edu.tw','2026-08-14 16:28:18','2026-08-14 16:28:18'),(2,'teacher2@school.edu.tw','$2y$12$BdjKBDpHtL0VF.1RysbDM.VGFDaLYmzvdMKNbmT4AwrPEPRs51JSO','陳老師','teacher2@school.edu.tw','2026-08-14 16:28:18','2026-08-14 16:28:18');
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topics`
--

DROP TABLE IF EXISTS `topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `topics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主題 ID',
  `course_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主題名稱',
  `sort_order` int NOT NULL COMMENT '排序順序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `topics_course_id_foreign` (`course_id`),
  CONSTRAINT `topics_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topics`
--

LOCK TABLES `topics` WRITE;
/*!40000 ALTER TABLE `topics` DISABLE KEYS */;
/*!40000 ALTER TABLE `topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '單元 ID',
  `chapter_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '單元名稱',
  `sort_order` int NOT NULL COMMENT '排序順序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `units_chapter_id_foreign` (`chapter_id`),
  CONSTRAINT `units_chapter_id_foreign` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-15  0:57:19
