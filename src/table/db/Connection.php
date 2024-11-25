<?php
/**
 * Created by PhpStorm.
 * User: YashonLvan
 * Date: 2023/3/14
 * Time: 10:36 AM
 */

namespace huaweichenai\dmbase\table\db;

use huaweichenai\dmbase\utils\StringHelper;
use PDO;

class Connection extends \yii\db\Connection
{

    public $defaultSchema;

    /**
     * 初始化注入自定义驱动
     * @param $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        // 导入数据处理类
        $this->schemaMap = array_merge($this->schemaMap, ['dm' => 'huaweichenai\dmbase\table\db\Schema']);
        $this->commandMap = array_merge($this->commandMap, ['dm' => 'huaweichenai\dmbase\table\db\Command']);

        // 保持查询结果均为字符串类型，这里设为返回字符串类型
        $this->attributes[\PDO::ATTR_STRINGIFY_FETCHES] = true;
    }

    /**
     * 重写连接代码
     * @return void
     */
    protected function initConnection()
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if ($this->emulatePrepare !== null && constant('PDO::ATTR_EMULATE_PREPARES')) {
            if ($this->driverName !== 'sqlsrv') {
                $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->emulatePrepare);
            }
        }
        if ($this->charset !== null && in_array($this->getDriverName(), ['pgsql', 'mysql', 'mysqli', 'cubrid'], true)) {
            $this->pdo->exec('SET NAMES ' . $this->pdo->quote($this->charset));
        }


        if ($this->getDriverName() == 'dm') {
            $schema = StringHelper::getValueByParesDSN($this->dsn);
            if ($schema) {
                $this->pdo->exec('SET SCHEMA ' . $schema);
            }
        }
        $this->trigger(self::EVENT_AFTER_OPEN);
    }
}
