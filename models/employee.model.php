<?php
require_once "connection.php";

class ModelEmployee {

  static public function mdlSaveEmployee($data) {
    $db  = new Connection();
    $pdo = $db->connect();

    try {
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $pdo->beginTransaction();

      $stmt = $pdo->prepare("
        INSERT INTO employee (
          empFName,
          empLName,
          empMI,
          empSuffix,
          empBirthDate,
          empPhoneNumber,
          empEmail,
          empType,
          empPassword,
          empStatus,
          dateCreated
        ) VALUES (
          :empFName,
          :empLName,
          :empMI,
          :empSuffix,
          :empBirthDate,
          :empPhoneNumber,
          :empEmail,
          :empType,
          :empPassword,
          'active',
          NOW()
        )
      ");

      $stmt->bindParam(':empFName',       $data['empFName'],       PDO::PARAM_STR);
      $stmt->bindParam(':empLName',       $data['empLName'],       PDO::PARAM_STR);
      $stmt->bindParam(':empMI',          $data['empMI'],          PDO::PARAM_STR);
      $stmt->bindParam(':empSuffix',      $data['empSuffix'],      PDO::PARAM_STR);
      $stmt->bindParam(':empBirthDate',   $data['empBirthDate'],   PDO::PARAM_STR);
      $stmt->bindParam(':empPhoneNumber', $data['empPhoneNumber'], PDO::PARAM_STR);
      $stmt->bindParam(':empEmail',       $data['empEmail'],       PDO::PARAM_STR);
      $stmt->bindParam(':empType',        $data['empType'],        PDO::PARAM_STR);
      $stmt->bindParam(':empPassword',    $data['empPassword'],    PDO::PARAM_STR);

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
  static public function mdlGetEmployeeCredentials($tableUsers, $item, $value, $empType){
		$stmt = (new Connection)->connect()->prepare("SELECT * FROM $tableUsers WHERE $item = :$item AND empType = :$empType");
		$stmt -> bindParam(":".$item, $value, PDO::PARAM_STR);
		$stmt -> bindParam(":".$empType, $empType, PDO::PARAM_STR);
		$stmt -> execute();
		return $stmt -> fetch();
	}
}