<?php

/**
 * PHP resolves an unqualified function call from inside a namespace by
 * looking for a function of that name in the SAME namespace before falling
 * back to the global one. Declaring mail() here, in the same namespace as
 * core\classes\Email, lets EmailTest intercept exactly the real mail()
 * call Email::send() makes (so no test ever sends a real email) and
 * inspect what was actually handed to it.
 *
 * Loaded via require_once (not autoloading) because its namespace has to
 * match core\classes, not this file's own location under tests/.
 */

namespace core\classes;

class EmailMailStub {
	/** @var array|null Captures the last call's arguments, or NULL if none captured yet. */
	public static $lastCall = NULL;

	/** @var bool What mail() should report back to the caller. */
	public static $returnValue = TRUE;

	public static function reset(): void {
		self::$lastCall = NULL;
		self::$returnValue = TRUE;
	}
}

function mail($to, $subject, $message, $headers = '', $extra_parameters = '') {
	EmailMailStub::$lastCall = [
		'to' => $to,
		'subject' => $subject,
		'message' => $message,
		'headers' => $headers,
	];
	return EmailMailStub::$returnValue;
}
