<?php

namespace core\classes;

use NumberFormatter;
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
	 * @var array The configuration data as an array
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
		$filename = $this->getConfigFile();
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
					$clones[$domain] = clone $site_data;
					if (property_exists($site_data, 'themes') && property_exists($site_data->themes, $domain)) {
						$clones[$domain]->default_theme = $site_data->theme;
						$clones[$domain]->theme = $site_data->themes->$domain;
					}
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

	protected function getConfigFile() {
		if (isset($_SERVER['OPENMVC_CONFIG_FILE'])) {
			return $_SERVER['OPENMVC_CONFIG_FILE'];
		}
		else {
			return __DIR__.DS.'..'.DS.'config'.DS.'config.php';
		}
	}

	/**
	 * Magic getter for the class, for fetching config values
	 * @param $name \b string TRUE if the connection is from a robot, FALSE otherwise
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
	 * @param $value \b boolean TRUE if the connection is from a robot, FALSE otherwise
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
	 * @param $module \b string The modules name
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
	 * @param $module \b array The modules specification
	 */
	public function installModule(array $module) {
		$filename = $this->getConfigFile();
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
	 * @param $module \b array The modules specification
	 */
	public function uninstallModule($module) {
		$filename = $this->getConfigFile();
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
	 * @param $module \b array The modules specification
	 */
	public function enableModule($module) {
		$filename = $this->getConfigFile();
		require($filename);

		if (!isset($_CONFIG['sites'][$this->siteConfig()->domain]['modules'])) {
			$_CONFIG['sites'][$this->siteConfig()->domain]['modules'] = [];
		}

		if (!in_array($module['namespace'], $_CONFIG['sites'][$this->siteConfig()->domain]['modules'])) {
			$_CONFIG['sites'][$this->siteConfig()->domain]['modules'][$module['namespace']] = $module['default_config'];
		}

		file_put_contents($filename, '<?php $_CONFIG = '.var_export($_CONFIG, TRUE).';');
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($filename);
		}
	}

	/**
	 * Disables a module on the current site
	 * @param $module \b array The modules specification
	 */
	public function disableModule($module) {
		$filename = $this->getConfigFile();
		require($filename);

		if (!isset($_CONFIG['sites'][$this->siteConfig()->domain]['modules'])) {
			$_CONFIG['sites'][$this->siteConfig()->domain]['modules'] = [];
		}

		if (isset($_CONFIG['sites'][$this->siteConfig()->domain]['modules'][$module['namespace']])) {
			unset($_CONFIG['sites'][$this->siteConfig()->domain]['modules'][$module['namespace']]);
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
	 * @param $name  \b string The site configuration setting's name
	 * @param $value \b mixed The value to store
	 */
	public function updateSiteConfigParam($name, $value) {
		$this->sites->{$this->site_domain}->{$name} = $value;
	}

	/**
	 * Updates a main configuaration value for this request
	 * @param $name  \b string The main configuration setting's name
	 * @param $value \b mixed The value to store
	 */
	public function updateConfigParam($name, $value) {
		$this->sites->{$name} = $value;
	}

	/**
	 * Gets the current sites raw configuration data
	 * @return \b array The sites configuration file as an array
	 */
	public function getSiteConfig() {
		$filename = $this->getConfigFile();
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
		$filename = $this->getConfigFile();

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
	 * @param $ssl_override \b boolean Create SSL url is connection is over HTTPS
	 * @return \b string The sites base URL
	 */
	public function getSiteUrl($ssl_override = FALSE) {
		$domain = $this->site_domain;
		if ($this->siteConfig()->force_www_subdomain) {
			$domain = 'www.'.$domain;
		}

		if ($ssl_override && $this->isHttps()) {
			return $this->getSecureSiteUrl();
		}
		return 'http://'.$domain;
	}

	/**
	 * Gets the current sites base HTTPS URL
	 * @return \b string The sites base URL
	 */
	public function getSecureSiteUrl() {
		$domain = $this->site_domain;
		if ($this->siteConfig()->force_www_subdomain) {
			$domain = 'www.'.$domain;
		}

		if ($this->siteConfig()->enable_ssl) {
			return 'https://'.$domain;
		}
		return 'http://'.$domain;
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
			if ($domain == $host) {
				if ($redirect && $site->force_www_subdomain) {
					throw new DomainRedirectException('www.'.$domain);
				}
				else {
					$this->site_domain = $domain;
					$locale = $this->siteConfig()->locale;
					$formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
					$this->siteConfig()->site_currency = $formatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);
					$this->setLocale($locale);
					return;
				}
			}
			elseif ('www.'.$domain == $host || (!($redirect && $site->force_www_subdomain) && $domain == $host)) {
				$this->site_domain = $domain;
				$locale = $this->siteConfig()->locale;
				$formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
				$this->siteConfig()->site_currency = $formatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);
				$this->setLocale($locale);
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

	/**
	 * Sets the current locale
	 * @param $locale  \b string The host to set as the current site
	 */
	public function setLocale($locale) {
		$this->siteConfig()->site_locale = $locale;
		$formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
		$this->siteConfig()->currency = $formatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);

		setlocale(LC_MONETARY, $locale);
	}

	/**
	 * Update the modules in the config file
	 */
	public function updateConfig() {
		$filename = $this->getConfigFile();
		require($filename);
		if (!isset($_CONFIG['modules'])) $_CONFIG['modules'] = [];

		$module_class = new Module($this);
		$modules = $module_class->getModules();
		foreach ($modules as $namespace => $module) {
			if ($module['enabled'] && isset($module['default_config'])) {
				foreach ($module['default_config'] as $name => $value) {
					if (!isset($_CONFIG['sites'][$this->site_domain]['modules'][$namespace][$name])) {
						$_CONFIG['sites'][$this->site_domain]['modules'][$namespace][$name] = $value;
					}
				}
			}
		}

		file_put_contents($filename, '<?php $_CONFIG = '.var_export($_CONFIG, TRUE).';');
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($filename);
		}
	}
}
