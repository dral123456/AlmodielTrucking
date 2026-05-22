<?php
session_start();

require_once "../controllers/salary.controller.php";
require_once "../models/salary.model.php";

header("Content-Type: application/json");

$role = $_SESSION["role"] ?? "";
if (!in_array($role, array("admin", "employee"), true)) {
  echo json_encode(array("status" => "forbidden"));
  exit;
}

$action = $_POST["action"] ?? "";

if ($action === "create") {
  $data = array(
    "empID" => $_POST["empID"] ?? 0,
    "tripID" => $_POST["tripID"] ?? 0,
    "payPeriodStart" => $_POST["payPeriodStart"] ?? "",
    "payPeriodEnd" => $_POST["payPeriodEnd"] ?? "",
    "payType" => $_POST["payType"] ?? "trip",
    "baseRate" => $_POST["baseRate"] ?? 0,
    "grossPay" => $_POST["grossPay"] ?? 0,
    "deductions" => $_POST["deductions"] ?? 0,
    "status" => $_POST["status"] ?? "pending",
    "remarks" => $_POST["remarks"] ?? "",
    "createdBy" => $_SESSION["id"] ?? null
  );

  echo json_encode(array("status" => ControllerSalary::ctrSaveSalary($data)));
  exit;
}

if ($action === "pay") {
  $salaryID = isset($_POST["salaryID"]) ? (int) $_POST["salaryID"] : 0;
  echo json_encode(array("status" => $salaryID > 0 ? ControllerSalary::ctrMarkSalaryPaid($salaryID) : "invalid"));
  exit;
}

echo json_encode(array("status" => "invalid"));
