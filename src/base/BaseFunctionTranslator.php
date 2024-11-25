<?php

namespace huaweichenai\dmbase\base;

use huaweichenai\dmbase\SqlContext;

abstract class BaseFunctionTranslator implements \huaweichenai\dmbase\interfaces\FunctionTranslatorInterface
{

    public function translate(SqlContext $sqlContext)
    {
        $sqlContext->setParsed($this->internalTranslate($sqlContext->getParsed()));
        return $sqlContext;
    }

    abstract protected function internalTranslate($parsed);
}
