# Almodiel Trucking Services System Documentation

## Document Information

| Item | Details |
|---|---|
| System Name | Almodiel Trucking Services |
| System Type | Web-based trucking, booking, delivery, billing, and operations management system |
| Main Technology | PHP, MySQL/MariaDB, JavaScript, Bootstrap, Leaflet maps |
| Database Source | `almodieltrucking.sql` |
| Documentation Folder | `docs/` |

## Table Of Contents

1. System Overview
2. User Roles
3. System Architecture
4. ERD
5. Data Dictionary
6. System Modules
7. User Interfaces
8. Core Workflows
9. Business Rules
10. Reports And Outputs
11. Notes And Recommendations

## 1. System Overview

Almodiel Trucking Services is a web-based logistics management system for handling customer registration, company/customer records, booking registration, cargo information, truck assignment, driver and assistant assignment, delivery status updates, incident reporting, sales monitoring, expenses, salary records, tariff rates, and truck fuel/mileage tracking.

The system supports multiple user roles. Admin users can manage records, monitor operations, update trips, review sales and reports, and handle incident reports. Drivers can view assigned trips, update delivery progress, submit incident reports, and view salary records. Customers can create bookings and view their booking details.

The system uses location records with coordinates to display pickup and destination points on maps. Trip information is organized using `booking.tripID`. There is no separate `trip` table in the current database. A trip is a logical group of one or more booking records that share the same `tripID`.

## 2. User Roles

| Role | Description | Main Access |
|---|---|---|
| Admin | Full operations user who manages system records and reports. | Dashboard, registrations, booking, trips, management, sales, reports, incident reports |
| Driver | Delivery user assigned to trips. | My Trips, Trip Details, My Salary, incident submission |
| Assistant | Crew user assigned to trips. | Trip visibility and assistant dashboard |
| Customer Individual | Customer who can register and submit bookings. | Booking registration, own bookings, booking details |
| Customer Company | Company customer account. | Booking registration and own booking records |

## 3. System Architecture

The project follows a simple PHP MVC-style structure:

| Layer | Folder | Responsibility |
|---|---|---|
| Routes | `configs/routes.php` | Defines which routes are accessible by each role |
| Module Map | `configs/module-paths.php` | Maps route names to PHP view modules |
| Views | `views/modules/` | Page UI templates |
| JavaScript | `views/js/` | Client-side behavior, AJAX calls, maps, form handling |
| Controllers | `controllers/` | Receives requests from views/AJAX and calls models |
| Models | `models/` | Database queries, inserts, updates, and business logic |
| AJAX | `ajax/` | Async endpoints for saving records, updating statuses, and fetching data |
| Database | `almodieltrucking.sql` | MySQL/MariaDB schema and sample data |

## 4. ERD

The ERD documentation is available in the following files:

| ERD File | Purpose |
|---|---|
| [ERD.md](ERD.md) | Main ERD notes and relationship summary |
| [ERD-Crows-Foot.md](ERD-Crows-Foot.md) | Crow's foot notation ERD using Mermaid |
| [ERD-Access-Style.html](ERD-Access-Style.html) | MS Access-style table relationship view |
| [ERD-Access-Style-wide.png](ERD-Access-Style-wide.png) | Screenshot image of the Access-style ERD |

### Important ERD Note

The database does not currently have a physical `trip` table. Trips are grouped by `booking.tripID`. This means tables such as `tripemployee`, `trucktripusage`, `staffsalary`, `expenses`, `sales`, `deliverycharge`, and `incidentreport` reference the trip using a logical `tripID` relationship.

### Main Relationship Summary

| Parent Table | Child Table | Relationship |
|---|---|---|
| `location` | `customer` | One location can be assigned to many customers |
| `customer` | `booking` | One customer can have many bookings |
| `location` | `booking` | Locations are used as pickup and destination records |
| `booking` | `cargo` | One booking can contain many cargo records |
| `booking` | `sales` | One booking can generate billing/sales records |
| `customer` | `tariff` | One customer can have many tariff rates |
| `employee` | `tripemployee` | Employees are assigned as trip crew |
| `truck` | `tripemployee` | Trucks are assigned to trip crews |
| `truck` | `truckfuellog` | One truck can have many fuel logs |
| `truck` | `trucktripusage` | Truck usage is recorded per completed trip |
| `employee` | `incidentreport` | Drivers submit incidents and admins review them |
| `booking` | `staffsalary` | Completed trips can generate staff salary records |

## 5. Data Dictionary

### 5.1 `booking`

Stores customer booking records and acts as the central operational table.

| Field | Type | Key | Description |
|---|---|---|---|
| `bookingID` | int | PK | Unique booking identifier |
| `customerID` | int | FK/logical | Customer who owns the booking |
| `storeName` | varchar(150) |  | Store/customer display name for the booking |
| `pickupLocationID` | int | FK/logical | Pickup location reference |
| `destinationLocationID` | int | FK/logical | Destination location reference |
| `tripID` | int | Logical group | Groups one or more bookings into a trip |
| `pickupDateTime` | datetime |  | Scheduled pickup date and time |
| `price` | double |  | Booking price |
| `createdBy` | int | FK/logical | Employee/admin who created the booking |
| `dateCreated` | datetime |  | Record creation date |
| `status` | varchar(20) |  | Delivery status such as pending, in-transit, stopover, completed |

### 5.2 `cargo`

Stores cargo items attached to a booking.

| Field | Type | Key | Description |
|---|---|---|---|
| `cargoID` | int | PK | Unique cargo identifier |
| `bookingID` | int | FK/logical | Related booking |
| `cargoType` | varchar(100) |  | Type/name of cargo |
| `quantity` | int |  | Cargo quantity |
| `condition` | varchar(100) |  | Cargo condition |
| `description` | text |  | Cargo description |
| `specialHandling` | text |  | Special handling instructions |

### 5.3 `customer`

Stores individual and company customer accounts.

| Field | Type | Key | Description |
|---|---|---|---|
| `id` | int | PK | Unique customer identifier |
| `customerType` | enum |  | `individual` or `company` |
| `customerFName` | varchar(100) |  | Customer first name or company name part |
| `customerLName` | varchar(50) |  | Customer last name |
| `customerMI` | varchar(1) |  | Middle initial |
| `contactPerson` | varchar(100) |  | Company contact person |
| `email` | varchar(100) |  | Customer email |
| `phoneNumber` | varchar(11) |  | Customer phone number |
| `province` | varchar(50) |  | Customer province |
| `warehouseLatitude` | double |  | Warehouse latitude |
| `warehouseLongitude` | double |  | Warehouse longitude |
| `companyDocument` | varchar(255) |  | Uploaded company document |
| `password` | varchar(255) |  | Customer password |
| `dateRegistered` | date |  | Registration date |
| `status` | enum |  | active or inactive |
| `locationID` | int | FK | Linked location record |

### 5.4 `deliverycharge`

Stores hauling and other delivery charges linked to bookings/trips.

| Field | Type | Key | Description |
|---|---|---|---|
| `deliveryChargeID` | int | PK | Unique charge identifier |
| `bookingID` | int | FK/logical | Related booking |
| `tripID` | int | Logical group | Related trip group |
| `chargeType` | enum |  | hauling or others |
| `amount` | double |  | Charge amount |
| `notes` | text |  | Charge notes |
| `createdBy` | int | FK/logical | User who created the charge |
| `dateCreated` | datetime |  | Charge creation date |

### 5.5 `employee`

Stores admin, driver, and assistant records.

| Field | Type | Key | Description |
|---|---|---|---|
| `id` | int | PK | Unique employee identifier |
| `empFName` | varchar(50) |  | First name |
| `empLName` | varchar(50) |  | Last name |
| `empMI` | varchar(1) |  | Middle initial |
| `empSuffix` | varchar(10) |  | Name suffix |
| `empBirthDate` | date |  | Birth date |
| `empPhoneNumber` | varchar(20) |  | Phone number used for login |
| `empEmail` | varchar(100) |  | Employee email |
| `empType` | enum |  | driver, assistant, or admin |
| `empStatus` | enum |  | active or inactive |
| `dateCreated` | datetime |  | Employee registration date |
| `empPassword` | varchar(255) |  | Login password |
| `licenseNumber` | varchar(50) |  | Driver license number |
| `licenseExpire` | varchar(50) |  | License expiration |
| `licenseImage` | varchar(255) |  | Uploaded license image |

### 5.6 `expenses`

Stores expenses for trucks, trips, employees, and general operations.

| Field | Type | Key | Description |
|---|---|---|---|
| `expenseID` | int | PK | Unique expense identifier |
| `expenseDate` | date |  | Expense date |
| `category` | enum |  | fuel, maintenance, salary, toll, parking, repair, office, other |
| `amount` | double |  | Expense amount |
| `description` | text |  | Expense details |
| `truckID` | int | FK | Related truck |
| `empID` | int | FK | Related employee |
| `tripID` | int | Logical group | Related trip |
| `bookingID` | int | FK/logical | Related booking |
| `referenceNo` | varchar(100) |  | Receipt/reference number |
| `receiptImage` | varchar(255) |  | Uploaded receipt image |
| `status` | enum |  | pending, approved, paid, cancelled |
| `createdBy` | int | FK | User who created the expense |
| `dateCreated` | datetime |  | Record creation date |

### 5.7 `incidentreport`

Stores incident reports submitted by drivers and reviewed by admins.

| Field | Type | Key | Description |
|---|---|---|---|
| `incidentID` | int | PK | Unique incident identifier |
| `tripID` | int | Logical group | Related trip |
| `bookingID` | int | FK/logical | Related booking, if specific |
| `driverID` | int | FK/logical | Reporting driver |
| `incidentType` | enum |  | accident, breakdown, cargo damage, delay, route issue, customer issue, other |
| `severity` | enum |  | low, medium, high, critical |
| `incidentDateTime` | datetime |  | Incident date/time |
| `locationText` | varchar(255) |  | Incident location text |
| `description` | text |  | Incident description |
| `actionTaken` | text |  | Driver/admin action taken |
| `status` | enum |  | open, reviewing, resolved, dismissed |
| `adminNotes` | text |  | Admin notes |
| `reviewedBy` | int | FK/logical | Reviewing admin |
| `dateSubmitted` | datetime |  | Submission date |
| `dateUpdated` | datetime |  | Last update date |

### 5.8 `location`

Stores reusable location records with coordinates.

| Field | Type | Key | Description |
|---|---|---|---|
| `locationID` | int | PK | Unique location identifier |
| `province` | varchar(100) |  | Province |
| `city` | varchar(100) |  | City |
| `barangay` | varchar(100) |  | Barangay |
| `street` | varchar(100) |  | Street |
| `description` | text |  | Full address or notes |
| `latitude` | double |  | Map latitude |
| `longitude` | double |  | Map longitude |

### 5.9 `sales`

Stores billing and sales records generated from completed bookings.

| Field | Type | Key | Description |
|---|---|---|---|
| `salesID` | int | PK | Unique sales identifier |
| `bookingID` | int | FK | Related booking |
| `tripID` | int | Logical group | Related trip |
| `customerID` | int | FK | Related customer |
| `grossAmount` | double |  | Total billing amount |
| `expenseAmount` | double |  | Expenses deducted |
| `netAmount` | double |  | Net sales amount |
| `paidAmount` | double |  | Amount paid |
| `balanceAmount` | double |  | Remaining balance |
| `customerType` | varchar(20) |  | Customer type |
| `paymentStatus` | varchar(20) |  | unpaid, partial, paid |
| `salesStatus` | varchar(20) |  | Sales record status |
| `dateGenerated` | datetime |  | Sales generation date |
| `datePaid` | datetime |  | Payment date |
| `remarks` | text |  | Notes |

### 5.10 `staffsalary`

Stores salary and allowance records for trip crew.

| Field | Type | Key | Description |
|---|---|---|---|
| `salaryID` | int | PK | Unique salary identifier |
| `empID` | int | FK | Employee being paid |
| `tripID` | int | Logical group | Related trip |
| `creditedBookingID` | int | FK | Booking credited for salary |
| `creditedDistanceKm` | double |  | Distance credited |
| `tripRole` | varchar(50) |  | driver or assistant |
| `payPeriodStart` | date |  | Salary period start |
| `payPeriodEnd` | date |  | Salary period end |
| `payType` | enum |  | daily, weekly, semi-monthly, monthly, trip, allowance, bonus, adjustment |
| `baseRate` | double |  | Base salary/rate |
| `grossPay` | double |  | Gross pay |
| `deductions` | double |  | Deductions |
| `netPay` | double |  | Net pay |
| `datePaid` | datetime |  | Paid date |
| `status` | enum |  | pending, paid, cancelled |
| `remarks` | text |  | Salary remarks |
| `createdBy` | int | FK | Created by employee/admin |
| `dateCreated` | datetime |  | Record creation date |

### 5.11 `tariff`

Stores customer route pricing and fuel subsidy information.

| Field | Type | Key | Description |
|---|---|---|---|
| `tariffID` | int | PK | Unique tariff identifier |
| `customerID` | int | FK | Customer/company owner |
| `branch` | varchar(100) |  | Branch, default Bacolod |
| `origin` | varchar(100) |  | Route origin |
| `destination` | varchar(255) |  | Route destination |
| `distanceKm` | double |  | Route distance |
| `truckType` | varchar(50) |  | Truck type |
| `baseRate` | double |  | Base rate |
| `hasFuelSubsidy` | tinyint |  | Fuel subsidy enabled flag |
| `fuelRangeStart` | double |  | Fuel price range start |
| `fuelRangeEnd` | double |  | Fuel price range end |
| `fuelSubsidy` | double |  | Fuel subsidy amount |
| `status` | enum |  | active or inactive |
| `dateCreated` | datetime |  | Record creation date |

### 5.12 `tripemployee`

Stores crew assignment per trip group.

| Field | Type | Key | Description |
|---|---|---|---|
| `tripEmployeeID` | int | PK | Unique trip employee identifier |
| `tripID` | int | Logical group | Related trip |
| `truckID` | int | FK/logical | Assigned truck |
| `empID` | int | FK/logical | Assigned employee |
| `role` | varchar(50) |  | driver or assistant |
| `dateCreated` | datetime |  | Assignment date |

### 5.13 `truck`

Stores truck records and current truck status.

| Field | Type | Key | Description |
|---|---|---|---|
| `id` | int | PK | Unique truck identifier |
| `plateNumber` | varchar(20) |  | Plate number |
| `type` | varchar(20) |  | Truck type |
| `capacity` | double |  | Truck capacity |
| `fuel` | int |  | Current fuel |
| `mileage` | int |  | Current mileage |
| `brand` | varchar(20) |  | Truck brand |
| `corDocument` | varchar(255) |  | Certificate of registration document |
| `otherDocument` | varchar(255) |  | Other truck document |
| `status` | varchar(20) |  | Truck status |

### 5.14 `truckemployee`

Stores default truck crew assignments.

| Field | Type | Key | Description |
|---|---|---|---|
| `truckEmployeeID` | int | PK | Unique truck crew identifier |
| `truckID` | int | FK | Related truck |
| `empID` | int | FK | Related employee |
| `role` | varchar(50) |  | driver or assistant |
| `dateCreated` | datetime |  | Assignment date |

### 5.15 `truckfuellog`

Stores fuel and odometer log entries for trucks.

| Field | Type | Key | Description |
|---|---|---|---|
| `truckFuelLogID` | int | PK | Unique fuel log identifier |
| `truckID` | int | FK/logical | Related truck |
| `logDate` | datetime |  | Fuel log date |
| `litersAdded` | double |  | Liters added |
| `fuelAfter` | double |  | Fuel after log |
| `odometer` | double |  | Odometer reading |
| `amount` | double |  | Fuel expense amount |
| `station` | varchar(150) |  | Fuel station |
| `referenceNo` | varchar(100) |  | Receipt/reference number |
| `notes` | text |  | Notes |
| `createdBy` | int | FK/logical | User who created log |
| `dateCreated` | datetime |  | Record creation date |

### 5.16 `trucktripusage`

Stores truck mileage/fuel usage after completed trips.

| Field | Type | Key | Description |
|---|---|---|---|
| `truckTripUsageID` | int | PK | Unique usage identifier |
| `tripID` | int | Logical group | Related trip |
| `truckID` | int | FK/logical | Related truck |
| `oneWayDistanceKm` | double |  | One-way trip distance |
| `roundTripDistanceKm` | double |  | Round-trip distance |
| `efficiencyKmPerLiter` | double |  | Estimated truck efficiency |
| `fuelUsed` | double |  | Fuel used |
| `fuelBefore` | double |  | Fuel before trip |
| `fuelAfter` | double |  | Fuel after trip |
| `mileageBefore` | double |  | Mileage before trip |
| `mileageAfter` | double |  | Mileage after trip |
| `dateCreated` | datetime |  | Usage record creation date |

### 5.17 `userrights`

Legacy user rights table.

| Field | Type | Key | Description |
|---|---|---|---|
| `id` | int | PK | Unique record identifier |
| `userid` | varchar(10) |  | Legacy user ID |
| `empid` | varchar(10) |  | Legacy employee ID/code |
| `username` | varchar(20) |  | Legacy username |
| `upassword` | varchar(20) |  | Legacy password |

## 6. System Modules

### 6.1 Authentication And Role Access

The system uses PHP sessions to store login state and role:

| Session Key | Description |
|---|---|
| `loggedIn` | Indicates successful login |
| `id` | Current user/customer/employee ID |
| `role` | Current role such as admin, driver, assistant, customer-individual |
| `empType` | Employee type for staff accounts |

Role-based routes are defined in `configs/routes.php`. Page files are mapped in `configs/module-paths.php`.

### 6.2 Dashboard Module

The admin dashboard summarizes operations such as sales, upcoming trips, truck status, and schedule/calendar information. It also supports notifications for upcoming trips, completed deliveries, and incident reports.

### 6.3 Customer Module

This module handles customer registration, customer records, company data, customer profile information, and customer booking history.

Main tables:

| Table | Purpose |
|---|---|
| `customer` | Customer records |
| `location` | Customer and booking locations |
| `booking` | Customer bookings |

### 6.4 Booking Module

The booking module handles customer selection, pickup date/time, truck and crew assignment, location pins, cargo details, hauling charges, tariff price matching, and saving booking records.

Main tables:

| Table | Purpose |
|---|---|
| `booking` | Booking header and trip group |
| `cargo` | Multiple cargo items |
| `location` | Pickup and destination |
| `deliverycharge` | Hauling and other charges |
| `tripemployee` | Assigned driver/assistants |

### 6.5 Trips And Delivery Module

This module displays trips, route maps, connected bookings, crew, truck, status, and delivery actions. Drivers can update delivery status from pending to in-transit, stopover, and completed.

Main statuses:

| Status | Meaning |
|---|---|
| `pending` | Trip is scheduled but not started |
| `in-transit` | Delivery has started |
| `stopover` | Delivery reached an intermediate stop |
| `completed` | Delivery is delivered/completed |

### 6.6 Truck Management Module

Truck management handles truck registration, truck records, assigned default crew, truck status, fuel logs, mileage, and truck trip usage.

Main tables:

| Table | Purpose |
|---|---|
| `truck` | Truck profile and current status |
| `truckemployee` | Default crew assignment |
| `truckfuellog` | Fuel and odometer logs |
| `trucktripusage` | Usage generated after completed trips |

### 6.7 Employee Management Module

Employee management handles admin, driver, and assistant registration and record management.

Main tables:

| Table | Purpose |
|---|---|
| `employee` | Employee details and credentials |
| `tripemployee` | Trip crew assignments |
| `staffsalary` | Salary records |

### 6.8 Tariff Module

The tariff module stores customer/company route rates, distance, truck type, base rate, fuel ranges, and fuel subsidies. It is used during booking to help calculate booking prices.

### 6.9 Sales Module

The sales module shows completed booking sales, billing status, payment status, grouped payments, revenue trends, expenses, and net income.

Main tables:

| Table | Purpose |
|---|---|
| `sales` | Billing and sales records |
| `expenses` | Deductible operational expenses |
| `booking` | Completed booking source |

### 6.10 Expenses And Reports Module

Reports summarize billing, expenses, staff, and salary records. Expenses include fuel, maintenance, salary, documents, tolls, parking, repair, office, and other categories.

### 6.11 Incident Report Module

Drivers can submit incident reports for assigned trips. Admin users can review, resolve, dismiss, or reopen incident reports. Resolving incidents requires an admin note/action taken.

Main table:

| Table | Purpose |
|---|---|
| `incidentreport` | Driver-submitted incident reports and admin review records |

### 6.12 Notification Module

The admin header shows notifications for:

| Notification Type | Description |
|---|---|
| Upcoming trips | Trips scheduled soon |
| Completed deliveries | Recently completed deliveries |
| Incident reports | Open or reviewing incident reports |

Notifications use browser local storage to track which notifications have been viewed.

## 7. User Interfaces

### 7.1 Public / Landing Page

| Route | File | Explanation |
|---|---|---|
| `landingpage` | `views/modules/landingpage.php` | Public landing page introducing the trucking service and navigation to login/registration |

### 7.2 Authentication Interfaces

| Interface | Explanation |
|---|---|
| Admin Login | Allows admin users to log in using employee credentials |
| Driver Login | Allows drivers to access assigned trips |
| Assistant Login | Allows assistants to access assistant trip views |
| Customer Signup | Allows individual customer registration |

### 7.3 Admin Interfaces

| Route | File | Explanation |
|---|---|---|
| `sample` | `views/modules/sample.php` | Admin dashboard with operational summaries and schedule calendar |
| `employee-reg` | `views/modules/admin/employee-reg.php` | Register drivers, assistants, and admins |
| `customer-reg` | `views/modules/admin/customer-reg.php` | Register company/customer records |
| `truck-reg` | `views/modules/admin/truck-reg.php` | Register trucks and assign default crew |
| `booking-reg` | `views/modules/booking-reg.php` | Create bookings, add cargo, assign truck/crew, pin locations |
| `trips` | `views/modules/trips.php` | View all trips and open trip details |
| `trip-details` | `views/modules/trip-details.php` | View route map, bookings, crew, and update/modify trip details |
| `manage-company` | `views/modules/admin/manage-company.php` | Manage company/customer records |
| `manage-employee` | `views/modules/admin/manage-employee.php` | Manage employee records and view salary-related details |
| `manage-tariff` | `views/modules/admin/manage-tariff.php` | Manage tariff/rate records |
| `manage-truck` | `views/modules/admin/manage-truck.php` | Manage truck records |
| `truck-details` | `views/modules/admin/truck-details.php` | View truck status, mileage, fuel, logs, and usage |
| `sales` | `views/modules/sales.php` | View sales, payments, filters, and trends |
| `reports` | `views/modules/admin/reports.php` | Review billing, expenses, staff, and salary reports |
| `incident-reports` | `views/modules/admin/incident-reports.php` | Review and update incident reports |

### 7.4 Driver Interfaces

| Route | File | Explanation |
|---|---|---|
| `trips` | `views/modules/trips.php` | Driver's assigned trips list |
| `trip-details` | `views/modules/trip-details.php` | Driver trip details, map, and delivery status buttons |
| `driver-salary` | `views/modules/driver/driver-salary.php` | Driver salary records per trip |
| `user-profile` | `views/modules/user-profile.php` | Driver profile page |

### 7.5 Assistant Interfaces

| Route | File | Explanation |
|---|---|---|
| `assistantDashboard` | `views/modules/assistant/assistantDashboard.php` | Assistant trip dashboard |
| `trips` | `views/modules/trips.php` | Assistant-visible assigned trip list |
| `trip-details` | `views/modules/trip-details.php` | Assistant trip detail view |

### 7.6 Customer Interfaces

| Route | File | Explanation |
|---|---|---|
| `booking-reg` | `views/modules/booking-reg.php` | Customer booking form |
| `bookings` | `views/modules/customer-individual/bookings.php` | Customer booking list |
| `booking-details` | `views/modules/customer-individual/booking-details.php` | Detailed view of one booking |
| `user-profile` | `views/modules/user-profile.php` | Customer profile and booking information |

## 8. Core Workflows

### 8.1 Booking Creation Workflow

1. User opens Booking Registration.
2. User selects or identifies the customer.
3. User selects pickup date/time.
4. Admin selects truck, driver, assistants, salary, allowance, price, and hauling charge.
5. User enters one or more cargo items.
6. User pins pickup and destination locations.
7. System validates date, location, truck availability, cargo, crew, and price.
8. System saves booking, cargo, delivery charge, trip crew, and salary records where applicable.
9. Booking appears in Trips.

### 8.2 Delivery Workflow

1. Driver logs in.
2. Driver opens Trips.
3. Driver selects an assigned trip.
4. Driver clicks Start Delivery.
5. Trip status changes to `in-transit`.
6. Driver may mark Stopover.
7. Driver marks Delivered.
8. Trip status changes to `completed`.
9. System can generate sales records and update truck mileage/fuel usage.

### 8.3 Incident Report Workflow

1. Driver opens assigned trip.
2. Driver submits incident report.
3. Admin receives notification.
4. Admin opens Incident Reports.
5. Admin reviews details.
6. Admin enters action taken/admin note.
7. Admin resolves, dismisses, or reopens the report.

### 8.4 Sales And Billing Workflow

1. Trip is completed.
2. System syncs completed booking into sales.
3. Admin reviews sales page.
4. Admin filters sales by date range/customer/status.
5. Admin marks billing/payment status as needed.
6. Reports show billing, expenses, staff salary, and net sales.

### 8.5 Truck Usage Workflow

1. Truck is assigned to a trip.
2. Trip is completed.
3. System estimates round-trip mileage and fuel usage.
4. Truck mileage and fuel values are updated.
5. Fuel/mileage logs can be viewed from truck details.

## 9. Business Rules

| Rule | Description |
|---|---|
| Past booking dates are blocked | Booking pickup date must be today or future date |
| Truck conflict is blocked | Truck cannot be double-booked on conflicting active trips |
| Multiple cargo items are allowed | A booking can have more than one cargo record |
| Trip grouping uses `tripID` | Multiple bookings can belong to one logical trip |
| Driver must start delivery first | Stopover and delivered actions should follow start delivery |
| Driver can only update assigned trips | Driver status updates are limited to trips where driver is assigned |
| Salary generation is tied to crew | Booking can auto-create salary records for driver/assistants |
| Assistant salary is lower by 100 | Assistant salary is computed from driver salary minus PHP 100 |
| Truck usage is round trip | Estimated mileage/fuel uses round-trip calculation |
| Incident resolution requires action note | Admin should fill action taken/admin notes before resolving |

## 10. Reports And Outputs

| Report/Output | Description |
|---|---|
| Billing Statement | Customer billing format based on completed bookings |
| Sales Dashboard | Shows sales, expenses, net income, and trends |
| Reports Page | Billing, expenses, staff list, and salary report |
| Incident Reports | Driver incident reports with status and admin notes |
| Truck Details | Truck status, mileage, fuel, fuel logs, and trip usage |
| Driver Salary | Salary records per trip for the logged-in driver |
| ERD Documentation | Database diagrams and table relationships |

## 11. Notes And Recommendations

1. Create a physical `trip` table in a future version. This would make trip relationships easier to enforce and document.
2. Add foreign keys for logical relationships such as `booking.customerID`, `booking.pickupLocationID`, `booking.destinationLocationID`, `cargo.bookingID`, and `tripemployee.empID`.
3. Normalize legacy `userrights` or remove it if it is no longer used.
4. Keep database migrations in version control so all team members have the same schema.
5. Add audit logs for admin actions such as payment updates, trip modification, incident resolution, and truck updates.
6. Add automated tests for booking creation, status updates, sales sync, and truck usage updates.
