<?php

namespace huaweichenai\dmbase\table\functions;

use huaweichenai\dmbase\base\BaseFunctionTranslator;
use huaweichenai\dmbase\utils\FunctionHelper;

class Md5FunctionTranslator extends BaseFunctionTranslator
{
    const FUNCTION_NAME = 'MD5';

    /**
     * @throws \huaweichenai\dmbase\exceptions\DbxException
     */
    protected function internalTranslate($parsed)
    {
        FunctionHelper::replaceFunctionEntrance($parsed, self::FUNCTION_NAME, 'TO_CHAR');
        return $parsed;
    }
}
