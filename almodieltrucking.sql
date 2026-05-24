-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 13, 2026 at 02:09 PM
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
(2, 2, 3, 4, 232, '2026-05-12 21:44:00', 100, 1, '2026-05-13 21:45:42', 'pending');

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
(2, 2, 'Water', 100, 'wet', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `deliverycharge`
--

CREATE TABLE `deliverycharge` (
  `deliveryChargeID` int NOT NULL AUTO_INCREMENT,
  `bookingID` int NOT NULL,
  `tripID` int NOT NULL,
  `chargeType` enum('hauling','others') NOT NULL DEFAULT 'hauling',
  `amount` double NOT NULL DEFAULT '0',
  `notes` text,
  `createdBy` int DEFAULT NULL,
  `dateCreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`deliveryChargeID`),
  KEY `idx_deliverycharge_bookingID` (`bookingID`),
  KEY `idx_deliverycharge_tripID` (`tripID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `city` varchar(50) NOT NULL,
  `barangay` varchar(50) NOT NULL,
  `street` varchar(50) NOT NULL,
  `houseNumber` varchar(50) NOT NULL,
  `warehouseLatitude` double DEFAULT NULL,
  `warehouseLongitude` double DEFAULT NULL,
  `companyDocument` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `dateRegistered` date NOT NULL,
  `status` enum('active','inactive') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`id`, `customerType`, `customerFName`, `customerLName`, `customerMI`, `contactPerson`, `email`, `phoneNumber`, `province`, `city`, `barangay`, `street`, `houseNumber`, `warehouseLatitude`, `warehouseLongitude`, `companyDocument`, `password`, `dateRegistered`, `status`) VALUES
(1, 'individual', 'Arldrich', 'Marcelino', 'A', '', 'marcelinoarldrich@gmail.com', '09369430341', 'Negros Occidental', 'Bacolod', 'Mansilingan', 'Teak', 'East Homes 6 Blk 27 Lot 7', NULL, NULL, '', '', '2026-04-24', 'active'),
(2, 'company', '', '', '', 'Jethro T. Almodiel', 'almodieljethro16@gmail.com', '09287310860', 'Negros Occidental', 'Bacolod', 'Mansilingan', 'Guanzon', 'Blk 2 Lot 1', NULL, NULL, '', '', '2026-04-24', 'active'),
(3, 'company', 'Almodiel Trucking Service', '', '', 'Jethro T. Almodiel', 'almodieljethro16@gmail.com', '09287310860', 'Negros Occidental', 'Bacolod', 'Mansilingan', 'Guanzon', 'Blk 1 Lot 2', NULL, NULL, '', '', '2026-04-24', 'active'),
(4, 'company', 'Almodiel Trucking Services', '', '', 'Jethro T. Almodiel', 'almodieljethro16@gmail.com', '09287310860', 'Negros Occidental', 'Bacolod', 'Mansilingan', 'Guanzon', 'Blk 2 Lot 1', NULL, NULL, '1777000022_barangay_check_in.png', '', '2026-04-24', 'active');

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
  `empType` enum('driver','assistant') NOT NULL,
  `empStatus` enum('active','inactive') NOT NULL,
  `dateCreated` datetime NOT NULL,
  `empPassword` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`id`, `empFName`, `empLName`, `empMI`, `empSuffix`, `empBirthDate`, `empPhoneNumber`, `empEmail`, `empType`, `empStatus`, `dateCreated`, `empPassword`) VALUES
(1, 'Arldrich', 'Marcelino', 'A', '', '2006-09-21', '09369430341', 'marcelinoarldrich@gmail.com', 'driver', 'active', '2026-05-06 10:05:30', ''),
(2, 'Arldrich', 'Marcelino', 'A', '', '2006-09-21', '09369430341', 'marcelinoarldrich@gmail.com', 'driver', 'active', '2026-05-06 10:05:32', ''),
(3, 'John Marion', 'Joniega', 'G', '', '2006-09-04', '09123495867', 'jonhmarionjoniega@gmail.com', 'assistant', 'active', '2026-05-06 10:14:00', ''),
(4, 'A', 'A', 'A', '', '2026-05-01', '09876543212', 'a@gmail.com', 'driver', 'active', '2026-05-06 10:14:48', ''),
(5, 'Jethro', 'Almodiel', 'T', 'Jr.', '2006-04-30', '09321312321', 'almodieljethro16@gmail.com', 'driver', 'active', '2026-05-11 18:03:05', ''),
(6, 'Mika', 'Zkie', '', '', '2026-05-12', '0932131235', 'almodieljethro@gmail.com', 'driver', 'active', '2026-05-11 18:10:51', '$2y$10$IMwtT2.zq9WvllzVOD/zpeRFXuDATGvOV9EKON1sKOkLHtfejLPAW'),
(7, 'Alex', 'Almodiel', 'T', 'jr.', '2026-05-13', '09321312321', 'almodieljetro16@gmail.com', 'assistant', 'active', '2026-05-13 20:42:43', '$2y$10$26pTtY/NzDcxQPsroovAWOJEg5sb2F9jc29kgv48BFNMr0gUrauvq');

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
(4, 'Negros Island Region', 'Bacolod', 'Villamonte', 'Palo Santol Stree', 'Palo Santol Stree, La Salleville, Villamonte, Bacolod-1, Bacolod, Negros Island Region, 6100, Philippines', 10.68101979, 122.96722239);

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
(6, 232, 1, 7, 'assistant', '2026-05-13 21:45:42');

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

INSERT INTO `truck` (`id`, `plateNumber`, `type`, `capacity`, `fuel`, `mileage`, `brand`, `status`) VALUES
(1, 'COC123', '6w', 5000, 50, 1200, 'isuzu', 'active');

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
(1, 1, 2, 'driver', '2026-05-13 12:43:11'),
(2, 1, 3, 'assistant', '2026-05-13 12:43:11'),
(3, 1, 7, 'assistant', '2026-05-13 12:43:11');

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
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`locationID`);

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
  MODIFY `bookingID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cargo`
--
ALTER TABLE `cargo`
  MODIFY `cargoID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `locationID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tripemployee`
--
ALTER TABLE `tripemployee`
  MODIFY `tripEmployeeID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `truck`
--
ALTER TABLE `truck`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `truckemployee`
--
ALTER TABLE `truckemployee`
  MODIFY `truckEmployeeID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `userrights`
--
ALTER TABLE `userrights`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

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
