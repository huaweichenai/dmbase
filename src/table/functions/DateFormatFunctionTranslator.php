<?php

namespace huaweichenai\dmbase\table\functions;

use huaweichenai\dmbase\base\BaseFunctionTranslator;
use huaweichenai\dmbase\SqlContext;
use huaweichenai\dmbase\utils\FunctionHelper;

class DateFormatFunctionTranslator extends BaseFunctionTranslator
{
    const FUNCTION_NAME = 'DATE_FORMAT';

    /**
     * @throws \huaweichenai\dmbase\exceptions\DbxException
     */
    protected function internalTranslate($parsed)
    {
        FunctionHelper::replaceFunctionEntrance($parsed, self::FUNCTION_NAME, 'TO_CHAR');
        return $parsed;
    }
}
