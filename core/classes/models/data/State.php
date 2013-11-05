<?php

namespace core\classes\models\data;

use core\classes\models as models;
use core\classes\Encryption;

class State extends models\State {

	public function getRecords() {
		$australia = $this->getModel('\core\classes\models\Country')->get(['code' => 'AU']);
		return [
			[
				'name' => 'Queensland',
				'country_id' => $australia->id,
			],
			[
				'name' => 'New South Wales',
				'country_id' => $australia->id,
			],
			[
				'name' => 'Victoria',
				'country_id' => $australia->id,
			],
			[
				'name' => 'Tasmania',
				'country_id' => $australia->id,
			],
			[
				'name' => 'Northern Territory',
				'country_id' => $australia->id,
			],
			[
				'name' => 'South Australia',
				'country_id' => $australia->id,
			],
			[
				'name' => 'Western Australia',
				'country_id' => $australia->id,
			],
			[
				'name' => 'Australian Capital Territory',
				'country_id' => $australia->id,
			],
		];
	}
}
