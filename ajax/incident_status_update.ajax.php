<?php
session_start();

require_once __DIR__ . "/../controllers/incident.controller.php";
require_once __DIR__ . "/../models/incident.model.php";

header("Content-Type: application/json");

if (!isset($_SESSION["loggedIn"]) || $_SESSION["loggedIn"] !== "ok" || ($_SESSION["role"] ?? "") !== "admin") {
  echo json_encode(array("status" => "error", "message" => "Only admins can update incident reports."));
  exit;
}

$incidentID = $_POST["incidentID"] ?? 0;
$status = $_POST["status"] ?? "";
$adminNotes = $_POST["adminNotes"] ?? "";
$reviewedBy = $_SESSION["id"] ?? 0;

$answer = ControllerIncident::ctrUpdateIncidentStatus($incidentID, $status, $adminNotes, $reviewedBy);

echo json_encode(array(
  "status" => $answer === "success" ? "success" : "error",
  "message" => $answer === "success" ? "Incident report updated." : "Unable to update incident report."
));
