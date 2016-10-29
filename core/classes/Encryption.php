<?php

namespace core\classes;

class Encryption {
	/**
	 * Encrypt a string using MCRYPT_RIJNDAEL_128
	 * @param  $string  \b string  The string to encrypt
	 * @param  $key     \b string  The encryption key
	 */
	public static function encrypt($string, $key) {
		return mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_ECB);
	}

	/**
	 * Decrypt a string using MCRYPT_RIJNDAEL_128
	 * @param $string  \b string  The string to decrypt
	 * @param $key     \b string  The encryption key
	 */
	public static function decrypt($string, $key) {
		return mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_ECB);
	}

	/**
	 * Use bcrypt to create a password hash
	 * @param $string \b string  The password string
	 * @param $cost   \b int     The computational cost
	 */
	public static function bcrypt($string, $cost) {
		if (BCRYPT_IMPLEMENTATION == BCRYPT_IMPLEMENTATION_2A) {
			if (strlen($cost) == 1) $cost = '0'.$cost;
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890';
			$salt = '$2a$'.$cost.'$';
			for ($i=0; $i<22; $i++) {
				$salt .= $chars{rand(0, strlen($chars)-1)};
			}
			return crypt($string, $salt);
		}
		else {
		   return password_hash($string, PASSWORD_BCRYPT, ['cost' => $cost]);
		}
	}

	/**
	 * Verify a password using bcrypt
	 * @param $string \b string  The password string
	 * @param $hash   \b int     The bcrypt hash
	 */
	public static function bcrypt_verify($string, $hash) {
		return password_verify($string, $hash);
	}

	/**
	 * Convert a string to a hash string
	 * @param $string \b string  The string to convert
	 */
	public static function str2Hex($string) {
		$hexstr = @unpack("H*", $string);
		return array_shift($hexstr);
	}

	/**
	 * Convert a hex string back to a string
	 * @param $string \b string  The string to convert
	 */
	public static function hex2Str($string) {
		$hexstr = @pack("H*", $string);
		return $hexstr;
	}

	/**
	 * Convert a large integer from one base to another.  Use when there is
	 * a loss of precision on large numbers.
	 * @param $string   \b string  The string to convert
	 * @param $frombase \b int     The base the number is currently in
	 * @param $tobase   \b int     The base to convert the number to
	 */
	public static function str_baseconvert($str, $frombase=10, $tobase=36) {
		$str = trim($str);
		if (intval($frombase) != 10) {
			$len = strlen($str);
			$q = 0;
			for ($i=0; $i<$len; $i++) {
				$r = base_convert($str[$i], $frombase, 10);
				$q = bcadd(bcmul($q, $frombase), $r);
			}
		}
		else $q = $str;

		if (intval($tobase) != 10) {
			$s = '';
			while (bccomp($q, '0', 0) > 0) {
				$r = intval(bcmod($q, $tobase));
				$s = base_convert($r, 10, $tobase) . $s;
				$q = bcdiv($q, $tobase, 0);
			}
		}
		else $s = $q;

		return $s;
	}

	/**
	 * Obfuscate an integer using MCRYPT_3DES
	 * @param  $integer  \b int     The integer to obfuscate
	 * @param  $key      \b string  The encryption key
	 */
	public static function obfuscate($integer, $key) {
		$integer = pack('I', $integer);
		$string = mcrypt_encrypt(MCRYPT_3DES, $key, $integer, MCRYPT_MODE_ECB);
		$string = self::str2Hex($string);
		$string = self::str_baseconvert($string, 16, 36);
		$string = chunk_split(strtoupper($string), 4, '-');
		if (preg_match('/-$/', $string)) {
			$string = substr($string, 0, -1);
		}
		return $string;
	}

	/**
	 * Defuscate a string using MCRYPT_3DES back to an integer
	 * @param  $string  \b string  The string to defuscate
	 * @param  $key     \b string  The encryption key
	 */
	public static function defuscate($string, $key) {
		$string = str_replace('-', '', $string);
		$string = self::str_baseconvert($string, 36, 16);
		if (strlen($string) % 2) $string = '0'.$string;
		$string = self::hex2Str($string);
		$string = mcrypt_decrypt(MCRYPT_3DES, $key, $string, MCRYPT_MODE_ECB);
		$string = unpack('I', $string);
		return $string[1];
	}
}
