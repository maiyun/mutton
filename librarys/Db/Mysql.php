<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15-7-7
 * Time: 下午5:59
 */

namespace Chameleon\Library\Db;

class Mysql {

    /** @var mysqli $link */
    var $link = NULL;

    function __destruct() {

        $this->quit();

    }

    function isConnected() {
        if ($this->link instanceof \mysqli) return true;
        return false;
    }

    function quit() {
        $this->link = NULL;
    }

    function escape($str) {
        return $this->link->real_escape_string($str);
    }

    function query($sql) {
        return $this->link->query($sql);
    }

    function getError() {
        if(@isset($this->link->error))
            return $this->link->error;
        else
            return $this->link->connect_error;
    }

    function connect($host, $user, $pwd, $dbname, $charset) {
        if($this->link = @new \mysqli($host, $user, $pwd, $dbname)) {
            if(mysqli_connect_errno())
                return false;
            else {
                $this->link->set_charset($charset);
                return true;
            }
        } else
            return false;
    }

    function getInsertID() {
        return $this->link->insert_id;
    }

    public function getAffectRows() {
        return $this->link->affected_rows;
    }

}