<?php
require_once "../controllers/customer.controller.php";
require_once "../models/customer.model.php";

class CustomerRegistration {

  public $customerType;

  // Individual fields
  public $firstName;
  public $lastName;
  public $middleInitial;

  // Shared
  public $email;
  public $phoneNumber;
  public $locationID;
  public $province;
  public $city;
  public $barangay;
  public $street;
  public $houseNumber;
  public $description;

  // Company fields
  public $companyName;
  public $contactPerson;
  public $warehouseLatitude;
  public $warehouseLongitude;
  public $businessDoc;

  // Account
  public $password;

  public function saveCustomer() {
    $data = [
      "customerType"  => $this->customerType,
      "email"         => $this->email,
      "phoneNumber"   => $this->phoneNumber,
      "locationID"    => $this->locationID,
      "province"      => $this->province,
      "city"          => $this->city,
      "barangay"      => $this->barangay,
      "street"        => $this->street,
      "houseNumber"   => $this->houseNumber,
      "description"   => $this->description,
      "password"      => password_hash($this->password, PASSWORD_DEFAULT),
      "firstName"     => $this->firstName,
      "lastName"      => $this->lastName,
      "middleInitial" => $this->middleInitial,
      "companyName"   => $this->companyName,
      "contactPerson" => $this->contactPerson,
      "warehouseLatitude"  => $this->warehouseLatitude,
      "warehouseLongitude" => $this->warehouseLongitude,
      "businessDoc"   => $this->businessDoc,
    ];

    $answer = (new ControllerCustomer)->ctrSaveCustomer($data);
    echo $answer;
  }
}

$save_customer = new CustomerRegistration();
$save_customer->customerType  = $_POST["customerType"]  ?? 'individual';
$save_customer->password      = $_POST["password"]      ?? '';
$save_customer->email         = $_POST["email"]         ?? '';
$save_customer->phoneNumber   = $_POST["phoneNumber"]   ?? '';
$save_customer->locationID    = $_POST["locationID"]    ?? null;
$save_customer->province      = $_POST["province"]      ?? '';
$save_customer->city          = $_POST["city"]          ?? '';
$save_customer->barangay      = $_POST["barangay"]      ?? '';
$save_customer->street        = $_POST["street"]        ?? '';
$save_customer->houseNumber   = $_POST["houseNumber"]   ?? '';
$save_customer->description   = $_POST["description"]   ?? '';
$save_customer->firstName     = $_POST["firstName"]     ?? '';
$save_customer->lastName      = $_POST["lastName"]      ?? '';
$save_customer->middleInitial = $_POST["middleInitial"] ?? '';
$save_customer->companyName   = $_POST["companyName"]   ?? '';
$save_customer->contactPerson = $_POST["contactPerson"] ?? '';
$save_customer->warehouseLatitude  = $_POST["warehouseLatitude"]  ?? '';
$save_customer->warehouseLongitude = $_POST["warehouseLongitude"] ?? '';
$save_customer->businessDoc   = $_FILES["businessDoc"]  ?? null;

$save_customer->saveCustomer();
