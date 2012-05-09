<?php

/**
 * Convenient class to profile pdo execution times
 */

class PDOProfiler extends PDO
{

    public function __construct($dsn, $username="", $password="", $driver_options=array())
    {
        parent::__construct($dsn,$username,$password, $driver_options);
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('PDOStatementProfiler', array($this)));
    }

    public function prepare($statement, array $driver_options = array())
    {
        $start = millitime();
        $ret = parent::prepare($statement, $driver_options);
        function_log('PDO->prepare()', millitime() - $start, $statement);
        return $ret;
    }

    public function beginTransaction()
    {
        $start = millitime();
        $ret = parent::beginTransaction();
        function_log('PDO->beginTransaction()', millitime() - $start);
        return $ret;
    }

    public function commit()
    {
        $start = millitime();
        $ret = parent::commit();
        function_log('PDO->commit()', millitime() - $start);
        return $ret;
    }

    public function rollBack()
    {
        $start = millitime();
        $ret = parent::rollBack();
        function_log('PDO->rollBack()', millitime() - $start);
        return $ret;
    }


}