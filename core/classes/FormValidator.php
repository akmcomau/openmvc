<?php

namespace core\classes;

use core\classes\exceptions\FormException;

class FormValidator {

	protected $request;
	protected $name;
	protected $inputs = [];
	protected $validators = [];
	protected $form_errors = [];
	protected $logger = NULL;

	protected $notification_message = NULL;
	protected $notification_type = NULL;

	protected $suppress_submit_check = FALSE;

	public function __construct($request, $name, array $inputs = NULL, array $validators  = NULL) {
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

	public function suppressSubmitCheck($value) {
		$this->suppress_submit_check = $value ? TRUE : FALSE;
	}

	public function setNotification($type, $message) {
		$this->notification_type = $type;
		$this->notification_message = $message;
	}

	public function getErrors() {
		return $this->form_errors;
	}

	public function setErrors(array $errors) {
		$this->form_errors = $errors;
	}

	public function addError($name, $message) {
		$this->form_errors[$name] = $message;
	}

	public function getInputs() {
		return $this->inputs;
	}

	public function setInputs(array $inputs) {
		$this->inputs = $inputs;
	}

	public function addInput($name, array $data) {
		$this->inputs[$name] = $data;
	}

	public function getValidators() {
		return $this->validators;
	}

	public function setValidators(array $validators) {
		$this->validators = $validators;
	}

	public function addValidator($name, array $data) {
		$this->validators[$name][] = $data;
	}

	public function getJavascriptValidation() {
		// register the form with the validator
		$js = "FormValidator.registerForm('".$this->name."', ".json_encode($this->inputs).", ".json_encode($this->validators).");";

		// add an onclick event to the submit button
		$js .= '$("#'.$this->name.' button[name=\''.$this->name.'-submit\']").click(function(event) {return FormValidator.validateForm("'.$this->name.'", event);});';

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

	public function validate() {
		$this->form_errors = [];
		$form_valid = TRUE;

		if (!$this->suppress_submit_check && is_null($this->request->requestParam($this->name.'-submit'))) {
			return FALSE;
		}

		foreach ($this->inputs as $name => $data) {
			$is_this_valid = TRUE;
			$value = $this->request->requestParam($name);

			if (!isset($data['required'])) $data['required'] = TRUE;
			if (!isset($data['zero_allowed'])) $data['zero_allowed'] = FALSE;

			if ((is_null($value) || $value == '') && !$data['required']) {
				$is_this_valid = TRUE;
			}
			elseif ((is_null($value) || $value == '') && $data['required']) {
				$is_this_valid = FALSE;
			}
			else {
				switch ($data['type']) {
					case 'integer':
						if (!$this->isInteger($value)) {
							$is_this_valid = FALSE;
						}
						break;

					case 'float':
						if (!$this->isFloat($value)) {
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
						throw new FormException("Invalid form element type [$name]: ".$data['type']);
						break;
				}
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
								$this->form_errors[$name] = $validator['message'];
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
						$this->form_errors[$name] = $data['message'];
					}
					else {
						throw new FormException('No message for element: '.$name);
					}
				}
				$form_valid = FALSE;
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
		if (preg_match('/^\s*[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}\s*[0-9]{2}:[0-9]{2}(:[0-9]{2})?\s*$/i', $string)) {
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
