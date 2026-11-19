<?php

namespace core\classes;

/**
 * Limits failed login attempts, per login name and per client IP, to slow
 * down password guessing.  Counters live in APCu when it is available,
 * otherwise in small files under the system temp directory, so this works
 * without a database table.
 */
class LoginThrottle {

	/**
	 * Failed attempts allowed for a single login name within the window
	 */
	const MAX_PER_LOGIN = 5;

	/**
	 * Failed attempts allowed from a single IP address within the window
	 */
	const MAX_PER_IP = 25;

	/**
	 * The window, in seconds, over which failures are counted
	 */
	const WINDOW = 900;

	/**
	 * Which login form this is for, e.g. 'customer' or 'administrator'
	 * @var string $scope
	 */
	protected $scope;

	/**
	 * The client's IP address
	 * @var string $ip
	 */
	protected $ip;

	/**
	 * Constructor
	 * @param $scope \b string Which login form this is for
	 * @param $ip    \b string The client's IP address
	 */
	public function __construct($scope, $ip) {
		$this->scope = $scope;
		$this->ip = (string)$ip;
	}

	/**
	 * Is the login name or IP address currently locked out
	 * @param $login \b string The login name being attempted
	 * @return \b bool TRUE if the attempt should be refused without checking the password
	 */
	public function isBlocked($login) {
		return $this->getCount($this->loginKey($login)) >= self::MAX_PER_LOGIN
			|| $this->getCount($this->ipKey()) >= self::MAX_PER_IP;
	}

	/**
	 * Record a failed login attempt
	 * @param $login \b string The login name that was attempted
	 */
	public function recordFailure($login) {
		$this->increment($this->loginKey($login));
		$this->increment($this->ipKey());
	}

	/**
	 * Clear the failure count for a login name after a successful login
	 * @param $login \b string The login name
	 */
	public function clear($login) {
		$this->delete($this->loginKey($login));
	}

	protected function loginKey($login) {
		return 'login_throttle_'.$this->scope.'_l_'.hash('sha256', strtolower(trim((string)$login)));
	}

	protected function ipKey() {
		return 'login_throttle_'.$this->scope.'_ip_'.hash('sha256', $this->ip);
	}

	protected function useApcu() {
		return function_exists('apcu_enabled') && apcu_enabled();
	}

	protected function filename($key) {
		$dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'openmvc_login_throttle';
		if (!is_dir($dir)) {
			try {
				mkdir($dir, 0700, TRUE);
			}
			catch (\Throwable $ex) {
				// another request may have created it; a failure here just disables the file fallback
			}
		}
		return $dir.DIRECTORY_SEPARATOR.$key;
	}

	/**
	 * Get the [count, window_start] for a key
	 */
	protected function read($key) {
		$data = NULL;
		if ($this->useApcu()) {
			$data = apcu_fetch($key);
		}
		else {
			$file = $this->filename($key);
			try {
				if (is_file($file)) {
					$data = json_decode((string)file_get_contents($file), TRUE);
				}
			}
			catch (\Throwable $ex) {
				$data = NULL;
			}
		}

		if (!is_array($data) || !isset($data[0], $data[1]) || $data[1] < time() - self::WINDOW) {
			return [0, time()];
		}
		return $data;
	}

	protected function write($key, array $data) {
		if ($this->useApcu()) {
			apcu_store($key, $data, self::WINDOW);
		}
		else {
			try {
				file_put_contents($this->filename($key), json_encode($data), LOCK_EX);
			}
			catch (\Throwable $ex) {
				Logger::getLogger(__CLASS__)->error('Could not store login throttle data: '.$ex->getMessage());
			}
		}
	}

	protected function getCount($key) {
		return $this->read($key)[0];
	}

	protected function increment($key) {
		$data = $this->read($key);
		$data[0]++;
		$this->write($key, $data);
	}

	protected function delete($key) {
		if ($this->useApcu()) {
			apcu_delete($key);
		}
		else {
			$file = $this->filename($key);
			try {
				if (is_file($file)) unlink($file);
			}
			catch (\Throwable $ex) {
				// nothing to clear
			}
		}
	}
}
