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

  public function saveTruck() {
    $data = array(
      "plateNumber" => $this->plateNumber,
      "type"        => $this->type,
      "capacity"    => $this->capacity,
      "fuel"        => $this->fuel,
      "mileage"     => $this->mileage,
      "brand"       => $this->brand,
      "driverID"    => $this->driverID,
      "assistant1ID" => $this->assistant1ID,
      "assistant2ID" => $this->assistant2ID
    );

    $answer = (new ControllerTruck)->ctrSaveTruck($data);
    echo $answer;
  }
}

$save_truck = new TruckRegistration();

$save_truck->plateNumber = $_POST["plateNumber"];
$save_truck->type        = $_POST["type"];
$save_truck->capacity    = $_POST["capacity"];
$save_truck->fuel        = $_POST["fuel"];
$save_truck->mileage     = $_POST["mileage"];
$save_truck->brand       = $_POST["brand"];
$save_truck->driverID    = $_POST["driverID"];
$save_truck->assistant1ID = $_POST["assistant1ID"];
$save_truck->assistant2ID = $_POST["assistant2ID"];

$save_truck->saveTruck();
