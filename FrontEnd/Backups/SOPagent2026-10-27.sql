-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: SOPAgent
-- ------------------------------------------------------
-- Server version	8.0.46

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
-- Current Database: `SOPAgent`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `SOPAgent` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `SOPAgent`;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `AdminId` int unsigned NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  PRIMARY KEY (`AdminId`),
  UNIQUE KEY `Username` (`Username`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (19,'admin','a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prompts`
--

DROP TABLE IF EXISTS `prompts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prompts` (
  `PromptId` int unsigned NOT NULL AUTO_INCREMENT,
  `UserId` int unsigned DEFAULT NULL,
  `AdminId` int unsigned DEFAULT NULL,
  `PromptText` text NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`PromptId`),
  KEY `UserId` (`UserId`),
  KEY `AdminId` (`AdminId`),
  CONSTRAINT `prompts_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE,
  CONSTRAINT `prompts_ibfk_2` FOREIGN KEY (`AdminId`) REFERENCES `admins` (`AdminId`) ON DELETE CASCADE,
  CONSTRAINT `prompts_chk_1` CHECK (((`UserId` is null) <> (`AdminId` is null)))
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prompts`
--

LOCK TABLES `prompts` WRITE;
/*!40000 ALTER TABLE `prompts` DISABLE KEYS */;
INSERT INTO `prompts` VALUES (1,12,NULL,'TEST DATA - today prompt 10','2026-10-12 01:59:00');
INSERT INTO `prompts` VALUES (2,NULL,19,'TEST DATA - today prompt 9','2026-10-12 03:34:51');
INSERT INTO `prompts` VALUES (3,12,NULL,'TEST DATA - today prompt 8','2026-10-12 13:57:16');
INSERT INTO `prompts` VALUES (4,12,NULL,'TEST DATA - today prompt 7','2026-10-12 11:20:48');
INSERT INTO `prompts` VALUES (5,12,NULL,'TEST DATA - today prompt 6','2026-10-12 00:18:35');
INSERT INTO `prompts` VALUES (6,NULL,19,'TEST DATA - today prompt 5','2026-10-12 02:37:50');
INSERT INTO `prompts` VALUES (7,12,NULL,'TEST DATA - today prompt 4','2026-10-12 14:13:26');
INSERT INTO `prompts` VALUES (8,12,NULL,'TEST DATA - today prompt 3','2026-10-11 22:59:02');
INSERT INTO `prompts` VALUES (9,12,NULL,'TEST DATA - today prompt 2','2026-10-12 03:55:52');
INSERT INTO `prompts` VALUES (10,NULL,19,'TEST DATA - today prompt 1','2026-10-12 08:08:33');
INSERT INTO `prompts` VALUES (16,12,NULL,'TEST DATA - this month prompt 10','2026-10-11 00:26:29');
INSERT INTO `prompts` VALUES (17,NULL,19,'TEST DATA - this month prompt 9','2026-10-06 08:06:03');
INSERT INTO `prompts` VALUES (18,12,NULL,'TEST DATA - this month prompt 8','2026-10-05 23:56:32');
INSERT INTO `prompts` VALUES (19,12,NULL,'TEST DATA - this month prompt 7','2026-10-11 15:59:45');
INSERT INTO `prompts` VALUES (20,12,NULL,'TEST DATA - this month prompt 6','2026-10-09 10:26:33');
INSERT INTO `prompts` VALUES (21,NULL,19,'TEST DATA - this month prompt 5','2026-10-04 00:26:51');
INSERT INTO `prompts` VALUES (22,12,NULL,'TEST DATA - this month prompt 4','2026-10-06 04:31:14');
INSERT INTO `prompts` VALUES (23,12,NULL,'TEST DATA - this month prompt 3','2026-10-09 03:52:00');
INSERT INTO `prompts` VALUES (24,12,NULL,'TEST DATA - this month prompt 2','2026-10-09 02:47:00');
INSERT INTO `prompts` VALUES (25,NULL,19,'TEST DATA - this month prompt 1','2026-10-08 12:05:07');
INSERT INTO `prompts` VALUES (26,12,NULL,'TEST DATA - this month prompt 20','2026-10-01 06:34:16');
INSERT INTO `prompts` VALUES (27,12,NULL,'TEST DATA - this month prompt 19','2026-10-08 10:05:10');
INSERT INTO `prompts` VALUES (28,12,NULL,'TEST DATA - this month prompt 18','2026-10-05 06:29:45');
INSERT INTO `prompts` VALUES (29,NULL,19,'TEST DATA - this month prompt 17','2026-10-08 02:38:49');
INSERT INTO `prompts` VALUES (30,12,NULL,'TEST DATA - this month prompt 16','2026-10-01 09:49:53');
INSERT INTO `prompts` VALUES (31,12,NULL,'TEST DATA - this month prompt 15','2026-10-05 12:58:22');
INSERT INTO `prompts` VALUES (32,12,NULL,'TEST DATA - this month prompt 14','2026-10-10 08:00:06');
INSERT INTO `prompts` VALUES (33,NULL,19,'TEST DATA - this month prompt 13','2026-10-06 05:16:41');
INSERT INTO `prompts` VALUES (34,12,NULL,'TEST DATA - this month prompt 12','2026-10-10 22:16:18');
INSERT INTO `prompts` VALUES (35,12,NULL,'TEST DATA - this month prompt 11','2026-10-02 09:39:29');
INSERT INTO `prompts` VALUES (36,12,NULL,'TEST DATA - this month prompt 30','2026-10-02 01:03:27');
INSERT INTO `prompts` VALUES (37,NULL,19,'TEST DATA - this month prompt 29','2026-10-03 23:10:27');
INSERT INTO `prompts` VALUES (38,12,NULL,'TEST DATA - this month prompt 28','2026-10-05 16:47:23');
INSERT INTO `prompts` VALUES (39,12,NULL,'TEST DATA - this month prompt 27','2026-10-09 08:26:30');
INSERT INTO `prompts` VALUES (40,12,NULL,'TEST DATA - this month prompt 26','2026-10-10 03:17:02');
INSERT INTO `prompts` VALUES (41,NULL,19,'TEST DATA - this month prompt 25','2026-10-05 04:25:20');
INSERT INTO `prompts` VALUES (42,12,NULL,'TEST DATA - this month prompt 24','2026-10-02 00:28:07');
INSERT INTO `prompts` VALUES (43,12,NULL,'TEST DATA - this month prompt 23','2026-10-11 11:06:17');
INSERT INTO `prompts` VALUES (44,12,NULL,'TEST DATA - this month prompt 22','2026-10-09 08:46:18');
INSERT INTO `prompts` VALUES (45,NULL,19,'TEST DATA - this month prompt 21','2026-10-09 12:43:21');
INSERT INTO `prompts` VALUES (46,12,NULL,'TEST DATA - this month prompt 40','2026-10-08 12:46:55');
INSERT INTO `prompts` VALUES (47,12,NULL,'TEST DATA - this month prompt 39','2026-10-01 02:21:16');
INSERT INTO `prompts` VALUES (48,12,NULL,'TEST DATA - this month prompt 38','2026-10-10 19:41:51');
INSERT INTO `prompts` VALUES (49,NULL,19,'TEST DATA - this month prompt 37','2026-10-10 11:03:49');
INSERT INTO `prompts` VALUES (50,12,NULL,'TEST DATA - this month prompt 36','2026-10-02 02:56:03');
INSERT INTO `prompts` VALUES (51,12,NULL,'TEST DATA - this month prompt 35','2026-10-05 22:10:46');
INSERT INTO `prompts` VALUES (52,12,NULL,'TEST DATA - this month prompt 34','2026-10-06 05:00:09');
INSERT INTO `prompts` VALUES (53,NULL,19,'TEST DATA - this month prompt 33','2026-10-01 09:25:52');
INSERT INTO `prompts` VALUES (54,12,NULL,'TEST DATA - this month prompt 32','2026-10-02 08:10:22');
INSERT INTO `prompts` VALUES (55,12,NULL,'TEST DATA - this month prompt 31','2026-10-07 16:24:08');
INSERT INTO `prompts` VALUES (56,12,NULL,'TEST DATA - this month prompt 50','2026-10-01 16:05:23');
INSERT INTO `prompts` VALUES (57,NULL,19,'TEST DATA - this month prompt 49','2026-10-09 08:05:15');
INSERT INTO `prompts` VALUES (58,12,NULL,'TEST DATA - this month prompt 48','2026-10-10 02:51:27');
INSERT INTO `prompts` VALUES (59,12,NULL,'TEST DATA - this month prompt 47','2026-10-03 23:30:24');
INSERT INTO `prompts` VALUES (60,12,NULL,'TEST DATA - this month prompt 46','2026-10-04 06:54:26');
INSERT INTO `prompts` VALUES (61,NULL,19,'TEST DATA - this month prompt 45','2026-10-11 09:40:46');
INSERT INTO `prompts` VALUES (62,12,NULL,'TEST DATA - this month prompt 44','2026-10-08 19:44:46');
INSERT INTO `prompts` VALUES (63,12,NULL,'TEST DATA - this month prompt 43','2026-10-06 18:03:51');
INSERT INTO `prompts` VALUES (64,12,NULL,'TEST DATA - this month prompt 42','2026-10-08 15:30:13');
INSERT INTO `prompts` VALUES (65,NULL,19,'TEST DATA - this month prompt 41','2026-10-08 05:59:28');
INSERT INTO `prompts` VALUES (66,12,NULL,'TEST DATA - this month prompt 60','2026-10-07 16:18:07');
INSERT INTO `prompts` VALUES (67,12,NULL,'TEST DATA - this month prompt 59','2026-10-02 09:49:01');
INSERT INTO `prompts` VALUES (68,12,NULL,'TEST DATA - this month prompt 58','2026-10-11 09:48:25');
INSERT INTO `prompts` VALUES (69,NULL,19,'TEST DATA - this month prompt 57','2026-10-06 19:58:57');
INSERT INTO `prompts` VALUES (70,12,NULL,'TEST DATA - this month prompt 56','2026-10-02 21:24:27');
INSERT INTO `prompts` VALUES (71,12,NULL,'TEST DATA - this month prompt 55','2026-10-05 04:54:01');
INSERT INTO `prompts` VALUES (72,12,NULL,'TEST DATA - this month prompt 54','2026-10-02 14:26:28');
INSERT INTO `prompts` VALUES (73,NULL,19,'TEST DATA - this month prompt 53','2026-10-02 08:16:37');
INSERT INTO `prompts` VALUES (74,12,NULL,'TEST DATA - this month prompt 52','2026-10-10 21:17:47');
INSERT INTO `prompts` VALUES (75,12,NULL,'TEST DATA - this month prompt 51','2026-10-04 12:08:30');
INSERT INTO `prompts` VALUES (79,12,NULL,'TEST DATA - earlier this year prompt 10','2026-01-13 09:01:21');
INSERT INTO `prompts` VALUES (80,NULL,19,'TEST DATA - earlier this year prompt 9','2026-02-13 08:09:16');
INSERT INTO `prompts` VALUES (81,12,NULL,'TEST DATA - earlier this year prompt 8','2026-06-23 19:44:00');
INSERT INTO `prompts` VALUES (82,12,NULL,'TEST DATA - earlier this year prompt 7','2026-06-20 07:32:41');
INSERT INTO `prompts` VALUES (83,12,NULL,'TEST DATA - earlier this year prompt 6','2026-02-02 07:45:00');
INSERT INTO `prompts` VALUES (84,NULL,19,'TEST DATA - earlier this year prompt 5','2026-07-03 01:20:58');
INSERT INTO `prompts` VALUES (85,12,NULL,'TEST DATA - earlier this year prompt 4','2026-07-06 21:53:22');
INSERT INTO `prompts` VALUES (86,12,NULL,'TEST DATA - earlier this year prompt 3','2026-09-11 13:45:30');
INSERT INTO `prompts` VALUES (87,12,NULL,'TEST DATA - earlier this year prompt 2','2026-05-16 10:24:10');
INSERT INTO `prompts` VALUES (88,NULL,19,'TEST DATA - earlier this year prompt 1','2026-01-26 19:49:47');
INSERT INTO `prompts` VALUES (89,12,NULL,'TEST DATA - earlier this year prompt 20','2026-03-16 13:08:25');
INSERT INTO `prompts` VALUES (90,12,NULL,'TEST DATA - earlier this year prompt 19','2026-04-03 17:11:52');
INSERT INTO `prompts` VALUES (91,12,NULL,'TEST DATA - earlier this year prompt 18','2026-09-26 10:29:57');
INSERT INTO `prompts` VALUES (92,NULL,19,'TEST DATA - earlier this year prompt 17','2026-06-27 14:42:13');
INSERT INTO `prompts` VALUES (93,12,NULL,'TEST DATA - earlier this year prompt 16','2026-05-24 10:52:37');
INSERT INTO `prompts` VALUES (94,12,NULL,'TEST DATA - earlier this year prompt 15','2026-01-30 20:19:50');
INSERT INTO `prompts` VALUES (95,12,NULL,'TEST DATA - earlier this year prompt 14','2026-03-31 18:23:22');
INSERT INTO `prompts` VALUES (96,NULL,19,'TEST DATA - earlier this year prompt 13','2026-03-13 16:25:41');
INSERT INTO `prompts` VALUES (97,12,NULL,'TEST DATA - earlier this year prompt 12','2026-01-14 20:37:41');
INSERT INTO `prompts` VALUES (98,12,NULL,'TEST DATA - earlier this year prompt 11','2026-06-03 22:04:46');
INSERT INTO `prompts` VALUES (99,12,NULL,'TEST DATA - earlier this year prompt 30','2026-03-28 11:51:30');
INSERT INTO `prompts` VALUES (100,NULL,19,'TEST DATA - earlier this year prompt 29','2026-09-13 20:35:40');
INSERT INTO `prompts` VALUES (101,12,NULL,'TEST DATA - earlier this year prompt 28','2026-09-04 14:34:18');
INSERT INTO `prompts` VALUES (102,12,NULL,'TEST DATA - earlier this year prompt 27','2026-07-22 13:30:47');
INSERT INTO `prompts` VALUES (103,12,NULL,'TEST DATA - earlier this year prompt 26','2025-12-31 23:36:07');
INSERT INTO `prompts` VALUES (104,NULL,19,'TEST DATA - earlier this year prompt 25','2026-04-01 08:49:20');
INSERT INTO `prompts` VALUES (105,12,NULL,'TEST DATA - earlier this year prompt 24','2026-03-14 21:12:16');
INSERT INTO `prompts` VALUES (106,12,NULL,'TEST DATA - earlier this year prompt 23','2026-01-12 05:29:06');
INSERT INTO `prompts` VALUES (107,12,NULL,'TEST DATA - earlier this year prompt 22','2026-04-28 03:22:30');
INSERT INTO `prompts` VALUES (108,NULL,19,'TEST DATA - earlier this year prompt 21','2026-08-13 08:45:47');
INSERT INTO `prompts` VALUES (109,12,NULL,'TEST DATA - earlier this year prompt 40','2026-07-30 10:07:35');
INSERT INTO `prompts` VALUES (110,12,NULL,'TEST DATA - earlier this year prompt 39','2026-02-28 11:31:54');
INSERT INTO `prompts` VALUES (111,12,NULL,'TEST DATA - earlier this year prompt 38','2026-02-16 01:57:58');
INSERT INTO `prompts` VALUES (112,NULL,19,'TEST DATA - earlier this year prompt 37','2026-03-26 23:34:22');
INSERT INTO `prompts` VALUES (113,12,NULL,'TEST DATA - earlier this year prompt 36','2026-04-17 16:11:27');
INSERT INTO `prompts` VALUES (114,12,NULL,'TEST DATA - earlier this year prompt 35','2026-06-18 17:28:24');
INSERT INTO `prompts` VALUES (115,12,NULL,'TEST DATA - earlier this year prompt 34','2026-02-26 12:16:25');
INSERT INTO `prompts` VALUES (116,NULL,19,'TEST DATA - earlier this year prompt 33','2026-04-08 21:56:32');
INSERT INTO `prompts` VALUES (117,12,NULL,'TEST DATA - earlier this year prompt 32','2026-09-08 12:26:34');
INSERT INTO `prompts` VALUES (118,12,NULL,'TEST DATA - earlier this year prompt 31','2026-03-10 08:55:59');
INSERT INTO `prompts` VALUES (119,12,NULL,'TEST DATA - earlier this year prompt 50','2026-05-23 03:44:33');
INSERT INTO `prompts` VALUES (120,NULL,19,'TEST DATA - earlier this year prompt 49','2026-06-22 08:42:41');
INSERT INTO `prompts` VALUES (121,12,NULL,'TEST DATA - earlier this year prompt 48','2026-04-01 05:50:12');
INSERT INTO `prompts` VALUES (122,12,NULL,'TEST DATA - earlier this year prompt 47','2026-06-22 02:29:48');
INSERT INTO `prompts` VALUES (123,12,NULL,'TEST DATA - earlier this year prompt 46','2026-01-10 12:54:18');
INSERT INTO `prompts` VALUES (124,NULL,19,'TEST DATA - earlier this year prompt 45','2026-09-29 00:43:36');
INSERT INTO `prompts` VALUES (125,12,NULL,'TEST DATA - earlier this year prompt 44','2026-06-08 11:39:47');
INSERT INTO `prompts` VALUES (126,12,NULL,'TEST DATA - earlier this year prompt 43','2026-01-28 17:03:31');
INSERT INTO `prompts` VALUES (127,12,NULL,'TEST DATA - earlier this year prompt 42','2026-07-02 21:08:47');
INSERT INTO `prompts` VALUES (128,NULL,19,'TEST DATA - earlier this year prompt 41','2026-08-11 02:15:15');
INSERT INTO `prompts` VALUES (142,NULL,19,'hallo','2026-10-13 11:11:17');
INSERT INTO `prompts` VALUES (143,NULL,19,'what is the correct way to dispose of radioactive waste?','2026-10-26 14:13:11');
INSERT INTO `prompts` VALUES (144,7,NULL,'how do i dispose of radioactive waste?','2026-10-26 14:16:12');
INSERT INTO `prompts` VALUES (145,7,NULL,'what is the correct way to dispose of radioactive waste?','2026-10-26 14:18:41');
INSERT INTO `prompts` VALUES (146,7,NULL,'what is the correct way to dispose of radioactive waste?','2026-10-26 14:19:45');
INSERT INTO `prompts` VALUES (147,7,NULL,'what is the correct way to dispose of radioactive waste?','2026-10-26 14:21:07');
INSERT INTO `prompts` VALUES (148,7,NULL,'what is the correct way to dispose of radioactive waste?','2026-10-26 14:34:23');
/*!40000 ALTER TABLE `prompts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `responses`
--

DROP TABLE IF EXISTS `responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `responses` (
  `ResponseId` int unsigned NOT NULL AUTO_INCREMENT,
  `PromptId` int unsigned NOT NULL,
  `UserId` int unsigned DEFAULT NULL,
  `AdminId` int unsigned DEFAULT NULL,
  `ResponseText` mediumtext NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ResponseId`),
  KEY `PromptId` (`PromptId`),
  KEY `UserId` (`UserId`),
  KEY `AdminId` (`AdminId`),
  CONSTRAINT `responses_ibfk_1` FOREIGN KEY (`PromptId`) REFERENCES `prompts` (`PromptId`) ON DELETE CASCADE,
  CONSTRAINT `responses_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE,
  CONSTRAINT `responses_ibfk_3` FOREIGN KEY (`AdminId`) REFERENCES `admins` (`AdminId`) ON DELETE CASCADE,
  CONSTRAINT `responses_chk_1` CHECK (((`UserId` is null) <> (`AdminId` is null)))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `responses`
--

LOCK TABLES `responses` WRITE;
/*!40000 ALTER TABLE `responses` DISABLE KEYS */;
INSERT INTO `responses` VALUES (1,143,NULL,19,'Traceback (most recent call last):\n  File \"C:\\Users\\tygra\\Documents\\Code\\Pythom\\LLM\\LocalAITest.py\", line 29, in <module>\n    with open(PASSAGES_FOLDER / \"passages.jsonl\", encoding=\"utf-8\") as f:\n         ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^\nFileNotFoundError: [Errno 2] No such file or directory: \'C:\\\\Users\\\\tygra\\\\Documents\\\\Code\\\\Pythom\\\\LLM\\\\PreProcessing\\\\Raw_Text\\\\passages.jsonl\'\n','2026-10-26 14:15:29');
INSERT INTO `responses` VALUES (2,144,7,NULL,'Traceback (most recent call last):\n  File \"C:\\Users\\tygra\\Documents\\Code\\Pythom\\LLM\\LocalAITest.py\", line 29, in <module>\n    with open(PASSAGES_FOLDER / \"passages.jsonl\", encoding=\"utf-8\") as f:\n         ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^\nFileNotFoundError: [Errno 2] No such file or directory: \'C:\\\\Users\\\\tygra\\\\Documents\\\\Code\\\\Pythom\\\\LLM\\\\PreProcessing\\\\Raw_Text\\\\passages.jsonl\'\n','2026-10-26 14:16:39');
INSERT INTO `responses` VALUES (3,145,7,NULL,'Traceback (most recent call last):\n  File \"C:\\Users\\tygra\\Documents\\Code\\Pythom\\LLM\\LocalAITest.py\", line 29, in <module>\n    with open(PASSAGES_FOLDER / \"passages.jsonl\", encoding=\"utf-8\") as f:\n         ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^\nFileNotFoundError: [Errno 2] No such file or directory: \'C:\\\\Users\\\\tygra\\\\Documents\\\\Code\\\\Pythom\\\\LLM\\\\PreProcessing\\\\Raw_Text\\\\passages.jsonl\'\n','2026-10-26 14:19:07');
INSERT INTO `responses` VALUES (4,146,7,NULL,'Traceback (most recent call last):\n  File \"C:\\Users\\tygra\\Documents\\Code\\Pythom\\LLM\\LocalAITest.py\", line 31, in <module>\n    vectors = np.load(VECTORS_FOLDER / \"passage_vectors.npy\")\n              ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^\n  File \"C:\\Users\\tygra\\AppData\\Local\\Programs\\Python\\Python312\\Lib\\site-packages\\numpy\\lib\\_npyio_impl.py\", line 455, in load\n    fid = stack.enter_context(open(os.fspath(file), \"rb\"))\n                              ^^^^^^^^^^^^^^^^^^^^^^^^^^^\nFileNotFoundError: [Errno 2] No such file or directory: \'C:\\\\Users\\\\tygra\\\\Documents\\\\Code\\\\Pythom\\\\LLM\\\\PreProcessing\\\\Embeds\\\\passage_vectors.npy\'\n','2026-10-26 14:20:10');
INSERT INTO `responses` VALUES (5,147,7,NULL,'Ollama isn\'t running. Start the Ollama app and try again.\n','2026-10-26 14:21:37');
INSERT INTO `responses` VALUES (6,148,7,NULL,'Ollama isn\'t running. Start the Ollama app and try again.\n','2026-10-26 14:34:57');
/*!40000 ALTER TABLE `responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `UserId` int unsigned NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  PRIMARY KEY (`UserId`),
  UNIQUE KEY `Username` (`Username`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (7,'user','a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3');
INSERT INTO `users` VALUES (12,'testuser1','a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3');
INSERT INTO `users` VALUES (13,'testuser2','a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3');
INSERT INTO `users` VALUES (14,'testuser3','a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3');
INSERT INTO `users` VALUES (15,'testuser4','a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3');
INSERT INTO `users` VALUES (16,'testuser5','a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-27  9:04:20
