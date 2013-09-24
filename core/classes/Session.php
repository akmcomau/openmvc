<?php

namespace core\classes;

class Session {
	public function set($name, $value) {
		if (is_array($name)) {
			$data = &$_SESSION;
			foreach ($name as $element) {
				if (!isset($data[$name])) {
					$data[$element] = NULL;
				}
				$data = &$data[$element];
			}
			$data = $value;
			return $value;
		}

		$_SESSION[$name] = $value;
		return $value;
	}

	public function get($name) {
		if (is_array($name)) {
			$data = &$_SESSION;
			foreach ($name as $element) {
				if (!isset($data[$name])) {
					return NULL;
				}
				$data = &$data[$element];
			}
			$data;
		}

		if (isset($_SESSION[$name])) {
			return $_SESSION[$name];
		}
		else {
			return NULL;
		}
	}
}
