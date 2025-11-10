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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashier_requests`
--

LOCK TABLES `cashier_requests` WRITE;
/*!40000 ALTER TABLE `cashier_requests` DISABLE KEYS */;
INSERT INTO `cashier_requests` VALUES (1,11,'add_item',NULL,'{\"name\": \"noodles\", \"price\": 100, \"category\": \"lunch\", \"description\": \"yummy\", \"time_available\": \"12:00-16:00\"}','rejected',10,'2025-09-14 12:32:27','2025-09-14 12:40:39'),(2,11,'add_item',NULL,'{\"name\": \"noodles\", \"price\": 100, \"category\": \"lunch\", \"description\": \"yummy\", \"time_available\": \"12:00-16:00\"}','rejected',10,'2025-09-14 12:32:55','2025-09-14 12:40:37'),(3,11,'add_item',NULL,'{\"name\": \"lassi\", \"price\": 200, \"category\": \"beverages\", \"description\": \"tasty\", \"time_available\": \"06:00-22:00\"}','rejected',10,'2025-09-14 12:33:34','2025-09-14 12:40:35'),(4,11,'add_item',NULL,'{\"name\": \"noodles\", \"price\": 100, \"category\": \"lunch\", \"description\": \"yummy\", \"time_available\": \"12:00-16:00\", \"quantity_available\": 20}','approved',10,'2025-09-14 12:40:00','2025-09-14 12:40:49'),(5,11,'add_item',NULL,'{\"name\": \"lassi\", \"price\": 200, \"category\": \"beverages\", \"description\": \"tasty\", \"time_available\": \"12:00-16:00\", \"quantity_available\": 25}','approved',10,'2025-09-14 12:43:48','2025-09-14 12:44:01'),(6,11,'add_item',NULL,'{\"name\": \"pizza\", \"price\": 500, \"category\": \"lunch\", \"description\": \"nice\", \"time_available\": \"06:00-22:00\", \"quantity_available\": 30}','rejected',10,'2025-09-14 12:44:51','2025-09-14 12:45:16');
/*!40000 ALTER TABLE `cashier_requests` ENABLE KEYS */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `food_items`
--

LOCK TABLES `food_items` WRITE;
/*!40000 ALTER TABLE `food_items` DISABLE KEYS */;
INSERT INTO `food_items` VALUES (1,'Breakfast Combo','Eggs, toast, and coffee',150.00,'breakfast','06:00-11:00',NULL,400,1,'2025-09-14 06:34:21','2025-11-05 11:21:46'),(2,'Masala Dosa','South Indian crispy crepe with potato filling',120.00,'breakfast','06:00-11:00',NULL,10,1,'2025-09-14 06:34:21','2025-09-14 13:07:10'),(3,'Idli Sambar','Steamed rice cakes with lentil curry',80.00,'breakfast','06:00-11:00',NULL,2,1,'2025-09-14 06:34:21','2025-09-14 13:12:30'),(4,'Poha','Flattened rice with vegetables and spices',60.00,'breakfast','06:00-11:00',NULL,0,1,'2025-09-14 06:34:21','2025-09-14 06:34:21'),(5,'mutton briyani','Aromatic rice with tender chicken pieces',300.00,'lunch','12:00-16:00',NULL,10,1,'2025-09-14 06:34:21','2025-09-15 06:23:50'),(6,'Veg Thali','Complete vegetarian meal with rice, dal, vegetables',180.00,'lunch','12:00-16:00',NULL,0,1,'2025-09-14 06:34:21','2025-09-14 06:34:21'),(7,'Paneer Butter Masala','Cottage cheese in rich tomato gravy with rice',220.00,'lunch','12:00-16:00',NULL,0,1,'2025-09-14 06:34:21','2025-09-14 06:34:21'),(8,'Dal Tadka','Yellow lentils with spices and rice',140.00,'lunch','12:00-16:00',NULL,120,1,'2025-09-14 06:34:21','2025-09-25 09:09:39'),(9,'Samosa','Crispy fried pastry with spiced potato filling',30.00,'snacks','16:00-19:00',NULL,0,1,'2025-09-14 06:34:21','2025-09-14 06:34:21'),(10,'Pakora','Deep fried vegetable fritters',40.00,'snacks','16:00-19:00',NULL,0,1,'2025-09-14 06:34:21','2025-09-14 06:34:21'),(11,'Sandwich','Grilled vegetable sandwich',80.00,'snacks','16:00-19:00',NULL,0,1,'2025-09-14 06:34:21','2025-09-14 06:34:21'),(12,'Chaat','Spicy street food snack',50.00,'snacks','16:00-19:00',NULL,100,1,'2025-09-14 06:34:21','2025-10-23 04:14:04'),(13,'Masala Tea','Hot Indian spiced tea',20.00,'beverages','06:00-22:00',NULL,0,1,'2025-09-14 06:34:21','2025-09-14 06:34:21'),(14,'Coffee','Fresh brewed coffee',25.00,'beverages','06:00-22:00',NULL,100,1,'2025-09-14 06:34:21','2025-11-08 08:37:53'),(15,'Fresh Juice','Seasonal fruit juice',50.00,'beverages','06:00-22:00',NULL,30,1,'2025-09-14 06:34:21','2025-09-14 09:04:39'),(16,'Lassi','Yogurt-based drink',40.00,'beverages','06:00-22:00',NULL,100,1,'2025-09-14 06:34:21','2025-09-16 03:05:21'),(17,'Cold Drink','Soft drinks and sodas',30.00,'beverages','06:00-22:00',NULL,20,1,'2025-09-14 06:34:21','2025-09-25 06:29:17'),(18,'Veg Burger','Delicious crispy veg patty burger',89.00,'lunch','10:00-18:00','uploads/veg_burger.jpg',20,1,'2025-09-14 08:26:26','2025-09-14 08:29:21'),(19,'noodles','yummy',100.00,'lunch','12:00-16:00',NULL,0,1,'2025-09-14 12:40:49','2025-09-14 12:40:49'),(20,'lassi','tasty',200.00,'beverages','12:00-16:00',NULL,25,1,'2025-09-14 12:44:01','2025-09-14 12:44:01');
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `morning_balance`
--

LOCK TABLES `morning_balance` WRITE;
/*!40000 ALTER TABLE `morning_balance` DISABLE KEYS */;
INSERT INTO `morning_balance` VALUES (1,'2025-09-22',1000,NULL),(2,'2025-09-22',1000,NULL),(3,'2025-09-25',100,NULL),(4,'2025-09-25',1000,NULL),(5,'2025-11-05',1000,NULL),(6,'2025-11-08',1500,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,13,1,20.00),(2,2,8,1,140.00),(3,3,8,1,140.00),(4,4,8,1,140.00),(5,5,15,1,50.00),(6,6,14,1,25.00),(7,7,5,13,300.00),(8,8,2,3,120.00),(9,9,1,1,150.00),(10,10,14,1,25.00),(11,11,14,1,25.00),(12,12,17,1,30.00),(13,13,17,1,30.00),(14,14,14,1,25.00),(15,15,5,1,300.00),(16,16,8,1,140.00),(17,17,5,1,300.00),(18,17,8,2,140.00),(19,18,3,1,80.00),(20,19,1,4,150.00),(21,20,3,1,80.00),(24,23,3,1,80.00),(25,23,17,1,30.00),(26,24,12,2,50.00),(27,25,12,1,50.00),(28,26,17,1,30.00),(29,27,5,1,300.00),(30,28,5,1,300.00),(31,29,12,1,50.00),(32,29,14,1,25.00),(33,29,17,3,30.00),(34,30,12,1,50.00),(35,30,14,2,25.00),(36,30,16,2,40.00),(37,31,12,1,50.00),(38,31,14,2,25.00),(39,31,16,2,40.00),(40,32,12,2,50.00),(41,33,12,2,50.00),(42,34,12,3,50.00),(43,35,12,1,50.00),(44,35,14,1,25.00),(45,35,15,1,50.00),(46,35,20,1,200.00),(47,36,5,1,300.00),(48,37,5,1,300.00),(49,37,8,1,140.00),(50,38,3,1,80.00),(51,38,1,1,150.00),(52,38,14,1,25.00),(53,38,17,1,30.00);
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `bill_number` (`bill_number`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,7,'BILL202509149680',20.00,NULL,'razorpay','completed','order_RHO2lmfM90nMHt','pay_RHO4IJGZL6jD60','2025-09-14 06:43:12'),(2,7,'BILL202509146132',140.00,NULL,'razorpay','completed','order_RHODYPBbPbpeGs','pay_RHODjwJHLHdymw','2025-09-14 06:53:25'),(3,7,'BILL202509145541',140.00,NULL,'razorpay','pending','order_RHOXKNwo34tbZF',NULL,'2025-09-14 07:12:08'),(4,7,'BILL202509146914',140.00,NULL,'razorpay','completed','order_RHOd1ak8mQQGxz','pay_RHOdAsK83wfsxn','2025-09-14 07:17:32'),(5,7,'BILL202509145796',50.00,NULL,'razorpay','completed','order_RHOqYnhP2z09yz','pay_RHOqigDo4iXF31','2025-09-14 07:30:21'),(6,7,'BILL202509144166',25.00,NULL,'razorpay','completed','order_RHQSiB0ukVy09z','pay_RHQSnlVMCcsbQg','2025-09-14 09:05:08'),(7,7,'BILL202509154343',3900.00,NULL,'razorpay','completed','order_RHmvRDa3rJllnE','pay_RHmva1pIgJQ0tK','2025-09-15 07:03:36'),(8,7,'BILL202509161277',360.00,NULL,'razorpay','completed','order_RI7MGG6X5nrrzN','pay_RI7MPbTCUT4BgG','2025-09-16 03:02:51'),(9,7,'BILL202509223343',150.00,NULL,'razorpay','completed','order_RKVg2ruDWIXCQu','pay_RKVgBOlOA5efhw','2025-09-22 04:08:13'),(10,7,'BILL202509225551',25.00,NULL,'razorpay','completed','order_RKVtVDPKcXcbdC','pay_RKVtaOeN0RXkuV','2025-09-22 04:20:59'),(11,7,'BILL202509251902',25.00,NULL,'wallet','completed',NULL,NULL,'2025-09-25 05:46:48'),(12,7,'BILL202510017920',30.00,NULL,'wallet','completed',NULL,NULL,'2025-10-01 14:35:29'),(13,13,'BILL202510052144',30.00,NULL,'razorpay','completed','order_RPr75XIRFwMYwB','pay_RPr7BfuB2cWsHr','2025-10-05 16:21:27'),(14,13,'BILL202510059773',25.00,NULL,'wallet','completed',NULL,NULL,'2025-10-05 16:23:15'),(15,7,'BILL202510062314',300.00,NULL,'razorpay','completed','order_RQ7aRq5H2KOagg','pay_RQ7agG3rdiv2aX','2025-10-06 08:28:17'),(16,7,'BILL202510063824',140.00,NULL,'wallet','completed',NULL,NULL,'2025-10-06 08:32:52'),(17,7,'BILL202510069173',580.00,NULL,'wallet','completed',NULL,NULL,'2025-10-06 08:34:36'),(18,7,'BILL202510236968',80.00,NULL,'razorpay','completed','order_RWmIsoDbRxJAEt','pay_RWmJ0mTboE7Cca','2025-10-23 04:12:06'),(19,7,'BILL202510242916',600.00,NULL,'razorpay','completed','order_RXBm2Z1KXiFALE','pay_RXBmAmfpjnnQrD','2025-10-24 05:07:03'),(20,7,'BILL202510244778',80.00,NULL,'wallet','completed',NULL,NULL,'2025-10-24 05:08:31'),(23,7,'BILL202510287805',110.00,NULL,'wallet','completed',NULL,NULL,'2025-10-28 05:35:58'),(24,7,'BILL202511053260',100.00,NULL,'razorpay','completed','order_Rc2G5dYsk0kGIP','pay_Rc2GCr18QVB24P','2025-11-05 11:03:35'),(25,7,'BILL202511051132',50.00,NULL,'razorpay','completed','order_Rc2NmlUIq3A7Mn','pay_Rc2NsCmQzDVsBc','2025-11-05 11:10:53'),(26,19,'BILL202511075078',30.00,NULL,'wallet','completed',NULL,NULL,'2025-11-07 06:22:54'),(27,19,'BILL202511076846',300.00,NULL,'razorpay','pending','order_RcnsuuIVa0b4v7',NULL,'2025-11-07 09:38:56'),(28,19,'BILL202511075174',300.00,'{\"mutton briyani\": 1}','razorpay','completed','order_RcoiHDRVKad5AH','pay_RcoiSl9SUBeMQR','2025-11-07 10:27:34'),(29,19,'BILL202511075321',165.00,'{\"Chaat\": 1, \"Coffee\": 1, \"Cold Drink\": 3}','razorpay','completed','order_RcowQIKh8qFkd7','pay_RcowWP90YIk8OQ','2025-11-07 10:40:58'),(30,19,'BILL202511071275',180.00,'{\"Chaat\": {\"rate\": \"50.00\", \"total\": 50, \"quantity\": 1}, \"Lassi\": {\"rate\": \"40.00\", \"total\": 80, \"quantity\": 2}, \"Coffee\": {\"rate\": \"25.00\", \"total\": 50, \"quantity\": 2}}','razorpay','pending','order_Rcp2ScyVz2h5YX',NULL,'2025-11-07 10:46:41'),(31,19,'BILL202511072539',180.00,'{\"Chaat\": {\"rate\": \"50.00\", \"total\": 50, \"quantity\": 1}, \"Lassi\": {\"rate\": \"40.00\", \"total\": 80, \"quantity\": 2}, \"Coffee\": {\"rate\": \"25.00\", \"total\": 50, \"quantity\": 2}}','razorpay','pending','order_Rcp2ipfePN5k84',NULL,'2025-11-07 10:46:56'),(32,19,'BILL202511078119',100.00,'{\"Chaat\": {\"rate\": \"50.00\", \"total\": 100, \"quantity\": 2}}','razorpay','pending','order_Rcp3IxYdY7J52u',NULL,'2025-11-07 10:47:29'),(33,19,'BILL202511074857',100.00,'{\"Chaat\": 2}','razorpay','pending','order_Rcp3ZlcwwXtwVs',NULL,'2025-11-07 10:47:45'),(34,19,'BILL202511074433',150.00,'{\"Chaat\": {\"rate\": \"50.00\", \"total\": 150, \"quantity\": 3}}','razorpay','completed','order_Rcp7V8wUqQKXyF','pay_Rcp7Zl4dnDv2PU','2025-11-07 10:51:28'),(35,19,'BILL202511078724',325.00,'{\"Chaat\": {\"rate\": \"50.00\", \"total\": 50, \"quantity\": 1}, \"lassi\": {\"rate\": \"200.00\", \"total\": 200, \"quantity\": 1}, \"Coffee\": {\"rate\": \"25.00\", \"total\": 25, \"quantity\": 1}, \"Fresh Juice\": {\"rate\": \"50.00\", \"total\": 50, \"quantity\": 1}}','razorpay','completed','order_Rcp8uDLF0pHxJh','pay_Rcp8zxe8Tq7y1B','2025-11-07 10:52:47'),(36,19,'BILL202511088142',300.00,'{\"mutton briyani\": {\"rate\": \"300.00\", \"total\": 300, \"quantity\": 1}}','razorpay','completed','order_RdB4votmHwLtDW','pay_RdB5BnoilD3XSE','2025-11-08 08:20:15'),(37,19,'BILL202511081113',440.00,'{\"Dal Tadka\": {\"rate\": \"140.00\", \"total\": 140, \"quantity\": 1}, \"mutton briyani\": {\"rate\": \"300.00\", \"total\": 300, \"quantity\": 1}}','razorpay','completed','order_RdBJk3ngP3C1E6','pay_RdBJs5qqluvdgv','2025-11-08 08:34:18'),(38,19,'BILL202511106765',285.00,'{\"Coffee\": {\"rate\": \"25.00\", \"total\": 25, \"quantity\": 1}, \"Cold Drink\": {\"rate\": \"30.00\", \"total\": 30, \"quantity\": 1}, \"Idli Sambar\": {\"rate\": \"80.00\", \"total\": 80, \"quantity\": 1}, \"Breakfast Combo\": {\"rate\": \"150.00\", \"total\": 150, \"quantity\": 1}}','razorpay','completed','order_Rdtj5BlDsEttTV','pay_RdtjBKJjG23bND','2025-11-10 04:00:48');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `roll_no` (`roll_no`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'1','admin@foodsystem.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin','0.00','2025-09-14 06:34:21','2025-09-14 06:34:21'),(2,'2','cashier@foodsystem.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','cashier','0.00','2025-09-14 06:34:21','2025-09-14 06:34:21'),(3,'3','user@foodsystem.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user','500.00','2025-09-14 06:34:21','2025-09-14 06:34:21'),(4,'4','john@admin.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin','0.00','2025-09-14 06:34:21','2025-09-14 06:34:21'),(5,'5','mary@cashier.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','cashier','0.00','2025-09-14 06:34:21','2025-09-14 06:34:21'),(6,'6','bob@user.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user','1000.00','2025-09-14 06:34:21','2025-09-14 06:34:21'),(7,'75522104','22z114@psgitech.ac.in','$2y$10$JV0whWYCmGNGq2VLOtcx/.jHKWaZAPQ833KlQyETe.KzZYM1rGWl.','user','770.00','2025-09-14 06:38:54','2025-10-28 05:35:58'),(10,'75522105','admin1@psgitech.ac.in','$2y$10$w3O8s/opCK.xAh4F58lt/u/4kF3PjNG7FZZ0e5heFGONOD8Wka5tK','admin','0.00','2025-09-14 11:22:46','2025-09-14 11:22:46'),(11,'75522106','cashier1@psgitech.ac.in','$2y$10$w3O8s/opCK.xAh4F58lt/u/4kF3PjNG7FZZ0e5heFGONOD8Wka5tK','cashier','0.00','2025-09-14 11:27:42','2025-09-14 11:27:42'),(12,'75522107','22z118@psgitech.ac.in','$2y$10$TvPhhsegzDzV109vdZlBwOG7o7RewO28xB5yNMJhXJczk35xj5EsK','user','0.00','2025-09-19 03:53:13','2025-09-19 03:53:13'),(13,'I0339','staff1@psgitech.ac.in','$2y$10$A36f4NjtXYXR/xNeyYaLmeYPw/l9slRav78kVrPZurhJZu4Bj6PZG','staff','75.00','2025-10-05 14:44:22','2025-10-05 16:23:15'),(14,'I0184','staff2@psgitech.ac.in','$2y$10$AdTzEu7cOHdFR.6SadiWFOr09/.NLJG7kjGWUpUBShix47BsqoJ6i','staff','0.00','2025-10-05 14:49:52','2025-10-05 14:49:52'),(15,'715522104003','22z161@psgitech.ac.in','$2y$10$UMEVUKfRK6x4XOm1MVaZJu77W7n1WroziVkg0ylt17XIJUdB8f00m','user','0.00','2025-10-05 14:53:45','2025-10-05 14:53:45'),(16,'7155221040048','22z148@psgitech.ac.in','$2y$10$KNMhcC6XjFoMyNzYO.1iYuBrjjF/AbUTLclp8aVJjEUNIt.MorwaO','user','0.00','2025-10-05 18:29:20','2025-10-05 18:29:20'),(17,'715522104046','22z146@psgitech.ac.in','$2y$10$/6Vw/2VA2vFqXUdML5LHruEOx4xvkxou7e6OPr9w9Br3kl/jB6oua','user','0.00','2025-10-25 15:22:27','2025-10-25 15:22:27'),(18,'715522104001','22z101@psgitech.ac.in','$2y$10$H.zAIi45IDM4huiP3JPspO0VXeGkqsl51fyC8jVfXQSHesfcYnJqi','user','0.00','2025-11-04 05:50:09','2025-11-04 05:50:09'),(19,'715522104002','22z102@psgitech.ac.in','$2y$10$iD6KbkfZoYpLz4BcD8SZXO9KGs4h1BhX2VdQHLqP39iHbAcEYT8t6','user','dXdrTW1aNllybE1kZC82c1ZCanQ4UT09OjrR0DrCkRnLCzZVoLguW4Nl','2025-11-04 06:07:50','2025-11-08 08:34:01'),(20,'715522104004','22z104@psgitech.ac.in','$2y$10$ENBsaDIrFr8itf1U4Z/Ug.pmdApfxB5QtfDF1KXzk7unLmVvssMZ.','user','cmV2ams3SzhSR1FSck1vS0VSNS9ydz09OjpF6Ff/BUivGTZ+S+U4MGlN','2025-11-06 19:06:55','2025-11-06 19:06:55'),(21,'715522104019','22z119@psgitech.ac.in','$2y$10$9mwLrBoKYrezsJ2Yd2g7uOZEfiKCrF/qiAESSjc1ajXV8HbKfqdqG','user','bFdJQlVSVVFvWm5LNjNFLzdmRkk5UT09Ojq3kn9ip4FpFWlBkyJXc2xg','2025-11-08 08:19:31','2025-11-08 08:19:31'),(22,'715522104020','22z120@psgitech.ac.in','$2y$10$.9jtJLJPT5tSJmWkz4BAgO8ceVGXkmH/PQm2ETdAy2HXQf2i4yZ7y','user','U1VyWWtzNVZCZUVjQzd5M0FBRWpnZz09OjqZR6jo9ReWd5K14QFDzNIs','2025-11-08 08:24:50','2025-11-08 08:24:50');
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_transactions`
--

LOCK TABLES `wallet_transactions` WRITE;
/*!40000 ALTER TABLE `wallet_transactions` DISABLE KEYS */;
INSERT INTO `wallet_transactions` VALUES (1,7,'credit',100.00,'Wallet top-up',NULL,'2025-09-14 06:46:26'),(2,7,'credit',100.00,'Wallet top-up',NULL,'2025-09-22 16:49:09'),(3,7,'credit',100.00,'Wallet top-up via Razorpay',NULL,'2025-09-22 16:49:47'),(4,7,'credit',500.00,'Wallet top-up via Razorpay',NULL,'2025-09-22 16:50:27'),(5,7,'debit',25.00,'Order payment',11,'2025-09-25 05:46:48'),(6,7,'credit',100.00,'Wallet top-up',NULL,'2025-09-25 05:47:08'),(7,7,'credit',100.00,'Wallet top-up via Razorpay',NULL,'2025-09-25 05:47:49'),(8,7,'credit',100.00,'Wallet top-up via Razorpay',NULL,'2025-09-25 06:08:17'),(9,7,'credit',25.00,'Wallet top-up via Razorpay',NULL,'2025-09-25 06:14:27'),(10,7,'credit',10.00,'Wallet top-up ',NULL,'2025-09-25 06:25:32'),(11,7,'debit',30.00,'Order payment',12,'2025-10-01 14:35:29'),(12,7,'credit',100.00,'Wallet top-up ',NULL,'2025-10-01 14:36:11'),(13,13,'credit',100.00,'Wallet top-up ',NULL,'2025-10-05 16:22:33'),(14,13,'debit',25.00,'Order payment',14,'2025-10-05 16:23:15'),(15,7,'debit',140.00,'Order payment',16,'2025-10-06 08:32:52'),(16,7,'debit',580.00,'Order payment',17,'2025-10-06 08:34:36'),(17,7,'credit',500.00,'Wallet top-up ',NULL,'2025-10-24 05:08:17'),(18,7,'debit',80.00,'Order payment',20,'2025-10-24 05:08:31'),(21,7,'debit',110.00,'Order payment',23,'2025-10-28 05:35:58'),(22,19,'credit',10.00,'Wallet top-up ',NULL,'2025-11-06 18:57:07'),(23,19,'credit',100.00,'Wallet top-up ',NULL,'2025-11-06 18:58:18'),(24,19,'debit',30.00,'Order payment',26,'2025-11-07 06:22:54'),(25,19,'credit',100.00,'Wallet top-up ',NULL,'2025-11-08 08:21:35'),(26,19,'credit',1000.00,'Wallet top-up ',NULL,'2025-11-08 08:34:01');
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

-- Dump completed on 2025-11-10 10:06:26
