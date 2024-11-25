<?php

namespace huaweichenai\dmbase\table\functions;

use huaweichenai\dmbase\exceptions\DbxException;
use huaweichenai\dmbase\SqlContext;
use huaweichenai\dmbase\utils\StringHelper;

class DatabaseFunctionValidator implements \huaweichenai\dmbase\interfaces\FunctionValidatorInterface
{
    const FUNCTION_NAME = 'DATABASE';

    /**
     * @throws DbxException
     */
    public function validate(SqlContext $sqlContext)
    {
        $parserArr = $sqlContext->getParsed();
        foreach ($parserArr as $key => &$item) {
            if ($key == self::FUNCTION_NAME){
                throw new DbxException("不支持系统函数database的翻译和执行");
            }
        }
        $sqlContext->setParsed($parserArr);

        return $sqlContext;
    }

    private function validateFunction(&$item,$name){

    }
}
