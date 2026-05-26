<?php
$adminRoutes = [
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

$driverRoutes = [
    'sample',
    'trips',
    'logout',
];

$assistantRoutes = [
    'sample',
    'trips',
    'logout',
];

$customerRoutes = [
    'sample',
    'customer-individual/profile',
    'customer-individual/bookings',
    'booking-reg',
    'logout',
    'bookings',
];

return [
    'admin' => $adminRoutes,
    'assistant' => $assistantRoutes,
    'driver' => $driverRoutes,
    'customer' => $customerRoutes,
    'customer-individual' => $customerRoutes,
    'customer-company' => $customerRoutes,
];
