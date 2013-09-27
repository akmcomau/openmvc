<?php

namespace core\classes;

class Session {
	public function delete($name) {
		if (is_array($name)) {
			$data = &$_SESSION;
			foreach ($name as $element) {
				if (!isset($data[$element])) {
					$data[$element] = NULL;
				}
				$data = &$data[$element];
			}
			unset($data);
			return;
		}

		unset($_SESSION[$name]);
		return;
	}

	public function set($name, $value) {
		if (is_array($name)) {
			$data = &$_SESSION;
			foreach ($name as $element) {
				if (!isset($data[$element])) {
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
				if (!isset($data[$element])) {
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
