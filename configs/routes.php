<?php
$staffRoutes = [
    'sample',
    'employee-reg',
    'customer-reg',
    'truck-reg',
    'booking-reg',
    'trips',
    'reports',
    'manage-company',
    'manage-employee',
    'manage-truck',
    'logout',
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
    'customer-individual' => $customerRoutes,
    'customer-company' => $customerRoutes,
];
