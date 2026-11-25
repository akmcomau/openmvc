<?php

namespace tests\Unit;

use core\classes\models\data\Administrator;
use tests\FrameworkTestCase;

class AdministratorSeedTest extends FrameworkTestCase {
	public function testBootstrapAccountRequiresAnExplicitCredential(): void {
		$previous = getenv('OPENMVC_INITIAL_ADMIN_PASSWORD');
		$seed = new Administrator($this->makeConfig(), $this->makeMockDatabase());
		try {
			putenv('OPENMVC_INITIAL_ADMIN_PASSWORD');
			$this->assertSame([], $seed->getRecords());
			$password = bin2hex(random_bytes(16));
			putenv('OPENMVC_INITIAL_ADMIN_PASSWORD='.$password);
			$this->assertTrue(password_verify($password, $seed->getRecords()[0]['password']));
		}
		finally {
			putenv($previous === FALSE ? 'OPENMVC_INITIAL_ADMIN_PASSWORD' : 'OPENMVC_INITIAL_ADMIN_PASSWORD='.$previous);
		}
	}
}
