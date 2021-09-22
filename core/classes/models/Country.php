<?php

namespace core\classes\models;

use core\classes\Model;

class Country extends Model {

	protected $cacheable   = TRUE;
	protected $table       = 'country';
	protected $primary_key = 'country_id';
	protected $columns     = [
		'country_id' => [
			'data_type'      => 'int',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'country_name' => [
			'data_type'      => 'text',
			'data_length'    => '128',
			'null_allowed'   => FALSE,
		],
		'country_code' => [
			'data_type'      => 'text',
			'data_length'    => '2',
			'null_allowed'   => FALSE,
		],
		'country_continent' => [
			'data_type'      => 'text',
			'data_length'    => '2',
			'null_allowed'   => TRUE,
		],
		'country_code3' => [
			'data_type'      => 'text',
			'data_length'    => '3',
			'null_allowed'   => TRUE,
		],
		'country_language' => [
			'data_type'      => 'text',
			'data_length'    => '2',
			'null_allowed'   => TRUE,
		],
		'country_currency' => [
			'data_type'      => 'text',
			'data_length'    => '3',
			'null_allowed'   => TRUE,
		],
	];
	protected $indexes = [
		'lower(country_name)',
		'country_continent',
	];
	protected $uniques = [
		'country_name',
		'country_code',
	];

	public function getLocale() {
		if ($this->language) {
			return $this->language.'_'.$this->code.'.UTF8';
		}
		else {
			return NULL;
		}
	}

	public function getContinents() {
		$sql = "
			SELECT DISTINCT country_continent as continent
			FROM country
			WHERE
				country_continent IS NOT NULL
				AND country_continent != ''
		 ";
		$results = $this->database->queryMulti($sql);
		foreach ($results as $result) {
			$result['text_tag'] = 'continent_'.strtolower($result['continent']);
		}
		return $results;
	}

	public function getCountriesByContinent($filter = NULL) {
		$result = [];
		$countries = $this->getMulti();
		foreach ($countries as $country) {
			if ($country->continent) {
				if ($filter == NULL || $filter($country)) {
					$result[$country->continent][] = $country;
				}
			}
		}
		return $result;
	}

	public function getCurrenciesByContinent($filter = NULL) {
		$result = [];
		$countries = $this->getMulti();
		foreach ($countries as $country) {
			if ($country->continent && $country->currency) {
				if ($filter == NULL || $filter($country)) {
					$result[$country->continent][$country->currency] = $country->code;
				}
			}
		}
		return $result;
	}

	public function updateCountries() {
		set_time_limit(600);

		$url      = 'http://download.geonames.org/export/dump/countryInfo.txt';
		$contents = file_get_contents($url);

		$headers = [];
		$data    = [];
		$lines   = explode("\n", $contents);
		foreach ($lines as $line) {
			if (strlen($line) > 0) {
				$parts = str_getcsv($line, "\t");

				if ($parts[0][0] == '#') {
					$parts[0] = substr($parts[0], 1);
					$headers = $parts;
				}
				else {
					$row = [];
					foreach ($parts as $index => $cell) {
						$row[$headers[$index]] = $cell;
					}
					$data[] = $row;
				}
			}
		}

		foreach ($data as $row) {
			$country = $this->get(['code' => $row['ISO']]);
			if (!$country) {
				$country = $this->getModel('\core\classes\models\Country');
			}

			$language = NULL;
			$languages = explode(',', $row['Languages']);
			if (count($languages)) {
				$language = substr($languages[0], 0, 2);
				if (!$language) $language = NULL;
			}

			$country->code = $row['ISO'];
			$country->code3 = $row['ISO3'];
			$country->name = $row['Country'];
			$country->continent = $row['Continent'];
			$country->language = $language;
			$country->currency = empty($row['CurrencyCode']) ? NULL : $row['CurrencyCode'];

			if ($country->id) {
				$country->update();
			}
			else {
				$country->insert();
			}
		}
	}
}
