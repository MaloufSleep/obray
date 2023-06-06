<?php

if (!function_exists('getDatabaseConnection')) {
	function getDatabaseConnection($reconnect = FALSE)
	{

		global $conn;
		if (!isset($conn) || $reconnect) {
			$conn = new PDO('mysql:host=' . __OBRAY_DATABASE_HOST__ . ';dbname=' . __OBRAY_DATABASE_NAME__ . ';charset=utf8', __OBRAY_DATABASE_USERNAME__, __OBRAY_DATABASE_PASSWORD__, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		return $conn;
	}
}

if (!function_exists('getReaderDatabaseConnection')) {
	function getReaderDatabaseConnection($reconnect = FALSE)
	{
		global $readConn;
		if (!defined('__OBRAY_DATABASE_HOST_READER__')) {
			return getDatabaseConnection($reconnect);
		}
		if (!isset($readConn) || $reconnect) {
			try {
				$readConn = new PDO('mysql:host=' . __OBRAY_DATABASE_HOST_READER__ . ';dbname=' . __OBRAY_DATABASE_NAME__ . ';charset=utf8', __OBRAY_DATABASE_USERNAME__, __OBRAY_DATABASE_PASSWORD__, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
				$readConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch (PDOException $e) {
				echo 'ERROR: ' . $e->getMessage();
				exit();
			}
		}
		return $readConn;
	}
}

if (!function_exists('removeSpecialChars')) {
	function removeSpecialChars($string, $space = '', $amp = '')
	{
		$string = str_replace(' ', $space, $string);
		$string = str_replace('&', $amp, $string);
		return preg_replace('/[^a-zA-Z0-9\-_s]/', '', $string);
	}
}

if (!function_exists('getallheaders')) {
	function getallheaders()
	{
		$headers = [];
		foreach ($_SERVER as $name => $value) {
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}
}
