-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: localhost    Database: food_ordering_system
-- ------------------------------------------------------
-- Server version	8.0.41

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
-- Table structure for table `bulk_order_items`
--

DROP TABLE IF EXISTS `bulk_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bulk_order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `bulk_order_items_ibfk_1` (`order_id`),
  CONSTRAINT `bulk_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `bulk_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bulk_order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `food_items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bulk_order_items`
--

LOCK TABLES `bulk_order_items` WRITE;
/*!40000 ALTER TABLE `bulk_order_items` DISABLE KEYS */;
INSERT INTO `bulk_order_items` VALUES (1,1,3,1,100.00,100.00);
/*!40000 ALTER TABLE `bulk_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bulk_orders`
--

DROP TABLE IF EXISTS `bulk_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bulk_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supply_date` date NOT NULL,
  `event_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bulk_orders`
--

LOCK TABLES `bulk_orders` WRITE;
/*!40000 ALTER TABLE `bulk_orders` DISABLE KEYS */;
INSERT INTO `bulk_orders` VALUES (1,'2025-11-16','sample','CSE','2025-11-16 18:48:28',0);
/*!40000 ALTER TABLE `bulk_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cashier_requests`
--

DROP TABLE IF EXISTS `cashier_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashier_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cashier_id` int NOT NULL,
  `request_type` enum('add_item','update_item','delete_item') NOT NULL,
  `food_item_id` int DEFAULT NULL,
  `request_data` json NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cashier_id` (`cashier_id`),
  KEY `food_item_id` (`food_item_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `cashier_requests_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cashier_requests_ibfk_2` FOREIGN KEY (`food_item_id`) REFERENCES `food_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cashier_requests_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashier_requests`
--

LOCK TABLES `cashier_requests` WRITE;
/*!40000 ALTER TABLE `cashier_requests` DISABLE KEYS */;
INSERT INTO `cashier_requests` VALUES (1,11,'add_item',NULL,'{\"name\": \"noodles\", \"price\": 100, \"category\": \"lunch\", \"description\": \"yummy\", \"time_available\": \"12:00-16:00\"}','rejected',10,'2025-09-14 12:32:27','2025-09-14 12:40:39'),(2,11,'add_item',NULL,'{\"name\": \"noodles\", \"price\": 100, \"category\": \"lunch\", \"description\": \"yummy\", \"time_available\": \"12:00-16:00\"}','rejected',10,'2025-09-14 12:32:55','2025-09-14 12:40:37'),(3,11,'add_item',NULL,'{\"name\": \"lassi\", \"price\": 200, \"category\": \"beverages\", \"description\": \"tasty\", \"time_available\": \"06:00-22:00\"}','rejected',10,'2025-09-14 12:33:34','2025-09-14 12:40:35'),(4,11,'add_item',NULL,'{\"name\": \"noodles\", \"price\": 100, \"category\": \"lunch\", \"description\": \"yummy\", \"time_available\": \"12:00-16:00\", \"quantity_available\": 20}','approved',10,'2025-09-14 12:40:00','2025-09-14 12:40:49'),(5,11,'add_item',NULL,'{\"name\": \"lassi\", \"price\": 200, \"category\": \"beverages\", \"description\": \"tasty\", \"time_available\": \"12:00-16:00\", \"quantity_available\": 25}','approved',10,'2025-09-14 12:43:48','2025-09-14 12:44:01'),(6,11,'add_item',NULL,'{\"name\": \"pizza\", \"price\": 500, \"category\": \"lunch\", \"description\": \"nice\", \"time_available\": \"06:00-22:00\", \"quantity_available\": 30}','rejected',10,'2025-09-14 12:44:51','2025-09-14 12:45:16'),(7,11,'add_item',NULL,'{\"name\": \"momos\", \"price\": 150, \"category\": \"snacks\", \"description\": \"spicy and tasty\", \"time_available\": \"16:00-19:00\", \"quantity_available\": 4}','approved',10,'2025-11-12 06:30:51','2025-11-12 06:31:06'),(8,11,'add_item',NULL,'{\"name\": \"momos\", \"price\": 150, \"category\": \"snacks\", \"description\": \"spicy and tasty\", \"time_available\": \"12:00-16:00\", \"quantity_available\": 4}','approved',10,'2025-11-12 06:33:39','2025-11-12 06:33:59'),(9,11,'add_item',NULL,'{\"name\": \"abc\", \"price\": 100, \"category\": \"breakfast\", \"description\": \"hhh\", \"time_available\": \"12:00-16:00\", \"quantity_available\": 5}','approved',10,'2025-11-12 09:14:23','2025-11-12 09:15:56'),(10,11,'add_item',NULL,'{\"name\": \"prawn munjurian\", \"price\": 150, \"category\": \"breakfast\", \"description\": \"tasty and spicy\", \"time_available\": \"06:00-11:00\", \"quantity_available\": 1}','approved',10,'2025-11-13 04:50:05','2025-11-13 04:50:55');
/*!40000 ALTER TABLE `cashier_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dept_name` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'CSE','$2y$10$iD6KbkfZoYpLz4BcD8SZXO9KGs4h1BhX2VdQHLqP39iHbAcEYT8t6','2025-11-16 18:59:51'),(2,'EEE','$2y$10$iD6KbkfZoYpLz4BcD8SZXO9KGs4h1BhX2VdQHLqP39iHbAcEYT8t6','2025-11-16 19:00:07');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `food_items`
--

DROP TABLE IF EXISTS `food_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `food_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `category` enum('breakfast','lunch','snacks','beverages') NOT NULL,
  `time_available` varchar(50) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `quantity_available` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_stock_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `food_items`
--

LOCK TABLES `food_items` WRITE;
/*!40000 ALTER TABLE `food_items` DISABLE KEYS */;
INSERT INTO `food_items` VALUES (1,'Breakes Combo','tasty',200.00,'breakfast','06:00-11:00',NULL,400,1,'2025-09-14 06:34:21','2025-11-13 04:42:24',NULL),(2,'Masala Dosa','South Indian crispy crepe with potato filling',120.00,'breakfast','06:00-11:00',NULL,10,1,'2025-09-14 06:34:21','2025-09-14 13:07:10',NULL),(3,'Sambar Idli','tasty and healthy',100.00,'breakfast','06:00-11:00',NULL,1,1,'2025-09-14 06:34:21','2025-11-16 18:48:28',NULL),(4,'Poha','Flattened rice with vegetables and spices',60.00,'breakfast','06:00-11:00',NULL,0,1,'2025-09-14 06:34:21','2025-09-14 06:34:21',NULL),(5,'mutton briyani','Aromatic rice with tender chicken pieces',300.00,'lunch','12:00-16:00',NULL,7,1,'2025-09-14 06:34:21','2025-11-18 09:08:31',NULL),(6,'Veg Thali','Complete vegetarian meal with rice, dal, vegetables',180.00,'lunch','12:00-16:00',NULL,0,1,'2025-09-14 06:34:21','2025-09-14 06:34:21',NULL),(7,'Paneer Butter Masala','Cottage cheese in rich tomato gravy with rice',220.00,'lunch','12:00-16:00',NULL,12,1,'2025-09-14 06:34:21','2025-11-12 08:39:30',NULL),(8,'Dal Tadka','Yellow lentils with spices and rice',140.00,'lunch','12:00-16:00',NULL,0,1,'2025-09-14 06:34:21','2025-11-12 09:04:33',NULL),(9,'Samosa','Crispy fried pastry with spiced potato filling',30.00,'snacks','16:00-19:00',NULL,0,1,'2025-09-14 06:34:21','2025-09-14 06:34:21',NULL),(10,'Pakora','Deep fried vegetable fritters',40.00,'snacks','16:00-19:00',NULL,0,1,'2025-09-14 06:34:21','2025-09-14 06:34:21',NULL),(11,'Veg Sandwich','tasty and cheesy',100.00,'snacks','16:00-19:00',NULL,100,1,'2025-09-14 06:34:21','2025-11-12 06:26:26',NULL),(12,'Chaat','Spicy street food snack',50.00,'snacks','16:00-19:00',NULL,98,1,'2025-09-14 06:34:21','2025-11-10 10:50:29',NULL),(13,'Masala Tea','Hot Indian spiced tea',20.00,'beverages','06:00-22:00',NULL,0,1,'2025-09-14 06:34:21','2025-09-14 06:34:21',NULL),(14,'Coffee','Fresh brewed coffee',25.00,'beverages','06:00-22:00',NULL,0,1,'2025-09-14 06:34:21','2025-11-12 03:38:30',NULL),(15,'Fresh Juice','Seasonal fruit juice',50.00,'beverages','06:00-22:00',NULL,27,1,'2025-09-14 06:34:21','2025-11-18 09:59:34',NULL),(16,'Lassi','Yogurt-based drink',40.00,'beverages','06:00-22:00',NULL,100,1,'2025-09-14 06:34:21','2025-09-16 03:05:21',NULL),(17,'Cold Drink','Soft drinks and sodas',30.00,'beverages','06:00-22:00',NULL,16,1,'2025-09-14 06:34:21','2025-11-18 09:59:34',NULL),(18,'Veg Burger','Delicious crispy veg patty burger',89.00,'lunch','10:00-18:00','uploads/veg_burger.jpg',18,1,'2025-09-14 08:26:26','2025-11-18 09:59:34',NULL),(19,'noodles','yummy',100.00,'lunch','12:00-16:00',NULL,8,1,'2025-09-14 12:40:49','2025-11-18 09:59:34',NULL),(20,'lassi','tasty',200.00,'beverages','12:00-16:00',NULL,25,1,'2025-09-14 12:44:01','2025-09-14 12:44:01',NULL),(21,'spicy momos','spicy and cheesy',200.00,'snacks','16:00-19:00',NULL,4,1,'2025-11-12 06:31:06','2025-11-12 06:34:52',NULL),(22,'momos','spicy and tasty',150.00,'snacks','12:00-16:00',NULL,4,1,'2025-11-12 06:33:59','2025-11-12 06:33:59',NULL),(23,'abc','hhh',100.00,'breakfast','12:00-16:00',NULL,97,1,'2025-11-12 09:15:56','2025-11-19 03:44:39','2025-11-18 06:47:42'),(24,'prawn munchurian','spicy',150.00,'breakfast','06:00-11:00',NULL,1,1,'2025-11-13 04:50:55','2025-11-13 05:30:55',NULL),(25,'prawn munjurian','tasty and spicy',150.00,'breakfast','06:00-11:00',NULL,0,1,'2025-11-13 05:10:49','2025-11-19 03:44:39',NULL);
/*!40000 ALTER TABLE `food_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `morning_balance`
--

DROP TABLE IF EXISTS `morning_balance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `morning_balance` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `dates` date DEFAULT (curdate()),
  `balance` bigint DEFAULT NULL,
  `cashier_id` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `morning_balance`
--

LOCK TABLES `morning_balance` WRITE;
/*!40000 ALTER TABLE `morning_balance` DISABLE KEYS */;
INSERT INTO `morning_balance` VALUES (1,'2025-09-22',1000,NULL),(2,'2025-09-22',1000,NULL),(3,'2025-09-25',100,NULL),(4,'2025-09-25',1000,NULL),(5,'2025-11-05',1000,NULL),(6,'2025-11-08',1500,NULL),(7,'2025-11-12',200,NULL),(8,'2025-11-12',123334,NULL);
/*!40000 ALTER TABLE `morning_balance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `food_item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `food_item_id` (`food_item_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`food_item_id`) REFERENCES `food_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (28,26,17,1,30.00),(29,27,5,1,300.00),(30,28,5,1,300.00),(31,29,12,1,50.00),(32,29,14,1,25.00),(33,29,17,3,30.00),(34,30,12,1,50.00),(35,30,14,2,25.00),(36,30,16,2,40.00),(37,31,12,1,50.00),(38,31,14,2,25.00),(39,31,16,2,40.00),(40,32,12,2,50.00),(41,33,12,2,50.00),(42,34,12,3,50.00),(43,35,12,1,50.00),(44,35,14,1,25.00),(45,35,15,1,50.00),(46,35,20,1,200.00),(47,36,5,1,300.00),(48,37,5,1,300.00),(49,37,8,1,140.00),(50,38,3,1,80.00),(51,38,1,1,150.00),(52,38,14,1,25.00),(53,38,17,1,30.00),(54,39,3,1,80.00),(55,40,17,1,30.00),(56,41,16,1,40.00),(57,41,17,1,30.00),(58,41,15,1,50.00),(59,42,17,1,30.00),(60,43,20,3,200.00),(61,43,15,2,50.00),(62,44,16,2,40.00),(63,45,14,1,25.00),(64,46,14,1,25.00),(65,47,14,1,25.00),(66,48,14,1,25.00),(67,49,14,1,25.00),(68,50,14,1,25.00),(69,51,14,1,25.00),(70,52,12,2,50.00),(71,53,14,1,25.00),(72,54,15,1,50.00),(73,55,17,1,30.00),(74,56,17,1,30.00),(75,57,17,1,30.00),(76,58,8,1,140.00),(77,59,5,1,300.00),(78,60,8,1,140.00),(79,60,15,1,50.00),(80,60,5,1,300.00),(81,61,14,17,25.00),(82,62,14,82,25.00),(84,64,19,1,100.00),(85,65,5,1,300.00),(86,66,19,1,100.00),(87,66,18,2,89.00),(88,66,17,1,30.00),(89,66,15,1,50.00),(90,67,23,3,100.00),(91,67,25,1,150.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `bill_number` varchar(20) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `items` json DEFAULT NULL,
  `payment_method` enum('wallet','razorpay') NOT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_scanned` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bill_number` (`bill_number`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (26,19,'BILL202511075078',30.00,NULL,'wallet','completed',NULL,NULL,'2025-11-07 06:22:54',NULL),(27,19,'BILL202511076846',300.00,NULL,'razorpay','pending','order_RcnsuuIVa0b4v7',NULL,'2025-11-07 09:38:56',NULL),(28,19,'BILL202511075174',300.00,'{\"mutton briyani\": 1}','razorpay','completed','order_RcoiHDRVKad5AH','pay_RcoiSl9SUBeMQR','2025-11-07 10:27:34',NULL),(29,19,'BILL202511075321',165.00,'{\"Chaat\": 1, \"Coffee\": 1, \"Cold Drink\": 3}','razorpay','completed','order_RcowQIKh8qFkd7','pay_RcowWP90YIk8OQ','2025-11-07 10:40:58',NULL),(30,19,'BILL202511071275',180.00,'{\"Chaat\": {\"rate\": \"50.00\", \"total\": 50, \"quantity\": 1}, \"Lassi\": {\"rate\": \"40.00\", \"total\": 80, \"quantity\": 2}, \"Coffee\": {\"rate\": \"25.00\", \"total\": 50, \"quantity\": 2}}','razorpay','pending','order_Rcp2ScyVz2h5YX',NULL,'2025-11-07 10:46:41',NULL),(31,19,'BILL202511072539',180.00,'{\"Chaat\": {\"rate\": \"50.00\", \"total\": 50, \"quantity\": 1}, \"Lassi\": {\"rate\": \"40.00\", \"total\": 80, \"quantity\": 2}, \"Coffee\": {\"rate\": \"25.00\", \"total\": 50, \"quantity\": 2}}','razorpay','pending','order_Rcp2ipfePN5k84',NULL,'2025-11-07 10:46:56',NULL),(32,19,'BILL202511078119',100.00,'{\"Chaat\": {\"rate\": \"50.00\", \"total\": 100, \"quantity\": 2}}','razorpay','pending','order_Rcp3IxYdY7J52u',NULL,'2025-11-07 10:47:29',NULL),(33,19,'BILL202511074857',100.00,'{\"Chaat\": 2}','razorpay','pending','order_Rcp3ZlcwwXtwVs',NULL,'2025-11-07 10:47:45',NULL),(34,19,'BILL202511074433',150.00,'{\"Chaat\": {\"rate\": \"50.00\", \"total\": 150, \"quantity\": 3}}','razorpay','completed','order_Rcp7V8wUqQKXyF','pay_Rcp7Zl4dnDv2PU','2025-11-07 10:51:28',NULL),(35,19,'BILL202511078724',325.00,'{\"Chaat\": {\"rate\": \"50.00\", \"total\": 50, \"quantity\": 1}, \"lassi\": {\"rate\": \"200.00\", \"total\": 200, \"quantity\": 1}, \"Coffee\": {\"rate\": \"25.00\", \"total\": 25, \"quantity\": 1}, \"Fresh Juice\": {\"rate\": \"50.00\", \"total\": 50, \"quantity\": 1}}','razorpay','completed','order_Rcp8uDLF0pHxJh','pay_Rcp8zxe8Tq7y1B','2025-11-07 10:52:47',NULL),(36,19,'BILL202511088142',300.00,'{\"mutton briyani\": {\"rate\": \"300.00\", \"total\": 300, \"quantity\": 1}}','razorpay','completed','order_RdB4votmHwLtDW','pay_RdB5BnoilD3XSE','2025-11-08 08:20:15',NULL),(37,19,'BILL202511081113',440.00,'{\"Dal Tadka\": {\"rate\": \"140.00\", \"total\": 140, \"quantity\": 1}, \"mutton briyani\": {\"rate\": \"300.00\", \"total\": 300, \"quantity\": 1}}','razorpay','completed','order_RdBJk3ngP3C1E6','pay_RdBJs5qqluvdgv','2025-11-08 08:34:18',NULL),(38,19,'BILL202511106765',285.00,'{\"Coffee\": {\"rate\": \"25.00\", \"total\": 25, \"quantity\": 1}, \"Cold Drink\": {\"rate\": \"30.00\", \"total\": 30, \"quantity\": 1}, \"Idli Sambar\": {\"rate\": \"80.00\", \"total\": 80, \"quantity\": 1}, \"Breakfast Combo\": {\"rate\": \"150.00\", \"total\": 150, \"quantity\": 1}}','razorpay','completed','order_Rdtj5BlDsEttTV','pay_RdtjBKJjG23bND','2025-11-10 04:00:48',NULL),(39,19,'BILL202511101439',80.00,'[{\"id\": 3, \"name\": \"Idli Sambar\", \"price\": \"80.00\", \"category\": \"breakfast\", \"quantity\": 1}]','wallet','completed',NULL,NULL,'2025-11-10 05:31:58',NULL),(40,19,'BILL202511103073',30.00,'[{\"id\": 17, \"name\": \"Cold Drink\", \"price\": \"30.00\", \"category\": \"beverages\", \"quantity\": 1}]','wallet','completed',NULL,NULL,'2025-11-10 05:32:15',NULL),(41,19,'BILL202511105679',120.00,'[{\"id\": 16, \"name\": \"Lassi\", \"price\": \"40.00\", \"category\": \"beverages\", \"quantity\": 1}, {\"id\": 17, \"name\": \"Cold Drink\", \"price\": \"30.00\", \"category\": \"beverages\", \"quantity\": 1}, {\"id\": 15, \"name\": \"Fresh Juice\", \"price\": \"50.00\", \"category\": \"beverages\", \"quantity\": 1}]','wallet','completed',NULL,NULL,'2025-11-10 05:34:39',NULL),(42,19,'BILL202511101008',30.00,'[{\"id\": 17, \"name\": \"Cold Drink\", \"price\": \"30.00\", \"category\": \"beverages\", \"quantity\": 1}]','wallet','completed',NULL,NULL,'2025-11-10 06:02:23',NULL),(43,19,'BILL2025111000001',700.00,NULL,'wallet','completed',NULL,NULL,'2025-11-10 06:09:40',NULL),(44,19,'C12025111000002',80.00,NULL,'wallet','completed',NULL,NULL,'2025-11-10 06:10:50',NULL),(45,19,'C12025111000003',25.00,NULL,'wallet','completed',NULL,NULL,'2025-11-10 06:11:15',NULL),(46,19,'C12025111000004',25.00,NULL,'wallet','completed',NULL,NULL,'2025-11-10 06:11:18',NULL),(47,19,'C12025111000005',25.00,NULL,'wallet','completed',NULL,NULL,'2025-11-10 06:11:27',NULL),(48,19,'C12025111000006',25.00,NULL,'wallet','completed',NULL,NULL,'2025-11-10 06:11:31',NULL),(49,19,'C12025111000007',25.00,NULL,'wallet','completed',NULL,NULL,'2025-11-10 06:11:35',NULL),(50,19,'C12025111000008',25.00,NULL,'razorpay','pending','order_Rdw03G3CPYv53G',NULL,'2025-11-10 06:14:15',NULL),(51,19,'C12025111000009',25.00,NULL,'razorpay','completed','order_Rdw1SUfKJ7yfji','pay_Rdw1bBgFrlZZsR','2025-11-10 06:15:36',NULL),(52,19,'C12025111000010',100.00,NULL,'wallet','completed',NULL,NULL,'2025-11-10 10:50:29',NULL),(53,19,'C12025111000011',25.00,'[{\"id\": 14, \"name\": \"Coffee\", \"price\": \"25.00\", \"category\": \"beverages\", \"quantity\": 1}]','wallet','completed',NULL,NULL,'2025-11-10 11:00:48',NULL),(54,19,'C12025111000012',50.00,'[{\"id\": 15, \"name\": \"Fresh Juice\", \"price\": \"50.00\", \"category\": \"beverages\", \"quantity\": 1}]','wallet','completed',NULL,NULL,'2025-11-10 11:03:34',NULL),(55,19,'C12025111000013',30.00,'[{\"id\": 17, \"name\": \"Cold Drink\", \"price\": \"30.00\", \"category\": \"beverages\", \"quantity\": 1}]','wallet','completed',NULL,NULL,'2025-11-10 11:06:53',NULL),(56,19,'C12025111100001',30.00,'[{\"id\": 17, \"name\": \"Cold Drink\", \"price\": \"30.00\", \"category\": \"beverages\", \"quantity\": 1}]','wallet','completed',NULL,NULL,'2025-11-11 06:16:45',NULL),(57,19,'C12025111100002',30.00,'[{\"id\": 17, \"name\": \"Cold Drink\", \"price\": \"30.00\", \"category\": \"beverages\", \"quantity\": 1}]','wallet','completed',NULL,NULL,'2025-11-11 06:17:51',NULL),(58,19,'C12025111100003',140.00,'[{\"id\": 8, \"name\": \"Dal Tadka\", \"price\": \"140.00\", \"category\": \"lunch\", \"quantity\": 1}]','wallet','completed',NULL,NULL,'2025-11-11 07:59:19',NULL),(59,19,'C12025111100004',300.00,'[{\"id\": 5, \"name\": \"mutton briyani\", \"price\": \"300.00\", \"category\": \"lunch\", \"quantity\": 1}]','wallet','completed',NULL,NULL,'2025-11-11 08:01:45',NULL),(60,19,'C12025111100005',490.00,'{\"Dal Tadka\": {\"rate\": \"140.00\", \"total\": 140, \"quantity\": 1}, \"Fresh Juice\": {\"rate\": \"50.00\", \"total\": 50, \"quantity\": 1}, \"mutton briyani\": {\"rate\": \"300.00\", \"total\": 300, \"quantity\": 1}}','razorpay','completed','order_ReNK36nXDCNrIC','pay_ReNKEf6DR2Miud','2025-11-11 08:57:54',NULL),(61,19,'C12025111200001',425.00,'[{\"id\": 14, \"name\": \"Coffee\", \"price\": \"25.00\", \"category\": \"beverages\", \"quantity\": 17}]','wallet','completed',NULL,NULL,'2025-11-12 03:36:27',NULL),(62,19,'C12025111200002',2050.00,'{\"Coffee\": {\"rate\": \"25.00\", \"total\": 2050, \"quantity\": 82}}','razorpay','completed','order_RegPINM4jsVJGv','pay_RegPPfIa13sdsr','2025-11-12 03:38:02',NULL),(64,27,'C12025111700001',100.00,'{\"noodles\": {\"rate\": \"100.00\", \"total\": 100, \"quantity\": 1}}','razorpay','completed','order_RgkjWIxGZxCkrd','pay_RgkjefRfdCCfaH','2025-11-17 09:09:57',NULL),(65,19,'C12025111800001',300.00,'{\"mutton briyani\": {\"rate\": \"300.00\", \"total\": 300, \"quantity\": 1}}','razorpay','completed','order_Rh9EgIeOtiW3YK','pay_Rh9EojvhtlZJ5G','2025-11-18 09:08:04',NULL),(66,19,'C12025111800002',358.00,'{\"noodles\": {\"rate\": \"100.00\", \"total\": 100, \"quantity\": 1}, \"Cold Drink\": {\"rate\": \"30.00\", \"total\": 30, \"quantity\": 1}, \"Veg Burger\": {\"rate\": \"89.00\", \"total\": 178, \"quantity\": 2}, \"Fresh Juice\": {\"rate\": \"50.00\", \"total\": 50, \"quantity\": 1}}','razorpay','completed','order_RhA6dY2dKvs3I2','pay_RhA6kJ8LMQiD2g','2025-11-18 09:59:11',NULL),(67,28,'C12025111900001',450.00,'{\"abc\": {\"rate\": \"100.00\", \"total\": 300, \"quantity\": 3}, \"prawn munjurian\": {\"rate\": \"150.00\", \"total\": 150, \"quantity\": 1}}','razorpay','completed','order_RhSFak7Z98eI4A','pay_RhSFkuRzvuJMVC','2025-11-19 03:44:07',NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `special_item`
--

DROP TABLE IF EXISTS `special_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `special_item` (
  `id` int NOT NULL DEFAULT '1',
  `food_name` varchar(100) NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `special_item`
--

LOCK TABLES `special_item` WRITE;
/*!40000 ALTER TABLE `special_item` DISABLE KEYS */;
INSERT INTO `special_item` VALUES (1,'Veg Sandwich','2025-11-16 16:03:57');
/*!40000 ALTER TABLE `special_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(10) NOT NULL,
  `staff_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (1,'I0339','ABILASHA T K'),(2,'I0184','SHABARIRAM C P'),(3,'I0185','SANTHANAMARI G'),(4,'I0085','DEEPA M'),(5,'I0103','MANOJ KUMAR P'),(6,'I0070','KUMARAVEL A'),(7,'I0375','RAVINDRAN R'),(8,'I0083','ELAYARAJA S'),(9,'I0359','VAISHNAVI S'),(10,'I0203','SANKARA SUBRAMANIAN R S'),(11,'I0082','RAJKUMAR R'),(12,'I0189','GAJENDRAN P'),(13,'I0076','CHINNARAJ P'),(14,'I0164','LATHA G'),(15,'I0344','DEEPANNITA CHAKRABORTY'),(16,'I0073','SATHIYANATHAN M'),(17,'I0385','PRAKASH T'),(18,'I0147','VENKATESH D');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `roll_no` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','cashier','admin','staff') DEFAULT 'user',
  `wallet_balance` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `reset_code` varchar(10) DEFAULT NULL,
  `reset_code_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roll_no` (`roll_no`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'2','cashier@foodsystem.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','cashier','0.00','2025-09-14 06:34:21','2025-09-14 06:34:21',NULL,NULL),(5,'5','mary@cashier.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','cashier','0.00','2025-09-14 06:34:21','2025-09-14 06:34:21',NULL,NULL),(10,'75522105','admin1@psgitech.ac.in','$2y$10$w3O8s/opCK.xAh4F58lt/u/4kF3PjNG7FZZ0e5heFGONOD8Wka5tK','admin','0.00','2025-09-14 11:22:46','2025-09-14 11:22:46',NULL,NULL),(11,'75522106','cashier1@psgitech.ac.in','$2y$10$w3O8s/opCK.xAh4F58lt/u/4kF3PjNG7FZZ0e5heFGONOD8Wka5tK','cashier','0.00','2025-09-14 11:27:42','2025-09-14 11:27:42',NULL,NULL),(19,'715522104002','22z102@psgitech.ac.in','$2y$10$iD6KbkfZoYpLz4BcD8SZXO9KGs4h1BhX2VdQHLqP39iHbAcEYT8t6','user','b3V6ZzdmT1NDeklUVEg2UC95RG1JZz09OjrhxyP7R58ixi3tB1EZeEHV','2025-11-04 06:07:50','2025-11-12 04:04:19',NULL,NULL),(25,'715522104001','22z101@psgitech.ac.in','$2y$10$WNRzYraECfmgP/lKjS76Ke9woKgDQfsLhCSYLCq0C6HDiK9RBX.Ii','user','Vmgxb1VSdEpNYXh3SElCUHpNTXBEUT09OjrD8bgErggKqkncb0dluDFs','2025-11-16 17:18:24','2025-11-16 17:26:57',NULL,NULL),(27,'715522104015','22z114@psgitech.ac.in','$2y$10$zjYp2YEdmJlL3WMfFKKwaOrcgxMrLaahqyZFXZU.q/Y9umjujSTDi','user','RXcvKzZTY2FuQllWcUxNaStkRUx4dz09OjqwzRJYQLGK2ef5y5j8ZJHB','2025-11-17 05:24:31','2025-11-17 05:25:07',NULL,NULL),(28,'715522104010','22z109@psgitech.ac.in','$2y$10$lzrL84V7SbL5XQMEjEjNW.n6sI2Ga.ScwDwzdPMazisYwcdQJuVLm','user','MWVHbFlrMzhxT040bXlYMWMwMjV5Zz09OjpX2LkI00aB1LLojsFG9M1o','2025-11-19 03:40:22','2025-11-19 03:40:22',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_transactions`
--

DROP TABLE IF EXISTS `wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `transaction_type` enum('credit','debit') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wallet_transactions_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_transactions`
--

LOCK TABLES `wallet_transactions` WRITE;
/*!40000 ALTER TABLE `wallet_transactions` DISABLE KEYS */;
INSERT INTO `wallet_transactions` VALUES (22,19,'credit',10.00,'Wallet top-up ',NULL,'2025-11-06 18:57:07'),(23,19,'credit',100.00,'Wallet top-up ',NULL,'2025-11-06 18:58:18'),(24,19,'debit',30.00,'Order payment',26,'2025-11-07 06:22:54'),(25,19,'credit',100.00,'Wallet top-up ',NULL,'2025-11-08 08:21:35'),(26,19,'credit',1000.00,'Wallet top-up ',NULL,'2025-11-08 08:34:01'),(27,19,'debit',80.00,'Order payment',39,'2025-11-10 05:31:58'),(28,19,'debit',30.00,'Order payment',40,'2025-11-10 05:32:15'),(29,19,'debit',120.00,'Order payment',41,'2025-11-10 05:34:39'),(30,19,'debit',30.00,'Order payment',42,'2025-11-10 06:02:23'),(31,19,'debit',700.00,'Order payment',43,'2025-11-10 06:09:40'),(32,19,'debit',80.00,'Order payment',44,'2025-11-10 06:10:50'),(33,19,'debit',25.00,'Order payment',45,'2025-11-10 06:11:15'),(34,19,'debit',25.00,'Order payment',46,'2025-11-10 06:11:18'),(35,19,'debit',25.00,'Order payment',47,'2025-11-10 06:11:27'),(36,19,'debit',25.00,'Order payment',48,'2025-11-10 06:11:31'),(37,19,'debit',25.00,'Order payment',49,'2025-11-10 06:11:35'),(38,19,'credit',1000.00,'Wallet top-up ',NULL,'2025-11-10 10:50:22'),(39,19,'debit',100.00,'Order payment',52,'2025-11-10 10:50:29'),(40,19,'debit',25.00,'Order payment',53,'2025-11-10 11:00:48'),(41,19,'debit',50.00,'Order payment',54,'2025-11-10 11:03:34'),(42,19,'debit',30.00,'Order payment',55,'2025-11-10 11:06:53'),(43,19,'debit',30.00,'Order payment',56,'2025-11-11 06:16:45'),(44,19,'debit',30.00,'Order payment',57,'2025-11-11 06:17:51'),(45,19,'debit',140.00,'Order payment',58,'2025-11-11 07:59:19'),(46,19,'debit',300.00,'Order payment',59,'2025-11-11 08:01:45'),(47,19,'debit',425.00,'Order payment',61,'2025-11-12 03:36:27'),(49,25,'credit',100.00,'Wallet top-up ',NULL,'2025-11-16 17:19:28');
/*!40000 ALTER TABLE `wallet_transactions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-19 19:38:01
