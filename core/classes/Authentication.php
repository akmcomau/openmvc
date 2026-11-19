<?php

namespace core\classes;

use core\classes\models\Administrator;
use core\classes\models\Customer;
use core\classes\exceptions\AuthenticationException;

/**
 * This handles customer and administrator authentication
 */
class Authentication {

	/**
	 * The configuration object
	 * @var Config $config
	 */
	protected $config;

	/**
	 * The database object
	 * @var Database $database
	 */
	protected $database;

	/**
	 * The logger object
	 * @var Logger $logger
	 */
	protected $logger;

	/**
	 * The request object
	 * @var Request $request
	 */
	protected $request;

	/**
	 * Is the session logged in
	 * @var boolean $logged_in
	 */
	protected $logged_in = FALSE;

	/**
	 * Holds the administrator's record
	 * @var array $administrator_data
	 */
	protected $administrator_data = NULL;

	/**
	 * Holds the customers's record
	 * @var array $customer_data
	 */
	protected $customer_data  = NULL;

	/**
	 * Constructor
	 * @param $config   \b Config The configuration object
	 * @param $database \b Database The database object
	 * @param $request  \b Request The request object
	 */
	public function __construct(Config $config, Database $database, Request $request) {
		$this->config   = $config;
		$this->database = $database;
		$this->request  = $request;
		$this->logger   = Logger::getLogger(__CLASS__);

		$auth = $request->session->get('authentication');
		if ($auth) {
			$this->logged_in = TRUE;
			$this->customer_data = isset($auth['customer']) ? $auth['customer'] : NULL;
			$this->administrator_data = isset($auth['administrator']) ? $auth['administrator'] : NULL;
			$this->revalidateSession();
		}

		$this->callHook('init_authentication', [$this]);
	}

	/**
	 * How often (seconds) a logged in session is re-checked against the
	 * database, so disabling or deleting an account ends its sessions
	 */
	const REVALIDATE_INTERVAL = 60;

	/**
	 * Columns never copied into the session
	 */
	const SENSITIVE_COLUMNS = ['password', 'remember_me', 'token', 'token_created'];

	/**
	 * Strip secrets (password hash, remember-me and reset tokens) from a record
	 * before it is stored in the session
	 * @param $record \b array  The model's record
	 * @param $table  \b string The table name, used as the column prefix
	 * @return \b array The record without the sensitive columns
	 */
	protected function sessionRecord(array $record, $table) {
		foreach (self::SENSITIVE_COLUMNS as $column) {
			unset($record[$table.'_'.$column]);
			unset($record[$column]);
		}
		return $record;
	}

	/**
	 * Re-check, at most every REVALIDATE_INTERVAL seconds, that the logged in
	 * customer/administrator still exists and is active; log them out if not
	 */
	protected function revalidateSession() {
		if ($this->database->getEngine() == 'none') {
			return;
		}

		$checked = $this->request->session->get(['authentication', 'verified_at']);
		if ($checked && $checked > time() - self::REVALIDATE_INTERVAL) {
			return;
		}

		if ($this->getCustomerID()) {
			$active = $this->database->queryValue('SELECT customer_active FROM customer WHERE customer_id = '.(int)$this->getCustomerID());
			if (!$active) {
				$this->logger->info('Session ended for missing/inactive customer: '.$this->getCustomerID());
				$this->request->session->delete(['authentication', 'customer']);
				$this->customer_data = NULL;
			}
		}
		if ($this->getAdministratorID()) {
			$active = $this->database->queryValue('SELECT administrator_active FROM administrator WHERE administrator_id = '.(int)$this->getAdministratorID());
			if (!$active) {
				$this->logger->info('Session ended for missing/inactive administrator: '.$this->getAdministratorID());
				$this->request->session->delete(['authentication', 'administrator']);
				$this->administrator_data = NULL;
			}
		}

		$this->logged_in = ($this->customer_data || $this->administrator_data) ? TRUE : FALSE;
		if ($this->logged_in) {
			$this->request->session->set(['authentication', 'verified_at'], time());
		}
		else {
			$this->request->session->delete('authentication');
		}
	}

	public function getDatabase() {
		return $this->database;
	}

	/**
	 * Check if the session is logged in as either an administrator or customer
	 * @return \b boolean TRUE if the session is logged in, FALSE otherwise
	 */
	public function loggedIn() {
		if ($this->customerLoggedIn() || $this->administratorLoggedIn()) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Check if the session is logged in a  customer
	 * @return \b boolean TRUE if the session is logged in, FALSE otherwise
	 */
	public function customerLoggedIn() {
		if ($this->logged_in && $this->getCustomerID()) {
			return $this->customer_data;
		}
		return FALSE;
	}

	/**
	 * Check if the session is logged in as either an administrator
	 * @return \b boolean TRUE if the session is logged in, FALSE otherwise
	 */
	public function administratorLoggedIn() {
		if ($this->logged_in && $this->getAdministratorID()) {
			$data = $this->administrator_data;
			$data['administrator_name'] = $data['administrator_first_name'].' '.$data['administrator_last_name'];
			return $data;
		}
		return FALSE;
	}

	/**
	 * Gets the Customer ID
	 * @return \b integer the Customer ID or NULL otherwise
	 */
	public function getCustomerID() {
		if (isset($this->customer_data['customer_id'])) {
			return $this->customer_data['customer_id'];
		}
		return NULL;
	}

	/**
	 * Gets the Administrator ID
	 * @return \b integer the Administrator ID or NULL otherwise
	 */
	public function getAdministratorID() {
		if (isset($this->administrator_data['administrator_id'])) {
			return $this->administrator_data['administrator_id'];
		}
		return NULL;
	}

	/**
	 * Logs in a customer
	 * @param $customer \b Customer The customer to login
	 * @throws AuthenticationException if the customer object does not contain a ID
	 */
	public function loginCustomer(Customer $customer) {
		$this->callHook('before_loginCustomer', [$customer]);

		if ($this->customerLoggedIn()) {
			$this->logger->info("Customer already logged in, logging out: ".$this->getCustomerID());
			$this->logoutCustomer();
		}
		if (!$customer->id) {
			throw new AuthenticationException("Cannot login a customer with no customer_id");
		}
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_regenerate_id(TRUE);
		}
		$this->logged_in = TRUE;
		$this->request->session->set(['authentication', 'customer'], $this->sessionRecord($customer->getRecord(), 'customer'));
		$this->request->session->set(['authentication', 'verified_at'], time());
		$this->customer_data = $this->sessionRecord($customer->getRecord(), 'customer');
		$this->logger->info("Customer logged in: ".$this->getCustomerID());

		// clear the token on successful login
		if ($customer->token) {
			$customer->clearToken();
		}

		$this->callHook('after_loginCustomer', [$customer]);

		// Register an analytics event
		$this->request->addEvent('Customer Login', $this->getCustomerID());
	}

	/**
	 * Logs in an administrator
	 * @param $admin \b Administrator The administrator to login
	 * @throws AuthenticationException if the administrator object does not contain an ID
	 */
	public function loginAdministrator(Administrator $admin) {
		$this->callHook('before_loginAdministrator', [$admin]);

		if ($this->administratorLoggedIn()) {
			$this->logger->info("Administrator already logged in, logging out: ".$this->getAdministratorID());
			$this->logoutAdministrator();
		}
		if (!$admin->id) {
			throw new AuthenticationException("Cannot login a administrator with no admin_id");
		}
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_regenerate_id(TRUE);
		}
		$this->logged_in = TRUE;
		$this->request->session->set(['authentication', 'administrator'], $this->sessionRecord($admin->getRecord(), 'administrator'));
		$this->request->session->set(['authentication', 'verified_at'], time());
		$this->administrator_data = $this->sessionRecord($admin->getRecord(), 'administrator');
		$this->logger->info("Administrator logged in: ".$this->getAdministratorID());

		$this->callHook('after_loginAdministrator', [$admin]);

		// Register an analytics event
		$this->request->addEvent('Administrator Login', $this->getAdministratorID());
	}

	/**
	 * Logs out the administrator and customer
	 * @param $call_hooks \b bool Should the defined logout hooks be called
	 */
	public function logout($call_hooks = TRUE) {
		$this->logoutCustomer($call_hooks);
		$this->logoutAdministrator($call_hooks);
	}

	/**
	 * Logs out the customer
	 * @param $call_hooks \b bool Should the defined logout hooks be called
	 */
	public function logoutCustomer($call_hooks = TRUE) {
		if ($this->customerLoggedIn()) {
			$customer_id = $this->getCustomerID();
			if ($call_hooks) {
				$this->callHook('before_logoutCustomer', [$customer_id]);
			}

			$auth = $this->request->session->delete(['authentication', 'customer']);
			$this->logged_in = $this->administratorLoggedIn();
			$this->customer_data  = NULL;

			if ($call_hooks) {
				$this->callHook('after_logoutCustomer', [$customer_id]);
			}

			// Register an analytics event
			$this->request->addEvent('Customer Logout', $customer_id);
		}
	}

	/**
	 * Logs out the administrator
	 * @param $call_hooks \b bool Should the defined logout hooks be called
	 */
	public function logoutAdministrator($call_hooks = TRUE) {
		if ($this->administratorLoggedIn()) {
			$admin_id = $this->getAdministratorID();
			if ($call_hooks) {
				$this->callHook('before_logoutAdministrator', [$admin_id]);
			}

			$auth = $this->request->session->delete(['authentication', 'administrator']);
			$this->logged_in = $this->administratorLoggedIn();
			$this->administrator_data = NULL;

			if ($call_hooks) {
				$this->callHook('after_logoutAdministrator', [$admin_id]);
			}

			// Register an analytics event
			$this->request->addEvent('Administrator Logout', $admin_id);
		}
	}

	/**
	 * Checks for force password change is in effect
	 * @return \b boolean TRUE is force password change is in effect, FALSE otherwise
	 */
	public function forcePasswordChangeEnabled() {
		$enabled = $this->request->session->get('force_password_change');
		return $enabled ? TRUE : FALSE;
	}

	/**
	 * Sets the force password change flag
	 * @param $enable \b boolean TRUE to set the flag, FALSE to clear it
	 */
	public function forcePasswordChange($enable) {
		if ($enable) {
			$auth = $this->request->session->set('force_password_change',  TRUE);
		}
		else {
			$this->request->session->delete('force_password_change');
		}
	}

	/**
	 * Call the authentication hooks
	 * @param $name   \b string The authentication hook type to call
	 * @param $params \b array Parameters for the hook
	 */
	protected function callHook($name, array $params = []) {
		$modules = (new Module($this->config))->getEnabledModules();

		if (
			property_exists($this->config->siteConfig(), 'hooks') && is_object($this->config->siteConfig()->hooks) &&
			property_exists($this->config->siteConfig()->hooks, 'authentication') &&
			property_exists($this->config->siteConfig()->hooks->authentication, $name)
		) {
			$class_name = $this->config->siteConfig()->hooks->authentication->$name;
			$class = $class_name;
			$this->logger->debug("Calling Hook: $class::$name");
			$class = new $class($this->config, $this->database, $this->request);
			call_user_func_array(array($class, $name), $params);
		}

		foreach ($modules as $module) {
			if (isset($module['hooks']['authentication'][$name])) {
				$class = $module['namespace'].'\\'.$module['hooks']['authentication'][$name];
				$this->logger->debug("Calling Hook: $class::$name");
				$class = new $class($this->config, $this->database, $this->request);
				call_user_func_array(array($class, $name), $params);
			}
		}
	}

}
