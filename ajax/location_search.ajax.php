<?php
require_once "../controllers/location.controller.php";
require_once "../models/location.model.php";

header("Content-Type: application/json");

$query = trim($_GET["q"] ?? "");

// Need at least 2 characters to search
if (strlen($query) < 2) {
  echo json_encode([]);
  exit;
}

$results = ControllerLocation::ctrSearchLocations($query, 8);

$output = array_map(function ($row) {
  // Build a human-readable label from most-specific to least
  $parts = array_filter([
    $row["street"]   ?? "",
    $row["barangay"] ?? "",
    $row["city"]     ?? "",
    $row["province"] ?? "",
  ]);
  $label = implode(", ", $parts);

  // If there is a description and it differs from the label, append it
  $desc = trim($row["description"] ?? "");
  if ($desc !== "" && stripos($label, $desc) === false) {
    $label = $desc . " — " . $label;
  }

  return [
    "locationID"  => (int)  $row["locationID"],
    "label"       =>        $label,
    "province"    =>        $row["province"]    ?? "",
    "city"        =>        $row["city"]        ?? "",
    "barangay"    =>        $row["barangay"]    ?? "",
    "street"      =>        $row["street"]      ?? "",
    "description" =>        $row["description"] ?? "",
    "lat"         => (float)$row["latitude"],
    "lng"         => (float)$row["longitude"],
  ];
}, $results);

echo json_encode($output);