<?php
require_once "connection.php";
require_once __DIR__ . "/sales.model.php";
require_once "location.model.php";

class ModelBooking {

  static public function mdlCustomerList() {
    $pdo = (new Connection)->connect();

    $stmt = $pdo->prepare("
      SELECT
        c.id,
        c.customerType,
        c.customerFName,
        c.customerLName,
        c.contactPerson,
        c.locationID,
        l.province,
        l.city,
        l.barangay,
        l.street,
        l.description,
        l.latitude,
        l.longitude
      FROM customer c
      LEFT JOIN location l ON l.locationID = c.locationID
      WHERE c.status = 'active'
      ORDER BY c.customerFName, c.customerLName, c.contactPerson
    ");

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static public function mdlTripList() {
    $pdo = (new Connection)->connect();

    if (!self::tableExists($pdo, "trip")) {
      return array();
    }

    $stmt = $pdo->prepare("SELECT * FROM `trip` ORDER BY tripID DESC");
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static public function mdlTruckList() {
    $stmt = (new Connection)->connect()->prepare("
      SELECT id, plateNumber, brand, type
      FROM truck
      WHERE status = 'active'
      ORDER BY plateNumber
    ");

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static public function mdlEmployeeListByType($type) {
    $stmt = (new Connection)->connect()->prepare("
      SELECT id, empFName, empLName
      FROM employee
      WHERE empType = :type AND empStatus = 'active'
      ORDER BY empFName, empLName
    ");

    $stmt->bindParam(":type", $type, PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static public function mdlTruckDefaultCrew($truckID) {
    $pdo = (new Connection)->connect();

    if (!self::tableExists($pdo, "truckemployee")) {
      return array("driverID" => "", "assistantIDs" => array());
    }

    $stmt = $pdo->prepare("
      SELECT empID, role
      FROM truckemployee
      WHERE truckID = :truckID
      ORDER BY truckEmployeeID
    ");

    $stmt->bindParam(":truckID", $truckID, PDO::PARAM_INT);
    $stmt->execute();

    $crew = array("driverID" => "", "assistantIDs" => array());

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      if ($row["role"] === "driver" && $crew["driverID"] === "") {
        $crew["driverID"] = (string) $row["empID"];
      }

      if ($row["role"] === "assistant") {
        $crew["assistantIDs"][] = (string) $row["empID"];
      }
    }

    return $crew;
  }

  static public function mdlTripOverviewList() {
    $pdo = (new Connection)->connect();

    $stmt = $pdo->prepare("
      SELECT
        b.bookingID,
        b.tripID,
        b.customerID,
        b.pickupDateTime,
        b.price,
        b.status,
        c.customerType,
        c.customerFName,
        c.customerLName,
        c.contactPerson,
        pickup.locationID AS pickupLocationID,
        pickup.province AS pickupProvince,
        pickup.city AS pickupCity,
        pickup.barangay AS pickupBarangay,
        pickup.street AS pickupStreet,
        pickup.description AS pickupDescription,
        pickup.latitude AS pickupLatitude,
        pickup.longitude AS pickupLongitude,
        destination.locationID AS destinationLocationID,
        destination.province AS destinationProvince,
        destination.city AS destinationCity,
        destination.barangay AS destinationBarangay,
        destination.street AS destinationStreet,
        destination.description AS destinationDescription,
        destination.latitude AS destinationLatitude,
        destination.longitude AS destinationLongitude,
        (
          SELECT GROUP_CONCAT(CONCAT(cargo.cargoType, ' x', cargo.quantity) SEPARATOR ', ')
          FROM cargo
          WHERE cargo.bookingID = b.bookingID
        ) AS cargoSummary
      FROM booking b
      INNER JOIN customer c ON c.id = b.customerID
      INNER JOIN location pickup ON pickup.locationID = b.pickupLocationID
      INNER JOIN location destination ON destination.locationID = b.destinationLocationID
      ORDER BY b.pickupDateTime DESC, b.tripID DESC, b.bookingID ASC
    ");

    $stmt->execute();
    $trips = array();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $tripID = (string) $row["tripID"];

      if (!isset($trips[$tripID])) {
        $trips[$tripID] = array(
          "tripID" => (int) $row["tripID"],
          "firstPickupDateTime" => $row["pickupDateTime"],
          "lastPickupDateTime" => $row["pickupDateTime"],
          "status" => $row["status"],
          "bookingCount" => 0,
          "totalPrice" => 0,
          "totalDistanceKm" => 0,
          "customers" => array(),
          "crew" => array(),
          "bookings" => array()
        );
      }

      if (strtotime($row["pickupDateTime"]) < strtotime($trips[$tripID]["firstPickupDateTime"])) {
        $trips[$tripID]["firstPickupDateTime"] = $row["pickupDateTime"];
      }

      if (strtotime($row["pickupDateTime"]) > strtotime($trips[$tripID]["lastPickupDateTime"])) {
        $trips[$tripID]["lastPickupDateTime"] = $row["pickupDateTime"];
      }

      $customerName = trim($row["customerFName"] . " " . $row["customerLName"]);
      if ($customerName === "") {
        $customerName = $row["contactPerson"];
      }

      $distanceKm = round(self::distanceInKilometers(
        (float) $row["pickupLatitude"],
        (float) $row["pickupLongitude"],
        (float) $row["destinationLatitude"],
        (float) $row["destinationLongitude"]
      ), 2);

      $trips[$tripID]["bookingCount"]++;
      $trips[$tripID]["totalPrice"] += (float) $row["price"];
      $trips[$tripID]["totalDistanceKm"] += $distanceKm;
      $trips[$tripID]["customers"][$row["customerID"]] = $customerName;
      $trips[$tripID]["bookings"][] = array(
        "bookingID" => (int) $row["bookingID"],
        "customerID" => (int) $row["customerID"],
        "customerName" => $customerName,
        "customerType" => $row["customerType"],
        "pickupDateTime" => $row["pickupDateTime"],
        "status" => $row["status"],
        "price" => (float) $row["price"],
        "distanceKm" => $distanceKm,
        "cargoSummary" => $row["cargoSummary"] ?: "",
        "pickup" => array(
          "address" => self::formatAddress($row["pickupStreet"], $row["pickupBarangay"], $row["pickupCity"], $row["pickupProvince"]),
          "description" => $row["pickupDescription"],
          "latitude" => (float) $row["pickupLatitude"],
          "longitude" => (float) $row["pickupLongitude"]
        ),
        "destination" => array(
          "locationID" => (int) $row["destinationLocationID"],
          "address" => self::formatAddress($row["destinationStreet"], $row["destinationBarangay"], $row["destinationCity"], $row["destinationProvince"]),
          "description" => $row["destinationDescription"],
          "province" => $row["destinationProvince"],
          "city" => $row["destinationCity"],
          "barangay" => $row["destinationBarangay"],
          "street" => $row["destinationStreet"],
          "latitude" => (float) $row["destinationLatitude"],
          "longitude" => (float) $row["destinationLongitude"]
        )
      );
    }

    foreach ($trips as $tripID => $trip) {
      $trips[$tripID]["customers"] = array_values($trip["customers"]);
      $trips[$tripID]["status"] = self::deriveTripStatus($trip["bookings"]);
      $trips[$tripID]["crew"] = self::getTripCrew($pdo, $tripID);
    }

    return array_values($trips);
  }

  static public function mdlDriverTripList($driverID, $showAll = false) {
    $pdo = (new Connection)->connect();
    $driverFilter = "";

    if (!$showAll && self::tableExists($pdo, "tripemployee")) {
      $driverFilter = "
        AND EXISTS (
          SELECT 1
          FROM tripemployee te
          WHERE te.tripID = b.tripID
            AND te.empID = :driverID
            AND te.role = 'driver'
        )
      ";
    }

    $stmt = $pdo->prepare("
      SELECT
        b.bookingID,
        b.tripID,
        b.customerID,
        b.pickupDateTime,
        b.price,
        b.status,
        c.customerType,
        c.customerFName,
        c.customerLName,
        c.contactPerson,
        pickup.province AS pickupProvince,
        pickup.city AS pickupCity,
        pickup.barangay AS pickupBarangay,
        pickup.street AS pickupStreet,
        pickup.description AS pickupDescription,
        pickup.latitude AS pickupLatitude,
        pickup.longitude AS pickupLongitude,
        destination.province AS destinationProvince,
        destination.city AS destinationCity,
        destination.barangay AS destinationBarangay,
        destination.street AS destinationStreet,
        destination.description AS destinationDescription,
        destination.latitude AS destinationLatitude,
        destination.longitude AS destinationLongitude
      FROM booking b
      INNER JOIN customer c ON c.id = b.customerID
      INNER JOIN location pickup ON pickup.locationID = b.pickupLocationID
      INNER JOIN location destination ON destination.locationID = b.destinationLocationID
      WHERE b.status IN ('pending', 'in-transit', 'stopover')
      {$driverFilter}
      ORDER BY b.pickupDateTime ASC, b.tripID ASC, b.bookingID ASC
    ");

    if ($driverFilter !== "") {
      $stmt->bindParam(":driverID", $driverID, PDO::PARAM_INT);
    }

    $stmt->execute();
    $trips = array();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $tripID = (string) $row["tripID"];

      if (!isset($trips[$tripID])) {
        $trips[$tripID] = array(
          "tripID" => (int) $row["tripID"],
          "pickupDateTime" => $row["pickupDateTime"],
          "status" => $row["status"],
          "bookingCount" => 0,
          "bookings" => array()
        );
      }

      $customerName = trim($row["customerFName"] . " " . $row["customerLName"]);
      if ($customerName === "") {
        $customerName = $row["contactPerson"];
      }

      $trips[$tripID]["bookingCount"]++;
      $trips[$tripID]["bookings"][] = array(
        "bookingID" => (int) $row["bookingID"],
        "customerName" => $customerName,
        "customerType" => $row["customerType"],
        "status" => $row["status"],
        "pickupDateTime" => $row["pickupDateTime"],
        "pickupAddress" => self::formatAddress($row["pickupStreet"], $row["pickupBarangay"], $row["pickupCity"], $row["pickupProvince"]),
        "pickupDescription" => $row["pickupDescription"],
        "pickupLatitude" => (float) $row["pickupLatitude"],
        "pickupLongitude" => (float) $row["pickupLongitude"],
        "destinationAddress" => self::formatAddress($row["destinationStreet"], $row["destinationBarangay"], $row["destinationCity"], $row["destinationProvince"]),
        "destinationDescription" => $row["destinationDescription"],
        "destinationLatitude" => (float) $row["destinationLatitude"],
        "destinationLongitude" => (float) $row["destinationLongitude"]
      );
    }

    foreach ($trips as $tripID => $trip) {
      $trips[$tripID]["status"] = self::deriveTripStatus($trip["bookings"]);
    }

    return array_values($trips);
  }

  static public function mdlUpdateTripDeliveryStatus($tripID, $status, $driverID, $showAll = false) {
    $allowedStatuses = array("in-transit", "stopover", "completed");

    if (!in_array($status, $allowedStatuses, true)) {
      return "invalid";
    }

    $pdo = (new Connection)->connect();
    $driverFilter = "";

    if (!$showAll && self::tableExists($pdo, "tripemployee")) {
      $driverFilter = "
        AND EXISTS (
          SELECT 1
          FROM tripemployee te
          WHERE te.tripID = booking.tripID
            AND te.empID = :driverID
            AND te.role = 'driver'
        )
      ";
    }

    $stmt = $pdo->prepare("
      UPDATE booking
      SET status = :status
      WHERE tripID = :tripID
        AND status <> 'completed'
        {$driverFilter}
    ");

    $stmt->bindParam(":status", $status, PDO::PARAM_STR);
    $stmt->bindParam(":tripID", $tripID, PDO::PARAM_INT);
    if ($driverFilter !== "") {
      $stmt->bindParam(":driverID", $driverID, PDO::PARAM_INT);
    }

    if (!$stmt->execute()) {
      return "error";
    }

    if ($status === "completed") {
      ModelSales::mdlSyncSalesForTrip($pdo, $tripID);
    }

    return "success";
  }

  static public function mdlUpdateTripInfo($tripID, $data) {
    $pdo = (new Connection)->connect();
    $allowedStatuses = array("pending", "in-transit", "stopover", "completed");
    $status = trim($data["status"] ?? "");
    $pickupDateTime = trim($data["pickupDateTime"] ?? "");
    $truckID = (int) ($data["truckID"] ?? 0);
    $driverID = (int) ($data["driverID"] ?? 0);
    $assistantIDs = $data["assistantIDs"] ?? array();
    $bookingID = (int) ($data["bookingID"] ?? 0);
    $price = $data["price"] ?? null;
    $destination = $data["destination"] ?? array();
    $tripID = (int) $tripID;

    if ($tripID <= 0 || !in_array($status, $allowedStatuses, true) || $pickupDateTime === "" || $truckID <= 0 || $driverID <= 0 || $bookingID <= 0) {
      return "error";
    }

    if (!is_array($assistantIDs)) {
      return "error";
    }

    $assistantIDs = array_values(array_unique(array_filter(array_map("intval", $assistantIDs))));
    $assistantIDs = array_values(array_filter($assistantIDs, function ($assistantID) use ($driverID) {
      return $assistantID > 0 && $assistantID !== $driverID;
    }));

    if (count($assistantIDs) < 2 || !self::tableExists($pdo, "tripemployee")) {
      return "error";
    }

    if (!self::tripHasBooking($pdo, $tripID, $bookingID) || !self::isValidPrice($price) || !self::isValidLocationPayload($destination)) {
      return "error";
    }

    $timestamp = strtotime($pickupDateTime);
    if ($timestamp === false) {
      return "error";
    }

    $pickupDateTime = date("Y-m-d H:i:s", $timestamp);

    try {
      $pdo->beginTransaction();

      $stmt = $pdo->prepare("
        UPDATE booking
        SET pickupDateTime = :pickupDateTime,
            status = :status
        WHERE tripID = :tripID
      ");

      $stmt->bindParam(":pickupDateTime", $pickupDateTime, PDO::PARAM_STR);
      $stmt->bindParam(":status", $status, PDO::PARAM_STR);
      $stmt->bindParam(":tripID", $tripID, PDO::PARAM_INT);
      $stmt->execute();

      $destinationLocationID = self::insertLocation($pdo, $destination);

      $bookingUpdate = $pdo->prepare("
        UPDATE booking
        SET destinationLocationID = :destinationLocationID,
            price = :price
        WHERE bookingID = :bookingID
          AND tripID = :tripID
      ");
      $bookingUpdate->bindParam(":destinationLocationID", $destinationLocationID, PDO::PARAM_INT);
      $bookingUpdate->bindParam(":price", $price);
      $bookingUpdate->bindParam(":bookingID", $bookingID, PDO::PARAM_INT);
      $bookingUpdate->bindParam(":tripID", $tripID, PDO::PARAM_INT);
      $bookingUpdate->execute();

      $delete = $pdo->prepare("DELETE FROM tripemployee WHERE tripID = :tripID");
      $delete->bindParam(":tripID", $tripID, PDO::PARAM_INT);
      $delete->execute();

      $insert = $pdo->prepare("
        INSERT INTO tripemployee (tripID, truckID, empID, role, dateCreated)
        VALUES (:tripID, :truckID, :empID, :role, NOW())
      ");

      self::insertTripEmployee($insert, $tripID, $truckID, $driverID, "driver");
      foreach ($assistantIDs as $assistantID) {
        self::insertTripEmployee($insert, $tripID, $truckID, $assistantID, "assistant");
      }

      if ($status === "completed") {
        ModelSales::mdlSyncSalesForTrip($pdo, $tripID);
      }

      $pdo->commit();
    } catch (PDOException $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }

      return "error";
    }

    return "success";
  }

  static private function formatAddress($street, $barangay, $city, $province) {
    return implode(", ", array_filter(array($street, $barangay, $city, $province)));
  }

  static private function tripHasBooking($pdo, $tripID, $bookingID) {
    $stmt = $pdo->prepare("
      SELECT COUNT(*)
      FROM booking
      WHERE tripID = :tripID
        AND bookingID = :bookingID
    ");
    $stmt->bindParam(":tripID", $tripID, PDO::PARAM_INT);
    $stmt->bindParam(":bookingID", $bookingID, PDO::PARAM_INT);
    $stmt->execute();

    return (int) $stmt->fetchColumn() > 0;
  }

  static private function isValidPrice($price) {
    return $price !== null && $price !== "" && is_numeric($price) && (float) $price >= 0;
  }

  static private function isValidLocationPayload($location) {
    if (!is_array($location)) {
      return false;
    }

    $latitude = $location["latitude"] ?? null;
    $longitude = $location["longitude"] ?? null;

    if ($latitude === null || $longitude === null || $latitude === "" || $longitude === "") {
      return false;
    }

    $lat = (float) $latitude;
    $lng = (float) $longitude;

    return is_finite($lat) &&
      is_finite($lng) &&
      $lat >= 9 &&
      $lat <= 11.2 &&
      $lng >= 122 &&
      $lng <= 123.6;
  }

  static private function deriveTripStatus($bookings) {
    $statuses = array_column($bookings, "status");

    if (in_array("in-transit", $statuses, true)) {
      return "in-transit";
    }

    if (in_array("stopover", $statuses, true)) {
      return "stopover";
    }

    if (!empty($statuses) && count(array_unique($statuses)) === 1 && $statuses[0] === "completed") {
      return "completed";
    }

    return "pending";
  }

  static private function getTripCrew($pdo, $tripID) {
    if (!self::tableExists($pdo, "tripemployee")) {
      return array();
    }

    $stmt = $pdo->prepare("
      SELECT
        te.truckID,
        te.empID,
        te.role,
        e.empFName,
        e.empLName,
        t.plateNumber
      FROM tripemployee te
      INNER JOIN employee e ON e.id = te.empID
      LEFT JOIN truck t ON t.id = te.truckID
      WHERE te.tripID = :tripID
      ORDER BY FIELD(te.role, 'driver', 'assistant'), e.empFName, e.empLName
    ");

    $stmt->bindParam(":tripID", $tripID, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static public function mdlSaveBooking($data) {
    $db = new Connection();
    $pdo = $db->connect();

    try {
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $pdo->beginTransaction();

      // ✅ FIX: ensure arrays exist (prevents undefined index + null location IDs)
      $pickupLocationID = $data["pickupLocationID"] ?? 0;
      $destinationLocationID = $data["destinationLocationID"] ?? 0;

      if (!$pickupLocationID || !$destinationLocationID) {
          throw new Exception("Missing location IDs");
      }

      $pickupLocationID = $data["pickupLocationID"];
      $destinationLocationID = $data["destinationLocationID"];  
      if (!$pickupLocationID || !$destinationLocationID) {
        throw new Exception("Missing location IDs");
      }

      $tripID = self::assignTripID($pdo, $data, $pickupLocationID, $destinationLocationID);

      $stmt = $pdo->prepare("
        INSERT INTO booking (
          customerID,
          pickupLocationID,
          destinationLocationID,
          tripID,
          pickupDateTime,
          price,
          createdBy,
          dateCreated,
          status
        ) VALUES (
          :customerID,
          :pickupLocationID,
          :destinationLocationID,
          :tripID,
          :pickupDateTime,
          :price,
          :createdBy,
          NOW(),
          'pending'
        )
      ");

      $stmt->bindParam(":customerID", $data["customerID"], PDO::PARAM_INT);
      $stmt->bindParam(":pickupLocationID", $pickupLocationID, PDO::PARAM_INT);
      $stmt->bindParam(":destinationLocationID", $destinationLocationID, PDO::PARAM_INT);
      $stmt->bindParam(":tripID", $tripID, PDO::PARAM_INT);
      $stmt->bindParam(":pickupDateTime", $data["pickupDateTime"], PDO::PARAM_STR);
      $stmt->bindParam(":price", $data["price"]);
      $stmt->bindParam(":createdBy", $data["createdBy"], PDO::PARAM_INT);
      $stmt->execute();

      $bookingID = $pdo->lastInsertId();
      self::insertCargo($pdo, $bookingID, $data["cargo"]);

      if (isset($data["truckID"], $data["crew"]) && !self::tripHasEmployees($pdo, $tripID)) {
        self::insertTripEmployees($pdo, $tripID, $data["truckID"], $data["crew"]);
      }

      $pdo->commit();
      return "success";

    } catch (PDOException $e) {

      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }

      die("BOOKING ERROR: " . $e->getMessage());
    }
  }


  static private function assignTripID($pdo, $data, $pickupLocationID, $destinationLocationID) {
    $customer = self::getCustomer($pdo, $data["customerID"]);

    if ($customer) {
      $existingTripID = self::findNearestExistingTrip($pdo, $customer, $data["pickupDateTime"], $pickupLocationID, $destinationLocationID);

      if ($existingTripID) {
        return $existingTripID;
      }
    }

    return self::generateTripID($pdo);
  }

  static private function getCustomer($pdo, $customerID) {
    $stmt = $pdo->prepare("
      SELECT
        c.id,
        c.customerType,
        c.locationID,

        l.province,
        l.city,
        l.barangay,
        l.street,
        l.description,
        l.latitude,
        l.longitude

      FROM customer c
      INNER JOIN location l
        ON l.locationID = c.locationID

      WHERE c.id = :customerID
      LIMIT 1
    ");

    $stmt->bindParam(":customerID", $customerID, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  static private function findNearestExistingTrip($pdo, $customer, $pickupDateTime, $pickupLocationID, $destinationLocationID) {
    $pickup = self::getLocationByID($pdo, $pickupLocationID);
    $destination = self::getLocationByID($pdo, $destinationLocationID);

    if (!$pickup || !$destination) {
      return null;
    }

    if ($customer["customerType"] === "company") {
      return self::findCompanyRouteTrip($pdo, $customer["id"], $pickupDateTime, $pickup, $destination);
    }

    $where = "
      DATE(b.pickupDateTime) = DATE(:pickupDateTime)
      AND b.status = 'pending'
    ";

    $where .= "
      AND c.customerType = 'individual'
      AND b.pickupDateTime <> :pickupDateTimeExact
    ";

    $stmt = $pdo->prepare("
      SELECT
        b.tripID,
        destination.latitude AS destinationLatitude,
        destination.longitude AS destinationLongitude
      FROM booking b
      INNER JOIN customer c ON c.id = b.customerID
      INNER JOIN location destination ON destination.locationID = b.destinationLocationID
      WHERE {$where}
      ORDER BY b.pickupDateTime ASC, b.bookingID ASC
    ");

    $stmt->bindParam(":pickupDateTime", $pickupDateTime, PDO::PARAM_STR);

    $stmt->bindParam(":pickupDateTimeExact", $pickupDateTime, PDO::PARAM_STR);

    $stmt->execute();

    $nearestTripID = null;
    $nearestDistance = null;

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $booking) {
      $distance = self::distanceInKilometers(
        $pickup["latitude"],
        $pickup["longitude"],
        $booking["destinationLatitude"],
        $booking["destinationLongitude"]
      );

      if ($nearestDistance === null || $distance < $nearestDistance) {
        $nearestDistance = $distance;
        $nearestTripID = (int) $booking["tripID"];
      }
    }

    return $nearestTripID;
  }

  static private function findCompanyRouteTrip($pdo, $customerID, $pickupDateTime, $pickup, $destination) {
    $stmt = $pdo->prepare("
      SELECT
        b.tripID,
        b.bookingID,
        pickup.latitude AS pickupLatitude,
        pickup.longitude AS pickupLongitude,
        destination.latitude AS destinationLatitude,
        destination.longitude AS destinationLongitude
      FROM booking b
      INNER JOIN location pickup ON pickup.locationID = b.pickupLocationID
      INNER JOIN location destination ON destination.locationID = b.destinationLocationID
      WHERE DATE(b.pickupDateTime) = DATE(:pickupDateTime)
        AND b.customerID = :customerID
        AND b.status = 'pending'
      ORDER BY b.pickupDateTime ASC, b.bookingID ASC
    ");

    $stmt->bindParam(":pickupDateTime", $pickupDateTime, PDO::PARAM_STR);
    $stmt->bindParam(":customerID", $customerID, PDO::PARAM_INT);
    $stmt->execute();

    $bestTripID = null;
    $bestScore = null;

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $booking) {
      $existingPickup = array(
        "latitude" => $booking["pickupLatitude"],
        "longitude" => $booking["pickupLongitude"]
      );
      $existingDestination = array(
        "latitude" => $booking["destinationLatitude"],
        "longitude" => $booking["destinationLongitude"]
      );

      if (!self::isOneWayRouteCompatible($existingPickup, $existingDestination, $pickup, $destination)) {
        continue;
      }

      $score = min(
        self::distanceInKilometers($pickup["latitude"], $pickup["longitude"], $existingDestination["latitude"], $existingDestination["longitude"]),
        self::distanceInKilometers($pickup["latitude"], $pickup["longitude"], $existingPickup["latitude"], $existingPickup["longitude"]),
        self::distanceInKilometers($destination["latitude"], $destination["longitude"], $existingDestination["latitude"], $existingDestination["longitude"])
      );

      if ($bestScore === null || $score < $bestScore) {
        $bestScore = $score;
        $bestTripID = (int) $booking["tripID"];
      }
    }

    return $bestTripID;
  }

  static private function isOneWayRouteCompatible($existingPickup, $existingDestination, $pickup, $destination) {
    $stopRadiusKm = 8;
    $routeCorridorKm = 12;
    $minimumDirectionCosine = 0.25;

    $direction = self::routeDirectionCosine($existingPickup, $existingDestination, $pickup, $destination);
    if ($direction < $minimumDirectionCosine) {
      return false;
    }

    $existingDestinationToPickup = self::distanceInKilometers(
      $existingDestination["latitude"],
      $existingDestination["longitude"],
      $pickup["latitude"],
      $pickup["longitude"]
    );

    if ($existingDestinationToPickup <= $stopRadiusKm) {
      return true;
    }

    $sameStart = self::distanceInKilometers(
      $existingPickup["latitude"],
      $existingPickup["longitude"],
      $pickup["latitude"],
      $pickup["longitude"]
    ) <= $stopRadiusKm;

    if ($sameStart && self::isPointOnForwardSegment($destination, $existingPickup, $existingDestination, $routeCorridorKm)) {
      return true;
    }

    if ($sameStart && self::isPointOnForwardSegment($existingDestination, $pickup, $destination, $routeCorridorKm)) {
      return true;
    }

    if (self::isPointOnForwardSegment($pickup, $existingPickup, $existingDestination, $routeCorridorKm) &&
        self::isPointOnForwardSegment($destination, $existingPickup, $destination, $routeCorridorKm)) {
      return true;
    }

    return false;
  }

  static private function routeDirectionCosine($aStart, $aEnd, $bStart, $bEnd) {
    $aStartPoint = self::locationToPoint($aStart, $aStart["latitude"]);
    $aEndPoint = self::locationToPoint($aEnd, $aStart["latitude"]);
    $bStartPoint = self::locationToPoint($bStart, $aStart["latitude"]);
    $bEndPoint = self::locationToPoint($bEnd, $aStart["latitude"]);

    $aVector = array("x" => $aEndPoint["x"] - $aStartPoint["x"], "y" => $aEndPoint["y"] - $aStartPoint["y"]);
    $bVector = array("x" => $bEndPoint["x"] - $bStartPoint["x"], "y" => $bEndPoint["y"] - $bStartPoint["y"]);
    $aLength = sqrt(($aVector["x"] * $aVector["x"]) + ($aVector["y"] * $aVector["y"]));
    $bLength = sqrt(($bVector["x"] * $bVector["x"]) + ($bVector["y"] * $bVector["y"]));

    if ($aLength <= 0 || $bLength <= 0) {
      return -1;
    }

    return (($aVector["x"] * $bVector["x"]) + ($aVector["y"] * $bVector["y"])) / ($aLength * $bLength);
  }

  static private function isPointOnForwardSegment($point, $segmentStart, $segmentEnd, $corridorKm) {
    $originLatitude = $segmentStart["latitude"];
    $start = self::locationToPoint($segmentStart, $originLatitude);
    $end = self::locationToPoint($segmentEnd, $originLatitude);
    $target = self::locationToPoint($point, $originLatitude);
    $segmentX = $end["x"] - $start["x"];
    $segmentY = $end["y"] - $start["y"];
    $segmentLengthSquared = ($segmentX * $segmentX) + ($segmentY * $segmentY);

    if ($segmentLengthSquared <= 0) {
      return false;
    }

    $projection = ((($target["x"] - $start["x"]) * $segmentX) + (($target["y"] - $start["y"]) * $segmentY)) / $segmentLengthSquared;

    if ($projection < 0 || $projection > 1) {
      return false;
    }

    $closest = array(
      "x" => $start["x"] + ($projection * $segmentX),
      "y" => $start["y"] + ($projection * $segmentY)
    );
    $distanceFromRoute = sqrt(pow($target["x"] - $closest["x"], 2) + pow($target["y"] - $closest["y"], 2));

    return $distanceFromRoute <= $corridorKm;
  }

  static private function locationToPoint($location, $originLatitude) {
    $earthRadius = 6371;
    $latitude = deg2rad((float) $location["latitude"]);
    $longitude = deg2rad((float) $location["longitude"]);
    $origin = deg2rad((float) $originLatitude);

    return array(
      "x" => $earthRadius * $longitude * cos($origin),
      "y" => $earthRadius * $latitude
    );
  }

  static private function getLocationByID($pdo, $locationID) {
    $stmt = $pdo->prepare("
      SELECT locationID, latitude, longitude
      FROM location
      WHERE locationID = :locationID
      LIMIT 1
    ");

    $stmt->bindParam(":locationID", $locationID, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  static private function distanceInKilometers($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371;
    $latDelta = deg2rad((float) $lat2 - (float) $lat1);
    $lngDelta = deg2rad((float) $lng2 - (float) $lng1);
    $startLat = deg2rad((float) $lat1);
    $endLat = deg2rad((float) $lat2);

    $a = sin($latDelta / 2) * sin($latDelta / 2) +
      cos($startLat) * cos($endLat) *
      sin($lngDelta / 2) * sin($lngDelta / 2);

    return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
  }

  static private function generateTripID($pdo) {
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(tripID), 0) + 1 FROM booking");
    $stmt->execute();

    return (int) $stmt->fetchColumn();
  }

  static private function insertCargo($pdo, $bookingID, $cargo) {
    $stmt = $pdo->prepare("
      INSERT INTO cargo (
        bookingID,
        cargoType,
        quantity,
        `condition`,
        description,
        specialHandling
      ) VALUES (
        :bookingID,
        :cargoType,
        :quantity,
        :condition,
        :description,
        :specialHandling
      )
    ");

    $items = isset($cargo["items"]) && is_array($cargo["items"]) ? $cargo["items"] : array();

    foreach ($items as $item) {
      $cargoType = trim((string) ($item["cargoType"] ?? ""));
      $quantity = (int) ($item["quantity"] ?? 0);

      if ($cargoType === "" || $quantity < 1) {
        continue;
      }

      $stmt->bindValue(":bookingID", $bookingID, PDO::PARAM_INT);
      $stmt->bindValue(":cargoType", $cargoType, PDO::PARAM_STR);
      $stmt->bindValue(":quantity", $quantity, PDO::PARAM_INT);
      $stmt->bindValue(":condition", $cargo["condition"], PDO::PARAM_STR);
      $stmt->bindValue(":description", $cargo["description"], PDO::PARAM_STR);
      $stmt->bindValue(":specialHandling", $cargo["specialHandling"], PDO::PARAM_STR);
      $stmt->execute();
    }
  }

  static private function insertTripEmployees($pdo, $tripID, $truckID, $crew) {
    if (!self::tableExists($pdo, "tripemployee")) {
      throw new PDOException("Missing tripemployee table");
    }

    $stmt = $pdo->prepare("
      INSERT INTO tripemployee (
        tripID,
        truckID,
        empID,
        role,
        dateCreated
      ) VALUES (
        :tripID,
        :truckID,
        :empID,
        :role,
        NOW()
      )
    ");

    self::insertTripEmployee($stmt, $tripID, $truckID, $crew["driverID"], "driver");

    $assistantIDs = array_unique(array_filter($crew["assistantIDs"]));
    foreach ($assistantIDs as $assistantID) {
      if ((int) $assistantID !== (int) $crew["driverID"]) {
        self::insertTripEmployee($stmt, $tripID, $truckID, $assistantID, "assistant");
      }
    }
  }

  static private function insertTripEmployee($stmt, $tripID, $truckID, $empID, $role) {
    $stmt->bindValue(":tripID", $tripID, PDO::PARAM_INT);
    $stmt->bindValue(":truckID", $truckID, PDO::PARAM_INT);
    $stmt->bindValue(":empID", $empID, PDO::PARAM_INT);
    $stmt->bindValue(":role", $role, PDO::PARAM_STR);
    $stmt->execute();
  }

  static private function tripHasEmployees($pdo, $tripID) {
    if (!self::tableExists($pdo, "tripemployee")) {
      return false;
    }

    $stmt = $pdo->prepare("
      SELECT COUNT(*)
      FROM tripemployee
      WHERE tripID = :tripID
    ");

    $stmt->bindParam(":tripID", $tripID, PDO::PARAM_INT);
    $stmt->execute();

    return (int) $stmt->fetchColumn() > 0;
  }

  static private function tableExists($pdo, $tableName) {
    $stmt = $pdo->prepare("SHOW TABLES LIKE :tableName");
    $stmt->bindParam(":tableName", $tableName, PDO::PARAM_STR);
    $stmt->execute();

    return (bool) $stmt->fetchColumn();
  }

  static private function columnExists($pdo, $tableName, $columnName) {
    $stmt = $pdo->prepare("
      SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = :tableName
        AND COLUMN_NAME = :columnName
    ");

    $stmt->bindParam(":tableName", $tableName, PDO::PARAM_STR);
    $stmt->bindParam(":columnName", $columnName, PDO::PARAM_STR);
    $stmt->execute();

    return (int) $stmt->fetchColumn() > 0;
  }

  static public function mdlCustomerBookingList($customerID) {
    $pdo = (new Connection)->connect();

    $stmt = $pdo->prepare("
      SELECT
        b.bookingID,
        b.tripID,
        b.customerID,
        b.pickupDateTime,
        b.price,
        b.status,

        c.customerType,
        c.customerFName,
        c.customerLName,
        c.contactPerson,

        pickup.province AS pickupProvince,
        pickup.city AS pickupCity,
        pickup.barangay AS pickupBarangay,
        pickup.street AS pickupStreet,
        pickup.description AS pickupDescription,
        pickup.latitude AS pickupLatitude,
        pickup.longitude AS pickupLongitude,

        destination.province AS destinationProvince,
        destination.city AS destinationCity,
        destination.barangay AS destinationBarangay,
        destination.street AS destinationStreet,
        destination.description AS destinationDescription,
        destination.latitude AS destinationLatitude,
        destination.longitude AS destinationLongitude

      FROM booking b

      INNER JOIN customer c
        ON c.id = b.customerID

      INNER JOIN location pickup
        ON pickup.locationID = b.pickupLocationID

      INNER JOIN location destination
        ON destination.locationID = b.destinationLocationID

      WHERE b.customerID = :customerID

      ORDER BY b.pickupDateTime ASC,
              b.tripID ASC,
              b.bookingID ASC
    ");

    $stmt->bindParam(":customerID", $customerID, PDO::PARAM_INT);

    $stmt->execute();

    $bookings = array();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {

      $customerName = trim(
        $row["customerFName"] . " " . $row["customerLName"]
      );

      if ($customerName === "") {
        $customerName = $row["contactPerson"];
      }

      $bookings[] = array(
        "bookingID" => (int) $row["bookingID"],
        "tripID" => (int) $row["tripID"],
        "customerID" => (int) $row["customerID"],

        "customerName" => $customerName,
        "customerType" => $row["customerType"],

        "pickupDateTime" => $row["pickupDateTime"],
        "price" => $row["price"],
        "status" => $row["status"],

        "pickupAddress" => self::formatAddress(
          $row["pickupStreet"],
          $row["pickupBarangay"],
          $row["pickupCity"],
          $row["pickupProvince"]
        ),

        "pickupDescription" => $row["pickupDescription"],
        "pickupLatitude" => (float) $row["pickupLatitude"],
        "pickupLongitude" => (float) $row["pickupLongitude"],

        "destinationAddress" => self::formatAddress(
          $row["destinationStreet"],
          $row["destinationBarangay"],
          $row["destinationCity"],
          $row["destinationProvince"]
        ),

        "destinationDescription" => $row["destinationDescription"],
        "destinationLatitude" => (float) $row["destinationLatitude"],
        "destinationLongitude" => (float) $row["destinationLongitude"]
      );
    }

    return $bookings;
  }

  static public function mdlGetBooking($bookingID) {
    $pdo = (new Connection)->connect();

    $stmt = $pdo->prepare("
      SELECT
        b.bookingID,
        b.tripID,
        b.customerID,
        b.pickupDateTime,
        b.price,
        b.status,

        c.customerType,
        c.customerFName,
        c.customerLName,
        c.contactPerson,

        pickup.province AS pickupProvince,
        pickup.city AS pickupCity,
        pickup.barangay AS pickupBarangay,
        pickup.street AS pickupStreet,
        pickup.description AS pickupDescription,
        pickup.latitude AS pickupLatitude,
        pickup.longitude AS pickupLongitude,

        destination.province AS destinationProvince,
        destination.city AS destinationCity,
        destination.barangay AS destinationBarangay,
        destination.street AS destinationStreet,
        destination.description AS destinationDescription,
        destination.latitude AS destinationLatitude,
        destination.longitude AS destinationLongitude

      FROM booking b

      INNER JOIN customer c
        ON c.id = b.customerID

      INNER JOIN location pickup
        ON pickup.locationID = b.pickupLocationID

      INNER JOIN location destination
        ON destination.locationID = b.destinationLocationID

      WHERE b.bookingID = :bookingID

      ORDER BY b.pickupDateTime ASC,
              b.tripID ASC,
              b.bookingID ASC
    ");

    $stmt->bindParam(":bookingID", $bookingID, PDO::PARAM_INT);

    $stmt->execute();

    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    return $booking;
  }

  static public function mdlReceiptBooking(int $bookingID): ?array {
      $pdo  = (new Connection)->connect();
      $stmt = $pdo->prepare("
          SELECT
              b.bookingID,
              b.tripID,
              b.pickupDateTime,
              b.price,
              b.status,
              c.customerFName,
              c.customerLName,
              c.contactPerson,
              c.customerType,
              pickup.street     AS pickupStreet,
              pickup.barangay   AS pickupBarangay,
              pickup.city       AS pickupCity,
              pickup.province   AS pickupProvince,
              dest.street       AS destinationStreet,
              dest.barangay     AS destinationBarangay,
              dest.city         AS destinationCity,
              dest.province     AS destinationProvince,
              COALESCE(dc.extraAmount, 0)  AS extraAmount,
              COALESCE(dc.extraTypes,  '') AS extraTypes,
              cargo.condition              AS cargoCondition
          FROM booking b
          LEFT JOIN customer  c      ON c.id                = b.customerID
          LEFT JOIN location  pickup ON pickup.locationID   = b.pickupLocationID
          LEFT JOIN location  dest   ON dest.locationID     = b.destinationLocationID
          LEFT JOIN (
              SELECT bookingID,
                      SUM(amount) AS extraAmount,
                      GROUP_CONCAT(DISTINCT chargeType SEPARATOR ', ') AS extraTypes
              FROM deliverycharge
              GROUP BY bookingID
          ) dc ON dc.bookingID = b.bookingID
          LEFT JOIN cargo ON cargo.bookingID = b.bookingID
          WHERE b.bookingID = :bookingID
          LIMIT 1
      ");
      $stmt->bindValue(':bookingID', $bookingID, PDO::PARAM_INT);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      return $row ?: null;
  }

  static public function mdlReceiptCargoItems(int $bookingID): array {
      $pdo  = (new Connection)->connect();
      $stmt = $pdo->prepare("
          SELECT cargoType, quantity, `condition`, description, specialHandling
          FROM cargo
          WHERE bookingID = :bookingID
          ORDER BY cargoID
      ");
      $stmt->bindValue(':bookingID', $bookingID, PDO::PARAM_INT);
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static public function mdlReceiptTripCrew(int $tripID): array {
      $pdo  = (new Connection)->connect();
      $stmt = $pdo->prepare("
          SELECT te.role, e.empFName, e.empLName, t.plateNumber
          FROM tripemployee te
          INNER JOIN employee e ON e.id  = te.empID
          LEFT JOIN  truck    t ON t.id  = te.truckID
          WHERE te.tripID = :tripID
          ORDER BY FIELD(te.role, 'driver', 'assistant'), e.empFName
      ");
      $stmt->bindValue(':tripID', $tripID, PDO::PARAM_INT);
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  
}
