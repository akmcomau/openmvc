<?php

namespace core\classes;

/* https://www.php.net/manual/en/function.openssl-encrypt.php */

class Encryption {
	/**
	 * Marker byte for ciphertext from the previous encrypt(): AES-256-CBC with a
	 * random IV but no authentication. Still accepted by decrypt().
	 */
	const CIPHER_VERSION_CBC = "\x02";

	/**
	 * Marker byte prefixed to ciphertext produced by encrypt() (AES-256-GCM)
	 */
	const CIPHER_VERSION = "\x03";

	const GCM_IV_LENGTH = 12;

	const GCM_TAG_LENGTH = 16;

	/**
	 * Encrypt a string using AES-256-GCM, which is authenticated: any change to
	 * the ciphertext makes decrypt() fail, rather than silently producing
	 * attacker-influenced plaintext as unauthenticated CBC would.
	 * Format: CIPHER_VERSION | 12 byte IV | 16 byte tag | ciphertext
	 * @param  $string  \b string  The string to encrypt
	 * @param  $key     \b string  The encryption key
	 */
	public static function encrypt($string, $key) {
		$key = hash('sha256', $key, TRUE);
		$iv = random_bytes(self::GCM_IV_LENGTH);
		$tag = '';
		$ciphertext = openssl_encrypt((string)$string, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, self::CIPHER_VERSION, self::GCM_TAG_LENGTH);
		return self::CIPHER_VERSION.$iv.$tag.$ciphertext;
	}

	/**
	 * Decrypt a string encrypted with encrypt(). Also accepts the two older
	 * formats (AES-256-CBC, and the original AES-128-ECB) so values already in
	 * storage stay readable; re-encrypt them to upgrade.
	 * @param $string  \b string  The string to decrypt
	 * @param $key     \b string  The encryption key
	 * @return \b string The plaintext, or FALSE if the ciphertext was tampered with or the key is wrong
	 */
	public static function decrypt($string, $key) {
		if (isset($string[0]) && $string[0] === self::CIPHER_VERSION) {
			$iv = substr($string, 1, self::GCM_IV_LENGTH);
			$tag = substr($string, 1 + self::GCM_IV_LENGTH, self::GCM_TAG_LENGTH);
			$ciphertext = substr($string, 1 + self::GCM_IV_LENGTH + self::GCM_TAG_LENGTH);
			if (strlen($iv) != self::GCM_IV_LENGTH || strlen($tag) != self::GCM_TAG_LENGTH) {
				return FALSE;
			}
			return openssl_decrypt($ciphertext, 'aes-256-gcm', hash('sha256', $key, TRUE), OPENSSL_RAW_DATA, $iv, $tag, self::CIPHER_VERSION);
		}

		if (isset($string[0]) && $string[0] === self::CIPHER_VERSION_CBC) {
			$iv_length = openssl_cipher_iv_length('aes-256-cbc');
			$iv = substr($string, 1, $iv_length);
			$ciphertext = substr($string, 1 + $iv_length);
			return openssl_decrypt($ciphertext, 'aes-256-cbc', hash('sha256', $key, TRUE), OPENSSL_RAW_DATA, $iv);
		}

		// Legacy format
		if (strlen($string) % 16) {
			$string = str_pad($string, strlen($string) + 16 - strlen($string) % 16, "\0");
		}
		if (strlen($key) % 16) {
			$key = str_pad($key, strlen($key) + 16 - strlen($key) % 16, "\0");
		}
		return rtrim(openssl_decrypt($string, "aes-128-ecb", $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING));
	}

	/**
	 * Use bcrypt to create a password hash
	 * @param $string \b string  The password string
	 * @param $cost   \b int     The computational cost
	 */
	public static function bcrypt($string, $cost) {
		if (BCRYPT_IMPLEMENTATION == BCRYPT_IMPLEMENTATION_2A && FALSE) {
			if (strlen($cost) == 1) $cost = '0'.$cost;
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890';
			$salt = '$2a$'.$cost.'$';
			for ($i=0; $i<22; $i++) {
				$salt .= $chars[rand(0, strlen($chars)-1)];
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
	 * Obfuscate an integer using AES-128 (deterministic, single block).
	 * Note: this is intentionally deterministic (same integer always yields the
	 * same token) since it's used to produce a stable, reversible short code
	 * rather than to hide repeated-block structure across multiple blocks -
	 * the entire input is always exactly one 16-byte block, so CBC/ECB make no
	 * difference here. Deprecated 3DES was replaced with AES-128 for its
	 * larger, modern key schedule.
	 * @param  $integer  \b int     The integer to obfuscate
	 * @param  $key      \b string  The encryption key
	 */
	public static function obfuscate($integer, $key) {
		$key = str_pad(substr(hash('sha256', $key, TRUE), 0, 16), 16, "\0");
		$integer = str_pad(pack('I', $integer), 16, "\0");
		$string = openssl_encrypt($integer, "aes-128-ecb", $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
		$string = self::str2Hex($string);
		$string = self::str_baseconvert($string, 16, 36);
		$string = chunk_split(strtoupper($string), 4, '-');
		if (preg_match('/-$/', $string)) {
			$string = substr($string, 0, -1);
		}
		return $string;
	}

	/**
	 * Defuscate a string produced by obfuscate() back to an integer
	 * @param  $string  \b string  The string to defuscate
	 * @param  $key     \b string  The encryption key
	 */
	public static function defuscate($string, $key) {
		$key = str_pad(substr(hash('sha256', $key, TRUE), 0, 16), 16, "\0");
		$string = str_replace('-', '', (string)$string);
		if (!preg_match('/^[0-9A-Za-z]{1,32}$/', $string)) return NULL;
		$string = self::str_baseconvert(strtolower($string), 36, 16);
		// The base conversion drops leading zeros, so restore the full 16 byte
		// (32 hex digit) block; padding only to an even length broke any token
		// whose ciphertext started with a zero byte
		if (strlen($string) > 32 || !ctype_xdigit($string)) return NULL;
		$string = str_pad($string, 32, '0', STR_PAD_LEFT);
		$string = self::hex2Str($string);
		$string = openssl_decrypt($string, "aes-128-ecb", $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
		// obfuscate() zero-pads the integer to a full block; anything else wasn't produced by it
		if ($string === FALSE || strlen($string) != 16 || substr($string, 4) !== str_repeat("\0", 12)) return NULL;
		$string = unpack('I', $string);
		return $string[1];
	}
}
