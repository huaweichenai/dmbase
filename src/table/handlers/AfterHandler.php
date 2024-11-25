<?php

namespace huaweichenai\dmbase\table\handlers;

use huaweichenai\dmbase\SqlContext;

class AfterHandler extends \huaweichenai\dmbase\base\BaseAfterHandler
{

    protected function afterHandle(SqlContext $sqlContext)
    {
        $parsed = $sqlContext->getParsed();
        if (isset($parsed['UNION'])) {
            if (count($parsed['UNION']) == 1) {
                $sqlContext->setParsed($parsed['UNION'][0]);
            }
        }

        return $sqlContext;
    }
}
