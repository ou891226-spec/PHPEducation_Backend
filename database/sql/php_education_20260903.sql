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
INSERT INTO `admins` VALUES (1,'admin@school.edu.tw','$2y$12$8UXZXq.2aoxVnZm92Ic8jOSdkljheDQ.A21MeBPJEHzfhT9JtJuai');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bloom`
--

DROP TABLE IF EXISTS `bloom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bloom` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Bloom 編碼',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Bloom 標題',
  `cognition_info` text COLLATE utf8mb4_unicode_ci COMMENT '認知層級說明',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bloom`
--

LOCK TABLES `bloom` WRITE;
/*!40000 ALTER TABLE `bloom` DISABLE KEYS */;
INSERT INTO `bloom` VALUES ('B1','記憶','回憶事實、名詞與基本概念','2026-09-03 08:01:06','2026-09-03 08:01:06'),('B11','記憶（事實/定義）','回憶事實、名詞與基本概念','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B12','記憶（事實/定義）','回憶事實、名詞與基本概念','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B13','記憶（事實/定義）','回憶事實、名詞與基本概念','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B2','理解','解釋、摘要並說明意義','2026-09-03 08:01:06','2026-09-03 08:01:06'),('B21','理解（解釋/說明）','解釋、摘要並說明意義','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B22','理解（解釋/說明）','解釋、摘要並說明意義','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B23','理解（解釋/說明）','解釋、摘要並說明意義','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B3','應用','在新情境使用程序或方法','2026-09-03 08:01:06','2026-09-03 08:01:06'),('B31','應用（程式實作/填空）','在新情境使用程序或方法','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B32','應用（程式實作/填空）','在新情境使用程序或方法','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B33','應用（程式實作/填空）','在新情境使用程序或方法','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B4','分析','拆解、比較並找出關係','2026-09-03 08:01:06','2026-09-03 08:01:06'),('B41','分析（程式除錯/判讀）','拆解、比較並找出關係','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B42','分析（程式除錯/判讀）','拆解、比較並找出關係','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B43','分析（程式除錯/判讀）','拆解、比較並找出關係','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B5','評鑑','依規準判斷與評論','2026-09-03 08:01:06','2026-09-03 08:01:06'),('B51','評鑑（判斷/評論）','依規準判斷與評論','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B52','評鑑（判斷/評論）','依規準判斷與評論','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B53','評鑑（判斷/評論）','依規準判斷與評論','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B6','創造','重組、設計並產出新結構','2026-09-03 08:01:06','2026-09-03 08:01:06'),('B61','創造（設計/產出）','重組、設計並產出新結構','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B62','創造（設計/產出）','重組、設計並產出新結構','2026-09-03 08:01:08','2026-09-03 08:01:08'),('B63','創造（設計/產出）','重組、設計並產出新結構','2026-09-03 08:01:08','2026-09-03 08:01:08');
/*!40000 ALTER TABLE `bloom` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chapters`
--

DROP TABLE IF EXISTS `chapters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chapters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '章節 ID',
  `course_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '章節名稱',
  `sort_order` int NOT NULL COMMENT '排序順序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `chapters_course_id_foreign` (`course_id`),
  CONSTRAINT `chapters_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
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
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '課程介紹',
  `semester` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '開課學期',
  `class_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '開課班級',
  `teacher_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `courses_teacher_id_foreign` (`teacher_id`),
  CONSTRAINT `courses_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,'網際系統設計','資應班網際系統設計課程','115-1','資應',2,'2026-09-03 08:01:09','2026-09-03 08:01:09'),(2,'網際系統設計','資管班網際系統設計課程','115-1','資管',2,'2026-09-03 08:01:09','2026-09-03 08:01:09');
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
INSERT INTO `enrollments` VALUES (1,1);
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knowledge_card_unit`
--

DROP TABLE IF EXISTS `knowledge_card_unit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_card_unit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '關聯 ID',
  `unit_id` bigint unsigned NOT NULL,
  `knowledge_card_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `knowledge_card_unit_unit_id_knowledge_card_id_unique` (`unit_id`,`knowledge_card_id`),
  KEY `knowledge_card_unit_knowledge_card_id_foreign` (`knowledge_card_id`),
  CONSTRAINT `knowledge_card_unit_knowledge_card_id_foreign` FOREIGN KEY (`knowledge_card_id`) REFERENCES `knowledge_cards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `knowledge_card_unit_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_card_unit`
--

LOCK TABLES `knowledge_card_unit` WRITE;
/*!40000 ALTER TABLE `knowledge_card_unit` DISABLE KEYS */;
/*!40000 ALTER TABLE `knowledge_card_unit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knowledge_cards`
--

DROP TABLE IF EXISTS `knowledge_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_cards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '知識卡 ID',
  `unit_id` bigint unsigned DEFAULT NULL,
  `course_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '知識卡標題',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'keyword' COMMENT '知識卡類型，例如 keyword、function',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '知識卡內容',
  `example` text COLLATE utf8mb4_unicode_ci COMMENT '知識卡範例',
  `sort_order` int NOT NULL COMMENT '排序順序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `knowledge_cards_unit_id_foreign` (`unit_id`),
  KEY `knowledge_cards_course_id_foreign` (`course_id`),
  CONSTRAINT `knowledge_cards_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `knowledge_cards_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
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
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_08_14_000001_create_admins_table',1),(2,'2026_08_14_000002_create_teachers_table',1),(3,'2026_08_14_000003_create_students_table',1),(4,'2026_08_14_000005_create_courses_table',1),(5,'2026_08_14_000006_create_personal_access_tokens_table',1),(6,'2026_08_14_000007_create_enrollments_table',1),(7,'2026_08_14_000008_create_topics_table',1),(8,'2026_08_14_000009_create_chapters_table',1),(9,'2026_08_14_000010_create_units_table',1),(10,'2026_08_14_000011_create_knowledge_cards_table',1),(11,'2026_08_18_000012_create_teacher_applications_table',1),(12,'2026_08_18_000013_create_student_applications_table',1),(13,'2026_08_18_000014_create_student_application_items_table',1),(14,'2026_08_22_000015_create_material_drafts_table',1),(15,'2026_08_23_000016_add_course_id_and_item_status_to_student_applications',1),(16,'2026_08_23_000017_add_example_to_knowledge_cards',1),(17,'2026_08_23_000018_add_account_to_teacher_applications',1),(18,'2026_08_23_000018_create_bloom_table',1),(19,'2026_08_23_000019_create_solo_table',1),(20,'2026_08_23_000019_drop_email_from_student_application_items',1),(21,'2026_08_23_000020_create_questions_table',1),(22,'2026_08_23_000021_create_question_options_table',1),(23,'2026_08_23_000022_create_debug_sub_info_table',1),(24,'2026_08_23_000023_create_coding_sub_info_table',1),(25,'2026_08_23_000024_create_question_bloom_solo_mappings_table',1),(26,'2026_08_23_000025_create_question_records_table',1),(27,'2026_08_23_000026_create_ai_feedback_table',1),(28,'2026_08_24_000020_add_class_name_to_students_table',1),(29,'2026_08_24_000027_create_question_knowledge_cards_table',1),(30,'2026_08_25_000028_make_knowledge_cards_unit_id_nullable',1),(31,'2026_08_26_000029_add_class_name_to_courses_table',1),(32,'2026_08_30_000030_add_bloom_id_and_description_to_questions_table',1),(33,'2026_08_30_000031_add_solo_to_question_options_table',1),(34,'2026_08_30_000032_create_question_sub_answers_table',1),(35,'2026_08_30_000033_drop_legacy_question_bank_tables',1),(36,'2026_08_30_000034_create_question_record_subs_table',1),(37,'2026_08_30_000037_add_solo_and_bloom_id_to_question_records_table',1),(38,'2026_08_30_000038_add_teacher_bloom_codes',1),(39,'2026_09_01_000041_add_show_example_to_questions_table',1),(40,'2026_09_01_000042_add_coding_fields_to_questions_table',1),(41,'2026_09_03_000043_add_graph_fields_to_knowledge_cards',1),(42,'2026_09_03_000044_drop_material_drafts_table',1),(43,'2026_09_03_000045_move_chapters_and_cards_to_courses',1);
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
-- Table structure for table `question_knowledge_cards`
--

DROP TABLE IF EXISTS `question_knowledge_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_knowledge_cards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '流水號',
  `question_id` bigint unsigned NOT NULL,
  `knowledge_card_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `question_knowledge_cards_question_id_knowledge_card_id_unique` (`question_id`,`knowledge_card_id`),
  KEY `question_knowledge_cards_knowledge_card_id_foreign` (`knowledge_card_id`),
  CONSTRAINT `question_knowledge_cards_knowledge_card_id_foreign` FOREIGN KEY (`knowledge_card_id`) REFERENCES `knowledge_cards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `question_knowledge_cards_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_knowledge_cards`
--

LOCK TABLES `question_knowledge_cards` WRITE;
/*!40000 ALTER TABLE `question_knowledge_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_knowledge_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_options`
--

DROP TABLE IF EXISTS `question_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '選項編號',
  `question_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '選項標題',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '選項描述',
  `is_answer` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否為正確答案',
  `solo` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '正解 2、其餘 1',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `question_options_question_id_foreign` (`question_id`),
  CONSTRAINT `question_options_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_options`
--

LOCK TABLES `question_options` WRITE;
/*!40000 ALTER TABLE `question_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_record_subs`
--

DROP TABLE IF EXISTS `question_record_subs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_record_subs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '子題作答編號',
  `question_record_id` bigint unsigned NOT NULL,
  `sub_id` int unsigned NOT NULL COMMENT '對應 question_sub_answers.sub_id',
  `answer` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '學生這格的答案',
  `is_right` tinyint(1) NOT NULL DEFAULT '0' COMMENT '這格是否答對',
  `solo` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '對了抄正解 solo，錯了為 1',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `question_record_subs_question_record_id_foreign` (`question_record_id`),
  CONSTRAINT `question_record_subs_question_record_id_foreign` FOREIGN KEY (`question_record_id`) REFERENCES `question_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_record_subs`
--

LOCK TABLES `question_record_subs` WRITE;
/*!40000 ALTER TABLE `question_record_subs` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_record_subs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_records`
--

DROP TABLE IF EXISTS `question_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '學生闖關紀錄編號',
  `student_id` bigint unsigned NOT NULL,
  `question_id` bigint unsigned NOT NULL,
  `result` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '學生當時的答案',
  `system_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending、correct 或 wrong',
  `teacher_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending、correct 或 wrong',
  `solo` tinyint unsigned DEFAULT NULL COMMENT '錯 1、對 2',
  `bloom_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '實作題老師批改的 Bloom',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `question_records_student_id_foreign` (`student_id`),
  KEY `question_records_question_id_foreign` (`question_id`),
  KEY `question_records_bloom_id_foreign` (`bloom_id`),
  CONSTRAINT `question_records_bloom_id_foreign` FOREIGN KEY (`bloom_id`) REFERENCES `bloom` (`id`),
  CONSTRAINT `question_records_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`),
  CONSTRAINT `question_records_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_records`
--

LOCK TABLES `question_records` WRITE;
/*!40000 ALTER TABLE `question_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_sub_answers`
--

DROP TABLE IF EXISTS `question_sub_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_sub_answers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '子題正解編號',
  `question_id` bigint unsigned NOT NULL,
  `sub_id` int unsigned NOT NULL COMMENT '格子編號或行號',
  `answer` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '這一格的標準答案',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '說明',
  `solo` tinyint unsigned NOT NULL DEFAULT '2' COMMENT '出題配分；學生答錯時作答端記 1',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `question_sub_answers_question_id_sub_id_unique` (`question_id`,`sub_id`),
  CONSTRAINT `question_sub_answers_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_sub_answers`
--

LOCK TABLES `question_sub_answers` WRITE;
/*!40000 ALTER TABLE `question_sub_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_sub_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '題目編號',
  `course_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '題目標題',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'choice、debug 或 coding',
  `question_content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '題目內容',
  `bloom_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '出題時的 Bloom 層級',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '題目說明',
  `show_example` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否把知識卡範例給學生看',
  `starter_code` text COLLATE utf8mb4_unicode_ci COMMENT '實作題已知條件（給學生看）',
  `expected_output` text COLLATE utf8mb4_unicode_ci COMMENT '實作題期望輸出（學生看不到）',
  `reference_answer` text COLLATE utf8mb4_unicode_ci COMMENT '實作題參考答案（學生看不到）',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `questions_course_id_foreign` (`course_id`),
  KEY `questions_teacher_id_foreign` (`teacher_id`),
  KEY `questions_bloom_id_foreign` (`bloom_id`),
  CONSTRAINT `questions_bloom_id_foreign` FOREIGN KEY (`bloom_id`) REFERENCES `bloom` (`id`),
  CONSTRAINT `questions_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `questions_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_application_items`
--

DROP TABLE IF EXISTS `student_application_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_application_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '明細 ID',
  `application_id` bigint unsigned NOT NULL,
  `student_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '學生學號',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '學生姓名',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending 或 approved',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_application_items_application_id_student_no_unique` (`application_id`,`student_no`),
  CONSTRAINT `student_application_items_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `student_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_application_items`
--

LOCK TABLES `student_application_items` WRITE;
/*!40000 ALTER TABLE `student_application_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_application_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_applications`
--

DROP TABLE IF EXISTS `student_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tid` bigint unsigned NOT NULL,
  `course_id` bigint unsigned DEFAULT NULL,
  `class_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '班級名稱',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT '申請狀態',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `student_applications_tid_foreign` (`tid`),
  KEY `student_applications_course_id_foreign` (`course_id`),
  CONSTRAINT `student_applications_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_applications_tid_foreign` FOREIGN KEY (`tid`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_applications`
--

LOCK TABLES `student_applications` WRITE;
/*!40000 ALTER TABLE `student_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_applications` ENABLE KEYS */;
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
  `student_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '學號',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '學生姓名',
  `class_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '現有班級',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '學校信箱（登入帳號）',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_student_no_unique` (`student_no`),
  UNIQUE KEY `students_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'$2y$12$8UXZXq.2aoxVnZm92Ic8jOSdkljheDQ.A21MeBPJEHzfhT9JtJuai','1411131000','王小明','資應','s1411131000@nutc.edu.tw','2026-09-03 08:01:09','2026-09-03 08:01:09');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_applications`
--

DROP TABLE IF EXISTS `teacher_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '申請 ID',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '教師名稱',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '教師信箱',
  `account` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '教師自訂帳號',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '申請理由',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT '申請狀態',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_applications_email_unique` (`email`),
  UNIQUE KEY `teacher_applications_account_unique` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_applications`
--

LOCK TABLES `teacher_applications` WRITE;
/*!40000 ALTER TABLE `teacher_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_applications` ENABLE KEYS */;
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
INSERT INTO `teachers` VALUES (1,'teacher@school.edu.tw','$2y$12$8UXZXq.2aoxVnZm92Ic8jOSdkljheDQ.A21MeBPJEHzfhT9JtJuai','許老師','teacher@school.edu.tw','2026-09-03 08:01:09','2026-09-03 08:01:09'),(2,'teacher2@school.edu.tw','$2y$12$8UXZXq.2aoxVnZm92Ic8jOSdkljheDQ.A21MeBPJEHzfhT9JtJuai','陳老師','teacher2@school.edu.tw','2026-09-03 08:01:09','2026-09-03 08:01:09');
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
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

--
-- Dumping routines for database 'php_education'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-03 16:02:22
