<?php
require_once "../controllers/customer.controller.php";
require_once "../models/customer.model.php";

class CustomerRegistration {
  public $customerType;

  // Individual fields
  public $firstName;
  public $lastName;
  public $middleInitial;

  // Company fields
  public $companyName;
  public $contactPerson;

  // Shared
  public $email;
  public $phoneNumber;

  // Address
  public $province;
  public $city;
  public $barangay;
  public $street;
  public $houseNumber;

  // Company documents
  public $businessDoc;
  public $otherDocs;

  public function saveCustomer() {
    $data = array(
      "customerType"  => $this->customerType,
      "email"         => $this->email,
      "phoneNumber"   => $this->phoneNumber,
      "province"      => $this->province,
      "city"          => $this->city,
      "barangay"      => $this->barangay,
      "street"        => $this->street,
      "houseNumber"   => $this->houseNumber,
    );

    if ($this->customerType === 'company') {
      $data["companyName"]   = $this->companyName;
      $data["contactPerson"] = $this->contactPerson;
      $data["businessDoc"]   = $this->businessDoc;
      $data["otherDocs"]     = $this->otherDocs;
    } else {
      $data["firstName"]     = $this->firstName;
      $data["lastName"]      = $this->lastName;
      $data["middleInitial"] = $this->middleInitial;
    }

    $answer = (new ControllerCustomer)->ctrSaveCustomer($data);
    echo $answer;
  }
}

$save_customer = new CustomerRegistration();

$save_customer->customerType  = $_POST["customerType"];
$save_customer->email         = $_POST["email"];
$save_customer->phoneNumber   = $_POST["phoneNumber"];
$save_customer->province      = $_POST["province"];
$save_customer->city          = $_POST["city"];
$save_customer->barangay      = $_POST["barangay"];
$save_customer->street        = $_POST["street"];
$save_customer->houseNumber   = $_POST["houseNumber"];

if ($_POST["customerType"] === 'company') {
  $save_customer->companyName   = $_POST["companyName"]   ?? '';
  $save_customer->contactPerson = $_POST["contactPerson"] ?? '';
  $save_customer->businessDoc   = $_FILES["businessDoc"]  ?? null;
  $save_customer->otherDocs     = $_FILES["otherDocs"]    ?? null;
} else {
  $save_customer->firstName     = $_POST["firstName"]     ?? '';
  $save_customer->lastName      = $_POST["lastName"]      ?? '';
  $save_customer->middleInitial = $_POST["middleInitial"] ?? '';
}

$save_customer->saveCustomer();