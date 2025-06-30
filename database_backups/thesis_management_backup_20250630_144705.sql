-- MariaDB dump 10.19  Distrib 10.4.24-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: thesis_management
-- ------------------------------------------------------
-- Server version	10.4.24-MariaDB

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
-- Current Database: `thesis_management`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `thesis_management` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;

USE `thesis_management`;

--
-- Table structure for table `adviser_metrics`
--

DROP TABLE IF EXISTS `adviser_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `adviser_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adviser_id` int(11) NOT NULL,
  `metric_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_value` float NOT NULL,
  `time_period` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `adviser_id` (`adviser_id`),
  KEY `metric_name` (`metric_name`),
  KEY `time_period` (`time_period`),
  CONSTRAINT `adviser_metrics_ibfk_1` FOREIGN KEY (`adviser_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `adviser_metrics`
--

LOCK TABLES `adviser_metrics` WRITE;
/*!40000 ALTER TABLE `adviser_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `adviser_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `analytics_logs`
--

DROP TABLE IF EXISTS `analytics_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `analytics_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `event_type` (`event_type`),
  KEY `entity_type` (`entity_type`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `analytics_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analytics_logs`
--

LOCK TABLES `analytics_logs` WRITE;
/*!40000 ALTER TABLE `analytics_logs` DISABLE KEYS */;
INSERT INTO `analytics_logs` VALUES (1,'assign_adviser',7,22,'student','{\"thesis_id\":\"15\",\"thesis_title\":\"test\",\"student_id\":\"22\",\"adviser_id\":\"7\"}','2025-06-24 06:40:59');
/*!40000 ALTER TABLE `analytics_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chapters`
--

DROP TABLE IF EXISTS `chapters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chapters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thesis_id` int(11) NOT NULL,
  `chapter_number` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `file_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_chapter` (`thesis_id`,`chapter_number`),
  KEY `thesis_id` (`thesis_id`),
  KEY `idx_chapters_status` (`status`),
  KEY `fk_chapters_file_id` (`file_id`),
  CONSTRAINT `chapters_ibfk_1` FOREIGN KEY (`thesis_id`) REFERENCES `theses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chapters_file_id` FOREIGN KEY (`file_id`) REFERENCES `file_uploads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chapters`
--

LOCK TABLES `chapters` WRITE;
/*!40000 ALTER TABLE `chapters` DISABLE KEYS */;
INSERT INTO `chapters` VALUES (1,1,1,'Introduction','\r\n        <h2>Chapter: Introduction</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'approved','2024-01-15 02:00:00',NULL,'2025-06-19 06:22:47','2025-06-23 05:59:46',NULL),(2,1,2,'Literature Review','\r\n        <h2>Chapter: Literature Review</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'approved','2024-02-20 06:30:00',NULL,'2025-06-19 06:22:47','2025-06-23 05:59:46',NULL),(3,1,3,'Methodology','\r\n        <h2>Chapter: Methodology</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'submitted','2024-03-10 01:15:00',NULL,'2025-06-19 06:22:47','2025-06-23 05:59:46',NULL),(4,1,4,'Implementation','\r\n        <h2>Chapter: Implementation</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'draft',NULL,NULL,'2025-06-19 06:22:47','2025-06-23 05:59:46',NULL),(5,1,5,'Results and Analysis','\r\n        <h2>Chapter: Results and Analysis</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'draft',NULL,NULL,'2025-06-19 06:22:47','2025-06-23 05:59:46',NULL),(6,2,1,'Introduction','\r\n        <h2>Chapter: Introduction</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'approved','2024-01-20 03:00:00',NULL,'2025-06-19 06:22:47','2025-06-23 05:59:46',NULL),(7,2,2,'Background Study','\r\n        <h2>Chapter: Background Study</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'submitted','2024-03-05 07:45:00',NULL,'2025-06-19 06:22:47','2025-06-23 05:59:46',NULL),(8,2,3,'System Design','\r\n        <h2>Chapter: System Design</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'draft',NULL,NULL,'2025-06-19 06:22:47','2025-06-23 05:59:46',NULL),(9,3,1,'Project Overview','\r\n        <h2>Chapter: Project Overview</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'draft',NULL,NULL,'2025-06-19 06:22:47','2025-06-23 05:59:46',NULL),(10,3,2,'Technical Requirements','\r\n        <h2>Chapter: Technical Requirements</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'draft',NULL,NULL,'2025-06-19 06:22:47','2025-06-23 05:59:46',NULL),(11,4,1,'Introduction','This chapter introduces the research problem and objectives...','../uploads/6858e82805ecc_1750657064.docx','approved','2024-02-10 01:30:00',NULL,'2025-06-19 06:29:09','2025-06-23 05:37:44',NULL),(12,4,2,'Literature Review','This chapter reviews existing literature on machine learning in healthcare...','../uploads/6858e83437ac2_1750657076.docx','approved','2024-03-15 06:20:00',NULL,'2025-06-19 06:29:09','2025-06-23 05:37:56',NULL),(13,4,3,'Research Methodology','This chapter outlines the research methodology and data collection methods...','../uploads/6853c0cc40484_1750319308.docx','submitted','2024-04-05 03:45:00',NULL,'2025-06-19 06:29:09','2025-06-19 07:48:28',NULL),(14,4,4,'System Design','This chapter will detail the design of the proposed machine learning system...','../uploads/6853bcdb1d5bf_1750318299.docx','draft',NULL,NULL,'2025-06-19 06:29:09','2025-06-19 07:31:39',NULL),(15,13,1,'Introduction','\r\n        <h2>Chapter: Introduction</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ','../uploads/6858ea8f69106_1750657679.docx','submitted','2025-06-23 05:47:59',NULL,'2025-06-23 05:47:01','2025-06-23 05:59:46',NULL),(16,13,2,'Literature Review','\r\n        <h2>Chapter: Literature Review</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'draft',NULL,NULL,'2025-06-23 05:47:01','2025-06-23 05:59:46',NULL),(17,13,3,'Methodology','\r\n        <h2>Chapter: Methodology</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'draft',NULL,NULL,'2025-06-23 05:47:01','2025-06-23 05:59:46',NULL),(18,13,4,'Results and Discussion','\r\n        <h2>Chapter: Results and Discussion</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'draft',NULL,NULL,'2025-06-23 05:47:01','2025-06-23 05:59:46',NULL),(19,13,5,'Conclusion','\r\n        <h2>Chapter: Conclusion</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'draft',NULL,NULL,'2025-06-23 05:47:01','2025-06-23 05:59:46',NULL),(20,14,1,'Introduction','\r\n        <h2>Chapter: Introduction</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'submitted',NULL,NULL,'2025-06-23 06:42:54','2025-06-24 05:29:54',NULL),(21,14,2,'Literature Review','\r\n        <h2>Chapter: Literature Review</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'approved',NULL,NULL,'2025-06-23 06:42:54','2025-06-24 05:29:54',NULL),(22,14,3,'Methodology','\r\n        <h2>Chapter: Methodology</h2>\r\n        \r\n        <h3>Introduction</h3>\r\n        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>\r\n        \r\n        <h3>Background</h3>\r\n        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>\r\n        \r\n        <h3>Methodology</h3>\r\n        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>\r\n        <ul>\r\n            <li>Literature review and analysis</li>\r\n            <li>Data collection and processing</li>\r\n            <li>Statistical analysis and interpretation</li>\r\n            <li>Validation and verification of results</li>\r\n        </ul>\r\n        \r\n        <h3>Key Findings</h3>\r\n        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>\r\n        \r\n        <h3>Discussion</h3>\r\n        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>\r\n        \r\n        <h3>Conclusion</h3>\r\n        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>\r\n        ',NULL,'draft',NULL,NULL,'2025-06-23 06:42:54','2025-06-24 05:29:54',NULL),(23,15,1,'Introduction',NULL,NULL,'draft',NULL,NULL,'2025-06-24 06:40:59','2025-06-24 06:40:59',NULL),(24,15,2,'Literature Review',NULL,NULL,'draft',NULL,NULL,'2025-06-24 06:40:59','2025-06-24 06:40:59',NULL),(25,15,3,'Methodology',NULL,NULL,'draft',NULL,NULL,'2025-06-24 06:40:59','2025-06-24 06:40:59',NULL),(26,15,4,'Results and Discussion',NULL,NULL,'draft',NULL,NULL,'2025-06-24 06:40:59','2025-06-24 06:40:59',NULL),(27,15,5,'Conclusion',NULL,NULL,'draft',NULL,NULL,'2025-06-24 06:40:59','2025-06-24 06:40:59',NULL);
/*!40000 ALTER TABLE `chapters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_metrics`
--

DROP TABLE IF EXISTS `department_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_value` float NOT NULL,
  `time_period` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `department` (`department`),
  KEY `metric_name` (`metric_name`),
  KEY `time_period` (`time_period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_metrics`
--

LOCK TABLES `department_metrics` WRITE;
/*!40000 ALTER TABLE `department_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_comments`
--

DROP TABLE IF EXISTS `document_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chapter_id` int(11) NOT NULL,
  `adviser_id` int(11) NOT NULL,
  `highlight_id` int(11) DEFAULT NULL,
  `comment_text` text NOT NULL,
  `start_offset` int(11) DEFAULT NULL,
  `end_offset` int(11) DEFAULT NULL,
  `position_x` float DEFAULT NULL,
  `position_y` float DEFAULT NULL,
  `status` enum('active','resolved') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `metadata` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chapter_id` (`chapter_id`),
  KEY `adviser_id` (`adviser_id`),
  KEY `highlight_id` (`highlight_id`),
  CONSTRAINT `document_comments_ibfk_1` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_comments_ibfk_2` FOREIGN KEY (`adviser_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_comments_ibfk_3` FOREIGN KEY (`highlight_id`) REFERENCES `document_highlights` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_comments`
--

LOCK TABLES `document_comments` WRITE;
/*!40000 ALTER TABLE `document_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_highlights`
--

DROP TABLE IF EXISTS `document_highlights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_highlights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chapter_id` int(11) NOT NULL,
  `adviser_id` int(11) NOT NULL,
  `start_offset` int(11) NOT NULL,
  `end_offset` int(11) NOT NULL,
  `highlighted_text` text NOT NULL,
  `highlight_color` varchar(20) DEFAULT '#ffeb3b',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `chapter_id` (`chapter_id`),
  KEY `adviser_id` (`adviser_id`),
  CONSTRAINT `document_highlights_ibfk_1` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_highlights_ibfk_2` FOREIGN KEY (`adviser_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_highlights`
--

LOCK TABLES `document_highlights` WRITE;
/*!40000 ALTER TABLE `document_highlights` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_highlights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chapter_id` int(11) NOT NULL,
  `adviser_id` int(11) NOT NULL,
  `feedback_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `feedback_type` enum('comment','revision','approval') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'comment',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `chapter_id` (`chapter_id`),
  KEY `adviser_id` (`adviser_id`),
  KEY `idx_feedback_type` (`feedback_type`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`adviser_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
INSERT INTO `feedback` VALUES (1,1,1,'Excellent introduction. The problem statement is clear and well-defined. Consider adding more recent statistics about the current market trends.','approval','2025-06-19 06:22:47'),(2,2,1,'Good literature review overall. Please include more recent research papers from 2023-2024. The theoretical framework section needs more detail.','comment','2025-06-19 06:22:47'),(3,3,1,'The methodology section needs revision. Please clarify the data collection methods and add more details about the experimental setup.','revision','2025-06-19 06:22:47'),(4,6,1,'Strong background research. The blockchain concepts are well explained. Consider adding a comparison table of different blockchain platforms.','approval','2025-06-19 06:22:47'),(5,7,1,'The system design is comprehensive but needs more technical details. Please include system architecture diagrams and database schema.','revision','2025-06-19 06:22:47'),(6,10,5,'Well-structured introduction. The research gap is clearly identified. Consider strengthening the significance section with more industry statistics.','approval','2025-06-19 06:29:09'),(7,11,5,'Comprehensive literature review. Good analysis of current techniques. Add more recent papers from 2023-2024 in the deep learning section.','approval','2025-06-19 06:29:09'),(8,12,5,'The methodology is promising but needs refinement. Please elaborate on the data preprocessing steps and validation methods. Consider adding a flowchart of the entire process.','revision','2025-06-19 06:29:09'),(9,1,1,'Test feedback','comment','2025-06-24 05:53:08'),(10,1,1,'Test feedback','comment','2025-06-24 05:54:57'),(11,4,1,'Direct test feedback - 2025-06-30 08:10:55','comment','2025-06-30 06:10:55'),(12,23,7,'This is a test feedback - 2025-06-30 08:12:03','comment','2025-06-30 06:12:03'),(13,23,7,'Test feedback from simple test - 2025-06-30 08:15:04','comment','2025-06-30 06:15:04');
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_analysis`
--

DROP TABLE IF EXISTS `feedback_analysis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback_analysis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feedback_id` int(11) NOT NULL,
  `sentiment_score` float DEFAULT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keywords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`keywords`)),
  `analyzed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `feedback_id` (`feedback_id`),
  KEY `sentiment_score` (`sentiment_score`),
  KEY `category` (`category`),
  CONSTRAINT `feedback_analysis_ibfk_1` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_analysis`
--

LOCK TABLES `feedback_analysis` WRITE;
/*!40000 ALTER TABLE `feedback_analysis` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback_analysis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `file_uploads`
--

DROP TABLE IF EXISTS `file_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `file_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chapter_id` int(11) NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `chapter_id` (`chapter_id`),
  CONSTRAINT `file_uploads_ibfk_1` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `file_uploads`
--

LOCK TABLES `file_uploads` WRITE;
/*!40000 ALTER TABLE `file_uploads` DISABLE KEYS */;
INSERT INTO `file_uploads` VALUES (1,11,'literature_review_v2.pdf','ch2_6_lit_review_20240315.pdf','uploads/chapters/ch2_6_lit_review_20240315.pdf',2458621,'application/pdf','2025-06-19 06:29:09'),(2,14,'asynchWork3.1_mid_hci2.docx','6853b99975129_1750317465.docx','../uploads/6853b99975129_1750317465.docx',13611,'application/vnd.openxmlformats-officedocument.word','2025-06-19 07:17:45'),(3,14,'asynchWork3.1_mid_hci2.docx','6853ba6c1750a_1750317676.docx','../uploads/6853ba6c1750a_1750317676.docx',13611,'application/vnd.openxmlformats-officedocument.word','2025-06-19 07:21:16'),(4,14,'asynchWork3.1_mid_hci2 (1).docx','6853bcdb1d5bf_1750318299.docx','../uploads/6853bcdb1d5bf_1750318299.docx',13611,'application/vnd.openxmlformats-officedocument.word','2025-06-19 07:31:39'),(5,13,'asynchWork3.1_mid_hci2.docx','6853c0cc40484_1750319308.docx','../uploads/6853c0cc40484_1750319308.docx',13611,'application/vnd.openxmlformats-officedocument.word','2025-06-19 07:48:28'),(6,11,'asynchWork3.1_mid_hci2.docx','6858e82805ecc_1750657064.docx','../uploads/6858e82805ecc_1750657064.docx',13611,'application/vnd.openxmlformats-officedocument.word','2025-06-23 05:37:44'),(7,12,'asynchWork3.1_mid_hci2 (1).docx','6858e83437ac2_1750657076.docx','../uploads/6858e83437ac2_1750657076.docx',13611,'application/vnd.openxmlformats-officedocument.word','2025-06-23 05:37:56'),(8,15,'asynchWork3.1_mid_hci2 (1).docx','6858ea8f69106_1750657679.docx','../uploads/6858ea8f69106_1750657679.docx',13611,'application/vnd.openxmlformats-officedocument.word','2025-06-23 05:47:59');
/*!40000 ALTER TABLE `file_uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('info','warning','success','error') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_notifications_read` (`is_read`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,2,'Chapter Approved','Your Chapter 1: Introduction has been approved by Dr. John Doe','success',0,'2025-06-19 06:22:47'),(2,2,'Feedback Available','New feedback is available for Chapter 3: Methodology','info',0,'2025-06-19 06:22:47'),(3,2,'Deadline Reminder','Chapter 4-5 submission deadline is approaching (Due: May 20, 2024)','warning',0,'2025-06-19 06:22:47'),(4,3,'Chapter Approved','Your Chapter 1: Introduction has been approved','success',0,'2025-06-19 06:22:47'),(5,3,'Revision Required','Chapter 2: Background Study requires revision','warning',0,'2025-06-19 06:22:47'),(6,1,'New Submission','Alice Smith has submitted Chapter 3: Methodology for review','info',0,'2025-06-19 06:22:47'),(7,1,'Student Progress','Bob Johnson has updated his thesis progress','info',0,'2025-06-19 06:22:47'),(8,6,'Chapter Approved','Your Chapter 1: Introduction has been approved by Dr. Jane Smith','success',1,'2025-06-19 06:29:09'),(9,6,'Chapter Approved','Your Chapter 2: Literature Review has been approved by Dr. Jane Smith','success',1,'2025-06-19 06:29:09'),(10,6,'Feedback Available','New feedback is available for Chapter 3: Research Methodology','info',0,'2025-06-19 06:29:09'),(11,6,'Deadline Reminder','Data Collection milestone deadline is approaching (Due: May 15, 2024)','warning',0,'2025-06-19 06:29:09'),(12,5,'New Submission','Sample Student has submitted Chapter 3: Research Methodology for review','info',0,'2025-06-19 06:29:09'),(13,2,'Adviser Assigned','You have been assigned to an adviser. Please schedule an initial meeting.','info',0,'2025-06-19 07:18:55'),(14,7,'New Student Assigned','Alice Smith has been assigned as your advisee.','info',0,'2025-06-19 07:18:55'),(15,6,'Adviser Assigned','You have been assigned to an adviser. Please schedule an initial meeting.','info',0,'2025-06-19 07:19:03'),(16,7,'New Student Assigned','dave aban has been assigned as your advisee.','info',0,'2025-06-19 07:19:03'),(17,3,'Adviser Assigned','You have been assigned to an adviser. Please schedule an initial meeting.','info',0,'2025-06-19 07:19:24'),(18,7,'New Student Assigned','Bob Johnson has been assigned as your advisee.','info',0,'2025-06-19 07:19:24'),(19,4,'Adviser Assigned','You have been assigned to an adviser. Please schedule an initial meeting.','info',0,'2025-06-19 07:32:31'),(20,7,'New Student Assigned','Carol Williams has been assigned as your advisee.','info',0,'2025-06-19 07:32:31'),(21,15,'Adviser Assigned','You have been assigned to an adviser. Please schedule an initial meeting.','info',0,'2025-06-23 05:40:36'),(22,7,'New Student Assigned','aiki abasola has been assigned as your advisee.','info',0,'2025-06-23 05:40:36'),(23,16,'Adviser Assigned','You have been assigned to an adviser. Please schedule an initial meeting.','info',0,'2025-06-23 05:47:01'),(24,7,'New Student Assigned','james talamo has been assigned as your advisee.','info',0,'2025-06-23 05:47:01'),(25,22,'Adviser Assigned','You have been assigned to an adviser. Please schedule an initial meeting.','info',0,'2025-06-24 06:40:59'),(26,7,'New Student Assigned','zer acevedo has been assigned as your advisee.','info',0,'2025-06-24 06:40:59');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_templates`
--

DROP TABLE IF EXISTS `report_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `query` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `chart_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `report_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_templates`
--

LOCK TABLES `report_templates` WRITE;
/*!40000 ALTER TABLE `report_templates` DISABLE KEYS */;
INSERT INTO `report_templates` VALUES (12,'Thesis Progress Overview','Shows the progress of all theses currently in the system','SELECT CONCAT(u.full_name, \" - \", LEFT(t.title, 30), \"...\") AS thesis_info, t.progress_percentage FROM theses t JOIN users u ON t.student_id = u.id ORDER BY t.progress_percentage DESC',NULL,'bar',1,'2025-06-23 07:00:23','2025-06-23 07:00:23'),(13,'Adviser Workload','Displays the number of students assigned to each adviser','SELECT u.full_name AS adviser_name, COUNT(t.id) AS student_count FROM users u LEFT JOIN theses t ON u.id = t.adviser_id WHERE u.role = \"adviser\" GROUP BY u.id, u.full_name ORDER BY student_count DESC',NULL,'bar',1,'2025-06-23 07:00:23','2025-06-23 07:00:23'),(14,'Thesis Status Distribution','Shows the distribution of thesis statuses','SELECT status, COUNT(*) AS count FROM theses GROUP BY status ORDER BY count DESC',NULL,'pie',1,'2025-06-23 07:00:23','2025-06-23 07:00:23'),(15,'Student Progress Summary','Summary of student progress across all theses','SELECT u.full_name AS student_name, t.progress_percentage FROM theses t JOIN users u ON t.student_id = u.id ORDER BY t.progress_percentage DESC',NULL,'bar',1,'2025-06-23 07:00:23','2025-06-23 07:00:23'),(16,'Chapter Submission Timeline','Shows chapter submission patterns over time','SELECT DATE_FORMAT(created_at, \"%Y-%m\") AS month, COUNT(*) AS submission_count FROM chapters WHERE created_at IS NOT NULL GROUP BY month ORDER BY month DESC LIMIT 12',NULL,'line',1,'2025-06-23 07:00:23','2025-06-23 07:00:23'),(17,'Active Students Count','Shows count of students by thesis status','SELECT t.status AS thesis_status, COUNT(DISTINCT t.student_id) AS student_count FROM theses t GROUP BY t.status ORDER BY student_count DESC',NULL,'pie',1,'2025-06-23 07:00:23','2025-06-23 07:00:23'),(18,'Chapter Submissions by Number','Simple breakdown showing how many students submitted Chapter 1, Chapter 2, Chapter 3, etc.','SELECT \r\n                            CONCAT(\"Chapter \", c.chapter_number) AS chapter,\r\n                            c.chapter_number as chapter_num,\r\n                            COUNT(DISTINCT CASE WHEN c.status IN (\"submitted\", \"approved\") THEN t.student_id END) as students_submitted,\r\n                            COUNT(DISTINCT t.student_id) as total_students_with_chapter,\r\n                            ROUND((COUNT(DISTINCT CASE WHEN c.status IN (\"submitted\", \"approved\") THEN t.student_id END) / COUNT(DISTINCT t.student_id)) * 100, 1) as submission_rate\r\n                        FROM chapters c\r\n                        JOIN theses t ON c.thesis_id = t.id\r\n                        JOIN users u ON t.student_id = u.id\r\n                        WHERE u.role = \"student\"\r\n                        GROUP BY c.chapter_number\r\n                        ORDER BY c.chapter_number',NULL,'bar',1,'2025-06-23 07:40:54','2025-06-23 07:40:54'),(19,'Students per Chapter - Quick Summary','Quick overview: Chapter 1 = X students, Chapter 2 = Y students, etc.','SELECT \r\n                            CASE c.chapter_number\r\n                                WHEN 1 THEN \"Chapter 1\"\r\n                                WHEN 2 THEN \"Chapter 2\" \r\n                                WHEN 3 THEN \"Chapter 3\"\r\n                                WHEN 4 THEN \"Chapter 4\"\r\n                                WHEN 5 THEN \"Chapter 5\"\r\n                                ELSE CONCAT(\"Chapter \", c.chapter_number)\r\n                            END as chapter_name,\r\n                            COUNT(DISTINCT t.student_id) as number_of_students\r\n                        FROM chapters c\r\n                        JOIN theses t ON c.thesis_id = t.id\r\n                        JOIN users u ON t.student_id = u.id\r\n                        WHERE u.role = \"student\" \r\n                        AND c.status IN (\"submitted\", \"approved\")\r\n                        GROUP BY c.chapter_number\r\n                        ORDER BY c.chapter_number',NULL,'bar',1,'2025-06-23 07:40:54','2025-06-23 07:40:54');
/*!40000 ALTER TABLE `report_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saved_reports`
--

DROP TABLE IF EXISTS `saved_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saved_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`report_data`)),
  `parameters_used` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters_used`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `template_id` (`template_id`),
  CONSTRAINT `saved_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_reports_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saved_reports`
--

LOCK TABLES `saved_reports` WRITE;
/*!40000 ALTER TABLE `saved_reports` DISABLE KEYS */;
INSERT INTO `saved_reports` VALUES (4,7,18,'f','f','{\"template\":{\"name\":\"Chapter Submissions by Number\",\"description\":\"f\",\"chart_type\":\"bar\"},\"data\":[{\"chapter\":\"Chapter 1\",\"chapter_num\":1,\"students_submitted\":4,\"total_students_with_chapter\":5,\"submission_rate\":\"80.0\"},{\"chapter\":\"Chapter 2\",\"chapter_num\":2,\"students_submitted\":3,\"total_students_with_chapter\":5,\"submission_rate\":\"60.0\"},{\"chapter\":\"Chapter 3\",\"chapter_num\":3,\"students_submitted\":2,\"total_students_with_chapter\":4,\"submission_rate\":\"50.0\"},{\"chapter\":\"Chapter 4\",\"chapter_num\":4,\"students_submitted\":0,\"total_students_with_chapter\":3,\"submission_rate\":\"0.0\"},{\"chapter\":\"Chapter 5\",\"chapter_num\":5,\"students_submitted\":0,\"total_students_with_chapter\":2,\"submission_rate\":\"0.0\"}]}','[]','2025-06-24 06:27:27'),(9,7,15,'da','da','{\"template\":{\"name\":\"Student Progress Summary\",\"description\":\"da\",\"chart_type\":\"bar\"},\"data\":[{\"student_name\":\"dave aban\",\"progress_percentage\":63},{\"student_name\":\"Alice Smith\",\"progress_percentage\":50},{\"student_name\":\"Bob Johnson\",\"progress_percentage\":50},{\"student_name\":\"Alice Smith\",\"progress_percentage\":45},{\"student_name\":\"james talamo\",\"progress_percentage\":10},{\"student_name\":\"aiki abasola\",\"progress_percentage\":0},{\"student_name\":\"Carol Williams\",\"progress_percentage\":0},{\"student_name\":\"Bob Johnson\",\"progress_percentage\":0},{\"student_name\":\"dave aban\",\"progress_percentage\":0},{\"student_name\":\"Alice Smith\",\"progress_percentage\":0},{\"student_name\":\"Carol Williams\",\"progress_percentage\":0},{\"student_name\":\"zer acevedo\",\"progress_percentage\":0}]}','[]','2025-06-24 07:14:56');
/*!40000 ALTER TABLE `saved_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_metrics`
--

DROP TABLE IF EXISTS `student_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `metric_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_value` float NOT NULL,
  `time_period` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `metric_name` (`metric_name`),
  KEY `time_period` (`time_period`),
  CONSTRAINT `student_metrics_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_metrics`
--

LOCK TABLES `student_metrics` WRITE;
/*!40000 ALTER TABLE `student_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `theses`
--

DROP TABLE IF EXISTS `theses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `theses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `adviser_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abstract` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','in_progress','for_review','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `progress_percentage` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `adviser_id` (`adviser_id`),
  KEY `idx_theses_status` (`status`),
  CONSTRAINT `theses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `theses_ibfk_2` FOREIGN KEY (`adviser_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `theses`
--

LOCK TABLES `theses` WRITE;
/*!40000 ALTER TABLE `theses` DISABLE KEYS */;
INSERT INTO `theses` VALUES (1,2,1,'AI-Based Recommendation System','This thesis explores the development of an AI-based recommendation system for e-commerce platforms. The system utilizes machine learning algorithms to analyze user behavior and provide personalized product recommendations.','in_progress',50,'2025-06-19 06:22:47','2025-06-19 07:27:44'),(2,3,1,'Blockchain Technology in Supply Chain Management','An investigation into the application of blockchain technology for improving transparency and traceability in supply chain management systems.','in_progress',50,'2025-06-19 06:22:47','2025-06-19 07:27:44'),(3,4,1,'Mobile App Development for Educational Purposes','Development of a mobile application designed to enhance learning experiences in higher education through interactive features and gamification.','draft',0,'2025-06-19 06:22:47','2025-06-19 07:27:44'),(4,6,5,'Machine Learning Applications in Healthcare','This research explores the implementation of machine learning algorithms in healthcare diagnostics, focusing on early disease detection and personalized treatment recommendations based on patient data analysis.','in_progress',63,'2025-06-19 06:29:09','2025-06-19 07:27:44'),(8,2,7,'Untitled Thesis',NULL,'draft',0,'2025-06-19 07:18:55','2025-06-19 07:18:55'),(9,6,7,'Untitled Thesis',NULL,'draft',0,'2025-06-19 07:19:03','2025-06-19 07:19:03'),(10,3,7,'Untitled Thesis',NULL,'draft',0,'2025-06-19 07:19:24','2025-06-19 07:19:24'),(11,4,7,'Untitled Thesis',NULL,'draft',0,'2025-06-19 07:32:31','2025-06-19 07:32:31'),(12,15,7,'Untitled Thesis',NULL,'draft',0,'2025-06-23 05:40:36','2025-06-23 05:40:36'),(13,16,7,'the effect of thesis in the mental health of the students','','draft',10,'2025-06-23 05:47:01','2025-06-23 05:47:59'),(14,2,1,'AI-Based Recommendation System','This thesis explores the development of an AI-based recommendation system...','in_progress',45,'2025-06-23 06:42:54','2025-06-23 06:42:54'),(15,22,7,'test','','draft',0,'2025-06-24 06:40:59','2025-06-24 06:40:59');
/*!40000 ALTER TABLE `theses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `thesis_metrics`
--

DROP TABLE IF EXISTS `thesis_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `thesis_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thesis_id` int(11) NOT NULL,
  `metric_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_value` float NOT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `thesis_id` (`thesis_id`),
  KEY `metric_name` (`metric_name`),
  CONSTRAINT `thesis_metrics_ibfk_1` FOREIGN KEY (`thesis_id`) REFERENCES `theses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thesis_metrics`
--

LOCK TABLES `thesis_metrics` WRITE;
/*!40000 ALTER TABLE `thesis_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `thesis_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timeline`
--

DROP TABLE IF EXISTS `timeline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thesis_id` int(11) NOT NULL,
  `milestone_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `due_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed','overdue') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `thesis_id` (`thesis_id`),
  KEY `idx_timeline_status` (`status`),
  CONSTRAINT `timeline_ibfk_1` FOREIGN KEY (`thesis_id`) REFERENCES `theses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timeline`
--

LOCK TABLES `timeline` WRITE;
/*!40000 ALTER TABLE `timeline` DISABLE KEYS */;
INSERT INTO `timeline` VALUES (1,1,'Proposal Defense',NULL,'2024-01-10','2024-01-10','completed','2025-06-19 06:22:47'),(2,1,'Chapter 1-3 Submission',NULL,'2024-03-15','2024-03-10','completed','2025-06-19 06:22:47'),(3,1,'Chapter 4-5 Submission',NULL,'2024-05-20',NULL,'in_progress','2025-06-19 06:22:47'),(4,1,'First Draft Completion',NULL,'2024-07-15',NULL,'pending','2025-06-19 06:22:47'),(5,1,'Final Defense',NULL,'2024-09-30',NULL,'pending','2025-06-19 06:22:47'),(6,2,'Proposal Defense',NULL,'2024-01-15','2024-01-15','completed','2025-06-19 06:22:47'),(7,2,'Chapter 1-2 Submission',NULL,'2024-03-20',NULL,'in_progress','2025-06-19 06:22:47'),(8,2,'System Implementation',NULL,'2024-06-30',NULL,'pending','2025-06-19 06:22:47'),(9,2,'Final Defense',NULL,'2024-10-15',NULL,'pending','2025-06-19 06:22:47'),(10,3,'Proposal Defense',NULL,'2024-02-01',NULL,'pending','2025-06-19 06:22:47'),(11,3,'Requirements Analysis',NULL,'2024-04-15',NULL,'pending','2025-06-19 06:22:47'),(12,3,'Prototype Development',NULL,'2024-07-30',NULL,'pending','2025-06-19 06:22:47'),(13,3,'Final Defense',NULL,'2024-11-30',NULL,'pending','2025-06-19 06:22:47'),(14,4,'Proposal Defense','Initial presentation of research proposal','2024-01-30','2024-01-28','completed','2025-06-19 06:29:09'),(15,4,'Chapter 1-2 Submission','Submission of Introduction and Literature Review','2024-03-20','2024-03-15','completed','2025-06-19 06:29:09'),(16,4,'Chapter 3 Submission','Submission of Research Methodology','2024-04-10','2024-04-05','completed','2025-06-19 06:29:09'),(17,4,'Data Collection','Collection and preprocessing of healthcare datasets','2024-05-15',NULL,'in_progress','2025-06-19 06:29:09'),(18,4,'Algorithm Implementation','Implementation of machine learning algorithms','2024-06-30',NULL,'pending','2025-06-19 06:29:09'),(19,4,'Final Defense','Final thesis defense presentation','2024-10-15',NULL,'pending','2025-06-19 06:29:09'),(20,14,'Proposal Defense',NULL,'2024-03-15',NULL,'completed','2025-06-23 06:42:54'),(21,14,'Chapter 1-3 Submission',NULL,'2024-06-01',NULL,'completed','2025-06-23 06:42:54'),(22,14,'Chapter 4-5 Submission',NULL,'2024-08-15',NULL,'in_progress','2025-06-23 06:42:54'),(23,14,'Final Defense',NULL,'2024-10-30',NULL,'pending','2025-06-23 06:42:54');
/*!40000 ALTER TABLE `timeline` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('student','adviser') COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `faculty_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'adviser@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Dr. John Doe','adviser',NULL,'FAC001',NULL,'Computer Science','2025-06-19 06:22:47','2025-06-19 06:22:47'),(2,'student@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Alice Smith','student','STU001',NULL,'Computer Science',NULL,'2025-06-19 06:22:47','2025-06-19 06:22:47'),(3,'student2@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Bob Johnson','student','STU002',NULL,'Information Technology',NULL,'2025-06-19 06:22:47','2025-06-19 06:22:47'),(4,'student3@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Carol Williams','student','STU003',NULL,'Computer Science',NULL,'2025-06-19 06:22:47','2025-06-19 06:22:47'),(5,'adviser2@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Dr. Jane Smith','adviser',NULL,'FAC002',NULL,'Information Technology','2025-06-19 06:22:47','2025-06-19 06:22:47'),(6,'students@example.com','$2y$10$aed1zqtB9mfzO6nlbmSsyev7/AG396vnAN4uu/1dti6ZdGQ6BBIE.','dave aban','student','22-0585-676','','Computer Science','','2025-06-19 06:24:12','2025-06-19 06:24:12'),(7,'advisers@example.com','$2y$10$6cfEwNkouhALxqd.gTYAnOyXOaeGps0aOErwr/kgKMb1m8lpX6BJa','adrian servillejo','adviser','','123','','Computer Science','2025-06-19 06:28:03','2025-06-19 06:28:03'),(15,'aiki@gmail.com','$2y$10$aorl8X/b8fYYKZt5MTjwa.uLrlWCy.AAPzk4Qn4ESUL.Wi/fyxR5e','aiki abasola','student','12345678','','Information Technology','','2025-06-23 05:40:21','2025-06-23 05:40:21'),(16,'james@gmail.com','$2y$10$3i2qYvYBqjYME32/XhvUUuaP4dpL3v1nA2pSFBaZax2Cx.NPBZDBG','james talamo','student','1234',NULL,'bsit',NULL,'2025-06-23 05:47:01','2025-06-23 05:47:01'),(22,'zer@gmail.com','$2y$10$P0ZarxnHzcggfRMeNxnUqepfyDcwynN2sR4CDN6uHZVwQQ6065pgm','zer acevedo','student','12345678',NULL,'comsci',NULL,'2025-06-24 06:40:59','2025-06-24 06:40:59');
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

-- Dump completed on 2025-06-30 14:47:05
