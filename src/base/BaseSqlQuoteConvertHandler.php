<?php

namespace huaweichenai\dmbase\base;

use huaweichenai\dmbase\SqlContext;

abstract class BaseSqlQuoteConvertHandler extends BaseHandler
{
    public function handle(SqlContext $sqlContext)
    {
        $sqlContext = $this->convertSqlQuote($sqlContext);
        if ($this->nextHandler != null) {
            $this->nextHandler->handle($sqlContext);
        }

        return $sqlContext;
    }

    abstract protected function convertSqlQuote(SqlContext $sqlContext);
}
