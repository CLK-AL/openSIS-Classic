<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Start a session for tests that need it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the base path for includes
define('TEST_ROOT', dirname(__DIR__));

// Load param constants (needed by many tests)
require_once TEST_ROOT . '/functions/ParamLibFnc.php';

// Load month conversion (dependency for date functions)
require_once TEST_ROOT . '/functions/MonthNwSwitchFnc.php';

// Load regex helpers (dependency for clean_param)
require_once TEST_ROOT . '/functions/PragRepFnc.php';

// Load TSV mock (must come before any DB includes to override db_fetch_row/DBQuery)
require_once __DIR__ . '/TsvMock.php';

// Load DBGet (uses our mocked db_fetch_row)
require_once TEST_ROOT . '/functions/DbGetFnc.php';
