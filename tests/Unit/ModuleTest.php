<?php

namespace tests\Unit;

use tests\FrameworkTestCase;
use core\classes\Config;
use core\classes\Module;
use tests\fixtures\modules\dummy\Installer;

/**
 * Tests for core\classes\Module: module discovery (globbing modules/*
 * /module.php) and the install/uninstall/enable/disable wrappers that tie
 * a module's Installer class to Config's persisted module lists.
 *
 * Module::getModules()/getBlockTypes() cache their result in a *static*
 * property that lives for the whole PHPUnit process, so every test here
 * must call Module::clearCache() both before (to see a freshly-written
 * fixture module.php) and after (so this fixture module doesn't leak into
 * unrelated tests that call getModules() later in the same run).
 */
class ModuleTest extends FrameworkTestCase {

	const FIXTURE_NAMESPACE = 'tests\fixtures\modules\dummy';

	protected ?string $fixtureModuleDir = NULL;
	protected ?string $tempConfigFile = NULL;

	protected function setUp(): void {
		parent::setUp();
		Module::clearCache();
		Installer::$calls = [];
	}

	protected function tearDown(): void {
		if ($this->fixtureModuleDir && is_dir($this->fixtureModuleDir)) {
			unlink($this->fixtureModuleDir.'/module.php');
			rmdir($this->fixtureModuleDir);
		}
		$this->fixtureModuleDir = NULL;

		if ($this->tempConfigFile && file_exists($this->tempConfigFile)) {
			unlink($this->tempConfigFile);
		}
		$this->tempConfigFile = NULL;
		$_SERVER['OPENMVC_CONFIG_FILE'] = TESTS_ROOT.'/fixtures/config.php';

		Module::clearCache();
		parent::tearDown();
	}

	/**
	 * Drops a real modules/<dir>/module.php file (Module::getModules() globs
	 * an actual project directory, not something injectable via config), so
	 * discovery has something to find. Removed again in tearDown().
	 */
	protected function writeFixtureModule(array $overrides = []): void {
		$module = array_merge([
			'namespace' => self::FIXTURE_NAMESPACE,
			'name'      => 'Dummy Test Module',
			'default_config' => ['setting' => 'value'],
		], $overrides);

		$this->fixtureModuleDir = OPENMVC_ROOT.'/modules/__openmvc_test_fixture__';
		mkdir($this->fixtureModuleDir, 0777, TRUE);
		file_put_contents(
			$this->fixtureModuleDir.'/module.php',
			'<?php $_MODULE = '.var_export($module, TRUE).';'
		);
	}

	/** Point Config at a private, throwaway copy of the fixture config. */
	protected function useTemporaryConfigFile(array $overrides = []): Config {
		$config_array = array_merge([
			'sites' => [
				'test.local' => ['site_id' => 1, 'force_www_subdomain' => false],
			],
		], $overrides);

		$this->tempConfigFile = tempnam(sys_get_temp_dir(), 'openmvc_test_config_');
		file_put_contents($this->tempConfigFile, '<?php $_CONFIG = '.var_export($config_array, TRUE).';');
		$_SERVER['OPENMVC_CONFIG_FILE'] = $this->tempConfigFile;

		$config = new Config();
		$config->setSiteDomain('test.local');
		return $config;
	}

	// -- getModules() discovery --

	public function testGetModulesDiscoversFixtureModule(): void {
		$this->writeFixtureModule();
		$module = new Module($this->makeConfig());

		$modules = $module->getModules();

		$this->assertArrayHasKey(self::FIXTURE_NAMESPACE, $modules);
		$this->assertSame('Dummy Test Module', $modules[self::FIXTURE_NAMESPACE]['name']);
	}

	public function testGetModulesOmitsHiddenModule(): void {
		$this->writeFixtureModule(['hidden' => TRUE]);
		$module = new Module($this->makeConfig());

		$modules = $module->getModules();

		$this->assertArrayNotHasKey(self::FIXTURE_NAMESPACE, $modules);
	}

	public function testGetModulesDefaultsToNotInstalledAndNotEnabled(): void {
		$this->writeFixtureModule();
		$module = new Module($this->makeConfig());

		$modules = $module->getModules();

		$this->assertFalse($modules[self::FIXTURE_NAMESPACE]['installed']);
		$this->assertFalse($modules[self::FIXTURE_NAMESPACE]['enabled']);
	}

	public function testGetModulesMarksInstalledWhenListedInConfigModules(): void {
		$this->writeFixtureModule();
		$config = $this->useTemporaryConfigFile(['modules' => [self::FIXTURE_NAMESPACE]]);
		$module = new Module($config);

		$modules = $module->getModules();

		$this->assertTrue($modules[self::FIXTURE_NAMESPACE]['installed']);
		$this->assertFalse($modules[self::FIXTURE_NAMESPACE]['enabled']);
	}

	public function testGetModulesMarksEnabledWhenPresentInSiteModules(): void {
		$this->writeFixtureModule();
		$config = $this->useTemporaryConfigFile([
			'sites' => [
				'test.local' => [
					'site_id' => 1,
					'force_www_subdomain' => false,
					'modules' => [self::FIXTURE_NAMESPACE => ['setting' => 'value']],
				],
			],
		]);
		$module = new Module($config);

		$modules = $module->getModules();

		$this->assertTrue($modules[self::FIXTURE_NAMESPACE]['enabled']);
	}

	// -- isModuleEnabled() / getEnabledModules() --

	public function testIsModuleEnabledFalseWhenNotEnabled(): void {
		$this->writeFixtureModule();
		$module = new Module($this->makeConfig());

		$this->assertFalse($module->isModuleEnabled(self::FIXTURE_NAMESPACE));
	}

	public function testIsModuleEnabledTrueWhenEnabled(): void {
		$this->writeFixtureModule();
		$config = $this->useTemporaryConfigFile([
			'sites' => [
				'test.local' => [
					'site_id' => 1,
					'force_www_subdomain' => false,
					'modules' => [self::FIXTURE_NAMESPACE => ['setting' => 'value']],
				],
			],
		]);
		$module = new Module($config);

		$this->assertTrue($module->isModuleEnabled(self::FIXTURE_NAMESPACE));
	}

	public function testGetEnabledModulesFiltersToEnabledOnly(): void {
		$this->writeFixtureModule();
		$config = $this->useTemporaryConfigFile();
		$module = new Module($config);

		$this->assertSame([], $module->getEnabledModules());
	}

	// -- install()/uninstall()/enable()/disable(): delegate to the
	// module's Installer class, then persist via Config --

	public function testInstallThrowsForUnknownModule(): void {
		$module = new Module($this->makeConfig());
		$database = $this->makeMockDatabase();

		$this->expectException(\ErrorException::class);
		$module->install('no\such\module', $database);
	}

	public function testInstallCallsInstallerAndPersistsToConfig(): void {
		$this->writeFixtureModule();
		$config = $this->useTemporaryConfigFile();
		$module = new Module($config);
		$database = $this->makeMockDatabase();

		$module->install(self::FIXTURE_NAMESPACE, $database);

		$this->assertSame(['install'], Installer::$calls);
		$reloaded = new Config();
		$this->assertContains(self::FIXTURE_NAMESPACE, $reloaded->modules);
	}

	public function testUninstallCallsInstallerAndPersistsToConfig(): void {
		$this->writeFixtureModule();
		$config = $this->useTemporaryConfigFile(['modules' => [self::FIXTURE_NAMESPACE]]);
		$module = new Module($config);
		$database = $this->makeMockDatabase();

		$module->uninstall(self::FIXTURE_NAMESPACE, $database);

		$this->assertSame(['uninstall'], Installer::$calls);
		$reloaded = new Config();
		$this->assertNotContains(self::FIXTURE_NAMESPACE, $reloaded->modules);
	}

	public function testEnableCallsInstallerAndPersistsToSiteConfig(): void {
		$this->writeFixtureModule();
		$config = $this->useTemporaryConfigFile();
		$module = new Module($config);
		$database = $this->makeMockDatabase();

		$module->enable(self::FIXTURE_NAMESPACE, $database);

		$this->assertSame(['enable'], Installer::$calls);
		$reloaded = new Config();
		$reloaded->setSiteDomain('test.local');
		$this->assertSame(['setting' => 'value'], (array)$reloaded->siteConfig()->modules->{self::FIXTURE_NAMESPACE});
	}

	public function testDisableCallsInstallerAndPersistsToSiteConfig(): void {
		$this->writeFixtureModule();
		$config = $this->useTemporaryConfigFile([
			'sites' => [
				'test.local' => [
					'site_id' => 1,
					'force_www_subdomain' => false,
					'modules' => [self::FIXTURE_NAMESPACE => ['setting' => 'value']],
				],
			],
		]);
		$module = new Module($config);
		$database = $this->makeMockDatabase();

		$module->disable(self::FIXTURE_NAMESPACE, $database);

		$this->assertSame(['disable'], Installer::$calls);
		$reloaded = new Config();
		$reloaded->setSiteDomain('test.local');
		$this->assertArrayNotHasKey(self::FIXTURE_NAMESPACE, (array)$reloaded->siteConfig()->modules);
	}
}
