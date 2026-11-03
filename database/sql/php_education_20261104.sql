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
INSERT INTO `admins` VALUES (1,'admin@nutc.edu.tw','$2y$12$Uv81r4onAkE5xF7brN5UfOwxfSY6Er.j782HCJtKkFgWJNJgNIiAq');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_feedback`
--

DROP TABLE IF EXISTS `ai_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_feedback` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'AI 回饋編號',
  `question_record_id` bigint unsigned NOT NULL,
  `feedback_content` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'AI 的回饋內容 (JSON 或文字)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_feedback_question_record_id_foreign` (`question_record_id`),
  CONSTRAINT `ai_feedback_question_record_id_foreign` FOREIGN KEY (`question_record_id`) REFERENCES `question_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_feedback`
--

LOCK TABLES `ai_feedback` WRITE;
/*!40000 ALTER TABLE `ai_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_feedback` ENABLE KEYS */;
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
INSERT INTO `bloom` VALUES ('B1','記憶瞭解','回憶事實、名詞與基本概念','2026-11-02 07:48:04','2026-11-02 07:48:07'),('B11','記憶瞭解／事實',NULL,'2026-11-02 07:48:06','2026-11-02 07:48:07'),('B12','記憶瞭解／概念',NULL,'2026-11-02 07:48:06','2026-11-02 07:48:07'),('B13','記憶瞭解／程序',NULL,'2026-11-02 07:48:06','2026-11-02 07:48:07'),('B2','應用','解釋、摘要並說明意義','2026-11-02 07:48:04','2026-11-02 07:48:07'),('B21','應用／事實',NULL,'2026-11-02 07:48:06','2026-11-02 07:48:07'),('B22','應用／概念',NULL,'2026-11-02 07:48:06','2026-11-02 07:48:07'),('B23','應用／程序',NULL,'2026-11-02 07:48:06','2026-11-02 07:48:07'),('B3','分析','在新情境使用程序或方法','2026-11-02 07:48:04','2026-11-02 07:48:07'),('B31','分析／事實',NULL,'2026-11-02 07:48:06','2026-11-02 07:48:07'),('B32','分析／概念',NULL,'2026-11-02 07:48:06','2026-11-02 07:48:07'),('B33','分析／程序',NULL,'2026-11-02 07:48:06','2026-11-02 07:48:07'),('B4','評價','拆解、比較並找出關係','2026-11-02 07:48:04','2026-11-02 07:48:07'),('B41','評價／事實',NULL,'2026-11-02 07:48:06','2026-11-02 07:48:07'),('B42','評價／概念',NULL,'2026-11-02 07:48:06','2026-11-02 07:48:07'),('B43','評價／程序',NULL,'2026-11-02 07:48:06','2026-11-02 07:48:07'),('B5','評鑑','依規準判斷與評論','2026-11-02 07:48:04','2026-11-02 07:48:04'),('B51','B51','依規準判斷與評論','2026-11-02 07:48:06','2026-11-02 07:48:06'),('B52','B52','依規準判斷與評論','2026-11-02 07:48:06','2026-11-02 07:48:06'),('B53','B53','依規準判斷與評論','2026-11-02 07:48:06','2026-11-02 07:48:06'),('B6','創造','重組、設計並產出新結構','2026-11-02 07:48:04','2026-11-02 07:48:04'),('B61','B61','重組、設計並產出新結構','2026-11-02 07:48:06','2026-11-02 07:48:06'),('B62','B62','重組、設計並產出新結構','2026-11-02 07:48:06','2026-11-02 07:48:06'),('B63','B63','重組、設計並產出新結構','2026-11-02 07:48:06','2026-11-02 07:48:06');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chapters`
--

LOCK TABLES `chapters` WRITE;
/*!40000 ALTER TABLE `chapters` DISABLE KEYS */;
INSERT INTO `chapters` VALUES (1,3,'第一章 Python 基礎',1,'2026-11-03 09:13:55','2026-11-03 09:13:55'),(2,3,'第一章 Python 進階',2,'2026-11-03 09:13:55','2026-11-03 09:13:55');
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
  `approved_at` timestamp NULL DEFAULT NULL COMMENT '管理員第一次開通課程的時間',
  `teacher_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `courses_teacher_id_foreign` (`teacher_id`),
  CONSTRAINT `courses_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,'網際系統設計','資應班網際系統設計課程','115-1','資應','2026-11-02 07:48:07',2,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(2,'網際系統設計','資管班網際系統設計課程','115-1','資管',NULL,2,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(3,'程式設計','程式設計','115-1','資管','2026-11-03 09:18:01',1,'2026-11-03 09:13:23','2026-11-03 09:18:01');
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
INSERT INTO `enrollments` VALUES (1,1),(1,2),(1,3);
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
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
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
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
-- Table structure for table `knowledge_card_completions`
--

DROP TABLE IF EXISTS `knowledge_card_completions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_card_completions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '流水號',
  `student_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `knowledge_card_id` bigint unsigned NOT NULL,
  `method` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'quiz：即學即測全對；read：無測驗題，已讀完',
  `completed_at` timestamp NOT NULL COMMENT '完成時間',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `card_completion_unique` (`student_id`,`course_id`,`knowledge_card_id`),
  KEY `knowledge_card_completions_course_id_foreign` (`course_id`),
  KEY `knowledge_card_completions_knowledge_card_id_foreign` (`knowledge_card_id`),
  CONSTRAINT `knowledge_card_completions_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `knowledge_card_completions_knowledge_card_id_foreign` FOREIGN KEY (`knowledge_card_id`) REFERENCES `knowledge_cards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `knowledge_card_completions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_card_completions`
--

LOCK TABLES `knowledge_card_completions` WRITE;
/*!40000 ALTER TABLE `knowledge_card_completions` DISABLE KEYS */;
INSERT INTO `knowledge_card_completions` VALUES (1,1,3,1,'quiz','2026-11-03 09:42:04','2026-11-03 09:42:04','2026-11-03 09:42:04');
/*!40000 ALTER TABLE `knowledge_card_completions` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_card_unit`
--

LOCK TABLES `knowledge_card_unit` WRITE;
/*!40000 ALTER TABLE `knowledge_card_unit` DISABLE KEYS */;
INSERT INTO `knowledge_card_unit` VALUES (1,1,1,'2026-11-03 09:13:55','2026-11-03 09:13:55'),(2,2,2,'2026-11-03 09:13:55','2026-11-03 09:13:55'),(3,3,3,'2026-11-03 09:13:55','2026-11-03 09:13:55');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_cards`
--

LOCK TABLES `knowledge_cards` WRITE;
/*!40000 ALTER TABLE `knowledge_cards` DISABLE KEYS */;
INSERT INTO `knowledge_cards` VALUES (1,1,3,'變數宣告與印出','concept','Python 使用等號 = 來指派變數值，並用 print() 印出內容。','name = \"Alice\"\nprint(f\"Hello, {name}\")',1,'2026-11-03 09:13:55','2026-11-03 09:13:55'),(2,2,3,'If 條件判斷','concept','使用 if、elif 與 else 進行判斷，注意程式碼區塊需要縮排。','age = 18\nif age >= 18:\n    print(\"成年\")',1,'2026-11-03 09:13:55','2026-11-03 09:13:55'),(3,3,3,'第一支路由','concept','使用 if、elif 與 else 進行判斷，注意程式碼區塊需要縮排。','age = 18\nif age >= 19:\n    print(\"成年\")',1,'2026-11-03 09:13:55','2026-11-03 09:13:55');
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
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_08_14_000001_create_admins_table',1),(2,'2026_08_14_000002_create_teachers_table',1),(3,'2026_08_14_000003_create_students_table',1),(4,'2026_08_14_000005_create_courses_table',1),(5,'2026_08_14_000006_create_personal_access_tokens_table',1),(6,'2026_08_14_000007_create_enrollments_table',1),(7,'2026_08_14_000008_create_topics_table',1),(8,'2026_08_14_000009_create_chapters_table',1),(9,'2026_08_14_000010_create_units_table',1),(10,'2026_08_14_000011_create_knowledge_cards_table',1),(11,'2026_08_18_000012_create_teacher_applications_table',1),(12,'2026_08_18_000013_create_student_applications_table',1),(13,'2026_08_18_000014_create_student_application_items_table',1),(14,'2026_08_22_000015_create_material_drafts_table',1),(15,'2026_08_23_000016_add_course_id_and_item_status_to_student_applications',1),(16,'2026_08_23_000017_add_example_to_knowledge_cards',1),(17,'2026_08_23_000018_add_account_to_teacher_applications',1),(18,'2026_08_23_000018_create_bloom_table',1),(19,'2026_08_23_000019_create_solo_table',1),(20,'2026_08_23_000019_drop_email_from_student_application_items',1),(21,'2026_08_23_000020_create_questions_table',1),(22,'2026_08_23_000021_create_question_options_table',1),(23,'2026_08_23_000022_create_debug_sub_info_table',1),(24,'2026_08_23_000023_create_coding_sub_info_table',1),(25,'2026_08_23_000024_create_question_bloom_solo_mappings_table',1),(26,'2026_08_23_000025_create_question_records_table',1),(27,'2026_08_23_000026_create_ai_feedback_table',1),(28,'2026_08_24_000020_add_class_name_to_students_table',1),(29,'2026_08_24_000027_create_question_knowledge_cards_table',1),(30,'2026_08_25_000028_make_knowledge_cards_unit_id_nullable',1),(31,'2026_08_26_000029_add_class_name_to_courses_table',1),(32,'2026_08_30_000030_add_bloom_id_and_description_to_questions_table',1),(33,'2026_08_30_000031_add_solo_to_question_options_table',1),(34,'2026_08_30_000032_create_question_sub_answers_table',1),(35,'2026_08_30_000033_drop_legacy_question_bank_tables',1),(36,'2026_08_30_000034_create_question_record_subs_table',1),(37,'2026_08_30_000037_add_solo_and_bloom_id_to_question_records_table',1),(38,'2026_08_30_000038_add_teacher_bloom_codes',1),(39,'2026_09_01_000041_add_show_example_to_questions_table',1),(40,'2026_09_01_000042_add_coding_fields_to_questions_table',1),(41,'2026_09_03_000043_add_graph_fields_to_knowledge_cards',1),(42,'2026_09_03_000044_drop_material_drafts_table',1),(43,'2026_09_03_000045_move_chapters_and_cards_to_courses',1),(44,'2026_09_11_000050_cascade_delete_question_records_with_questions',1),(45,'2026_09_24_000021_add_email_to_student_application_items',1),(46,'2026_09_24_000022_add_status_to_units_table',1),(47,'2026_09_29_000001_create_jobs_table',1),(48,'2026_09_29_000002_create_failed_jobs_table',1),(49,'2026_09_29_000003_create_ai_feedback_table',1),(50,'2026_09_29_000023_add_approved_at_to_courses_table',1),(51,'2026_10_13_000024_create_password_reset_links_table',1),(52,'2026_10_15_000025_create_solo_table',1),(53,'2026_10_15_000026_create_knowledge_card_completions_table',1),(54,'2026_10_15_000027_default_show_example_to_true_on_questions_table',1),(55,'2026_10_20_000028_rename_bloom_titles_to_teacher_taxonomy',1),(56,'2026_10_20_000029_add_solo_descriptions',1),(57,'2026_11_03_000030_add_accepted_answers_to_question_sub_answers',2),(58,'2026_11_04_000031_create_site_settings_table',3);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_links`
--

DROP TABLE IF EXISTS `password_reset_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_links` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'student 或 teacher',
  `user_id` bigint unsigned NOT NULL,
  `token_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `password_reset_links_token_hash_unique` (`token_hash`),
  KEY `password_reset_links_user_type_user_id_index` (`user_type`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_links`
--

LOCK TABLES `password_reset_links` WRITE;
/*!40000 ALTER TABLE `password_reset_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_links` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\Teacher',1,'auth','16dae5c2d4f5da4e4e430e59c02c97abe1cb76f353d6afa900d02b3c13502b29','[\"*\"]','2026-11-03 09:42:41',NULL,'2026-11-03 09:10:17','2026-11-03 09:42:41'),(3,'App\\Models\\Admin',1,'auth','f94e264cb52255cfc208d06d765991fde5c90eb5e166c3d9cebe5ea990ce04b4','[\"*\"]','2026-11-03 09:18:12',NULL,'2026-11-03 09:15:45','2026-11-03 09:18:12'),(4,'App\\Models\\Admin',1,'auth','76b8270373e5fb6ce6a69864ee4100407c9536b1cb9e4fe95ebef626fb34b800','[\"*\"]','2026-11-03 16:31:37',NULL,'2026-11-03 16:24:40','2026-11-03 16:31:37'),(5,'App\\Models\\Teacher',1,'auth','5a51b76fa4f306769c016884b10cf8806376f399dea748176973c9b95667ea02','[\"*\"]','2026-11-03 16:24:59',NULL,'2026-11-03 16:24:58','2026-11-03 16:24:59'),(6,'App\\Models\\Admin',1,'auth','445d5864da3ca6af0ecd6c2e748c1b0c5ddebfca6acb3ec47a14a8f62d1ad308','[\"*\"]','2026-11-03 16:29:27',NULL,'2026-11-03 16:29:25','2026-11-03 16:29:27'),(7,'App\\Models\\Teacher',1,'auth','10e1449627e62d6badbffe146756ae82ebb15d4be80aa43d5710da670754de2c','[\"*\"]','2026-11-03 16:30:46',NULL,'2026-11-03 16:30:44','2026-11-03 16:30:46');
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_knowledge_cards`
--

LOCK TABLES `question_knowledge_cards` WRITE;
/*!40000 ALTER TABLE `question_knowledge_cards` DISABLE KEYS */;
INSERT INTO `question_knowledge_cards` VALUES (1,17,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(2,18,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(3,19,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(4,20,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(5,21,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(6,22,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(7,23,2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(8,24,2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(9,25,2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(10,26,2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(11,27,2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(12,28,2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(13,29,3,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(14,30,3,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(15,31,3,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(16,32,1,'2026-11-03 09:29:44','2026-11-03 09:29:44'),(17,33,1,'2026-11-03 09:34:13','2026-11-03 09:34:13');
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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_options`
--

LOCK TABLES `question_options` WRITE;
/*!40000 ALTER TABLE `question_options` DISABLE KEYS */;
INSERT INTO `question_options` VALUES (1,1,'$','變數名稱前面要加 $',1,2,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(2,1,'@','這不是正解',0,1,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(3,4,'$arr = [1, 2, 3];','正確簡短語法',1,2,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(4,4,'$arr = (1, 2, 3);','括號錯誤',0,1,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(5,5,'是','== 主要比較值；=== 才會同時比較值與型別',0,1,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(6,5,'否','== 主要比較值；=== 才會同時比較值與型別',1,2,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(7,9,'$','變數名稱前面要加 $',1,2,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(8,9,'@','這不是正解',0,1,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(9,12,'$arr = [1, 2, 3];','正確簡短語法',1,2,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(10,12,'$arr = (1, 2, 3);','括號錯誤',0,1,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(11,13,'是','== 主要比較值；=== 才會同時比較值與型別',0,1,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(12,13,'否','== 主要比較值；=== 才會同時比較值與型別',1,2,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(13,17,'print()','print() 會把括號裡的內容印出來。',1,2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(14,17,'echo()','echo 是 PHP 的輸出寫法，不是 Python。',0,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(15,17,'console.log()','console.log() 是 JavaScript 的寫法。',0,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(16,17,'printf()','這不是 Python 印出變數的基本寫法。',0,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(17,18,'是','Python 用 = 指派變數值。',1,2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(18,18,'否','指派就是用 =，比較相等才會用 ==。',0,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(19,23,'else','else 接在 if 後面，處理條件不成立的情況。',1,2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(20,23,'elseif','Python 沒有 elseif，多個條件用 elif。',0,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(21,23,'otherwise','otherwise 不是 Python 的關鍵字。',0,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(22,23,'switch','這題要的是 if 不成立時的 else。',0,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(23,24,'是','if 後面的程式碼要縮排，否則不是同一個區塊。',1,2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(24,24,'否','Python 用縮排區分區塊，不能省略。',0,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(25,29,'elif','elif 會在前面的條件不成立時，再判斷新的條件。',1,2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(26,29,'else','else 不再檢查條件，只處理剩下的情況。',0,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(27,29,'elseif','Python 的寫法是 elif，不是 elseif。',0,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(28,29,'loop','loop 不是條件判斷的關鍵字。',0,1,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(29,30,'是','elif 會再做一次條件判斷。',1,2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(30,30,'否','不再判斷的是 else；elif 仍會檢查條件。',0,1,'2026-11-03 09:17:31','2026-11-03 09:17:31');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_record_subs`
--

LOCK TABLES `question_record_subs` WRITE;
/*!40000 ALTER TABLE `question_record_subs` DISABLE KEYS */;
INSERT INTO `question_record_subs` VALUES (1,3,1,'print(\"Ada\")',1,2,'2026-11-03 09:42:33','2026-11-03 09:42:33');
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
  `source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'practice' COMMENT 'practice：題目練習；quiz：知識卡即學即測',
  `result` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '學生當時的答案',
  `system_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending、correct 或 wrong',
  `teacher_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending、correct 或 wrong',
  `solo` tinyint unsigned DEFAULT NULL COMMENT '錯 1、對 2',
  `bloom_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '實作題老師批改的 Bloom',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `question_records_student_id_foreign` (`student_id`),
  KEY `question_records_bloom_id_foreign` (`bloom_id`),
  KEY `question_records_question_id_foreign` (`question_id`),
  CONSTRAINT `question_records_bloom_id_foreign` FOREIGN KEY (`bloom_id`) REFERENCES `bloom` (`id`),
  CONSTRAINT `question_records_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `question_records_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_records`
--

LOCK TABLES `question_records` WRITE;
/*!40000 ALTER TABLE `question_records` DISABLE KEYS */;
INSERT INTO `question_records` VALUES (1,1,17,'quiz','13','correct','correct',2,NULL,'2026-11-03 09:41:53','2026-11-03 09:41:53'),(2,1,18,'quiz','17','correct','correct',2,NULL,'2026-11-03 09:42:04','2026-11-03 09:42:04'),(3,1,33,'practice','{\"correct\":1,\"total\":1,\"answers\":{\"1\":\"print(\\\"Ada\\\")\"}}','correct','correct',2,NULL,'2026-11-03 09:42:33','2026-11-03 09:42:33');
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
  `accepted_answers` json DEFAULT NULL COMMENT '同一格也可接受的其他答案字串',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '說明',
  `solo` tinyint unsigned NOT NULL DEFAULT '2' COMMENT '出題配分；學生答錯時作答端記 1',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `question_sub_answers_question_id_sub_id_unique` (`question_id`,`sub_id`),
  CONSTRAINT `question_sub_answers_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_sub_answers`
--

LOCK TABLES `question_sub_answers` WRITE;
/*!40000 ALTER TABLE `question_sub_answers` DISABLE KEYS */;
INSERT INTO `question_sub_answers` VALUES (1,2,3,'$name = \"Ada\";',NULL,'第 3 行變數少了 $',2,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(2,6,1,';',NULL,'分號結束',2,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(3,7,1,'20',NULL,'PHP 陣列索引從 0 開始，因此 $numbers[1] 為 20',2,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(4,10,3,'$name = \"Ada\";',NULL,'第 3 行變數少了 $',2,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(5,14,1,';',NULL,'分號結束',2,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(6,15,1,'20',NULL,'PHP 陣列索引從 0 開始，因此 $numbers[1] 為 20',2,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(7,19,1,'print(name)',NULL,'print(name) 會印出變數 name 的值。',2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(8,20,2,'print(name)',NULL,'print 被拼成 prnt，Python 找不到這個函式。',2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(9,21,1,'Ada',NULL,'name 被設成 Ada，print(name) 就印出 Ada。',2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(10,25,1,'if',NULL,'用 if 開始條件判斷。',2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(11,25,2,'else',NULL,'條件不成立時用 else。',2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(12,26,2,'if score >= 60:',NULL,'if 條件的結尾要加冒號，下一行才能縮排。',2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(13,27,1,'不及格',NULL,'50 小於 60，條件不成立，所以執行 else，印出不及格。',2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(14,31,1,'B',NULL,'75 不到 90，但大於等於 60，所以印出 B。',2,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(15,32,1,'print(name)','[\"print (name)\"]','函式名稱是 print，括號裡放變數 name。括號前有沒有空格都接受。',2,'2026-11-03 09:29:44','2026-11-03 09:29:44'),(16,33,1,'print(name)','[\"print(\\\"Ada\\\")\"]','print(name) 會印出變數裡的 Ada；print(\"Ada\") 是直接印出這個字串。兩種結果一樣。',2,'2026-11-03 09:34:13','2026-11-03 09:34:13');
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
  `show_example` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否把知識卡範例給學生看',
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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
INSERT INTO `questions` VALUES (1,1,1,'PHP 變數符號','choice','PHP 宣告變數時，名稱前面要加什麼符號？','B11','基礎語法',0,NULL,NULL,NULL,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(2,1,1,'找出少了錢字號','debug','下列程式哪一行少了 $？\n<!--debug-code-->\n1. echo \"hi\";\n2. $age = 21;\n3. name = \"Ada\";','B41','除錯',0,NULL,NULL,NULL,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(3,1,1,'輸出 hello','coding','請寫一段 PHP 程式，輸出 hello。','B31','基礎輸出實作',0,'<?php\n// 請在下方撰寫程式碼\n','hello','<?php\necho \"hello\";\n','2026-11-02 07:48:07','2026-11-02 07:48:07'),(4,1,1,'PHP選擇01','choice','以下哪一個是正確的 PHP 陣列宣告？','B11','記憶',0,NULL,NULL,NULL,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(5,1,1,'PHP是非01','true_false','PHP 使用 == 比較兩個變數時，會同時檢查資料型別','B13','記憶',0,NULL,NULL,NULL,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(6,1,1,'PHP填空01','fill','PHP 程式碼通常以（1）作為結束標記','B33','填空題',0,NULL,NULL,NULL,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(7,1,1,'PHP解讀01','interpret','請解讀以下 PHP 程式，說明最後會輸出什麼\n<!--code-stem-->\n$numbers = [10, 20, 30];\necho $numbers[1];','B31','程式解讀',0,NULL,NULL,NULL,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(8,1,1,'PHP實作01','coding','請使用 PHP 撰寫程式，判斷一個數字 $number 是否為偶數，若是請輸出「偶數」，否則輸出「奇數」。','B23','流程控制實作題',0,'<?php\n$number = 8;\n// 請在下方撰寫判斷邏輯\n','偶數','<?php\n$number = 8;\nif ($number % 2 == 0) {\n    echo \"偶數\";\n} else {\n    echo \"奇數\";\n}\n','2026-11-02 07:48:07','2026-11-02 07:48:07'),(9,2,1,'PHP 變數符號','choice','PHP 宣告變數時，名稱前面要加什麼符號？','B11','基礎語法',0,NULL,NULL,NULL,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(10,2,1,'找出少了錢字號','debug','下列程式哪一行少了 $？\n<!--debug-code-->\n1. echo \"hi\";\n2. $age = 21;\n3. name = \"Ada\";','B41','除錯',0,NULL,NULL,NULL,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(11,2,1,'輸出 hello','coding','請寫一段 PHP 程式，輸出 hello。','B31','基礎輸出實作',0,'<?php\n// 請在下方撰寫程式碼\n','hello','<?php\necho \"hello\";\n','2026-11-02 07:48:07','2026-11-02 07:48:07'),(12,2,1,'PHP選擇01','choice','以下哪一個是正確的 PHP 陣列宣告？','B11','記憶',0,NULL,NULL,NULL,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(13,2,1,'PHP是非01','true_false','PHP 使用 == 比較兩個變數時，會同時檢查資料型別','B13','記憶',0,NULL,NULL,NULL,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(14,2,1,'PHP填空01','fill','PHP 程式碼通常以（1）作為結束標記','B33','填空題',0,NULL,NULL,NULL,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(15,2,1,'PHP解讀01','interpret','請解讀以下 PHP 程式，說明最後會輸出什麼\n<!--code-stem-->\n$numbers = [10, 20, 30];\necho $numbers[1];','B31','程式解讀',0,NULL,NULL,NULL,'2026-11-02 07:48:07','2026-11-02 07:48:07'),(16,2,1,'PHP實作01','coding','請使用 PHP 撰寫程式，判斷一個數字 $number 是否為偶數，若是請輸出「偶數」，否則輸出「奇數」。','B23','流程控制實作題',0,'<?php\n$number = 8;\n// 請在下方撰寫判斷邏輯\n','偶數','<?php\n$number = 8;\nif ($number % 2 == 0) {\n    echo \"偶數\";\n} else {\n    echo \"奇數\";\n}\n','2026-11-02 07:48:07','2026-11-02 07:48:07'),(17,3,1,'印出內容的函式','choice','Python 要把變數內容顯示在畫面上，應使用哪一個函式？','B11','Python 用 print() 印出內容。',1,NULL,NULL,NULL,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(18,3,1,'指派用等號','true_false','Python 使用等號 = 把右邊的值指派給左邊的變數。','B11','例如 name = \"Ada\" 會把 Ada 存進 name。',1,NULL,NULL,NULL,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(19,3,1,'補上印出語法','fill','變數 name 的值是 Ada。要把這個變數印出來，橫線應填：\n（1）','B13','呼叫 print，並把變數放進括號。',1,NULL,NULL,NULL,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(20,3,1,'印出函式拼錯了','debug','name = \"Ada\"\nprnt(name)','B33','找出拼錯的函式名稱，並填上修正後的那一行。',1,NULL,NULL,NULL,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(21,3,1,'變數印出什麼','interpret','請說明這段程式最後會輸出什麼。\n<!--code-stem-->\nname = \"Ada\"\nprint(name)','B31','先指派，再印出變數目前的值。',1,NULL,NULL,NULL,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(22,3,1,'印出問候語','coding','請用 Python 建立變數 name，值為 Ada，並印出 Hello, Ada。','B23','先指派變數，再用 print() 把文字和變數接起來輸出。',1,'name = \"Ada\"\n','Hello, Ada','name = \"Ada\"\nprint(\"Hello, \" + name)','2026-11-03 09:17:31','2026-11-03 09:17:31'),(23,3,1,'否則用哪個關鍵字','choice','Python 在 if 條件不成立時，要改走另一段程式，應使用哪個關鍵字？','B11','if 搭配 else，else 區塊同樣要縮排。',1,NULL,NULL,NULL,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(24,3,1,'條件區塊要縮排','true_false','Python 的 if 區塊必須縮排，沒有縮排會發生語法錯誤。','B11','縮排用來表示這一行屬於 if 或 else。',1,NULL,NULL,NULL,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(25,3,1,'補上 if 與 else','fill','分數大於等於 60 印出及格，否則印出不及格。請補上兩個關鍵字。\n（1） score >= 60:\n    print(\"及格\")\n（2）:\n    print(\"不及格\")','B23','第一格是條件判斷，第二格是條件不成立時的分支。',1,NULL,NULL,NULL,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(26,3,1,'if 少了冒號','debug','score = 70\nif score >= 60\n    print(\"及格\")\nelse:\n    print(\"不及格\")','B33','條件那一行的結尾少了一個符號。請填修正後的完整那一行。',1,NULL,NULL,NULL,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(27,3,1,'不及格會印出什麼','interpret','請說明這段程式最後會輸出什麼。\n<!--code-stem-->\nscore = 50\nif score >= 60:\n    print(\"及格\")\nelse:\n    print(\"不及格\")','B31','先看條件成不成立，再決定要走 if 還是 else。',1,NULL,NULL,NULL,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(28,3,1,'寫出及格判斷','coding','請用 Python 撰寫程式：變數 score 的值是 80。若 score 大於等於 60 就印出及格，否則印出不及格。','B23','使用 if 與 else，兩個 print 都要縮排。',1,'score = 80\n','及格','score = 80\nif score >= 60:\n    print(\"及格\")\nelse:\n    print(\"不及格\")','2026-11-03 09:17:31','2026-11-03 09:17:31'),(29,3,1,'再檢查下一個條件','choice','第一個 if 不成立時，還要再檢查另一個條件，Python 應使用哪個關鍵字？','B11','elif 是 else if 的縮寫，用來接第二個條件。',1,NULL,NULL,NULL,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(30,3,1,'elif 會再判斷','true_false','elif 代表「否則如果」，前面的條件不成立時，會再檢查一個新條件。','B11','if、elif、else 可以串成多個分支。',1,NULL,NULL,NULL,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(31,3,1,'elif 走到哪一支','interpret','請說明這段程式最後會輸出什麼。\n<!--code-stem-->\nscore = 75\nif score >= 90:\n    print(\"A\")\nelif score >= 60:\n    print(\"B\")\nelse:\n    print(\"C\")','B32','由上往下檢查，第一個成立的條件就會執行，後面不再看。',1,NULL,NULL,NULL,'2026-11-03 09:17:31','2026-11-03 09:17:31'),(32,3,1,'印出變數的兩種寫法','fill','變數 name 的值是 Ada。要把這個變數印出來，橫線可以填哪一種寫法？\n（1）','B13','print(name) 與 print (name) 都算對。',1,NULL,NULL,NULL,'2026-11-03 09:29:44','2026-11-03 09:29:44'),(33,3,1,'兩種印出 Ada 的寫法','fill','變數 name 的值已經是 Ada。要讓畫面印出 Ada，橫線填下面任一種都算對。\n（1）','B13','印出變數，或直接印出字串 Ada，結果相同。',1,NULL,NULL,NULL,'2026-11-03 09:34:13','2026-11-03 09:34:13');
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_login_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否允許學生登入',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,1,'2026-11-03 16:27:24','2026-11-03 16:31:37');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solo`
--

DROP TABLE IF EXISTS `solo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solo` (
  `level` tinyint unsigned NOT NULL COMMENT 'SOLO 層級 1–5，對應各表 solo 欄位',
  `code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'S1–S5',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '層級名稱',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '層級說明',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`level`),
  UNIQUE KEY `solo_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solo`
--

LOCK TABLES `solo` WRITE;
/*!40000 ALTER TABLE `solo` DISABLE KEYS */;
INSERT INTO `solo` VALUES (1,'S1','預結構','對於題意無法理解，亦無法推演程式執行的結果。','2026-11-02 07:48:06','2026-11-02 07:48:07'),(2,'S2','單一結構','嘗試解讀，但只推演出部分的結果，無法產出完整的結果。','2026-11-02 07:48:06','2026-11-02 07:48:07'),(3,'S3','多結構','對於多個子問題(例如function) 推演相關結果，但無法將其關聯性有系統地整合起來，產出完整的結果。','2026-11-02 07:48:06','2026-11-02 07:48:07'),(4,'S4','關係型','能解讀程式碼，整合相關知識，推演正確的結果。','2026-11-02 07:48:06','2026-11-02 07:48:07'),(5,'S5','擴展抽象化','可以在更高的抽象層次上概念化，並推廣到新的主題或領域。在這個階段，學生可以將課堂概念應用到現實生活中。','2026-11-02 07:48:06','2026-11-02 07:48:07');
/*!40000 ALTER TABLE `solo` ENABLE KEYS */;
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
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '信箱（開通前可先指定）',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending 或 approved',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_application_items_application_id_student_no_unique` (`application_id`,`student_no`),
  CONSTRAINT `student_application_items_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `student_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_application_items`
--

LOCK TABLES `student_application_items` WRITE;
/*!40000 ALTER TABLE `student_application_items` DISABLE KEYS */;
INSERT INTO `student_application_items` VALUES (1,1,'1411131000','王小明','s1411131000@nutc.edu.tw','approved','2026-11-03 09:14:54','2026-11-03 09:18:01');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_applications`
--

LOCK TABLES `student_applications` WRITE;
/*!40000 ALTER TABLE `student_applications` DISABLE KEYS */;
INSERT INTO `student_applications` VALUES (1,1,3,'資管','approved','2026-11-03 09:14:54','2026-11-03 09:18:01');
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
INSERT INTO `students` VALUES (1,'$2y$12$Uv81r4onAkE5xF7brN5UfOwxfSY6Er.j782HCJtKkFgWJNJgNIiAq','1411131000','王小明','資應','s1411131000@nutc.edu.tw','2026-11-02 07:48:07','2026-11-02 07:48:07');
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
INSERT INTO `teachers` VALUES (1,'teacher@school.edu.tw','$2y$12$Uv81r4onAkE5xF7brN5UfOwxfSY6Er.j782HCJtKkFgWJNJgNIiAq','許老師','teacher@school.edu.tw','2026-11-02 07:48:07','2026-11-02 07:48:07'),(2,'teacher2@school.edu.tw','$2y$12$Uv81r4onAkE5xF7brN5UfOwxfSY6Er.j782HCJtKkFgWJNJgNIiAq','陳老師','teacher2@school.edu.tw','2026-11-02 07:48:07','2026-11-02 07:48:07');
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
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published' COMMENT 'draft=草稿（僅教師可見）、published=已開放給學生',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `units_chapter_id_foreign` (`chapter_id`),
  CONSTRAINT `units_chapter_id_foreign` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (1,1,'變數與資料型態',1,'published','2026-11-03 09:13:55','2026-11-03 09:13:55'),(2,1,'流程控制',2,'published','2026-11-03 09:13:55','2026-11-03 09:13:55'),(3,2,'流程控制',1,'published','2026-11-03 09:13:55','2026-11-03 09:13:55');
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

-- Dump completed on 2026-11-04  0:44:45
