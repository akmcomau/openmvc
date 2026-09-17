<?php

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use core\classes\Encryption;

class EncryptionTest extends TestCase {

	public function testEncryptDecryptRoundTrip(): void {
		$plaintext = 'The quick brown fox jumps over the lazy dog - spanning multiple AES blocks.';
		$key = 'a test key';

		$ciphertext = Encryption::encrypt($plaintext, $key);

		$this->assertNotSame($plaintext, $ciphertext);
		$this->assertSame($plaintext, Encryption::decrypt($ciphertext, $key));
	}

	public function testEncryptIsNonDeterministic(): void {
		// A random IV means the same plaintext/key never produces the same
		// ciphertext twice - this is what makes the scheme resistant to the
		// pattern leakage that AES-ECB (the previous implementation) had.
		$plaintext = 'repeat me repeat me repeat me repeat me';
		$key = 'same-key';

		$a = Encryption::encrypt($plaintext, $key);
		$b = Encryption::encrypt($plaintext, $key);

		$this->assertNotSame($a, $b);
		$this->assertSame($plaintext, Encryption::decrypt($a, $key));
		$this->assertSame($plaintext, Encryption::decrypt($b, $key));
	}

	public function testDecryptWithWrongKeyDoesNotReturnOriginalPlaintext(): void {
		$plaintext = 'sensitive value';
		$ciphertext = Encryption::encrypt($plaintext, 'correct key');

		$this->assertNotSame($plaintext, Encryption::decrypt($ciphertext, 'wrong key'));
	}

	public function testDecryptAcceptsLegacyAes128EcbFormat(): void {
		// Values encrypted before the AES-256-CBC upgrade must remain
		// readable. Reproduce the old (pre-fix) encrypt() here exactly.
		$key = 'legacy-key';
		$plaintext = 'old data';

		$padded_string = str_pad($plaintext, strlen($plaintext) + 16 - strlen($plaintext) % 16, "\0");
		$padded_key = str_pad($key, strlen($key) + 16 - strlen($key) % 16, "\0");
		$legacy_ciphertext = openssl_encrypt($padded_string, 'aes-128-ecb', $padded_key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);

		$this->assertSame($plaintext, Encryption::decrypt($legacy_ciphertext, $key));
	}

	public function testObfuscateDefuscateRoundTrip(): void {
		$key = 'obfuscation-key';

		foreach ([0, 1, 42, 123456, 4294967295] as $id) {
			$token = Encryption::obfuscate($id, $key);
			$this->assertSame($id, Encryption::defuscate($token, $key));
		}
	}

	public function testObfuscateTokenFormat(): void {
		// Tokens are uppercase base36 chunks separated by '-', e.g. XXXX-XXXX.
		$token = Encryption::obfuscate(12345, 'a-key');
		$this->assertMatchesRegularExpression('/^[0-9A-Z]{1,4}(-[0-9A-Z]{1,4})*$/', $token);
	}

	public function testDefuscateWithWrongKeyDoesNotReturnOriginalInteger(): void {
		$token = Encryption::obfuscate(999, 'right-key');
		$this->assertNotSame(999, Encryption::defuscate($token, 'wrong-key'));
	}

	public function testBcryptHashAndVerify(): void {
		$hash = Encryption::bcrypt('my password', 10);

		$this->assertTrue(Encryption::bcrypt_verify('my password', $hash));
		$this->assertFalse(Encryption::bcrypt_verify('wrong password', $hash));
	}

	public function testStr2HexAndHex2StrRoundTrip(): void {
		$string = "binary\x00data\xff";
		$hex = Encryption::str2Hex($string);

		$this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $hex);
		$this->assertSame($string, Encryption::hex2Str($hex));
	}

	public function testStrBaseconvertRoundTrip(): void {
		$decimal = '123456789012345';
		$base36 = Encryption::str_baseconvert($decimal, 10, 36);
		$back = Encryption::str_baseconvert($base36, 36, 10);

		$this->assertSame($decimal, $back);
	}
}
