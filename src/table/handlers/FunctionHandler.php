<?php

namespace huaweichenai\dmbase\table\handlers;

use huaweichenai\dmbase\interfaces\FunctionTranslatorInterface;
use huaweichenai\dmbase\interfaces\FunctionValidatorInterface;
use huaweichenai\dmbase\SqlContext;

class FunctionHandler extends \huaweichenai\dmbase\base\BaseFunctionHandler
{
    protected $validatorClasses = [];

    protected $validatorObjects = [];

    protected $translatorClasses = [
        '\huaweichenai\dmbase\table\functions\LengthFunctionTranslator',
        '\huaweichenai\dmbase\table\functions\AddDateFunctionTranslator',
        '\huaweichenai\dmbase\table\functions\DateSubFunctionTranslator',
        '\huaweichenai\dmbase\table\functions\DateAddFunctionTranslator',
        '\huaweichenai\dmbase\table\functions\DateFunctionTranslator',
        '\huaweichenai\dmbase\table\functions\Md5FunctionTranslator',
        '\huaweichenai\dmbase\table\functions\ConvertOrderTranslator',
        '\huaweichenai\dmbase\table\functions\ConvertFunctionTranslator',
        '\huaweichenai\dmbase\table\functions\DateFormatFunctionTranslator',
        '\huaweichenai\dmbase\table\functions\UUIDFunctionTranslator',
    ];

    /**
     * @var FunctionTranslatorInterface[]
     */
    protected $translatorObjects = [];

    public function appendValidator(FunctionValidatorInterface $validator)
    {
        // TODO: Implement appendValidator() method.
    }

    public function appendTranslator(FunctionTranslatorInterface $translator)
    {
        // TODO: Implement appendTranslator() method.
    }

    public function handle(SqlContext $sqlContext)
    {
        foreach ($this->translatorObjects as $translatorObject) {
            $sqlContext->setParsed($translatorObject->translate($sqlContext)->getParsed());
        }

        if ($this->nextHandler != null){
            $this->nextHandler->handle($sqlContext);
        }

        return $sqlContext;
    }
}
