<?php
$adminRoutes = [
    'sample',
    'employee-reg',
    'customer-reg',
    'truck-reg',
    'booking-reg',
    'trips',
    'reports',
    'sales',
    'manage-company',
    'manage-employee',
    'manage-tariff',
    'manage-truck',
    'logout',
    'signup',
    'trip-details'
];

$driverRoutes = [
    'trips',
    'logout',
    'driverDashboard',
    'trip-details'
];

$assistantRoutes = [
    'sample',
    'trips',
    'logout',
    'assistantDashboard',
    'trip-details',
];

$customerRoutes = [
    'sample',
    'customer-individual/profile',
    'customer-individual/bookings',
    'booking-reg',
    'logout',
    'bookings',
    'booking-details',
];

return [
    'admin' => $adminRoutes,
    'assistant' => $assistantRoutes,
    'driver' => $driverRoutes,
    'customer' => $customerRoutes,
    'customer-individual' => $customerRoutes,
    'customer-company' => $customerRoutes,
];
