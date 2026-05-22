<?php
session_start();

require_once "../controllers/tariff.controller.php";
require_once "../models/tariff.model.php";

header("Content-Type: application/json");

$role = $_SESSION["role"] ?? "";
if (!isset($_SESSION["loggedIn"]) || $_SESSION["loggedIn"] !== "ok" || !in_array($role, array("admin", "employee", "assistant", "driver"), true)) {
  echo json_encode(array("status" => "forbidden"));
  exit;
}

$action = $_POST["action"] ?? "";

if ($action === "save") {
  $data = array(
    "tariffID" => $_POST["tariffID"] ?? 0,
    "customerID" => $_POST["customerID"] ?? 0,
    "branch" => $_POST["branch"] ?? "BACOLOD",
    "origin" => $_POST["origin"] ?? "BACOLOD",
    "destination" => $_POST["destination"] ?? "",
    "distanceKm" => $_POST["distanceKm"] ?? 0,
    "truckType" => $_POST["truckType"] ?? "",
    "baseRate" => $_POST["baseRate"] ?? 0,
    "fuelRangeStart" => $_POST["fuelRangeStart"] ?? "",
    "fuelRangeEnd" => $_POST["fuelRangeEnd"] ?? "",
    "hasFuelSubsidy" => $_POST["hasFuelSubsidy"] ?? 0,
    "fuelSubsidy" => $_POST["fuelSubsidy"] ?? 0,
    "status" => $_POST["status"] ?? "active"
  );

  echo json_encode(array("status" => ControllerTariff::ctrSaveTariff($data)));
  exit;
}

if ($action === "archive") {
  echo json_encode(array("status" => ControllerTariff::ctrArchiveTariff((int) ($_POST["tariffID"] ?? 0))));
  exit;
}

if ($action === "bulkFuelRange") {
  echo json_encode(ControllerTariff::ctrBulkUpdateFuelRange(array(
    "customerID" => $_POST["customerID"] ?? 0,
    "truckType" => $_POST["truckType"] ?? "",
    "fuelRangeStart" => $_POST["fuelRangeStart"] ?? "",
    "fuelRangeEnd" => $_POST["fuelRangeEnd"] ?? "",
    "hasFuelSubsidy" => $_POST["hasFuelSubsidy"] ?? 0
  )));
  exit;
}

if ($action === "import") {
  if (empty($_FILES["tariffCsv"]["tmp_name"])) {
    echo json_encode(array("status" => "missing-file"));
    exit;
  }

  $handle = fopen($_FILES["tariffCsv"]["tmp_name"], "r");
  if (!$handle) {
    echo json_encode(array("status" => "invalid-file"));
    exit;
  }

  $headers = fgetcsv($handle);
  if (!$headers) {
    fclose($handle);
    echo json_encode(array("status" => "invalid-file"));
    exit;
  }

  $normalizedHeaders = array_map(array("ModelTariff", "normalizeHeader"), $headers);
  $rows = array();

  while (($data = fgetcsv($handle)) !== false) {
    $row = array();
    foreach ($normalizedHeaders as $index => $header) {
      $row[$header] = $data[$index] ?? "";
    }
    $rows[] = $row;
  }
  fclose($handle);

  $defaults = array(
    "customerID" => $_POST["customerID"] ?? 0,
    "branch" => $_POST["branch"] ?? "BACOLOD",
    "origin" => $_POST["origin"] ?? "BACOLOD",
    "truckType" => $_POST["truckType"] ?? "",
    "fuelRangeStart" => $_POST["fuelRangeStart"] ?? 60,
    "fuelRangeEnd" => $_POST["fuelRangeEnd"] ?? 65,
    "hasFuelSubsidy" => isset($_POST["hasFuelSubsidy"]) && $_POST["hasFuelSubsidy"] === "1"
  );

  echo json_encode(ControllerTariff::ctrImportTariffCsv($rows, $defaults));
  exit;
}

echo json_encode(array("status" => "invalid"));
