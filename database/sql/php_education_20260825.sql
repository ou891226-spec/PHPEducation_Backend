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
INSERT INTO `admins` VALUES (1,'admin@school.edu.tw','$2y$12$dNFYn6bQ4i5bGeSW.UnSiOSyxIQk2S/x5QjUGNXGih4adJg2ZCJdS');
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
  `record_id` bigint unsigned NOT NULL,
  `feedback_content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'AI 的回饋內容',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `ai_feedback_record_id_foreign` (`record_id`),
  CONSTRAINT `ai_feedback_record_id_foreign` FOREIGN KEY (`record_id`) REFERENCES `question_records` (`id`) ON DELETE CASCADE
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
INSERT INTO `bloom` VALUES ('B1','記憶','回憶事實、名詞與基本概念','2026-08-24 11:52:23','2026-08-24 11:52:23'),('B2','理解','解釋、摘要並說明意義','2026-08-24 11:52:23','2026-08-24 11:52:23'),('B3','應用','在新情境使用程序或方法','2026-08-24 11:52:23','2026-08-24 11:52:23'),('B4','分析','拆解、比較並找出關係','2026-08-24 11:52:23','2026-08-24 11:52:23'),('B5','評鑑','依規準判斷與評論','2026-08-24 11:52:23','2026-08-24 11:52:23'),('B6','創造','重組、設計並產出新結構','2026-08-24 11:52:23','2026-08-24 11:52:23');
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
  `topic_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '章節名稱',
  `sort_order` int NOT NULL COMMENT '排序順序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `chapters_topic_id_foreign` (`topic_id`),
  CONSTRAINT `chapters_topic_id_foreign` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chapters`
--

LOCK TABLES `chapters` WRITE;
/*!40000 ALTER TABLE `chapters` DISABLE KEYS */;
INSERT INTO `chapters` VALUES (1,1,'第一章 PHP 說明',1,'2026-08-25 10:35:50','2026-08-25 10:35:50'),(2,1,'第一章 PHP 實作01',2,'2026-08-25 10:35:50','2026-08-25 10:35:50'),(3,1,'第一章 PHP 實作02',3,'2026-08-25 10:35:50','2026-08-25 10:35:50'),(4,1,'test01',4,'2026-08-25 10:35:50','2026-08-25 10:35:50'),(5,2,'第一章 PHP 說明',1,'2026-08-25 10:36:05','2026-08-25 10:36:05'),(6,2,'第一章 PHP 實作01',2,'2026-08-25 10:36:05','2026-08-25 10:36:05'),(7,2,'第一章 PHP 實作02',3,'2026-08-25 10:36:05','2026-08-25 10:36:05'),(8,3,'第一章 PHP 說明',1,'2026-08-25 10:37:11','2026-08-25 10:37:11'),(9,3,'第一章 PHP 實作01',2,'2026-08-25 10:37:11','2026-08-25 10:37:11'),(10,3,'第一章 PHP 實作02',3,'2026-08-25 10:37:11','2026-08-25 10:37:11'),(11,3,'第一章 PHP 實作03',4,'2026-08-25 10:39:47','2026-08-25 10:39:47'),(12,3,'第一章 PHP 實作04',5,'2026-08-25 10:39:47','2026-08-25 10:39:47'),(13,3,'第一章 PHP 實作05',6,'2026-08-25 10:39:47','2026-08-25 10:39:47'),(14,4,'第一章 PHP 說明',1,'2026-08-25 10:45:06','2026-08-25 10:45:06'),(15,4,'第一章 PHP 實作01',2,'2026-08-25 10:45:06','2026-08-25 10:45:06'),(16,4,'第一章 PHP 實作02',3,'2026-08-25 10:45:06','2026-08-25 10:45:06'),(29,7,'第一章 PHP 說明',1,'2026-08-25 11:33:46','2026-08-25 11:33:46'),(30,7,'第一章 PHP 實作01',2,'2026-08-25 11:33:47','2026-08-25 11:33:47'),(31,7,'第一章 PHP 實作02',3,'2026-08-25 11:33:47','2026-08-25 11:33:47'),(32,7,'第一章 PHP 實作03',4,'2026-08-25 11:33:47','2026-08-25 11:33:47'),(33,7,'第一章 PHP 實作04',5,'2026-08-25 11:33:47','2026-08-25 11:33:47'),(34,7,'第一章 PHP 實作05',6,'2026-08-25 11:33:47','2026-08-25 11:33:47');
/*!40000 ALTER TABLE `chapters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coding_sub_info`
--

DROP TABLE IF EXISTS `coding_sub_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coding_sub_info` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '補充資訊編號',
  `question_id` bigint unsigned NOT NULL,
  `ref_answer` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '人類判斷要有的程式碼',
  `ref_output` text COLLATE utf8mb4_unicode_ci COMMENT '人類判斷要有的程式碼輸出結果',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `coding_sub_info_question_id_unique` (`question_id`),
  CONSTRAINT `coding_sub_info_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coding_sub_info`
--

LOCK TABLES `coding_sub_info` WRITE;
/*!40000 ALTER TABLE `coding_sub_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `coding_sub_info` ENABLE KEYS */;
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
INSERT INTO `courses` VALUES (1,'網際系統設計 (資應)','資應班網際系統設計課程','115-1',2,'2026-08-24 11:52:24','2026-08-24 11:52:24'),(2,'網際系統設計 (資管)','資管班網際系統設計課程','115-1',2,'2026-08-24 11:52:24','2026-08-24 11:52:24');
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `debug_sub_info`
--

DROP TABLE IF EXISTS `debug_sub_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `debug_sub_info` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '除錯題補充資訊編號',
  `question_id` bigint unsigned NOT NULL,
  `code_line` int NOT NULL COMMENT '錯誤程式碼的目標行數',
  `answer` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '正解',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '該除錯題目描述',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `debug_sub_info_question_id_foreign` (`question_id`),
  CONSTRAINT `debug_sub_info_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `debug_sub_info`
--

LOCK TABLES `debug_sub_info` WRITE;
/*!40000 ALTER TABLE `debug_sub_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `debug_sub_info` ENABLE KEYS */;
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
INSERT INTO `enrollments` VALUES (1,1),(1,2);
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
  `unit_id` bigint unsigned DEFAULT NULL COMMENT '所屬單元 ID；有題目使用而脫離教材樹時可為空',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '知識卡標題',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '知識卡內容',
  `example` text COLLATE utf8mb4_unicode_ci COMMENT '知識卡範例',
  `sort_order` int NOT NULL COMMENT '排序順序',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `knowledge_cards_unit_id_foreign` (`unit_id`),
  CONSTRAINT `knowledge_cards_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_cards`
--

LOCK TABLES `knowledge_cards` WRITE;
/*!40000 ALTER TABLE `knowledge_cards` DISABLE KEYS */;
INSERT INTO `knowledge_cards` VALUES (1,1,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 21;\necho $name;\necho $age;',1,'2026-08-25 10:35:50','2026-08-25 10:35:50'),(2,2,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\necho $name;',1,'2026-08-25 10:35:50','2026-08-25 10:35:50'),(3,3,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 23;\necho $name;\necho $age;',1,'2026-08-25 10:35:50','2026-08-25 10:35:50'),(4,4,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 21;\necho $name;\necho $age;',1,'2026-08-25 10:36:05','2026-08-25 10:36:05'),(5,5,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\necho $name;',1,'2026-08-25 10:36:05','2026-08-25 10:36:05'),(6,6,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 23;\necho $name;\necho $age;',1,'2026-08-25 10:36:05','2026-08-25 10:36:05'),(7,7,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 21;\necho $name;\necho $age;',1,'2026-08-25 10:37:11','2026-08-25 10:37:11'),(8,8,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\necho $name;',1,'2026-08-25 10:37:11','2026-08-25 10:37:11'),(9,9,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 23;\necho $name;\necho $age;',1,'2026-08-25 10:37:11','2026-08-25 10:37:11'),(10,10,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 24;\necho $name;\necho $age;',1,'2026-08-25 10:39:47','2026-08-25 10:39:47'),(11,11,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 25;\necho $name;\necho $age;',1,'2026-08-25 10:39:47','2026-08-25 10:39:47'),(12,12,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 26;\necho $name;\necho $age;',1,'2026-08-25 10:39:47','2026-08-25 10:39:47'),(13,13,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 21;\necho $name;\necho $age;',1,'2026-08-25 10:45:06','2026-08-25 10:45:06'),(14,14,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\necho $name;',1,'2026-08-25 10:45:06','2026-08-25 10:45:06'),(15,15,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 23;\necho $name;\necho $age;',1,'2026-08-25 10:45:06','2026-08-25 10:45:06'),(28,28,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 21;\necho $name;\necho $age;',1,'2026-08-25 11:33:47','2026-08-25 11:33:47'),(29,29,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\necho $name;',1,'2026-08-25 11:33:47','2026-08-25 11:33:47'),(30,30,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 23;\necho $name;\necho $age;',1,'2026-08-25 11:33:47','2026-08-25 11:33:47'),(31,31,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 24;\necho $name;\necho $age;',1,'2026-08-25 11:33:47','2026-08-25 11:33:47'),(32,32,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 25;\necho $name;\necho $age;',1,'2026-08-25 11:33:47','2026-08-25 11:33:47'),(33,33,'變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變','變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \"小明\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。','$name = \"小明\";\n$age = 26;\necho $name;\necho $age;',1,'2026-08-25 11:33:47','2026-08-25 11:33:47');
/*!40000 ALTER TABLE `knowledge_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_drafts`
--

DROP TABLE IF EXISTS `material_drafts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_drafts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '草稿 ID',
  `course_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '教材名稱',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT 'draft、published 或 archived',
  `tree` json NOT NULL COMMENT '主題／章節／單元／知識卡樹',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_drafts_course_id_foreign` (`course_id`),
  KEY `material_drafts_teacher_id_foreign` (`teacher_id`),
  CONSTRAINT `material_drafts_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `material_drafts_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_drafts`
--

LOCK TABLES `material_drafts` WRITE;
/*!40000 ALTER TABLE `material_drafts` DISABLE KEYS */;
INSERT INTO `material_drafts` VALUES (1,2,2,'PHP 基礎01','archived','{\"topics\": [{\"id\": \"01M0W7Q542T0ATK8VW48WTA3RV\", \"name\": \"PHP 基礎01\", \"chapters\": [{\"id\": \"01M0W7Q542T0ATK8VW48WTA3RW\", \"name\": \"第一章 PHP 說明\", \"units\": [{\"id\": \"01M0W7Q542T0ATK8VW48WTA3RX\", \"name\": \"說明\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W7Q542T0ATK8VW48WTA3RY\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 21;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 1}, {\"id\": \"01M0W7Q543QZCW4BJF6BCZSB3Z\", \"name\": \"第一章 PHP 實作01\", \"units\": [{\"id\": \"01M0W7Q543QZCW4BJF6BCZSB40\", \"name\": \"實作變數01\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W7Q543QZCW4BJF6BCZSB41\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\necho $name;\", \"sort_order\": 1}]}], \"sort_order\": 2}, {\"id\": \"01M0W7Q543QZCW4BJF6BCZSB42\", \"name\": \"第一章 PHP 實作02\", \"units\": [{\"id\": \"01M0W7Q543QZCW4BJF6BCZSB43\", \"name\": \"實作變數02\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W7Q543QZCW4BJF6BCZSB44\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 23;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 3}, {\"id\": \"01M0W7THJMVEF4G4SWHTE2M62X\", \"name\": \"test01\", \"units\": [], \"sort_order\": 4}], \"sort_order\": 1}]}','2026-08-25 10:33:38','2026-08-25 10:36:05'),(2,2,2,'PHP 基礎02','archived','{\"topics\": [{\"id\": \"01M0W7R3GJ0D0GN3JJ5P10HDJY\", \"name\": \"PHP 基礎02\", \"chapters\": [{\"id\": \"01M0W7R3GJ0D0GN3JJ5P10HDJZ\", \"name\": \"第一章 PHP 說明\", \"units\": [{\"id\": \"01M0W7R3GJ0D0GN3JJ5P10HDK0\", \"name\": \"說明\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W7R3GJ0D0GN3JJ5P10HDK1\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 21;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 1}, {\"id\": \"01M0W7R3GJ0D0GN3JJ5P10HDK2\", \"name\": \"第一章 PHP 實作01\", \"units\": [{\"id\": \"01M0W7R3GK920RQDMKCE8Q2A04\", \"name\": \"實作變數01\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W7R3GK920RQDMKCE8Q2A05\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\necho $name;\", \"sort_order\": 1}]}], \"sort_order\": 2}, {\"id\": \"01M0W7R3GK920RQDMKCE8Q2A06\", \"name\": \"第一章 PHP 實作02\", \"units\": [{\"id\": \"01M0W7R3GK920RQDMKCE8Q2A07\", \"name\": \"實作變數02\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W7R3GK920RQDMKCE8Q2A08\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 23;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 3}], \"sort_order\": 1}]}','2026-08-25 10:34:09','2026-08-25 10:38:26'),(3,2,2,'PHP 進階01','archived','{\"topics\": [{\"id\": \"01M0W7RS2V7F5A3SDB758RYYR9\", \"name\": \"PHP 進階01\", \"chapters\": [{\"id\": \"01M0W7RS2V7F5A3SDB758RYYRA\", \"name\": \"第一章 PHP 說明\", \"units\": [{\"id\": \"01M0W7RS2V7F5A3SDB758RYYRB\", \"name\": \"說明\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W7RS2V7F5A3SDB758RYYRC\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 21;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 1}, {\"id\": \"01M0W7RS2V7F5A3SDB758RYYRD\", \"name\": \"第一章 PHP 實作01\", \"units\": [{\"id\": \"01M0W7RS2V7F5A3SDB758RYYRE\", \"name\": \"實作變數01\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W7RS2V7F5A3SDB758RYYRF\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\necho $name;\", \"sort_order\": 1}]}], \"sort_order\": 2}, {\"id\": \"01M0W7RS2V7F5A3SDB758RYYRG\", \"name\": \"第一章 PHP 實作02\", \"units\": [{\"id\": \"01M0W7RS2V7F5A3SDB758RYYRH\", \"name\": \"實作變數02\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W7RS2V7F5A3SDB758RYYRJ\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 23;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 3}], \"sort_order\": 1}]}','2026-08-25 10:34:31','2026-08-25 10:37:11'),(4,2,2,'PHP 進階02','archived','{\"topics\": [{\"id\": \"01M0W7SM309HASHNSRSB9F9DC3\", \"name\": \"PHP 進階02\", \"chapters\": [{\"id\": \"01M0W7SM309HASHNSRSB9F9DC4\", \"name\": \"第一章 PHP 說明\", \"units\": [{\"id\": \"01M0W7SM309HASHNSRSB9F9DC5\", \"name\": \"說明\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W7SM309HASHNSRSB9F9DC6\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 21;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 1}, {\"id\": \"01M0W7SM309HASHNSRSB9F9DC7\", \"name\": \"第一章 PHP 實作01\", \"units\": [{\"id\": \"01M0W7SM309HASHNSRSB9F9DC8\", \"name\": \"實作變數01\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W7SM309HASHNSRSB9F9DC9\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\necho $name;\", \"sort_order\": 1}]}], \"sort_order\": 2}, {\"id\": \"01M0W7SM309HASHNSRSB9F9DCA\", \"name\": \"第一章 PHP 實作02\", \"units\": [{\"id\": \"01M0W7SM309HASHNSRSB9F9DCB\", \"name\": \"實作變數02\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W7SM309HASHNSRSB9F9DCC\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 23;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 3}], \"sort_order\": 1}]}','2026-08-25 10:34:59','2026-08-25 11:32:10'),(5,2,2,'PHP 基礎02','archived','{\"topics\": [{\"id\": \"01M0W7ZNJHB143EEHDYKQSAZYM\", \"name\": \"PHP 基礎02\", \"chapters\": [{\"id\": \"01M0W7ZNJHB143EEHDYKQSAZYN\", \"name\": \"第一章 PHP 說明\", \"units\": [{\"id\": \"01M0W7ZNJHB143EEHDYKQSAZYP\", \"name\": \"說明\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W7ZNJHB143EEHDYKQSAZYQ\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 21;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 1}, {\"id\": \"01M0W7ZNJHB143EEHDYKQSAZYR\", \"name\": \"第一章 PHP 實作01\", \"units\": [{\"id\": \"01M0W7ZNJHB143EEHDYKQSAZYS\", \"name\": \"實作變數01\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W7ZNJHB143EEHDYKQSAZYT\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\necho $name;\", \"sort_order\": 1}]}], \"sort_order\": 2}, {\"id\": \"01M0W7ZNJHB143EEHDYKQSAZYV\", \"name\": \"第一章 PHP 實作02\", \"units\": [{\"id\": \"01M0W7ZNJHB143EEHDYKQSAZYW\", \"name\": \"實作變數02\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W7ZNJHB143EEHDYKQSAZYX\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 23;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 3}], \"sort_order\": 1}]}','2026-08-25 10:38:17','2026-08-25 10:39:47'),(6,2,2,'PHP 基礎02','archived','{\"topics\": [{\"id\": \"01M0W81STN6X6R3DYHNF6X9NNA\", \"name\": \"PHP 基礎02\", \"chapters\": [{\"id\": \"01M0W81STN6X6R3DYHNF6X9NNB\", \"name\": \"第一章 PHP 說明\", \"units\": [{\"id\": \"01M0W81STN6X6R3DYHNF6X9NNC\", \"name\": \"說明\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W81STN6X6R3DYHNF6X9NND\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 21;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 1}, {\"id\": \"01M0W81STN6X6R3DYHNF6X9NNE\", \"name\": \"第一章 PHP 實作01\", \"units\": [{\"id\": \"01M0W81STN6X6R3DYHNF6X9NNF\", \"name\": \"實作變數01\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W81STN6X6R3DYHNF6X9NNG\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\necho $name;\", \"sort_order\": 1}]}], \"sort_order\": 2}, {\"id\": \"01M0W81STN6X6R3DYHNF6X9NNH\", \"name\": \"第一章 PHP 實作02\", \"units\": [{\"id\": \"01M0W81STN6X6R3DYHNF6X9NNJ\", \"name\": \"實作變數02\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W81STN6X6R3DYHNF6X9NNK\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 23;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 3}, {\"id\": \"01M0W81STN6X6R3DYHNF6X9NNM\", \"name\": \"第一章 PHP 實作03\", \"units\": [{\"id\": \"01M0W81STN6X6R3DYHNF6X9NNN\", \"name\": \"實作變數03\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W81STN6X6R3DYHNF6X9NNP\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 24;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 4}, {\"id\": \"01M0W81STN6X6R3DYHNF6X9NNQ\", \"name\": \"第一章 PHP 實作04\", \"units\": [{\"id\": \"01M0W81STN6X6R3DYHNF6X9NNR\", \"name\": \"實作變數04\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W81STN6X6R3DYHNF6X9NNS\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 25;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 5}, {\"id\": \"01M0W81STN6X6R3DYHNF6X9NNT\", \"name\": \"第一章 PHP 實作05\", \"units\": [{\"id\": \"01M0W81STN6X6R3DYHNF6X9NNV\", \"name\": \"實作變數05\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W81STN6X6R3DYHNF6X9NNW\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 26;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 6}], \"sort_order\": 1}]}','2026-08-25 10:39:27','2026-08-25 10:45:06'),(12,2,2,'PHP 實作','archived','{\"topics\": [{\"id\": \"01M0W93D391ZJF1TGH11Z98AJV\", \"name\": \"PHP 實作\", \"chapters\": [{\"id\": \"01M0W93D391ZJF1TGH11Z98AJW\", \"name\": \"第一章 PHP 說明\", \"units\": [{\"id\": \"01M0W93D391ZJF1TGH11Z98AJX\", \"name\": \"說明\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W93D391ZJF1TGH11Z98AJY\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 21;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 1}, {\"id\": \"01M0W93D391ZJF1TGH11Z98AJZ\", \"name\": \"第一章 PHP 實作01\", \"units\": [{\"id\": \"01M0W93D391ZJF1TGH11Z98AK0\", \"name\": \"實作變數01\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W93D391ZJF1TGH11Z98AK1\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\necho $name;\", \"sort_order\": 1}]}], \"sort_order\": 2}, {\"id\": \"01M0W93D391ZJF1TGH11Z98AK2\", \"name\": \"第一章 PHP 實作02\", \"units\": [{\"id\": \"01M0W93D391ZJF1TGH11Z98AK3\", \"name\": \"實作變數02\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W93D391ZJF1TGH11Z98AK4\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 23;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 3}, {\"id\": \"01M0W93D391ZJF1TGH11Z98AK5\", \"name\": \"第一章 PHP 實作03\", \"units\": [{\"id\": \"01M0W93D391ZJF1TGH11Z98AK6\", \"name\": \"實作變數03\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W93D391ZJF1TGH11Z98AK7\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 24;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 4}, {\"id\": \"01M0W93D391ZJF1TGH11Z98AK8\", \"name\": \"第一章 PHP 實作04\", \"units\": [{\"id\": \"01M0W93D3AW1ZSSV5FW9P3WYHA\", \"name\": \"實作變數04\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W93D3AW1ZSSV5FW9P3WYHB\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 25;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 5}, {\"id\": \"01M0W93D3AW1ZSSV5FW9P3WYHC\", \"name\": \"第一章 PHP 實作05\", \"units\": [{\"id\": \"01M0W93D3AW1ZSSV5FW9P3WYHD\", \"name\": \"實作變數05\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0W93D3AW1ZSSV5FW9P3WYHE\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 26;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 6}], \"sort_order\": 1}]}','2026-08-25 10:57:48','2026-08-25 11:33:35'),(13,2,2,'PHP 實作','published','{\"topics\": [{\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC69\", \"name\": \"PHP 實作\", \"chapters\": [{\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6A\", \"name\": \"第一章 PHP 說明\", \"units\": [{\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6B\", \"name\": \"說明\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6C\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 21;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 1}, {\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6D\", \"name\": \"第一章 PHP 實作01\", \"units\": [{\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6E\", \"name\": \"實作變數01\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6F\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\necho $name;\", \"sort_order\": 1}]}], \"sort_order\": 2}, {\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6G\", \"name\": \"第一章 PHP 實作02\", \"units\": [{\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6H\", \"name\": \"實作變數02\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6J\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 23;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 3}, {\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6K\", \"name\": \"第一章 PHP 實作03\", \"units\": [{\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6M\", \"name\": \"實作變數03\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6N\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 24;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 4}, {\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6P\", \"name\": \"第一章 PHP 實作04\", \"units\": [{\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6Q\", \"name\": \"實作變數04\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6R\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 25;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 5}, {\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6S\", \"name\": \"第一章 PHP 實作05\", \"units\": [{\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6T\", \"name\": \"實作變數05\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WB3XQ4YBJDQX33ZE98ZC6V\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 26;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 6}], \"sort_order\": 1}]}','2026-08-25 11:33:02','2026-08-25 11:33:47'),(14,2,2,'PHP 實作02','archived','{\"topics\": [{\"id\": \"01M0WB48ZXJRZNY3DHQ63C3EQD\", \"name\": \"PHP 實作02\", \"chapters\": [{\"id\": \"01M0WB48ZXJRZNY3DHQ63C3EQE\", \"name\": \"第一章 PHP 說明\", \"units\": [{\"id\": \"01M0WB48ZXJRZNY3DHQ63C3EQF\", \"name\": \"說明\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WB48ZXJRZNY3DHQ63C3EQG\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 21;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 1}, {\"id\": \"01M0WB48ZXJRZNY3DHQ63C3EQH\", \"name\": \"第一章 PHP 實作01\", \"units\": [{\"id\": \"01M0WB48ZXJRZNY3DHQ63C3EQJ\", \"name\": \"實作變數01\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WB48ZXJRZNY3DHQ63C3EQK\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\necho $name;\", \"sort_order\": 1}]}], \"sort_order\": 2}, {\"id\": \"01M0WB48ZXJRZNY3DHQ63C3EQM\", \"name\": \"第一章 PHP 實作02\", \"units\": [{\"id\": \"01M0WB48ZXJRZNY3DHQ63C3EQN\", \"name\": \"實作變數02\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WB48ZXJRZNY3DHQ63C3EQP\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 23;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 3}, {\"id\": \"01M0WB48ZY8FSDT7DF6VJT74B2\", \"name\": \"第一章 PHP 實作03\", \"units\": [{\"id\": \"01M0WB48ZY8FSDT7DF6VJT74B3\", \"name\": \"實作變數03\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WB48ZY8FSDT7DF6VJT74B4\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 24;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 4}, {\"id\": \"01M0WB48ZY8FSDT7DF6VJT74B5\", \"name\": \"第一章 PHP 實作04\", \"units\": [{\"id\": \"01M0WB48ZY8FSDT7DF6VJT74B6\", \"name\": \"實作變數04\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WB48ZY8FSDT7DF6VJT74B7\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 25;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 5}, {\"id\": \"01M0WB48ZY8FSDT7DF6VJT74B8\", \"name\": \"第一章 PHP 實作05\", \"units\": [{\"id\": \"01M0WB48ZY8FSDT7DF6VJT74B9\", \"name\": \"實作變數05\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WB48ZY8FSDT7DF6VJT74BA\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 26;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 6}], \"sort_order\": 1}]}','2026-08-25 11:33:13','2026-08-25 11:33:46'),(15,2,2,'PHP 實作02','draft','{\"topics\": [{\"id\": \"01M0WBNS3MC3N6061H2RPZWMD0\", \"name\": \"PHP 實作02\", \"chapters\": [{\"id\": \"01M0WBNS3MC3N6061H2RPZWMD1\", \"name\": \"第一章 PHP 說明\", \"units\": [{\"id\": \"01M0WBNS3MC3N6061H2RPZWMD2\", \"name\": \"說明\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WBNS3MC3N6061H2RPZWMD3\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 21;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 1}, {\"id\": \"01M0WBNS3MC3N6061H2RPZWMD4\", \"name\": \"第一章 PHP 實作01\", \"units\": [{\"id\": \"01M0WBNS3MC3N6061H2RPZWMD5\", \"name\": \"實作變數01\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WBNS3MC3N6061H2RPZWMD6\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\necho $name;\", \"sort_order\": 1}]}], \"sort_order\": 2}, {\"id\": \"01M0WBNS3MC3N6061H2RPZWMD7\", \"name\": \"第一章 PHP 實作02\", \"units\": [{\"id\": \"01M0WBNS3MC3N6061H2RPZWMD8\", \"name\": \"實作變數02\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WBNS3NDPCB58H8ZEKBSJZ3\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 23;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 3}, {\"id\": \"01M0WBNS3NDPCB58H8ZEKBSJZ4\", \"name\": \"第一章 PHP 實作03\", \"units\": [{\"id\": \"01M0WBNS3NDPCB58H8ZEKBSJZ5\", \"name\": \"實作變數03\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WBNS3NDPCB58H8ZEKBSJZ6\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 24;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 4}, {\"id\": \"01M0WBNS3NDPCB58H8ZEKBSJZ7\", \"name\": \"第一章 PHP 實作04\", \"units\": [{\"id\": \"01M0WBNS3NDPCB58H8ZEKBSJZ8\", \"name\": \"實作變數04\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WBNS3NDPCB58H8ZEKBSJZ9\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 25;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 5}, {\"id\": \"01M0WBNS3NDPCB58H8ZEKBSJZA\", \"name\": \"第一章 PHP 實作05\", \"units\": [{\"id\": \"01M0WBNS3NDPCB58H8ZEKBSJZB\", \"name\": \"實作變數05\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WBNS3NDPCB58H8ZEKBSJZC\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 26;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 6}], \"sort_order\": 1}]}','2026-08-25 11:42:47','2026-08-25 11:42:47'),(16,2,2,'PHP 實作03','draft','{\"topics\": [{\"id\": \"01M0WBPEBKET5HN5MX98MQCHXK\", \"name\": \"PHP 實作03\", \"chapters\": [{\"id\": \"01M0WBPEBKET5HN5MX98MQCHXM\", \"name\": \"第一章 PHP 說明\", \"units\": [{\"id\": \"01M0WBPEBKET5HN5MX98MQCHXN\", \"name\": \"說明\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WBPEBKET5HN5MX98MQCHXP\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 21;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 1}, {\"id\": \"01M0WBPEBKET5HN5MX98MQCHXQ\", \"name\": \"第一章 PHP 實作01\", \"units\": [{\"id\": \"01M0WBPEBKET5HN5MX98MQCHXR\", \"name\": \"實作變數01\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WBPEBKET5HN5MX98MQCHXS\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\necho $name;\", \"sort_order\": 1}]}], \"sort_order\": 2}, {\"id\": \"01M0WBPEBKET5HN5MX98MQCHXT\", \"name\": \"第一章 PHP 實作02\", \"units\": [{\"id\": \"01M0WBPEBKET5HN5MX98MQCHXV\", \"name\": \"實作變數02\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WBPEBKET5HN5MX98MQCHXW\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 23;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 3}, {\"id\": \"01M0WBPEBKET5HN5MX98MQCHXX\", \"name\": \"第一章 PHP 實作03\", \"units\": [{\"id\": \"01M0WBPEBKET5HN5MX98MQCHXY\", \"name\": \"實作變數03\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WBPEBKET5HN5MX98MQCHXZ\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 24;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 4}, {\"id\": \"01M0WBPEBKET5HN5MX98MQCHY0\", \"name\": \"第一章 PHP 實作04\", \"units\": [{\"id\": \"01M0WBPEBKET5HN5MX98MQCHY1\", \"name\": \"實作變數04\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WBPEBKET5HN5MX98MQCHY2\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 25;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 5}, {\"id\": \"01M0WBPEBKET5HN5MX98MQCHY3\", \"name\": \"第一章 PHP 實作05\", \"units\": [{\"id\": \"01M0WBPEBKET5HN5MX98MQCHY4\", \"name\": \"實作變數05\", \"sort_order\": 1, \"knowledge_cards\": [{\"id\": \"01M0WBPEBKET5HN5MX98MQCHY5\", \"title\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變\", \"content\": \"變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數，例如 $name = \\\"小明\\\"；。變數名稱需以字母或底線開頭，區分大小寫。使用 = 運算子將值指派給變數，PHP 為弱型別語言，不需要事先宣告型別。函式內宣告的變數僅在該函式內有效，全域變數需用 global 關鍵字才能在函式內存取。\", \"example\": \"$name = \\\"小明\\\";\\n$age = 26;\\necho $name;\\necho $age;\", \"sort_order\": 1}]}], \"sort_order\": 6}], \"sort_order\": 1}]}','2026-08-25 11:43:09','2026-08-25 11:43:09');
/*!40000 ALTER TABLE `material_drafts` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_08_14_000001_create_admins_table',1),(2,'2026_08_14_000002_create_teachers_table',1),(3,'2026_08_14_000003_create_students_table',1),(4,'2026_08_14_000005_create_courses_table',1),(5,'2026_08_14_000006_create_personal_access_tokens_table',1),(6,'2026_08_14_000007_create_enrollments_table',1),(7,'2026_08_14_000008_create_topics_table',1),(8,'2026_08_14_000009_create_chapters_table',1),(9,'2026_08_14_000010_create_units_table',1),(10,'2026_08_14_000011_create_knowledge_cards_table',1),(11,'2026_08_18_000012_create_teacher_applications_table',1),(12,'2026_08_18_000013_create_student_applications_table',1),(13,'2026_08_18_000014_create_student_application_items_table',1),(14,'2026_08_22_000015_create_material_drafts_table',1),(15,'2026_08_23_000016_add_course_id_and_item_status_to_student_applications',1),(16,'2026_08_23_000017_add_example_to_knowledge_cards',1),(17,'2026_08_23_000018_add_account_to_teacher_applications',1),(18,'2026_08_23_000018_create_bloom_table',1),(19,'2026_08_23_000019_create_solo_table',1),(20,'2026_08_23_000019_drop_email_from_student_application_items',1),(21,'2026_08_23_000020_create_questions_table',1),(22,'2026_08_23_000021_create_question_options_table',1),(23,'2026_08_23_000022_create_debug_sub_info_table',1),(24,'2026_08_23_000023_create_coding_sub_info_table',1),(25,'2026_08_23_000024_create_question_bloom_solo_mappings_table',1),(26,'2026_08_23_000025_create_question_records_table',1),(27,'2026_08_23_000026_create_ai_feedback_table',1),(28,'2026_08_24_000020_add_class_name_to_students_table',1),(29,'2026_08_25_000028_make_knowledge_cards_unit_id_nullable',2);
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\Teacher',2,'auth','77e3bd5ceda7bd39b31b109c4d4906937b3454d96e067553ef29e9366a7a09ed','[\"*\"]','2026-08-25 11:43:10',NULL,'2026-08-25 10:27:33','2026-08-25 11:43:10'),(2,'App\\Models\\Student',1,'auth','8fb7f171caad1c85f08edf4aa49077ec6875ce33c51245c7e23b644675e69df1','[\"*\"]',NULL,NULL,'2026-08-25 11:14:29','2026-08-25 11:14:29'),(3,'App\\Models\\Student',1,'auth','044c32e5deff5a7c7242a0506e461a5ad30603a94d41e76eaf3bb4522f95f518','[\"*\"]',NULL,NULL,'2026-08-25 11:54:08','2026-08-25 11:54:08'),(4,'App\\Models\\Student',1,'auth','fe80fbc9d6107cbbc5b22bf554a021278eee871d8e0f55b10d7a1577ae32709f','[\"*\"]','2026-08-25 11:55:20',NULL,'2026-08-25 11:55:20','2026-08-25 11:55:20'),(5,'App\\Models\\Student',1,'auth','97b39568feef7dc3bdada23f84c7445260bb96300ecebda32017eac4dd0fa4c5','[\"*\"]','2026-08-25 12:04:56',NULL,'2026-08-25 11:58:21','2026-08-25 12:04:56');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_bloom_solo_mappings`
--

DROP TABLE IF EXISTS `question_bloom_solo_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_bloom_solo_mappings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '問題與 B&S 映射編號',
  `question_id` bigint unsigned NOT NULL,
  `bloom_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Bloom 編碼',
  `solo_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SOLO 編碼',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `question_bloom_solo_mappings_question_id_bloom_id_solo_id_unique` (`question_id`,`bloom_id`,`solo_id`),
  KEY `question_bloom_solo_mappings_bloom_id_foreign` (`bloom_id`),
  KEY `question_bloom_solo_mappings_solo_id_foreign` (`solo_id`),
  CONSTRAINT `question_bloom_solo_mappings_bloom_id_foreign` FOREIGN KEY (`bloom_id`) REFERENCES `bloom` (`id`),
  CONSTRAINT `question_bloom_solo_mappings_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `question_bloom_solo_mappings_solo_id_foreign` FOREIGN KEY (`solo_id`) REFERENCES `solo` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_bloom_solo_mappings`
--

LOCK TABLES `question_bloom_solo_mappings` WRITE;
/*!40000 ALTER TABLE `question_bloom_solo_mappings` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_bloom_solo_mappings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_knowledge_cards`
--

DROP TABLE IF EXISTS `question_knowledge_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_knowledge_cards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '流水號',
  `question_id` bigint unsigned NOT NULL COMMENT '題目編號',
  `knowledge_card_id` bigint unsigned NOT NULL COMMENT '知識卡編號',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `question_knowledge_cards_question_id_knowledge_card_id_unique` (`question_id`,`knowledge_card_id`),
  KEY `question_knowledge_cards_knowledge_card_id_foreign` (`knowledge_card_id`),
  CONSTRAINT `question_knowledge_cards_knowledge_card_id_foreign` FOREIGN KEY (`knowledge_card_id`) REFERENCES `knowledge_cards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `question_knowledge_cards_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='題目與知識卡';
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
  `question_mapping_id` bigint unsigned NOT NULL,
  `system_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending、correct 或 wrong',
  `teacher_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending、correct 或 wrong',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `question_records_student_id_foreign` (`student_id`),
  KEY `question_records_question_id_foreign` (`question_id`),
  KEY `question_records_question_mapping_id_foreign` (`question_mapping_id`),
  CONSTRAINT `question_records_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`),
  CONSTRAINT `question_records_question_mapping_id_foreign` FOREIGN KEY (`question_mapping_id`) REFERENCES `question_bloom_solo_mappings` (`id`),
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
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '題目編號',
  `course_id` bigint unsigned NOT NULL COMMENT '所屬課程 ID',
  `teacher_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '題目標題',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'choice、debug 或 coding',
  `question_content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '題目內容',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `questions_teacher_id_foreign` (`teacher_id`),
  KEY `questions_course_id_foreign` (`course_id`),
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
-- Table structure for table `solo`
--

DROP TABLE IF EXISTS `solo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solo` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SOLO 編碼',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SOLO 標題',
  `structure_info` text COLLATE utf8mb4_unicode_ci COMMENT '結構層級說明',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '建立時間',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新時間',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solo`
--

LOCK TABLES `solo` WRITE;
/*!40000 ALTER TABLE `solo` DISABLE KEYS */;
INSERT INTO `solo` VALUES ('S1','前結構','未掌握題意或回答與題目無關','2026-08-24 11:52:23','2026-08-24 11:52:23'),('S2','單一結構','只用到單一要點','2026-08-24 11:52:23','2026-08-24 11:52:23'),('S3','多元結構','列出多個要點但尚未整合','2026-08-24 11:52:23','2026-08-24 11:52:23'),('S4','關聯結構','能串連多個要點成整體','2026-08-24 11:52:23','2026-08-24 11:52:23'),('S5','延伸抽象','能類推到新情境並抽象化','2026-08-24 11:52:23','2026-08-24 11:52:23');
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
INSERT INTO `students` VALUES (1,'$2y$12$dNFYn6bQ4i5bGeSW.UnSiOSyxIQk2S/x5QjUGNXGih4adJg2ZCJdS','1411131000','王小明','資應二甲','s1411131000@nutc.edu.tw','2026-08-24 11:52:24','2026-08-24 11:52:24');
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
INSERT INTO `teachers` VALUES (1,'teacher@school.edu.tw','$2y$12$dNFYn6bQ4i5bGeSW.UnSiOSyxIQk2S/x5QjUGNXGih4adJg2ZCJdS','許老師','teacher@school.edu.tw','2026-08-24 11:52:24','2026-08-24 11:52:24'),(2,'teacher2@school.edu.tw','$2y$12$dNFYn6bQ4i5bGeSW.UnSiOSyxIQk2S/x5QjUGNXGih4adJg2ZCJdS','陳老師','teacher2@school.edu.tw','2026-08-24 11:52:24','2026-08-24 11:52:24');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topics`
--

LOCK TABLES `topics` WRITE;
/*!40000 ALTER TABLE `topics` DISABLE KEYS */;
INSERT INTO `topics` VALUES (1,2,'PHP 基礎01',1,'2026-08-25 10:35:50','2026-08-25 10:35:50'),(2,2,'PHP 進階01',1,'2026-08-25 10:36:05','2026-08-25 10:36:05'),(3,2,'PHP 基礎02',1,'2026-08-25 10:37:11','2026-08-25 10:39:47'),(4,2,'PHP 進階02',1,'2026-08-25 10:45:06','2026-08-25 10:45:06'),(7,2,'PHP 實作',1,'2026-08-25 11:33:46','2026-08-25 11:33:47');
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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (1,1,'說明',1,'2026-08-25 10:35:50','2026-08-25 10:35:50'),(2,2,'實作變數01',1,'2026-08-25 10:35:50','2026-08-25 10:35:50'),(3,3,'實作變數02',1,'2026-08-25 10:35:50','2026-08-25 10:35:50'),(4,5,'說明',1,'2026-08-25 10:36:05','2026-08-25 10:36:05'),(5,6,'實作變數01',1,'2026-08-25 10:36:05','2026-08-25 10:36:05'),(6,7,'實作變數02',1,'2026-08-25 10:36:05','2026-08-25 10:36:05'),(7,8,'說明',1,'2026-08-25 10:37:11','2026-08-25 10:37:11'),(8,9,'實作變數01',1,'2026-08-25 10:37:11','2026-08-25 10:37:11'),(9,10,'實作變數02',1,'2026-08-25 10:37:11','2026-08-25 10:37:11'),(10,11,'實作變數03',1,'2026-08-25 10:39:47','2026-08-25 10:39:47'),(11,12,'實作變數04',1,'2026-08-25 10:39:47','2026-08-25 10:39:47'),(12,13,'實作變數05',1,'2026-08-25 10:39:47','2026-08-25 10:39:47'),(13,14,'說明',1,'2026-08-25 10:45:06','2026-08-25 10:45:06'),(14,15,'實作變數01',1,'2026-08-25 10:45:06','2026-08-25 10:45:06'),(15,16,'實作變數02',1,'2026-08-25 10:45:06','2026-08-25 10:45:06'),(28,29,'說明',1,'2026-08-25 11:33:47','2026-08-25 11:33:47'),(29,30,'實作變數01',1,'2026-08-25 11:33:47','2026-08-25 11:33:47'),(30,31,'實作變數02',1,'2026-08-25 11:33:47','2026-08-25 11:33:47'),(31,32,'實作變數03',1,'2026-08-25 11:33:47','2026-08-25 11:33:47'),(32,33,'實作變數04',1,'2026-08-25 11:33:47','2026-08-25 11:33:47'),(33,34,'實作變數05',1,'2026-08-25 11:33:47','2026-08-25 11:33:47');
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

-- Dump completed on 2026-08-25 20:07:31
