<?php

namespace huaweichenai\dmbase\table\handlers;

use huaweichenai\dmbase\SqlContext;
use huaweichenai\dmbase\utils\QuoteHelper;

class SqlQuoteConvertHandler extends \huaweichenai\dmbase\base\BaseSqlQuoteConvertHandler
{
    protected function convertSqlQuote(SqlContext $sqlContext)
    {
        $parsedArr = $sqlContext->getParsed();
        QuoteHelper::convertItemsQuote($parsedArr);
        $sqlContext->setParsed($parsedArr);
        return $sqlContext;
    }
}
