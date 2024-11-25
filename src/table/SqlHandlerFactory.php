<?php

namespace huaweichenai\dmbase\table;

use huaweichenai\dmbase\table\handlers\AfterHandler;
use huaweichenai\dmbase\table\handlers\GeneralSqlValidateHandler;
use huaweichenai\dmbase\table\handlers\FunctionHandler;
use huaweichenai\dmbase\table\handlers\SqlQuoteConvertHandler;

class SqlHandlerFactory implements \huaweichenai\dmbase\interfaces\SqlHandlerFactoryInterface
{

    /**
     * @inheritDoc
     */
    public function createGeneralSqlValidateHandler()
    {
        return new GeneralSqlValidateHandler();

    }

    /**
     * @inheritDoc
     */
    public function createFunctionHandler()
    {
        return new FunctionHandler();
    }

    /**
     * @inheritDoc
     */
    public function createSqlQuoteHandler()
    {
        return new SqlQuoteConvertHandler();
    }


    /**
     * @inheritDoc
     */
    public function createAfterHandler()
    {
        return new AfterHandler();
    }
}
