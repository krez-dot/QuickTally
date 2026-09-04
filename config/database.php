<?php
/**
 * Database configuration.
 * Reads Railway's MySQL env vars when present (production), falls back
 * to local XAMPP defaults otherwise (local development).
 */
define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_PORT', getenv('MYSQLPORT') ?: '3306');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'quicktally_pos');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
