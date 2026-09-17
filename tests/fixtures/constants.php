<?php

/**
 * Minimal constants fixture so the framework test suite never depends on
 * any site's own core/config/constants.php (which, in a checked-out site,
 * may be a symlink to real, site-specific values).
 *
 * core/Constants.php already defines DS, CUSTOMER_TYPE_NORMAL,
 * ADMINISTRATOR_TYPE_SUPER and the BCRYPT_IMPLEMENTATION_* constants itself,
 * so only the constants that a site's constants.php would otherwise be
 * expected to provide (and that core/classes/Model.php's APC caching code
 * references) are defined here.
 */
define('CACHE_TTL', 600);
define('CACHE_GZIP_ENABLE', TRUE);
define('CACHE_GZIP_THRESHOLD', 1000);
define('CACHE_GZIP_LEVEL', 1);
