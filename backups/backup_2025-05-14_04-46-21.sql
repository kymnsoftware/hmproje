-- PDKS Veritabanı Yedeklemesi
-- Oluşturulma Tarihi: 2025-05-14 04:46:21
-- ------------------------------------------------------

DROP TABLE IF EXISTS `attendance_logs`;
CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `card_number` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `event_time` datetime NOT NULL,
  `event_type` enum('ENTRY','EXIT') NOT NULL,
  `device_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `attendance_logs` VALUES ("50","2587674","0","","2025-05-14 03:42:44","ENTRY","1","2025-05-14 03:42:46"),
("51","2587674","0","","2025-05-14 03:42:48","EXIT","1","2025-05-14 03:42:50"),
("52","2587674","0","","2025-05-14 03:42:50","ENTRY","1","2025-05-14 03:42:52"),
("53","2587674","1","Halil","2025-05-14 03:48:09","EXIT","1","2025-05-14 03:48:11"),
("54","4963194","2","Mustafa","2025-05-14 04:42:51","ENTRY","1","2025-05-14 04:42:52"),
("55","4963194","2","Mustafa","2025-05-14 04:44:06","EXIT","1","2025-05-14 04:44:08"),
("56","4963194","2","Mustafa","2025-05-14 04:44:12","ENTRY","1","2025-05-14 04:44:13"),
("57","4963194","2","Mustafa","2025-05-14 04:44:48","EXIT","1","2025-05-14 04:44:49"),
("58","4963194","2","Mustafa","2025-05-14 04:46:07","ENTRY","1","2025-05-14 04:46:08"),
("59","4963194","2","Mustafa","2025-05-14 04:46:36","EXIT","1","2025-05-14 04:46:37"),
("60","4963194","2","Mustafa","2025-05-14 04:47:27","ENTRY","1","2025-05-14 04:47:29"),
("61","8832330","5","Deneme","2025-05-14 04:48:24","ENTRY","1","2025-05-14 04:48:26"),
("62","2587674","1","Halil","2025-05-14 04:58:22","ENTRY","1","2025-05-14 04:58:23"),
("63","2587674","1","Halil","2025-05-14 04:58:29","EXIT","1","2025-05-14 04:58:31"),
("64","2587674","1","Halil","2025-05-14 05:00:30","ENTRY","1","2025-05-14 05:00:31"),
("65","8832330","5","Deneme","2025-05-14 05:01:47","EXIT","1","2025-05-14 05:01:48"),
("66","4963194","2","Mustafa Şaban","2025-05-14 05:01:52","EXIT","1","2025-05-14 05:01:52"),
("67","2587674","1","Halil","2025-05-14 05:02:37","EXIT","1","2025-05-14 05:02:38"),
("68","2587674","1","Halil","2025-05-14 05:06:45","ENTRY","1","2025-05-14 05:06:46"),
("69","4963194","2","Mustafa ","2025-05-14 05:06:47","ENTRY","1","2025-05-14 05:06:48"),
("70","4963194","2","Mustafa ","2025-05-14 05:42:00","EXIT","1","2025-05-14 05:42:01"),
("71","4963194","2","Mustafa ","2025-05-14 05:42:12","ENTRY","1","2025-05-14 05:42:13"),
("72","4963194","2","Mustafa ","2025-05-14 05:42:18","EXIT","1","2025-05-14 05:42:19"),
("73","2587674","1","Halil","2025-05-14 05:43:46","EXIT","1","2025-05-14 05:43:47"),
("74","8832330","3","Yusuf","2025-05-14 05:46:01","ENTRY","1","2025-05-14 05:46:02"),
("75","8832330","3","Yusuf","2025-05-14 05:46:15","EXIT","1","2025-05-14 05:46:15");

DROP TABLE IF EXISTS `card_logs`;
CREATE TABLE `card_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `card_number` varchar(50) NOT NULL,
  `scan_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=181 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `card_logs` VALUES ("133","2587674","2025-05-14 03:42:45","2025-05-14 03:42:45"),
("134","2587674","2025-05-14 03:42:49","2025-05-14 03:42:49"),
("135","2587674","2025-05-14 03:42:51","2025-05-14 03:42:51"),
("136","2587674","2025-05-14 03:43:10","2025-05-14 03:43:10"),
("137","2587674","2025-05-14 03:43:11","2025-05-14 03:43:11"),
("138","2587674","2025-05-14 03:43:18","2025-05-14 03:43:18"),
("139","2587674","2025-05-14 03:43:21","2025-05-14 03:43:21"),
("140","2587674","2025-05-14 03:45:05","2025-05-14 03:45:05"),
("141","2587674","2025-05-14 03:46:08","2025-05-14 03:46:08"),
("142","2587674","2025-05-14 03:48:10","2025-05-14 03:48:10"),
("143","4963194","2025-05-14 03:48:21","2025-05-14 03:48:21"),
("144","4963194","2025-05-14 03:48:26","2025-05-14 03:48:26"),
("145","8832330","2025-05-14 03:52:54","2025-05-14 03:52:54"),
("146","8832330","2025-05-14 03:52:57","2025-05-14 03:52:57"),
("147","8832330","2025-05-14 04:42:43","2025-05-14 04:42:43"),
("148","8832330","2025-05-14 04:42:47","2025-05-14 04:42:47"),
("149","4963194","2025-05-14 04:42:52","2025-05-14 04:42:52"),
("150","8832330","2025-05-14 04:44:05","2025-05-14 04:44:05"),
("151","4963194","2025-05-14 04:44:07","2025-05-14 04:44:07"),
("152","4963194","2025-05-14 04:44:12","2025-05-14 04:44:12"),
("153","4963194","2025-05-14 04:44:48","2025-05-14 04:44:48"),
("154","4963194","2025-05-14 04:46:08","2025-05-14 04:46:08"),
("155","4963194","2025-05-14 04:46:36","2025-05-14 04:46:36"),
("156","4963194","2025-05-14 04:47:28","2025-05-14 04:47:28"),
("157","8832330","2025-05-14 04:47:31","2025-05-14 04:47:31"),
("158","8832330","2025-05-14 04:47:33","2025-05-14 04:47:33"),
("159","8832330","2025-05-14 04:47:33","2025-05-14 04:47:33"),
("160","8832330","2025-05-14 04:47:36","2025-05-14 04:47:36"),
("161","8832330","2025-05-14 04:48:25","2025-05-14 04:48:25"),
("162","2587674","2025-05-14 04:58:22","2025-05-14 04:58:22"),
("163","2587674","2025-05-14 04:58:30","2025-05-14 04:58:30"),
("164","2587674","2025-05-14 05:00:30","2025-05-14 05:00:30"),
("165","8832330","2025-05-14 05:01:48","2025-05-14 05:01:48"),
("166","4963194","2025-05-14 05:01:52","2025-05-14 05:01:52"),
("167","2587674","2025-05-14 05:02:37","2025-05-14 05:02:37"),
("168","8832330","2025-05-14 05:06:43","2025-05-14 05:06:43"),
("169","2587674","2025-05-14 05:06:45","2025-05-14 05:06:45"),
("170","4963194","2025-05-14 05:06:47","2025-05-14 05:06:47"),
("171","8832330","2025-05-14 05:07:09","2025-05-14 05:07:09"),
("172","4963194","2025-05-14 05:42:01","2025-05-14 05:42:01"),
("173","4963194","2025-05-14 05:42:12","2025-05-14 05:42:12"),
("174","4963194","2025-05-14 05:42:18","2025-05-14 05:42:18"),
("175","2587674","2025-05-14 05:43:46","2025-05-14 05:43:46"),
("176","8832330","2025-05-14 05:43:51","2025-05-14 05:43:51"),
("177","8832330","2025-05-14 05:44:38","2025-05-14 05:44:38"),
("178","8832330","2025-05-14 05:45:41","2025-05-14 05:45:41"),
("179","8832330","2025-05-14 05:46:01","2025-05-14 05:46:01"),
("180","8832330","2025-05-14 05:46:15","2025-05-14 05:46:15");

DROP TABLE IF EXISTS `cards`;
CREATE TABLE `cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `card_number` varchar(50) NOT NULL,
  `privilege` varchar(10) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `enabled` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `synced_to_device` tinyint(4) DEFAULT 0,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cards` VALUES ("1","1","Halil",NULL,"2587674","0","","true","2025-05-14 05:05:48","1",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
("2","2","Mustafa ","Kaya","4963194","0","","true","2025-05-14 05:05:48","1","IT","IT Manager","0532592929","mustafa@gmail.com","2022-01-14","2000-11-16","istanbul","uploads/user_2_1747190484.png"),
("4","3","Yusuf","Yücel","8832330","0","","true","2025-05-14 05:45:34","1","IT","xKralTR","0558585858","sivaskangali@gmail.com","2025-05-14","1999-01-24","SİVAS","uploads/user_3_1747190734.png");

DROP TABLE IF EXISTS `commands`;
CREATE TABLE `commands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `command_type` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `commands` VALUES ("1","delete_user","2","completed","2025-05-13 03:25:41","2025-05-13 03:26:02"),
("2","delete_all",NULL,"completed","2025-05-13 03:33:48","2025-05-13 03:34:02"),
("3","delete_user","2","completed","2025-05-13 03:37:29","2025-05-13 03:37:32"),
("4","delete_all",NULL,"completed","2025-05-13 03:39:38","2025-05-13 03:40:02"),
("5","delete_user","3","completed","2025-05-14 03:42:24","2025-05-14 03:42:54"),
("6","delete_user","4","completed","2025-05-14 03:42:28","2025-05-14 03:42:54"),
("7","sync_user","1","failed","2025-05-14 03:47:35","2025-05-14 03:47:53"),
("8","sync_user","2","failed","2025-05-14 03:49:50","2025-05-14 03:49:53"),
("9","sync_user","3","failed","2025-05-14 03:53:06","2025-05-14 03:53:23"),
("10","delete_user","3","completed","2025-05-14 04:45:43","2025-05-14 04:45:54"),
("11","sync_user","5","failed","2025-05-14 04:48:04","2025-05-14 04:48:24"),
("12","sync_all",NULL,"failed","2025-05-14 04:58:50","2025-05-14 04:58:54"),
("13","sync_user","2","failed","2025-05-14 05:01:04","2025-05-14 05:01:24"),
("14","sync_user","2","failed","2025-05-14 05:01:27","2025-05-14 05:01:54"),
("15","sync_all",NULL,"failed","2025-05-14 05:01:37","2025-05-14 05:01:54"),
("16","sync_all",NULL,"failed","2025-05-14 05:03:16","2025-05-14 05:03:24"),
("17","sync_all",NULL,"failed","2025-05-14 05:04:40","2025-05-14 05:04:54"),
("18","sync_all",NULL,"failed","2025-05-14 05:05:07","2025-05-14 05:05:24"),
("19","sync_all",NULL,"failed","2025-05-14 05:05:19","2025-05-14 05:05:24"),
("20","delete_user","5","completed","2025-05-14 05:06:22","2025-05-14 05:06:24"),
("21","sync_user","2","failed","2025-05-14 05:41:24","2025-05-14 05:41:54"),
("22","sync_all",NULL,"failed","2025-05-14 05:41:28","2025-05-14 05:41:54"),
("23","sync_user","3","failed","2025-05-14 05:45:34","2025-05-14 05:45:54");

