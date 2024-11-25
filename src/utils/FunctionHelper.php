<?php

namespace huaweichenai\dmbase\utils;

use huaweichenai\dmbase\table\functions\AddDateFunctionTranslator;
use huaweichenai\dmbase\table\functions\ConvertFunctionTranslator;
use huaweichenai\dmbase\table\functions\DateAddFunctionTranslator;
use huaweichenai\dmbase\table\functions\DateFunctionTranslator;
use huaweichenai\dmbase\table\functions\DateSubFunctionTranslator;
use huaweichenai\dmbase\table\functions\DateFormatFunctionTranslator;
use huaweichenai\dmbase\table\functions\LengthFunctionTranslator;
use huaweichenai\dmbase\table\functions\Md5FunctionTranslator;
use huaweichenai\dmbase\table\functions\UUIDFunctionTranslator;
use huaweichenai\dmbase\exceptions\DbxException;

class FunctionHelper
{
    /**
     * @throws DbxException
     */
    public static function replaceFunctionEntrance(&$parsed, $sourceName, $targetName)
    {
        if (!isset($parsed['UNION'])) {
            $parsed = ['UNION' => [$parsed]];
        }
        foreach ($parsed['UNION'] as &$query) {
            foreach ($query as $key => &$subQuery) {
                if ($key == 'LIMIT') {
                    continue;
                }
                switch ($sourceName) {
                    case LengthFunctionTranslator::FUNCTION_NAME:
                    case UUIDFunctionTranslator::FUNCTION_NAME:
                        self::replaceFunction($subQuery, $sourceName, $targetName);
                        break;
                    case DateFunctionTranslator::FUNCTION_NAME:
                        self::replaceDateFunction($subQuery, $sourceName, $targetName);
                        break;
                    case Md5FunctionTranslator::FUNCTION_NAME:
                        self::wrapFunction($subQuery, $sourceName, $targetName);
                        break;
                    case ConvertFunctionTranslator::FUNCTION_NAME:
                        self::interchangeFunctionPosition($subQuery, $sourceName, $targetName);
                        break;
                    case DateSubFunctionTranslator::FUNCTION_NAME:
                    case AddDateFunctionTranslator::FUNCTION_NAME:
                    case DateAddFunctionTranslator::FUNCTION_NAME:
                        self::replaceDateAddFunction($subQuery, $sourceName, $targetName);
                        break;
                    case DateFormatFunctionTranslator::FUNCTION_NAME:
                        self::replaceDateFormatFunction($subQuery, $sourceName, $targetName);
                        break;
                    default:
                        throw new DbxException("不存在该函数的翻译能力，函数名为：{$sourceName}");
                }
            }
        }
    }

    /**
     * @throws DbxException
     */
    public static function changeConvertOrderEntrance(&$parsed)
    {
        if (!isset($parsed['UNION'])) {
            $parsed = ['UNION' => [$parsed]];
        }
        foreach ($parsed['UNION'] as &$query) {
            foreach ($query as $key => &$subQuery) {
                if ($key == 'LIMIT') {
                    continue;
                }

                self::changeConvertOrderExpr($subQuery);
            }
        }
    }

    /**
     * @throws DbxException
     */
    public static function replaceFunction(&$items, $sourceName, $targetName)
    {
        if (is_array($items)) {
            foreach ($items as &$v) {
                if (isset ($v['expr_type']) && strpos($v['expr_type'], 'function') !== false) {
                    if (strtoupper($v['base_expr']) == $sourceName) {
                        $v['base_expr'] = $targetName;
                    }
                }
                if (isset ($v['sub_tree']) && is_array($v['sub_tree'])) {
                    if (isset ($v['expr_type']) && $v['expr_type'] == 'subquery') {
                        self::replaceFunctionEntrance($v['sub_tree'], $sourceName, $targetName);
                    } else {
                        self::replaceFunction($v['sub_tree'], $sourceName, $targetName);
                    }
                }
            }
        }
    }

    /**
     * @throws DbxException
     */
    public static function replaceDateFunction(&$items, $sourceName, $targetName)
    {
        if (is_array($items)) {
            foreach ($items as &$v) {
                if (isset ($v['expr_type']) && strpos($v['expr_type'], 'function') !== false) {
                    if (strtoupper($v['base_expr']) == $sourceName) {
                        $v['base_expr'] = $targetName;
                        $v['sub_tree'][] = [
                            'expr_type' => 'const',
                            'base_expr' => "'yyyy-mm-dd'",
                            'sub_tree' => false,
                        ];
                    }
                }
                if (isset ($v['sub_tree']) && is_array($v['sub_tree'])) {
                    if (isset ($v['expr_type']) && $v['expr_type'] == 'subquery') {
                        self::replaceFunctionEntrance($v['sub_tree'], $sourceName, $targetName);
                    } else {
                        self::replaceDateFunction($v['sub_tree'], $sourceName, $targetName);
                    }
                }
            }
        }
    }


    /**
     * @throws DbxException
     */
    public static function replaceDateFormatFunction(&$items, $sourceName, $targetName)
    {
        if (is_array($items)) {
            foreach ($items as &$v) {
                if (isset ($v['expr_type']) && strpos($v['expr_type'], 'function') !== false) {
                    if (strtoupper($v['base_expr']) == $sourceName) {
                        $v['base_expr'] = $targetName;
                        if ($v['sub_tree'][1]['base_expr'] == "'%Y-%m-%d'") {
                            $v['sub_tree'][1]['base_expr'] = "'YYYY-MM-DD'";
                        }
                    }
                }
                if (isset ($v['sub_tree']) && is_array($v['sub_tree'])) {
                    if (isset ($v['expr_type']) && $v['expr_type'] == 'subquery') {
                        self::replaceFunctionEntrance($v['sub_tree'], $sourceName, $targetName);
                    } else {
                        self::replaceDateFunction($v['sub_tree'], $sourceName, $targetName);
                    }
                }
            }
        }
    }

    /**
     * @throws DbxException
     */
    public static function replaceDateAddFunction(&$items, $sourceName, $targetName)
    {
        if (is_array($items)) {
            foreach ($items as &$v) {
                if (isset ($v['expr_type']) && (strpos($v['expr_type'], 'function') !== false) && in_array(strtoupper($v['base_expr']), [DateAddFunctionTranslator::FUNCTION_NAME, DateSubFunctionTranslator::FUNCTION_NAME, AddDateFunctionTranslator::FUNCTION_NAME])) {
                    if ($v['sub_tree'][1]['expr_type'] == 'expression') {
                        $v['sub_tree'][1]['sub_tree'][2]['expr_type'] = 'const';
                        if (strtoupper($v['base_expr']) == DateSubFunctionTranslator::FUNCTION_NAME) {
                            $v['sub_tree'][1]['sub_tree'][1]['base_expr'] = '-' . $v['sub_tree'][1]['sub_tree'][1]['base_expr'];
                            if ($v['sub_tree'][1]['sub_tree'][1]['expr_type'] == 'colref') {
                                unset($v['sub_tree'][1]['sub_tree'][2]['no_quotes']);
                            }
                        }

                        $subTree = [];
                        $subTree[] = $v['sub_tree'][0];
                        $subTree[] = $v['sub_tree'][1]['sub_tree'][1];
                        $typeContent = $v['sub_tree'][1]['sub_tree'][2];
                        $v['sub_tree'] = $subTree;
                        switch (strtolower($typeContent['base_expr'])) {
                            case 'month':
                                $v['base_expr'] = 'ADD_MONTHS';
                                break;
                            case 'day':
                                $v['base_expr'] = 'ADD_DAYS';
                                break;
                            case 'week':
                                $v['base_expr'] = 'ADD_WEEKS';
                                break;
                            default :
                                throw new DbxException('不支持的类型：' . $typeContent['base_expr']);
                        }
                    }
                }
                if (isset ($v['sub_tree']) && is_array($v['sub_tree'])) {
                    if (isset ($v['expr_type']) && $v['expr_type'] == 'subquery') {
                        self::replaceFunctionEntrance($v['sub_tree'], $sourceName, $targetName);
                    } else {
                        self::replaceDateAddFunction($v['sub_tree'], $sourceName, $targetName);
                    }
                }

            }
        }
    }

    /**
     * @throws DbxException
     */
    public static function wrapFunction(&$items, $sourceName, $warpName)
    {
        if (is_array($items)) {
            foreach ($items as &$v) {
                if (isset ($v['expr_type']) && strpos($v['expr_type'], 'function') !== false) {
                    if (strtoupper($v['base_expr']) == $sourceName) {
                        if (isset ($v['sub_tree']) && is_array($v['sub_tree'])) {
                            if ($v['expr_type'] == 'subquery') {
                                self::replaceFunctionEntrance($v['sub_tree'], $sourceName, $warpName);
                            } else {
                                self::wrapFunction($v['sub_tree'], $sourceName, $warpName);
                            }
                        }

                        $innerItem = $v;
                        $v['base_expr'] = $warpName;
                        $v['sub_tree'] = [$innerItem];
                    }
                } else {
                    if (isset ($v['sub_tree']) && is_array($v['sub_tree'])) {
                        if (isset ($v['expr_type']) && $v['expr_type'] == 'subquery') {
                            self::replaceFunctionEntrance($v['sub_tree'], $sourceName, $warpName);
                        } else {
                            self::wrapFunction($v['sub_tree'], $sourceName, $warpName);
                        }
                    }
                }


            }
        }
    }

    /**
     * @throws DbxException
     */
    public static function interchangeFunctionPosition(&$items, $sourceName, $targetName)
    {
        if (is_array($items)) {
            foreach ($items as &$v) {
                if (isset ($v['expr_type']) && strpos($v['expr_type'], 'function') !== false) {
                    if (strtoupper($v['base_expr']) == $sourceName && count($v['sub_tree']) == 2) {
                        $temp = $v['sub_tree'][1];
                        $v['sub_tree'][1] = $v['sub_tree'][0];
                        $v['sub_tree'][0] = $temp;
                    }
                }
                if (isset ($v['sub_tree']) && is_array($v['sub_tree'])) {
                    if (isset ($v['expr_type']) && $v['expr_type'] == 'subquery') {
                        self::replaceFunctionEntrance($v['sub_tree'], $sourceName, $targetName);
                    } else {
                        self::interchangeFunctionPosition($v['sub_tree'], $sourceName, $targetName);
                    }
                }
            }
        }
    }

    /**
     * @throws DbxException
     */
    public static function changeConvertOrderExpr(&$items)
    {
        if (is_array($items)) {
            foreach ($items as &$v) {
                if (isset($v['expr_type']) && $v['expr_type'] == 'expression') {
                    if ($v['sub_tree'][0]['base_expr'] == 'CONVERT' && $v['sub_tree'][0]['sub_tree'][0]['sub_tree'][1]['base_expr'] == 'USING') {
                        $subTree = [];
                        $subTree[] = $v['sub_tree'][0]['sub_tree'][0]['sub_tree'][0];
                        $subTree[] = [
                            'expr_type' => 'const',
                            'base_expr' => "'NLS_SORT = SCHINESE_PINYIN_M'",
                            "sub_tree" => false
                        ];
                        $v['sub_tree'] = $subTree;
                        $v['expr_type'] = 'function';
                        $v['base_expr'] = 'NLSSORT';
                    }
                }
                if (isset($v['expr_type']) && $v['expr_type'] == 'subquery') {
                    self::changeConvertOrderEntrance($v['sub_tree']);
                }

            }
        }
    }

}
