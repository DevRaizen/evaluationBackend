-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: tval_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `AdminID` int(11) NOT NULL AUTO_INCREMENT,
  `AccID` int(11) DEFAULT NULL,
  `Fname` varchar(50) DEFAULT NULL,
  `Mname` varchar(50) DEFAULT NULL,
  `Lname` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`AdminID`),
  KEY `AccID` (`AccID`),
  CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`AccID`) REFERENCES `user_account` (`AccID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,3,'Raizen','S','Bulos'),(2,44,'Melva','C','Dela Cruz');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `catID` int(11) NOT NULL AUTO_INCREMENT,
  `categoryName` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`catID`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES (1,'Respect'),(2,'Communication'),(3,'Preparedness'),(4,'Time Management'),(5,'Participation'),(6,'Feedback'),(7,'Comment');
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrollment`
--

DROP TABLE IF EXISTS `enrollment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollment` (
  `EnrollmentID` int(11) NOT NULL AUTO_INCREMENT,
  `StudID` varchar(20) DEFAULT NULL,
  `YearSecID` int(11) DEFAULT NULL,
  `SchoolYearID` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`EnrollmentID`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollment`
--

LOCK TABLES `enrollment` WRITE;
/*!40000 ALTER TABLE `enrollment` DISABLE KEYS */;
INSERT INTO `enrollment` VALUES (4,'s2025-20025',1,1),(5,'s2025-2002523',8,1),(6,'s2025-200252',7,1),(7,'s2025-20025232',5,1),(8,'s2025-200252323',1,1),(10,'s2025-20025sd',3,1),(11,'asd',2,1),(12,'s2025-2002522323',4,1),(15,'s2025-2002523233',3,1),(25,'s2025-20025223',4,1),(26,'s2025-2002512312',1,1),(27,'s2025-20025223323',4,1);
/*!40000 ALTER TABLE `enrollment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation`
--

DROP TABLE IF EXISTS `evaluation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation` (
  `EvalID` int(11) NOT NULL AUTO_INCREMENT,
  `ESetID` int(11) DEFAULT NULL,
  `TeacherID` varchar(20) DEFAULT NULL,
  `StudID` varchar(20) DEFAULT NULL,
  `EvalDate` date DEFAULT NULL,
  `SubjectID` int(11) DEFAULT NULL,
  `SchoolYearID` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`EvalID`),
  KEY `ESetID` (`ESetID`),
  KEY `TeacherID` (`TeacherID`),
  KEY `StudID` (`StudID`),
  KEY `SubjectID` (`SubjectID`),
  CONSTRAINT `evaluation_ibfk_1` FOREIGN KEY (`ESetID`) REFERENCES `evaluation_settings` (`ESetID`),
  CONSTRAINT `evaluation_ibfk_3` FOREIGN KEY (`TeacherID`) REFERENCES `teacher` (`TeacherID`),
  CONSTRAINT `evaluation_ibfk_4` FOREIGN KEY (`StudID`) REFERENCES `student` (`StudID`),
  CONSTRAINT `evaluation_ibfk_5` FOREIGN KEY (`SubjectID`) REFERENCES `subject` (`SubjectID`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation`
--

LOCK TABLES `evaluation` WRITE;
/*!40000 ALTER TABLE `evaluation` DISABLE KEYS */;
INSERT INTO `evaluation` VALUES (35,73,'T008','s2025-200252323','2025-09-21',3,1),(36,73,'T008','s2025-200252323','2025-09-21',1,1),(38,73,'T013','s2025-200252323','2025-09-24',7,1),(39,73,'T008','s2025-20025','2025-09-24',1,1),(44,73,'T008','s2025-20025','2025-10-02',3,1),(46,73,'T013','s2025-20025','2025-10-14',7,1),(47,73,'T013','s2025-200252','2025-10-16',25,1),(55,73,'T011','asd','2025-10-17',5,1),(56,73,'T011','s2025-20025','2025-10-17',5,1),(57,73,'T011','s2025-20025','2025-10-17',6,1),(58,73,'T011','s2025-20025','2025-10-17',2,1),(59,73,'T008','s2025-200252','2025-10-17',25,1),(60,73,'T008','s2025-200252','2025-10-17',28,1),(61,73,'T008','s2025-200252','2025-10-17',29,1),(62,73,'T008','s2025-200252','2025-10-17',30,1),(63,73,'T008','s2025-200252','2025-10-17',31,1),(64,73,'T008','s2025-200252','2025-10-17',32,1),(65,73,'T008','s2025-200252','2025-10-17',27,1),(66,73,'T011','s2025-20025','2025-10-21',4,1),(67,73,'T008','s2025-20025223323','2025-10-24',9,1);
/*!40000 ALTER TABLE `evaluation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_settings`
--

DROP TABLE IF EXISTS `evaluation_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_settings` (
  `ESetID` int(11) NOT NULL AUTO_INCREMENT,
  `AdminID` int(11) DEFAULT NULL,
  `QID` int(11) NOT NULL,
  `Title` varchar(100) DEFAULT NULL,
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `Status` varchar(20) DEFAULT NULL,
  `TargetGrade` varchar(100) DEFAULT NULL,
  `TimeDuration` int(11) DEFAULT 20,
  `SchoolYearID` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ESetID`),
  KEY `AdminID` (`AdminID`),
  CONSTRAINT `evaluation_settings_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_settings`
--

LOCK TABLES `evaluation_settings` WRITE;
/*!40000 ALTER TABLE `evaluation_settings` DISABLE KEYS */;
INSERT INTO `evaluation_settings` VALUES (73,1,1,'Mid Year Evaluation','2025-10-22','2025-10-30','Active','Grade 9, Grade 8, Grade 7, Grade 10',20,1);
/*!40000 ALTER TABLE `evaluation_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback` (
  `FID` int(11) NOT NULL AUTO_INCREMENT,
  `EvalID` int(11) NOT NULL,
  `StudID` varchar(20) DEFAULT NULL,
  `Comment` text DEFAULT NULL,
  `Timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (`FID`),
  KEY `StudID` (`StudID`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`StudID`) REFERENCES `student` (`StudID`)
) ENGINE=InnoDB AUTO_INCREMENT=310 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
INSERT INTO `feedback` VALUES (145,35,'s2025-200252323','hello','2025-09-21 12:20:37'),(146,35,'s2025-200252323','opkay lang','2025-09-21 12:20:37'),(147,35,'s2025-200252323','Rasd','2025-09-21 12:20:37'),(148,35,'s2025-200252323','Rasd','2025-09-21 12:20:37'),(149,35,'s2025-200252323','Rasda','2025-09-21 12:20:37'),(150,36,'s2025-200252323','Magaling sya','2025-09-21 12:37:38'),(151,36,'s2025-200252323','galingan pa lalo','2025-09-21 12:37:38'),(152,36,'s2025-200252323','magaling','2025-09-21 12:37:38'),(153,36,'s2025-200252323','maayos','2025-09-21 12:37:38'),(154,36,'s2025-200252323','wala','2025-09-21 12:37:38'),(160,38,'s2025-200252323','Hes good','2025-09-24 16:48:27'),(161,38,'s2025-200252323','No he already better','2025-09-24 16:48:27'),(162,38,'s2025-200252323','goods','2025-09-24 16:48:27'),(163,38,'s2025-200252323','bad ','2025-09-24 16:48:27'),(164,38,'s2025-200252323','goods','2025-09-24 16:48:27'),(165,39,'s2025-20025','goods','2025-09-24 16:51:31'),(166,39,'s2025-20025','goods','2025-09-24 16:51:31'),(167,39,'s2025-20025','good','2025-09-24 16:51:31'),(168,39,'s2025-20025','handsome','2025-09-24 16:51:31'),(169,39,'s2025-20025','good','2025-09-24 16:51:31'),(190,44,'s2025-20025','asd','2025-10-02 23:19:36'),(191,44,'s2025-20025','asd','2025-10-02 23:19:36'),(192,44,'s2025-20025','asd','2025-10-02 23:19:36'),(193,44,'s2025-20025','asd','2025-10-02 23:19:36'),(194,44,'s2025-20025','asd','2025-10-02 23:19:36'),(200,46,'s2025-20025','tanga bobo','2025-10-14 14:37:37'),(201,46,'s2025-20025','inutil','2025-10-14 14:37:37'),(202,46,'s2025-20025','jh','2025-10-14 14:37:37'),(203,46,'s2025-20025','hj','2025-10-14 14:37:37'),(204,46,'s2025-20025','hj','2025-10-14 14:37:37'),(205,47,'s2025-200252','good','2025-10-16 14:22:02'),(206,47,'s2025-200252','good','2025-10-16 14:22:02'),(207,47,'s2025-200252','good','2025-10-16 14:22:02'),(208,47,'s2025-200252','good','2025-10-16 14:22:02'),(209,47,'s2025-200252','good','2025-10-16 14:22:02'),(245,55,'asd','asd','2025-10-17 23:33:10'),(246,55,'asd','asd','2025-10-17 23:33:10'),(247,55,'asd','asd','2025-10-17 23:33:10'),(248,55,'asd','asd','2025-10-17 23:33:10'),(249,55,'asd','asd','2025-10-17 23:33:10'),(250,56,'s2025-20025','asd','2025-10-17 23:34:04'),(251,56,'s2025-20025','asd','2025-10-17 23:34:04'),(252,56,'s2025-20025','asd','2025-10-17 23:34:04'),(253,56,'s2025-20025','asd','2025-10-17 23:34:04'),(254,56,'s2025-20025','asd','2025-10-17 23:34:04'),(255,57,'s2025-20025','asd','2025-10-17 23:36:25'),(256,57,'s2025-20025','asd','2025-10-17 23:36:25'),(257,57,'s2025-20025','asd','2025-10-17 23:36:25'),(258,57,'s2025-20025','asd','2025-10-17 23:36:25'),(259,57,'s2025-20025','asd','2025-10-17 23:36:25'),(260,58,'s2025-20025','Tanga','2025-10-17 23:37:33'),(261,58,'s2025-20025','BOBO','2025-10-17 23:37:33'),(262,58,'s2025-20025','good ang galing e','2025-10-17 23:37:33'),(263,58,'s2025-20025','good ang galing e','2025-10-17 23:37:33'),(264,58,'s2025-20025','good ang galing e','2025-10-17 23:37:33'),(265,59,'s2025-200252','asd','2025-10-17 23:55:43'),(266,59,'s2025-200252','asd','2025-10-17 23:55:43'),(267,59,'s2025-200252','asd','2025-10-17 23:55:43'),(268,59,'s2025-200252','asd','2025-10-17 23:55:43'),(269,59,'s2025-200252','asd','2025-10-17 23:55:43'),(270,60,'s2025-200252','asd','2025-10-17 23:56:03'),(271,60,'s2025-200252','asd','2025-10-17 23:56:03'),(272,60,'s2025-200252','asd','2025-10-17 23:56:03'),(273,60,'s2025-200252','asda','2025-10-17 23:56:03'),(274,60,'s2025-200252','dasd','2025-10-17 23:56:03'),(275,61,'s2025-200252','asd','2025-10-17 23:56:25'),(276,61,'s2025-200252','asd','2025-10-17 23:56:25'),(277,61,'s2025-200252','asd','2025-10-17 23:56:25'),(278,61,'s2025-200252','asd','2025-10-17 23:56:25'),(279,61,'s2025-200252','asd','2025-10-17 23:56:25'),(280,62,'s2025-200252','asd','2025-10-17 23:56:44'),(281,62,'s2025-200252','asd','2025-10-17 23:56:44'),(282,62,'s2025-200252','asd','2025-10-17 23:56:44'),(283,62,'s2025-200252','asd','2025-10-17 23:56:44'),(284,62,'s2025-200252','asd','2025-10-17 23:56:44'),(285,63,'s2025-200252','asd','2025-10-17 23:57:02'),(286,63,'s2025-200252','dsa','2025-10-17 23:57:02'),(287,63,'s2025-200252','asd','2025-10-17 23:57:02'),(288,63,'s2025-200252','asd','2025-10-17 23:57:02'),(289,63,'s2025-200252','asd','2025-10-17 23:57:02'),(290,64,'s2025-200252','asd','2025-10-17 23:57:20'),(291,64,'s2025-200252','asd','2025-10-17 23:57:20'),(292,64,'s2025-200252','asd','2025-10-17 23:57:20'),(293,64,'s2025-200252','asda','2025-10-17 23:57:20'),(294,64,'s2025-200252','asd','2025-10-17 23:57:20'),(295,65,'s2025-200252','sad','2025-10-17 23:57:38'),(296,65,'s2025-200252','sad','2025-10-17 23:57:38'),(297,65,'s2025-200252','asda','2025-10-17 23:57:38'),(298,65,'s2025-200252','asdasd','2025-10-17 23:57:38'),(299,65,'s2025-200252','aasdasd','2025-10-17 23:57:38'),(300,66,'s2025-20025','asd','2025-10-21 00:14:25'),(301,66,'s2025-20025','asd','2025-10-21 00:14:25'),(302,66,'s2025-20025','goods','2025-10-21 00:14:25'),(303,66,'s2025-20025','goiods','2025-10-21 00:14:25'),(304,66,'s2025-20025','goods','2025-10-21 00:14:25'),(305,67,'s2025-20025223323','fddgdgfsgfffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff','2025-10-24 15:09:29'),(306,67,'s2025-20025223323','dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd1\'','2025-10-24 15:09:29'),(307,67,'s2025-20025223323','asd','2025-10-24 15:09:29'),(308,67,'s2025-20025223323','asd','2025-10-24 15:09:29'),(309,67,'s2025-20025223323','asd','2025-10-24 15:09:29');
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs` (
  `LogID` int(11) NOT NULL AUTO_INCREMENT,
  `AdminID` int(11) DEFAULT NULL,
  `TimeStamp` datetime DEFAULT NULL,
  PRIMARY KEY (`LogID`),
  KEY `AdminID` (`AdminID`),
  CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` VALUES (1,1,'2025-10-22 13:55:31'),(2,1,'2025-10-22 15:15:48'),(3,1,'2025-10-22 15:27:09'),(4,1,'2025-10-22 15:27:51'),(5,1,'2025-10-22 15:32:39'),(6,1,'2025-10-22 15:48:43'),(7,1,'2025-10-22 16:09:37'),(8,1,'2025-10-22 16:13:51'),(9,1,'2025-10-22 16:19:49'),(10,1,'2025-10-22 16:23:15'),(11,1,'2025-10-22 16:26:49'),(12,1,'2025-10-22 16:28:29'),(13,1,'2025-10-22 16:37:28'),(14,1,'2025-10-22 16:39:28'),(15,1,'2025-10-22 16:41:12'),(16,1,'2025-10-22 16:49:12'),(17,1,'2025-10-22 22:08:54'),(18,1,'2025-10-22 22:11:21'),(19,1,'2025-10-23 14:07:58'),(20,1,'2025-10-23 15:05:28'),(21,1,'2025-10-23 15:12:43'),(22,1,'2025-10-23 15:19:11'),(23,1,'2025-10-23 17:34:03'),(24,1,'2025-10-23 20:20:01'),(25,1,'2025-10-23 20:21:41'),(26,1,'2025-10-23 20:38:07'),(27,1,'2025-10-23 21:17:43'),(28,1,'2025-10-23 21:20:58'),(29,1,'2025-10-23 21:22:25'),(30,1,'2025-10-23 21:26:24'),(31,1,'2025-10-23 21:40:10'),(32,1,'2025-10-23 21:43:20'),(33,1,'2025-10-23 21:51:34'),(34,1,'2025-10-23 21:53:04'),(35,1,'2025-10-23 21:55:11'),(36,1,'2025-10-23 22:03:49'),(37,1,'2025-10-23 23:09:06'),(38,1,'2025-10-24 11:59:44'),(39,1,'2025-10-24 13:00:07'),(40,1,'2025-10-24 13:57:29'),(41,1,'2025-10-24 14:31:17'),(42,1,'2025-10-24 14:57:05'),(43,1,'2025-10-24 15:16:35'),(44,1,'2025-10-24 15:52:10'),(45,1,'2025-10-24 15:54:17'),(46,1,'2025-10-24 15:54:17'),(47,1,'2025-10-24 15:59:41'),(48,1,'2025-10-24 22:54:29'),(49,1,'2025-10-24 22:54:38'),(50,1,'2025-10-24 22:55:43');
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `principal`
--

DROP TABLE IF EXISTS `principal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `principal` (
  `PrincipalID` varchar(20) NOT NULL,
  `AccID` int(11) DEFAULT NULL,
  `Fname` varchar(50) DEFAULT NULL,
  `Mname` varchar(50) DEFAULT NULL,
  `Lname` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`PrincipalID`),
  KEY `AccID` (`AccID`),
  CONSTRAINT `principal_ibfk_1` FOREIGN KEY (`AccID`) REFERENCES `user_account` (`AccID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `principal`
--

LOCK TABLES `principal` WRITE;
/*!40000 ALTER TABLE `principal` DISABLE KEYS */;
INSERT INTO `principal` VALUES ('P001',45,'Melva','C','Dela Cruz');
/*!40000 ALTER TABLE `principal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question`
--

DROP TABLE IF EXISTS `question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question` (
  `QuesId` int(11) NOT NULL AUTO_INCREMENT,
  `questionText` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `Status` varchar(20) NOT NULL DEFAULT 'Active',
  `catID` int(11) DEFAULT NULL,
  PRIMARY KEY (`QuesId`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question`
--

LOCK TABLES `question` WRITE;
/*!40000 ALTER TABLE `question` DISABLE KEYS */;
INSERT INTO `question` VALUES (1,'The teacher treats all students fairly and equally.','likert','Active',1),(2,'The teacher respects students’ opinions and listens attentively.','likert','Active',2),(3,'The teacher explains lessons clearly and understandably.','likert','Active',2),(4,'The teacher is well-prepared for class activities and discussions.','likert','Active',3),(5,'The teacher uses class time effectively and efficiently.','likert','Active',4),(6,'The teacher encourages students to participate and ask questions.','likert','Active',5),(7,'The teacher provides helpful feedback on student work.','likert','Active',6),(8,'What do you like most about this teacher’s teaching style?','comment','Active',7),(9,'What suggestions do you have for improving the teacher’s methods?','comment','Active',7),(10,'Any other comments or feedback you’d like to share?','comment','Active',7),(21,'The teacher respects students’ opinions and listens attentively.','likert','Active',1),(23,'asd','likert','Active',2),(24,'asdd','comment','Active',5),(25,'asdasd','likert','Active',2),(26,'asds','likert','Active',1),(27,'asdsadaa','likert','Active',1),(28,'shawnMichael','likert','Active',1),(30,'qwe','likert','Active',1),(31,'awty','likert','Active',5),(32,'asddfqwe','likert','Active',2),(33,'sdfsf','likert','Active',2),(34,'puansdn','likert','Active',4),(35,'wantotree','likert','Active',5),(36,'The teacher uses language that is appropriate and easy for students to follow.','likert','Active',2),(37,'The teacher encourages students to ask questions and express their ideas.','likert','Active',2),(38,'The teacher provides constructive feedback that helps improve student learning.','likert','Active',2),(39,'The teacher communicates course objectives and expectations effectively.','likert','Active',2),(40,'The teacher maintains eye contact and uses body language that supports understanding.','likert','Active',2),(41,'The teacher clarifies misunderstandings when students are confused.','likert','Active',2),(42,'What do you appreciate most about your teacher’s way of communicating?','comment','Active',2),(43,'How can your teacher further improve communication in class?','comment','Active',2),(44,'Does the teacher provide all necessary resources for learning?','likert','Active',3),(46,'sad','likert','Active',2),(47,'Rasd','likert','Active',4),(48,'iloveyouu','likert','Active',1),(49,'The teacher respect the students opinions','likert','Active',1);
/*!40000 ALTER TABLE `question` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `questionnaire`
--

DROP TABLE IF EXISTS `questionnaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `questionnaire` (
  `QID` int(11) NOT NULL,
  `QuesID` int(11) NOT NULL,
  PRIMARY KEY (`QID`,`QuesID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questionnaire`
--

LOCK TABLES `questionnaire` WRITE;
/*!40000 ALTER TABLE `questionnaire` DISABLE KEYS */;
INSERT INTO `questionnaire` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,36),(1,37),(1,38),(1,39),(1,40),(1,41),(1,42),(1,43),(1,44),(1,49),(2,3),(2,21),(2,23),(2,26),(2,28),(2,31),(2,46),(3,23),(3,24),(3,25),(3,27),(3,35),(3,47),(4,23),(4,30),(4,32),(4,33),(4,34),(4,48),(5,23);
/*!40000 ALTER TABLE `questionnaire` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `result`
--

DROP TABLE IF EXISTS `result`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `result` (
  `ResultID` int(11) NOT NULL AUTO_INCREMENT,
  `EvalID` int(11) NOT NULL,
  `QuesID` int(11) NOT NULL,
  `catID` int(11) DEFAULT NULL,
  `Score` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`ResultID`)
) ENGINE=InnoDB AUTO_INCREMENT=550 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `result`
--

LOCK TABLES `result` WRITE;
/*!40000 ALTER TABLE `result` DISABLE KEYS */;
INSERT INTO `result` VALUES (87,35,1,1,5.00),(88,35,2,2,5.00),(89,35,3,2,5.00),(90,35,36,2,5.00),(91,35,37,2,5.00),(92,35,38,2,5.00),(93,35,39,2,5.00),(94,35,40,2,5.00),(95,35,41,2,5.00),(96,35,4,3,5.00),(97,35,44,3,5.00),(98,35,5,4,5.00),(99,35,6,5,5.00),(100,35,7,6,5.00),(101,36,1,1,4.00),(102,36,2,2,5.00),(103,36,3,2,5.00),(104,36,36,2,4.00),(105,36,37,2,4.00),(106,36,38,2,4.00),(107,36,39,2,4.00),(108,36,40,2,5.00),(109,36,41,2,5.00),(110,36,4,3,4.00),(111,36,44,3,4.00),(112,36,5,4,4.00),(113,36,6,5,4.00),(114,36,7,6,5.00),(129,38,1,1,4.00),(130,38,2,2,5.00),(131,38,3,2,5.00),(132,38,36,2,5.00),(133,38,37,2,5.00),(134,38,38,2,5.00),(135,38,39,2,5.00),(136,38,40,2,5.00),(137,38,41,2,5.00),(138,38,4,3,3.00),(139,38,44,3,3.00),(140,38,5,4,4.00),(141,38,6,5,2.00),(142,38,7,6,5.00),(143,39,1,1,3.00),(144,39,2,2,5.00),(145,39,3,2,5.00),(146,39,36,2,5.00),(147,39,37,2,5.00),(148,39,38,2,5.00),(149,39,39,2,5.00),(150,39,40,2,5.00),(151,39,41,2,5.00),(152,39,4,3,3.00),(153,39,44,3,3.00),(154,39,5,4,5.00),(155,39,6,5,3.00),(156,39,7,6,5.00),(213,44,1,1,2.00),(214,44,2,2,1.00),(215,44,3,2,1.00),(216,44,36,2,1.00),(217,44,37,2,1.00),(218,44,38,2,1.00),(219,44,39,2,1.00),(220,44,40,2,1.00),(221,44,41,2,1.00),(222,44,4,3,2.00),(223,44,44,3,2.00),(224,44,5,4,2.00),(225,44,6,5,2.00),(226,44,7,6,2.00),(241,46,1,1,1.00),(242,46,2,2,1.00),(243,46,3,2,1.00),(244,46,36,2,1.00),(245,46,37,2,1.00),(246,46,38,2,1.00),(247,46,39,2,1.00),(248,46,40,2,1.00),(249,46,41,2,1.00),(250,46,4,3,2.00),(251,46,44,3,2.00),(252,46,5,4,1.00),(253,46,6,5,5.00),(254,46,7,6,5.00),(255,47,1,1,3.00),(256,47,2,2,4.00),(257,47,3,2,4.00),(258,47,36,2,4.00),(259,47,37,2,4.00),(260,47,38,2,4.00),(261,47,39,2,4.00),(262,47,40,2,4.00),(263,47,41,2,4.00),(264,47,4,3,4.00),(265,47,44,3,4.00),(266,47,5,4,3.00),(267,47,6,5,4.00),(268,47,7,6,4.00),(367,55,1,1,5.00),(368,55,2,2,5.00),(369,55,3,2,5.00),(370,55,36,2,5.00),(371,55,37,2,5.00),(372,55,38,2,5.00),(373,55,39,2,5.00),(374,55,40,2,5.00),(375,55,41,2,5.00),(376,55,4,3,5.00),(377,55,44,3,5.00),(378,55,5,4,5.00),(379,55,6,5,5.00),(380,55,7,6,5.00),(381,56,1,1,4.00),(382,56,2,2,4.00),(383,56,3,2,4.00),(384,56,36,2,4.00),(385,56,37,2,4.00),(386,56,38,2,4.00),(387,56,39,2,4.00),(388,56,40,2,4.00),(389,56,41,2,4.00),(390,56,4,3,4.00),(391,56,44,3,4.00),(392,56,5,4,4.00),(393,56,6,5,4.00),(394,56,7,6,4.00),(395,57,1,1,5.00),(396,57,2,2,5.00),(397,57,3,2,4.00),(398,57,36,2,5.00),(399,57,37,2,4.00),(400,57,38,2,5.00),(401,57,39,2,4.00),(402,57,40,2,5.00),(403,57,41,2,4.00),(404,57,4,3,5.00),(405,57,44,3,4.00),(406,57,5,4,5.00),(407,57,6,5,5.00),(408,57,7,6,5.00),(409,58,1,1,3.00),(410,58,2,2,3.00),(411,58,3,2,3.00),(412,58,36,2,4.00),(413,58,37,2,4.00),(414,58,38,2,3.00),(415,58,39,2,3.00),(416,58,40,2,2.00),(417,58,41,2,1.00),(418,58,4,3,3.00),(419,58,44,3,3.00),(420,58,5,4,5.00),(421,58,6,5,3.00),(422,58,7,6,1.00),(423,59,1,1,3.00),(424,59,2,2,3.00),(425,59,3,2,3.00),(426,59,36,2,4.00),(427,59,37,2,4.00),(428,59,38,2,3.00),(429,59,39,2,3.00),(430,59,40,2,4.00),(431,59,41,2,5.00),(432,59,4,3,3.00),(433,59,44,3,4.00),(434,59,5,4,3.00),(435,59,6,5,3.00),(436,59,7,6,4.00),(437,60,1,1,2.00),(438,60,2,2,5.00),(439,60,3,2,4.00),(440,60,36,2,3.00),(441,60,37,2,2.00),(442,60,38,2,1.00),(443,60,39,2,1.00),(444,60,40,2,2.00),(445,60,41,2,3.00),(446,60,4,3,1.00),(447,60,44,3,1.00),(448,60,5,4,4.00),(449,60,6,5,1.00),(450,60,7,6,5.00),(451,61,1,1,2.00),(452,61,2,2,5.00),(453,61,3,2,4.00),(454,61,36,2,3.00),(455,61,37,2,2.00),(456,61,38,2,1.00),(457,61,39,2,2.00),(458,61,40,2,3.00),(459,61,41,2,4.00),(460,61,4,3,2.00),(461,61,44,3,3.00),(462,61,5,4,2.00),(463,61,6,5,2.00),(464,61,7,6,3.00),(465,62,1,1,2.00),(466,62,2,2,5.00),(467,62,3,2,4.00),(468,62,36,2,3.00),(469,62,37,2,2.00),(470,62,38,2,1.00),(471,62,39,2,2.00),(472,62,40,2,3.00),(473,62,41,2,4.00),(474,62,4,3,2.00),(475,62,44,3,3.00),(476,62,5,4,2.00),(477,62,6,5,2.00),(478,62,7,6,2.00),(479,63,1,1,3.00),(480,63,2,2,3.00),(481,63,3,2,3.00),(482,63,36,2,3.00),(483,63,37,2,3.00),(484,63,38,2,3.00),(485,63,39,2,3.00),(486,63,40,2,3.00),(487,63,41,2,3.00),(488,63,4,3,3.00),(489,63,44,3,4.00),(490,63,5,4,5.00),(491,63,6,5,3.00),(492,63,7,6,3.00),(493,64,1,1,2.00),(494,64,2,2,5.00),(495,64,3,2,4.00),(496,64,36,2,3.00),(497,64,37,2,2.00),(498,64,38,2,1.00),(499,64,39,2,2.00),(500,64,40,2,3.00),(501,64,41,2,4.00),(502,64,4,3,2.00),(503,64,44,3,3.00),(504,64,5,4,2.00),(505,64,6,5,2.00),(506,64,7,6,2.00),(507,65,1,1,1.00),(508,65,2,2,5.00),(509,65,3,2,4.00),(510,65,36,2,4.00),(511,65,37,2,4.00),(512,65,38,2,4.00),(513,65,39,2,4.00),(514,65,40,2,4.00),(515,65,41,2,4.00),(516,65,4,3,1.00),(517,65,44,3,2.00),(518,65,5,4,3.00),(519,65,6,5,5.00),(520,65,7,6,5.00),(521,66,1,1,5.00),(522,66,2,2,5.00),(523,66,3,2,5.00),(524,66,36,2,5.00),(525,66,37,2,5.00),(526,66,38,2,5.00),(527,66,39,2,5.00),(528,66,40,2,5.00),(529,66,41,2,5.00),(530,66,4,3,5.00),(531,66,44,3,5.00),(532,66,5,4,5.00),(533,66,6,5,5.00),(534,66,7,6,5.00),(535,67,1,1,3.00),(536,67,49,1,3.00),(537,67,2,2,5.00),(538,67,3,2,3.00),(539,67,36,2,3.00),(540,67,37,2,4.00),(541,67,38,2,4.00),(542,67,39,2,4.00),(543,67,40,2,4.00),(544,67,41,2,4.00),(545,67,4,3,2.00),(546,67,44,3,1.00),(547,67,5,4,3.00),(548,67,6,5,1.00),(549,67,7,6,1.00);
/*!40000 ALTER TABLE `result` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schoolyear`
--

DROP TABLE IF EXISTS `schoolyear`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schoolyear` (
  `SchoolYearID` int(11) NOT NULL AUTO_INCREMENT,
  `SchoolYear` varchar(9) NOT NULL,
  `Status` varchar(20) NOT NULL,
  PRIMARY KEY (`SchoolYearID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schoolyear`
--

LOCK TABLES `schoolyear` WRITE;
/*!40000 ALTER TABLE `schoolyear` DISABLE KEYS */;
INSERT INTO `schoolyear` VALUES (1,'2025-2026','Active');
/*!40000 ALTER TABLE `schoolyear` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student` (
  `StudID` varchar(20) NOT NULL,
  `AccID` int(11) DEFAULT NULL,
  `Fname` varchar(50) DEFAULT NULL,
  `Mname` varchar(50) DEFAULT NULL,
  `Lname` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT '/user.png',
  PRIMARY KEY (`StudID`),
  KEY `AccID` (`AccID`),
  CONSTRAINT `student_ibfk_1` FOREIGN KEY (`AccID`) REFERENCES `user_account` (`AccID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student`
--

LOCK TABLES `student` WRITE;
/*!40000 ALTER TABLE `student` DISABLE KEYS */;
INSERT INTO `student` VALUES ('asd',48,'harry','leonora','asd','/user.png'),('s2025-20025',37,'Shawn','Michael','Bulos','StudentProfile/s2025-20025/img_68edf0a6b53504.78926408.jpg'),('s2025-2002512312',63,'Shawn','leonora','teresa','/user.png'),('s2025-200252',39,'Lyndon','asd','Cabang','/user.png'),('s2025-20025223',62,'Joseph','A','Capule','/user.png'),('s2025-2002522323',49,'Shawn','Rei','Bulos','/user.png'),('s2025-20025223323',64,'Raizen','Rei','Raizen','/user.png'),('s2025-2002523',38,'Lyndon ','leonora','cabang','/user.png'),('s2025-20025232',40,'Takte','Ka','Boss','/user.png'),('s2025-200252323',41,'Shawn','michael','Dela Cruz','StudentProfile/s2025-200252323/img_68d39a1b5e4514.23839369.jpg'),('s2025-2002523233',52,'Gol','D','Roger','/user.png'),('s2025-20025sd',47,'harry','leonora','Bulos','/user.png');
/*!40000 ALTER TABLE `student` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subject`
--

DROP TABLE IF EXISTS `subject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subject` (
  `SubjectID` int(11) NOT NULL AUTO_INCREMENT,
  `SubjectName` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`SubjectID`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subject`
--

LOCK TABLES `subject` WRITE;
/*!40000 ALTER TABLE `subject` DISABLE KEYS */;
INSERT INTO `subject` VALUES (1,'Math1'),(2,'Science1'),(3,'English1'),(4,'Filipino1'),(5,'TLE1'),(6,'Araling Panlipunan1'),(7,'Character Education1'),(8,'MAPEH1'),(9,'Math2'),(10,'Science2'),(11,'English2'),(12,'Filipino2'),(13,'TLE2'),(14,'Araling Panlipunan2'),(15,'Character Education2'),(16,'MAPEH2'),(17,'Math3'),(18,'Science3'),(19,'English3'),(20,'Filipino3'),(21,'TLE3'),(22,'Araling Panlipunan3'),(23,'Character Education3'),(24,'MAPEH3'),(25,'Math4'),(26,'Science4'),(27,'English4'),(28,'Filipino4'),(29,'TLE4'),(30,'Araling Panlipunan4'),(31,'Character Education4'),(32,'MAPEH4');
/*!40000 ALTER TABLE `subject` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subject_peryear`
--

DROP TABLE IF EXISTS `subject_peryear`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subject_peryear` (
  `YearLevel` varchar(20) NOT NULL,
  `SubjectID` int(11) NOT NULL,
  PRIMARY KEY (`YearLevel`,`SubjectID`),
  KEY `SubjectID` (`SubjectID`),
  CONSTRAINT `subject_peryear_ibfk_2` FOREIGN KEY (`SubjectID`) REFERENCES `subject` (`SubjectID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subject_peryear`
--

LOCK TABLES `subject_peryear` WRITE;
/*!40000 ALTER TABLE `subject_peryear` DISABLE KEYS */;
INSERT INTO `subject_peryear` VALUES ('10',25),('10',26),('10',27),('10',28),('10',29),('10',30),('10',31),('10',32),('7',1),('7',2),('7',3),('7',4),('7',5),('7',6),('7',7),('7',8),('8',9),('8',10),('8',11),('8',12),('8',13),('8',14),('8',15),('8',16),('9',17),('9',18),('9',19),('9',20),('9',21),('9',22),('9',23),('9',24);
/*!40000 ALTER TABLE `subject_peryear` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher`
--

DROP TABLE IF EXISTS `teacher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher` (
  `TeacherID` varchar(20) NOT NULL,
  `AccID` int(11) DEFAULT NULL,
  `Fname` varchar(50) DEFAULT NULL,
  `Mname` varchar(50) DEFAULT NULL,
  `Lname` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT '/user.png',
  PRIMARY KEY (`TeacherID`),
  KEY `AccID` (`AccID`),
  CONSTRAINT `teacher_ibfk_1` FOREIGN KEY (`AccID`) REFERENCES `user_account` (`AccID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher`
--

LOCK TABLES `teacher` WRITE;
/*!40000 ALTER TABLE `teacher` DISABLE KEYS */;
INSERT INTO `teacher` VALUES ('T001',1,'Juan','M.','Dela Cruz','TeacherProfile/T001/img_687e483572d703.99652106.jpg'),('T00100',42,'Aris Kwong','Sheng','Diego','/user.png'),('T002',2,'Maria Selene','G.','Reyes','/user.png'),('T003',3,'Jose','L.','Santos','/user.png'),('T004',4,'Ana','P.','Garcia','/user.png'),('T005',5,'Pedro','B.','Lopez','/user.png'),('T006',6,'Luisa','R.','Mendoza','/user.png'),('T007',7,'Carlos','D.','Torres','/user.png'),('T008',8,'Isabel','A.','Ramos','TeacherProfile/T008/img_68fb0b6eafe952.90730171.jpg'),('T009',9,'Ramon','F.','Cruz','/user.png'),('T010',10,'Elena','C.','Bautista','/user.png'),('T011',11,'Arnold','J.','Navarro','TeacherProfile/T011/img_68fb0b41093400.82802876.jpg'),('T012',12,'Lorna','E.','Domingo','/user.png'),('T013',13,'Dennis','V.','Aquino','/user.png'),('T014',14,'Sheila','K.','Villanueva','/user.png'),('T015',15,'Nestor','Q.','Salvador','/user.png'),('T016',16,'Jenny','Z.','Fernandez','/user.png'),('T017',23,'harry','michael','rei','/user.png'),('T018',25,'tine','mendez','cabang','/user.png'),('T19',28,'harry','asd','asd','/user.png');
/*!40000 ALTER TABLE `teacher` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_persubject`
--

DROP TABLE IF EXISTS `teacher_persubject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher_persubject` (
  `TeacherID` varchar(20) NOT NULL,
  `SubjectID` int(11) NOT NULL,
  `SchoolYearID` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`TeacherID`,`SubjectID`),
  KEY `SubjectID` (`SubjectID`),
  CONSTRAINT `teacher_persubject_ibfk_1` FOREIGN KEY (`TeacherID`) REFERENCES `teacher` (`TeacherID`),
  CONSTRAINT `teacher_persubject_ibfk_2` FOREIGN KEY (`SubjectID`) REFERENCES `subject` (`SubjectID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_persubject`
--

LOCK TABLES `teacher_persubject` WRITE;
/*!40000 ALTER TABLE `teacher_persubject` DISABLE KEYS */;
INSERT INTO `teacher_persubject` VALUES ('T008',1,1),('T008',3,1),('T008',9,1),('T008',25,1),('T008',27,1),('T008',28,1),('T008',29,1),('T008',30,1),('T008',31,1),('T008',32,1),('T011',2,1),('T011',4,1),('T011',5,1),('T011',6,1),('T011',7,1),('T011',10,1),('T011',13,1),('T011',17,1),('T011',28,1),('T013',7,1),('T013',25,1);
/*!40000 ALTER TABLE `teacher_persubject` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_subjectmap`
--

DROP TABLE IF EXISTS `teacher_subjectmap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher_subjectmap` (
  `TeacherID` varchar(20) DEFAULT NULL,
  `SubjectID` int(11) DEFAULT NULL,
  `YearLevel` varchar(20) DEFAULT NULL,
  `SectionName` varchar(50) DEFAULT NULL,
  `SchoolYearID` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_subjectmap`
--

LOCK TABLES `teacher_subjectmap` WRITE;
/*!40000 ALTER TABLE `teacher_subjectmap` DISABLE KEYS */;
INSERT INTO `teacher_subjectmap` VALUES ('T008',1,'7','St. Paul',1),('T008',1,'7','St. Peter',1),('T011',17,'9','St. Monica',1),('T008',9,'8','St. Agnes',1),('T008',3,'7','St. Paul',1),('T008',3,'7','St. Peter',1),('T011',6,'7','St. Peter',1),('T011',6,'7','St. Paul',1),('T011',5,'7','St. Paul',1),('T011',5,'7','St. Peter',1),('T013',7,'7','St. Peter',1),('T011',7,'7','St. Paul',1),('T011',4,'7','St. Peter',1),('T011',13,'8','St. Agnes',1),('T013',25,'10','St. Joseph',1),('T011',28,'10','St. Veronica',1),('T011',2,'7','St. Peter',1),('T011',10,'8','St. John',1),('T011',10,'8','St. Agnes',1),('T008',25,'10','St. Joseph',1),('T008',28,'10','St. Joseph',1),('T008',29,'10','St. Joseph',1),('T008',30,'10','St. Joseph',1),('T008',31,'10','St. Joseph',1),('T008',32,'10','St. Joseph',1),('T008',27,'10','St. Joseph',1),('T013',14,'8','St. Agnes',1),('T011',9,'8','St. Agnes',5),('T011',12,'8','St. Agnes',1),('T011',15,'8','St. Agnes',1),('T011',16,'8','St. Agnes',1),('T011',11,'8','St. Agnes',1),('T008',18,'9','St. Monica',1),('T008',18,'9','St. Therese',1);
/*!40000 ALTER TABLE `teacher_subjectmap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_yearsection`
--

DROP TABLE IF EXISTS `teacher_yearsection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher_yearsection` (
  `TeacherID` varchar(20) NOT NULL,
  `YearLevel` varchar(20) NOT NULL,
  `SectionName` varchar(50) NOT NULL,
  `SchoolYearID` int(11) NOT NULL DEFAULT 1,
  CONSTRAINT `teacher_yearsection_ibfk_1` FOREIGN KEY (`TeacherID`) REFERENCES `teacher` (`TeacherID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_yearsection`
--

LOCK TABLES `teacher_yearsection` WRITE;
/*!40000 ALTER TABLE `teacher_yearsection` DISABLE KEYS */;
INSERT INTO `teacher_yearsection` VALUES ('T008','7','St. Paul',1),('T008','7','St. Peter',1),('T011','9','St. Monica',1),('T008','8','St. Agnes',1),('T011','10','St. Joseph',1),('T011','7','St. Peter',1),('T011','7','St. Paul',1),('T013','7','St. Peter',1),('T011','8','St. Agnes',1),('T013','10','St. Joseph',1),('T011','10','St. Veronica',1),('T011','8','St. John',1),('T011','8','St. Agnes',1),('T008','10','St. Joseph',1);
/*!40000 ALTER TABLE `teacher_yearsection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_account`
--

DROP TABLE IF EXISTS `user_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_account` (
  `AccID` int(11) NOT NULL AUTO_INCREMENT,
  `Email` varchar(100) DEFAULT NULL,
  `Password` varchar(100) DEFAULT NULL,
  `UserType` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`AccID`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_account`
--

LOCK TABLES `user_account` WRITE;
/*!40000 ALTER TABLE `user_account` DISABLE KEYS */;
INSERT INTO `user_account` VALUES (1,'teacher1@gmail.com','$2y$10$q7Upu1sg0cDjnxa1cjA3nertERf7xPxoH2V9DGO4jkOP.riAQMwYG','Teacher',1),(2,'alanbradbalarag@gmail.com','$2y$10$MtTEsqDairEe.oH1nz9TROaL/.ENIzqgaoxRUJIK3CSRlE6i5J4Sq','Teacher',1),(3,'admin1@gmail.com','$2y$10$jvIdLSZKs1e5cG8AGbQDP.lXTWIe1TxrciQKknJOUWrd3CKUQ7mSi','Admin',1),(4,'shawnbulos03d@gmail.com','$2y$10$keIPy6zVR7NE.jGxjlILyuNhon9vBp4G5rybzYeJKkmHV1PjAMof2','Teacher',1),(5,'buloss012@gmail.com','$2y$10$qV7HDW4kHN2amuGIu1G92uNyy/hgCiPnyfjF16Q.izbI.4H8dqCvm','Teacher',1),(6,'ryzen1520@gmail.com','$2y$10$MkJabcMAYAZ5mKXjF/gRvOhxRDDHewRqhWZKi7WLb/3Ag0Jynjvae','Teacher',1),(7,'teacher1@gmail.com','$2y$10$TAn.mgltEXNAgd1b4Rg1puFgq7qxgc1JCvQy7pSqzLAcL.FDIOnNO','Teacher',1),(8,'teacher2@gmail.com','$2y$10$c/SAlIBIiawkjiRmq8VOIusY52yutQjBbnflClH9XbVG84AMoF6ea','Teacher',1),(9,'teacher3@gmail.com','$2y$10$Osz72mfBxKPFDTZqNXVt8.isRjTSGUymdoRDFgz.ipa49Q3wk21/u','Teacher',1),(10,'teacher4@gmail.com','$2y$10$NINxu/1x6v/UP1Qgv9zQoO8Po57pALtTZoAcbkdiwsiyCuGVB972C','Teacher',1),(11,'teacher5@gmail.com','$2y$10$AB3kRrL4E2ZV3rUfKscuzenhntSpjb.QRfHgF88L59vzK98WU9Lu6','Teacher',1),(12,'teacher6@gmail.com','$2y$10$8ly6kMTV0OlNo47EeCEz8.qWFQlINnGusHccJ0wGZ9cBwy/QA1gX6','Teacher',1),(13,'teacher7@gmail.com','$2y$10$1rp3kWor43lFD48wTP7WxuS4wZNKva3qsAYHQyMWQLSfhE9pcWVr2','Teacher',1),(14,'teacher8@gmail.com','$2y$10$0plqpQswHjIERpwAJJKmwOmAptJzjqNT0rNnQq6xs/YN48pOuWGiO','Teacher',1),(15,'teacher9@gmail.com','$2y$10$BRv4HO74BnfMZ.WHUc74bu2HcWrKYABmBqhuHkcmImk9O2Twnr9WG','Teacher',1),(16,'teacher10@gmail.com','$2y$10$lUAg69v9I0bBRpnBTzjmte6gcaLHtoipr4tJDBQaiZ4R0aVlzaam.','Teacher',1),(17,'teacher11@gmail.com','$2y$10$.gCmceBlZVZlrhW6mv7jbuVoxRenuC.C71KTlnkXy9D3gtkxP9FC.','Teacher',1),(18,'teacher12@gmail.com','$2y$10$Q20OVi4DmglsGAShtYZvv.ziji3Bm86umynhSYrBMD3PUvbBytAIy','Teacher',1),(19,'teacher13@gmail.com','$2y$10$uAC/uNTYAsdbUkjQabEvduoQsOKo573M4Y2AqCgS7DvkeL8nDJuJm','Teacher',1),(20,'teacher14@gmail.com','$2y$10$5FAQDcus2xTBhKc4ooDo6uJDYraJCaVP5EcONV6AGG4h46NcTIwJS','Teacher',1),(21,'teacher15@gmail.com','$2y$10$aG3jUHSz1p0SX6yqSXSgH.KbjvYE96rKwk4vq0iWxqyJsss8a5lZy','Teacher',1),(22,'teacher16@gmail.com','$2y$10$O7Diyd7wPZVGJ1lH0kCFC.6q1x.vtQNnO6.wAub7ToU5Z.FAghSH6','Teacher',1),(23,'buloss0122@gmail.com','$2y$10$1CbLODrtbWaujH/mZfK32.o8a2aRkMH7DZJdZo1QiGi0pS4YxuyMy','Teacher',1),(25,'ryzen12312@gmail.com','$2y$10$TUv7Yq.Bes1R9q2xT03wVe4ma/Opclg.iaD1bRWNy4qnh9lzwrqVm','Teacher',1),(28,'teacher19@gmail.com','$2y$10$LahbWS3S22N8JCf66SCWxu7STlhFGSikcn2KYN1HP1BtPGKSehale','Teacher',1),(37,'shawnbulos03@gmail.com','$2y$10$.aa513yShKvvdblX47o.jOVTz19vpthe2AtV.C6qNF/wk1Z/TgfE6','Student',1),(38,'sharmainepagador@gmail.com','$2y$10$l2Lm0hQykYIE5RiK8DdbYujeap7qkqorib8rEdKSfgz6K/.JjSUy6','Student',1),(39,'lyndoncabang@gmail.com','$2y$10$9fH2LToAmZstdWFGKLsIa.nUUvq1KUus4J/1y1k51bn0alFoTdlwa','Student',1),(40,'shisalleva27@gmail.com','$2y$10$AlOj/MbLRRH/vhaeiwR8FeXyXepNrBgd/.n6/tppsayMAmJsrbsTC','Student',1),(41,'arisdiegoml@gmail.com','$2y$10$1z9ERzSNp5gcoCY0rhjTaOx.FDs4resnxKXEHKlGOx2eCpTWKeWDW','Student',1),(42,'arisdiegosml@gmail.com','$2y$10$80nbFTXpCxEzPg6nxg/Cw.RtJ9TmAYTQaUnHjFfCFYhK8RMu.qQdC','Teacher',1),(43,'ryzen152sd0@gmail.com','$2y$10$mVGnG4sTJvgXU6AFuBSmc.ljAzMAUu2gSu4IzRx0Ke2kavzgDxd3S','Teacher',1),(44,'shawnbulosss03@gmail.com','$2y$10$3fxX3Soh8AiiVdejcOh50ujG/kOfAc6X0PCeze.msbMDm2q0Uun1W','Admin',1),(45,'MelbaDelaCruz@gmail.com','$2y$10$kJCBChAr.swA.Gu4vcFzzuFGQ3UxxPZCDjHek0tQmRIRl4pTqGGya','Principal',1),(47,'sakuragih805@gmail.com','$2y$10$aXT8n31TNcS42vQx6LHl3.CoZmTSZv1URZqsi70DfPKbFz5bwHrh6','Student',1),(48,'shawnbulos033@gmail.com','$2y$10$Ep7/gVqlwD8kGoU.8mZUReE8jXVB4duecpvoBhXv0iecMkJWzV.Fy','Student',1),(49,'shawnbulos0333@gmail.com','$2y$10$0n4H/37dJJowq/gTZZjr4ufVk91Ymy4LZ4uW3DKmjKm4R4zo7rIzG','Student',1),(52,'shawnbulos03333@gmail.com','$2y$10$5pSQWJeZo8m2E2wGCF/95O7tIRZKE16jpzCaYqTsqFJOUnJ19JbbO','Student',1),(62,'josephbryan@gmail.com','$2y$10$LzWKPJvlqSnBrDsV3k1lzegWXX7fc2nEy8H0mEJ2eBZdZt4mK/kta','Student',2),(63,'shawnbulos033333@gmail.com','$2y$10$jMh/GWpi2/2B/KfN.OnghuPkquR1GWrc5Gwt7IYOE2rel3FqbUu9y','Student',2),(64,'shawnbulos0333333@gmail.com','$2y$10$SRqQd1LSDKMu/wg3zd05BOSDDagZuUoy9WYlfiC5pZW56zYGy/MP6','Student',1);
/*!40000 ALTER TABLE `user_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `year_section`
--

DROP TABLE IF EXISTS `year_section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `year_section` (
  `YearSecID` int(11) NOT NULL,
  `YearLevel` varchar(20) NOT NULL,
  `SectionName` varchar(50) NOT NULL,
  PRIMARY KEY (`YearLevel`,`SectionName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `year_section`
--

LOCK TABLES `year_section` WRITE;
/*!40000 ALTER TABLE `year_section` DISABLE KEYS */;
INSERT INTO `year_section` VALUES (7,'10','St. Joseph'),(8,'10','St. Veronica'),(2,'7','St. Paul'),(1,'7','St. Peter'),(4,'8','St. Agnes'),(3,'8','St. John'),(6,'9','St. Monica'),(5,'9','St. Therese');
/*!40000 ALTER TABLE `year_section` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-24 23:10:19
