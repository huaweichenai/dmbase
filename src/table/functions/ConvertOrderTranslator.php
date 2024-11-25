<?php

namespace huaweichenai\dmbase\table\functions;

use huaweichenai\dmbase\base\BaseFunctionTranslator;
use huaweichenai\dmbase\SqlContext;
use huaweichenai\dmbase\utils\FunctionHelper;

class ConvertOrderTranslator extends BaseFunctionTranslator
{
    /**
     * 将 CONVERT(xxx USING gbk ) 改成 NLSSORT(xxx,'NLS_SORT = SCHINESE_PINYIN_M')
     * @throws \huaweichenai\dmbase\exceptions\DbxException
     */
    protected function internalTranslate($parsed)
    {
        FunctionHelper::changeConvertOrderEntrance($parsed);
        return $parsed;
    }
}
