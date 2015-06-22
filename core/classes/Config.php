<?php

namespace core\classes;

use core\classes\exceptions\ConfigException;
use core\classes\exceptions\DomainRedirectException;
use ErrorException;

/**
 * This is used for loading and retrieving configuration parameters
 */
class Config {

	/**
	 * The site's domain name
	 * @var string $site_domain
	 */
	protected $site_domain;

	/**
	 * The configuration data
	 * @var array configuration
	 */
	protected $configuration = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		// get the default config
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'default_config.php';
		require($filename);

		// get the custom config
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		require($filename);

		// Convert the config data to objects
		$_CONFIG = json_decode(json_encode($_CONFIG), FALSE);
		$_DEFAULT_CONFIG = json_decode(json_encode($_DEFAULT_CONFIG), FALSE);

		$this->configuration = [];

		// add the custom config to this object
		foreach ($_CONFIG as $key => $value) {
			$this->configuration[$key] = $value;
		}

		// add the default config to this object
		$clones = [];
		foreach ($this->configuration['sites'] as $domain => &$site_data) {
			foreach ($_DEFAULT_CONFIG->default_site as $key => $value) {
				if (!isset($site_data->$key)) {
					$site_data->$key = $value;
				}
			}
			$site_data->domain = $domain;
			if (property_exists($site_data, 'domains')) {
				foreach ($site_data->domains as $domain) {
					$clones[$domain] = &$site_data;
				}
			}
		}

		// add the clone sites
		foreach ($clones as $domain => &$site_data) {
			$this->configuration['sites']->$domain = &$site_data;
		}

		foreach ($_DEFAULT_CONFIG as $key => $value) {
			if (!isset($this->configuration[$key])) {
				$this->configuration[$key] = $value;
			}
		}

		// default value for is_robot
		$this->configuration['is_robot'] = FALSE;
	}

	/**
	 * Magic getter for the class, for fetching config values
	 * @param[in] $name \b string TRUE if the connection is from a robot, FALSE otherwise
	 * @throws ConfigException if the configuration parameter does not exist
	 */
	public function __get($name) {
		if (isset($this->configuration[$name])) {
			return $this->configuration[$name];
		}
		throw new ConfigException("Undefined config property: $name");
	}

	/**
	 * Sets the is_robot flag
	 * @param[in] $value \b boolean TRUE if the connection is from a robot, FALSE otherwise
	 */
	public function setRobot($value) {
		if ($value) {
			$this->configuration['is_robot'] = TRUE;
		}
		else {
			$this->configuration['is_robot'] = FALSE;
		}
	}

	/**
	 * Gets the configuation object for a module
	 * @param[in] $module \b string The modules name
	 * @throws ConfigException if the module's configuration was not found
	 */
	public function moduleConfig($module) {
		try {
			return $this->sites->{$this->site_domain}->modules->$module;
		}
		catch (\Exception $ex) {
			throw new ConfigException("Module config not found for $module: ".$ex->getMessage());
		}
	}

	/**
	 * Installs a new module
	 * @param[in] $module \b array The modules specification
	 */
	public function installModule(array $module) {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		require($filename);

		if (!isset($_CONFIG['modules'])) $_CONFIG['modules'] = [];

		if (!in_array($module['namespace'], $_CONFIG['modules'])) {
			$_CONFIG['modules'][] = $module['namespace'];
		}

		file_put_contents($filename, '<?php $_CONFIG = '.var_export($_CONFIG, TRUE).';');
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($filename);
		}
	}

	/**
	 * Uninstalls a module
	 * @param[in] $module \b array The modules specification
	 */
	public function uninstallModule($module) {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		require($filename);

		if (!isset($_CONFIG['modules'])) $_CONFIG['modules'] = [];

		if (in_array($module['namespace'], $_CONFIG['modules'])) {
			$index = array_search($module['namespace'], $_CONFIG['modules']);
			array_splice($_CONFIG['modules'], $index, 1);
		}

		file_put_contents($filename, '<?php $_CONFIG = '.var_export($_CONFIG, TRUE).';');
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($filename);
		}
	}

	/**
	 * Enables a module on the current site
	 * @param[in] $module \b array The modules specification
	 */
	public function enableModule($module) {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		require($filename);

		if (!isset($_CONFIG['sites'][$this->site_domain]['modules'])) {
			$_CONFIG['sites'][$this->site_domain]['modules'] = [];
		}

		if (!in_array($module['namespace'], $_CONFIG['sites'][$this->site_domain]['modules'])) {
			$_CONFIG['sites'][$this->site_domain]['modules'][$module['namespace']] = $module['default_config'];
		}

		file_put_contents($filename, '<?php $_CONFIG = '.var_export($_CONFIG, TRUE).';');
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($filename);
		}
	}

	/**
	 * Disables a module on the current site
	 * @param[in] $module \b array The modules specification
	 */
	public function disableModule($module) {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		require($filename);

		if (!isset($_CONFIG['sites'][$this->site_domain]['modules'])) {
			$_CONFIG['sites'][$this->site_domain]['modules'] = [];
		}

		if (isset($_CONFIG['sites'][$this->site_domain]['modules'][$module['namespace']])) {
			unset($_CONFIG['sites'][$this->site_domain]['modules'][$module['namespace']]);
		}

		file_put_contents($filename, '<?php $_CONFIG = '.var_export($_CONFIG, TRUE).';');
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($filename);
		}
	}

	/**
	 * Gets the current sites configuration
	 * @return \b stdClass The sites configuration object
	 */
	public function siteConfig() {
		if (!$this->site_domain) return NULL;
		return $this->sites->{$this->site_domain};
	}

	/**
	 * Updates a site configuaration value for this request
	 * @param[in] $name  \b string The site configuration setting's name
	 * @param[in] $value \b mixed The value to store
	 */
	public function updateSiteConfigParam($name, $value) {
		$this->sites->{$this->site_domain}->{$name} = $value;
	}

	/**
	 * Updates a main configuaration value for this request
	 * @param[in] $name  \b string The main configuration setting's name
	 * @param[in] $value \b mixed The value to store
	 */
	public function updateConfigParam($name, $value) {
		$this->sites->{$name} = $value;
	}

	/**
	 * Gets the current sites raw configuration data
	 * @return \b array The sites configuration file as an array
	 */
	public function getSiteConfig() {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		require($filename);

		if (!isset($_CONFIG['sites'][$this->site_domain]['modules'])) {
			$_CONFIG['sites'][$this->site_domain]['modules'] = [];
		}

		return $_CONFIG;
	}

	/**
	 * Sets the current sites configuration file
	 * @param $config \b array The sites configuration file as an array
	 */
	public function setSiteConfig($config) {
		$filename = __DIR__.DS.'..'.DS.'config'.DS.'config.php';

		file_put_contents($filename, '<?php $_CONFIG = '.var_export($config, TRUE).';');
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($filename);
		}
	}

	/**
	 * Checks if the current connection is over https
	 * @return \b boolean TRUE if the connection is https, FLASE otherwise
	 */
	public function isHttps() {
		if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Gets the current sites base URL
	 * @param[in] $ssl_override \b boolean Create SSL url is connection is over HTTPS
	 * @return \b string The sites base URL
	 */
	public function getSiteUrl($ssl_override = FALSE) {
		if ($ssl_override && $this->isHttps()) {
			return $this->getSecureSiteUrl();
		}
		return 'http://www.'.$this->site_domain;
	}

	/**
	 * Gets the current sites base HTTPS URL
	 * @return \b string The sites base URL
	 */
	public function getSecureSiteUrl() {
		if ($this->siteConfig()->enable_ssl) {
			return 'https://www.'.$this->site_domain;
		}
		return 'http://www.'.$this->site_domain;
	}

	/**
	 * Gets the current sites domain
	 * @return \b string The current sites domain
	 */
	public function getSiteDomain() {
		return $this->site_domain;
	}

	/**
	 * Sets the current sites domain
	 * @param $host     \b string The host to set as the current site
	 * @param $redirect \b boolean TRUE to redirect to www.$domain, FALSE otherwise
	 * @throws DomainRedirectException To redirect to a different domain
	 * @throws ConfigException if the host does not reference a site and there is no default site
	 */
	public function setSiteDomain($host, $redirect = TRUE) {
		// strip the port off the host
		$parts = explode(":", $host);
		$host = $parts[0];

		$default_site = NULL;
		$sites = $this->sites;
		foreach ($sites as $domain => $site) {
			if ($redirect && $domain == $host) {
				throw new DomainRedirectException('www.'.$domain);
			}
			elseif ('www.'.$domain == $host || (!$redirect && $domain == $host)) {
				$this->site_domain = $domain;

				// set locale
				setlocale(LC_ALL, $this->siteConfig()->locale);

				return;
			}

			if (!$default_site && $site->default_site) {
				$default_site = $site;
			}
		}

		// redirect to the first default site in the list
		if ($default_site) {
			throw new DomainRedirectException('www.'.$default_site->domain);
		}

		throw new ConfigException("HTTP_HOST does not reference a site: $host");
	}
}
