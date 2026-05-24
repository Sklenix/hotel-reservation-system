SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET NAMES utf8mb4;

CREATE DATABASE `hotel_system`
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci;

USE `hotel_system`;

--
-- Struktura tabulky `equipment`
--

DROP TABLE IF EXISTS `equipment`;
CREATE TABLE IF NOT EXISTS `equipment` (
  `equipment_id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_name` varchar(255) NOT NULL,
  PRIMARY KEY (`equipment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

INSERT INTO `equipment` (`equipment_id`, `equipment_name`) VALUES
(1, 'Wi-Fi'),
(2, 'Televize'),
(3, 'Balkón'),
(4, 'Sprchový kout'),
(5, 'Vana'),
(6, 'Umyvadlo'),
(7, 'Toaleta'),
(8, 'Stůl'),
(9, 'Židle');

-- --------------------------------------------------------

--
-- Struktura tabulky `hotel`
--

DROP TABLE IF EXISTS `hotel`;
CREATE TABLE IF NOT EXISTS `hotel` (
  `hotel_id` int(11) NOT NULL AUTO_INCREMENT,
  `hotel_name` varchar(255) NOT NULL,
  `hotel_city` varchar(255) NOT NULL,
  `hotel_address` varchar(255) NOT NULL,
  `hotel_star_rating` tinyint(4) NOT NULL,
  `hotel_description` text NOT NULL,
  `hotel_phone` varchar(20) NOT NULL,
  `hotel_email` varchar(255) NOT NULL,
  `hotel_owner_id` int(11) NOT NULL,
  PRIMARY KEY (`hotel_id`),
  KEY `hotel_owner_id_fk` (`hotel_owner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `hotel_images`
--

DROP TABLE IF EXISTS `hotel_images`;
CREATE TABLE IF NOT EXISTS `hotel_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `image_hotel_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  PRIMARY KEY (`image_id`),
  KEY `image_hotel_id_fk` (`image_hotel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `hotel_receptionists`
--

DROP TABLE IF EXISTS `hotel_receptionists`;
CREATE TABLE IF NOT EXISTS `hotel_receptionists` (
  `hotel_receptionist_id` int(11) NOT NULL AUTO_INCREMENT,
  `hotel_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`hotel_receptionist_id`),
  KEY `hotel_receptionist_id_fk` (`hotel_id`),
  KEY `receptionist_id_fk` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `reservations`
--

DROP TABLE IF EXISTS `reservations`;
CREATE TABLE IF NOT EXISTS `reservations` (
  `reservation_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reservation_date_from` datetime NOT NULL,
  `reservation_date_to` datetime NOT NULL,
  `reservation_confirmed` tinyint(4) NOT NULL DEFAULT '0',
  `reservation_check_in` tinyint(4) NOT NULL DEFAULT '0',
  `reservation_check_out` tinyint(4) NOT NULL DEFAULT '0',
  `reservation_user_name` varchar(255) DEFAULT NULL,
  `reservation_user_surname` varchar(255) DEFAULT NULL,
  `reservation_user_phone` varchar(20) DEFAULT NULL,
  `reservation_user_email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`reservation_id`),
  KEY `user_id_fk` (`user_id`),
  KEY `room_reservation_id_fk` (`room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(255) NOT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'Customer'),
(2, 'Receptionist'),
(3, 'Owner'),
(4, 'Admin');

-- --------------------------------------------------------

--
-- Struktura tabulky `rooms`
--

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE IF NOT EXISTS `rooms` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_hotel_id` int(11) NOT NULL,
  `room_capacity` tinyint(4) NOT NULL,
  `room_price` double NOT NULL,
  `room_type` int(11) NOT NULL,
  `room_number` int(11) DEFAULT NULL,
  PRIMARY KEY (`room_id`),
  KEY `hotel_id_fk` (`room_hotel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `room_equipment`
--

DROP TABLE IF EXISTS `room_equipment`;
CREATE TABLE IF NOT EXISTS `room_equipment` (
  `room_equipment_id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  PRIMARY KEY (`room_equipment_id`),
  KEY `room_id_fk` (`room_id`),
  KEY `equipment_id_fk` (`equipment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `room_images`
--

DROP TABLE IF EXISTS `room_images`;
CREATE TABLE IF NOT EXISTS `room_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `image_room_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  PRIMARY KEY (`image_id`),
  KEY `image_room_id_fk` (`image_room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) NOT NULL,
  `user_surname` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `user_phone` varchar(20) NOT NULL,
  `user_login` varchar(255) NOT NULL,
  `user_password` varchar(255) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

INSERT INTO `users` (`user_id`, `user_name`, `user_surname`, `user_email`, `user_phone`, `user_login`, `user_password`) VALUES
(7, 'Administrátor', 'Administrátovský', 'example@example.com', '798498614', 'admin', '$2y$10$GZtLeENj7NfbSby.tV8boudc4dbZZCWg0ZnubThl1cMmuIiZyEiUm'),
(12, 'Zákazník', 'Zákazníkovič', 'example@example.com', '', 'customer', '$2y$10$XJNY0aHpx2Q0CSelrHAfHuFJCg5a6yWVQkF5ACpCbjdEuon5KM0dW'),
(13, 'Recepční', 'Recepce', 'example@example.com', '', 'receptionist', '$2y$10$BTraKjDVbwIZtMrSwjLc0eMdiIHr68mwwusc1EgCPCe9s6fhEd8fm'),
(14, 'Vlastník', 'Vlastenecký', 'example@example.com', '', 'owner', '$2y$10$ndttAU4r4mE3rebMw6n0fOaFtA9Rzh2QS5enR02dDgzGAXXLY8u2G');

-- --------------------------------------------------------

--
-- Struktura tabulky `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_role_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`user_role_id`),
  KEY `role_id_fk` (`role_id`),
  KEY `user_role_id_fk` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;

INSERT INTO `user_roles` (`user_role_id`, `user_id`, `role_id`) VALUES
(7, 7, 1),
(8, 7, 4),
(9, 7, 3),
(10, 7, 2),
(17, 12, 1),
(18, 13, 1),
(19, 13, 2),
(20, 14, 1),
(21, 14, 2),
(22, 14, 3);

--
-- Omezení pro tabulku `hotel`
--
ALTER TABLE `hotel`
  ADD CONSTRAINT `hotel_owner_id_fk` FOREIGN KEY (`hotel_owner_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Omezení pro tabulku `hotel_images`
--
ALTER TABLE `hotel_images`
  ADD CONSTRAINT `image_hotel_id_fk` FOREIGN KEY (`image_hotel_id`) REFERENCES `hotel` (`hotel_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `hotel_receptionists`
--
ALTER TABLE `hotel_receptionists`
  ADD CONSTRAINT `hotel_receptionist_id_fk` FOREIGN KEY (`hotel_id`) REFERENCES `hotel` (`hotel_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `receptionist_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `room_reservation_id_fk` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `hotel_id_fk` FOREIGN KEY (`room_hotel_id`) REFERENCES `hotel` (`hotel_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `room_equipment`
--
ALTER TABLE `room_equipment`
  ADD CONSTRAINT `equipment_id_fk` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `room_id_fk` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `room_images`
--
ALTER TABLE `room_images`
  ADD CONSTRAINT `image_room_id_fk` FOREIGN KEY (`image_room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `role_id_fk` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `user_role_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;