<?php
/**
 * SMS Notification Service - Main Entry Point
 * 
 * Sistem jasa SMS notification menggunakan SMPP
 * 
 * @author Your Company
 * @version 1.0.0
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Create container
$container = new Container();

// Configure container
require_once __DIR__ . '/../src/config/container.php';

// Create app
$app = AppFactory::createFromContainer($container);

// Add middleware
require_once __DIR__ . '/../src/middleware/middleware.php';

// Add routes
require_once __DIR__ . '/../src/routes/api.php';

// Run app
$app->run();



