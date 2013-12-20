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
				'abbrev' => 'QLD',
				'country_id' => $australia->id,
			],
			[
				'name' => 'New South Wales',
				'abbrev' => 'NSW',
				'country_id' => $australia->id,
			],
			[
				'name' => 'Victoria',
				'abbrev' => 'VIC',
				'country_id' => $australia->id,
			],
			[
				'name' => 'Tasmania',
				'abbrev' => 'TAS',
				'country_id' => $australia->id,
			],
			[
				'name' => 'Northern Territory',
				'abbrev' => 'NT',
				'country_id' => $australia->id,
			],
			[
				'name' => 'South Australia',
				'abbrev' => 'SA',
				'country_id' => $australia->id,
			],
			[
				'name' => 'Western Australia',
				'abbrev' => 'WA',
				'country_id' => $australia->id,
			],
			[
				'name' => 'Australian Capital Territory',
				'abbrev' => 'ACT',
				'country_id' => $australia->id,
			],
		];
	}
}
