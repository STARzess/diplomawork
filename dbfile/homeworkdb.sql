-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 02, 2026 at 07:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `homeworkdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `ajiltan`
--

CREATE TABLE `ajiltan` (
  `ajiltan_id` int(11) NOT NULL,
  `ovog` varchar(50) NOT NULL,
  `ner` varchar(50) NOT NULL,
  `huis` enum('Эр','Эм') DEFAULT NULL,
  `utas` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `nuuts_ug` varchar(255) NOT NULL,
  `erkh_id` int(11) NOT NULL,
  `tuluv` enum('Идэвхтэй','Идэвхгүй') DEFAULT 'Идэвхтэй',
  `burtgesen_ognoo` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ajiltan`
--

INSERT INTO `ajiltan` (`ajiltan_id`, `ovog`, `ner`, `huis`, `utas`, `email`, `nuuts_ug`, `erkh_id`, `tuluv`, `burtgesen_ognoo`) VALUES
(1, 'Жавхлантөгс', 'Гэрэлт-Од', 'Эр', '80217079', 'admin@school.mn', '123', 1, 'Идэвхтэй', '2026-04-01 13:43:01'),
(2, 'Ганзориг', 'Батхүү', 'Эр', '99101020', 'surgaltiinalba@school.mn', '123', 2, 'Идэвхтэй', '2026-04-01 13:43:01'),
(3, 'Болдсайхан', 'Болдбаатар', 'Эр', '95304050', 'bagsh@school.mn', '123', 3, 'Идэвхтэй', '2026-04-01 13:43:01'),
(4, 'Төмөр', 'Бат', NULL, '88010203', 'bagsh2@school.mn', '$2y$10$NwazqyMNHxCJQfHtxUeRT.pYArzasj0Mvv.cidmDCb3bY60fR0BbW', 3, 'Идэвхтэй', '2026-04-07 21:37:16'),
(5, 'Даваадаш', 'Пүрэв', 'Эр', '96809033', 'bagsh3@school.mn', '$2y$10$R/o.IZqqHKkx18j80RmWJ.I3o6aQSMQLa0G2Gk2b4jQ8yu1.AD5uy', 3, 'Идэвхтэй', '2026-04-07 21:38:01'),
(7, 'Дорж', 'Тэмүүжин', 'Эр', '60040302', 'bagsh4@school.mn', '$2y$10$NOYLVJP32lCJsABb1P/LYOMfrPe3OybqfzobRKWb9hRTdInTk8rpq', 3, 'Идэвхтэй', '2026-04-07 21:38:51');

-- --------------------------------------------------------

--
-- Table structure for table `angi`
--

CREATE TABLE `angi` (
  `angi_id` int(11) NOT NULL,
  `angi_ner` varchar(20) NOT NULL,
  `tuvshin` varchar(20) NOT NULL,
  `surgaltiin_jil` varchar(20) NOT NULL,
  `angi_udirdagch_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `angi`
--

INSERT INTO `angi` (`angi_id`, `angi_ner`, `tuvshin`, `surgaltiin_jil`, `angi_udirdagch_id`) VALUES
(1, '5Ж', 'бага', '2025-2026', 3),
(2, '8А', 'дунд', '2025-2026', 4),
(3, '11A', 'ахлах', '2025-2026', 5),
(4, '12Б', 'ахлах', '2025-2026', 7);

-- --------------------------------------------------------

--
-- Table structure for table `daalgavar_havsralt`
--

CREATE TABLE `daalgavar_havsralt` (
  `havsralt_id` int(11) NOT NULL,
  `daalgavar_id` int(11) NOT NULL,
  `file_ner` varchar(255) DEFAULT NULL,
  `file_zam` varchar(255) DEFAULT NULL,
  `video_link` varchar(500) DEFAULT NULL,
  `turul` enum('Файл','Видео','Бусад') DEFAULT 'Файл'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daalgavar_havsralt`
--

INSERT INTO `daalgavar_havsralt` (`havsralt_id`, `daalgavar_id`, `file_ner`, `file_zam`, `video_link`, `turul`) VALUES
(3, 5, '1775573900_MNHL11.png', 'uploads/1775573900_MNHL11.png', NULL, 'Файл'),
(8, 13, '1777724308___________.pdf', 'uploads/1777724308___________.pdf', 'https://www.youtube.com/watch?v=9cRHCZoUBwI', 'Видео'),
(9, 10, '1775574307_________________-________.pdf', 'uploads/1775574307_________________-________.pdf', NULL, 'Файл'),
(10, 4, '1775573857_MNHL11.png', 'uploads/1775573857_MNHL11.png', NULL, 'Файл'),
(11, 9, '1775574280_________________-________.pdf', 'uploads/1775574280_________________-________.pdf', NULL, 'Файл'),
(12, 11, '1775574356_________________-________.pdf', 'uploads/1775574356_________________-________.pdf', NULL, 'Файл'),
(13, 6, '1775573939_MNHL11.png', 'uploads/1775573939_MNHL11.png', NULL, 'Файл'),
(14, 16, 'havsralt_2', 'uploads/havsralt_2', NULL, 'Файл'),
(15, 17, 'havsralt_3', 'uploads/havsralt_3', NULL, 'Файл');

-- --------------------------------------------------------

--
-- Table structure for table `daalgavriin_hariu`
--

CREATE TABLE `daalgavriin_hariu` (
  `hariu_id` int(11) NOT NULL,
  `daalgavar_id` int(11) NOT NULL,
  `suragch_id` int(11) NOT NULL,
  `tekst_hariu` text DEFAULT NULL,
  `file_zam` varchar(255) DEFAULT NULL,
  `video_link` varchar(500) DEFAULT NULL,
  `ilgeesen_ognoo` datetime DEFAULT current_timestamp(),
  `tuluv` enum('Илгээгдсэн','Хугацаа хоцорсон','Шалгагдсан') DEFAULT 'Илгээгдсэн'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daalgavriin_hariu`
--

INSERT INTO `daalgavriin_hariu` (`hariu_id`, `daalgavar_id`, `suragch_id`, `tekst_hariu`, `file_zam`, `video_link`, `ilgeesen_ognoo`, `tuluv`) VALUES
(2, 9, 22, 'Хийж гүйцэтгэсэн 11A ангийн сурагч М. Оюунцэцэг', 'uploads/1775574522_________________-________.pdf', '', '2026-04-07 23:08:42', 'Шалгагдсан'),
(3, 4, 22, 'Хийж гүйцэтгэсэн 11А ангийн сурагч М. Оюунцэцэг', 'uploads/1775574596_________________-________.pdf', '', '2026-04-07 23:09:56', 'Шалгагдсан'),
(10, 13, 12, 'aaaaaaaaaaaaaaaaaaaaa', 'uploads/1777724430_hcibiydaalt.pdf', '', '2026-05-02 20:20:30', 'Шалгагдсан'),
(11, 14, 12, 'example1111', '', '', '2026-05-02 23:36:17', 'Шалгагдсан'),
(12, 13, 3, 'examplee inputtt 123456789', '', '', '2026-05-05 16:55:13', 'Шалгагдсан'),
(13, 13, 4, 'example', 'uploads/havsralt', '', '2026-05-05 19:35:59', 'Шалгагдсан'),
(14, 14, 4, 'example', '', '', '2026-05-05 20:34:06', 'Шалгагдсан');

-- --------------------------------------------------------

--
-- Table structure for table `erkh`
--

CREATE TABLE `erkh` (
  `erkh_id` int(11) NOT NULL,
  `erkh_ner` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `erkh`
--

INSERT INTO `erkh` (`erkh_id`, `erkh_ner`) VALUES
(1, 'Админ'),
(3, 'Багш'),
(2, 'Сургалтын алба');

-- --------------------------------------------------------

--
-- Table structure for table `geriin_daalgavar`
--

CREATE TABLE `geriin_daalgavar` (
  `daalgavar_id` int(11) NOT NULL,
  `hicheel_bagsh_id` int(11) NOT NULL,
  `garchig` varchar(200) NOT NULL,
  `tailbar` text DEFAULT NULL,
  `daalgavar_turul` enum('Энгийн') DEFAULT 'Энгийн',
  `hariu_turul` enum('Текст','Файл','Видео','Холимог') DEFAULT 'Холимог',
  `ogson_ognoo` datetime DEFAULT current_timestamp(),
  `duusah_ognoo` datetime NOT NULL,
  `hamgiin_ih_onoo` decimal(5,2) DEFAULT 100.00,
  `tuluv` enum('Идэвхтэй','Хаагдсан','Хугацаа дууссан') DEFAULT 'Идэвхтэй'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `geriin_daalgavar`
--

INSERT INTO `geriin_daalgavar` (`daalgavar_id`, `hicheel_bagsh_id`, `garchig`, `tailbar`, `daalgavar_turul`, `hariu_turul`, `ogson_ognoo`, `duusah_ognoo`, `hamgiin_ih_onoo`, `tuluv`) VALUES
(4, 3, 'Гэрийн даалгавар #1', 'Хавсаргасан файлыг татаж аваад word дээр хийж гүйцэтгээд буцаад явуулна. (pdf)\r\n\r\nИлгээсэн даалгавар 1-100 хүртэлх оноогоор үнэлэгдэх болхыг анхаарна уу!', 'Энгийн', 'Холимог', '2026-04-07 22:57:00', '2026-06-01 23:59:00', 100.00, 'Хугацаа дууссан'),
(5, 16, 'Гэрийн даалгавар #1', 'Хавсаргасан файлыг татаж аваад word дээр хийж гүйцэтгээд буцаад явуулна. (pdf)\r\n\r\nИлгээсэн даалгавар 1-100 хүртэлх оноогоор үнэлэгдэх болхыг анхаарна уу!', 'Энгийн', 'Холимог', '2026-04-07 22:58:20', '2026-04-10 23:59:00', 100.00, 'Хаагдсан'),
(6, 7, 'Гэрийн даалгавар #1', 'Хавсаргасан файлыг татаж аваад word дээр хийж гүйцэтгээд буцаад явуулна. (pdf)\r\n\r\nИлгээсэн даалгавар 1-100 хүртэлх оноогоор үнэлэгдэх болхыг анхаарна уу!', 'Энгийн', 'Холимог', '2026-06-01 22:58:00', '2026-04-10 23:59:00', 100.00, 'Хугацаа дууссан'),
(9, 10, 'Гэрийн даалгавар #1', 'Хавсаргасан файлыг татаж аваад word дээр хийж гүйцэтгээд буцаад явуулна. (pdf)\r\n\r\nИлгээсэн даалгавар 1-100 хүртэлх оноогоор үнэлэгдэх болхыг анхаарна уу!', 'Энгийн', 'Холимог', '2026-04-07 23:04:00', '2026-06-01 23:59:00', 100.00, 'Хугацаа дууссан'),
(10, 15, 'Гэрийн даалгавар #1', 'Хавсаргасан файлыг татаж аваад word дээр хийж гүйцэтгээд буцаад явуулна. (pdf)\r\n\r\nИлгээсэн даалгавар 1-100 хүртэлх оноогоор үнэлэгдэх болхыг анхаарна уу!', 'Энгийн', 'Холимог', '2026-04-07 23:05:00', '2026-06-01 23:59:00', 100.00, 'Хугацаа дууссан'),
(11, 11, 'Гэрийн даалгавар #1', 'Хавсаргасан файлыг татаж аваад word дээр хийж гүйцэтгээд буцаад явуулна. (pdf)\r\n\r\nИлгээсэн даалгавар 1-100 хүртэлх оноогоор үнэлэгдэх болхыг анхаарна уу!', 'Энгийн', 'Холимог', '2026-04-07 23:05:00', '2026-06-01 23:59:00', 100.00, 'Хугацаа дууссан'),
(13, 16, 'Жишээ даалгавар #2', 'LOREM IPSUM', 'Энгийн', 'Холимог', '2026-05-02 20:18:28', '2026-05-22 23:59:00', 100.00, 'Хугацаа дууссан'),
(14, 16, 'МОНГОЛ ХЭЛ ЖИШЭЭ ЭНГИЙН ДААЛГАВАР #1', 'МОНГОЛ ХЭЛ ЖИШЭЭ ЭНГИЙН ДААЛГАВАР #1', 'Энгийн', 'Холимог', '2026-05-02 22:45:00', '2026-05-31 22:45:00', 100.00, 'Хугацаа дууссан'),
(15, 3, 'ЖИШЭЭ даалгавар #1', 'ЖИШЭЭЖИШЭЭЖИШЭЭ1234', 'Энгийн', 'Холимог', '2026-05-05 17:18:00', '2026-06-01 17:18:00', 100.00, 'Хугацаа дууссан'),
(16, 16, 'Гэрийн даалгавар #2', 'Гэрийн даалгавар #2', 'Энгийн', 'Холимог', '2026-06-02 14:59:00', '2026-06-06 14:59:00', 100.00, 'Идэвхтэй'),
(17, 16, 'Гэрийн даалгавар #3', 'Гэрийн даалгавар #3', 'Энгийн', 'Холимог', '2026-06-02 16:17:00', '2026-06-04 16:17:00', 100.00, 'Идэвхтэй'),
(18, 16, 'Гэрийн даалгавар #4', 'Гэрийн даалгавар #4', 'Энгийн', 'Холимог', '2026-06-02 16:18:00', '2026-06-04 16:18:00', 100.00, 'Идэвхтэй');

-- --------------------------------------------------------

--
-- Table structure for table `hicheel`
--

CREATE TABLE `hicheel` (
  `hicheel_id` int(11) NOT NULL,
  `hicheel_kod` varchar(20) DEFAULT NULL,
  `hicheel_ner` varchar(100) NOT NULL,
  `tailbar` text DEFAULT NULL,
  `tuluv` enum('Идэвхтэй','Идэвхгүй') DEFAULT 'Идэвхтэй',
  `uusgesen_ognoo` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hicheel`
--

INSERT INTO `hicheel` (`hicheel_id`, `hicheel_kod`, `hicheel_ner`, `tailbar`, `tuluv`, `uusgesen_ognoo`) VALUES
(1, 'HIST08', 'Түүх 8-р анги', 'Түүхийн хичээл', 'Идэвхтэй', '2026-04-01 13:43:01'),
(2, 'HIST11', 'Түүх 11-р анги', 'Түүхийн хичээл', 'Идэвхтэй', '2026-04-07 21:46:44'),
(3, 'MATH11', 'Математик 11-р анги', 'Математикийн хичээл', 'Идэвхтэй', '2026-04-07 21:47:45'),
(4, 'SOCI11', 'Нийгэм 11-р анги', 'Нийгмийн хичээл', 'Идэвхтэй', '2026-04-07 21:48:43'),
(5, 'CHEM11', 'Хими 11-р анги', 'Химийн хичээл', 'Идэвхтэй', '2026-04-07 21:49:32'),
(6, 'MNHL11', 'Монгол хэл 11-р анги', 'Монгол хэлийн хичээл', 'Идэвхтэй', '2026-04-07 21:50:39'),
(7, 'LITE11', 'Уран зохиол 11-р анги', 'Уран зохиолын хичээл', 'Идэвхтэй', '2026-04-07 21:51:10'),
(8, 'LITE8', 'Уран зохиол 8-р анги', 'Уран зохиолын хичээл', 'Идэвхтэй', '2026-04-07 21:52:08'),
(9, 'MNHL08', 'Монгол хэл 8-р анги', 'Монгол хэлийн хичээл', 'Идэвхтэй', '2026-04-07 21:53:00'),
(10, 'CHEM08', 'Хими 8-р анги', 'Химийн хичээл', 'Идэвхтэй', '2026-04-07 21:53:31'),
(11, 'SOCI08', 'Нийгэм 8-р анги', 'Нийгмийн хичээл', 'Идэвхтэй', '2026-04-07 21:54:12'),
(12, 'MATH08', 'Математик 8-р анги', 'Математикийн хичээл', 'Идэвхтэй', '2026-04-07 21:54:47'),
(13, 'HIST12', 'Түүх 12-р анги', 'Түүхийн хичээл', 'Идэвхтэй', '2026-04-07 22:47:10'),
(14, 'MATH12', 'Математик 12-р анги', 'Математикийн хичээл', 'Идэвхтэй', '2026-04-07 22:47:33'),
(15, 'SOCI12', 'Нийгэм 12-р анги', 'Нийгмийн хичээл', 'Идэвхтэй', '2026-04-07 22:47:53'),
(16, 'CHEM12', 'Хими 12-р анги', 'Химийн хичээл', 'Идэвхтэй', '2026-04-07 22:48:17'),
(17, 'LITE12', 'Уран зохиол 12-р анги', 'Уран зохиолын хичээл', 'Идэвхтэй', '2026-04-07 22:48:41'),
(18, 'MNHL12', 'Монгол хэл 12-р анги', 'Монгол хэлийн хичээл', 'Идэвхтэй', '2026-04-07 22:50:22');

-- --------------------------------------------------------

--
-- Table structure for table `hicheel_bagsh`
--

CREATE TABLE `hicheel_bagsh` (
  `hicheel_bagsh_id` int(11) NOT NULL,
  `hicheel_id` int(11) NOT NULL,
  `bagsh_id` int(11) NOT NULL,
  `angi_id` int(11) NOT NULL,
  `surgaltiin_jil` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hicheel_bagsh`
--

INSERT INTO `hicheel_bagsh` (`hicheel_bagsh_id`, `hicheel_id`, `bagsh_id`, `angi_id`, `surgaltiin_jil`) VALUES
(11, 1, 3, 2, '2025-2026'),
(10, 2, 3, 3, '2025-2026'),
(2, 3, 5, 3, '2025-2026'),
(4, 4, 7, 3, '2025-2026'),
(12, 5, 5, 3, '2025-2026'),
(3, 6, 3, 3, '2025-2026'),
(5, 7, 4, 3, '2025-2026'),
(9, 8, 4, 2, '2025-2026'),
(7, 9, 3, 2, '2025-2026'),
(13, 10, 5, 2, '2025-2026'),
(8, 11, 7, 2, '2025-2026'),
(6, 12, 5, 2, '2025-2026'),
(15, 13, 3, 4, '2025-2026'),
(19, 14, 5, 4, '2025-2026'),
(18, 15, 7, 4, '2025-2026'),
(14, 16, 5, 4, '2025-2026'),
(17, 17, 4, 4, '2025-2026'),
(16, 18, 3, 4, '2025-2026');

-- --------------------------------------------------------

--
-- Table structure for table `hicheel_suragch`
--

CREATE TABLE `hicheel_suragch` (
  `hicheel_suragch_id` int(11) NOT NULL,
  `hicheel_bagsh_id` int(11) NOT NULL,
  `suragch_id` int(11) NOT NULL,
  `burtgesen_ognoo` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suragch`
--

CREATE TABLE `suragch` (
  `suragch_id` int(11) NOT NULL,
  `ovog` varchar(50) NOT NULL,
  `ner` varchar(50) NOT NULL,
  `huis` enum('Эр','Эм') DEFAULT NULL,
  `torson_ognoo` date DEFAULT NULL,
  `register_dugaar` varchar(20) DEFAULT NULL,
  `utas` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `nuuts_ug` varchar(255) NOT NULL,
  `angi_id` int(11) NOT NULL,
  `tuluv` enum('Идэвхтэй','Идэвхгүй','Төгссөн','Шилжсэн') DEFAULT 'Идэвхтэй',
  `burtgesen_ognoo` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suragch`
--

INSERT INTO `suragch` (`suragch_id`, `ovog`, `ner`, `huis`, `torson_ognoo`, `register_dugaar`, `utas`, `email`, `nuuts_ug`, `angi_id`, `tuluv`, `burtgesen_ognoo`) VALUES
(3, 'Бат', 'Намуун', 'Эм', '2009-08-28', 'УК09101010', '80809494', 'suragch@school.mn', '$2y$10$uAZsVG9zVcoNEMJRyyX3SeN6kxtFGWqFw7jetoj/050PK/ESs/TP6', 4, 'Идэвхтэй', '2026-04-07 22:02:20'),
(4, 'Эрдэнэ', 'Сарнай', 'Эм', '2009-05-04', 'УК09101011', '99192030', 'suragch2@school.mn', '$2y$10$GCV25HPIwpjF7HxXRQoiXunXGedXdfzjAbTECa3pfiUCH0/HpfCt6', 4, 'Идэвхтэй', '2026-04-07 22:02:53'),
(5, 'Болд', 'Баатархүү', 'Эр', '2009-09-28', 'УК09101012', '60405033', 'suragch3@school.mn', '$2y$10$42v5WuP9.XQXGaKlxngxTu7gw3VlvtX4Vy.nuI/HKKz33v06eEel.', 4, 'Идэвхтэй', '2026-04-07 22:03:12'),
(6, 'Тэмүүжин', 'Болд', 'Эр', '2009-09-30', 'УК09101013', '60990203', 'suragch4@school.mn', '$2y$10$y1WPeEhjZv57tMB/fBXlDuVXCNHCDwykixYmctRtJNsrGUbsfc8IG', 4, 'Идэвхтэй', '2026-04-07 22:03:39'),
(7, 'Болд', 'Төмөр', 'Эр', '2009-09-03', 'УК09101014', '88109698', 'suragch5@school.mn', '$2y$10$T4HgHbGdEhqhQlypXF5N7.FlJFZJnHP8a1WFcLYFbbJK02v6urxV6', 4, 'Идэвхтэй', '2026-04-07 22:04:15'),
(8, 'Энхтөгс', 'Баяр', 'Эр', '2009-12-01', 'УК09101015', '60223310', 'suragch6@school.mn', '$2y$10$QC2jWU2U4Am4jkjxfKF02eJJczhdT4o5/z5o70R7MGhu.s9f/94a6', 4, 'Идэвхтэй', '2026-04-07 22:05:22'),
(9, 'Гэрэлт-Од', 'Агар', 'Эр', '2009-05-29', 'УК09101016', '80210101', 'suragch7@school.mn', '$2y$10$nidJU8ZpS5mlPyYV9JCg2eYgnb8xHcCpy9vBGULG7vftZkE616k4K', 4, 'Идэвхтэй', '2026-04-07 22:06:05'),
(10, 'Баасанхүү', 'Амаржаргал', 'Эр', '2009-10-30', 'УК09101017', '99890909', 'suragch8@school.mn', '$2y$10$xA6e0a4UwrLY3gh/VVKc0.mGpJe4UIcuaKKctqZBswwobJrVTmnge', 4, 'Идэвхтэй', '2026-04-07 22:06:59'),
(11, 'Энхбат', 'Номинчулуу', 'Эм', '2009-11-26', 'УК09101018', '96270101', 'suragch9@school.mn', '$2y$10$VwS6ehEYMMhaseecoB4fiuY5zhoJZgeHglByGO9R32/RCD/MxsnhK', 4, 'Идэвхтэй', '2026-04-07 22:07:51'),
(12, 'Мөнхтогтох', 'Ангараг', 'Эр', '2009-07-09', 'УК09101019', '95620000', 'suragch10@school.mn', '$2y$10$MUvynaGDBJRX9oE1wOr42Ods64bC5dPBnwdn.EAgTzzwBq4kXSgiS', 4, 'Идэвхтэй', '2026-04-07 22:08:41'),
(13, 'Бат-Эрдэнэ', 'Манлайбаатар', 'Эр', '2010-06-09', 'УК10101010', '60023040', 'suragch11@school.mn', '$2y$10$Pa3FAmzbz14omvHAm4wp0.nm0DNnQTvnTyG.2910HToULsCTj4pWq', 3, 'Идэвхтэй', '2026-04-07 22:10:21'),
(14, 'Билгүүн', 'Номин-Эрдэнэ', 'Эм', '2010-10-29', 'УК10101011', '96789090', 'suragch12@school.mn', '$2y$10$em.BoMTQxe/WA8qFx8oHvubIRchhLlNmmArH8AHQKYLCOxC7t/GKe', 3, 'Идэвхтэй', '2026-04-07 22:11:10'),
(15, 'Ананд', 'Лхагвасүрэн', 'Эр', '2010-02-06', 'УК10101012', '99939494', 'suragch13@school.mn', '$2y$10$khjKkj3PIDer54/UdpKJw.zaZu1uNCIjQlIF9NhTnaJVeK0rBGpM.', 3, 'Идэвхтэй', '2026-04-07 22:11:55'),
(16, 'Анхбаяр', 'Энэрэл', 'Эр', '2010-05-04', 'УК10101013', '99334210', 'suragch14@school.mn', '$2y$10$qgDnAuCuWZGIMSODxczogOKfsDa3eWq4mmrPDVvgr7QVwZa7Z6jhi', 3, 'Идэвхтэй', '2026-04-07 22:12:28'),
(17, 'Ангараг', 'Хонгорзул', 'Эм', '2010-08-03', 'УК10101014', '88020304', 'suragch15@school.mn', '$2y$10$0gLTwP7x6d8bU4bHnwTaC.Y2fE.e4Kia3p.N2IbaJ/figR1ArWmve', 3, 'Идэвхтэй', '2026-04-07 22:13:10'),
(18, 'Ганзориг', 'Анужин', 'Эм', '2010-02-02', 'УК10101015', '99960001', 'suragch16@school.mn', '$2y$10$hDhhcu74uWsYD8yRDGc1VePGdXyAuTAayquSKxvSCzlW4iVbPunYC', 3, 'Идэвхтэй', '2026-04-07 22:14:23'),
(19, 'Энхбаяр', 'Алтангэрэл', 'Эр', '2010-05-02', 'УК10101016', '88119040', 'suragch17@school.mn', '$2y$10$MY/6bSlgZ0E4KZJ5gVeouefNsLvHFQX4/PZxJD8vu7.4rMQt2Dk52', 3, 'Идэвхтэй', '2026-04-07 22:17:04'),
(20, 'Ганболд', 'Уянга', 'Эм', '2010-12-10', 'УК10101017', '96739080', 'suragch18@school.mn', '$2y$10$ut.fHA4UeOpUUjlFD2fdw.esogbkKLkm8OQRGEOVPBotfbi5tCXxa', 3, 'Идэвхтэй', '2026-04-07 22:23:14'),
(21, 'Батжаргал', 'Баярмаа', 'Эм', '2010-05-01', 'УК10101018', '88819090', 'suragch19@school.mn', '$2y$10$GUg/Nq7D1XMfT/IPdIR9JuhfKkOkbpEZaIIFnxlI7qlzaaiLFVm0W', 3, 'Идэвхтэй', '2026-04-07 22:23:53'),
(22, 'Мягмарсүрэн', 'Оюунцэцэг', 'Эм', '2010-01-26', 'УК10101019', '99102030', 'suragch20@school.mn', '$2y$10$mdld1ZJT3CCQJUnmZeTxnudpsSRP6vsZbTNY636xiUqx0aUrbPoBO', 3, 'Идэвхтэй', '2026-04-07 22:24:42'),
(23, 'Баттулга', 'Мишээл', 'Эм', '2013-11-03', 'УК13101010', '88001909', 'suragch21@school.mn', '$2y$10$5khV2oKx7yHtyPf3qkgEue9fF6G3tpY7fkXF3dfRBVbcmYI2O/x5y', 2, 'Идэвхтэй', '2026-04-07 22:25:47'),
(24, 'Пүрэвсүрэн', 'Энэрэл', 'Эм', '2013-01-27', 'УК13101011', '88961012', 'suragch22@school.mn', '$2y$10$FNqts5gMk7Qnf8P3ECe.ge.Gp8EGFOjFPw9cgAKSqWkGq4I4jNFBK', 2, 'Идэвхтэй', '2026-04-07 22:26:28'),
(25, 'Ганбаатар', 'Оюунболд', 'Эр', '2013-07-10', 'УК13101012', '80100302', 'suragch23@school.mn', '$2y$10$0uJcbb/bp1AKUekVhZD7V.6GXF2YbD3KfLkoQn4rZG32KDUNacM2S', 2, 'Идэвхтэй', '2026-04-07 22:27:18'),
(26, 'Отгонбаяр', 'Нандинцэцэг', 'Эм', '2013-02-22', 'УК13101013', '99960000', 'suragch24@school.mn', '$2y$10$7q.VGi49Gn.F7NRtSxpyQ.SYhBkyCQk7YkM/YuqLoKOFdQ.bB8wpy', 2, 'Идэвхтэй', '2026-04-07 22:28:03'),
(27, 'Төгөлдөр', 'Дөлгөөн', 'Эр', '2013-05-21', 'УК13101014', '99981023', 'suragch25@school.mn', '$2y$10$1A0Iot9GKAS9MTC8MfCpguvoRrnV9hnf11goj27n5aT6A7NWd3mM2', 2, 'Идэвхтэй', '2026-04-07 22:28:50'),
(28, 'Тэмүүлэн', 'Хулан', 'Эм', '2013-12-17', 'УК13101015', '60090807', 'suragch26@school.mn', '$2y$10$9yubqUpcNod0txRJOiyD/u5wVS7KiQOkZ3EblhiYrlors5X/myYB2', 2, 'Идэвхтэй', '2026-04-07 22:29:30'),
(29, 'Болд-Эрдэнэ', 'Энхтуяа', 'Эм', '2013-04-26', 'УК13101016', '60092030', 'suragch27@school.mn', '$2y$10$XErgFMPOxoJaya3USlPZ1uLu3BgF3kKWTsGNzGAHlN1ghXN5pHQUu', 2, 'Идэвхтэй', '2026-04-07 22:30:08'),
(30, 'Эрдэнэбаатар', 'Саруултөгс', 'Эр', '2013-09-29', 'УК13101017', '88192039', 'suragch28@school.mn', '$2y$10$wAm3aV2lzSimSQwCrU/O2.9SGtZF5V0CMBzSvQN/8Pv37Mahol5eG', 2, 'Идэвхтэй', '2026-04-07 22:31:08'),
(31, 'Мягмаржав', 'Соронзонболд', 'Эр', '2013-03-07', 'УК13101018', '80100304', 'suragch29@school.mn', '$2y$10$kfDXIOGaFtqJciAkckkrPOI.XW1w7w0Yks6./WO4/ocU.0u6I8NWG', 2, 'Идэвхтэй', '2026-04-07 22:31:46'),
(32, 'Галтхүү', 'Бат', 'Эр', '2013-01-29', 'УК13101019', '99108893', 'suragch30@school.mn', '$2y$10$DYOH32CNxPTNr6PELTEd0eOmke5l0e27uglrSWK09/UmGuDY169YG', 2, 'Идэвхтэй', '2026-04-07 22:32:44');

-- --------------------------------------------------------

--
-- Table structure for table `unelgee`
--

CREATE TABLE `unelgee` (
  `unelgee_id` int(11) NOT NULL,
  `hariu_id` int(11) NOT NULL,
  `shalgasan_bagsh_id` int(11) NOT NULL,
  `avsan_onoo` decimal(5,2) NOT NULL,
  `bagshiin_tailbar` text DEFAULT NULL,
  `unelsen_ognoo` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `unelgee`
--

INSERT INTO `unelgee` (`unelgee_id`, `hariu_id`, `shalgasan_bagsh_id`, `avsan_onoo`, `bagshiin_tailbar`, `unelsen_ognoo`) VALUES
(4, 2, 3, 100.00, 'Бүгд зөв', '2026-04-07 23:12:36'),
(5, 3, 3, 85.00, 'Сүүлийн 2 асуултыг хийхдээ буруу тайлбарласан', '2026-04-07 23:13:09'),
(10, 10, 3, 90.00, 'example', '2026-05-02 22:15:54'),
(11, 11, 3, 100.00, 'Зөв', '2026-05-02 23:40:26'),
(12, 12, 3, 88.00, 'inputtt12345', '2026-05-05 16:57:00'),
(13, 13, 3, 95.00, 'example', '2026-05-05 19:38:00'),
(14, 14, 3, 100.00, 'example', '2026-05-05 20:36:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ajiltan`
--
ALTER TABLE `ajiltan`
  ADD PRIMARY KEY (`ajiltan_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_ajiltan_erkh` (`erkh_id`);

--
-- Indexes for table `angi`
--
ALTER TABLE `angi`
  ADD PRIMARY KEY (`angi_id`),
  ADD UNIQUE KEY `uniq_angi_ner_jil` (`angi_ner`,`surgaltiin_jil`),
  ADD KEY `fk_angi_udirdagch` (`angi_udirdagch_id`);

--
-- Indexes for table `daalgavar_havsralt`
--
ALTER TABLE `daalgavar_havsralt`
  ADD PRIMARY KEY (`havsralt_id`),
  ADD KEY `fk_daalgavar_havsralt` (`daalgavar_id`);

--
-- Indexes for table `daalgavriin_hariu`
--
ALTER TABLE `daalgavriin_hariu`
  ADD PRIMARY KEY (`hariu_id`),
  ADD UNIQUE KEY `uniq_daalgavar_suragch` (`daalgavar_id`,`suragch_id`),
  ADD KEY `fk_daalgavriin_hariu_suragch` (`suragch_id`);

--
-- Indexes for table `erkh`
--
ALTER TABLE `erkh`
  ADD PRIMARY KEY (`erkh_id`),
  ADD UNIQUE KEY `erkh_ner` (`erkh_ner`);

--
-- Indexes for table `geriin_daalgavar`
--
ALTER TABLE `geriin_daalgavar`
  ADD PRIMARY KEY (`daalgavar_id`),
  ADD KEY `fk_geriin_daalgavar_hb` (`hicheel_bagsh_id`);

--
-- Indexes for table `hicheel`
--
ALTER TABLE `hicheel`
  ADD PRIMARY KEY (`hicheel_id`),
  ADD UNIQUE KEY `hicheel_kod` (`hicheel_kod`);

--
-- Indexes for table `hicheel_bagsh`
--
ALTER TABLE `hicheel_bagsh`
  ADD PRIMARY KEY (`hicheel_bagsh_id`),
  ADD UNIQUE KEY `uniq_hicheel_bagsh` (`hicheel_id`,`bagsh_id`,`angi_id`,`surgaltiin_jil`),
  ADD KEY `fk_hicheel_bagsh_bagsh` (`bagsh_id`),
  ADD KEY `fk_hicheel_bagsh_angi` (`angi_id`);

--
-- Indexes for table `hicheel_suragch`
--
ALTER TABLE `hicheel_suragch`
  ADD PRIMARY KEY (`hicheel_suragch_id`),
  ADD UNIQUE KEY `uniq_hicheel_suragch` (`hicheel_bagsh_id`,`suragch_id`),
  ADD KEY `fk_hicheel_suragch_suragch` (`suragch_id`);

--
-- Indexes for table `suragch`
--
ALTER TABLE `suragch`
  ADD PRIMARY KEY (`suragch_id`),
  ADD UNIQUE KEY `register_dugaar` (`register_dugaar`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_suragch_angi` (`angi_id`);

--
-- Indexes for table `unelgee`
--
ALTER TABLE `unelgee`
  ADD PRIMARY KEY (`unelgee_id`),
  ADD UNIQUE KEY `hariu_id` (`hariu_id`),
  ADD KEY `fk_unelgee_bagsh` (`shalgasan_bagsh_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ajiltan`
--
ALTER TABLE `ajiltan`
  MODIFY `ajiltan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `angi`
--
ALTER TABLE `angi`
  MODIFY `angi_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `daalgavar_havsralt`
--
ALTER TABLE `daalgavar_havsralt`
  MODIFY `havsralt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `daalgavriin_hariu`
--
ALTER TABLE `daalgavriin_hariu`
  MODIFY `hariu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `erkh`
--
ALTER TABLE `erkh`
  MODIFY `erkh_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `geriin_daalgavar`
--
ALTER TABLE `geriin_daalgavar`
  MODIFY `daalgavar_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `hicheel`
--
ALTER TABLE `hicheel`
  MODIFY `hicheel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `hicheel_bagsh`
--
ALTER TABLE `hicheel_bagsh`
  MODIFY `hicheel_bagsh_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `hicheel_suragch`
--
ALTER TABLE `hicheel_suragch`
  MODIFY `hicheel_suragch_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suragch`
--
ALTER TABLE `suragch`
  MODIFY `suragch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `unelgee`
--
ALTER TABLE `unelgee`
  MODIFY `unelgee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ajiltan`
--
ALTER TABLE `ajiltan`
  ADD CONSTRAINT `fk_ajiltan_erkh` FOREIGN KEY (`erkh_id`) REFERENCES `erkh` (`erkh_id`) ON UPDATE CASCADE;

--
-- Constraints for table `angi`
--
ALTER TABLE `angi`
  ADD CONSTRAINT `fk_angi_udirdagch` FOREIGN KEY (`angi_udirdagch_id`) REFERENCES `ajiltan` (`ajiltan_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `daalgavar_havsralt`
--
ALTER TABLE `daalgavar_havsralt`
  ADD CONSTRAINT `fk_daalgavar_havsralt` FOREIGN KEY (`daalgavar_id`) REFERENCES `geriin_daalgavar` (`daalgavar_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `daalgavriin_hariu`
--
ALTER TABLE `daalgavriin_hariu`
  ADD CONSTRAINT `fk_daalgavriin_hariu_daalgavar` FOREIGN KEY (`daalgavar_id`) REFERENCES `geriin_daalgavar` (`daalgavar_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_daalgavriin_hariu_suragch` FOREIGN KEY (`suragch_id`) REFERENCES `suragch` (`suragch_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `geriin_daalgavar`
--
ALTER TABLE `geriin_daalgavar`
  ADD CONSTRAINT `fk_geriin_daalgavar_hb` FOREIGN KEY (`hicheel_bagsh_id`) REFERENCES `hicheel_bagsh` (`hicheel_bagsh_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `hicheel_bagsh`
--
ALTER TABLE `hicheel_bagsh`
  ADD CONSTRAINT `fk_hicheel_bagsh_angi` FOREIGN KEY (`angi_id`) REFERENCES `angi` (`angi_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hicheel_bagsh_bagsh` FOREIGN KEY (`bagsh_id`) REFERENCES `ajiltan` (`ajiltan_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hicheel_bagsh_hicheel` FOREIGN KEY (`hicheel_id`) REFERENCES `hicheel` (`hicheel_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `hicheel_suragch`
--
ALTER TABLE `hicheel_suragch`
  ADD CONSTRAINT `fk_hicheel_suragch_hb` FOREIGN KEY (`hicheel_bagsh_id`) REFERENCES `hicheel_bagsh` (`hicheel_bagsh_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hicheel_suragch_suragch` FOREIGN KEY (`suragch_id`) REFERENCES `suragch` (`suragch_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `suragch`
--
ALTER TABLE `suragch`
  ADD CONSTRAINT `fk_suragch_angi` FOREIGN KEY (`angi_id`) REFERENCES `angi` (`angi_id`) ON UPDATE CASCADE;

--
-- Constraints for table `unelgee`
--
ALTER TABLE `unelgee`
  ADD CONSTRAINT `fk_unelgee_bagsh` FOREIGN KEY (`shalgasan_bagsh_id`) REFERENCES `ajiltan` (`ajiltan_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_unelgee_hariu` FOREIGN KEY (`hariu_id`) REFERENCES `daalgavriin_hariu` (`hariu_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
