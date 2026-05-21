<?php
$staffRoutes = [
    'sample',
    'employee-reg',
    'customer-reg',
    'truck-reg',
    'booking-reg',
    'trips',
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
    'customerindividual' => $customerRoutes,
    'customercompany' => $customerRoutes,
];
