<?php

include_once 'config/const.php';

/**
 * Simple PDO wrapper
 */
class Db {
    /**
     * @var PDO
     */
    protected static $_connection = null;
    protected static $_modQueries = [];

    /**
     * Get the connection
     *
     * @static
     * @return null|PDO
     */
    public static function connection()
    {
        if (self::$_connection === null) {
            self::$_connection = new PDO(DB_TYPE . ':dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASSWORD);
            self::$_connection->query("SET NAMES utf8");
        }

        return self::$_connection;
    }

    /**
     * Run SELECT query and return associative array
     *
     * @static
     * @param $query
     * @return array
     */
    public static function get($query)
    {
        return self::connection()->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Run modificational query and return status
     *
     * @static
     * @param $query
     * @return int
     */
    public static function exec($query)
    {
        self::$_modQueries []= $query;
        return self::connection()->exec($query);
    }
}
