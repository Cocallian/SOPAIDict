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
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=169 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prompts`
--

LOCK TABLES `prompts` WRITE;
/*!40000 ALTER TABLE `prompts` DISABLE KEYS */;
INSERT INTO `prompts` VALUES (2,NULL,19,'TEST DATA - today prompt 9','2026-10-12 03:34:51');
INSERT INTO `prompts` VALUES (6,NULL,19,'TEST DATA - today prompt 5','2026-10-12 02:37:50');
INSERT INTO `prompts` VALUES (10,NULL,19,'TEST DATA - today prompt 1','2026-10-12 08:08:33');
INSERT INTO `prompts` VALUES (17,NULL,19,'TEST DATA - this month prompt 9','2026-10-06 08:06:03');
INSERT INTO `prompts` VALUES (21,NULL,19,'TEST DATA - this month prompt 5','2026-10-04 00:26:51');
INSERT INTO `prompts` VALUES (25,NULL,19,'TEST DATA - this month prompt 1','2026-10-08 12:05:07');
INSERT INTO `prompts` VALUES (29,NULL,19,'TEST DATA - this month prompt 17','2026-10-08 02:38:49');
INSERT INTO `prompts` VALUES (33,NULL,19,'TEST DATA - this month prompt 13','2026-10-06 05:16:41');
INSERT INTO `prompts` VALUES (37,NULL,19,'TEST DATA - this month prompt 29','2026-10-03 23:10:27');
INSERT INTO `prompts` VALUES (41,NULL,19,'TEST DATA - this month prompt 25','2026-10-05 04:25:20');
INSERT INTO `prompts` VALUES (45,NULL,19,'TEST DATA - this month prompt 21','2026-10-09 12:43:21');
INSERT INTO `prompts` VALUES (49,NULL,19,'TEST DATA - this month prompt 37','2026-10-10 11:03:49');
INSERT INTO `prompts` VALUES (53,NULL,19,'TEST DATA - this month prompt 33','2026-10-01 09:25:52');
INSERT INTO `prompts` VALUES (57,NULL,19,'TEST DATA - this month prompt 49','2026-10-09 08:05:15');
INSERT INTO `prompts` VALUES (61,NULL,19,'TEST DATA - this month prompt 45','2026-10-11 09:40:46');
INSERT INTO `prompts` VALUES (65,NULL,19,'TEST DATA - this month prompt 41','2026-10-08 05:59:28');
INSERT INTO `prompts` VALUES (69,NULL,19,'TEST DATA - this month prompt 57','2026-10-06 19:58:57');
INSERT INTO `prompts` VALUES (73,NULL,19,'TEST DATA - this month prompt 53','2026-10-02 08:16:37');
INSERT INTO `prompts` VALUES (80,NULL,19,'TEST DATA - earlier this year prompt 9','2026-02-13 08:09:16');
INSERT INTO `prompts` VALUES (84,NULL,19,'TEST DATA - earlier this year prompt 5','2026-07-03 01:20:58');
INSERT INTO `prompts` VALUES (88,NULL,19,'TEST DATA - earlier this year prompt 1','2026-01-26 19:49:47');
INSERT INTO `prompts` VALUES (92,NULL,19,'TEST DATA - earlier this year prompt 17','2026-06-27 14:42:13');
INSERT INTO `prompts` VALUES (96,NULL,19,'TEST DATA - earlier this year prompt 13','2026-03-13 16:25:41');
INSERT INTO `prompts` VALUES (100,NULL,19,'TEST DATA - earlier this year prompt 29','2026-09-13 20:35:40');
INSERT INTO `prompts` VALUES (104,NULL,19,'TEST DATA - earlier this year prompt 25','2026-04-01 08:49:20');
INSERT INTO `prompts` VALUES (108,NULL,19,'TEST DATA - earlier this year prompt 21','2026-08-13 08:45:47');
INSERT INTO `prompts` VALUES (112,NULL,19,'TEST DATA - earlier this year prompt 37','2026-03-26 23:34:22');
INSERT INTO `prompts` VALUES (116,NULL,19,'TEST DATA - earlier this year prompt 33','2026-04-08 21:56:32');
INSERT INTO `prompts` VALUES (120,NULL,19,'TEST DATA - earlier this year prompt 49','2026-06-22 08:42:41');
INSERT INTO `prompts` VALUES (124,NULL,19,'TEST DATA - earlier this year prompt 45','2026-09-29 00:43:36');
INSERT INTO `prompts` VALUES (128,NULL,19,'TEST DATA - earlier this year prompt 41','2026-08-11 02:15:15');
INSERT INTO `prompts` VALUES (142,NULL,19,'hallo','2026-10-13 11:11:17');
INSERT INTO `prompts` VALUES (143,NULL,19,'what is the correct way to dispose of radioactive waste?','2026-10-26 14:13:11');
INSERT INTO `prompts` VALUES (144,7,NULL,'how do i dispose of radioactive waste?','2026-10-26 14:16:12');
INSERT INTO `prompts` VALUES (145,7,NULL,'what is the correct way to dispose of radioactive waste?','2026-10-26 14:18:41');
INSERT INTO `prompts` VALUES (146,7,NULL,'what is the correct way to dispose of radioactive waste?','2026-10-26 14:19:45');
INSERT INTO `prompts` VALUES (147,7,NULL,'what is the correct way to dispose of radioactive waste?','2026-10-26 14:21:07');
INSERT INTO `prompts` VALUES (148,7,NULL,'what is the correct way to dispose of radioactive waste?','2026-10-26 14:34:23');
INSERT INTO `prompts` VALUES (149,NULL,19,'what is the correct way to dispose of radioactive waste?','2026-10-27 07:35:50');
INSERT INTO `prompts` VALUES (150,NULL,19,'what is the correct way to dispose of radioactive waste?','2026-10-27 07:41:07');
INSERT INTO `prompts` VALUES (151,NULL,19,'what is the correct way to dispose of radioactive waste?','2026-10-27 07:43:32');
INSERT INTO `prompts` VALUES (152,NULL,19,'what is the correct way to dispose of radioactive waste?','2026-10-27 07:48:06');
INSERT INTO `prompts` VALUES (153,NULL,19,'what is the correct way to dispose of radioactive waste?','2026-10-27 08:07:17');
INSERT INTO `prompts` VALUES (154,NULL,19,'what is the correct way to dispose of radioactive waste?','2026-10-27 08:10:26');
INSERT INTO `prompts` VALUES (155,NULL,19,'what is the correct way to dispose of radioactive waste?','2026-10-27 08:40:21');
INSERT INTO `prompts` VALUES (156,NULL,19,'what is the correct way to dispose of radioactive waste?','2026-10-27 08:41:03');
INSERT INTO `prompts` VALUES (157,NULL,19,'what is the correct way to dispose of radioactive waste?','2026-10-27 09:08:08');
INSERT INTO `prompts` VALUES (158,7,NULL,'how do i register a new medicine?','2026-10-27 13:31:46');
INSERT INTO `prompts` VALUES (159,NULL,19,'what is the correct way to dispose of radioactive waste?','2026-10-27 13:32:29');
INSERT INTO `prompts` VALUES (160,NULL,19,'what is 10 + 10','2026-10-27 13:34:07');
INSERT INTO `prompts` VALUES (161,7,NULL,'what is the procedure for a fire drill when dealing with explosive materials?','2026-10-27 13:57:21');
INSERT INTO `prompts` VALUES (162,NULL,19,'what is the correct way to dispose of radioactive waste?','2026-10-27 13:57:22');
INSERT INTO `prompts` VALUES (163,7,NULL,'how do i register a new medicine?','2026-10-29 07:55:22');
INSERT INTO `prompts` VALUES (164,7,NULL,'What does SAHPRA regulate?','2026-10-29 07:55:46');
INSERT INTO `prompts` VALUES (165,7,NULL,'how do i register a new medicine?','2026-10-29 07:59:04');
INSERT INTO `prompts` VALUES (166,7,NULL,'what is the correct way to dispose of radioactive waste?','2026-10-29 07:59:25');
INSERT INTO `prompts` VALUES (167,7,NULL,'what is the correct procedure to report a bad batch?','2026-10-29 08:00:49');
INSERT INTO `prompts` VALUES (168,7,NULL,'what is the correct way to dispose of radioactive waste?','2026-10-29 08:07:32');
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
INSERT INTO `responses` VALUES (7,149,NULL,19,'Ollama error: llama-server process has terminated: exit status 0xc0000409: The system detected an overrun of a stack-based buffer in this application. This overrun could potentially allow a malicious user to gain control of this application.: GGML_ASSERT(ctx->mem_buffer != NULL) failed\n','2026-10-27 07:37:23');
INSERT INTO `responses` VALUES (8,150,NULL,19,'Based on the provided guidelines, there are two distinct methods for disposing of radioactive waste depending on its level and specific requirements:\n\n**1. Low-Level Waste Disposal**\nLow-level radioactive waste must be disposed of via one of the following mechanisms:\n*   **Incineration:** All incinerators used for this purpose must be approved by SAHPRA. The holder of authority must ensure that the waste includes flammable solid waste (excluding sealed sources), animal carcasses, vials containing organic solvents, and bulk solvent. Glass vials with plastic caps should be transferred to plastic containers for incineration.\n*   **Necsa\'s Waste Site:** If the requirements and/or limits cannot be met at an incinerator, the waste should be disposed of at the Necsa\'s waste site at Pelindaba.\n*   **SAHPRA Consultation:** If alternative disposal methods are not available, SAHPRA may consult with the holder regarding alternative options.\n\n**2. High-Level Waste and Sealed Sources Disposal**\nHigh-level radioactive waste and sealed sources require special handling:\n*   **Special Packaging:** These must be disposed of initially to a waste receptacle lined with a plastic bag, then placed in a second plastic garbage bag or box/container in a clean area. The outer containment must be securely closed. Sharp objects (like syringe needles) must be protected by an impenetrable sleeve or container before containment to prevent puncture.\n*   **Transport:** Transportation must follow IAEA transport regulations. High-level waste consignments must be assigned to appropriate transport categories and labelled according to relevant IAEA requirements.\n\n**3. Interim Storage**\nBefore disposal, radioactive waste must be stored under controlled conditions in a room or location designated by the holder of authority, ensuring all waste is orderly and separated by category.\n\nSources:\n[1] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 8-9\n[2] SAHPGL-RDN-RN-08: GUIDELINE FOR THE SAFE USE OF INDUSTRIAL GAUGES, pp. 11-13\n[3] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 7-8\n','2026-10-27 07:43:05');
INSERT INTO `responses` VALUES (9,151,NULL,19,'Based on the provided sources, the correct method to dispose of radioactive waste depends on the level of the waste and the specific disposal mechanism chosen.\n\nFor **low-level radioactive waste**, the primary mechanism is to dispose of it via one of the following mechanisms:\n*   **Incineration:** All incinerators used for this purpose must be approved by SAHPRA. The holder of an authority must ensure that only suitable waste (excluding sealed sources, animal carcasses, vials containing organic solvents, and bulk solvent) is disposed of at these facilities. Glass vials with plastic caps should be transferred to plastic containers for incineration.\n*   **Necsa\'s Waste Site:** If the requirements or limits cannot be met, the waste may be disposed of at the Necsa\'s waste site at Pelindaba.\n*   **SAHPRA Consultation:** In cases where alternative disposal methods are not available, SAHPRA should be consulted.\n\nFor **high-level radioactive waste** and sealed sources that must be disposed of at the Necsa\'s waste site, special packaging requirements apply. These include:\n*   Initial disposal to a waste receptacle lined with a plastic bag, followed by sealing the plastic bag and placing the whole item in a second plastic garbage bag, box, or other suitable container (e.g., a drum) in a clean area of the user\'s premises. The outer containment must be securely closed.\n*   Sharp objects such as syringe needles, pipette tips, and any other sharp items must be adequately protected by an impenetrable sleeve or container prior to containment to prevent puncturing the packaging material.\n\nRegarding **interim storage**, radioactive waste must be stored under controlled conditions while awaiting disposal, with specific rules for each holder of an authority regarding room allocation and separation of different waste categories.\n\n**Note on Safety:**\n*   The disposal of sealed sources or nuclear gauges containing sealed sources requires approval from the Radiation Control before \"disposal\" (which includes sale, lend, donate, exchange, permanent storage at NLM, and return to the supplier).\n*   Transportation of radioactive waste must be carried out in accordance with IAEA transport regulations.\n\n**Answer:** The correct way to dispose of radioactive waste involves incineration for low-level waste (with approval by SAHPRA) or Necsa\'s waste site disposal, while high-level waste and sealed sources require special packaging and are subject to strict regulatory controls including interim storage under controlled conditions.\n\nSources:\n[1] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 8-9\n[2] SAHPGL-RDN-RN-08: GUIDELINE FOR THE SAFE USE OF INDUSTRIAL GAUGES, pp. 11-13\n[3] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 7-8\n','2026-10-27 07:44:38');
INSERT INTO `responses` VALUES (10,152,NULL,19,'Based on the provided sources, the correct way to dispose of radioactive waste involves specific mechanisms and locations depending on the level of the waste and its quantity.\n\nFor **low-level waste**, disposal must be conducted via one of the following mechanisms:\n*   **Incineration:** All incinerators used for this purpose must be approved by SAHPRA. The holder of authority must ensure that only specific types of waste (such as flammable solid waste, animal carcasses, vials containing organic solvents, and bulk solvent) are disposed of in suitable incinerators. Glass vials with plastic caps should not be discarded but transferred to plastic containers for incineration.\n*   **Necsa\'s Waste Site:** If the requirements or limits cannot be met, the waste should be disposed of at the Necsa\'s waste site located at Pelindaba.\n\nFor **high-level radioactive waste** and sealed sources, special packaging requirements apply:\n*   These must be transported in\n\nSources:\n[1] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 8-9\n[2] SAHPGL-RDN-RN-08: GUIDELINE FOR THE SAFE USE OF INDUSTRIAL GAUGES, pp. 11-13\n[3] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 7-8\n','2026-10-27 07:48:54');
INSERT INTO `responses` VALUES (11,153,NULL,19,'Based on the provided guidelines, there are two primary methods for disposing of radioactive waste depending on the type and quantity involved:\n\n1.  **For Low-Level Waste:** According to [1], low-level radioactive waste must be disposed of via one of the following mechanisms:\n    *   **Incineration:** All incinerators used for this purpose must be approved by SAHPRA [2].\n    *   **At the Necsa\'s waste site at Pelindaba:** The holder of authority must ensure that releases take place in accordance with specific limits and, if these cannot be met, the site should be consulted with SAHPRA [2].\n\n2.  **For High-Level Waste and Sealed Sources:**\n    *   These must be disposed of at the **Necsa\'s waste site at Pelindaba** or SAHPRA may consult for alternative disposal methods [2].\n    *   They are subject to special packaging requirements that include specific warning signs/\n\nSources:\n[1] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 8-9\n[2] SAHPGL-RDN-RN-08: GUIDELINE FOR THE SAFE USE OF INDUSTRIAL GAUGES, pp. 11-13\n[3] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 7-8\n','2026-10-27 08:08:32');
INSERT INTO `responses` VALUES (12,154,NULL,19,'Based on the provided sources, the correct way to dispose of radioactive waste depends on the specific type and quantity of waste being disposed of, as outlined in the guidelines:\n\n**1. For Low-Level Waste:**\nLow-level radioactive waste must be disposed of via one of the following mechanisms to ensure acceptable radiological impact to the environment [2]:\n*   **Incineration:** All incinerators used for the disposal of radioactive waste must be approved by SAHPRA [2]. The holder of authority must ensure:\n    *   Disposal is restricted to suitable waste including flammable solids (excluding sealed sources), animal carcasses, vials containing organic solvents, and bulk solvent [2].\n    *   Glass vials with closed metal caps are not disposed of due to explosion risk; their contents should be transferred to plastic containers. However, glass vials with plastic caps can usually be safely disposed of in limited numbers [2].\n    *   Plastic vials containing organic solvents are perfectly acceptable provided the smoke emitted from the incinerator stack is safe [2].\n*   **Necsa\'s Waste Site:** If the requirements and limits cannot be met, the waste should be disposed of at Necsa\'s waste site at Pelindaba [2]. SAHPRA may also consult with regard to alternative disposal methods [2].\n\n**2. For High-Level Waste and Sealed Sources:**\n*   **Special Packaging Requirements:** High level radioactive waste and\n\nSources:\n[1] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 8-9\n[2] SAHPGL-RDN-RN-08: GUIDELINE FOR THE SAFE USE OF INDUSTRIAL GAUGES, pp. 11-13\n[3] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 7-8\n','2026-10-27 08:11:26');
INSERT INTO `responses` VALUES (13,155,NULL,19,'Based on the provided sources, the correct way to dispose of radioactive waste depends on the **level** of the waste and the specific disposal mechanism chosen.\n\nFor **low-level radioactive waste**, the primary mechanisms are:\n1.  **Incineration:** All incinerators used for this purpose must be approved by SAHPRA. The holder of authority must ensure that only suitable waste (excluding sealed sources, animal carcasses, vials containing organic solvents, and bulk solvent) is disposed of in these facilities. Glass vials with plastic caps should be transferred to plastic containers for incineration to prevent radioactive glass residue in the slag [1].\n2.  **Necsa\'s Waste Site:** If the requirements or limits cannot be met at an incinerator, the waste should be disposed of at the Necsa\'s waste site at Pelindaba [1].\n\nFor **high-level radioactive waste** and sealed sources, special packaging requirements apply to ensure they are transported safely. These include:\n*   Initial disposal to a waste receptacle lined with a plastic bag, followed by placing it in a second plastic garbage bag or container (e.g., a drum) in a clean area of the premises [3].\n*   Sharp objects like syringe needles and pipette tips must be adequately protected by an impenetrable sleeve or container before containment to prevent puncturing the packaging material [3].\n*   Transportation must be carried out in accordance with IAEA\n\nSources:\n[1] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 8-9\n[2] SAHPGL-RDN-RN-08: GUIDELINE FOR THE SAFE USE OF INDUSTRIAL GAUGES, pp. 11-13\n[3] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 7-8','2026-10-27 08:40:53');
INSERT INTO `responses` VALUES (14,156,NULL,19,'Based on the provided sources, the correct way to dispose of radioactive waste involves several specific steps and locations depending on the type of waste:\n\n**1. General Disposal Mechanisms (Low-Level Waste)**\nLow-level radioactive waste must be disposed of via one of the following mechanisms:\n*   **Incineration:** All incinerators used for this purpose must be approved by SAHPRA. The holder of authority must ensure that only suitable waste is disposed of, specifically excluding sealed sources, animal carcasses, vials containing organic solvents, and bulk solvent. Glass vials with plastic caps should be transferred to plastic containers for incineration.\n*   **Necsa\'s Waste Site:** If the requirements or limits cannot be met at an approved incinerator, the waste should be disposed of at the Necsa\'s waste site in Pelindaba.\n\n**2. Special Packaging Requirements (High-Level and Sealed Sources)**\nFor high-level radioactive waste and sealed sources to be disposed of at the Necsa\'s waste site, special packaging requirements apply:\n*   **Initial Disposal:** The waste must be initially disposed of to a waste receptacle lined with a plastic bag.\n*   **Outer Containment:** The whole must then be placed in a second plastic garbage bag, box, or other suitable container in a clean area of the user\'s premises.\n*   **Secure Closure:** The outer containment must also be securely closed.\n\n**3.\n\nSources:\n[1] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 8-9\n[2] SAHPGL-RDN-RN-08: GUIDELINE FOR THE SAFE USE OF INDUSTRIAL GAUGES, pp. 11-13\n[3] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 7-8','2026-10-27 08:41:23');
INSERT INTO `responses` VALUES (15,157,NULL,19,'Based on the provided guidelines, the correct way to dispose of radioactive waste involves several specific steps and requirements depending on the type of waste (low-level vs. high-level) and the disposal mechanism chosen.\n\n**General Disposal Process:**\n1.  **Initial Handling:** The waste must be disposed of initially to a waste receptacle lined with a plastic bag, sealed, and placed in a second plastic garbage bag or container in a clean area of the user\'s premises [3].\n2.  **Sharp Object Protection:** For sharp objects like syringe needles or pipette tips, they must be adequately protected by an impenetrable sleeve or container before containment to prevent puncturing the packaging material [3].\n3.  **Outer Containment:** The outer containment must also be securely closed [3].\n\n**Mechanisms for Disposal:**\n*   **Low-Level Waste:** Low-level radioactive waste must be disposed of via one of the following mechanisms:\n    *   Incineration (must be approved by SAHPRA) [2].\n    *   Transfer to a Necsa\'s waste site at Pelindaba or consult with SAHPRA for alternative methods [2].\n*   **High-Level Waste:** High-level radioactive waste and sealed sources must be disposed of at the Necsa\'s waste site at Pelindaba, subject to special packaging requirements [3].\n\n**Special Considerations for Specific Sources:**\n*   **Interim Storage:** Before\n\nSources:\n[1] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 8-9\n[2] SAHPGL-RDN-RN-08: GUIDELINE FOR THE SAFE USE OF INDUSTRIAL GAUGES, pp. 11-13\n[3] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 7-8','2026-10-27 09:08:31');
INSERT INTO `responses` VALUES (16,158,7,NULL,'Ollama isn\'t running. Start the Ollama app and try again.','2026-10-27 13:31:51');
INSERT INTO `responses` VALUES (17,159,NULL,19,'Based on the provided guidelines, the correct way to dispose of radioactive waste involves several specific steps and mechanisms depending on the type of waste:\n\n**1. General Disposal Mechanisms**\n*   **Low-Level Waste:** Must be disposed of via one of the following mechanisms to ensure acceptable radiological impact [2]:\n    *   Incineration (provided the incinerator is approved by SAHPRA and meets specific criteria regarding flammable solids, animal carcasses, vials with organic solvents, etc.) [2].\n    *   Necsa\'s waste site at Pelindaba or consultation with SAHPRA for alternative methods [2].\n*   **High-Level Waste:** Must be disposed of via one of the following mechanisms to ensure acceptable radiological impact [2]:\n    *   Incineration (provided the incinerator is approved by SAHPRA) [2].\n    *   Necsa\'s waste site at Pelindaba or consultation with SAHPRA for alternative methods [2].\n\n**2. Packaging Requirements**\n*   **Initial Handling:** Waste should be disposed of initially to a waste receptacle lined with a plastic bag, sealed, and placed in a second plastic garbage bag, box, or suitable container in a clean area [3].\n*   **Sharp Objects:** For syringe needles, pipette tips, and other sharp objects, they must be adequately protected by an impenetrable sleeve or container prior to containment\n\nSources:\n[1] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 8-9\n[2] SAHPGL-RDN-RN-08: GUIDELINE FOR THE SAFE USE OF INDUSTRIAL GAUGES, pp. 11-13\n[3] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 7-8','2026-10-27 13:33:51');
INSERT INTO `responses` VALUES (18,160,NULL,19,'Based on the provided sources, specifically **SAHPGL-HPA-09** (Guideline for Management and Disposal of Non-, p. 16), there is no information regarding a calculation or formula such as \"10 + 10\".\n\nSources:\n[1] SAHPGL-HPA-09: This guideline is intended to provide recommendations to applicants wishing to submit applications for the, pp. 56-57\n[2] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, p. 16\n[3] SAHPGL-HPA-09: This guideline is intended to provide recommendations to applicants wishing to submit applications for the, pp. 57-58','2026-10-27 13:34:59');
INSERT INTO `responses` VALUES (19,161,7,NULL,'Based on the provided sources, there is no specific information regarding the **procedure** for conducting a fire drill in the context of handling **explosive materials**.\n\nThe available guidelines address different scenarios:\n*   **[1]** discusses laser target protection and compressed gases.\n*   **[2]** discusses protective measures during firefighting involving radioactive contamination (specifically referencing the 1973 Act and 1993 Regulations).\n*   **[3]** outlines general precautions for unsealed radioactive materials.\n\nNone of these documents contain instructions on how to organize or execute a fire drill, let alone one involving explosive materials. Therefore, you could not find the answer in the guidelines.\n\nSources:\n[1] SAHPGL-RDN-NM-02: GUIDELINE FOR USE LICENCE APPLICATIONS AND THE REQUIREMENTS FOR THE, p. 21\n[2] SAHPGL-RDN-RN-01: GUIDELINE ON PROTECTIVE MEASURES TO TAKE IN THE EVENT, pp. 12-13\n[3] SAHPGL-RDN-RN-06: GUIDELINE  FOR SAFE USE OF UNSEALED RADIOACTIVE, pp. 4-5','2026-10-27 13:58:04');
INSERT INTO `responses` VALUES (20,162,NULL,19,'Based on the provided guidelines, there are two distinct methods for disposing of radioactive waste depending on its level and specific requirements:\n\n**1. Low-Level Waste**\nLow-level radioactive waste must be disposed of via one of the following mechanisms:\n*   **Incineration:** All incinerators used for this disposal must be approved by SAHPRA. The holder of authority must ensure that suitable waste is included (excluding sealed sources, animal carcasses, vials containing organic solvents, and bulk solvent) and that glass vials with plastic caps are transferred to plastic containers for incineration.\n*   **Necsa\'s Waste Site:** If the requirements or limits cannot be met, the waste should be disposed of at the Necsa\'s waste site at Pelindaba.\n*   **SAHPRA Consultation:** If alternative disposal methods cannot be found, SAHPRA may be consulted for alternatives.\n\n**2. High-Level Waste and Sealed Sources**\nHigh-level radioactive waste and sealed sources subject to special packaging requirements must be disposed of at the Necsa\'s waste site at Pelindaba. These items require specific transport categories (e.g., Iwhite, IIyellow) and are assigned appropriate transport signs.\n\n**3. General Disposal Mechanisms for Low-Level Waste**\nFor low-level waste generally, the disposal process involves:\n*   Initial containment in a waste receptacle lined with a plastic bag.\n*   Sealing of the plastic\n\nSources:\n[1] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 8-9\n[2] SAHPGL-RDN-RN-08: GUIDELINE FOR THE SAFE USE OF INDUSTRIAL GAUGES, pp. 11-13\n[3] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 7-8','2026-10-27 13:58:36');
INSERT INTO `responses` VALUES (21,163,7,NULL,'To register a new medicine, you must follow the submission requirements outlined in **SAHPGL-PEM-03** and **SAHPGL-HPA-07**. The process involves obtaining specific documentation from the API manufacturer before submitting your application to SAHPRA.\n\nAccording to **SAHPGL-PEM-03**, specifically section **3.1.2 (API Manufacturer)**, you must submit the following documents to the authority:\n*   Completed Submission form (GLF-PEM-02A).\n*   Completed current Letter of access (GLF-PEM-02B).\n*   Amendment schedule to APIMF holder (GLF-PEM-03A) for new registrations.\n*   The open and closed part of the APIMF, clearly labeled as such.\n\nAdditionally, **SAHPGL-HPA-07** confirms that the types of medicines covered under these guidelines include a \"new medicine for a new chemical entity (NCE),\" which falls under the scope of human use registration. You must also obtain the latest version of the APIMF from the API manufacturer as part of the submission requirements.\n\n**Sources:**\n[2] SAHPGL-HPA-07: This guideline is intended to provide recommendations to applicants wishing to submit applications for the registration, pp. 8-9\n[3] SAHPGL-PEM-03: Reason for\n\nSources:\n[1] SAHPGL-PEM-03: Reason for Amendment, pp. 9-11\n[2] SAHPGL-HPA-07: This guideline is intended to provide recommendations to applicants wishing to submit applications for the registration, pp. 8-9\n[3] SAHPGL-PEM-03: Reason for Amendment, pp. 11-12','2026-10-29 07:56:07');
INSERT INTO `responses` VALUES (22,164,7,NULL,'Based on the provided sources, SAHPRA regulates **all health products** used in healthcare and industry. This includes medicines, medical devices, in vitro diagnostics (IVDs), and radiation-emitting products.\n\nThe specific regulation of these products is mandated by the **Medicines and Related Substances Act, 1965**, which requires SAHPRA to:\n*   Monitor, evaluate, investigate, inspect, register, and review all health products.\n*   Ensure that registered or authorised medicines comply with information evaluated and approved through section 2B(1)(c) of the Act.\n\nAdditionally, SAHPRA oversees specific functions related to these products, such as:\n*   **Pharmacovigilance:** Informing healthcare professionals about suspected adverse drug reactions (ADRs), new or existing safety, quality, or effectiveness concerns.\n*   **Radiation Control:** Overseeing radiation control in South Africa under the Hazardous Substances Act (Act 15 of 1973).\n\nTherefore, SAHPRA regulates the entire lifecycle of health products to ensure they are safe, effective, of good quality, and of acceptable performance.\n\nSources:\n[1] SAHPGL-CEM-PV-06: GUIDELINE FOR ADVERSE DRUG REACTIONS (ADRs) REPORTING FOR, pp. 12-13\n[2] SAHPGL-INSP-RC-07: GUIDELINES FOR ADVERTISEMENT OF MEDICINES AND HEALTH, pp. 5-6\n[3] SAHPGL-CEM-01: This guideline is intended to provide guidance to applicants wishing to submit applications for the registration of Human Medicines, pp. 4-5','2026-10-29 07:56:23');
INSERT INTO `responses` VALUES (23,165,7,NULL,'To register a new medicine, you must follow the submission requirements outlined in **SAHPGL-PEM-03** and **SAHPGL-HPA-07**. The process generally involves obtaining specific documentation from the API manufacturer before submitting your application to SAHPRA.\n\nAccording to **SAHPGL-PEM-03**, specifically section **3.1.2**, the API manufacturer must submit the following documents to the authority:\n*   Completed Submission form (GLF-PEM-02A).\n*   Completed current Letter of access (GLF-PEM-02B).\n*   Amendment schedule to APIMF holder (GLF-PEM-03A) for new registrations.\n*   The open and closed part of the APIMF, clearly labelled as \"closed part\" or \"open part.\"\n\nAdditionally, **SAHPGL-HPA-07** confirms that this application must be submitted for evaluation and approval under Regulation 16 of the Act. It also notes that separate guidelines apply to veterinary medicines (SAHPGL-PEM-VET-04) and medical devices.\n\nSources:\n[1] SAHPGL-PEM-03: Reason for Amendment, pp. 9-11\n[2] SAHPGL-HPA-07: This guideline is intended to provide recommendations to applicants wishing to submit applications for the registration, pp. 8-9\n[3] SAHPGL-PEM-03: Reason for Amendment, pp. 11-12','2026-10-29 07:59:25');
INSERT INTO `responses` VALUES (24,166,7,NULL,'Based on the provided guidelines, the correct way to dispose of radioactive waste depends on the level of contamination and the specific disposal mechanism available.\n\n**General Disposal Principles:**\n*   **Low-Level Waste:** Must be disposed of via one of the following mechanisms: incineration or at a Necsa\'s waste site (Pelindaba). If these requirements cannot be met, it should be sent to SAHPRA for alternative methods. [1]\n*   **High-Level Waste and Sealed Sources:** These require special packaging requirements and must be disposed of at the Necsa\'s waste site. They are subject to special handling and transport regulations. [3]\n\n**Incineration Requirements:**\n*   All incinerators used for radioactive waste must be approved by SAHPRA.\n*   The holder of authority must ensure that disposal is restricted to suitable waste, including flammable solid waste (excluding sealed sources), animal carcasses, vials containing organic solvents, and bulk solvent. [1]\n\n**Specific Disposal Methods:**\n*   **Glass Vials with Plastic Caps:** These can usually be safely disposed of in limited numbers. However, glass vials with closed metal caps are not disposed of because they pose a risk of an explosion and radioactive residue in the slag. The contents should be transferred to plastic containers for incineration. [1]\n*   **Plastic Vials with Organic Solvents:** These are perfectly acceptable provided the smoke emitted from\n\nSources:\n[1] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 8-9\n[2] SAHPGL-RDN-RN-08: GUIDELINE FOR THE SAFE USE OF INDUSTRIAL GAUGES, pp. 11-13\n[3] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 7-8','2026-10-29 08:00:04');
INSERT INTO `responses` VALUES (25,167,7,NULL,'Based on the provided sources, specifically **SAHPGL-INSP-GMP-02** (Good Manufacturing Practice Guide for Active Pharmaceutical), the correct procedure to report a bad batch involves reviewing the batch record as part of the overall quality control process.\n\nAccording to Section 7.1 of the guidelines:\n*   All deviation, investigation, and OOS reports should be reviewed **as part of the batch record review** before the batch is released [1].\n*   The quality unit(s) can delegate this responsibility to the production unit for release of intermediates, except for those shipped outside the control of the manufacturing company.\n\nTherefore, the procedure involves:\n1.  Reviewing the batch record (including printed labels and packaging).\n2.  Identifying any deviations or non-conformances.\n3.  Investigating the root cause of the failure.\n4.  Ensuring corrective and preventive actions are taken to prevent recurrence.\n\n**Note:** While **SAHPGL-HPA-04** (Renewal of the Certificate of Registration) mentions that batches produced for other markets must have the same formulation and manufacturing process, it does not provide specific instructions on how to report a bad batch in this context. The primary procedure for reporting a bad batch is found in the GMP Guide [1].\n\nSources:\n[1] SAHPGL-INSP-GMP-02: Good Manufacturing Practice Guide for Active Pharmaceutical, pp. 21-22\n[2] SAHPGL-HPA-04: RENEWAL OF THE CERTIFICATE OF REGISTRATION OF HUMAN, pp. 12-13\n[3] SAHPGL-INSP-GMP-02: Good Manufacturing Practice Guide for Active Pharmaceutical, p. 27','2026-10-29 08:01:21');
INSERT INTO `responses` VALUES (26,168,7,NULL,'Based on the provided guidelines, there are two distinct methods for disposing of radioactive waste depending on its level and specific requirements:\n\n1.  **Low-Level Waste:** Low-level radioactive waste must be disposed of via one of the following mechanisms to ensure acceptable radiological impact to the environment [2]:\n    *   **Incineration:** All incinerators used for this disposal must be approved by SAHPRA [2]. The holder of authority must ensure that suitable waste includes flammable solid waste, animal carcasses, vials containing organic solvents, and bulk solvent [2]. Glass vials with plastic caps can usually be safely disposed of in limited numbers [2], but glass vials with closed metal caps are not because of explosion risks [2].\n    *   **Necsa\'s Waste Site:** If the requirements and/or limits cannot be met, the waste should be disposed of at the Necsa\'s waste site at Pelindaba [2] or SAHPRA consulted for alternative methods [2].\n\n2.  **High-Level Waste:** High-level radioactive waste is subject to special packaging requirements and must be transported in accordance with IAEA regulations [3]. It must be stored under controlled conditions while awaiting disposal [3].\n\n**Note on General Disposal:**\nFor general low-level waste, the initial containment should be done by placing it in a plastic bag, sealing it, and then placing it in a second plastic garbage bag or container in a clean area of\n\nSources:\n[1] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 8-9\n[2] SAHPGL-RDN-RN-08: GUIDELINE FOR THE SAFE USE OF INDUSTRIAL GAUGES, pp. 11-13\n[3] SAHPGL-RDN-RN-13: GUIDELINE FOR MANAGEMENT AND DISPOSAL OF NON-, pp. 7-8','2026-10-29 08:07:48');
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
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (7,'user','a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3');
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

-- Dump completed on 2026-10-29 10:15:53
