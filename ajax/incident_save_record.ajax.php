<?php
session_start();

require_once __DIR__ . "/../controllers/incident.controller.php";
require_once __DIR__ . "/../models/incident.model.php";

header("Content-Type: application/json");

if (!isset($_SESSION["loggedIn"]) || $_SESSION["loggedIn"] !== "ok" || ($_SESSION["role"] ?? "") !== "driver") {
  echo json_encode(array("status" => "error", "message" => "Only logged-in drivers can submit incident reports."));
  exit;
}

$data = array(
  "driverID" => $_SESSION["id"] ?? 0,
  "tripID" => $_POST["tripID"] ?? 0,
  "bookingID" => $_POST["bookingID"] ?? 0,
  "incidentType" => $_POST["incidentType"] ?? "",
  "severity" => $_POST["severity"] ?? "",
  "incidentDateTime" => $_POST["incidentDateTime"] ?? "",
  "locationText" => $_POST["locationText"] ?? "",
  "description" => $_POST["description"] ?? "",
  "actionTaken" => $_POST["actionTaken"] ?? ""
);

$answer = ControllerIncident::ctrSaveIncident($data);

$messages = array(
  "success" => "Incident report submitted.",
  "invalid" => "Please complete the required incident report fields.",
  "forbidden" => "You can only report incidents for trips assigned to you.",
  "error" => "Unable to submit incident report."
);

echo json_encode(array(
  "status" => $answer === "success" ? "success" : "error",
  "message" => $messages[$answer] ?? $messages["error"]
));
