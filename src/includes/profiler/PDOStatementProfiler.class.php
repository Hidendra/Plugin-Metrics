<?php


class PDOStatementProfiler extends PDOStatement
{
    /**
     * The database handle
     * @var PDO
     */
    public $dbh;

    protected function __construct($dbh) {
        $this->dbh = $dbh;
    }

    public function execute(array $input_parameters = NULL)
    {
        $start = millitime();
        $ret = parent::execute($input_parameters);
        function_log('PDOStatement->execute()', millitime() - $start);
        return $ret;
    }

}