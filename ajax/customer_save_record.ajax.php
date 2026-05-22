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

  // Account
  public $password;

  public function saveCustomer() {
    $data = [
      "customerType"  => $this->customerType,
      "email"         => $this->email,
      "phoneNumber"   => $this->phoneNumber,
      "locationID"    => $this->locationID,
      "password"      => password_hash($this->password, PASSWORD_DEFAULT),
      "firstName"     => $this->firstName,
      "lastName"      => $this->lastName,
      "middleInitial" => $this->middleInitial,
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
$save_customer->firstName     = $_POST["firstName"]     ?? '';
$save_customer->lastName      = $_POST["lastName"]      ?? '';
$save_customer->middleInitial = $_POST["middleInitial"] ?? '';

$save_customer->saveCustomer();