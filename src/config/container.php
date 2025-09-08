<?php
/**
 * Container Configuration
 * 
 * Dependency Injection Container untuk aplikasi
 */

use App\Services\SmppService;
use App\Services\DatabaseService;
use App\Controllers\SmsController;

// Register services
$container->set(SmppService::class, function() {
    return new SmppService();
});

$container->set(DatabaseService::class, function() {
    return new DatabaseService();
});

$container->set(SmsController::class, function($container) {
    return new SmsController(
        $container->get(SmppService::class),
        $container->get(DatabaseService::class)
    );
});



