<?php
require_once "../models/connection.php";

class ManageRecordAjax {
  public function handle() {
    $entity = $_POST["entity"] ?? "";
    $action = $_POST["action"] ?? "";
    $id = (int) ($_POST["id"] ?? 0);

    if ($id <= 0 || !in_array($entity, ["company", "employee", "truck"], true) || !in_array($action, ["edit", "archive", "crew"], true)) {
      echo "error";
      return;
    }

    try {
      $pdo = (new Connection)->connect();
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      if ($action === "archive") {
        echo $this->archive($pdo, $entity, $id);
        return;
      }

      if ($action === "crew") {
        echo $this->reassignCrew($pdo, $entity, $id);
        return;
      }

      echo $this->edit($pdo, $entity, $id);
    } catch (PDOException $e) {
      if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
      }
      echo "error";
    }
  }

  private function archive($pdo, $entity, $id) {
    if ($entity === "company") {
      $stmt = $pdo->prepare("UPDATE customer SET status = 'inactive' WHERE id = :id AND customerType = 'company'");
    } elseif ($entity === "employee") {
      $stmt = $pdo->prepare("UPDATE employee SET empStatus = 'inactive' WHERE id = :id");
    } else {
      $stmt = $pdo->prepare("UPDATE truck SET status = 'inactive' WHERE id = :id");
    }

    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    return "success";
  }

  private function edit($pdo, $entity, $id) {
    if ($entity === "company") {
      $setFields = array(
        "customerFName = :companyName",
        "contactPerson = :contactPerson",
        "email = :email",
        "phoneNumber = :phoneNumber",
        "status = :status"
      );
      $hasProvince = $this->columnExists($pdo, "customer", "province");
      $hasCity = $this->columnExists($pdo, "customer", "city");
      $hasBarangay = $this->columnExists($pdo, "customer", "barangay");
      $hasStreet = $this->columnExists($pdo, "customer", "street");
      $hasHouseNumber = $this->columnExists($pdo, "customer", "houseNumber");
      $hasLocationID = $this->columnExists($pdo, "customer", "locationID");
      $hasWarehouseColumns = $this->columnExists($pdo, "customer", "warehouseLatitude") &&
        $this->columnExists($pdo, "customer", "warehouseLongitude");

      if ($hasProvince) {
        $setFields[] = "province = :province";
      }
      if ($hasCity) {
        $setFields[] = "city = :city";
      }
      if ($hasBarangay) {
        $setFields[] = "barangay = :barangay";
      }
      if ($hasStreet) {
        $setFields[] = "street = :street";
      }
      if ($hasHouseNumber) {
        $setFields[] = "houseNumber = :houseNumber";
      }

      $locationID = null;
      if ($hasLocationID && $this->tableExists($pdo, "location")) {
        $locationID = $this->saveCompanyLocation($pdo, $id);
        if ($locationID !== null) {
          $setFields[] = "locationID = :locationID";
        }
      }

      if ($hasWarehouseColumns) {
        $setFields[] = "warehouseLatitude = :warehouseLatitude";
        $setFields[] = "warehouseLongitude = :warehouseLongitude";
      }

      $stmt = $pdo->prepare("
        UPDATE customer
        SET " . implode(", ", $setFields) . "
        WHERE id = :id AND customerType = 'company'
      ");
      $stmt->bindValue(":companyName", $_POST["companyName"] ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":contactPerson", $_POST["contactPerson"] ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":email", $_POST["email"] ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":phoneNumber", $_POST["phoneNumber"] ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":status", $_POST["status"] ?? "active", PDO::PARAM_STR);
      if ($hasProvince) {
        $stmt->bindValue(":province", $_POST["province"] ?? "", PDO::PARAM_STR);
      }
      if ($hasCity) {
        $stmt->bindValue(":city", $_POST["city"] ?? "", PDO::PARAM_STR);
      }
      if ($hasBarangay) {
        $stmt->bindValue(":barangay", $_POST["barangay"] ?? "", PDO::PARAM_STR);
      }
      if ($hasStreet) {
        $stmt->bindValue(":street", $_POST["street"] ?? "", PDO::PARAM_STR);
      }
      if ($hasHouseNumber) {
        $stmt->bindValue(":houseNumber", $_POST["houseNumber"] ?? "", PDO::PARAM_STR);
      }
      if ($locationID !== null) {
        $stmt->bindValue(":locationID", $locationID, PDO::PARAM_INT);
      }
      if ($hasWarehouseColumns) {
        $warehouseLatitude = $_POST["warehouseLatitude"] ?? "";
        $warehouseLongitude = $_POST["warehouseLongitude"] ?? "";
        $stmt->bindValue(":warehouseLatitude", $warehouseLatitude !== "" ? $warehouseLatitude : null);
        $stmt->bindValue(":warehouseLongitude", $warehouseLongitude !== "" ? $warehouseLongitude : null);
      }
    } elseif ($entity === "employee") {
      $stmt = $pdo->prepare("
        UPDATE employee
        SET empFName = :firstName,
            empLName = :lastName,
            empPhoneNumber = :phoneNumber,
            empEmail = :email,
            empType = :empType,
            empStatus = :status
        WHERE id = :id
      ");
      $stmt->bindValue(":firstName", $_POST["firstName"] ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":lastName", $_POST["lastName"] ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":phoneNumber", $_POST["phoneNumber"] ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":email", $_POST["email"] ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":empType", $_POST["empType"] ?? "driver", PDO::PARAM_STR);
      $stmt->bindValue(":status", $_POST["status"] ?? "active", PDO::PARAM_STR);
    } else {
      $stmt = $pdo->prepare("
        UPDATE truck
        SET plateNumber = :plateNumber,
            brand = :brand,
            type = :type,
            capacity = :capacity,
            fuel = :fuel,
            mileage = :mileage,
            status = :status
        WHERE id = :id
      ");
      $stmt->bindValue(":plateNumber", $_POST["plateNumber"] ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":brand", $_POST["brand"] ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":type", $_POST["type"] ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":capacity", $_POST["capacity"] ?? 0);
      $stmt->bindValue(":fuel", $_POST["fuel"] ?? 0, PDO::PARAM_INT);
      $stmt->bindValue(":mileage", $_POST["mileage"] ?? 0, PDO::PARAM_INT);
      $stmt->bindValue(":status", $_POST["status"] ?? "active", PDO::PARAM_STR);
    }

    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    return "success";
  }

  private function saveCompanyLocation($pdo, $companyID) {
    $latitude = trim($_POST["warehouseLatitude"] ?? "");
    $longitude = trim($_POST["warehouseLongitude"] ?? "");

    if ($latitude === "" || $longitude === "") {
      return null;
    }

    $province = trim($_POST["province"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $barangay = trim($_POST["barangay"] ?? "");
    $street = trim($_POST["street"] ?? "");
    $houseNumber = trim($_POST["houseNumber"] ?? "");
    $description = implode(", ", array_filter(array($houseNumber, $street, $barangay, $city, $province)));
    $currentLocationID = $this->currentCompanyLocationID($pdo, $companyID);

    if ($currentLocationID !== null && $this->locationUsageCount($pdo, $currentLocationID) <= 1) {
      $stmt = $pdo->prepare("
        UPDATE location
        SET province = :province,
            city = :city,
            barangay = :barangay,
            street = :street,
            description = :description,
            latitude = :latitude,
            longitude = :longitude
        WHERE locationID = :locationID
      ");
      $stmt->bindValue(":locationID", $currentLocationID, PDO::PARAM_INT);
    } else {
      $stmt = $pdo->prepare("
        INSERT INTO location (province, city, barangay, street, description, latitude, longitude)
        VALUES (:province, :city, :barangay, :street, :description, :latitude, :longitude)
      ");
    }

    $stmt->bindValue(":province", $province, PDO::PARAM_STR);
    $stmt->bindValue(":city", $city, PDO::PARAM_STR);
    $stmt->bindValue(":barangay", $barangay, PDO::PARAM_STR);
    $stmt->bindValue(":street", $street, PDO::PARAM_STR);
    $stmt->bindValue(":description", $description, PDO::PARAM_STR);
    $stmt->bindValue(":latitude", $latitude);
    $stmt->bindValue(":longitude", $longitude);
    $stmt->execute();

    return $currentLocationID !== null && $this->locationUsageCount($pdo, $currentLocationID) <= 1
      ? $currentLocationID
      : (int) $pdo->lastInsertId();
  }

  private function currentCompanyLocationID($pdo, $companyID) {
    if (!$this->columnExists($pdo, "customer", "locationID")) {
      return null;
    }

    $stmt = $pdo->prepare("SELECT locationID FROM customer WHERE id = :id AND customerType = 'company'");
    $stmt->bindParam(":id", $companyID, PDO::PARAM_INT);
    $stmt->execute();

    $locationID = $stmt->fetchColumn();
    return $locationID ? (int) $locationID : null;
  }

  private function locationUsageCount($pdo, $locationID) {
    if (!$this->columnExists($pdo, "customer", "locationID")) {
      return 0;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer WHERE locationID = :locationID");
    $stmt->bindParam(":locationID", $locationID, PDO::PARAM_INT);
    $stmt->execute();

    return (int) $stmt->fetchColumn();
  }

  private function reassignCrew($pdo, $entity, $id) {
    if ($entity !== "truck") {
      return "error";
    }

    $driverID = (int) ($_POST["driverID"] ?? 0);
    $assistantIDs = json_decode($_POST["assistantIDs"] ?? "[]", true);

    if ($driverID <= 0 || !is_array($assistantIDs)) {
      return "error";
    }

    $assistantIDs = array_values(array_unique(array_filter(array_map("intval", $assistantIDs))));
    $assistantIDs = array_values(array_filter($assistantIDs, function ($assistantID) use ($driverID) {
      return $assistantID > 0 && $assistantID !== $driverID;
    }));

    if (count($assistantIDs) < 2) {
      return "error";
    }

    $table = $this->resolveTruckEmployeeTable($pdo);
    $safeTable = "`" . str_replace("`", "``", $table) . "`";

    $pdo->beginTransaction();

    $delete = $pdo->prepare("DELETE FROM {$safeTable} WHERE truckID = :truckID");
    $delete->bindParam(":truckID", $id, PDO::PARAM_INT);
    $delete->execute();

    $insert = $pdo->prepare("
      INSERT INTO {$safeTable} (truckID, empID, role, dateCreated)
      VALUES (:truckID, :empID, :role, NOW())
    ");

    $this->insertTruckEmployee($insert, $id, $driverID, "driver");
    foreach ($assistantIDs as $assistantID) {
      $this->insertTruckEmployee($insert, $id, $assistantID, "assistant");
    }

    $pdo->commit();
    return "success";
  }

  private function insertTruckEmployee($stmt, $truckID, $empID, $role) {
    $stmt->bindValue(":truckID", $truckID, PDO::PARAM_INT);
    $stmt->bindValue(":empID", $empID, PDO::PARAM_INT);
    $stmt->bindValue(":role", $role, PDO::PARAM_STR);
    $stmt->execute();
  }

  private function resolveTruckEmployeeTable($pdo) {
    foreach (["truckemployee", "truck_employee"] as $tableName) {
      $stmt = $pdo->prepare("SHOW TABLES LIKE :tableName");
      $stmt->bindParam(":tableName", $tableName, PDO::PARAM_STR);
      $stmt->execute();

      if ($stmt->fetchColumn()) {
        return $tableName;
      }
    }

    return "truckemployee";
  }

  private function columnExists($pdo, $tableName, $columnName) {
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

  private function tableExists($pdo, $tableName) {
    $stmt = $pdo->prepare("
      SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = :tableName
    ");

    $stmt->bindParam(":tableName", $tableName, PDO::PARAM_STR);
    $stmt->execute();

    return (int) $stmt->fetchColumn() > 0;
  }
}

(new ManageRecordAjax())->handle();
