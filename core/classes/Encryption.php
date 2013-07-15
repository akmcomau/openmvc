<?php

namespace core\classes;

class Encryption {
	public static function encrypt($string, $key) {
		srand((double) microtime() * 1000000);
		$key = md5($key);

		/* Open module, and create IV */
		$td = mcrypt_module_open('rijndael-128', '','cfb', '/usr/lib/');
		$key = substr($key, 0, mcrypt_enc_get_key_size($td));
		$iv_size = mcrypt_enc_get_iv_size($td);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

		/* Initialize encryption handle */
		if (mcrypt_generic_init($td, $key, $iv) != -1) {
			/* Encrypt data */
			$c_t = mcrypt_generic($td, $string);
			mcrypt_generic_deinit($td);
			mcrypt_module_close($td);
			$c_t = $iv.$c_t;
			return $c_t;
		}
	}

	public static function decrypt($string, $key) {
		$key = md5($key);

		/* Open module, and create IV */
		$td = mcrypt_module_open('rijndael-128', '','cfb', '/usr/lib/');
		$key = substr($key, 0, mcrypt_enc_get_key_size($td));
		$iv_size = mcrypt_enc_get_iv_size($td);
		$iv = substr($string,0,$iv_size);
		$string = substr($string,$iv_size);

		/* Initialize encryption handle */
		if (mcrypt_generic_init($td, $key, $iv) != -1) {
			/* Encrypt data */
			$c_t = mdecrypt_generic($td, $string);
			mcrypt_generic_deinit($td);
			mcrypt_module_close($td);
			return $c_t;
		}
	}

	public static function str2Hex($string) {
		$hexstr = @unpack("H*", $string);
		return array_shift($hexstr);
	}

	public static function hex2Str($string) {
		$hexstr = @pack("H*", $string);
		return $hexstr;
	}
}
