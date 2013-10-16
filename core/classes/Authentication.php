<?php

namespace core\classes;

use core\classes\models\Administrator;
use core\classes\models\Customer;
use core\classes\exceptions\AuthenticationException;

class Authentication {

	protected $config;
	protected $database;
	protected $logger;
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
			return $this->administrator_data;
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
		return TRUE;
	}

	public function loginAdministrator(Administrator $admin) {
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
		return TRUE;
	}

	public function logout() {
		$auth = $this->request->session->delete('authentication');
		$this->logged_in = FALSE;
		$this->administrator_data = NULL;
		$this->customer_data  = NULL;
	}

	public function logoutCustomer() {
		$auth = $this->request->session->delete(['authentication', 'customer']);
		$this->logged_in = $this->administratorLoggedIn();
		$this->customer_data  = NULL;
	}

	public function logoutAdministrator() {
		$auth = $this->request->session->delete(['authentication', 'administrator']);
		$this->logged_in = $this->administratorLoggedIn();
		$this->customer_data  = NULL;
	}

}
