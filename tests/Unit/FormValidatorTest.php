<?php

namespace tests\Unit;

use tests\FrameworkTestCase;
use core\classes\FormValidator;

class FormValidatorTest extends FrameworkTestCase {

	protected function makeValidator(array $inputs = [], array $validators = []): FormValidator {
		$config = $this->makeConfig();
		$request = $this->makeRequest($config);
		return new FormValidator($request, 'test-form', $inputs, $validators);
	}

	// -- isInteger/isFloat/isMoney/isUrl/isDate/isDateTime/isTime/isUrlFragment/isEmail --

	public function testIsInteger(): void {
		$validator = $this->makeValidator();
		$this->assertTrue($validator->isInteger('123'));
		$this->assertTrue($validator->isInteger('-123'));
		$this->assertFalse($validator->isInteger('12.3'));
		$this->assertFalse($validator->isInteger('abc'));
	}

	public function testIsFloat(): void {
		$validator = $this->makeValidator();
		$this->assertTrue($validator->isFloat('12.3'));
		$this->assertTrue($validator->isFloat('-12.3'));
		$this->assertTrue($validator->isFloat('12'));
		$this->assertFalse($validator->isFloat('abc'));
	}

	public function testIsMoney(): void {
		$validator = $this->makeValidator();
		$this->assertTrue($validator->isMoney('12.99'));
		$this->assertTrue($validator->isMoney('0'));
		$this->assertFalse($validator->isMoney('-12.99'));
		$this->assertFalse($validator->isMoney('12.9'));
	}

	public function testIsUrl(): void {
		$validator = $this->makeValidator();
		$this->assertTrue($validator->isUrl('http://some.domain/some/page'));
		$this->assertTrue($validator->isUrl('some.domain'));
		$this->assertFalse($validator->isUrl('not a url'));
	}

	public function testIsDate(): void {
		$validator = $this->makeValidator();
		$this->assertTrue($validator->isDate('2024-01-05'));
		$this->assertFalse($validator->isDate('05/01/2024'));
	}

	public function testIsDateTime(): void {
		$validator = $this->makeValidator();
		$this->assertTrue($validator->isDateTime('2024-01-05 12:00:00'));
		$this->assertFalse($validator->isDateTime('not a datetime'));
	}

	public function testIsTime(): void {
		$validator = $this->makeValidator();
		$this->assertTrue($validator->isTime('12:00'));
		$this->assertFalse($validator->isTime('12:00:00'));
	}

	public function testIsUrlFragment(): void {
		$validator = $this->makeValidator();
		$this->assertTrue($validator->isUrlFragment('some-fragment_123'));
		$this->assertFalse($validator->isUrlFragment('has a space'));
		$this->assertFalse($validator->isUrlFragment('has/../traversal'.'/'));
	}

	public function testIsEmail(): void {
		$validator = $this->makeValidator();
		$this->assertTrue($validator->isEmail('name@domain.com'));
		$this->assertFalse($validator->isEmail('not-an-email'));
	}

	// -- checkFormValue / checkElement --

	public function testCheckFormValueStringRespectsMinAndMaxLength(): void {
		$validator = $this->makeValidator();
		$data = ['type' => 'string', 'min_length' => 3, 'max_length' => 5];

		$this->assertTrue($validator->checkFormValue('field', $data, 'abcd'));
		$this->assertFalse($validator->checkFormValue('field', $data, 'ab'));
		$this->assertFalse($validator->checkFormValue('field', $data, 'abcdef'));
	}

	public function testCheckElementRequiredFieldFailsWhenMissing(): void {
		$validator = $this->makeValidator();
		$data = ['type' => 'string', 'required' => TRUE, 'message' => 'required'];

		$this->assertFalse($validator->checkElement('field', $data, NULL));
		$this->assertSame(['field' => 'required'], $validator->getErrors());
	}

	public function testCheckElementOptionalFieldPassesWhenMissing(): void {
		$validator = $this->makeValidator();
		$data = ['type' => 'string', 'required' => FALSE];

		$this->assertTrue($validator->checkElement('field', $data, NULL));
		$this->assertSame([], $validator->getErrors());
	}

	// -- 'params-equal' validator: regression test for the != -> !== fix --

	public function testParamsEqualValidatorUsesStrictComparison(): void {
		$config = $this->makeValidator();

		$validators = [
			'password1' => [[
				'type'    => 'params-equal',
				'param'   => 'password2',
				'message' => 'mismatch',
			]],
		];
		$request = $this->makeRequest($this->makeConfig(), [], [
			'password1' => '100',
			'password2' => '1e2',
		]);
		$validator = new FormValidator($request, 'test-form', [], $validators);

		$data = ['type' => 'string', 'required' => TRUE];
		$is_valid = $validator->checkElement('password1', $data, '100');

		// '100' and '1e2' are numerically equal (loose ==) but not the same
		// string; with strict !== this must fail to validate as a match.
		$this->assertFalse($is_valid);
		$this->assertSame(['password1' => 'mismatch'], $validator->getErrors());
	}

	public function testParamsEqualValidatorPassesWhenValuesAreIdentical(): void {
		$request = $this->makeRequest($this->makeConfig(), [], [
			'password1' => 'secret123',
			'password2' => 'secret123',
		]);
		$validators = [
			'password1' => [[
				'type'    => 'params-equal',
				'param'   => 'password2',
				'message' => 'mismatch',
			]],
		];
		$validator = new FormValidator($request, 'test-form', [], $validators);

		$data = ['type' => 'string', 'required' => TRUE];
		$this->assertTrue($validator->checkElement('password1', $data, 'secret123'));
		$this->assertSame([], $validator->getErrors());
	}

	// -- full validate() flow --

	public function testValidateReturnsFalseWhenFormNotSubmitted(): void {
		$config = $this->makeConfig();
		$request = $this->makeRequest($config);
		$inputs = ['name' => ['type' => 'string', 'required' => TRUE, 'message' => 'error']];
		$validator = new FormValidator($request, 'my-form', $inputs);

		$this->assertFalse($validator->validate());
	}

	public function testValidateReturnsTrueForValidSubmission(): void {
		$config = $this->makeConfig();
		$request = $this->makeRequest($config, [], [
			'my-form-submit' => 'submit',
			'name' => 'A valid name',
		]);
		$inputs = ['name' => ['type' => 'string', 'required' => TRUE, 'min_length' => 3, 'message' => 'error']];
		$validator = new FormValidator($request, 'my-form', $inputs);

		// validate() combines per-field results with the bitwise '&=' operator,
		// so a passing result comes back as int(1) rather than bool true.
		$this->assertTrue((bool)$validator->validate());
		$this->assertSame([], $validator->getErrors());
	}

	public function testValidateReturnsFalseForInvalidSubmission(): void {
		$config = $this->makeConfig();
		$request = $this->makeRequest($config, [], [
			'my-form-submit' => 'submit',
			'name' => 'ab',
		]);
		$inputs = ['name' => ['type' => 'string', 'required' => TRUE, 'min_length' => 3, 'message' => 'too short']];
		$validator = new FormValidator($request, 'my-form', $inputs);

		$this->assertFalse((bool)$validator->validate());
		$this->assertSame(['name' => 'too short'], $validator->getErrors());
	}
}
