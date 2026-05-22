<?php
require_once "../controllers/tariff.controller.php";
require_once "../models/tariff.model.php";

header("Content-Type: application/json");

$data = array(
  "customerID" => $_POST["customerID"] ?? 0,
  "truckType" => $_POST["truckType"] ?? "",
  "destinationText" => $_POST["destinationText"] ?? "",
  "fuelPrice" => $_POST["fuelPrice"] ?? ""
);

echo json_encode(ControllerTariff::ctrLookupTariff($data));
