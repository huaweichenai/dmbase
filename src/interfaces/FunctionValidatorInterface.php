<?php

namespace huaweichenai\dmbase\interfaces;

use huaweichenai\dmbase\SqlContext;

interface FunctionValidatorInterface
{
    public function validate(SqlContext  $sqlContext);

}
