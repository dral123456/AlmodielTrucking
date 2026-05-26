<?php
require_once "../models/connection.php";

class ManageRecordAjax {
  public function handle() {
    $entity = $_POST["entity"] ?? "";
    $action = $_POST["action"] ?? "";
    $id     = (int) ($_POST["id"] ?? 0);

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
      $pdo->beginTransaction();

      $province = trim($_POST["province"] ?? "");
      $city     = trim($_POST["city"] ?? "");
      $barangay = trim($_POST["barangay"] ?? "");
      $street   = trim($_POST["street"] ?? "");
      $lat      = trim($_POST["latitude"] ?? "");
      $lng      = trim($_POST["longitude"] ?? "");

      $parts = array_filter([$street, $barangay, $city, $province, "Philippines"]);
      $description = trim($_POST["description"] ?? "");
      if ($description === "") {
        $description = implode(", ", $parts);
      }

      // 1. Update customer fields
      $stmt = $pdo->prepare("
        UPDATE customer
        SET customerFName = :companyName,
            contactPerson = :contactPerson,
            email         = :email,
            phoneNumber   = :phoneNumber,
            province      = :province,
            warehouseLatitude  = :warehouseLatitude,
            warehouseLongitude = :warehouseLongitude,
            status        = :status
        WHERE id = :id AND customerType = 'company'
      ");
      $stmt->bindValue(":companyName",   $_POST["companyName"]   ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":contactPerson", $_POST["contactPerson"] ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":email",         $_POST["email"]         ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":phoneNumber",   $_POST["phoneNumber"]   ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":province",      $province, PDO::PARAM_STR);
      $stmt->bindValue(":warehouseLatitude",  $lat !== "" ? (float) $lat : null);
      $stmt->bindValue(":warehouseLongitude", $lng !== "" ? (float) $lng : null);
      $stmt->bindValue(":status",        $_POST["status"]        ?? "active", PDO::PARAM_STR);
      $stmt->bindParam(":id", $id, PDO::PARAM_INT);
      $stmt->execute();

      // 2. Get the locationID for this customer
      $locStmt = $pdo->prepare("SELECT locationID FROM customer WHERE id = :id");
      $locStmt->bindParam(":id", $id, PDO::PARAM_INT);
      $locStmt->execute();
      $locationID = (int) $locStmt->fetchColumn();

      if ($locationID > 0) {
        // 3. Update the existing location row
        $locUpdate = $pdo->prepare("
          UPDATE location
          SET province    = :province,
              city        = :city,
              barangay    = :barangay,
              street      = :street,
              description = :description,
              latitude    = :latitude,
              longitude   = :longitude
          WHERE locationID = :locationID
        ");
        $locUpdate->bindValue(":province",    $province, PDO::PARAM_STR);
        $locUpdate->bindValue(":city",        $city, PDO::PARAM_STR);
        $locUpdate->bindValue(":barangay",    $barangay, PDO::PARAM_STR);
        $locUpdate->bindValue(":street",      $street, PDO::PARAM_STR);
        $locUpdate->bindValue(":description", $description, PDO::PARAM_STR);
        $locUpdate->bindValue(":latitude",    $lat !== "" ? (float) $lat : null);
        $locUpdate->bindValue(":longitude",   $lng !== "" ? (float) $lng : null);
        $locUpdate->bindParam(":locationID",  $locationID, PDO::PARAM_INT);
        $locUpdate->execute();
      } else {
        // 4. No location yet — insert one and link it
        $locInsert = $pdo->prepare("
          INSERT INTO location (province, city, barangay, street, description, latitude, longitude)
          VALUES (:province, :city, :barangay, :street, :description, :latitude, :longitude)
        ");
        $locInsert->bindValue(":province",    $province,   PDO::PARAM_STR);
        $locInsert->bindValue(":city",        $city,       PDO::PARAM_STR);
        $locInsert->bindValue(":barangay",    $barangay,   PDO::PARAM_STR);
        $locInsert->bindValue(":street",      $street,     PDO::PARAM_STR);
        $locInsert->bindValue(":description", $description, PDO::PARAM_STR);
        $locInsert->bindValue(":latitude",    $lat !== "" ? (float) $lat : null);
        $locInsert->bindValue(":longitude",   $lng !== "" ? (float) $lng : null);
        $locInsert->execute();

        $newLocationID = (int) $pdo->lastInsertId();

        $linkStmt = $pdo->prepare("UPDATE customer SET locationID = :locationID WHERE id = :id");
        $linkStmt->bindParam(":locationID", $newLocationID, PDO::PARAM_INT);
        $linkStmt->bindParam(":id",         $id,            PDO::PARAM_INT);
        $linkStmt->execute();
      }

      $pdo->commit();
      return "success";

    } elseif ($entity === "employee") {
      $stmt = $pdo->prepare("
        UPDATE employee
        SET empFName       = :firstName,
            empLName       = :lastName,
            empPhoneNumber = :phoneNumber,
            empEmail       = :email,
            empType        = :empType,
            empStatus      = :status
        WHERE id = :id
      ");
      $stmt->bindValue(":firstName",   $_POST["firstName"]   ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":lastName",    $_POST["lastName"]    ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":phoneNumber", $_POST["phoneNumber"] ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":email",       $_POST["email"]       ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":empType",     $_POST["empType"]     ?? "driver", PDO::PARAM_STR);
      $stmt->bindValue(":status",      $_POST["status"]      ?? "active", PDO::PARAM_STR);
    } else {
      $stmt = $pdo->prepare("
        UPDATE truck
        SET plateNumber = :plateNumber,
            brand       = :brand,
            type        = :type,
            capacity    = :capacity,
            fuel        = :fuel,
            mileage     = :mileage,
            status      = :status
        WHERE id = :id
      ");
      $stmt->bindValue(":plateNumber", $_POST["plateNumber"] ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":brand",       $_POST["brand"]       ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":type",        $_POST["type"]        ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":capacity",    $_POST["capacity"]    ?? 0);
      $stmt->bindValue(":fuel",        $_POST["fuel"]        ?? 0,  PDO::PARAM_INT);
      $stmt->bindValue(":mileage",     $_POST["mileage"]     ?? 0,  PDO::PARAM_INT);
      $stmt->bindValue(":status",      $_POST["status"]      ?? "active", PDO::PARAM_STR);
    }

    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    return "success";
  }

  private function reassignCrew($pdo, $entity, $id) {
    if ($entity !== "truck") {
      return "error";
    }

    $driverID     = (int) ($_POST["driverID"] ?? 0);
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

    $table     = $this->resolveTruckEmployeeTable($pdo);
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
    $stmt->bindValue(":empID",   $empID,   PDO::PARAM_INT);
    $stmt->bindValue(":role",    $role,    PDO::PARAM_STR);
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
}

(new ManageRecordAjax())->handle();
