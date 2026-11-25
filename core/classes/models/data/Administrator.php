<?php

namespace core\classes\models\data;

use core\classes\models as models;
use core\classes\Encryption;

class Administrator extends models\Administrator {

	public function getRecords() {
		$password = getenv('OPENMVC_INITIAL_ADMIN_PASSWORD');
		if ($password === FALSE || $password === '') {
			return [];
		}
		return [
			[
				'type'       => ADMINISTRATOR_TYPE_SUPER,
				'login'      => 'administrator',
				'password'   => Encryption::bcrypt($password, 12),
				'first_name' => 'Administrator',
				'last_name'  => '',
				'email'      => 'admin@openmvc.com',
			]
		];
	}
}
