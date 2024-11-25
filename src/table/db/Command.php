<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace huaweichenai\dmbase\table\db;

use huaweichenai\dmbase\DbDialectType;
use huaweichenai\dmbase\DialectSqlHandleGate;
use huaweichenai\dmbase\exceptions\DbxException;
use PHPSQLParser\exceptions\UnsupportedFeatureException;

/**
 * Command represents an Oracle SQL statement to be executed against a database.
 *
 * {@inheritdoc}
 *
 * @since 2.0.33
 */
class Command extends \yii\db\Command
{
    /**
     * @var DialectSqlHandleGate
     */
    protected $handler;

    protected $isTranslate = true;

    public function init()
    {
        parent::init();
        $this->handler = new DialectSqlHandleGate(DbDialectType::DB_TYPE_KDB);
    }

    /**
     * @throws DbxException
     * @throws UnsupportedFeatureException
     */
    public function getSql()
    {
        return $this->translate(parent::getSql());
    }

    /**
     * @throws DbxException
     * @throws UnsupportedFeatureException
     */
    public function getRawSql()
    {
        return $this->translate(parent::getRawSql());
    }

    /**
     * 转换操作
     * @param $sql
     * @return string
     * @throws DbxException
     * @throws UnsupportedFeatureException
     */
    private function translate($sql)
    {
        if (!$this->isTranslate) {
            return $sql;
        }

        \Yii::info('开始进行解析翻译，原始语句为：' . $sql, 'iDbx');
        $beginTs = microtime(true);
        try {
            $handledSql = $this->handler->handle($sql);
        } catch (\Exception $e) {
            \Yii::error("解析翻译失败,原因为：" . $e->getMessage() . "详细信息为：" . $e->getTraceAsString(), 'iDbx');
            throw $e;
        }
        \Yii::info('解析翻译成功，耗时：' . sprintf(',耗时%.3f秒', microtime(true) - $beginTs) . '，翻译后语句为：' . $handledSql, 'iDbx');
        return $handledSql;
    }

    /**
     * 取消翻译
     * @return $this
     */
    public function disableTranslate()
    {
        $this->isTranslate = false;
        return $this;
    }
}
