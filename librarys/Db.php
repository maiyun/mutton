<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15-7-7
 * Time: 下午1:51
 */

namespace Chameleon\Library;

class Db {

    var $queries = 0;
    var $source = NULL;

    // --- 可编辑变量 ---

    var $host = '';
    var $user = '';
    var $pwd = '';
    var $dbname = '';
    var $charset = '';
    var $pre = '';

    function __construct() {

    }

    function __destruct() {

    }

    function isConnected() {
        return $this->source? $this->source->isConnected(): false;
    }

    function quit() {
        if ($this->source) {
            $this->queries = 0;
            $this->source = null;
        }
    }

    function escape($str) {
        return $this->source->escape($str);
    }

    function query($sql, $error = true) {
        ++$this->queries;
        // --- 如果 $sql 是布尔型，实际上就是 $error 的值 ---
        if(is_bool($sql))
            $error = $sql;
        if($r = $this->source->query($sql)) return $r;
        else
            if($error) logs('L(Db)',  $this->source->getError() , $sql);
            else return $r;
    }

    function getError() {
        return $this->source->getError();
    }

    function connect($mod = 'Mysql') {

        $this->host = $this->host == '' ? DBHOST : $this->host;
        $this->user = $this->user == '' ? DBUSER : $this->user;
        $pwd = $this->pwd == '' ? DBPW : $this->pwd;
        $this->dbname = $this->dbname == '' ? DBNAME : $this->dbname;
        $this->charset = $this->charset == '' ? DBCHARSET : $this->charset;
        $this->pre = $this->pre == '' ? DBPRE : $this->pre;

        if($mod == 'Mysql') {
            require(LIB_PATH.'Db/Mysql.php');
            $this->source = new \Chameleon\Library\Db\Mysql();
            return $this->source->connect($this->host, $this->user, $pwd, $this->dbname, $this->charset);
        }
    }

    function getQueries() {
        return $this->queries;
    }

    function getInsertID() {
        return $this->source->getInsertID();
    }

    public function getAffectRows() {
        return $this->source->getAffectRows();
    }

}