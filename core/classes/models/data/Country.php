<?php

namespace core\classes\models\data;

use core\classes\models as models;
use core\classes\Encryption;

class Country extends models\Country {

	public function getRecords() {
		return [
			[
				'name' => 'Australia',
				'code' => 'AU',
			]
		];
	}
}
