<?php
$staffRoutes = [
    'sample',
    'employee-reg',
    'customer-reg',
    'truck-reg',
    'booking-reg',
    'trips',
    'driver-trips',
    'reports',
    'sales',
    'manage-company',
    'manage-employee',
    'manage-tariff',
    'manage-truck',
    'logout',
    'signup',
];

$customerRoutes = [
    'sample',
    'customer-individual/profile',
    'customer-individual/bookings',
    'booking-reg',
    'logout',
];

return [
    'admin' => $staffRoutes,
    'employee' => $staffRoutes,
    'assistant' => $staffRoutes,
    'driver' => $staffRoutes,
    'customer' => $customerRoutes,
    'customerindividual' => $customerRoutes,
    'customercompany' => $customerRoutes,
];
