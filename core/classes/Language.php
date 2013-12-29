<?php

namespace core\classes;

use core\classes\exceptions\LanguageException;

class Language {


	/**
	 * The configuration object
	 * @var Config $config
	 */
	protected $config;

	/**
	 * The logger object
	 * @var Logger $logger
	 */
	protected $logger;

	protected $language;

	protected $strings = [];

	protected $loaded_files = [];

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

	// use params like '%1$d items' => [1]
	public function get($tag, $params = NULL) {
		if (!isset($this->strings[$tag])) {
			throw new LanguageException("Language string not defined: $tag");
		}

		if ($params) {
			return vsprintf($this->strings[$tag], $params);
		}
		else {
			return $this->strings[$tag];
		}
	}

	public function getFile($file, $path = NULL) {
		$filename = $this->getAbsoluteFilename($file, $path);
		require($filename);
		return $_LANGUAGE;
	}

	public function updateFile($file, array $strings) {
		$file = str_replace('\\', '/', $file);
		$site = $this->config->siteConfig();
		$root_path = __DIR__.DS.'..'.DS.'..'.DS;
		$theme_path = 'sites'.DS.$site->namespace.DS.'language'.DS.$this->language.DS;
		$theme_file = $root_path.$theme_path.$file;

		if (preg_match('|^(.*)/([^/]+)$|', $file, $matches)) {
			$path = str_replace('/', DS, $matches[1]);
			if ($path == '-') $path = '';
			$theme_path .= $path.DS;
			$theme_file = $root_path.$theme_path.$matches[2];
		}

		if (!is_dir($theme_path)) {
			mkdir($theme_path, 0775, TRUE);
		}
		file_put_contents($theme_file, '<?php $_LANGUAGE = '.var_export($strings, TRUE).';');
	}

	public function loadLanguageFile($filename, $path = NULL) {
		// load the language file
		$file = $filename;
		$filename = $this->getAbsoluteFilename($filename, $path);
		require($filename);
		$this->strings = array_merge($this->strings, $_LANGUAGE);
		$this->loaded_files[] = [$file, $path];
	}

	public function getAbsoluteFilename($filename, $path = NULL) {
		$filename = str_replace('\\', DS, $filename);
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
		if ($path) {
			$theme_file = 'sites'.DS.$site->namespace.DS.'language'.DS.$this->language.DS.$path.DS.$filename;
			if (file_exists($theme_file)) {
				return $theme_file;
			}
			$path_file = $root_path.$path.DS.'language'.DS.$this->language.DS.$filename;
			if (file_exists($path_file)) {
				return $path_file;
			}
		}

		throw new LanguageException("Could not find language file: $filename");
	}

	public function getLoadedFiles() {
		return $this->loaded_files;
	}

	public function getLanguageFiles(array $params = NULL, array $ordering = NULL, array $pagination = NULL) {
		$site = $this->config->siteConfig();
		$root_path = __DIR__.DS.'..'.DS.'..'.DS;
		$base_core_path = $root_path.'core'.DS.'language'.DS.$site->language.DS;
		$base_site_path = $root_path.'sites'.DS.$site->namespace.DS.'language'.DS.$site->language.DS;

		$globs = [
			$base_site_path.'*.php' => $base_site_path,
			$base_site_path.'*'.DS.'*.php' => $base_site_path,
			$base_core_path.'*.php' => $base_core_path,
			$base_core_path.'*'.DS.'*.php' => $base_core_path,
		];

		$added = [];
		$files = [];
		foreach ($globs as $glob => $path) {
			$path_length = strlen($path);
			foreach (glob($glob) as $filename) {
				$file = substr($filename, $path_length);
				$file = str_replace(DS, '\\', $file);

				$_LANGUAGE = [];
				$filename = $this->getAbsoluteFilename($file, $path);
				require($filename);

				// check the params
				$add_method = TRUE;
				if ($params) {
					foreach ($params as $property => $value) {
						if ($property == 'file' && strpos(strtolower($file), $value) === FALSE) {
							$add_method = FALSE;
						}
					}
				}

				// make sure it isnot already added
				if (isset($added[$file])) {
					$add_method = FALSE;
				}

				if ($add_method) {
					$added[$file] = TRUE;
					$files[] = [
						'file' => $file,
						'count' => count($_LANGUAGE),
					];
				}
			}
		}

		// do the ordering
		if ($ordering) {
			foreach ($ordering as $field => $direction) {
				$direction = (strtolower($direction) == 'asc') ? TRUE : FALSE;

				// sort the list
				if (in_array($field, ['file', 'count'])) {
					usort($files, function ($a, $b) use ($field, $direction) {
						if ($a[$field] < $b[$field]) return $direction ? -1 : 1;
						if ($a[$field] > $b[$field]) return $direction ? 1 : -1;
						return 0;
					});
				}
			}
		}

		// do the pagination
		if ($pagination) {
			$files = array_splice($files, $pagination['offset'], $pagination['limit']);
		}

		return $files;
	}
}