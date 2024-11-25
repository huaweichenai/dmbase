<?php

namespace huaweichenai\dmbase\interfaces;

use huaweichenai\dmbase\base\BaseAfterHandler;
use huaweichenai\dmbase\base\BaseFunctionHandler;
use huaweichenai\dmbase\base\BaseGeneralSqlValidateHandler;
use huaweichenai\dmbase\base\BaseSqlQuoteConvertHandler;

interface SqlHandlerFactoryInterface
{
    /**
     * @return BaseGeneralSqlValidateHandler
     */
    public function createGeneralSqlValidateHandler();

    /**
     * @return BaseFunctionHandler
     */
    public function createFunctionHandler();

    /**
     * @return BaseSqlQuoteConvertHandler
     */
    public function createSqlQuoteHandler();

    /**
     * @return BaseAfterHandler
     */
    public function createAfterHandler();

}
