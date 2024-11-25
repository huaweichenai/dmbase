<?php

namespace huaweichenai\dmbase\base;

use huaweichenai\dmbase\interfaces\FunctionTranslatorInterface;
use huaweichenai\dmbase\interfaces\FunctionValidatorInterface;
use huaweichenai\dmbase\SqlContext;

abstract class BaseFunctionHandler extends BaseHandler
{
    protected $validatorClasses = [];

    protected $validatorObjects = [];

    protected $translatorClasses = [];

    protected $translatorObjects = [];

    public function __construct(){
        foreach ($this->translatorClasses as $translatorClass) {
            $this->translatorObjects[] =\Yii::createObject($translatorClass);
        }
    }

    public function handle(SqlContext $sqlContext)
    {
        // TODO: Implement handle() method.
    }

    abstract public function appendValidator(FunctionValidatorInterface $validator);

    abstract public function appendTranslator(FunctionTranslatorInterface $translator);

}
