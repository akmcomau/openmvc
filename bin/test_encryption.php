#!/usr/bin/env php
<?php

// drop all tables in postgres database
//   drop schema public cascade;
//   create schema public;

use core\classes\Database;
use core\classes\Config;
use core\classes\Logger;
use core\classes\Model;
use core\classes\AutoLoader;
use core\classes\Encryption;

include('core/ErrorHandler.php');
include('core/Constants.php');
include('core/classes/AutoLoader.php');
AutoLoader::init();
Logger::init();

$logger     = Logger::getLogger('');
$config     = new Config();

if (isset($argv[1])) {
	$config->setSiteDomain($argv[1], FALSE);
}

$database   = new Database($config);
$model = new Model($config, $database);

print "\n";
print "------------------------\n";
print "---------  DES3  -------\n";
print "------------------------\n";

$des3_key = "secret";
$des3_value = rand();
$result = Encryption::obfuscate($des3_value, $des3_key);
print "    Value: ".$des3_value."\n";
print "Encrypted: ".$result."\n";
print "Decrypted: ".Encryption::defuscate($result, $des3_key)."\n";
print "    Match: ".((Encryption::defuscate($result, $des3_key) == $des3_value) ? 'Yes' : '** NO **')."\n\n";


print "\n";
print "------------------------\n";
print "----------  AES  -------\n";
print "------------------------\n";
$aes_key = "secret";
$aes_value = "This is a random number: ".rand();
$result = Encryption::encrypt($aes_value, $aes_key);
print "    Value: ".$aes_value."\n";
print "Encrypted: ".Encryption::str2Hex($result)."\n";
print "Decrypted: ".Encryption::decrypt($result, $aes_key)."\n\n";
print "    Match: ".((Encryption::decrypt($result, $aes_key) == $aes_value) ? 'Yes' : '** NO **')."\n\n";


print "\n";
print "------------------------\n";
print "--------  BCRYPT  ------\n";
print "------------------------\n";
$bcrypt_cost = 10;
$bcrypt_value = "password".rand();
$result = Encryption::bcrypt($bcrypt_value, $bcrypt_cost);
print "    Value: ".$bcrypt_value."\n";
print "Encrypted: ".$result."\n";
print "    Match: ".(Encryption::bcrypt_verify($bcrypt_value, $result) ? 'Yes' : '** NO **')."\n";
print "Not Match: ".(Encryption::bcrypt_verify($bcrypt_value.'1', $result) ? '** NO **' : 'Yes')."\n\n";
