<?php

namespace tests\fixtures\models;

use core\classes\Model;

/**
 * A minimal Model subclass with a site_id column, used to exercise
 * Controller::siteProtection()/allowedSiteIDs() - the multi-tenant
 * (per-site) admin scoping boundary - without needing a real database.
 */
class SiteScopedThing extends Model {

	protected $table = 'site_scoped_thing';
	protected $primary_key = 'site_scoped_thing_id';

	protected $columns = [
		'site_scoped_thing_id' => [
			'data_type'      => 'bigint',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'site_id' => [
			'data_type'    => 'bigint',
			'null_allowed' => FALSE,
		],
	];

	/** Returns whatever's been seeded into the 'related' object-cache slot (or NULL). */
	public function getRelated() {
		return $this->getObjectCache('related');
	}
}
