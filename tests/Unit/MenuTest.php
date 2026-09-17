<?php

namespace tests\Unit;

use tests\FrameworkTestCase;
use core\classes\Menu;
use core\classes\Language;
use core\classes\Template;
use ReflectionClass;

class MenuTest extends FrameworkTestCase {

	protected function makeMenu(): Menu {
		$config = $this->makeConfig();
		$language = new Language($config);
		return new Menu($config, $language);
	}

	public function testAddMenuDataFlatKey(): void {
		$menu = $this->makeMenu();
		$menu->addMenuData('home', ['text' => 'Home']);

		$this->assertSame(['home' => ['text' => 'Home']], $menu->getMenuData());
	}

	public function testAddMenuDataNestedKey(): void {
		$menu = $this->makeMenu();
		$menu->setMenuData(['parent' => ['children' => []]]);
		$menu->addMenuData(['parent', 'children', 'child1'], ['text' => 'Child']);

		$data = $menu->getMenuData();
		$this->assertSame(['text' => 'Child'], $data['parent']['children']['child1']);
	}

	public function testInsertMenuAtEnd(): void {
		$menu = $this->makeMenu();
		$menu->setMenuData(['a' => ['text' => 'A'], 'b' => ['text' => 'B']]);
		$menu->insert_menu(NULL, 'z', ['text' => 'Z']);

		$this->assertSame(['z', 'a', 'b'], array_keys($menu->getMenuData()));
	}

	public function testInsertMenuAfterExistingKey(): void {
		$menu = $this->makeMenu();
		$menu->setMenuData(['a' => ['text' => 'A'], 'b' => ['text' => 'B']]);
		$menu->insert_menu('a', 'x', ['text' => 'X']);

		$this->assertSame(['a', 'x', 'b'], array_keys($menu->getMenuData()));
	}

	// -- XSS regression tests: url/class/text attributes must be escaped
	// wherever they're printed as raw HTML. --

	public function testEchoBootstrapMenuEscapesUrlAttribute(): void {
		$menu = $this->makeMenu();
		$this->injectFakeTemplates($menu);

		$menu->setMenuData([
			'evil' => [
				'url' => '"><script>alert(1)</script>',
				'text' => 'Evil',
			],
		]);

		$output = $this->captureOutput(fn() => $menu->echoBootstrapMenu());

		$this->assertStringNotContainsString('<script>alert(1)</script>', $output);
		$this->assertStringContainsString('&lt;script&gt;', $output);
	}

	public function testEchoBootstrapMenuEscapesClassAttribute(): void {
		$menu = $this->makeMenu();
		$this->injectFakeTemplates($menu);

		$menu->setMenuData([
			'evil' => [
				'url' => '/safe',
				'text' => 'Label',
				'class' => '"><script>alert(2)</script>',
			],
		]);

		$output = $this->captureOutput(fn() => $menu->echoBootstrapMenu());

		$this->assertStringNotContainsString('<script>alert(2)</script>', $output);
	}

	public function testEchoBootstrapMenu2EscapesTextAttribute(): void {
		$menu = $this->makeMenu();
		$this->injectFakeTemplates($menu);

		$menu->setMenuData([
			'evil' => [
				'controller' => 'Root',
				'method' => 'index',
				'url' => '/safe',
				'text' => '<script>alert(3)</script>',
			],
		]);

		$output = $this->captureOutput(fn() => $menu->echoBootstrapMenu2());

		$this->assertStringNotContainsString('<script>alert(3)</script>', $output);
	}

	protected function captureOutput(callable $fn): string {
		ob_start();
		$fn();
		return ob_get_clean();
	}

	/**
	 * echoBootstrapMenu()/echoBootstrapMenu2() render via $this->template
	 * and $this->template_dropdown, which are normally real Template
	 * objects pointed at a theme file on disk (via loadMenu()/setTemplate()).
	 * Inject stand-ins whose render() just returns the '[% text %]'
	 * placeholder Menu itself substitutes into, so these tests don't depend
	 * on any theme files existing on disk.
	 */
	protected function injectFakeTemplates(Menu $menu): void {
		$fakeTemplate = $this->createMock(Template::class);
		$fakeTemplate->method('render')->willReturn('[% text %]');

		$reflection = new ReflectionClass($menu);
		foreach (['template', 'template_dropdown'] as $property) {
			$prop = $reflection->getProperty($property);
			$prop->setAccessible(true);
			$prop->setValue($menu, $fakeTemplate);
		}
	}
}
