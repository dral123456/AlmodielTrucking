<?php
$adminRoutes = [
    'sample',
    'employee-reg',
    'customer-reg',
    'truck-reg',
    'booking-reg',
    'trips',
    'incident-reports',
    'reports',
    'sales',
    'manage-company',
    'manage-employee',
    'manage-tariff',
    'manage-truck',
    'truck-details',
    'logout',
    'signup',
    'trip-details',
    'user-profile',
];

$driverRoutes = [
    'trips',
    'logout',
    'driverDashboard',
    'trip-details',
    'user-profile',
];

$assistantRoutes = [
    'sample',
    'trips',
    'logout',
    'assistantDashboard',
    'trip-details',
    'user-profile',
];

$customerRoutes = [
    'sample',
    'booking-reg',
    'logout',
    'bookings',
    'booking-details',
    'user-profile',
];

return [
    'admin' => $adminRoutes,
    'assistant' => $assistantRoutes,
    'driver' => $driverRoutes,
    'customer' => $customerRoutes,
    'customer-individual' => $customerRoutes,
    'customer-company' => $customerRoutes,
];
