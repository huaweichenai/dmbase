<?php

namespace huaweichenai\dmbase\table\db;

use huaweichenai\dmbase\utils\StringHelper;
use yii\base\InvalidCallException;
use yii\base\NotSupportedException;
use yii\db\CheckConstraint;
use yii\db\ColumnSchema;
use yii\db\Constraint;
use yii\db\ConstraintFinderInterface;
use yii\db\ConstraintFinderTrait;
use yii\db\Expression;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yii\db\TableSchema;
use yii\helpers\ArrayHelper;

/**
 * Schema is the class for retrieving metadata from an Oracle database.
 *
 * @property-read string $lastInsertID The row ID of the last row inserted, or the last value retrieved from
 * the sequence object.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Schema extends \yii\db\Schema implements ConstraintFinderInterface
{
    use ConstraintFinderTrait;

    /**
     * @var array map of DB errors and corresponding exceptions
     * If left part is found in DB error message exception class from the right part is used.
     */
    public $exceptionMap = [
        'ORA-00001: unique constraint' => 'yii\db\IntegrityException',
    ];

    /**
     * {@inheritdoc}
     */
    protected $tableQuoteCharacter = '"';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $schema = StringHelper::getValueByParesDSN($this->db->dsn);
        if ($schema){
            $this->defaultSchema = $schema;
        }
        if ($this->defaultSchema === null) {
            $username = $this->db->username;
            if (empty($username)) {
                $username = isset($this->db->masters[0]['username']) ? $this->db->masters[0]['username'] : '';
            }
            $this->defaultSchema = strtoupper($username);
        }
        // 与 mysql use database 类似;
        if ($this->defaultSchema !== null) {
            $this->db->createCommand('SET SCHEMA "' . $this->defaultSchema . '"')->disableTranslate()->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveTableName($name)
    {
        $resolvedName = new TableSchema();
        $parts = explode('.', str_replace('"', '', $name));
        if (isset($parts[1])) {
            $resolvedName->schemaName = $parts[0];
            $resolvedName->name = $parts[1];
        } else {
            $resolvedName->schemaName = $this->defaultSchema;
            $resolvedName->name = $name;
        }
        $resolvedName->fullName = ($resolvedName->schemaName !== $this->defaultSchema ? $resolvedName->schemaName . '.' : '') . $resolvedName->name;
        return $resolvedName;
    }

    /**
     * Resolves the table name and schema name (if any).
     *
     * @param TableSchema $table the table metadata object
     * @param string $name the table name
     */
    protected function resolveTableNames($table, $name)
    {
        $parts = explode('.', str_replace('"', '', $name));
        if (isset($parts[1])) {
            $table->schemaName = $parts[0];
            $table->name = $parts[1];
        } else {
            $table->schemaName = $this->defaultSchema;
            $table->name = $name;
        }

        $table->fullName = $table->schemaName !== $this->defaultSchema ? $table->schemaName . '.' . $table->name : $table->name;
    }

    public function testFindSchemaNames(){
        return $this->findSchemaNames();
    }

    public function testFindTableNames($schema = ''){
        return $this->findTableNames($schema);
    }

    public function testLoadTableSchema($name){
        return $this->loadTableSchema($name);
    }

    public function testLoadTablePrimaryKey($tableName){
        return $this->loadTablePrimaryKey($tableName);
    }

    public function testLoadTableForeignKeys($tableName){
        return $this->loadTableForeignKeys($tableName);
    }

    public function testLoadTableIndexes($tableName){
        return $this->loadTableIndexes($tableName);
    }

    public function testLoadTableUniques($tableName = ''){
        return $this->loadTableUniques($tableName);
    }

    public function testLoadTableChecks($tableName = ''){
        return $this->loadTableChecks($tableName);
    }

    public function testGetTableSequenceName($tableName){
        return $this->getTableSequenceName($tableName);
    }

    public function TestCreateColumn($column){
        return $this->createColumn($column);
    }

    public function testGetLastInsertID($sequenceName = ''){
        return $this->getLastInsertID($sequenceName);
    }

    public function testInsert($table, $columns){
        return $this->insert($table, $columns);
    }


    /**
     * {@inheritdoc}
     * @see https://docs.oracle.com/cd/B28359_01/server.111/b28337/tdpsg_user_accounts.htm
     */
    protected function findSchemaNames()
    {
        static $sql = <<<'SQL'
SELECT "u"."USERNAME"
FROM "DBA_USERS" "u"
WHERE "u"."DEFAULT_TABLESPACE" NOT IN ('SYSTEM', 'SYSAUX')
ORDER BY "u"."USERNAME" ASC
SQL;
        return $this->db->createCommand($sql)->disableTranslate()->queryColumn();
    }

    /**
     * {@inheritdoc}
     */
    protected function findTableNames($schema = '')
    {
        if ($schema === '') {
            $sql = <<<'SQL'
SELECT
    TABLE_NAME
FROM USER_TABLES
UNION ALL
SELECT
    VIEW_NAME AS TABLE_NAME
FROM USER_VIEWS
UNION ALL
SELECT
    MVIEW_NAME AS TABLE_NAME
FROM USER_MVIEWS
ORDER BY TABLE_NAME
SQL;
            $command = $this->db->createCommand($sql);
        } else {
            $sql = <<<'SQL'
SELECT
    OBJECT_NAME AS TABLE_NAME
FROM ALL_OBJECTS
WHERE
    OBJECT_TYPE IN ('TABLE', 'VIEW', 'MATERIALIZED VIEW')
    AND OWNER = :schema
ORDER BY OBJECT_NAME
SQL;
            $command = $this->db->createCommand($sql, [':schema' => $schema]);
        }

        $rows = $command->disableTranslate()->queryAll();
        $names = [];
        foreach ($rows as $row) {
            if ($this->db->slavePdo->getAttribute(\PDO::ATTR_CASE) === \PDO::CASE_LOWER) {
                $row = array_change_key_case($row, CASE_UPPER);
            }
            $names[] = $row['TABLE_NAME'];
        }

        return array_values(array_unique($names));
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableSchema($name)
    {
        $table = new TableSchema();
        $this->resolveTableNames($table, $name);
        if ($this->findColumns($table)) {
            $this->findConstraints($table); // 查找约束
            return $table;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTablePrimaryKey($tableName)
    {
        return $this->loadTableConstraints($tableName, 'primaryKey');
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableForeignKeys($tableName)
    {
        return $this->loadTableConstraints($tableName, 'foreignKeys');
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableIndexes($tableName)
    {
        static $sql = <<<'SQL'
SELECT
    /*+ PUSH_PRED("ui") PUSH_PRED("uicol") PUSH_PRED("uc") */
    "ui"."INDEX_NAME" AS "name",
    "uicol"."COLUMN_NAME" AS "column_name",
    CASE "ui"."UNIQUENESS" WHEN 'UNIQUE' THEN 1 ELSE 0 END AS "index_is_unique",
    CASE WHEN "uc"."CONSTRAINT_NAME" IS NOT NULL THEN 1 ELSE 0 END AS "index_is_primary"
FROM "SYS"."USER_INDEXES" "ui"
LEFT JOIN "SYS"."USER_IND_COLUMNS" "uicol"
    ON "uicol"."INDEX_NAME" = "ui"."INDEX_NAME"
LEFT JOIN "SYS"."USER_CONSTRAINTS" "uc"
    ON "uc"."OWNER" = "ui"."TABLE_OWNER" AND "uc"."CONSTRAINT_NAME" = "ui"."INDEX_NAME" AND "uc"."CONSTRAINT_TYPE" = 'P'
WHERE "ui"."TABLE_OWNER" = :schemaName AND "ui"."TABLE_NAME" = :tableName
ORDER BY "uicol"."COLUMN_POSITION" ASC
SQL;

        $resolvedName = $this->resolveTableName($tableName);
        $indexes = $this->db->createCommand($sql, [
            ':schemaName' => $resolvedName->schemaName,
            ':tableName' => $resolvedName->name,
        ])->disableTranslate()->queryAll();
        $indexes = $this->normalizePdoRowKeyCase($indexes, true);
        $indexes = ArrayHelper::index($indexes, null, 'name');
        $result = [];
        foreach ($indexes as $name => $index) {
            $result[] = new IndexConstraint([
                'isPrimary' => (bool)$index[0]['index_is_primary'],
                'isUnique' => (bool)$index[0]['index_is_unique'],
                'name' => $name,
                'columnNames' => ArrayHelper::getColumn($index, 'column_name'),
            ]);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableUniques($tableName)
    {
        return $this->loadTableConstraints($tableName, 'uniques');
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableChecks($tableName)
    {
        return $this->loadTableConstraints($tableName, 'checks');
    }

    /**
     * {@inheritdoc}
     * @throws NotSupportedException if this method is called.
     */
    protected function loadTableDefaultValues($tableName)
    {
        throw new NotSupportedException('Oracle does not support default value constraints.');
    }

    /**
     * {@inheritdoc}
     */
    public function releaseSavepoint($name)
    {
        // does nothing as Oracle does not support this
    }

    /**
     * {@inheritdoc}
     */
    public function quoteSimpleTableName($name)
    {
        return strpos($name, '"') !== false ? $name : '"' . $name . '"';
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this->db);
    }

    /**
     * {@inheritdoc}
     */
    public function createColumnSchemaBuilder($type, $length = null)
    {
        return new ColumnSchemaBuilder($type, $length, $this->db);
    }



    /**
     * Collects the table column metadata.
     * @param TableSchema $table the table schema
     * @return bool whether the table exists
     */
    protected function findColumns($table)
    {
        $sql = <<<'SQL'
SELECT
    A.COLUMN_NAME,
    A.DATA_TYPE,
    A.DATA_PRECISION,
    A.DATA_SCALE,
    (
      CASE A.CHAR_USED WHEN 'C' THEN A.CHAR_LENGTH
        ELSE A.DATA_LENGTH
      END
    ) AS DATA_LENGTH,
    A.NULLABLE,
    A.DATA_DEFAULT,
    COM.COMMENTS AS COLUMN_COMMENT
FROM ALL_TAB_COLUMNS A
    INNER JOIN ALL_OBJECTS B ON B.OWNER = A.OWNER AND LTRIM(B.OBJECT_NAME) = LTRIM(A.TABLE_NAME)
    LEFT JOIN ALL_COL_COMMENTS COM ON (A.OWNER = COM.OWNER AND A.TABLE_NAME = COM.TABLE_NAME AND A.COLUMN_NAME = COM.COLUMN_NAME)
WHERE
    A.OWNER = :schemaName
    AND B.OBJECT_TYPE IN ('TABLE', 'VIEW', 'MATERIALIZED VIEW')
    AND B.OBJECT_NAME = :tableName
ORDER BY A.COLUMN_ID
SQL;

        try {
            $columns = $this->db->createCommand($sql, [
                ':tableName' => $table->name,
                ':schemaName' => $table->schemaName,
            ])->disableTranslate()->queryAll();
        } catch (\Exception $e) {
            return false;
        }

        if (empty($columns)) {
            return false;
        }

        $autoIncrementColumnsSet = $this->findAutoIncrementColumns($table);
        foreach ($columns as $column) {
            if ($this->db->slavePdo->getAttribute(\PDO::ATTR_CASE) === \PDO::CASE_LOWER) {
                $column = array_change_key_case($column, CASE_UPPER);
            }
            $c = $this->createColumn($column);
            if (isset($autoIncrementColumnsSet[$c->name])) {
                $c->autoIncrement = true;
            }
            $table->columns[$c->name] = $c;
        }

        return true;
    }

    /**
     * 查找自增列
     * https://eco.dameng.com/document/dm/zh-cn/faq/faq-sql-gramm#%E8%BE%BE%E6%A2%A6%E5%A6%82%E4%BD%95%E5%88%A4%E6%96%AD%E8%A1%A8%E4%B8%AD%E7%9A%84%E5%AD%97%E6%AE%B5%E6%98%AF%E5%90%A6%E4%B8%BA%E8%87%AA%E5%A2%9E%E5%88%97%EF%BC%9F%E5%A6%82%E4%BD%95%E7%94%A8%E8%84%9A%E6%9C%AC%E6%9F%A5%E8%AF%A2%EF%BC%9F
     * @param TableSchema $table the table schema
     * @return map[string]string columnSet
     */
    protected function findAutoIncrementColumns($table)
    {
        $sql = <<<'SQL'
SELECT
	B.OWNER,
	B.TABLE_NAME,
	C.NAME COLUMN_NAME
FROM
	SYS.SYSOBJECTS A
	INNER JOIN SYS.ALL_TABLES B ON B.TABLE_NAME = A.NAME
	INNER JOIN SYS.SYSCOLUMNS C ON A.ID = C.ID
WHERE
	B.OWNER = :schemaName
	AND B.TABLE_NAME = :tableName
	AND C.INFO2 & 0x01 = 0x01
SQL;
        try {
            $columns = $this->db->createCommand($sql, [
                ':tableName' => $table->name,
                ':schemaName' => $table->schemaName,
            ])->disableTranslate()->queryAll();
        } catch (\Exception $e) {
            return [];
        }
        if (empty($columns)) {
            return [];
        }
        $columnSet = [];
        foreach ($columns as $column) {
            $columnSet[$column['COLUMN_NAME']] = $column['COLUMN_NAME'];
        }
        return $columnSet;
    }

    /**
     * Sequence name of table.
     *
     * @param string $tableName
     * @return string|null whether the sequence exists
     * @internal param \yii\db\TableSchema $table->name the table schema
     */
    protected function getTableSequenceName($tableName)
    {
        $sequenceNameSql = <<<'SQL'
SELECT
    UD.REFERENCED_NAME AS SEQUENCE_NAME
FROM USER_DEPENDENCIES UD
    JOIN USER_TRIGGERS UT ON (UT.TRIGGER_NAME = UD.NAME)
WHERE
    UT.TABLE_NAME = :tableName
    AND UD.TYPE = 'TRIGGER'
    AND UD.REFERENCED_TYPE = 'SEQUENCE'
SQL;
        $sequenceName = $this->db->createCommand($sequenceNameSql, [':tableName' => $tableName])->disableTranslate()->queryScalar();
        return $sequenceName === false ? null : $sequenceName;
    }

    /**
     * @Overrides method in class 'Schema'
     * @see https://eco.dameng.com/document/dm/zh-cn/pm/sql-appendix
     * 返回插入到同一作用域中的 identity 列内的最后一个 identity 值。
     * SELECT SCOPE_IDENTITY();
     * SELECT @@IDENTITY;
     * @param string $sequenceName name of the sequence object (required by some DBMS)
     * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object
     * @throws InvalidCallException if the DB connection is not active
     */
    public function getLastInsertID($sequenceName = '')
    {
        if ($this->db->isActive) {
            // get the last insert id from the master connection
            return $this->db->useMaster(function (Connection $db) {
                return $db->createCommand("SELECT SCOPE_IDENTITY()")->disableTranslate()->queryScalar();
            });
        } else {
            throw new InvalidCallException('DB Connection is not active.');
        }
    }

    /**
     * Creates ColumnSchema instance.
     *
     * @param array $column
     * @return ColumnSchema
     */
    protected function createColumn($column)
    {
        $c = $this->createColumnSchema();
        $c->name = $column['COLUMN_NAME'];
        $c->allowNull = $column['NULLABLE'] === 'Y';
        $c->comment = $column['COLUMN_COMMENT'] === null ? '' : $column['COLUMN_COMMENT'];
        $c->isPrimaryKey = false;
        $this->extractColumnType($c, $column['DATA_TYPE'], $column['DATA_PRECISION'], $column['DATA_SCALE'], $column['DATA_LENGTH']);
        $this->extractColumnSize($c, $column['DATA_TYPE'], $column['DATA_PRECISION'], $column['DATA_SCALE'], $column['DATA_LENGTH']);

        $c->phpType = $this->getColumnPhpType($c);

        if (!$c->isPrimaryKey) {
            if (stripos((string)$column['DATA_DEFAULT'], 'timestamp') !== false) {
                $c->defaultValue = null;
            } else {
                $defaultValue = (string)$column['DATA_DEFAULT'];
                if ($c->type === 'timestamp' && $defaultValue === 'CURRENT_TIMESTAMP') {
                    $c->defaultValue = new Expression('CURRENT_TIMESTAMP');
                } else {
                    if ($defaultValue !== null) {
                        if (
                            strlen($defaultValue) > 2
                            && strncmp($defaultValue, "'", 1) === 0
                            && substr($defaultValue, -1) === "'"
                        ) {
                            $defaultValue = substr($defaultValue, 1, -1);
                        } else {
                            $defaultValue = trim($defaultValue);
                        }
                    }
                    $c->defaultValue = $c->phpTypecast($defaultValue);
                }
            }
        }

        return $c;
    }

    /**
     * Finds constraints and fills them into TableSchema object passed.
     * @param TableSchema $table
     */
    protected function findConstraints($table)
    {
        $sql = <<<'SQL'
SELECT
    D.CONSTRAINT_NAME,
    D.CONSTRAINT_TYPE,
    C.COLUMN_NAME,
    C.POSITION,
    D.R_CONSTRAINT_NAME,
    E.TABLE_NAME AS TABLE_REF,
    F.COLUMN_NAME AS COLUMN_REF,
    C.TABLE_NAME
FROM ALL_CONS_COLUMNS C
    INNER JOIN ALL_CONSTRAINTS D ON D.OWNER = C.OWNER AND D.CONSTRAINT_NAME = C.CONSTRAINT_NAME
    LEFT JOIN ALL_CONSTRAINTS E ON E.OWNER = D.R_OWNER AND E.CONSTRAINT_NAME = D.R_CONSTRAINT_NAME
    LEFT JOIN ALL_CONS_COLUMNS F ON F.OWNER = E.OWNER AND F.CONSTRAINT_NAME = E.CONSTRAINT_NAME AND F.POSITION = C.POSITION
WHERE
    C.OWNER = :schemaName
    AND C.TABLE_NAME = :tableName
ORDER BY D.CONSTRAINT_NAME, C.POSITION
SQL;
        $command = $this->db->createCommand($sql, [
            ':tableName' => $table->name,
            ':schemaName' => $table->schemaName,
        ])->disableTranslate();
        $constraints = [];
        foreach ($command->queryAll() as $row) {
            if ($this->db->slavePdo->getAttribute(\PDO::ATTR_CASE) === \PDO::CASE_LOWER) {
                $row = array_change_key_case($row, CASE_UPPER);
            }

            if ($row['CONSTRAINT_TYPE'] === 'P') {
                $table->columns[$row['COLUMN_NAME']]->isPrimaryKey = true;
                $table->primaryKey[] = $row['COLUMN_NAME'];
                if (empty($table->sequenceName)) {
                    $table->sequenceName = $this->getTableSequenceName($table->name);
                }
            }

            if ($row['CONSTRAINT_TYPE'] !== 'R') {
                // this condition is not checked in SQL WHERE because of an Oracle Bug:
                // see https://github.com/yiisoft/yii2/pull/8844
                continue;
            }

            $name = $row['CONSTRAINT_NAME'];
            if (!isset($constraints[$name])) {
                $constraints[$name] = [
                    'tableName' => $row['TABLE_REF'],
                    'columns' => [],
                ];
            }
            $constraints[$name]['columns'][$row['COLUMN_NAME']] = $row['COLUMN_REF'];
        }

        foreach ($constraints as $constraint) {
            $name = current(array_keys($constraint));

            $table->foreignKeys[$name] = array_merge([$constraint['tableName']], $constraint['columns']);
        }
    }

    /**
     * Returns all unique indexes for the given table.
     * Each array element is of the following structure:.
     *
     * ```php
     * [
     *     'IndexName1' => ['col1' [, ...]],
     *     'IndexName2' => ['col2' [, ...]],
     * ]
     * ```
     *
     * @param TableSchema $table the table metadata
     * @return array all unique indexes for the given table.
     * @since 2.0.4
     */
    public function findUniqueIndexes($table)
    {
        $query = <<<'SQL'
SELECT
    DIC.INDEX_NAME,
    DIC.COLUMN_NAME
FROM ALL_INDEXES DI
    INNER JOIN ALL_IND_COLUMNS DIC ON DI.TABLE_NAME = DIC.TABLE_NAME AND DI.INDEX_NAME = DIC.INDEX_NAME
WHERE
    DI.UNIQUENESS = 'UNIQUE'
    AND DIC.TABLE_OWNER = :schemaName
    AND DIC.TABLE_NAME = :tableName
ORDER BY DIC.TABLE_NAME, DIC.INDEX_NAME, DIC.COLUMN_POSITION
SQL;
        $result = [];
        $command = $this->db->createCommand($query, [
            ':tableName' => $table->name,
            ':schemaName' => $table->schemaName,
        ])->disableTranslate();
        foreach ($command->queryAll() as $row) {
            $result[$row['INDEX_NAME']][] = $row['COLUMN_NAME'];
        }

        return $result;
    }

    /**
     * Extracts the data types for the given column.
     * @param ColumnSchema $column
     * @param string $dbType DB type
     * @param string $precision total number of digits.
     * This parameter is available since version 2.0.4.
     * @param string $scale number of digits on the right of the decimal separator.
     * This parameter is available since version 2.0.4.
     * @param string $length length for character types.
     * This parameter is available since version 2.0.4.
     */
    protected function extractColumnType($column, $dbType, $precision, $scale, $length)
    {
        $column->dbType = $dbType;

        if (strpos($dbType, 'FLOAT') !== false || strpos($dbType, 'DOUBLE') !== false) {
            $column->type = 'double';
        } elseif (strpos($dbType, 'NUMBER') !== false) {
            if ($scale === null || $scale > 0) {
                $column->type = 'decimal';
            } else {
                $column->type = 'integer';
            }
        } elseif (strpos($dbType, 'INT') !== false || strpos($dbType, 'INTEGER') !== false) { // TINYINT INT BIGINT
            $column->type = self::TYPE_INTEGER;
        } elseif (strpos($dbType, 'TINYINT') !== false) {
            $column->type = self::TYPE_TINYINT;
        } elseif (strpos($dbType, 'BIGINT') !== false) {
            $column->type = self::TYPE_BIGINT;
        } elseif (strpos($dbType, 'BLOB') !== false) {
            $column->type = 'binary';
        } elseif (strpos($dbType, 'CLOB') !== false) {
            $column->type = 'text';
        } elseif (strpos($dbType, 'TIMESTAMP') !== false) {
            $column->type = 'timestamp';
        } else {
            $column->type = 'string';
        }
    }

    /**
     * Extracts size, precision and scale information from column's DB type.
     * @param ColumnSchema $column
     * @param string $dbType the column's DB type
     * @param string $precision total number of digits.
     * This parameter is available since version 2.0.4.
     * @param string $scale number of digits on the right of the decimal separator.
     * This parameter is available since version 2.0.4.
     * @param string $length length for character types.
     * This parameter is available since version 2.0.4.
     */
    protected function extractColumnSize($column, $dbType, $precision, $scale, $length)
    {
        $column->size = trim((string)$length) === '' ? null : (int)$length;
        $column->precision = trim((string)$precision) === '' ? null : (int)$precision;
        $column->scale = trim((string)$scale) === '' ? null : (int)$scale;
    }

    public function insert($table, $columns)
    {
        $command = $this->db->createCommand()->insert($table, $columns);
        if (!$command->execute()) {
            return false;
        }
        $tableSchema = $this->getTableSchema($table);
        $result = [];
        foreach ($tableSchema->primaryKey as $name) {
            if ($tableSchema->columns[$name]->autoIncrement) {
                $result[$name] = $this->getLastInsertID($tableSchema->sequenceName);
                break;
            }

            $result[$name] = isset($columns[$name]) ? $columns[$name] : $tableSchema->columns[$name]->defaultValue;
        }

        return $result;
    }

    /**
     * Loads multiple types of constraints and returns the specified ones.
     * @param string $tableName table name.
     * @param string $returnType return type:
     * - primaryKey
     * - foreignKeys
     * - uniques
     * - checks
     * @return mixed constraints.
     */
    private function loadTableConstraints($tableName, $returnType)
    {
        static $sql = <<<'SQL'
SELECT
    /*+ PUSH_PRED("uc") PUSH_PRED("uccol") PUSH_PRED("fuc") */
    "uc"."CONSTRAINT_NAME" AS "name",
    "uccol"."COLUMN_NAME" AS "column_name",
    "uc"."CONSTRAINT_TYPE" AS "type",
    "fuc"."OWNER" AS "foreign_table_schema",
    "fuc"."TABLE_NAME" AS "foreign_table_name",
    "fuccol"."COLUMN_NAME" AS "foreign_column_name",
    "uc"."DELETE_RULE" AS "on_delete",
    "uc"."SEARCH_CONDITION" AS "check_expr"
FROM "USER_CONSTRAINTS" "uc"
INNER JOIN "USER_CONS_COLUMNS" "uccol"
    ON "uccol"."OWNER" = "uc"."OWNER" AND "uccol"."CONSTRAINT_NAME" = "uc"."CONSTRAINT_NAME"
LEFT JOIN "USER_CONSTRAINTS" "fuc"
    ON "fuc"."OWNER" = "uc"."R_OWNER" AND "fuc"."CONSTRAINT_NAME" = "uc"."R_CONSTRAINT_NAME"
LEFT JOIN "USER_CONS_COLUMNS" "fuccol"
    ON "fuccol"."OWNER" = "fuc"."OWNER" AND "fuccol"."CONSTRAINT_NAME" = "fuc"."CONSTRAINT_NAME" AND "fuccol"."POSITION" = "uccol"."POSITION"
WHERE "uc"."OWNER" = :schemaName AND "uc"."TABLE_NAME" = :tableName
ORDER BY "uccol"."POSITION" ASC
SQL;

        $resolvedName = $this->resolveTableName($tableName);
        $constraints = $this->db->createCommand($sql, [
            ':schemaName' => $resolvedName->schemaName,
            ':tableName' => $resolvedName->name,
        ])->disableTranslate()->queryAll();
        $constraints = $this->normalizePdoRowKeyCase($constraints, true);
        $constraints = ArrayHelper::index($constraints, null, ['type', 'name']);
        $result = [
            'primaryKey' => null,
            'foreignKeys' => [],
            'uniques' => [],
            'checks' => [],
        ];
        foreach ($constraints as $type => $names) {
            foreach ($names as $name => $constraint) {
                switch ($type) {
                    case 'P':
                        $result['primaryKey'] = new Constraint([
                            'name' => $name,
                            'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                        ]);
                        break;
                    case 'R':
                        $result['foreignKeys'][] = new ForeignKeyConstraint([
                            'name' => $name,
                            'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                            'foreignSchemaName' => $constraint[0]['foreign_table_schema'],
                            'foreignTableName' => $constraint[0]['foreign_table_name'],
                            'foreignColumnNames' => ArrayHelper::getColumn($constraint, 'foreign_column_name'),
                            'onDelete' => $constraint[0]['on_delete'],
                            'onUpdate' => null,
                        ]);
                        break;
                    case 'U':
                        $result['uniques'][] = new Constraint([
                            'name' => $name,
                            'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                        ]);
                        break;
                    case 'C':
                        $result['checks'][] = new CheckConstraint([
                            'name' => $name,
                            'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                            'expression' => $constraint[0]['check_expr'],
                        ]);
                        break;
                }
            }
        }
        foreach ($result as $type => $data) {
            $this->setTableMetadata($tableName, $type, $data);
        }

        return $result[$returnType];
    }
}
