<?php
require_once "../controllers/location.controller.php";
require_once "../models/location.model.php";

class LocationRegistration {

  public $province;
  public $city;
  public $barangay;
  public $street;
  public $houseNumber;
  public $description;
  public $latitude;
  public $longitude;

  public function saveLocation() {
    $data = [
      "province"    => $this->province,
      "city"        => $this->city,
      "barangay"    => $this->barangay,
      "street"      => $this->street,
      "description" => $this->description,
      "latitude"    => $this->latitude,
      "longitude"   => $this->longitude,
    ];

    $locationID = (new ControllerLocation)->ctrSaveLocation($data);

    if ($locationID) {
      echo json_encode(["status" => "success", "locationID" => $locationID]);
    } else {
      echo json_encode(["status" => "error", "locationID" => null]);
    }
  }
}

$save_location = new LocationRegistration();
$save_location->province    = $_POST["province"]    ?? '';
$save_location->city        = $_POST["city"]        ?? '';
$save_location->barangay    = $_POST["barangay"]    ?? '';
$save_location->street      = $_POST["street"]      ?? '';
$save_location->houseNumber = $_POST["houseNumber"] ?? '';
$save_location->description = $_POST["description"] ?? '';
$save_location->latitude    = $_POST["lat"]         ?? 0;
$save_location->longitude   = $_POST["lng"]         ?? 0;

$save_location->saveLocation();