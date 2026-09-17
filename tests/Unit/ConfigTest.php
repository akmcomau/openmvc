<?php

namespace tests\Unit;

use tests\FrameworkTestCase;
use core\classes\Config;
use core\classes\exceptions\ConfigException;
use core\classes\exceptions\DomainRedirectException;

class ConfigTest extends FrameworkTestCase {

	protected ?string $tempConfigFile = NULL;

	protected function tearDown(): void {
		if ($this->tempConfigFile && file_exists($this->tempConfigFile)) {
			unlink($this->tempConfigFile);
		}
		$this->tempConfigFile = NULL;
		// Restore (never just unset) the fixture path every other test
		// relies on, so a leftover override can't leak into the next test
		// and silently fall back to core/config/config.php - which, in a
		// checked-out site, may be a symlink to that site's real config.
		$_SERVER['OPENMVC_CONFIG_FILE'] = TESTS_ROOT.'/fixtures/config.php';
		parent::tearDown();
	}

	/** Point Config at a private, throwaway copy of the fixture config. */
	protected function useTemporaryConfigFile(array $extra_sites = []): string {
		$config_array = [
			'sites' => array_merge([
				'test.local' => ['site_id' => 1, 'force_www_subdomain' => false],
			], $extra_sites),
		];

		$this->tempConfigFile = tempnam(sys_get_temp_dir(), 'openmvc_test_config_');
		file_put_contents($this->tempConfigFile, '<?php $_CONFIG = '.var_export($config_array, TRUE).';');
		$_SERVER['OPENMVC_CONFIG_FILE'] = $this->tempConfigFile;

		return $this->tempConfigFile;
	}

	public function testSiteDomainResolvesConfiguredSite(): void {
		$config = $this->makeConfig();
		$this->assertSame(self::TEST_DOMAIN, $config->getSiteDomain());
	}

	public function testSiteConfigInheritsDefaultSiteValues(): void {
		$config = $this->makeConfig();
		// 'records_per_page' is never set in the fixture, so it must come
		// from core/config/default_config.php's 'default_site' block.
		$this->assertSame(20, $config->siteConfig()->records_per_page);
	}

	public function testUnknownDomainThrowsConfigException(): void {
		$config = new Config();
		$this->expectException(ConfigException::class);
		$config->setSiteDomain('no-such-site.example');
	}

	public function testWwwSubdomainRedirectsWhenForced(): void {
		$this->useTemporaryConfigFile(['forced.local' => ['site_id' => 2, 'force_www_subdomain' => true]]);
		$config = new Config();

		$this->expectException(DomainRedirectException::class);
		$config->setSiteDomain('forced.local');
	}

	public function testWwwPrefixedHostResolvesToSameSite(): void {
		$this->useTemporaryConfigFile(['forced.local' => ['site_id' => 2, 'force_www_subdomain' => true]]);
		$config = new Config();

		$config->setSiteDomain('www.forced.local');
		$this->assertSame('forced.local', $config->getSiteDomain());
	}

	public function testUpdateSiteConfigParam(): void {
		$config = $this->makeConfig();
		$config->updateSiteConfigParam('custom_value', 'hello');
		$this->assertSame('hello', $config->siteConfig()->custom_value);
	}

	public function testModuleConfigReturnsNullForUnknownModuleOnAFreshSite(): void {
		// Config::moduleConfig()'s docblock promises a ConfigException when
		// the module isn't configured, but on a site where no module has
		// ever been enabled, siteConfig()->modules is an empty array
		// (default_site's default), and PHP's array/undefined-property
		// access here is a warning, not a catchable Exception - so nothing
		// actually throws. This documents the real current behaviour rather
		// than the promised one.
		$config = $this->makeConfig();
		$this->assertSame([], (array)$config->siteConfig()->modules);

		$result = @$config->moduleConfig('no_such_module');
		$this->assertNull($result);
	}

	public function testModuleConfigReturnsConfiguredModuleData(): void {
		$this->useTemporaryConfigFile();
		$config = new Config();
		$config->setSiteDomain('test.local');
		$config->enableModule(['namespace' => 'my_module', 'default_config' => ['setting' => 'value']]);

		$reloaded = new Config();
		$reloaded->setSiteDomain('test.local');

		$this->assertSame(['setting' => 'value'], (array)$reloaded->moduleConfig('my_module'));
	}

	public function testIsHttpsFalseByDefault(): void {
		unset($_SERVER['HTTPS']);
		$_SERVER['SERVER_PORT'] = 80;
		$config = $this->makeConfig();
		$this->assertFalse($config->isHttps());
	}

	public function testIsHttpsTrueWhenHttpsServerVarSet(): void {
		$_SERVER['HTTPS'] = 'on';
		$config = $this->makeConfig();
		$this->assertTrue($config->isHttps());
		unset($_SERVER['HTTPS']);
	}

	// -- config-file mutators: verify the atomic write + locking fix does
	// not corrupt data, and that changes are actually persisted to disk. --

	public function testInstallModuleAddsModuleToConfigFile(): void {
		$filename = $this->useTemporaryConfigFile();
		$config = new Config();
		$config->setSiteDomain('test.local');

		$config->installModule(['namespace' => 'my_module']);

		// Re-load a fresh Config instance to prove it was actually written
		// to disk, not just held in memory.
		$reloaded = new Config();
		$this->assertContains('my_module', $reloaded->modules);

		// The file must still be valid, parseable PHP after the write.
		$this->assertFileExists($filename);
	}

	public function testInstallModuleIsIdempotent(): void {
		$this->useTemporaryConfigFile();
		$config = new Config();
		$config->setSiteDomain('test.local');

		$config->installModule(['namespace' => 'my_module']);
		$config->installModule(['namespace' => 'my_module']);

		$reloaded = new Config();
		$this->assertSame(1, count(array_keys($reloaded->modules, 'my_module')));
	}

	public function testUninstallModuleRemovesModuleFromConfigFile(): void {
		$this->useTemporaryConfigFile();
		$config = new Config();
		$config->setSiteDomain('test.local');
		$config->installModule(['namespace' => 'my_module']);

		$config->uninstallModule(['namespace' => 'my_module']);

		$reloaded = new Config();
		$this->assertNotContains('my_module', $reloaded->modules);
	}

	public function testEnableModuleAddsModuleToSiteConfig(): void {
		$this->useTemporaryConfigFile();
		$config = new Config();
		$config->setSiteDomain('test.local');

		$config->enableModule(['namespace' => 'my_module', 'default_config' => ['setting' => 'value']]);

		$reloaded = new Config();
		$reloaded->setSiteDomain('test.local');
		$this->assertSame(['setting' => 'value'], (array)$reloaded->siteConfig()->modules->my_module);
	}

	public function testDisableModuleRemovesModuleFromSiteConfig(): void {
		$this->useTemporaryConfigFile();
		$config = new Config();
		$config->setSiteDomain('test.local');
		$config->enableModule(['namespace' => 'my_module', 'default_config' => ['setting' => 'value']]);

		$config->disableModule(['namespace' => 'my_module']);

		$reloaded = new Config();
		$reloaded->setSiteDomain('test.local');
		$this->assertArrayNotHasKey('my_module', (array)$reloaded->siteConfig()->modules);
	}

	public function testSetSiteConfigWritesConfigAtomically(): void {
		$filename = $this->useTemporaryConfigFile();
		$config = new Config();

		$new_config = ['sites' => ['test.local' => ['site_id' => 1, 'force_www_subdomain' => false, 'name' => 'Updated Name']]];
		$config->setSiteConfig($new_config);

		// The write is temp-file-then-rename, so the file must never be
		// observed empty/truncated, and must always be valid PHP.
		$this->assertFileExists($filename);
		$reloaded = new Config();
		$reloaded->setSiteDomain('test.local');
		$this->assertSame('Updated Name', $reloaded->siteConfig()->name);

		// No stray .tmp.* files should be left behind alongside it.
		$leftovers = glob($filename.'.tmp.*');
		$this->assertSame([], $leftovers);
	}

	public function testConcurrentInstallModuleCallsDoNotLoseWrites(): void {
		// Regression test for the missing-lock bug: two "concurrent"
		// read-modify-write cycles (simulated sequentially here, since we
		// can't fork threads in a unit test, but each call re-reads the
		// file from disk exactly as two real overlapping requests would)
		// must both survive - not overwrite each other via a stale
		// in-memory copy.
		$this->useTemporaryConfigFile();

		$config_a = new Config();
		$config_a->setSiteDomain('test.local');
		$config_b = new Config();
		$config_b->setSiteDomain('test.local');

		$config_a->installModule(['namespace' => 'module_a']);
		$config_b->installModule(['namespace' => 'module_b']);

		$reloaded = new Config();
		$this->assertContains('module_a', $reloaded->modules);
		$this->assertContains('module_b', $reloaded->modules);
	}
}
