<?php

namespace huaweichenai\dmbase\base;

use huaweichenai\dmbase\SqlContext;

abstract class BaseHandler
{
    /**
     * @var BaseHandler
     */
    protected $nextHandler;

    public function setNextHandler(BaseHandler $handler){
        $this->nextHandler = $handler;
    }

    abstract public function handle(SqlContext $sqlContext);

}
