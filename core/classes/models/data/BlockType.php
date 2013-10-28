<?php

namespace core\classes\models\data;

use core\classes\models as models;

class BlockType extends models\BlockType {

	public function getRecords() {
		return [
			[
				'name' => 'HTML',
			]
		];
	}
}
