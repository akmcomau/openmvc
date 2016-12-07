<?php

namespace core\classes;

use core\classes\exceptions\FormException;

class FormValidator {

	/**
	 * The request object
	 * @var Request $request
	 */
	protected $request;

	/**
	 * The name of the form
	 * @var string The form's name
	 */
	protected $name;

	/**
	 * The inputs of the form.  This is an array with the key of the elements
	 * being the name of the input, and its value being its vailidation properties.
	 * @code
	 *   $inputs = [
	 *		 'username' => [
	 *           'type' => 'string',
	 *           'required' => TRUE,
	 *           'min_length' => 6,
	 *           'max_length' => 32,
	 *           'message' => 'Enter a username between 6 and 32 characters long',
	 *       ],
	 *       ...
	 *   ]
	 * @endcode
	 *
	 * Valid input types, followed by there extra parameters are:
	 *
	 *    - \b string: For string/text input values, e.g. 'This is a string'
	 *       -# \b min_length: The minimum allowable length
	 *       -# \b max_length: The maximum allowable length
	 *
	 *    - \b integer: For integer input values, e.g. 45
	 *       -# \b min_value: The minimum allowable value
	 *       -# \b max_value: The maximum allowable value
	 *
	 *    - \b float: For floating point/decimal input values, e.g. 123.456
	 *       -# \b min_value: The minimum allowable value
	 *       -# \b max_value: The maximum allowable value
	 *
	 *    - \b money: For currency input values, e.g. 12.99
	 *       -# \b zero_allowed
	 *
	 *    - \b date: For date input values, e.g. 05/10/16
	 *
	 *    - \b time: For time input values, e.g. 12:00:00
	 *
	 *    - \b datetime: For date/time input values, e.g. 05/10/16 12:00:00
	 *
	 *    - \b email: For email input values, e.g. name@domain.com
	 *
	 *    - \b url: For URL input values, e.g. http://some.domain/some/page
	 *
	 *    - \b url-fragment: For URL fragment input values. These are the parts between 2
	 *      forward slashes in the url, can also be thought of as a URL folder or page.
	 *      E.g. in http://www.domain.com/fragment1/fragment2/fragment3
	 *
	 *    - \b date-segements: For date input values, that are seperated into 3 inputs, one
	 *      for the day, month and year.
	 *
	 *    - \b file: For text input values, that refer to a file location: e.g. /home/user/html/index.html
	 *
	 * @var array $inputs
	 */
	protected $inputs = [];

	/**
	 * The validators for the inputs.  This is an array with the key of the elements
	 * being the name of the input, and its value being its vailidation properties.
	 * @code
	 *    $validators = [
	 *      'email' => [
	 *           [
	 *              'type'     => 'function',
	 *              'message'  => $this->language->get('error_email_taken'),
	 *              'function' => function($value) use ($model, $customer_obj, $object) {
	 *                  $customer = $model->getModel($object->customerModelClass);
	 *                  $customer = $customer->get(['email' => $value]);
	 *                  if ($customer && $customer->id != $customer_obj->id) {
	 *                      return FALSE;
	 *                  }
	 *                  else {
	 *                      return TRUE;
	 *                  }
	 *              }
	 *          ],
	 *      ],
	 *      'password1' => [
	 *            [
	 *                'type'    => 'params-equal',
	 *                'param'   => 'password2',
	 *                'message' => $this->language->get('error_password_mismatch'),
	 *            ],
	 *            [
	 *                'type'      => 'regex',
	 *                'regex'     => '\d',
	 *                'modifiers' => '',
	 *                'message'   => $this->language->get('error_password_format'),
	 *            ],
	 *        ],
	 * @endcode
	 *
	 * Valid input types, followed by there extra parameters are:
	 *
	 *    - \b params-equal: Checks to ensure this inputs value matches another
	 *       -# \b param: The input name to check against
	 *
	 *    - \b regex: Checkes the input value against a regular expression
	 *       -# \b regex: The regex string
	 *       -# \b modifiers: The modifiers for the regex
	 *
	 *    - \b function: Checkes the input value against a user defined function
	 *       -# \b function: The function to call, must accept the value as a parameter and return a bool.
	 * @var array $validators
	 */
	protected $validators = [];

	/**
	 * An array containing all the form errors of the form:
	 * @code
	 *    $form_errors = [
	 *        'input_name' => 'error message',
	 *        ...
	 *    ];
	 * @endcode
	 * @var array $form_errors
	 */
	protected $form_errors = [];

	/**
	 * The logger object
	 * @var Logger $logger
	 */
	protected $logger = NULL;

	/**
	 * The notification message from validation
	 * @var string $notification_message
	 */
	protected $notification_message = NULL;

	/**
	 * The notification message type from validation, error, warn, info or success
	 * @var string $notification_type
	 */
	protected $notification_type = NULL;

	/**
	 * Should the submit button on the form be disabled
	 * @var bool $disable_submit_button
	 */
	protected $disable_submit_button = FALSE;

	/**
	 * Suppress the check to ensure the form has been submitted.  The check is that
	 * the name of the submit button exists in the form.  The button must be named
	 * with the form name postfixed with '-submit', e.g. 'form-name-submit'.
	 * @var bool $suppress_submit_check
	 */
	protected $suppress_submit_check = FALSE;

	/**
	 * Constructor
	 * @param $request     \b Request The request object
	 * @param $name        \b string  The name of the form
	 * @param $inputs      \b array   An array containing the inputs for the form
	 * @param $validators  \b array   An array containing the validators for the form
	 */
	public function __construct(Request $request, $name, array $inputs = NULL, array $validators  = NULL) {
		$this->request = $request;
		$this->name = $name;
		$this->logger = Logger::getLogger(get_class($this));

		if ($inputs) {
			$this->inputs = $inputs;
		}
		if ($validators) {
			$this->validators = $validators;
		}
	}

	/**
	 * Sets the flag to suppress the check to ensure the form has been submitted.  The check
	 * is that the name of the submit button exists in the form.  The button must be named
	 * with the form name postfixed with '-submit', e.g. 'form-name-submit'.
	 * @param $value \b bool TRUE if the check should be suppressed
	 */
	public function suppressSubmitCheck($value) {
		$this->suppress_submit_check = $value ? TRUE : FALSE;
	}

	/**
	 * Sets the form's notification message and type.
	 * @param $type    \b string The message type: error, warn, info or success
	 * @param $message \b string The message
	 */
	public function setNotification($type, $message) {
		$this->notification_type = $type;
		$this->notification_message = $message;
	}

	/**
	 * Returns an array of errors that where generated by validation or manually added.
	 * @return \b bool TRUE if this object represents a table in the database
	 */
	public function getErrors() {
		return $this->form_errors;
	}

	/**
	 * Sets the array of errors on the form.
	 * @param $errors \b array The array of errors for the form
	 */
	public function setErrors(array $errors) {
		$this->form_errors = $errors;
	}

	/**
	 * Sets the flag to disable the form's submission.
	 * @param $value \b bool TRUE if the form's submission should be disabled.
	 */
	public function setDisableSubmitButton($value) {
		$this->disable_submit_button = $value ? TRUE : FALSE;
	}

	/**
	 * Add an error to the form's array of validation errors
	 * @param $name    \b string The name of the input
	 * @param $message \b string The error message
	 */
	public function addError($name, $message) {
		$this->form_errors[$name] = $message;
	}

	/**
	 * Returns an array of the form's inputs
	 * @return \b array The form's inputs array
	 */
	public function getInputs() {
		return $this->inputs;
	}

	/**
	 * Sets the form's inputs array
	 * @param $errors \b array The array of inputs to set on the form
	 */
	public function setInputs(array $inputs) {
		$this->inputs = $inputs;
	}

	/**
	 * Add an input to the form
	 * @param $name \b string The name of the input
	 * @param $data \b array  The input definition array
	 */
	public function addInput($name, array $data) {
		$this->inputs[$name] = $data;
	}

	/**
	 * Returns an array of the form's validators
	 * @return \b array The form's validators array
	 */
	public function getValidators() {
		return $this->validators;
	}

	/**
	 * Sets the form's validators array
	 * @param $errors \b array The array of validators to set on the form
	 */
	public function setValidators(array $validators) {
		$this->validators = $validators;
	}

	/**
	 * Add a validator to the form
	 * @param $name \b string The name of the input
	 * @param $data \b array  The validator definition array
	 */
	public function addValidator($name, array $data) {
		$this->validators[$name][] = $data;
	}

	public function getJavascriptValidation() {
		// register the form with the validator
		$js = "FormValidator.registerForm('".$this->name."', ".json_encode($this->inputs).", ".json_encode($this->validators).", ".($this->disable_submit_button ? 'true' : 'false').");";

		// add an onclick event to the submit button
		$js .= '$("#'.$this->name.' button[name=\''.$this->name.'-submit\']").click(function(event) {return FormValidator.validateForm(this, "'.$this->name.'", event);});';

		// check for notification display
		if ($this->notification_message && $this->notification_type) {
			$js .= 'FormValidator.displayPageNotification("'.$this->notification_type.'", "'.htmlspecialchars($this->notification_message).'");';
		}

		$js = "$(document).ready(function() { $js });";

		return $js;
	}

	public function getHtmlErrorDiv($name, $class = '') {
		if (isset($this->form_errors[$name])) {
			return '<div id="'.$name.'-error" class="form-error visible '.$class.'">'.$this->form_errors[$name].'</div>';
		}
		else {
			return '<div id="'.$name.'-error" class="form-error '.$class.'"></div>';
		}
	}

	public function getValue($name) {
		// Make sure the form has been submitted
		if (!$this->suppress_submit_check && is_null($this->request->requestParam($this->name.'-submit'))) {
			return NULL;
		}
		return $this->request->requestParam($name);
	}

	public function setValue($name, $value) {
		// Add this value ot the request data
		$this->request->requestParam($this->name.'-submit','submit');
		$this->request->requestParam($name, $value);
	}

	public function getSubmittedValues() {
		$values = [];
		foreach ($this->inputs as $name => $data) {
			$value = $this->getValue($name);
			if (!is_null($name)) {
				$values[$name] = $value;
			}
		}
		return $values;
	}

	public function getEncodedValue($name) {
		// Make sure the form has been submitted
		if (!$this->suppress_submit_check && is_null($this->request->requestParam($this->name.'-submit'))) {
			return '';
		}
		return htmlspecialchars($this->request->requestParam($name));
	}

	public function isSubmitted() {
		if (!$this->suppress_submit_check && is_null($this->request->requestParam($this->name.'-submit'))) {
			return FALSE;
		}
		return TRUE;
	}

	public function checkFormValue($data, $value) {
		$is_this_valid = TRUE;
		switch ($data['type']) {
			case 'integer':
				if (!$this->isInteger($value)) {
					$is_this_valid = FALSE;
				}
				elseif (isset($data['max_value']) && $value > $data['max_value']) {
					$is_this_valid = FALSE;
				}
				elseif (isset($data['min_value']) && $value < $data['min_value']) {
					$is_this_valid = FALSE;
				}
				break;

			case 'float':
				if (!$this->isFloat($value)) {
					$is_this_valid = FALSE;
				}
				elseif (isset($data['max_value']) && $value > $data['max_value']) {
					$is_this_valid = FALSE;
				}
				elseif (isset($data['min_value']) && $value < $data['min_value']) {
					$is_this_valid = FALSE;
				}
				break;

			case 'date':
				if (!$this->isDate($value)) {
					$is_this_valid = FALSE;
				}
				break;

			case 'time':
				if (!$this->isTime($value)) {
					$is_this_valid = FALSE;
				}
				break;

			case 'datetime':
				if (!$this->isDateTime($value)) {
					$is_this_valid = FALSE;
				}
				break;

			case 'money':
				if (!$this->isMoney($value)) {
					$is_this_valid = FALSE;
				}
				elseif ($data['zero_allowed'] && (float)$value == 0) {
					$is_this_valid = TRUE;
				}
				elseif (!$data['zero_allowed'] && (float)$value <= 0) {
					$is_this_valid = FALSE;
				}
				break;

			case 'string':
				if (isset($data['max_length']) && strlen($value) > $data['max_length']) {
					$is_this_valid = FALSE;
				}
				elseif (isset($data['min_length']) && strlen($value) < $data['min_length']) {
					$is_this_valid = FALSE;
				}
				break;

			case 'email':
				if (!$this->isEmail($value)) {
					$is_this_valid = FALSE;
				}
				break;

			case 'url':
				if (!$this->isUrl($value)) {
					$is_this_valid = FALSE;
				}
				break;

			case 'url-fragment':
				if (!$this->isUrlFragment($value)) {
					$is_this_valid = FALSE;
				}
				break;

			case 'date-segements':
				if (!$this->isDate($value)) {
					$is_this_valid = FALSE;
				}
				break;

			case 'file':
				$file = $this->request->fileParam($name);
				if (!$file) {
					$is_this_valid = FALSE;
				}
				elseif ($file['error'] != UPLOAD_ERR_OK) {
					$is_this_valid = FALSE;
				}
				break;

			default:
				throw new FormException("Invalid form element type: ".$data['type']);
				break;
		}

		return $is_this_valid;
	}

	public function checkElement($name, $data, $value, $index = NULL) {
		$is_this_valid = TRUE;

		if (!isset($data['required'])) $data['required'] = TRUE;
		if (!isset($data['zero_allowed'])) $data['zero_allowed'] = FALSE;

		if ((is_null($value) || $value == '') && !$data['required']) {
			$is_this_valid = TRUE;
		}
		elseif ((is_null($value) || $value == '') && $data['required']) {
			$is_this_valid = FALSE;
		}
		else {
			$is_this_valid = $this->checkFormValue($data, $value);
		}

		// run the validators
		if ($is_this_valid && isset($this->validators[$name])) {
			foreach ($this->validators[$name] as $validator) {
				switch($validator['type']) {
					case 'params-equal':
						if ($value != $this->request->requestParam($validator['param'])) {
							$this->form_errors[$name] = $validator['message'];
							$is_this_valid = FALSE;
						}
						break;

					case 'regex':
						if (!empty($value) && !preg_match('/'.$validator['regex'].'/'.$validator['modifiers'], $value)) {
							$this->form_errors[$name] = $validator['message'];
							$is_this_valid = FALSE;
						}
						break;

					case 'function':
						if (!$validator['function']($value, $this)) {
							if ($index) {
								$this->form_errors[$name][$index] = $validator['message'];
							}
							else {
								$this->form_errors[$name] = $validator['message'];
							}
							$is_this_valid = FALSE;
						}
						break;
				}
			}
		}

		if (!$is_this_valid) {
			$this->logger->debug("Form field not valid: $name");
			if (!isset($this->form_errors[$name])) {
				if (isset($data['message'])) {
					if ($index) {
						$this->form_errors[$name] = $data['message'];
					}
					else {
						$this->form_errors[$name][$index] = $data['message'];
					}
				}
				else {
					throw new FormException('No message for element: '.$name);
				}
			}
			$form_valid = FALSE;
		}

		return $is_this_valid;
	}

	public function validate() {
		$this->form_errors = [];
		$form_valid = TRUE;

		if (!$this->suppress_submit_check && is_null($this->request->requestParam($this->name.'-submit'))) {
			return FALSE;
		}

		foreach ($this->inputs as $name => $data) {
			if ((isset($data['is_array']) && $data['is_array']) || (isset($data['array_value']) && $data['array_value'])) {
				$array = $this->request->requestParam($name);
				if (is_array($array)) {
					foreach ($array as $index => $value) {
						$form_valid &= $this->checkElement($name, $data, $value, $index);
					}
				}
			}
			else {
				$value = $this->request->requestParam($name);
				$form_valid &= $this->checkElement($name, $data, $value);
			}
		}

		return $form_valid;
	}

	public function isFloat ($string) {
		if (preg_match('/^-?[0-9]+(\.[0-9]+)?$/', $string)) {
			return TRUE;
		}
		return FALSE;
	}

	public function isInteger ($string) {
		if (preg_match('/^-?[0-9]+$/', $string)) {
			return TRUE;
		}
		return FALSE;
	}

	public function isMoney ($string) {
		if (preg_match('/^[0-9]+(\.[0-9]{2,4})?$/', $string)) {
			return TRUE;
		}
		return FALSE;
	}

	public function isUrl ($string) {
		if (preg_match('/^(https?:\/\/)?[\da-z\.\-]+\.[a-z\.]{2,6}[#&+_\?\/\w \.\-=]*$/', $string)) {
			return TRUE;
		}
		return FALSE;
	}

	public function isDate ($string) {
		if (preg_match('/^\s*[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}\s*$/i', $string)) {
			return TRUE;
		}
		return FALSE;
	}

	public function isDateTime ($string) {
		if (preg_match('/^\s*[0-9]{4}[\/-][0-9]{1,2}[\/-][0-9]{1,2}\s*[0-9]{2}:[0-9]{2}(:[0-9]{2})?\s*$/i', $string)) {
			return TRUE;
		}
		return FALSE;
	}

	public function isTime ($string) {
		if (preg_match('/^\s*[0-9]{2}:[0-9]{2}\s*$/i', $string)) {
			return TRUE;
		}
		return FALSE;
	}

	public function isUrlFragment ($string) {
		if (preg_match('/[^A-Za-z0-9\/_-]/i', $string)) {
			return FALSE;
		}
		return TRUE;
	}

	public function isEmail ($string) {
		if (preg_match('/^[a-zA-Z][\w\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w\.-]*[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z]$/', $string)) {
			return TRUE;
		}
		return FALSE;
	}
}
