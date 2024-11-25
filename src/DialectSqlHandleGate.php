<?php

namespace huaweichenai\dmbase;

use huaweichenai\dmbase\table\SqlHandlerFactory;
use huaweichenai\dmbase\exceptions\DbxException;
use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\PHPSQLParser;

class DialectSqlHandleGate
{
    private $dialectType;

    public function __construct($dialectType)
    {
        $this->dialectType = $dialectType ?: DbDialectType::DB_TYPE_KDB;
    }

    /**
     * @param $sql
     * @return \PHPSQLParser\builders\A|string
     * @throws DbxException
     * @throws \PHPSQLParser\exceptions\UnsupportedFeatureException
     */
    public function handle($sql)
    {
        if (empty($sql)) {
            return '';
        }
        $factory = new SqlHandlerFactory();

        if (empty($factory)) {
            throw new DbxException("无效的方言类型：{$this->dialectType}");
        }

        $generalSqlValidateHandler = $factory->createGeneralSqlValidateHandler();
        $functionHandler = $factory->createFunctionHandler();
        $sqlQuoteHandler = $factory->createSqlQuoteHandler();
        $afterHandler = $factory->createAfterHandler();
        $generalSqlValidateHandler->setNextHandler($functionHandler);
        $functionHandler->setNextHandler($sqlQuoteHandler);
        $sqlQuoteHandler->setNextHandler($afterHandler); // 注意afterHandler处理器一定要放在最后
        $sqlContext = new SqlContext($sql, new PHPSQLParser());
        $handledSqlContext = $generalSqlValidateHandler->handle($sqlContext);
        $creator = new PHPSQLCreator();
        $handledSql = $creator->create($handledSqlContext->getParsed());
        if (!$handledSql) {
            throw new DbxException("解析翻译失败！");
        }
        return $handledSql;
    }
}
