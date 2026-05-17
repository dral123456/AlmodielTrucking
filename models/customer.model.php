<?php
require_once "connection.php";

class ModelCustomer {

  static public function mdlCompanyList() {
    $pdo = (new Connection)->connect();
    $warehouseSelect = self::columnExists($pdo, "customer", "warehouseLatitude") &&
      self::columnExists($pdo, "customer", "warehouseLongitude")
      ? "warehouseLatitude, warehouseLongitude"
      : "NULL AS warehouseLatitude, NULL AS warehouseLongitude";

    $stmt = $pdo->prepare("
      SELECT
        id,
        customerFName,
        contactPerson,
        email,
        phoneNumber,
        province,
        city,
        barangay,
        street,
        houseNumber,
        companyDocument,
        dateRegistered,
        status,
        {$warehouseSelect}
      FROM customer
      WHERE customerType = 'company'
      ORDER BY customerFName, contactPerson
    ");

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static public function mdlSaveCustomer($data) {

    $db = new Connection();
    $pdo = $db->connect();

    try {
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $pdo->beginTransaction();

      $customerFName = "";
      $customerLName = "";
      $customerMI    = "";
      $contactPerson = "";
      $companyDoc    = "";

      if ($data["customerType"] === "individual") {
        $customerFName = $data["firstName"];
        $customerLName = $data["lastName"];
        $customerMI    = $data["middleInitial"];
      }

      if ($data["customerType"] === "company") {
        $customerFName = $data["companyName"];
        $customerLName = "";
        $customerMI    = "";
        $contactPerson = $data["contactPerson"];

        if (!empty($data["businessDoc"]["tmp_name"])) {
          $targetDir = __DIR__ . "/../uploads/";
          $fileName = time() . "_" . basename($data["businessDoc"]["name"]);
          $targetFile = $targetDir . $fileName;

          if (move_uploaded_file($data["businessDoc"]["tmp_name"], $targetFile)) {
            $companyDoc = $fileName;
          }
        }
      }

      $columns = array(
        "customerType",
        "customerFName",
        "customerLName",
        "customerMI",
        "contactPerson",
        "email",
        "phoneNumber",
        "province",
        "city",
        "barangay",
        "street",
        "houseNumber",
        "companyDocument",
        "password",
        "dateRegistered",
        "status"
      );

      $values = array(
        ":customerType",
        ":customerFName",
        ":customerLName",
        ":customerMI",
        ":contactPerson",
        ":email",
        ":phoneNumber",
        ":province",
        ":city",
        ":barangay",
        ":street",
        ":houseNumber",
        ":companyDocument",
        ":password",
        "CURRENT_DATE",
        "'active'"
      );

      $hasWarehouseLatitude = self::columnExists($pdo, "customer", "warehouseLatitude");
      $hasWarehouseLongitude = self::columnExists($pdo, "customer", "warehouseLongitude");

      if ($hasWarehouseLatitude && $hasWarehouseLongitude) {
        $columns[] = "warehouseLatitude";
        $columns[] = "warehouseLongitude";
        $values[] = ":warehouseLatitude";
        $values[] = ":warehouseLongitude";
      }

      $stmt = $pdo->prepare("
        INSERT INTO customer (`" . implode("`, `", $columns) . "`)
        VALUES (" . implode(", ", $values) . ")
      ");

      $stmt->bindParam(":customerType", $data["customerType"], PDO::PARAM_STR);
      $stmt->bindParam(":customerFName", $customerFName, PDO::PARAM_STR);
      $stmt->bindParam(":customerLName", $customerLName, PDO::PARAM_STR);
      $stmt->bindParam(":customerMI", $customerMI, PDO::PARAM_STR);
      $stmt->bindParam(":contactPerson", $contactPerson, PDO::PARAM_STR);
      $stmt->bindParam(":email", $data["email"], PDO::PARAM_STR);
      $stmt->bindParam(":phoneNumber", $data["phoneNumber"], PDO::PARAM_STR);
      $stmt->bindParam(":province", $data["province"], PDO::PARAM_STR);
      $stmt->bindParam(":city", $data["city"], PDO::PARAM_STR);
      $stmt->bindParam(":barangay", $data["barangay"], PDO::PARAM_STR);
      $stmt->bindParam(":street", $data["street"], PDO::PARAM_STR);
      $stmt->bindParam(":houseNumber", $data["houseNumber"], PDO::PARAM_STR);
      $stmt->bindParam(":companyDocument", $companyDoc, PDO::PARAM_STR);
      $stmt->bindParam(":password", $data["password"], PDO::PARAM_STR);

      if ($hasWarehouseLatitude && $hasWarehouseLongitude) {
        $stmt->bindParam(":warehouseLatitude", $data["warehouseLatitude"]);
        $stmt->bindParam(":warehouseLongitude", $data["warehouseLongitude"]);
      }

      $stmt->execute();

      $pdo->commit();
      return "success";

    } catch (PDOException $e) {
      $pdo->rollBack();

      if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
        return "existing";
      }

      return "error";
    }
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
}
