<?php

namespace huaweichenai\dmbase\utils;

class StringHelper
{
    /**
     * 获取dsn中键的值
     * @param $dsn
     * @param $key
     * @param $driverName
     * @return false|string
     */
    public static function getValueByParesDSN($dsn, $key = 'dbname', $driverName = 'dm')
    {
        if (strpos($dsn, $key, 0) == false) {
            return false;
        }
        $str = substr($dsn, strlen($driverName . ":"));
        $parts = explode(';', $str);
        foreach ($parts as $part) {
            if (strpos($part, $key, 0) === false) {
                continue;
            }
            $kv = explode('=', $part);
            if (isset($kv[1])) {
                return trim($kv[1]);
            }
        }
        return false;
    }

}
