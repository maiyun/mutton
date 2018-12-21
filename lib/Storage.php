<?php
/**
 * User: JianSuoQiYue
 * Date: 2018-12-7 23:49:08
 * Last: 2018-12-16 00:00:11
 */
declare(strict_types = 1);

namespace lib;

use lib\Storage\IStorage;

require ETC_PATH.'storage.php';

class Storage {

    /**
     * @param string $name
     * @param array $opt
     * @return IStorage
     * @throws \Exception
     */
    public static function get(string $name, array $opt = []): IStorage {
        try {
            require LIB_PATH . 'Storage/' . $name . '.php';
            $class = 'lib\\Storage\\' . $name;
            return new $class($opt);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

}

