<?php

// shortcuts to built in contants
define('DS', DIRECTORY_SEPARATOR);

// Customer constants
define('CUSTOMER_TYPE_NORMAL', 1);

// Administrator constants
define('ADMINISTRATOR_TYPE_SUPER',  1);

// BCrypt implmentations
define('BCRYPT_IMPLEMENTATION_DEFAULT', 1);
define('BCRYPT_IMPLEMENTATION_2A', 1);

if (isset($_SERVER['OPENMVC_CONSTANTS_FILE'])) {
	require($_SERVER['OPENMVC_CONSTANTS_FILE']);
}
elseif (file_exists(__DIR__.'/config/constants.php')) {
	require(__DIR__.'/config/constants.php');
}

// define which bcrypt implementation to use
if (!defined('BCRYPT_IMPLEMENTATION')) define('BCRYPT_IMPLEMENTATION', BCRYPT_IMPLEMENTATION_DEFAULT);
