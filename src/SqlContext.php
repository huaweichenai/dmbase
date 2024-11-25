<?php

namespace huaweichenai\dmbase;

use PHPSQLParser\PHPSQLParser;

class SqlContext
{
    /**
     * @var string
     */
    private $rawSql;

    /**
     * @var array
     */
    private $rawParsed;

    /**
     * @var array
     */
    private $parsed;

    public function __construct($rawSql, PHPSQLParser $parser)
    {
        $this->rawSql = $rawSql;
        $this->parsed = $this->rawParsed =  $parser->parse($rawSql);
    }

    public function getRawSql(){
        return $this->rawSql;
    }

    public function getParsed(){
        return $this->parsed;
    }

    public function setParsed($parsed){
        $this->parsed = $parsed;
    }
}
