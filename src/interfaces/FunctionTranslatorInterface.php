<?php

namespace huaweichenai\dmbase\interfaces;

use huaweichenai\dmbase\SqlContext;

interface FunctionTranslatorInterface
{
    public function translate(SqlContext $sqlContext);
}
