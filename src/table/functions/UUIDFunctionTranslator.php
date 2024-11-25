<?php

namespace huaweichenai\dmbase\table\functions;

use huaweichenai\dmbase\base\BaseFunctionTranslator;
use huaweichenai\dmbase\utils\FunctionHelper;

class UUIDFunctionTranslator extends BaseFunctionTranslator
{
    const FUNCTION_NAME = 'UUID';

    /**
     * @throws \huaweichenai\dmbase\exceptions\DbxException
     */
    protected function internalTranslate($parsed)
    {
        FunctionHelper::replaceFunctionEntrance($parsed, self::FUNCTION_NAME, 'NEWID');
        return $parsed;
    }
}
