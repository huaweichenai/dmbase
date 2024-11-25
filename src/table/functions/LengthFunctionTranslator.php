<?php

namespace huaweichenai\dmbase\table\functions;

use huaweichenai\dmbase\base\BaseFunctionTranslator;
use huaweichenai\dmbase\utils\FunctionHelper;

class LengthFunctionTranslator extends BaseFunctionTranslator
{
    const FUNCTION_NAME = 'LENGTH';

    /**
     * @throws \huaweichenai\dmbase\exceptions\DbxException
     */
    protected function internalTranslate($parsed)
    {
        FunctionHelper::replaceFunctionEntrance($parsed, self::FUNCTION_NAME, 'OCTET_LENGTH');
        return $parsed;
    }
}
