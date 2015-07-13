<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15-7-7
 * Time: 下午5:59
 */

class L_Db_Mysql {

    /** @var mysqli $link */
    var $link = NULL;

    function __destruct() {

        $this->quit();

    }

    function isConnected() {
        if ($this->link instanceof mysqli) return true;
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
        return $this->link->error;
    }

    function connect($host, $user, $pwd, $dbname, $charset) {
        if($this->link = @new mysqli($host, $user, $pwd, $dbname)) {
            if(mysqli_connect_errno()) {
                $this->link = NULL;
                return false;
            } else {
                $this->link->set_charset($charset);
                return true;
            }
        } else {
            $this->link = NULL;
            return false;
        }
    }

    function getInsertID() {
        return $this->link->insert_id;
    }

    public function getAffectRows() {
        return $this->link->affected_rows;
    }

}