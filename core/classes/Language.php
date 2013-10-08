<?php

namespace core\classes;

use core\classes\exceptions\LanguageException;

class Language {

	protected $config;
	protected $logger;
	protected $language;

	protected $strings = [];

	public function __construct(Config $config) {
		$this->config   = $config;
		$this->language = $config->siteConfig()->language;
		$this->logger   = Logger::getLogger(__CLASS__);
	}

	public function getLanguage() {
		return $this->language;
	}

	public function getStrings() {
		return $this->strings;
	}

	public function get($tag) {
		return $this->strings[$tag];
	}

	public function loadLanguageFile($filename) {
		// load the language file
		$filename = $this->getAbsoluteFilename($filename);
		require($filename);
		$this->strings = array_merge($this->strings, $_LANGUAGE);
	}

	public function getAbsoluteFilename($filename) {
		$site = $this->config->siteConfig();
		$theme = $site->theme;

		$root_path = __DIR__.DS.'..'.DS.'..'.DS;
		$default_path = 'core'.DS.'language'.DS.$this->language.DS;
		$default_file = $root_path.$default_path.$filename;
		$theme_path = 'sites'.DS.$site->namespace.DS.'language'.DS.$this->language.DS;
		$theme_file = $root_path.$theme_path.$filename;
		if (file_exists($theme_file)) {
			return $theme_file;
		}
		if (file_exists($default_file)) {
			return $default_file;
		}
		else {
			throw new LanguageException("Could not find language file: $filename");
		}
	}
}