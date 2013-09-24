<?php

namespace core\classes;

class Authentication {

	protected $config;
	protected $database;
	protected $logger;
	protected $request;

	protected $logged_in = FALSE;
	protected $user_data = NULL;

	public function __construct(Config $config, Database $database, Request $request) {
		$this->config   = $config;
		$this->database = $database;
		$this->request  = $request;
		$this->logger   = Logger::getLogger(__CLASS__);

		$auth = $request->session->get('authentication');
		if ($auth) {
			$this->logged_in  = TRUE;
			$this->user_data = $auth;
		}
	}

	public function getUserID() {
		if (!$this->logged_in) {
			return NULL;
		}
		return $this->user_data['user_id'];
	}

}
