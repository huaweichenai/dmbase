<?php

namespace huaweichenai\dmbase\utils;

use huaweichenai\dmbase\exceptions\DbxException;

class ValidateHelper
{
    /**
     * @throws DbxException
     */
    public static function validateDbsEntrance($parsed, $dbs)
    {
        if (!isset($parsed['UNION'])) {
            $parsed = ['UNION' => [$parsed]];
        }
        foreach ($parsed['UNION'] as $query) {
            foreach ($query as $key => $subQuery) {
                if ($key == 'LIMIT') {
                    continue;
                }
                self::validateDbs($subQuery, $dbs);
            }
        }
    }


    /**
     * @throws DbxException
     */
    public static function validateDbs($items, $dbs)
    {
        if (!empty($items)) {
            foreach ($items as $v) {
                if (isset ($v['expr_type']) && $v['expr_type'] == 'table') {
                    if (!self::validTable($v['table'], $dbs)) {
                        throw new DbxException('不支持的系统库:' . $v['table']);
                    }
                }

                if (isset ($v['sub_tree']) && is_array($v['sub_tree'])) {
                    if (isset ($v['expr_type']) && $v['expr_type'] == 'subquery') {
                        self::validateDbsEntrance($v['sub_tree'], $dbs);
                    } else {
                        self::validateDbs($v['sub_tree'], $dbs);
                    }
                }
            }
        }
    }

    /**
     * @param $table
     * @param $dbs
     * @return false
     */
    private static function validTable($table, $dbs)
    {
        foreach ($dbs as $db) {
            if (strpos(strtolower($table), $db . '.') !== false) {
                return false;
            }
        }
        return true;
    }

    /**
     * @throws DbxException
     */
    public static function validateFunctionsEntrance($parsed, $functions)
    {
        if (!isset($parsed['UNION'])) {
            $parsed = ['UNION' => [$parsed]];
        }
        foreach ($parsed['UNION'] as $query) {
            foreach ($query as $key => $subQuery) {
                if ($key == 'LIMIT') {
                    continue;
                }
                if (in_array(strtolower($key), $functions)) {
                    throw new DbxException('不支持的函数:' . $key);
                }

                self::validateFunctions($subQuery, $functions);
            }
        }
    }


    /**
     * @throws DbxException
     */
    public static function validateFunctions($items, $functions)
    {
        if (!empty($items)) {
            foreach ($items as $v) {
                if (isset ($v['expr_type']) && (strpos($v['expr_type'], 'function') !== false)) {
                    if (in_array(strtolower($v['base_expr']), $functions)) {
                        throw new DbxException('不支持的函数:' . $v['base_expr']);
                    }
                }

                if (isset ($v['sub_tree']) && is_array($v['sub_tree'])) {
                    if (isset ($v['expr_type']) && $v['expr_type'] == 'subquery') {
                        self::validateFunctionsEntrance($v['sub_tree'], $functions);
                    } else {
                        self::validateFunctions($v['sub_tree'], $functions);
                    }
                }
            }
        }
    }


}
