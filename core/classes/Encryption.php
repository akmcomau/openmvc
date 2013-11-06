<?php

namespace core\classes;

class Encryption {
	public static function encrypt($string, $key) {
		return mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_ECB);
	}

	public static function decrypt($string, $key) {
		return mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_ECB);
	}

	public static function bcrypt($string, $cost) {
		return password_hash($string, PASSWORD_BCRYPT, ['cost' => $cost]);
	}

	public static function bcrypt_verify($string, $hash) {
		return password_verify($string, $hash);
	}

	public static function str2Hex($string) {
		$hexstr = @unpack("H*", $string);
		return array_shift($hexstr);
	}

	public static function hex2Str($string) {
		$hexstr = @pack("H*", $string);
		return $hexstr;
	}

	public static function  str_baseconvert($str, $frombase=10, $tobase=36) {
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

	public static function defuscate($string, $key) {
		$string = str_replace('-', '', $string);
		$string = self::str_baseconvert($string, 36, 16);
		if (strlen($string) % 2) $string = '0'.$string;
		$string = self::hex2Str($string);
		$string = mcrypt_decrypt(MCRYPT_3DES, $key, $string, MCRYPT_MODE_ECB);
		print $string.'<br />';
		$string = unpack('I', $string);
		return $string[1];
	}
}
