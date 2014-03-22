<?php

namespace core\classes;

use core\classes\models\Administrator;
use core\classes\models\Customer;
use core\classes\exceptions\AuthenticationException;

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

	protected $logged_in = FALSE;
	protected $administrator_data = NULL;
	protected $customer_data  = NULL;

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
		}

		$this->callHook('init_authentication', [$this]);
	}

	public function loggedIn() {
		if ($this->customerLoggedIn() || $this->administratorLoggedIn()) {
			return TRUE;
		}
		return FALSE;
	}

	public function customerLoggedIn() {
		if ($this->logged_in && $this->getCustomerID()) {
			return $this->customer_data;
		}
		return FALSE;
	}

	public function administratorLoggedIn() {
		if ($this->logged_in && $this->getAdministratorID()) {
			$data = $this->administrator_data;
			$data['administrator_name'] = $data['administrator_first_name'].' '.$data['administrator_last_name'];
			return $data;
		}
		return FALSE;
	}

	public function getCustomerID() {
		if (isset($this->customer_data['customer_id'])) {
			return $this->customer_data['customer_id'];
		}
		return NULL;
	}

	public function getAdministratorID() {
		if (isset($this->administrator_data['administrator_id'])) {
			return $this->administrator_data['administrator_id'];
		}
		return NULL;
	}

	public function loginCustomer(Customer $customer) {
		$this->callHook('before_loginCustomer', [$customer]);

		if ($this->customerLoggedIn()) {
			$this->logger->info("Customer already logged in, logging out: ".$this->getCustomerID());
			$this->logoutCustomer();
		}
		if (!$customer->id) {
			throw new AuthenticationException("Cannot login a customer with no customer_id");
		}
		$this->logged_in = TRUE;
		$this->request->session->set(['authentication', 'customer'], $customer->getRecord());
		$this->customer_data = $customer->getRecord();
		$this->logger->info("Customer logged in: ".$this->getCustomerID());

		// clear the token on successful login
		if ($customer->token) {
			$customer->clearToken();
		}

		// update the customer on the analytics session record
		if (!$this->config->is_robot && $this->config->siteConfig()->enable_analytics && isset($_SESSION['db_session_id'])) {
			$model = new Model($this->config, $this->database);
			$session = $model->getModel('\core\classes\models\Session')->get(['id' => $_SESSION['db_session_id']]);
			if ($session) {
				$session_event = $model->getModel('\core\classes\models\SessionEvent');
				$session_event->session_id = $session->id;
				$session_event->time       = date('c');
				$session_event->category   = 'auth';
				$session_event->type       = 'login';
				$session_event->sub_type   = 'customer';
				$session_event->value      = $customer->id;
				$session_event->insert();
			}
		}

		$this->callHook('after_loginCustomer', [$customer]);
		return TRUE;
	}

	public function loginAdministrator(Administrator $admin) {
		$this->callHook('before_loginAdministrator', [$admin]);

		if ($this->administratorLoggedIn()) {
			$this->logger->info("Administrator already logged in, logging out: ".$this->getAdministratorID());
			$this->logoutAdministrator();
		}
		if (!$admin->id) {
			throw new AuthenticationException("Cannot login a administrator with no admin_id");
		}
		$this->logged_in = TRUE;
		$this->request->session->set(['authentication', 'administrator'], $admin->getRecord());
		$this->administrator_data = $admin->getRecord();
		$this->logger->info("Administrator logged in: ".$this->getAdministratorID());

		// update the customer on the analytics session record
		if (!$this->config->is_robot && $this->config->siteConfig()->enable_analytics && isset($_SESSION['db_session_id'])) {
			$model = new Model($this->config, $this->database);
			$session = $model->getModel('\core\classes\models\Session')->get(['id' => $_SESSION['db_session_id']]);
			if ($session) {
				$session_event = $model->getModel('\core\classes\models\SessionEvent');
				$session_event->session_id = $session->id;
				$session_event->time       = date('c');
				$session_event->category   = 'auth';
				$session_event->type       = 'login';
				$session_event->sub_type   = 'admin';
				$session_event->value      = $admin->id;
				$session_event->insert();
			}
		}

		$this->callHook('after_loginAdministrator', [$admin]);
		return TRUE;
	}

	public function logout($call_hooks = TRUE) {
		$this->logoutCustomer($call_hooks);
		$this->logoutAdministrator($call_hooks);
	}

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
		}
	}

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
		}
	}

	public function forcePasswordChangeEnabled() {
		$enabled = $this->request->session->get('force_password_change');
		return $enabled ? TRUE : FALSE;
	}

	public function forcePasswordChange($enable = NULL) {
		if ($enable) {
			$auth = $this->request->session->set('force_password_change',  TRUE);
		}
		else {
			$this->request->session->delete('force_password_change');
		}
	}

	protected function callHook($name, array $params = []) {
		$modules = (new Module($this->config))->getEnabledModules();
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
