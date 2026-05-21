<?php
require_once "../controllers/truck.controller.php";
require_once "../models/truck.model.php";

class TruckRegistration {
  public $plateNumber;
  public $type;
  public $capacity;
  public $fuel;
  public $mileage;
  public $brand;
  public $driverID;
  public $assistant1ID;
  public $assistant2ID;
  public $corDocument;
  public $otherDocument;

  public function saveTruck() {
    $data = array(
      "plateNumber" => $this->plateNumber,
      "type"        => $this->type,
      "capacity"    => $this->capacity,
      "fuel"        => $this->fuel,
      "mileage"     => $this->mileage,
      "brand"       => $this->brand,
      "corDocument" => $this->corDocument,
      "otherDocument" => $this->otherDocument,
      "driverID"    => $this->driverID,
      "assistant1ID" => $this->assistant1ID,
      "assistant2ID" => $this->assistant2ID
    );

    $answer = (new ControllerTruck)->ctrSaveTruck($data);
    echo $answer;
  }

  static public function saveUploadedImage($fieldName) {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]["error"] === UPLOAD_ERR_NO_FILE) {
      return "";
    }

    if ($_FILES[$fieldName]["error"] !== UPLOAD_ERR_OK) {
      return false;
    }

    $allowedTypes = array(
      "image/jpeg" => "jpg",
      "image/png" => "png",
      "image/webp" => "webp"
    );

    $mimeType = mime_content_type($_FILES[$fieldName]["tmp_name"]);
    if (!isset($allowedTypes[$mimeType])) {
      return false;
    }

    $uploadDir = "../uploads/";
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0775, true);
    }

    $safeName = preg_replace("/[^a-zA-Z0-9_-]/", "_", pathinfo($_FILES[$fieldName]["name"], PATHINFO_FILENAME));
    $fileName = time() . "_" . $fieldName . "_" . $safeName . "." . $allowedTypes[$mimeType];
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES[$fieldName]["tmp_name"], $targetPath)) {
      return false;
    }

    return $fileName;
  }
}

$save_truck = new TruckRegistration();

$corDocument = TruckRegistration::saveUploadedImage("corDocument");
$otherDocument = TruckRegistration::saveUploadedImage("otherDocument");

if ($corDocument === false || $corDocument === "" || $otherDocument === false) {
  echo "invalid_file";
  return;
}

$save_truck->plateNumber = $_POST["plateNumber"];
$save_truck->type        = $_POST["type"];
$save_truck->capacity    = $_POST["capacity"];
$save_truck->fuel        = $_POST["fuel"];
$save_truck->mileage     = $_POST["mileage"];
$save_truck->brand       = $_POST["brand"];
$save_truck->corDocument = $corDocument;
$save_truck->otherDocument = $otherDocument;
$save_truck->driverID    = $_POST["driverID"];
$save_truck->assistant1ID = $_POST["assistant1ID"];
$save_truck->assistant2ID = $_POST["assistant2ID"];

$save_truck->saveTruck();
