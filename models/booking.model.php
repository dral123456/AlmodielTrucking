<?php
require_once "connection.php";

class ModelBooking {

  static public function mdlCustomerList() {
    $stmt = (new Connection)->connect()->prepare("
      SELECT id, customerType, customerFName, customerLName, contactPerson
      FROM customer
      WHERE status = 'active'
      ORDER BY customerFName, customerLName, contactPerson
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

  static public function mdlSaveBooking($data) {
    $db = new Connection();
    $pdo = $db->connect();

    try {
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $pdo->beginTransaction();

      $pickupLocationID = self::insertLocation($pdo, $data["pickup"]);
      $destinationLocationID = self::insertLocation($pdo, $data["destination"]);
      $tripID = self::generateTripID($pdo);

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

      if (isset($data["truckID"], $data["crew"])) {
        self::insertTripEmployees($pdo, $tripID, $data["truckID"], $data["crew"]);
      }

      $pdo->commit();
      return "success";

    } catch (PDOException $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }

      return "error";
    }
  }

  static private function insertLocation($pdo, $location) {
    $stmt = $pdo->prepare("
      INSERT INTO `location` (
        province,
        city,
        barangay,
        street,
        description,
        latitude,
        longitude
      ) VALUES (
        :province,
        :city,
        :barangay,
        :street,
        :description,
        :latitude,
        :longitude
      )
    ");

    $stmt->bindParam(":province", $location["province"], PDO::PARAM_STR);
    $stmt->bindParam(":city", $location["city"], PDO::PARAM_STR);
    $stmt->bindParam(":barangay", $location["barangay"], PDO::PARAM_STR);
    $stmt->bindParam(":street", $location["street"], PDO::PARAM_STR);
    $stmt->bindParam(":description", $location["description"], PDO::PARAM_STR);
    $stmt->bindParam(":latitude", $location["latitude"]);
    $stmt->bindParam(":longitude", $location["longitude"]);
    $stmt->execute();

    return $pdo->lastInsertId();
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

    $stmt->bindParam(":bookingID", $bookingID, PDO::PARAM_INT);
    $stmt->bindParam(":cargoType", $cargo["cargoType"], PDO::PARAM_STR);
    $stmt->bindParam(":quantity", $cargo["quantity"], PDO::PARAM_INT);
    $stmt->bindParam(":condition", $cargo["condition"], PDO::PARAM_STR);
    $stmt->bindParam(":description", $cargo["description"], PDO::PARAM_STR);
    $stmt->bindParam(":specialHandling", $cargo["specialHandling"], PDO::PARAM_STR);
    $stmt->execute();
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

  static private function tableExists($pdo, $tableName) {
    $stmt = $pdo->prepare("SHOW TABLES LIKE :tableName");
    $stmt->bindParam(":tableName", $tableName, PDO::PARAM_STR);
    $stmt->execute();

    return (bool) $stmt->fetchColumn();
  }
}
