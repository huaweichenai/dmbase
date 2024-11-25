<?php

namespace huaweichenai\dmbase\utils;

class QuoteHelper
{
    public static function convertItemsQuote(&$parsed, $sourceQuote = '`', $targetQuote = '"')
    {
        if (!isset($parsed['UNION'])) {
            $parsed = ['UNION' => [$parsed]];
        }
        foreach ($parsed['UNION'] as &$query) {
            foreach ($query as $key => &$subQuery) {
                if ($key == 'LIMIT') {
                    continue;
                }
                QuoteHelper::replaceColumnQuote($subQuery, $sourceQuote, $targetQuote);
                if (in_array($key,['FROM','UPDATE','DELETE'])) {
                    QuoteHelper::replaceTableQuote($subQuery, $sourceQuote, $targetQuote);
                }
                if ($key == 'HAVING') {
                    QuoteHelper::replaceHavingAliasQuote($subQuery, $sourceQuote, $targetQuote);
                }
                QuoteHelper::replaceAliasQuote($subQuery, $sourceQuote, $targetQuote);

            }
        }
    }


    public static function replaceColumnQuote(&$items, $sourceQuote = '`', $targetQuote = '"')
    {
        if (is_array($items)) {
            foreach ($items as &$v) {
                if (!isset ($v['expr_type']) || !isset($v['base_expr'])) {
                    continue;
                }
                // 变量不需要加引号
                if (strpos($v['base_expr'], ':') === 0){
                    continue;
                }


                if ($v['expr_type'] == 'colref' && $v['base_expr'] != '*') {
                    $v['base_expr'] = self::replaceQuote($v['base_expr'], $sourceQuote, $targetQuote);
                    // 减号处理
                    if (strpos($v['base_expr'], '-') !== false) {
                        $v['base_expr'] = '-' . str_replace('-', '', $v['base_expr']);
                    }

                    // 点号处理
                    if (strpos($v['base_expr'], '.') !== false && strpos($v['base_expr'], "{$targetQuote}.{$targetQuote}") == false) {
                        $v['base_expr'] = str_replace('.', "{$targetQuote}.{$targetQuote}", $v['base_expr']);
                    }

                }

                if (isset ($v['sub_tree']) && is_array($v['sub_tree'])) {
                    if (strpos($v['expr_type'], 'subquery') !== false) {
                        self::convertItemsQuote($v['sub_tree'], $sourceQuote, $targetQuote);
                    } else {
                        self::replaceColumnQuote($v['sub_tree'], $sourceQuote, $targetQuote);
                    }
                }
            }
        }
    }

    public static function replaceTableQuote(&$items, $sourceQuote = '`', $targetQuote = '"')
    {
        if (is_array($items)) {
            foreach ($items as &$v) {
                if (isset ($v['expr_type']) && $v['expr_type'] == 'table') {
                    if (isset ($v['ref_clause']) && !empty($v['ref_clause'])) {
                        self::replaceColumnQuote($v['ref_clause'], $sourceQuote, $targetQuote);
                    }
                    $v['table'] = self::replaceQuote($v['table'], $sourceQuote, $targetQuote);
                }
                if (isset ($v['sub_tree']) && is_array($v['sub_tree'])) {
                    if (strpos($v['expr_type'], 'subquery') !== false) {
                        self::convertItemsQuote($v['sub_tree'], $sourceQuote, $targetQuote);
                    } else {
                        self::replaceTableQuote($v['sub_tree'], $sourceQuote, $targetQuote);
                    }
                }
            }
        }
    }

    public static function replaceAliasQuote(&$items, $sourceQuote = '`', $targetQuote = '"')
    {
        if (is_array($items)) {
            foreach ($items as &$v) {
                if (!empty(isset ($v['alias']) && $v['alias'])) {
                    $v['alias']['name'] = self::replaceQuote($v['alias']['name'], $sourceQuote, $targetQuote);
                }
                if (is_array(isset ($v['sub_tree']) && $v['sub_tree'])) {
                    if (strpos($v['expr_type'], 'subquery') !== false) {
                        self::convertItemsQuote($v['sub_tree'], $sourceQuote, $targetQuote);
                    } else {
                        self::replaceAliasQuote($v['sub_tree'], $sourceQuote, $targetQuote);
                    }
                }
            }
        }
    }

    public static function replaceHavingAliasQuote(&$items, $sourceQuote = '`', $targetQuote = '"')
    {
        if (is_array($items)) {
            foreach ($items as &$v) {
                if (isset ($v['expr_type']) && $v['expr_type'] == 'alias') {
                    $v['base_expr'] = self::replaceQuote($v['base_expr'], $sourceQuote, $targetQuote);
                }
                if (isset ($v['sub_tree']) && is_array($v['sub_tree'])) {
                    if (strpos($v['expr_type'], 'subquery') !== false) {
                        self::convertItemsQuote($v['sub_tree'], $sourceQuote, $targetQuote);
                    } else {
                        self::replaceHavingAliasQuote($v['sub_tree'], $sourceQuote, $targetQuote);
                    }
                }
            }

        }
    }

    private static function replaceQuote($string, $sourceQuote = '`', $targetQuote = '"')
    {
        $string = str_replace($sourceQuote, '', $string);
        if (strpos($string, $targetQuote) === false) {
            $string = $targetQuote . $string . $targetQuote;
        }
        return $string;
    }

}
