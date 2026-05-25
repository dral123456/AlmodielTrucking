<?php
require_once "connection.php";

class ModelCustomer {

  static public function mdlCompanyList() {
    $pdo = (new Connection)->connect();

    $stmt = $pdo->prepare("
      SELECT
        c.id,
        c.locationID,
        c.customerFName,
        c.contactPerson,
        c.email,
        c.phoneNumber,
        l.province,
        l.city,
        l.barangay,
        l.street,
        l.description,
        l.latitude,
        l.longitude,
        c.companyDocument,
        c.dateRegistered,
        c.status
      FROM customer c
      LEFT JOIN location l ON l.locationID = c.locationID
      WHERE c.customerType = 'company'
      ORDER BY c.customerFName, c.contactPerson
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
          $targetDir  = __DIR__ . "/../uploads/";
          $fileName   = time() . "_" . basename($data["businessDoc"]["name"]);
          $targetFile = $targetDir . $fileName;

          if (move_uploaded_file($data["businessDoc"]["tmp_name"], $targetFile)) {
            $companyDoc = $fileName;
          }
        }
      }

      $stmt = $pdo->prepare("
        INSERT INTO customer (
          customerType,
          customerFName,
          customerLName,
          customerMI,
          contactPerson,
          email,
          phoneNumber,
          locationID,
          companyDocument,
          password,
          dateRegistered,
          status
        ) VALUES (
          :customerType,
          :customerFName,
          :customerLName,
          :customerMI,
          :contactPerson,
          :email,
          :phoneNumber,
          :locationID,
          :companyDocument,
          :password,
          CURRENT_DATE,
          'active'
        )
      ");

      $stmt->bindParam(":customerType",  $data["customerType"],  PDO::PARAM_STR);
      $stmt->bindParam(":customerFName", $customerFName,         PDO::PARAM_STR);
      $stmt->bindParam(":customerLName", $customerLName,         PDO::PARAM_STR);
      $stmt->bindParam(":customerMI",    $customerMI,            PDO::PARAM_STR);
      $stmt->bindParam(":contactPerson", $contactPerson,         PDO::PARAM_STR);
      $stmt->bindParam(":email",         $data["email"],         PDO::PARAM_STR);
      $stmt->bindParam(":phoneNumber",   $data["phoneNumber"],   PDO::PARAM_STR);
      $stmt->bindParam(":locationID",    $data["locationID"],    PDO::PARAM_INT);
      $stmt->bindParam(":companyDocument", $companyDoc,          PDO::PARAM_STR);
      $stmt->bindParam(":password",      $data["password"],      PDO::PARAM_STR);

      $stmt->execute();

      $pdo->commit();
      return "success";

    } catch (PDOException $e) {
      $pdo->rollBack();

      if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
        return "existing";
      }

      return $e->getMessage();
    }
  }

  static public function mdlGetCustomerCredentials($tableUsers, $item, $value) {
    $allowedTables  = array("customer");
    $allowedColumns = array("phoneNumber");

    if (!in_array($tableUsers, $allowedTables, true) || !in_array($item, $allowedColumns, true)) {
      return false;
    }

    $stmt = (new Connection)->connect()->prepare("
      SELECT *
      FROM {$tableUsers}
      WHERE {$item} = :value
      ORDER BY (password <> '') DESC, id DESC
      LIMIT 1
    ");
    $stmt->bindParam(":value", $value, PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  static private function columnExists($pdo, $tableName, $columnName) {
    $stmt = $pdo->prepare("
      SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = :tableName
        AND COLUMN_NAME = :columnName
    ");

    $stmt->bindParam(":tableName",  $tableName,  PDO::PARAM_STR);
    $stmt->bindParam(":columnName", $columnName, PDO::PARAM_STR);
    $stmt->execute();

    return (int) $stmt->fetchColumn() > 0;
  }
}