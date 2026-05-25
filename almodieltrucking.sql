-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 24, 2026 at 12:20 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `almodieltrucking`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `bookingID` int NOT NULL,
  `customerID` int NOT NULL,
  `pickupLocationID` int NOT NULL,
  `destinationLocationID` int NOT NULL,
  `tripID` int NOT NULL,
  `pickupDateTime` datetime NOT NULL,
  `price` double NOT NULL,
  `createdBy` int NOT NULL,
  `dateCreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`bookingID`, `customerID`, `pickupLocationID`, `destinationLocationID`, `tripID`, `pickupDateTime`, `price`, `createdBy`, `dateCreated`, `status`) VALUES
(1, 2, 1, 2, 231, '2026-05-15 20:43:00', 5000, 1, '2026-05-13 20:44:16', 'pending'),
(2, 2, 3, 4, 232, '2026-05-12 21:44:00', 100, 1, '2026-05-13 21:45:42', 'pending'),
(3, 4, 5, 6, 233, '2026-05-04 00:33:00', 500, 1, '2026-05-17 21:31:46', 'pending'),
(4, 4, 7, 8, 234, '2026-05-20 21:35:00', 500, 1, '2026-05-17 21:36:17', 'pending'),
(5, 4, 9, 10, 235, '2026-05-26 10:00:00', 500, 1, '2026-05-17 21:49:23', 'pending'),
(6, 4, 11, 12, 235, '2026-05-26 12:52:00', 5000, 1, '2026-05-17 21:50:13', 'pending'),
(7, 4, 13, 14, 236, '2026-05-21 21:08:00', 500, 9, '2026-05-21 21:09:00', 'completed'),
(8, 4, 15, 16, 237, '2026-05-22 10:21:00', 5000, 9, '2026-05-22 19:19:13', 'completed'),
(9, 4, 17, 18, 238, '2026-05-22 23:40:00', 500, 9, '2026-05-22 20:41:15', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `cargo`
--

CREATE TABLE `cargo` (
  `cargoID` int NOT NULL,
  `bookingID` int NOT NULL,
  `cargoType` varchar(100) NOT NULL,
  `quantity` int NOT NULL,
  `condition` varchar(100) DEFAULT NULL,
  `description` text,
  `specialHandling` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cargo`
--

INSERT INTO `cargo` (`cargoID`, `bookingID`, `cargoType`, `quantity`, `condition`, `description`, `specialHandling`) VALUES
(1, 1, 'Foods', 1000, 'Good', 'Don\'t break', ''),
(2, 2, 'Water', 100, 'wet', '', ''),
(3, 3, 'Water', 500, 'Good', '', ''),
(4, 4, 'Foods', 332, 'wet', '', ''),
(5, 5, 'Foods', 500, 'Good', 'ddwad', ''),
(6, 6, 'Foods', 32, 'Good', '', ''),
(7, 7, 'Foods', 500, '', '', ''),
(8, 8, 'Milk', 500, 'wet', '', ''),
(9, 8, 'goods', 5000, 'wet', '', ''),
(10, 9, 'goods', 5000, '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `id` int NOT NULL,
  `customerType` enum('individual','company') NOT NULL,
  `customerFName` varchar(100) NOT NULL,
  `customerLName` varchar(50) NOT NULL,
  `customerMI` varchar(1) NOT NULL,
  `contactPerson` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phoneNumber` varchar(11) NOT NULL,
  `province` varchar(50) NOT NULL,
  `warehouseLatitude` double DEFAULT NULL,
  `warehouseLongitude` double DEFAULT NULL,
  `companyDocument` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `dateRegistered` date NOT NULL,
  `status` enum('active','inactive') NOT NULL,
  `locationID` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`id`, `customerType`, `customerFName`, `customerLName`, `customerMI`, `contactPerson`, `email`, `phoneNumber`, `province`, `warehouseLatitude`, `warehouseLongitude`, `companyDocument`, `password`, `dateRegistered`, `status`, `locationID`) VALUES
(1, 'individual', 'Arldrich', 'Marcelino', 'A', '', 'marcelinoarldrich@gmail.com', '09369430341', 'Negros Occidental', NULL, NULL, '', '', '2026-04-24', 'active', NULL),
(2, 'company', 'Jethro T. Almodiel', '', '', 'Jethro T. Almodiel', 'almodieljethro16@gmail.com', '09287310860', 'Negros Occidental', NULL, NULL, '', '', '2026-04-24', 'active', NULL),
(3, 'company', 'ZEST-O', '', '', 'Jethro T. Almodiel', 'almodieljethro16@gmail.com', '09287310860', 'Negros Occidental', NULL, NULL, '', '', '2026-04-24', 'active', NULL),
(4, 'company', 'Almodiel Trucking Services', '', '', 'Jethro T. Almodiel', 'almodieljethro16@gmail.com', '09287310860', 'Negros Occidental', 10.65038966, 122.94679642, '1777000022_barangay_check_in.png', '', '2026-04-24', 'active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deliverycharge`
--

CREATE TABLE `deliverycharge` (
  `deliveryChargeID` int NOT NULL,
  `bookingID` int NOT NULL,
  `tripID` int NOT NULL,
  `chargeType` enum('hauling','others') NOT NULL DEFAULT 'hauling',
  `amount` double NOT NULL DEFAULT '0',
  `notes` text,
  `createdBy` int DEFAULT NULL,
  `dateCreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `id` int NOT NULL,
  `empFName` varchar(50) NOT NULL,
  `empLName` varchar(50) NOT NULL,
  `empMI` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `empSuffix` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `empBirthDate` date NOT NULL,
  `empPhoneNumber` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `empEmail` varchar(100) NOT NULL,
  `empType` enum('driver','assistant','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `empStatus` enum('active','inactive') NOT NULL,
  `dateCreated` datetime NOT NULL,
  `empPassword` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `licenseNumber` varchar(50) NOT NULL,
  `licenseExpire` varchar(50) NOT NULL,
  `licenseImage` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`id`, `empFName`, `empLName`, `empMI`, `empSuffix`, `empBirthDate`, `empPhoneNumber`, `empEmail`, `empType`, `empStatus`, `dateCreated`, `empPassword`, `licenseNumber`, `licenseExpire`, `licenseImage`) VALUES
(1, 'Arldrich', 'Marcelino', 'A', '', '2006-09-21', '09369430341', 'marcelinoarldrich@gmail.com', 'driver', 'active', '2026-05-06 10:05:30', '', '', '', ''),
(2, 'Arldrich', 'Marcelino', 'A', '', '2006-09-21', '09369430341', 'marcelinoarldrich@gmail.com', 'driver', 'active', '2026-05-06 10:05:32', '', '', '', ''),
(3, 'John Marion', 'Joniega', 'G', '', '2006-09-04', '09123495867', 'jonhmarionjoniega@gmail.com', 'assistant', 'active', '2026-05-06 10:14:00', '', '', '', ''),
(4, 'A', 'A', 'A', '', '2026-05-01', '09876543212', 'a@gmail.com', 'driver', 'active', '2026-05-06 10:14:48', '', '', '', ''),
(5, 'Jethro', 'Almodiel', 'T', 'Jr.', '2006-04-30', '09321312321', 'almodieljethro16@gmail.com', 'driver', 'active', '2026-05-11 18:03:05', '', '', '', ''),
(6, 'Mika', 'Zkie', '', '', '2026-05-12', '0932131235', 'almodieljethro@gmail.com', 'driver', 'active', '2026-05-11 18:10:51', '$2y$10$IMwtT2.zq9WvllzVOD/zpeRFXuDATGvOV9EKON1sKOkLHtfejLPAW', '', '', ''),
(7, 'Alex', 'Almodiel', 'T', 'jr.', '2026-05-13', '09321312321', 'almodieljetro16@gmail.com', 'assistant', 'active', '2026-05-13 20:42:43', '$2y$10$26pTtY/NzDcxQPsroovAWOJEg5sb2F9jc29kgv48BFNMr0gUrauvq', '', '', ''),
(8, 'Guanzon', 'Miguel', 'T', 'jr', '2020-06-23', '09320312321', 'jeth@gmail.com', 'driver', 'active', '2026-05-17 19:40:04', '$2y$10$FH1QdXJ/uuzY3OG60jUxJe/FcczvyTgTneiBqv0cbYI.6xn0RhESa', 'f01-01-111111', '2030-06-27', 'uploads/licenses/license_6a09a914c3cb62.11101954.png'),
(9, 'GWAPO', 'Almodiel', 'T', 'Jr', '2006-12-21', '09123456789', 'jethro@gmail.com', 'driver', 'active', '2026-05-21 01:06:00', '$2y$10$m23sexlvAJAnI6wHKh/5E.y35D4.DqPizlMaQOp9qeeEonFGYht1K', 'f01-01-111112', '2030-12-28', 'uploads/licenses/license_6a0de9f89cba48.64775178.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expenseID` int NOT NULL,
  `expenseDate` date NOT NULL,
  `category` enum('fuel','truck_maintenance','employee_salary','truck_document','toll','parking','repair','office','other') NOT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `description` text,
  `truckID` int DEFAULT NULL,
  `empID` int DEFAULT NULL,
  `tripID` int DEFAULT NULL,
  `bookingID` int DEFAULT NULL,
  `referenceNo` varchar(100) DEFAULT NULL,
  `receiptImage` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','paid','cancelled') NOT NULL DEFAULT 'paid',
  `createdBy` int DEFAULT NULL,
  `dateCreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE `location` (
  `locationID` int NOT NULL,
  `province` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `street` varchar(100) NOT NULL,
  `description` text,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `location`
--

INSERT INTO `location` (`locationID`, `province`, `city`, `barangay`, `street`, `description`, `latitude`, `longitude`) VALUES
(1, 'Negros Island Region', 'Bacolod', 'Villamonte', 'Hilado Street', 'Queen of Peace Church, Hilado Street, Taal, Villamonte, Bacolod-1, Bacolod, Negros Island Region, 6100, Philippines', 10.6757061, 122.95776833),
(2, 'Negros Island Region', 'Bacolod', 'Cabug', 'Handumanan Road', 'Handumanan Road, One Communities, Cabug, Bacolod-2, Bacolod, Negros Island Region, 6100, Philippines', 10.60957237, 122.95502156),
(3, 'Negros Occidental', 'Talisay', 'Efigenio Lizares', 'Mansiligan', 'Menlo Village Ⅰ, Zone 10, Efigenio Lizares, Talisay, Negros Occidental, Negros Island Region, 6115, Philippines', 10.73011961, 122.98075272),
(4, 'Negros Island Region', 'Bacolod', 'Villamonte', 'Palo Santol Stree', 'Palo Santol Stree, La Salleville, Villamonte, Bacolod-1, Bacolod, Negros Island Region, 6100, Philippines', 10.68101979, 122.96722239),
(5, 'Negros Island Region', 'Bacolod', 'Mandalagan', 'Aguinaldo Street', 'Aguinaldo Street, Barangay 4, Mandalagan, Bacolod-1, Bacolod, Negros Island Region, 6100, Philippines', 10.68097762, 122.95352389),
(6, 'Negros Island Region', 'Bacolod', 'Banago', 'Lacson Street', 'Northbound Lacson St. at Bata, Lacson Street, Pepsi, Banago, Bacolod-1, Bacolod, Negros Island Region, 6100, Philippines', 10.70429773, 122.96241779),
(7, 'Negros Island Region', 'Bacolod', 'Villamonte', 'Locarno Street', 'Locarno Street, Villa Angela Subdivision, Villamonte, Bacolod-1, Bacolod, Negros Island Region, 6100, Philippines', 10.66309625, 122.96828782),
(8, 'Negros Occidental', 'Silay', 'Barangay IV', 'BSAAR Extension', 'BSAAR Extension, Barangay IV, Silay, Negros Occidental, Negros Island Region, 6116, Philippines', 10.79244848, 123.01572689),
(9, 'Negros Island Region', 'Bacolod', 'Mandalagan', '3rd Street', '3rd Street, Paghida-et II, Barangay 17, Mandalagan, Bacolod-1, Bacolod, Negros Island Region, 6100, Philippines', 10.67399811, 122.95299803),
(10, 'Negros Occidental', 'Talisay', 'Zone 9', 'Governor Rafael Lacson Street', 'Governor Rafael Lacson Street, Zone 9, Zone 12, Talisay, Negros Occidental, Negros Island Region, 6115, Philippines', 10.73556411, 122.96758604),
(11, 'Negros Island Region', 'Bacolod', 'Taculing', 'Gomez Street', 'Taculing, Bacolod-2, Bacolod, Negros Island Region, 6100, Philippines', 10.64896753, 122.95502294),
(12, 'Negros Occidental', 'Silay', 'Guinhalaran', 'Lizares Avenue', 'Carmela Valley Silay 2, Guinhalaran, Mambulac, Silay, Negros Occidental, Negros Island Region, 6116, Philippines', 10.78659958, 122.97561779),
(13, 'Negros Occidental', 'Bacolod', 'Mansilingan', 'Guanzon', 'Company warehouse pickup point', 10.65038966, 122.94679642),
(14, 'Negros Occidental', 'Talisay', 'Zone 16', 'Palo Santol Stree', 'Cauayan, Zone 16, Bubog, Talisay, Negros Occidental, Negros Island Region, 6115, Philippines', 10.75059528, 122.97277871),
(15, 'Negros Occidental', 'Bacolod', 'Mansilingan', 'Guanzon', 'Company warehouse pickup point', 10.65038966, 122.94679642),
(16, 'Negros Occidental', 'Murcia', 'Cansilayan', 'Murcia - Bago Road', 'Murcia - Bago Road, Zone Ⅰ, Zone Ⅱ, Puyas, Cansilayan, Murcia, Negros Occidental, Negros Island Region, 6129, Philippines', 10.6033716, 123.03866868),
(17, 'Negros Occidental', 'Bacolod', 'Mansilingan', 'Guanzon', 'Company warehouse pickup point', 10.65038966, 122.94679642),
(18, 'Negros Occidental', 'Silay', 'Barangay IV', 'Palo Santol Stree', 'Barangay IV, Mambulac, Silay, Negros Occidental, Negros Island Region, 6116, Philippines', 10.79646417, 122.97627723);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `salesID` int NOT NULL,
  `bookingID` int NOT NULL,
  `tripID` int NOT NULL,
  `customerID` int NOT NULL,
  `grossAmount` double NOT NULL DEFAULT '0',
  `expenseAmount` double NOT NULL DEFAULT '0',
  `netAmount` double NOT NULL DEFAULT '0',
  `paidAmount` double NOT NULL DEFAULT '0',
  `balanceAmount` double NOT NULL DEFAULT '0',
  `customerType` varchar(20) NOT NULL,
  `paymentStatus` varchar(20) NOT NULL DEFAULT 'unpaid',
  `salesStatus` varchar(20) NOT NULL DEFAULT 'recorded',
  `dateGenerated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datePaid` datetime DEFAULT NULL,
  `remarks` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`salesID`, `bookingID`, `tripID`, `customerID`, `grossAmount`, `expenseAmount`, `netAmount`, `paidAmount`, `balanceAmount`, `customerType`, `paymentStatus`, `salesStatus`, `dateGenerated`, `datePaid`, `remarks`) VALUES
(1, 7, 236, 4, 500, 0, 500, 0, 500, 'company', 'unpaid', 'recorded', '2026-05-22 19:08:19', NULL, 'Auto-generated from completed booking'),
(2, 8, 237, 4, 5000, 0, 5000, 0, 5000, 'company', 'unpaid', 'recorded', '2026-05-22 20:38:14', NULL, 'Auto-generated from completed booking'),
(3, 9, 238, 4, 500, 0, 500, 0, 500, 'company', 'unpaid', 'recorded', '2026-05-23 00:10:02', NULL, 'Auto-generated from completed booking');

-- --------------------------------------------------------

--
-- Table structure for table `staffsalary`
--

CREATE TABLE `staffsalary` (
  `salaryID` int NOT NULL,
  `empID` int NOT NULL,
  `tripID` int DEFAULT NULL,
  `creditedBookingID` int DEFAULT NULL,
  `creditedDistanceKm` double NOT NULL DEFAULT '0',
  `tripRole` varchar(50) DEFAULT NULL,
  `payPeriodStart` date NOT NULL,
  `payPeriodEnd` date NOT NULL,
  `payType` enum('daily','weekly','semi-monthly','monthly','trip','allowance','bonus','adjustment') NOT NULL DEFAULT 'monthly',
  `baseRate` double NOT NULL DEFAULT '0',
  `grossPay` double NOT NULL DEFAULT '0',
  `deductions` double NOT NULL DEFAULT '0',
  `netPay` double NOT NULL DEFAULT '0',
  `datePaid` datetime DEFAULT NULL,
  `status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `remarks` text,
  `createdBy` int DEFAULT NULL,
  `dateCreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tariff`
--

CREATE TABLE `tariff` (
  `tariffID` int NOT NULL,
  `customerID` int DEFAULT NULL,
  `branch` varchar(100) NOT NULL DEFAULT 'BACOLOD',
  `origin` varchar(100) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `distanceKm` double NOT NULL DEFAULT '0',
  `truckType` varchar(50) NOT NULL,
  `baseRate` double NOT NULL DEFAULT '0',
  `hasFuelSubsidy` tinyint(1) NOT NULL DEFAULT '1',
  `fuelRangeStart` double DEFAULT NULL,
  `fuelRangeEnd` double DEFAULT NULL,
  `fuelSubsidy` double NOT NULL DEFAULT '0',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `dateCreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tariff`
--

INSERT INTO `tariff` (`tariffID`, `customerID`, `branch`, `origin`, `destination`, `distanceKm`, `truckType`, `baseRate`, `hasFuelSubsidy`, `fuelRangeStart`, `fuelRangeEnd`, `fuelSubsidy`, `status`, `dateCreated`) VALUES
(1, 3, 'BACOLOD', 'BACOLOD', 'BACOLOD CITY', 20, '6W', 3256.63, 1, 60, 70, 0, 'active', '2026-05-22 20:44:03'),
(2, 3, 'BACOLOD', 'BACOLOD', 'TALISAY/MURCIA', 30, '6W', 3673.33, 1, 60, 70, 0, 'active', '2026-05-22 20:44:03'),
(3, 3, 'BACOLOD', 'BACOLOD', 'SILAY/BAGO/PULUPANDAN', 40, '6W', 4090.03, 1, 60, 70, 0, 'active', '2026-05-22 20:44:03'),
(4, 3, 'BACOLOD', 'BACOLOD', 'CAUAYAN', 220, '6W', 11590.62, 1, 60, 70, 0, 'active', '2026-05-22 20:44:03'),
(5, 3, 'BACOLOD', 'BACOLOD', 'EB MAGALONA/VALLADOLID/SAN ENRIQUE/MA-AO/LACARLOTA', 60, '6W', 4923.43, 1, 60, 70, 0, 'active', '2026-05-22 20:45:17'),
(6, 3, 'BACOLOD', 'BACOLOD', 'VICTORIAS/PONTEVEDRA', 80, '6W', 5756.83, 1, 60, 70, 0, 'active', '2026-05-22 20:45:17'),
(7, 3, 'BACOLOD', 'BACOLOD', 'DSB/MANAPLA/HINIGARAN', 100, '6W', 6590.23, 1, 60, 70, 0, 'active', '2026-05-22 20:45:17'),
(8, 3, 'BACOLOD', 'BACOLOD', 'LA CASTELLANA/BINALBAGAN', 120, '6W', 7423.62, 1, 60, 70, 0, 'active', '2026-05-22 20:45:17'),
(9, 3, 'BACOLOD', 'BACOLOD', 'MOISES PADILLA/ISABELA/CADIZ/HIMAMAYLAN', 140, '6W', 8257.02, 1, 60, 70, 0, 'active', '2026-05-22 20:45:17'),
(10, 3, 'BACOLOD', 'BACOLOD', 'SAN CARLOS VIA DSB', 160, '6W', 9090.42, 1, 60, 70, 0, 'active', '2026-05-22 20:45:17'),
(11, 3, 'BACOLOD', 'BACOLOD', 'CANLAON/SAGAY/KABANKALAN', 180, '6W', 9923.82, 1, 60, 70, 0, 'active', '2026-05-22 20:45:17'),
(12, 3, 'BACOLOD', 'BACOLOD', 'ILOG/ESCALANTE', 200, '6W', 10757.22, 1, 60, 70, 0, 'active', '2026-05-22 20:45:17'),
(13, 3, 'BACOLOD', 'BACOLOD', 'TOBOSO', 240, '6W', 12424.02, 1, 60, 70, 0, 'active', '2026-05-22 20:45:17'),
(14, 3, 'BACOLOD', 'BACOLOD', 'CALATRAVA/CANDONI', 280, '6W', 14090.81, 1, 60, 70, 0, 'active', '2026-05-22 20:45:17'),
(15, 3, 'BACOLOD', 'BACOLOD', 'SAN CARLOS VIA NORTH', 300, '6W', 14924.21, 1, 60, 70, 0, 'active', '2026-05-22 20:45:18'),
(16, 3, 'BACOLOD', 'BACOLOD', 'SIPALAY', 340, '6W', 16591.01, 1, 60, 70, 0, 'active', '2026-05-22 20:45:18'),
(17, 3, 'BACOLOD', 'BACOLOD', 'HINOBAAN', 380, '6W', 18257.81, 1, 60, 70, 0, 'active', '2026-05-22 20:45:18'),
(19, 4, 'BACOLOD', 'BACOLOD', 'BACOLOD CITY', 20, '6W', 3256.63, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(20, 4, 'BACOLOD', 'BACOLOD', 'TALISAY/MURCIA', 30, '6W', 3673.33, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(21, 4, 'BACOLOD', 'BACOLOD', 'SILAY/BAGO/PULUPANDAN', 40, '6W', 4090.03, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(22, 4, 'BACOLOD', 'BACOLOD', 'EB MAGALONA/VALLADOLID/SAN ENRIQUE/MA-AO/LACARLOTA', 60, '6W', 4923.43, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(23, 4, 'BACOLOD', 'BACOLOD', 'VICTORIAS/PONTEVEDRA', 80, '6W', 5756.83, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(24, 4, 'BACOLOD', 'BACOLOD', 'DSB/MANAPLA/HINIGARAN', 100, '6W', 6590.23, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(25, 4, 'BACOLOD', 'BACOLOD', 'LA CASTELLANA/BINALBAGAN', 120, '6W', 7423.62, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(26, 4, 'BACOLOD', 'BACOLOD', 'MOISES PADILLA/ISABELA/CADIZ/HIMAMAYLAN', 140, '6W', 8257.02, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(27, 4, 'BACOLOD', 'BACOLOD', 'SAN CARLOS VIA DSB', 160, '6W', 9090.42, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(28, 4, 'BACOLOD', 'BACOLOD', 'CANLAON/SAGAY/KABANKALAN', 180, '6W', 9923.82, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(29, 4, 'BACOLOD', 'BACOLOD', 'ILOG/ESCALANTE', 200, '6W', 10757.22, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(30, 4, 'BACOLOD', 'BACOLOD', 'CAUAYAN', 220, '6W', 11590.62, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(31, 4, 'BACOLOD', 'BACOLOD', 'TOBOSO', 240, '6W', 12424.02, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(32, 4, 'BACOLOD', 'BACOLOD', 'CALATRAVA/CANDONI', 280, '6W', 14090.81, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(33, 4, 'BACOLOD', 'BACOLOD', 'SAN CARLOS VIA NORTH', 300, '6W', 14924.21, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(34, 4, 'BACOLOD', 'BACOLOD', 'SIPALAY', 340, '6W', 16591.01, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29'),
(35, 4, 'BACOLOD', 'BACOLOD', 'HINOBAAN', 380, '6W', 18257.81, 1, 60, 65, 0, 'active', '2026-05-22 23:57:29');

-- --------------------------------------------------------

--
-- Table structure for table `tripemployee`
--

CREATE TABLE `tripemployee` (
  `tripEmployeeID` int NOT NULL,
  `tripID` int NOT NULL,
  `truckID` int NOT NULL,
  `empID` int NOT NULL,
  `role` varchar(50) NOT NULL,
  `dateCreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tripemployee`
--

INSERT INTO `tripemployee` (`tripEmployeeID`, `tripID`, `truckID`, `empID`, `role`, `dateCreated`) VALUES
(1, 231, 1, 2, 'driver', '2026-05-13 20:44:16'),
(2, 231, 1, 3, 'assistant', '2026-05-13 20:44:16'),
(3, 231, 1, 7, 'assistant', '2026-05-13 20:44:16'),
(4, 232, 1, 2, 'driver', '2026-05-13 21:45:42'),
(5, 232, 1, 3, 'assistant', '2026-05-13 21:45:42'),
(6, 232, 1, 7, 'assistant', '2026-05-13 21:45:42'),
(7, 233, 1, 2, 'driver', '2026-05-17 21:31:46'),
(8, 233, 1, 3, 'assistant', '2026-05-17 21:31:46'),
(9, 233, 1, 7, 'assistant', '2026-05-17 21:31:46'),
(10, 234, 1, 2, 'driver', '2026-05-17 21:36:17'),
(11, 234, 1, 3, 'assistant', '2026-05-17 21:36:17'),
(12, 234, 1, 7, 'assistant', '2026-05-17 21:36:17'),
(13, 235, 1, 2, 'driver', '2026-05-17 21:49:23'),
(14, 235, 1, 3, 'assistant', '2026-05-17 21:49:23'),
(15, 235, 1, 7, 'assistant', '2026-05-17 21:49:23'),
(16, 236, 1, 9, 'driver', '2026-05-21 21:09:00'),
(17, 236, 1, 3, 'assistant', '2026-05-21 21:09:00'),
(18, 236, 1, 7, 'assistant', '2026-05-21 21:09:00'),
(19, 237, 1, 9, 'driver', '2026-05-22 19:19:13'),
(20, 237, 1, 3, 'assistant', '2026-05-22 19:19:13'),
(21, 237, 1, 7, 'assistant', '2026-05-22 19:19:13'),
(22, 238, 1, 9, 'driver', '2026-05-22 20:41:15'),
(23, 238, 1, 3, 'assistant', '2026-05-22 20:41:15'),
(24, 238, 1, 7, 'assistant', '2026-05-22 20:41:15');

-- --------------------------------------------------------

--
-- Table structure for table `truck`
--

CREATE TABLE `truck` (
  `id` int NOT NULL,
  `plateNumber` varchar(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `capacity` double NOT NULL,
  `fuel` int NOT NULL,
  `mileage` int NOT NULL,
  `brand` varchar(20) NOT NULL,
  `corDocument` varchar(255) NOT NULL,
  `otherDocument` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `truck`
--

INSERT INTO `truck` (`id`, `plateNumber`, `type`, `capacity`, `fuel`, `mileage`, `brand`, `corDocument`, `otherDocument`, `status`) VALUES
(1, 'COC123', '6w', 5000, 50, 1200, 'isuzu', '', NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `truckemployee`
--

CREATE TABLE `truckemployee` (
  `truckEmployeeID` int NOT NULL,
  `truckID` int NOT NULL,
  `empID` int NOT NULL,
  `role` varchar(50) NOT NULL,
  `dateCreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `truckemployee`
--

INSERT INTO `truckemployee` (`truckEmployeeID`, `truckID`, `empID`, `role`, `dateCreated`) VALUES
(7, 1, 9, 'driver', '2026-05-21 20:58:02'),
(8, 1, 3, 'assistant', '2026-05-21 20:58:02'),
(9, 1, 7, 'assistant', '2026-05-21 20:58:02');

-- --------------------------------------------------------

--
-- Table structure for table `userrights`
--

CREATE TABLE `userrights` (
  `id` int NOT NULL,
  `userid` varchar(10) NOT NULL,
  `empid` varchar(10) NOT NULL,
  `username` varchar(20) NOT NULL,
  `upassword` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `userrights`
--

INSERT INTO `userrights` (`id`, `userid`, `empid`, `username`, `upassword`) VALUES
(1, 'U0001', 'EM0001', 'admin', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`bookingID`),
  ADD KEY `customerID` (`customerID`),
  ADD KEY `pickupLocationID` (`pickupLocationID`),
  ADD KEY `destinationLocationID` (`destinationLocationID`),
  ADD KEY `tripID` (`tripID`),
  ADD KEY `createdBy` (`createdBy`);

--
-- Indexes for table `cargo`
--
ALTER TABLE `cargo`
  ADD PRIMARY KEY (`cargoID`),
  ADD KEY `bookingID` (`bookingID`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_customer_location` (`locationID`);

--
-- Indexes for table `deliverycharge`
--
ALTER TABLE `deliverycharge`
  ADD PRIMARY KEY (`deliveryChargeID`),
  ADD KEY `idx_deliverycharge_bookingID` (`bookingID`),
  ADD KEY `idx_deliverycharge_tripID` (`tripID`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expenseID`),
  ADD KEY `idx_expenses_category` (`category`),
  ADD KEY `idx_expenses_expenseDate` (`expenseDate`),
  ADD KEY `idx_expenses_truckID` (`truckID`),
  ADD KEY `idx_expenses_empID` (`empID`),
  ADD KEY `idx_expenses_tripID` (`tripID`),
  ADD KEY `idx_expenses_bookingID` (`bookingID`),
  ADD KEY `fk_expenses_created_by` (`createdBy`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`locationID`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`salesID`),
  ADD KEY `idx_sales_bookingID` (`bookingID`),
  ADD KEY `idx_sales_tripID` (`tripID`),
  ADD KEY `idx_sales_customerID` (`customerID`);

--
-- Indexes for table `staffsalary`
--
ALTER TABLE `staffsalary`
  ADD PRIMARY KEY (`salaryID`),
  ADD UNIQUE KEY `uniq_staffsalary_employee_trip` (`empID`,`tripID`),
  ADD KEY `idx_staffsalary_empID` (`empID`),
  ADD KEY `idx_staffsalary_period` (`payPeriodStart`,`payPeriodEnd`),
  ADD KEY `idx_staffsalary_status` (`status`),
  ADD KEY `idx_staffsalary_createdBy` (`createdBy`),
  ADD KEY `idx_staffsalary_tripID` (`tripID`),
  ADD KEY `idx_staffsalary_creditedBookingID` (`creditedBookingID`);

--
-- Indexes for table `tariff`
--
ALTER TABLE `tariff`
  ADD PRIMARY KEY (`tariffID`),
  ADD UNIQUE KEY `uniq_tariff_company_route_truck` (`customerID`,`branch`,`origin`,`destination`,`truckType`),
  ADD KEY `idx_tariff_customerID` (`customerID`),
  ADD KEY `idx_tariff_truckType` (`truckType`),
  ADD KEY `idx_tariff_destination` (`destination`),
  ADD KEY `idx_tariff_status` (`status`);

--
-- Indexes for table `tripemployee`
--
ALTER TABLE `tripemployee`
  ADD PRIMARY KEY (`tripEmployeeID`),
  ADD KEY `idx_tripemployee_tripID` (`tripID`),
  ADD KEY `idx_tripemployee_truckID` (`truckID`),
  ADD KEY `idx_tripemployee_empID` (`empID`);

--
-- Indexes for table `truck`
--
ALTER TABLE `truck`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `truckemployee`
--
ALTER TABLE `truckemployee`
  ADD PRIMARY KEY (`truckEmployeeID`),
  ADD KEY `idx_truckemployee_truckID` (`truckID`),
  ADD KEY `idx_truckemployee_empID` (`empID`);

--
-- Indexes for table `userrights`
--
ALTER TABLE `userrights`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `bookingID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `cargo`
--
ALTER TABLE `cargo`
  MODIFY `cargoID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `deliverycharge`
--
ALTER TABLE `deliverycharge`
  MODIFY `deliveryChargeID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expenseID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `locationID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `salesID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `staffsalary`
--
ALTER TABLE `staffsalary`
  MODIFY `salaryID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tariff`
--
ALTER TABLE `tariff`
  MODIFY `tariffID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `tripemployee`
--
ALTER TABLE `tripemployee`
  MODIFY `tripEmployeeID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `truck`
--
ALTER TABLE `truck`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `truckemployee`
--
ALTER TABLE `truckemployee`
  MODIFY `truckEmployeeID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `userrights`
--
ALTER TABLE `userrights`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customer`
--
ALTER TABLE `customer`
  ADD CONSTRAINT `fk_customer_location` FOREIGN KEY (`locationID`) REFERENCES `location` (`locationID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_created_by` FOREIGN KEY (`createdBy`) REFERENCES `employee` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_employee` FOREIGN KEY (`empID`) REFERENCES `employee` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_truck` FOREIGN KEY (`truckID`) REFERENCES `truck` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_booking` FOREIGN KEY (`bookingID`) REFERENCES `booking` (`bookingID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customerID`) REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `staffsalary`
--
ALTER TABLE `staffsalary`
  ADD CONSTRAINT `fk_staffsalary_created_by` FOREIGN KEY (`createdBy`) REFERENCES `employee` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_staffsalary_credited_booking` FOREIGN KEY (`creditedBookingID`) REFERENCES `booking` (`bookingID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_staffsalary_employee` FOREIGN KEY (`empID`) REFERENCES `employee` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tariff`
--
ALTER TABLE `tariff`
  ADD CONSTRAINT `fk_tariff_customer` FOREIGN KEY (`customerID`) REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `truckemployee`
--
ALTER TABLE `truckemployee`
  ADD CONSTRAINT `fk_truckemployee_employee` FOREIGN KEY (`empID`) REFERENCES `employee` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_truckemployee_truck` FOREIGN KEY (`truckID`) REFERENCES `truck` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
