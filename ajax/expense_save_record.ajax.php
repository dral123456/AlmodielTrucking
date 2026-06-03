<?php
session_start();

require_once __DIR__ . "/../controllers/report.controller.php";
require_once __DIR__ . "/../models/report.model.php";

header("Content-Type: application/json");

if (!isset($_SESSION["loggedIn"]) || $_SESSION["loggedIn"] !== "ok" || ($_SESSION["role"] ?? "") !== "admin") {
  echo json_encode(array("status" => "error", "message" => "Only admins can add expenses."));
  exit;
}

$data = array(
  "expenseDate" => $_POST["expenseDate"] ?? "",
  "category" => $_POST["category"] ?? "",
  "amount" => $_POST["amount"] ?? 0,
  "description" => $_POST["description"] ?? "",
  "truckID" => $_POST["truckID"] ?? 0,
  "referenceNo" => $_POST["referenceNo"] ?? "",
  "status" => $_POST["status"] ?? "paid",
  "createdBy" => $_SESSION["id"] ?? 0
);

$answer = ControllerReport::ctrSaveExpense($data);

echo json_encode(array(
  "status" => $answer === "success" ? "success" : "error",
  "message" => $answer === "success"
    ? "Expense saved successfully."
    : ($answer === "invalid" ? "Please complete the required expense fields." : "Unable to save expense.")
));
