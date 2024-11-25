<?php

namespace huaweichenai\dmbase\table\handlers;

use huaweichenai\dmbase\exceptions\DbxException;
use huaweichenai\dmbase\SqlContext;
use huaweichenai\dmbase\utils\ValidateHelper;

class GeneralSqlValidateHandler extends \huaweichenai\dmbase\base\BaseGeneralSqlValidateHandler
{
    protected static $unSupportedDbs = ['information_schema', 'mysql', 'performance_schema'];
    protected static $unSupportedFunctions = ['group_concat', 'database'];

    /**
     * @throws DbxException
     */
    protected function validateSupportedFunctions(SqlContext $sqlContext)
    {
        ValidateHelper::validateFunctionsEntrance($sqlContext->getParsed(), self::$unSupportedFunctions);
    }

    protected function validateSupportedExpressions(SqlContext $sqlContext)
    {
        // TODO: Implement validateSupportedExpressions(SqlContext $sqlContext) method.
    }

    /**
     * @throws DbxException
     */
    protected function validateUnSupportedDbs(SqlContext $sqlContext)
    {
        ValidateHelper::validateDbsEntrance($sqlContext->getParsed(), self::$unSupportedDbs);
    }
}
