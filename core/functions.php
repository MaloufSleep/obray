<?php

if (!function_exists('getDatabaseConnection')) {
    /**
     * Declares the global `$conn` variable, builds a PDO object, sets `$conn` to the PDO object, and returns the PDO object.
     * @param bool $reconnect
     * @return \PDO
     */
    function getDatabaseConnection(bool $reconnect = false): PDO
    {
        global $conn;

        if ($conn && !$reconnect) {
            return $conn;
        }

        if ($resolver = ODBO::getPdoResolver()) {{
            return $conn = $resolver();
        }}

        return $conn = buildDefaultPdoObject(
            __OBRAY_DATABASE_HOST__,
            __OBRAY_DATABASE_NAME__,
            __OBRAY_DATABASE_USERNAME__,
            __OBRAY_DATABASE_PASSWORD__
        );
    }
}

if (!function_exists('getReaderDatabaseConnection')) {
    function getReaderDatabaseConnection($reconnect = false): PDO
    {
        global $readConn;

        if ($readConn && !$reconnect) {
            return $readConn;
        }

        if ($resolver = ODBO::getReadPdoResolver()) {
            return $readConn = $resolver();
        }

        if (!defined('__OBRAY_DATABASE_HOST_READER__')) {
            return getDatabaseConnection($reconnect);
        }

        return $readConn = buildDefaultPdoObject(
            __OBRAY_DATABASE_HOST_READER__,
            __OBRAY_DATABASE_NAME__,
            __OBRAY_DATABASE_USERNAME__,
            __OBRAY_DATABASE_PASSWORD__
        );
    }
}

if (!function_exists('buildDefaultPdoObject')) {
    function buildDefaultPdoObject($host, $db, $username, $password): PDO
    {
        $pdo = new PDO(
            'mysql:host=' . $host . ';dbname=' . $db . ';charset=utf8',
            $username,
            $password,
            array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8')
        );

        if (defined('__OBRAY_DATABASE_ATTRIBUTES__')) {
            foreach (__OBRAY_DATABASE_ATTRIBUTES__ as $attribute => $value) {
                $pdo->setAttribute($attribute, $value);
            }
        } else {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $pdo;
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
