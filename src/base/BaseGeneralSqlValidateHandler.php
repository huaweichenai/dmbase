<?php

namespace huaweichenai\dmbase\base;

use huaweichenai\dmbase\SqlContext;

abstract class BaseGeneralSqlValidateHandler extends BaseHandler
{
    /**
     * @param SqlContext $sqlContext
     * @return SqlContext
     */
    public function handle(SqlContext $sqlContext)
    {
        $this->validateSupportedFunctions($sqlContext);
        $this->validateUnSupportedDbs($sqlContext);
        if ($this->nextHandler != null) {
            $this->nextHandler->handle($sqlContext);
        }

        return $sqlContext;
    }

    abstract protected function validateSupportedFunctions(SqlContext $sqlContext);

    abstract protected function validateSupportedExpressions(SqlContext $sqlContext);

    abstract protected function validateUnSupportedDbs(SqlContext $sqlContext);
}
