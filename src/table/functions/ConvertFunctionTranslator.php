<?php

namespace huaweichenai\dmbase\table\functions;

use huaweichenai\dmbase\base\BaseFunctionTranslator;
use huaweichenai\dmbase\SqlContext;
use huaweichenai\dmbase\utils\FunctionHelper;

class ConvertFunctionTranslator extends BaseFunctionTranslator
{
    const FUNCTION_NAME = 'CONVERT';

    /**
     * @throws \huaweichenai\dmbase\exceptions\DbxException
     */
    protected function internalTranslate($parsed)
    {
        FunctionHelper::replaceFunctionEntrance($parsed, self::FUNCTION_NAME, '');
        return $parsed;
    }
}
