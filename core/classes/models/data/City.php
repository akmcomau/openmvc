<?php

namespace core\classes\models\data;

use core\classes\models as models;
use core\classes\Encryption;

class City extends models\City {

	public function getRecords() {
		$australia = $this->getModel('\core\classes\models\Country')->get(['code' => 'AU']);
		$qld = $this->getModel('\core\classes\models\State')->get([
			'country_id' => $australia->id,
			'name' => 'Queensland',
		]);
		$nsw = $this->getModel('\core\classes\models\State')->get([
			'country_id' => $australia->id,
			'name' => 'New South Wales',
		]);
		$vic = $this->getModel('\core\classes\models\State')->get([
			'country_id' => $australia->id,
			'name' => 'Victoria',
		]);
		$tas = $this->getModel('\core\classes\models\State')->get([
			'country_id' => $australia->id,
			'name' => 'Tasmania',
		]);
		$nt = $this->getModel('\core\classes\models\State')->get([
			'country_id' => $australia->id,
			'name' => 'Northern Territory',
		]);
		$sa = $this->getModel('\core\classes\models\State')->get([
			'country_id' => $australia->id,
			'name' => 'South Australia',
		]);
		$wa = $this->getModel('\core\classes\models\State')->get([
			'country_id' => $australia->id,
			'name' => 'Western Australia',
		]);
		$act = $this->getModel('\core\classes\models\State')->get([
			'country_id' => $australia->id,
			'name' => 'Australian Capital Territory',
		]);

		return [
			[
				'name' => 'Brisbane',
				'country_id' => $australia->id,
				'state_id' => $qld->id,
				'timezone' => 'Australia/Brisbane',
			],
			[
				'name' => 'Sydney',
				'country_id' => $australia->id,
				'state_id' => $nsw->id,
				'timezone' => 'Australia/Sydney',
			],
			[
				'name' => 'Melbourne',
				'country_id' => $australia->id,
				'state_id' => $vic->id,
				'timezone' => 'Australia/Melbourne',
			],
			[
				'name' => 'Hobart',
				'country_id' => $australia->id,
				'state_id' => $tas->id,
				'timezone' => 'Australia/Hobart',
			],
			[
				'name' => 'Darwin',
				'country_id' => $australia->id,
				'state_id' => $nt->id,
				'timezone' => 'Australia/Darwin',
			],
			[
				'name' => 'Adelaide',
				'country_id' => $australia->id,
				'state_id' => $sa->id,
				'timezone' => 'Australia/Adelaide',
			],
			[
				'name' => 'Perth',
				'country_id' => $australia->id,
				'state_id' => $wa->id,
				'timezone' => 'Australia/Perth',
			],
			[
				'name' => 'Canberra',
				'country_id' => $australia->id,
				'state_id' => $act->id,
				'timezone' => 'Australia/Canberra',
			],
		];
	}
}
