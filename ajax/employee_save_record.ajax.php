<?php
require_once "../controllers/employee.controller.php";
require_once "../models/employee.model.php";

class EmployeeRegistration {
    public $empFName;
    public $empLName;
    public $empMI;
    public $empSuffix;
    public $empBirthDate;
    public $empPhoneNumber;
    public $empEmail;
    public $empType;
    public $empPassword;
    public $empStatus = 'active';
    public $licenseNumber;
    public $licenseExpire;
    public $licenseImage;

    public function saveEmployee() {
        $data = array(
            "empFName"       => $this->empFName,
            "empLName"       => $this->empLName,
            "empMI"          => $this->empMI,
            "empSuffix"      => $this->empSuffix,
            "empBirthDate"   => $this->empBirthDate,
            "empPhoneNumber" => $this->empPhoneNumber,
            "empEmail"       => $this->empEmail,
            "empType"        => $this->empType,
            "empPassword"    => password_hash($this->empPassword, PASSWORD_DEFAULT),
            "empStatus"      => $this->empStatus,
            "licenseNumber"  => $this->licenseNumber,
            "licenseExpire"  => $this->licenseExpire,
            "licenseImage"   => $this->licenseImage
        );

        $answer = ControllerEmployee::ctrSaveEmployee($data);
        echo $answer;
    }
}

$emp = new EmployeeRegistration();

$emp->empFName       = $_POST["empFName"] ?? "";
$emp->empLName       = $_POST["empLName"] ?? "";
$emp->empMI          = $_POST["empMI"] ?? "";
$emp->empSuffix      = $_POST["empSuffix"] ?? "";
$emp->empBirthDate   = $_POST["empBirthDate"] ?? "";
$emp->empPhoneNumber = $_POST["empPhoneNumber"] ?? "";
$emp->empEmail       = $_POST["empEmail"] ?? "";
$emp->empType        = $_POST["empType"] ?? "";
$emp->empPassword    = $_POST["empPassword"] ?? "";

$emp->licenseNumber = null;
$emp->licenseExpire = null;
$emp->licenseImage  = null;

if ($emp->empType === "driver") {
    $emp->licenseNumber = $_POST["licenseNumber"] ?? null;
    $emp->licenseExpire = $_POST["expire"] ?? null;

    if (!isset($_FILES["licenseImage"]) || $_FILES["licenseImage"]["error"] !== UPLOAD_ERR_OK) {
        echo "License image is required for drivers.";
        exit;
    }

    $file = $_FILES["licenseImage"];
    $allowedExt = ["jpg", "jpeg", "png", "webp"];
    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt)) {
        echo "Invalid license image type.";
        exit;
    }

    $newName = uniqid("license_", true) . "." . $ext;
    $targetDir = "../uploads/licenses/";

    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetFile = $targetDir . $newName;

    if (!move_uploaded_file($file["tmp_name"], $targetFile)) {
        echo "Failed to upload license image.";
        exit;
    }

    $emp->licenseImage = "uploads/licenses/" . $newName;
}

$emp->saveEmployee();