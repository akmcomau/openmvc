<?php

namespace core\classes\models\data;

use core\classes\models as models;
use core\classes\Encryption;

class Administrator extends models\Administrator {

	public function getRecords() {
		return [
			[
				'type'       => ADMINISTRATOR_TYPE_SUPER,
				'login'      => 'administrator',
				'password'   => Encryption::bcrypt('admin12', 12),
				'first_name' => 'Administrator',
				'last_name'  => '',
				'email'      => 'admin@openmvc.com',
			]
		];
	}
}
