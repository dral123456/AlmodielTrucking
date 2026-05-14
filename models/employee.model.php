<?php
require_once "connection.php";

class ModelEmployee {

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
}