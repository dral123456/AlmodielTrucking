<?php
require_once "connection.php";

class ModelEmployee {

    static public function mdlEmployeeList() {
        $pdo = (new Connection)->connect();
        $licenseSelect = self::columnExists($pdo, "employee", "licenseNumber") &&
            self::columnExists($pdo, "employee", "licenseExpire") &&
            self::columnExists($pdo, "employee", "licenseImage")
            ? "licenseNumber, licenseExpire, licenseImage"
            : "NULL AS licenseNumber, NULL AS licenseExpire, NULL AS licenseImage";

        $stmt = $pdo->prepare("
            SELECT
                id,
                empFName,
                empLName,
                empMI,
                empSuffix,
                empBirthDate,
                empPhoneNumber,
                empEmail,
                empType,
                empStatus,
                dateCreated,
                {$licenseSelect}
            FROM employee
            ORDER BY empStatus DESC, empFName, empLName
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlSaveEmployee($data) {
        $db = new Connection();
        $pdo = $db->connect();

        try {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
                    licenseNumber,
                    licenseExpire,
                    licenseImage,
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
                    :empStatus,
                    :licenseNumber,
                    :licenseExpire,
                    :licenseImage,
                    NOW()
                )
            ");

            $stmt->bindValue(":empFName", $data["empFName"], PDO::PARAM_STR);
            $stmt->bindValue(":empLName", $data["empLName"], PDO::PARAM_STR);
            $stmt->bindValue(":empMI", $data["empMI"], PDO::PARAM_STR);
            $stmt->bindValue(":empSuffix", $data["empSuffix"], PDO::PARAM_STR);
            $stmt->bindValue(":empBirthDate", $data["empBirthDate"], PDO::PARAM_STR);
            $stmt->bindValue(":empPhoneNumber", $data["empPhoneNumber"], PDO::PARAM_STR);
            $stmt->bindValue(":empEmail", $data["empEmail"], PDO::PARAM_STR);
            $stmt->bindValue(":empType", $data["empType"], PDO::PARAM_STR);
            $stmt->bindValue(":empPassword", $data["empPassword"], PDO::PARAM_STR);
            $stmt->bindValue(":empStatus", $data["empStatus"], PDO::PARAM_STR);
            $stmt->bindValue(":licenseNumber", $data["licenseNumber"], PDO::PARAM_STR);
            $stmt->bindValue(":licenseExpire", $data["licenseExpire"], PDO::PARAM_STR);
            $stmt->bindValue(":licenseImage", $data["licenseImage"], PDO::PARAM_STR);

            if ($stmt->execute()) {
                return "success";
            }

            return "error";

        } catch (PDOException $e) {
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                return "existing";
            }

            return "error: " . $e->getMessage();
        }
    }
    static public function mdlGetEmployeeCredentials($tableUsers, $item, $value, $empType){
		$stmt = (new Connection)->connect()->prepare("SELECT * FROM $tableUsers WHERE $item = :$item AND empType = :$empType");
		$stmt -> bindParam(":".$item, $value, PDO::PARAM_STR);
		$stmt -> bindParam(":".$empType, $empType, PDO::PARAM_STR);
		$stmt -> execute();
		return $stmt -> fetch();
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
