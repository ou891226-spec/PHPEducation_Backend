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
INSERT INTO `admins` VALUES (1,'admin@nutc.edu.tw','$2y$12$YFRspIUjG2yfKWjJHlkl7.qCq7a4E5CWIfETd8lUuPzZ9pZosCvva');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_feedback`
--

LOCK TABLES `ai_feedback` WRITE;
/*!40000 ALTER TABLE `ai_feedback` DISABLE KEYS */;
INSERT INTO `ai_feedback` VALUES (1,6,'{\"solo\":\"S1\",\"feedback\":\"AI 評估服務暫時不可用，已將您的程式碼送交教師覆核。\"}','2026-10-11 18:22:40','2026-10-11 18:22:40');
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
INSERT INTO `bloom` VALUES ('B1','記憶瞭解','回憶事實、名詞與基本概念','2026-10-11 17:18:51','2026-10-19 19:52:48'),('B11','記憶瞭解／事實',NULL,'2026-10-11 17:18:53','2026-10-19 19:52:48'),('B12','記憶瞭解／概念',NULL,'2026-10-11 17:18:53','2026-10-19 19:52:48'),('B13','記憶瞭解／程序',NULL,'2026-10-11 17:18:53','2026-10-19 19:52:48'),('B2','應用','解釋、摘要並說明意義','2026-10-11 17:18:51','2026-10-19 19:52:48'),('B21','應用／事實',NULL,'2026-10-11 17:18:53','2026-10-19 19:52:48'),('B22','應用／概念',NULL,'2026-10-11 17:18:53','2026-10-19 19:52:48'),('B23','應用／程序',NULL,'2026-10-11 17:18:53','2026-10-19 19:52:48'),('B3','分析','在新情境使用程序或方法','2026-10-11 17:18:51','2026-10-19 19:52:48'),('B31','分析／事實',NULL,'2026-10-11 17:18:53','2026-10-19 19:52:48'),('B32','分析／概念',NULL,'2026-10-11 17:18:53','2026-10-19 19:52:48'),('B33','分析／程序',NULL,'2026-10-11 17:18:53','2026-10-19 19:52:48'),('B4','評價','拆解、比較並找出關係','2026-10-11 17:18:51','2026-10-19 19:52:48'),('B41','評價／事實',NULL,'2026-10-11 17:18:53','2026-10-19 19:52:48'),('B42','評價／概念',NULL,'2026-10-11 17:18:53','2026-10-19 19:52:48'),('B43','評價／程序',NULL,'2026-10-11 17:18:53','2026-10-19 19:52:48'),('B5','評鑑','依規準判斷與評論','2026-10-11 17:18:51','2026-10-11 17:18:51'),('B51','評鑑（判斷/評論）','依規準判斷與評論','2026-10-11 17:18:53','2026-10-11 17:18:53'),('B52','評鑑（判斷/評論）','依規準判斷與評論','2026-10-11 17:18:53','2026-10-11 17:18:53'),('B53','評鑑（判斷/評論）','依規準判斷與評論','2026-10-11 17:18:53','2026-10-11 17:18:53'),('B6','創造','重組、設計並產出新結構','2026-10-11 17:18:51','2026-10-11 17:18:51'),('B61','創造（設計/產出）','重組、設計並產出新結構','2026-10-11 17:18:53','2026-10-11 17:18:53'),('B62','創造（設計/產出）','重組、設計並產出新結構','2026-10-11 17:18:53','2026-10-11 17:18:53'),('B63','創造（設計/產出）','重組、設計並產出新結構','2026-10-11 17:18:53','2026-10-11 17:18:53');
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chapters`
--

LOCK TABLES `chapters` WRITE;
/*!40000 ALTER TABLE `chapters` DISABLE KEYS */;
INSERT INTO `chapters` VALUES (3,4,'第一章 Python 基礎',1,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(4,4,'第一章 Python 進階',2,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(5,3,'第一章 Python 基礎',1,'2026-10-16 07:14:56','2026-10-16 07:14:56'),(6,3,'第一章 Python 進階',2,'2026-10-16 07:14:56','2026-10-16 07:14:56'),(7,3,'第二章 Python 高階',3,'2026-10-16 07:15:13','2026-10-16 07:15:13'),(8,4,'第二章 Python 高階',3,'2026-10-16 07:18:29','2026-10-16 07:18:29');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,'網際系統設計','資應班網際系統設計課程','115-1','資應','2026-10-11 17:18:54',2,'2026-10-11 17:18:54','2026-10-11 17:18:54'),(2,'網際系統設計','資管班網際系統設計課程','115-1','資管',NULL,2,'2026-10-11 17:18:54','2026-10-11 17:18:54'),(3,'程式設計','程式設計','115-1','資應','2026-10-11 17:37:49',1,'2026-10-11 17:35:57','2026-10-11 17:37:49'),(4,'test','test','115-1','test','2026-10-11 18:01:14',1,'2026-10-11 17:57:16','2026-10-11 18:01:14');
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
INSERT INTO `enrollments` VALUES (1,1),(1,2),(1,3),(1,4),(2,4),(3,4);
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_card_completions`
--

LOCK TABLES `knowledge_card_completions` WRITE;
/*!40000 ALTER TABLE `knowledge_card_completions` DISABLE KEYS */;
INSERT INTO `knowledge_card_completions` VALUES (4,1,4,4,'quiz','2026-10-17 20:36:14','2026-10-17 20:36:14','2026-10-17 20:36:14'),(5,1,4,5,'quiz','2026-10-19 19:49:13','2026-10-19 19:49:13','2026-10-19 19:49:13');
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_card_unit`
--

LOCK TABLES `knowledge_card_unit` WRITE;
/*!40000 ALTER TABLE `knowledge_card_unit` DISABLE KEYS */;
INSERT INTO `knowledge_card_unit` VALUES (4,4,4,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(5,5,5,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(6,6,6,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(7,7,1,'2026-10-16 07:14:56','2026-10-16 07:14:56'),(8,8,2,'2026-10-16 07:14:56','2026-10-16 07:14:56'),(9,9,3,'2026-10-16 07:14:56','2026-10-16 07:14:56'),(10,10,7,'2026-10-16 07:15:13','2026-10-16 07:15:13'),(11,11,8,'2026-10-16 07:18:29','2026-10-16 07:18:29'),(12,4,9,'2026-10-17 18:55:06','2026-10-17 18:55:06');
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_cards`
--

LOCK TABLES `knowledge_cards` WRITE;
/*!40000 ALTER TABLE `knowledge_cards` DISABLE KEYS */;
INSERT INTO `knowledge_cards` VALUES (1,7,3,'變數宣告與印出','concept','Python 使用等號 = 來指派變數值，並用 print() 印出內容。','name = \"Alice\"\nprint(f\"Hello, {name}\")',1,'2026-10-11 17:41:00','2026-10-16 07:14:56'),(2,8,3,'If 條件判斷','concept','使用 if、elif 與 else 進行判斷，注意程式碼區塊需要縮排。','age = 18\nif age >= 18:\n    print(\"成年\")',1,'2026-10-11 17:41:00','2026-10-16 07:14:56'),(3,9,3,'第一支路由','concept','使用 if、elif 與 else 進行判斷，注意程式碼區塊需要縮排。','age = 18\nif age >= 19:\n    print(\"成年\")',1,'2026-10-11 17:41:00','2026-10-16 07:14:56'),(4,4,4,'變數宣告與印出','concept','Python 使用等號 = 來指派變數值，並用 print() 印出內容。','name = \"Alice\"\nprint(f\"Hello, {name}\")',1,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(5,5,4,'If 條件判斷','concept','使用 if、elif 與 else 進行判斷，注意程式碼區塊需要縮排。','age = 18\nif age >= 18:\n    print(\"成年\")',1,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(6,6,4,'第一支路由','concept','<p>使用 if、elif 與 else 進行判斷，注意程式碼區塊需要縮排。</p><img src=\"http://127.0.0.1:8000/storage/editor_images/LmnWkjGA2BuYVclpkYVQTWVrlF96n6jDaahMbUKJ.png\" alt=\"images.png\"><p></p>','age = 18\nif age >= 19:\n    print(\"成年\")',1,'2026-10-11 17:57:17','2026-10-16 06:53:43'),(7,10,3,'資安','concept','使用 if、elif 與 else 進行判斷，注意程式碼區塊需要縮排。','<?php\n$name = \'小明\';\n?>',1,'2026-10-16 07:15:13','2026-10-16 07:15:13'),(8,11,4,'資安','concept','使用 if、elif 與 else 進行判斷，注意程式碼區塊需要縮排。','<?php\n$name = \'小明\';\n?>',1,'2026-10-16 07:18:29','2026-10-16 07:18:29'),(9,4,4,'資料型態','keyword','<p>資料型態</p>',NULL,2,'2026-10-17 18:55:06','2026-10-17 18:55:06');
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
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_08_14_000001_create_admins_table',1),(2,'2026_08_14_000002_create_teachers_table',1),(3,'2026_08_14_000003_create_students_table',1),(4,'2026_08_14_000005_create_courses_table',1),(5,'2026_08_14_000006_create_personal_access_tokens_table',1),(6,'2026_08_14_000007_create_enrollments_table',1),(7,'2026_08_14_000008_create_topics_table',1),(8,'2026_08_14_000009_create_chapters_table',1),(9,'2026_08_14_000010_create_units_table',1),(10,'2026_08_14_000011_create_knowledge_cards_table',1),(11,'2026_08_18_000012_create_teacher_applications_table',1),(12,'2026_08_18_000013_create_student_applications_table',1),(13,'2026_08_18_000014_create_student_application_items_table',1),(14,'2026_08_22_000015_create_material_drafts_table',1),(15,'2026_08_23_000016_add_course_id_and_item_status_to_student_applications',1),(16,'2026_08_23_000017_add_example_to_knowledge_cards',1),(17,'2026_08_23_000018_add_account_to_teacher_applications',1),(18,'2026_08_23_000018_create_bloom_table',1),(19,'2026_08_23_000019_create_solo_table',1),(20,'2026_08_23_000019_drop_email_from_student_application_items',1),(21,'2026_08_23_000020_create_questions_table',1),(22,'2026_08_23_000021_create_question_options_table',1),(23,'2026_08_23_000022_create_debug_sub_info_table',1),(24,'2026_08_23_000023_create_coding_sub_info_table',1),(25,'2026_08_23_000024_create_question_bloom_solo_mappings_table',1),(26,'2026_08_23_000025_create_question_records_table',1),(27,'2026_08_23_000026_create_ai_feedback_table',1),(28,'2026_08_24_000020_add_class_name_to_students_table',1),(29,'2026_08_24_000027_create_question_knowledge_cards_table',1),(30,'2026_08_25_000028_make_knowledge_cards_unit_id_nullable',1),(31,'2026_08_26_000029_add_class_name_to_courses_table',1),(32,'2026_08_30_000030_add_bloom_id_and_description_to_questions_table',1),(33,'2026_08_30_000031_add_solo_to_question_options_table',1),(34,'2026_08_30_000032_create_question_sub_answers_table',1),(35,'2026_08_30_000033_drop_legacy_question_bank_tables',1),(36,'2026_08_30_000034_create_question_record_subs_table',1),(37,'2026_08_30_000037_add_solo_and_bloom_id_to_question_records_table',1),(38,'2026_08_30_000038_add_teacher_bloom_codes',1),(39,'2026_09_01_000041_add_show_example_to_questions_table',1),(40,'2026_09_01_000042_add_coding_fields_to_questions_table',1),(41,'2026_09_03_000043_add_graph_fields_to_knowledge_cards',1),(42,'2026_09_03_000044_drop_material_drafts_table',1),(43,'2026_09_03_000045_move_chapters_and_cards_to_courses',1),(44,'2026_09_11_000050_cascade_delete_question_records_with_questions',1),(45,'2026_09_24_000021_add_email_to_student_application_items',1),(46,'2026_09_24_000022_add_status_to_units_table',1),(47,'2026_09_29_000001_create_jobs_table',1),(48,'2026_09_29_000002_create_failed_jobs_table',1),(49,'2026_09_29_000003_create_ai_feedback_table',1),(50,'2026_09_29_000023_add_approved_at_to_courses_table',1),(51,'2026_10_13_000024_create_password_reset_links_table',2),(52,'2026_10_15_000025_create_solo_table',3),(53,'2026_10_15_000026_create_knowledge_card_completions_table',4),(54,'2026_10_15_000027_default_show_example_to_true_on_questions_table',5),(55,'2026_10_20_000028_rename_bloom_titles_to_teacher_taxonomy',6);
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_links`
--

LOCK TABLES `password_reset_links` WRITE;
/*!40000 ALTER TABLE `password_reset_links` DISABLE KEYS */;
INSERT INTO `password_reset_links` VALUES (3,'student',2,'d1940bab8814eb059bd4f94498f281ae52e03c77b6626d426395c4888221070d','2026-10-12 17:49:35','2026-10-12 17:34:35');
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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (3,'App\\Models\\Teacher',1,'auth','7412e21f8a4597232709e5ae9fba919e1ee9608a8975781b1a2d928e3f3a2a69','[\"*\"]','2026-10-11 18:23:09',NULL,'2026-10-11 17:35:30','2026-10-11 18:23:09'),(4,'App\\Models\\Admin',1,'auth','98c92f40e88a3934d0b6b5586d6a448fa08ee2902976cbf25c401ad882eba067','[\"*\"]','2026-10-11 18:01:22',NULL,'2026-10-11 17:37:08','2026-10-11 18:01:22'),(5,'App\\Models\\Student',1,'auth','2130b35e4d5c536513158c9189954309123b65ead9fa8045b2e1d3bb0644b6d9','[\"*\"]','2026-10-11 18:22:09',NULL,'2026-10-11 17:37:26','2026-10-11 18:22:09'),(7,'App\\Models\\Teacher',1,'auth','9684d1e2cf711307734cd81955fb2e98e1d42d431985f0f872fe6c711ba92129','[\"*\"]','2026-10-12 17:17:16',NULL,'2026-10-12 17:04:11','2026-10-12 17:17:16'),(9,'App\\Models\\Student',2,'auth','43c8d63b0d98de85219fc9ad48cc932403786c0c41285256e66dc03cf475f91b','[\"*\"]','2026-10-12 17:52:59',NULL,'2026-10-12 17:52:58','2026-10-12 17:52:59'),(10,'App\\Models\\Admin',1,'auth','b2e9b63742a7ae141c4b98d8e9c0c3f20a456cdc77c9402e01e72e29b882db1c','[\"*\"]','2026-10-12 18:11:07',NULL,'2026-10-12 18:01:29','2026-10-12 18:11:07'),(11,'App\\Models\\Teacher',1,'auth','60c871963d73160c1d4d6fb2438e6803842f43892536993b42e7a69a99a7f940','[\"*\"]','2026-10-12 18:11:19',NULL,'2026-10-12 18:01:37','2026-10-12 18:11:19'),(14,'App\\Models\\Student',3,'auth','c2cc9ed6b8c19a8ecdb09cf7a23774558da5df3fd0966a92f7300933de103390','[\"*\"]','2026-10-12 18:11:35',NULL,'2026-10-12 18:10:34','2026-10-12 18:11:35'),(15,'App\\Models\\Teacher',1,'auth','da904c522b30ceb534ca5a38cab9200e71334d17c1ddcb48c2d68632679a954d','[\"*\"]','2026-10-15 11:06:43',NULL,'2026-10-15 08:44:21','2026-10-15 11:06:43'),(16,'App\\Models\\Student',1,'auth','26a5bf9e06f55cc00d917dbc3f876db6ca14c9fbc119897ab39c14e82f8293a3','[\"*\"]','2026-10-15 10:51:38',NULL,'2026-10-15 08:44:38','2026-10-15 10:51:38'),(17,'App\\Models\\Admin',1,'auth','e649e00565173eb6e2d160349399c870242bd475134821b7a181d5a8a5d7e28a','[\"*\"]','2026-10-15 08:45:57',NULL,'2026-10-15 08:45:32','2026-10-15 08:45:57'),(18,'App\\Models\\Student',1,'auth','bff38557ff44aa2e0db93dfe93ff842de3d24d52cf4ee007f4314d9d1d2b56bf','[\"*\"]','2026-10-15 11:09:15',NULL,'2026-10-15 10:52:21','2026-10-15 11:09:15'),(19,'App\\Models\\Student',1,'auth','3f4ab73a682bbc7a370873591ae09ac78aca3ccc960478f795b65861adccd49d','[\"*\"]','2026-10-16 07:40:47',NULL,'2026-10-16 05:55:07','2026-10-16 07:40:47'),(20,'App\\Models\\Teacher',1,'auth','5b69fe77db5a28fa02e8a3f9c61f1e7ce30959f885de783ea04355f909bf0773','[\"*\"]','2026-10-16 07:40:41',NULL,'2026-10-16 05:55:14','2026-10-16 07:40:41'),(21,'App\\Models\\Teacher',1,'auth','71353d7d53bb5eb33ad7a37b240ae69c195e0ecec178ff9e4eb2929ed433bf93','[\"*\"]','2026-10-17 21:12:23',NULL,'2026-10-17 18:01:40','2026-10-17 21:12:23'),(22,'App\\Models\\Student',1,'auth','a8e9cc2ed91ba7260245df287d87c006bc85fb23faeb30661aa7d591b6274930','[\"*\"]','2026-10-17 21:40:21',NULL,'2026-10-17 18:01:56','2026-10-17 21:40:21'),(23,'App\\Models\\Student',1,'auth','fdd4416fe45bfb923d16282d99fa2afb6091f70bc6ba3b1310c3339cb2d4baab','[\"*\"]','2026-10-19 19:58:00',NULL,'2026-10-19 18:46:49','2026-10-19 19:58:00'),(24,'App\\Models\\Teacher',1,'auth','5fdc3b58b14127804b58433a0d8b8fa5c2124af3bfbb8ab312e76768dc239ae6','[\"*\"]','2026-10-19 20:13:14',NULL,'2026-10-19 18:47:10','2026-10-19 20:13:14');
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
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_knowledge_cards`
--

LOCK TABLES `question_knowledge_cards` WRITE;
/*!40000 ALTER TABLE `question_knowledge_cards` DISABLE KEYS */;
INSERT INTO `question_knowledge_cards` VALUES (1,17,1,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(2,18,1,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(3,19,2,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(4,20,1,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(5,21,2,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(6,22,1,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(7,23,4,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(9,25,5,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(10,26,4,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(11,27,5,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(12,28,4,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(13,23,5,'2026-10-16 06:12:18','2026-10-16 06:12:18'),(14,24,6,'2026-10-16 06:47:01','2026-10-16 06:47:01'),(15,24,5,'2026-10-16 06:47:01','2026-10-16 06:47:01'),(16,29,7,'2026-10-16 07:16:01','2026-10-16 07:16:01'),(17,30,8,'2026-10-16 07:18:54','2026-10-16 07:18:54'),(18,30,5,'2026-10-16 07:18:54','2026-10-16 07:18:54'),(19,31,6,'2026-10-17 18:21:28','2026-10-17 18:21:28'),(20,32,4,'2026-10-17 18:56:04','2026-10-17 18:56:04'),(21,32,9,'2026-10-17 18:56:04','2026-10-17 18:56:04'),(22,33,4,'2026-10-17 20:02:55','2026-10-17 20:02:55'),(23,34,4,'2026-10-17 20:02:55','2026-10-17 20:02:55'),(24,35,5,'2026-10-17 20:02:55','2026-10-17 20:02:55'),(25,36,9,'2026-10-17 20:02:55','2026-10-17 20:02:55'),(26,37,4,'2026-10-17 20:02:55','2026-10-17 20:02:55'),(27,37,9,'2026-10-17 20:02:55','2026-10-17 20:02:55'),(28,38,4,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(29,39,4,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(30,40,4,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(31,41,4,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(32,42,4,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(33,43,5,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(34,44,5,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(35,45,5,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(36,46,5,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(37,47,5,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(38,48,5,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(39,49,9,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(40,50,9,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(41,51,9,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(42,52,9,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(43,53,9,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(44,54,9,'2026-10-17 20:10:43','2026-10-17 20:10:43');
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
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_options`
--

LOCK TABLES `question_options` WRITE;
/*!40000 ALTER TABLE `question_options` DISABLE KEYS */;
INSERT INTO `question_options` VALUES (1,1,'$','變數名稱前面要加 $',1,2,'2026-10-11 17:18:54','2026-10-11 17:18:54'),(2,1,'@','這不是正解',0,1,'2026-10-11 17:18:54','2026-10-11 17:18:54'),(3,4,'$arr = [1, 2, 3];','正確簡短語法',1,2,'2026-10-11 17:18:54','2026-10-11 17:18:54'),(4,4,'$arr = (1, 2, 3);','括號錯誤',0,1,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(5,5,'是','== 主要比較值；=== 才會同時比較值與型別',0,1,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(6,5,'否','== 主要比較值；=== 才會同時比較值與型別',1,2,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(7,9,'$','變數名稱前面要加 $',1,2,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(8,9,'@','這不是正解',0,1,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(9,12,'$arr = [1, 2, 3];','正確簡短語法',1,2,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(10,12,'$arr = (1, 2, 3);','括號錯誤',0,1,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(11,13,'是','== 主要比較值；=== 才會同時比較值與型別',0,1,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(12,13,'否','== 主要比較值；=== 才會同時比較值與型別',1,2,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(13,17,'$','PHP 變數一律以 $ 開頭，例如 $name。',1,2,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(14,17,'@',NULL,0,1,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(15,17,'#',NULL,0,1,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(16,17,'&',NULL,0,1,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(17,18,'是','echo 是 PHP 最常用的輸出語法。',1,2,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(18,18,'否',NULL,0,1,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(25,23,'$','PHP 變數一律以 $ 開頭，例如 $name。',1,2,'2026-10-16 06:12:18','2026-10-16 06:12:18'),(26,23,'@',NULL,0,1,'2026-10-16 06:12:18','2026-10-16 06:12:18'),(27,23,'#',NULL,0,1,'2026-10-16 06:12:18','2026-10-16 06:12:18'),(28,23,'&',NULL,0,1,'2026-10-16 06:12:18','2026-10-16 06:12:18'),(33,29,'正確','12',1,2,'2026-10-16 07:17:39','2026-10-16 07:17:39'),(34,29,'錯誤',NULL,0,1,'2026-10-16 07:17:40','2026-10-16 07:17:40'),(61,33,'echo','echo 用來輸出字串。',1,2,'2026-10-17 20:04:03','2026-10-17 20:04:03'),(62,33,'input',NULL,0,1,'2026-10-17 20:04:03','2026-10-17 20:04:03'),(63,33,'scanf',NULL,0,1,'2026-10-17 20:04:03','2026-10-17 20:04:03'),(64,33,'read',NULL,0,1,'2026-10-17 20:04:03','2026-10-17 20:04:03'),(65,34,'是','PHP 變數名稱區分大小寫。',1,2,'2026-10-17 20:04:12','2026-10-17 20:04:12'),(66,34,'否',NULL,0,1,'2026-10-17 20:04:12','2026-10-17 20:04:12'),(67,35,'( )','if 的條件寫在小括號裡，例如 if ($a > 1)。',1,2,'2026-10-17 20:04:21','2026-10-17 20:04:21'),(68,35,'[ ]',NULL,0,1,'2026-10-17 20:04:21','2026-10-17 20:04:21'),(69,35,'{ }',NULL,0,1,'2026-10-17 20:04:21','2026-10-17 20:04:21'),(70,35,'< >',NULL,0,1,'2026-10-17 20:04:21','2026-10-17 20:04:21'),(71,36,'是','用引號包起來的值是字串。',1,2,'2026-10-17 20:04:30','2026-10-17 20:04:30'),(72,36,'否',NULL,0,1,'2026-10-17 20:04:30','2026-10-17 20:04:30'),(73,37,'var_dump','var_dump 會顯示型態與值。',1,2,'2026-10-17 20:04:38','2026-10-17 20:04:38'),(74,37,'echo',NULL,0,1,'2026-10-17 20:04:38','2026-10-17 20:04:38'),(75,37,'print',NULL,0,1,'2026-10-17 20:04:38','2026-10-17 20:04:38'),(76,37,'strlen',NULL,0,1,'2026-10-17 20:04:38','2026-10-17 20:04:38'),(77,38,'=','= 是指定運算子，例如 $a = 1;',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(78,38,'==',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(79,38,'=>',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(80,38,':=',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(81,39,';','PHP 敘述以分號結尾。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(82,39,'.',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(83,39,':',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(84,39,',',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(85,40,'是',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(86,40,'否','$ 後面第一個字元必須是字母或底線。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(87,41,'.','. 是字串連接運算子，例如 \"a\" . \"b\"。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(88,41,'+',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(89,41,'&',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(90,41,'*',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(91,42,'是','print 和 echo 一樣可以輸出字串。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(92,42,'否',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(93,43,'==','== 比較兩個值是否相等。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(94,43,'=',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(95,43,'=>',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(96,43,'!=',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(97,44,'else','else 接在 if 後面，條件不成立時執行。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(98,44,'then',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(99,44,'otherwise',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(100,44,'end',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(101,45,'是','elseif 用來接續判斷下一個條件。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(102,45,'否',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(103,46,'!=','!= 表示不等於。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(104,46,'=!',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(105,46,'<>=',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(106,46,'~=',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(107,47,'&&','&& 表示「而且」，兩邊都成立才為真。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(108,47,'||',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(109,47,'!',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(110,47,'??',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(111,48,'是','多行程式要放在 { } 裡。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(112,48,'否',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(113,49,'int','沒有小數點的數字是整數 int。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(114,49,'string',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(115,49,'float',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(116,49,'bool',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(117,50,'true','布林值只有 true 和 false。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(118,50,'\"yes\"',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(119,50,'1.0',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(120,50,'null',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(121,51,'是','有小數點的數字是 float。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(122,51,'否',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(123,52,'array','array 可以存放多個值。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(124,52,'int',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(125,52,'bool',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(126,52,'float',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(127,53,'gettype','gettype() 會回傳型態名稱，例如 \"integer\"。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(128,53,'strlen',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(129,53,'count',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(130,53,'echo',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(131,54,'是','null 代表沒有值。',1,2,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(132,54,'否',NULL,0,1,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(133,24,'是','echo 是 PHP 最常用的輸出語法。',1,2,'2026-10-17 20:19:14','2026-10-17 20:19:14'),(134,24,'否',NULL,0,1,'2026-10-17 20:19:14','2026-10-17 20:19:14'),(135,30,'正確','123',1,2,'2026-10-17 20:19:30','2026-10-17 20:19:30'),(136,30,'錯誤',NULL,0,1,'2026-10-17 20:19:30','2026-10-17 20:19:30'),(137,32,'測試單元練習題目',NULL,1,2,'2026-10-17 20:19:43','2026-10-17 20:19:43'),(138,32,'測試單元練習題目',NULL,0,1,'2026-10-17 20:19:43','2026-10-17 20:19:43'),(139,32,'測試單元練習題目',NULL,0,1,'2026-10-17 20:19:43','2026-10-17 20:19:43'),(140,32,'測試單元練習題目',NULL,0,1,'2026-10-17 20:19:43','2026-10-17 20:19:43'),(141,31,'題目說明測試',NULL,0,1,'2026-10-17 20:19:53','2026-10-17 20:19:53'),(142,31,'題目說明測試',NULL,1,2,'2026-10-17 20:19:53','2026-10-17 20:19:53'),(143,31,'題目說明測試',NULL,0,1,'2026-10-17 20:19:53','2026-10-17 20:19:53'),(144,31,'題目說明測試',NULL,0,1,'2026-10-17 20:19:53','2026-10-17 20:19:53');
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_record_subs`
--

LOCK TABLES `question_record_subs` WRITE;
/*!40000 ALTER TABLE `question_record_subs` DISABLE KEYS */;
INSERT INTO `question_record_subs` VALUES (1,3,1,'if',1,2,'2026-10-11 18:18:47','2026-10-11 18:18:47'),(2,3,2,'else',1,2,'2026-10-11 18:18:47','2026-10-11 18:18:47'),(3,4,2,'$name = \"Tom\";',1,2,'2026-10-11 18:18:47','2026-10-11 18:18:47'),(4,5,1,'B',1,2,'2026-10-11 18:18:47','2026-10-11 18:18:47'),(10,26,2,'$name = \"Tom\"',0,1,'2026-10-17 20:42:11','2026-10-17 20:42:11'),(11,32,1,'if',1,2,'2026-10-19 19:50:20','2026-10-19 19:50:20'),(12,32,2,'else',1,2,'2026-10-19 19:50:20','2026-10-19 19:50:20'),(13,33,1,'B',1,2,'2026-10-19 19:58:00','2026-10-19 19:58:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_records`
--

LOCK TABLES `question_records` WRITE;
/*!40000 ALTER TABLE `question_records` DISABLE KEYS */;
INSERT INTO `question_records` VALUES (1,1,17,'practice','13','correct','correct',2,NULL,'2026-10-11 18:18:47','2026-10-11 18:18:47'),(2,1,18,'practice','17','correct','correct',2,NULL,'2026-10-11 18:18:47','2026-10-11 18:18:47'),(3,1,19,'practice','{\"correct\":2,\"total\":2,\"answers\":{\"1\":\"if\",\"2\":\"else\"}}','correct','correct',3,NULL,'2026-10-11 18:18:47','2026-10-11 18:18:47'),(4,1,20,'practice','{\"correct\":1,\"total\":1,\"answers\":{\"2\":\"$name = \\\"Tom\\\";\"}}','correct','correct',3,NULL,'2026-10-11 18:18:47','2026-10-11 18:18:47'),(5,1,21,'practice','{\"correct\":1,\"total\":1,\"answers\":{\"1\":\"B\"}}','correct','correct',3,NULL,'2026-10-11 18:18:47','2026-10-11 18:18:47'),(6,1,22,'practice','<?php\n$a = 3;\n$b = 5;\necho $a + $b;','pending','pending',1,NULL,'2026-10-11 18:18:47','2026-10-11 18:22:40'),(20,1,33,'quiz','62','wrong','wrong',1,NULL,'2026-10-17 20:21:11','2026-10-17 20:21:11'),(21,1,38,'quiz','78','wrong','wrong',1,NULL,'2026-10-17 20:21:24','2026-10-17 20:21:24'),(22,1,40,'quiz','86','correct','correct',2,NULL,'2026-10-17 20:21:35','2026-10-17 20:21:35'),(23,1,41,'quiz','89','wrong','wrong',1,NULL,'2026-10-17 20:21:45','2026-10-17 20:21:45'),(24,1,42,'quiz','91','correct','correct',2,NULL,'2026-10-17 20:22:01','2026-10-17 20:22:01'),(25,1,38,'quiz','78','wrong','wrong',1,NULL,'2026-10-17 20:22:29','2026-10-17 20:22:29'),(26,1,26,'practice','{\"correct\":0,\"total\":1,\"answers\":{\"2\":\"$name = \\\"Tom\\\"\"}}','wrong','wrong',1,NULL,'2026-10-17 20:42:11','2026-10-17 20:42:11'),(27,1,35,'quiz','67','correct','correct',2,NULL,'2026-10-19 19:48:45','2026-10-19 19:48:45'),(28,1,44,'quiz','98','wrong','wrong',1,NULL,'2026-10-19 19:48:51','2026-10-19 19:48:51'),(29,1,45,'quiz','101','correct','correct',2,NULL,'2026-10-19 19:48:57','2026-10-19 19:48:57'),(30,1,47,'quiz','107','correct','correct',2,NULL,'2026-10-19 19:49:06','2026-10-19 19:49:06'),(31,1,48,'quiz','111','correct','correct',2,NULL,'2026-10-19 19:49:13','2026-10-19 19:49:13'),(32,1,25,'practice','{\"correct\":2,\"total\":2,\"answers\":{\"1\":\"if\",\"2\":\"else\"}}','correct','correct',3,NULL,'2026-10-19 19:50:20','2026-10-19 19:50:20'),(33,1,27,'practice','{\"correct\":1,\"total\":1,\"answers\":{\"1\":\"B\"}}','correct','correct',3,NULL,'2026-10-19 19:58:00','2026-10-19 19:58:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_sub_answers`
--

LOCK TABLES `question_sub_answers` WRITE;
/*!40000 ALTER TABLE `question_sub_answers` DISABLE KEYS */;
INSERT INTO `question_sub_answers` VALUES (1,2,3,'$name = \"Ada\";','第 3 行變數少了 $',2,'2026-10-11 17:18:54','2026-10-11 17:18:54'),(2,6,1,';','分號結束',2,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(3,7,1,'20','PHP 陣列索引從 0 開始，因此 $numbers[1] 為 20',2,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(4,10,3,'$name = \"Ada\";','第 3 行變數少了 $',2,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(5,14,1,';','分號結束',2,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(6,15,1,'20','PHP 陣列索引從 0 開始，因此 $numbers[1] 為 20',2,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(7,19,1,'if','條件判斷使用 if。',2,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(8,19,2,'else','條件不成立時執行 else 區塊。',2,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(9,20,2,'$name = \"Tom\";','PHP 敘述結尾缺少分號 ;',2,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(10,21,1,'B','$a 為 5、$b 為 10，5 > 10 不成立，所以執行 else，輸出 B。',2,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(11,25,1,'if','條件判斷使用 if。',2,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(12,25,2,'else','條件不成立時執行 else 區塊。',2,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(15,26,2,'$name = \"Tom\";','PHP 敘述結尾缺少分號 ;',2,'2026-10-11 18:12:21','2026-10-11 18:12:21'),(16,27,1,'B','$a 為 5、$b 為 10，5 > 10 不成立，所以執行 else，輸出 B。',2,'2026-10-11 18:13:30','2026-10-11 18:13:30');
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
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
INSERT INTO `questions` VALUES (1,1,1,'PHP 變數符號','choice','PHP 宣告變數時，名稱前面要加什麼符號？','B11','基礎語法',1,NULL,NULL,NULL,'2026-10-11 17:18:54','2026-10-11 17:18:54'),(2,1,1,'找出少了錢字號','debug','下列程式哪一行少了 $？\n<!--debug-code-->\n1. echo \"hi\";\n2. $age = 21;\n3. name = \"Ada\";','B41','除錯',1,NULL,NULL,NULL,'2026-10-11 17:18:54','2026-10-11 17:18:54'),(3,1,1,'輸出 hello','coding','請寫一段 PHP 程式，輸出 hello。','B31','基礎輸出實作',1,'<?php\n// 請在下方撰寫程式碼\n','hello','<?php\necho \"hello\";\n','2026-10-11 17:18:54','2026-10-11 17:18:54'),(4,1,1,'PHP選擇01','choice','以下哪一個是正確的 PHP 陣列宣告？','B11','記憶',1,NULL,NULL,NULL,'2026-10-11 17:18:54','2026-10-11 17:18:54'),(5,1,1,'PHP是非01','true_false','PHP 使用 == 比較兩個變數時，會同時檢查資料型別','B13','記憶',1,NULL,NULL,NULL,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(6,1,1,'PHP填空01','fill','PHP 程式碼通常以（1）作為結束標記','B33','填空題',1,NULL,NULL,NULL,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(7,1,1,'PHP解讀01','interpret','請解讀以下 PHP 程式，說明最後會輸出什麼\n<!--code-stem-->\n$numbers = [10, 20, 30];\necho $numbers[1];','B51','程式解讀',1,NULL,NULL,NULL,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(8,1,1,'PHP實作01','coding','請使用 PHP 撰寫程式，判斷一個數字 $number 是否為偶數，若是請輸出「偶數」，否則輸出「奇數」。','B61','流程控制實作題',1,'<?php\n$number = 8;\n// 請在下方撰寫判斷邏輯\n','偶數','<?php\n$number = 8;\nif ($number % 2 == 0) {\n    echo \"偶數\";\n} else {\n    echo \"奇數\";\n}\n','2026-10-11 17:18:55','2026-10-11 17:18:55'),(9,2,1,'PHP 變數符號','choice','PHP 宣告變數時，名稱前面要加什麼符號？','B11','基礎語法',1,NULL,NULL,NULL,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(10,2,1,'找出少了錢字號','debug','下列程式哪一行少了 $？\n<!--debug-code-->\n1. echo \"hi\";\n2. $age = 21;\n3. name = \"Ada\";','B41','除錯',1,NULL,NULL,NULL,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(11,2,1,'輸出 hello','coding','請寫一段 PHP 程式，輸出 hello。','B31','基礎輸出實作',1,'<?php\n// 請在下方撰寫程式碼\n','hello','<?php\necho \"hello\";\n','2026-10-11 17:18:55','2026-10-11 17:18:55'),(12,2,1,'PHP選擇01','choice','以下哪一個是正確的 PHP 陣列宣告？','B11','記憶',1,NULL,NULL,NULL,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(13,2,1,'PHP是非01','true_false','PHP 使用 == 比較兩個變數時，會同時檢查資料型別','B13','記憶',1,NULL,NULL,NULL,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(14,2,1,'PHP填空01','fill','PHP 程式碼通常以（1）作為結束標記','B33','填空題',1,NULL,NULL,NULL,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(15,2,1,'PHP解讀01','interpret','請解讀以下 PHP 程式，說明最後會輸出什麼\n<!--code-stem-->\n$numbers = [10, 20, 30];\necho $numbers[1];','B51','程式解讀',1,NULL,NULL,NULL,'2026-10-11 17:18:55','2026-10-11 17:18:55'),(16,2,1,'PHP實作01','coding','請使用 PHP 撰寫程式，判斷一個數字 $number 是否為偶數，若是請輸出「偶數」，否則輸出「奇數」。','B61','流程控制實作題',1,'<?php\n$number = 8;\n// 請在下方撰寫判斷邏輯\n','偶數','<?php\n$number = 8;\nif ($number % 2 == 0) {\n    echo \"偶數\";\n} else {\n    echo \"奇數\";\n}\n','2026-10-11 17:18:55','2026-10-11 17:18:55'),(17,3,1,'PHP 變數開頭符號','choice','PHP 的變數名稱必須以哪個符號開頭？','B11',NULL,1,NULL,NULL,NULL,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(18,3,1,'echo 可以輸出字串','true_false','在 PHP 中，echo 可以用來把字串輸出到畫面上。','B12',NULL,1,NULL,NULL,NULL,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(19,3,1,'完成 if 判斷式','fill','$score = 75;\n（1）($score >= 60) {\n    echo \"及格\";\n} （2） {\n    echo \"不及格\";\n}','B31',NULL,1,NULL,NULL,NULL,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(20,3,1,'找出變數宣告的錯誤','debug','請找出以下 PHP 程式的錯誤並修正。\n<?php\n$name = \"Tom\"\necho $name;','B41',NULL,1,NULL,NULL,NULL,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(21,3,1,'解讀 if 判斷的輸出','interpret','請解讀以下 PHP 程式，說明最後會輸出什麼。\n<!--code-stem-->\n$a = 5;\n$b = 10;\nif ($a > $b) {\n    echo \"A\";\n} else {\n    echo \"B\";\n}','B21',NULL,1,NULL,NULL,NULL,'2026-10-11 17:43:20','2026-10-11 17:43:20'),(22,3,1,'兩數相加','coding','請使用 PHP 撰寫程式，將 $a 與 $b 相加後輸出結果。','B32',NULL,1,'<?php\n$a = 3;\n$b = 5;\n// 在下方輸出 $a + $b\n','8','<?php\n$a = 3;\n$b = 5;\necho $a + $b;','2026-10-11 17:43:20','2026-10-11 17:43:20'),(23,4,1,'PHP 變數開頭符號','choice','PHP 的變數名稱必須以哪個符號開頭？','B11',NULL,1,NULL,NULL,NULL,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(24,4,1,'echo 可以輸出字串','true_false','在 PHP 中，echo 可以用來把字串輸出到畫面上。','B11',NULL,1,NULL,NULL,NULL,'2026-10-11 17:57:17','2026-10-17 20:19:14'),(25,4,1,'完成 if 判斷式','fill','$score = 75;\n（1）($score >= 60) {\n    echo \"及格\";\n} （2） {\n    echo \"不及格\";\n}','B31',NULL,1,NULL,NULL,NULL,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(26,4,1,'找出變數宣告的錯誤','debug','<?php\n$name = \"Tom\"\necho $name;','B41',NULL,1,NULL,NULL,NULL,'2026-10-11 17:57:17','2026-10-11 18:12:21'),(27,4,1,'解讀 if 判斷的輸出','interpret','請解讀以下 PHP 程式，說明最後會輸出什麼。\n<!--code-stem-->\n$a = 5;\n$b = 10;\nif ($a > $b) {\n    echo \"A\";\n} else {\n    echo \"B\";\n}','B21',NULL,1,NULL,NULL,NULL,'2026-10-11 17:57:17','2026-10-11 17:57:17'),(28,4,1,'兩數相加','coding','請使用 PHP 撰寫程式，將 $a 與 $b 相加後輸出結果。','B32',NULL,1,'<?php\n$a = 3;\n$b = 5;\n// 在下方輸出 $a + $b\n','8','<?php\n$a = 3;\n$b = 5;\necho $a + $b;','2026-10-11 17:57:17','2026-10-11 17:57:17'),(29,3,1,'12','true_false','123','B51',NULL,1,NULL,NULL,NULL,'2026-10-16 07:16:01','2026-10-16 07:17:39'),(30,4,1,'123','true_false','123','B11',NULL,1,NULL,NULL,NULL,'2026-10-16 07:18:54','2026-10-17 20:19:30'),(31,4,1,'題目說明測試','choice','題目說明測試','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 18:21:28','2026-10-17 20:19:53'),(32,4,1,'測試單元練習題目','choice','測試單元練習題目','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 18:56:04','2026-10-17 20:19:43'),(33,4,1,'1.印出文字的語法','choice','下列哪一個可以在 PHP 中把文字印到畫面上？','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:02:55','2026-10-17 20:04:03'),(34,4,1,'2.變數名稱大小寫','true_false','PHP 的變數名稱有區分大小寫，$Name 和 $name 是不同的變數。','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:02:55','2026-10-17 20:04:12'),(35,4,1,'3.if 的條件寫法','choice','PHP 的 if 條件要寫在哪個符號裡？','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:02:55','2026-10-17 20:04:21'),(36,4,1,'4.字串的型態','true_false','在 PHP 中，\'123\' 加上引號後是字串（string）型態。','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:02:55','2026-10-17 20:04:30'),(37,4,1,'5.跨卡：印出變數的型態','choice','下列哪個函式可以印出變數的值與型態？','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:02:55','2026-10-17 20:04:38'),(38,4,1,'指定運算子','choice','PHP 用哪個符號把值指定給變數？','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(39,4,1,'敘述結尾符號','choice','PHP 每一行敘述最後要加上哪個符號？','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(40,4,1,'變數不能以數字開頭','true_false','PHP 變數名稱可以用數字開頭，例如 $1name。','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(41,4,1,'字串連接符號','choice','PHP 用哪個符號把兩個字串接在一起？','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(42,4,1,'print 也能輸出','true_false','除了 echo，PHP 也可以用 print 把文字輸出到畫面上。','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(43,4,1,'相等比較運算子','choice','在 if 條件中判斷兩個值是否相等，要用哪個運算子？','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(44,4,1,'否則的關鍵字','choice','if 條件不成立時要執行的區塊，用哪個關鍵字？','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(45,4,1,'elseif 的用途','true_false','elseif 可以在 if 後面再加一個條件判斷。','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(46,4,1,'不等於運算子','choice','PHP 中「不等於」要用哪個運算子？','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(47,4,1,'且的邏輯運算子','choice','兩個條件都要成立時，要用哪個邏輯運算子連接？','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(48,4,1,'if 區塊的大括號','true_false','if 要執行多行程式時，要用大括號 { } 把程式包起來。','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(49,4,1,'整數型態','choice','PHP 中 10 這個值是哪一種型態？','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(50,4,1,'布林值','choice','下列哪一個是 PHP 的布林值？','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(51,4,1,'浮點數型態','true_false','3.14 在 PHP 中是浮點數（float）型態。','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(52,4,1,'陣列型態','choice','PHP 中可以存放多個值的型態是哪一個？','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(53,4,1,'查看型態的函式','choice','哪個函式會回傳變數的型態名稱？','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43'),(54,4,1,'null 代表沒有值','true_false','null 表示變數沒有值。','B11',NULL,1,NULL,NULL,NULL,'2026-10-17 20:10:43','2026-10-17 20:10:43');
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;
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
INSERT INTO `solo` VALUES (1,'S1',NULL,NULL,'2026-10-15 07:26:55','2026-10-15 07:26:55'),(2,'S2',NULL,NULL,'2026-10-15 07:26:55','2026-10-15 07:26:55'),(3,'S3',NULL,NULL,'2026-10-15 07:26:55','2026-10-15 07:26:55'),(4,'S4',NULL,NULL,'2026-10-15 07:26:55','2026-10-15 07:26:55'),(5,'S5',NULL,NULL,'2026-10-15 07:26:55','2026-10-15 07:26:55');
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_application_items`
--

LOCK TABLES `student_application_items` WRITE;
/*!40000 ALTER TABLE `student_application_items` DISABLE KEYS */;
INSERT INTO `student_application_items` VALUES (1,1,'1411131000','王小明','s1411131000@nutc.edu.tw','approved','2026-10-11 17:36:16','2026-10-11 17:37:49'),(3,3,'1411131001','陳大仁','1234ou12341234@gmail.com','approved','2026-10-12 18:02:33','2026-10-12 18:03:24'),(5,5,'123','測試','98075tom@gmail.com','approved','2026-10-12 18:10:45','2026-10-12 18:11:00'),(6,6,'1411131000','王小明','s1411131000@nutc.edu.tw','approved','2026-10-15 08:45:06','2026-10-15 08:45:49');
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_applications`
--

LOCK TABLES `student_applications` WRITE;
/*!40000 ALTER TABLE `student_applications` DISABLE KEYS */;
INSERT INTO `student_applications` VALUES (1,1,3,'資應','approved','2026-10-11 17:36:16','2026-10-11 17:37:49'),(3,1,4,'test','approved','2026-10-12 18:02:33','2026-10-12 18:03:24'),(5,1,4,'test','approved','2026-10-12 18:10:45','2026-10-12 18:11:00'),(6,1,4,'test','approved','2026-10-15 08:45:06','2026-10-15 08:45:49');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'$2y$12$YFRspIUjG2yfKWjJHlkl7.qCq7a4E5CWIfETd8lUuPzZ9pZosCvva','1411131000','王小明','資應','s1411131000@nutc.edu.tw','2026-10-11 17:18:54','2026-10-11 17:18:54'),(2,'$2y$12$Z/mq/C4Ey2P80uBEnBCc7.TIUDBquyhiH1VoIOc5IOwR2.XUCQStq','1411131001','陳大仁','test','1234ou12341234@gmail.com','2026-10-11 18:01:14','2026-10-12 17:18:27'),(3,'$2y$12$MbhGU92mKoWuqb.dWWwg9uBShVXo4DS/STYGwi0Gvq5wwkOjmmZJO','123','測試','test','98075tom@gmail.com','2026-10-12 18:05:08','2026-10-12 18:05:08');
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
INSERT INTO `teachers` VALUES (1,'teacher@school.edu.tw','$2y$12$YFRspIUjG2yfKWjJHlkl7.qCq7a4E5CWIfETd8lUuPzZ9pZosCvva','許老師','z4715783442@gmail.com','2026-10-11 17:18:54','2026-10-11 17:40:17'),(2,'teacher2@school.edu.tw','$2y$12$YFRspIUjG2yfKWjJHlkl7.qCq7a4E5CWIfETd8lUuPzZ9pZosCvva','陳老師','teacher2@school.edu.tw','2026-10-11 17:18:54','2026-10-11 17:18:54');
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (4,3,'變數與資料型態',1,'published','2026-10-11 17:57:17','2026-10-11 17:57:17'),(5,3,'流程控制',2,'published','2026-10-11 17:57:17','2026-10-11 17:57:17'),(6,4,'流程控制',1,'published','2026-10-11 17:57:17','2026-10-11 17:57:17'),(7,5,'變數與資料型態',1,'published','2026-10-16 07:14:56','2026-10-16 07:14:56'),(8,5,'流程控制',2,'published','2026-10-16 07:14:56','2026-10-16 07:14:56'),(9,6,'流程控制',1,'published','2026-10-16 07:14:56','2026-10-16 07:14:56'),(10,7,'資安',1,'published','2026-10-16 07:15:13','2026-10-16 07:15:13'),(11,8,'資安',1,'published','2026-10-16 07:18:29','2026-10-16 07:18:29');
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

-- Dump completed on 2026-10-20  4:21:11
