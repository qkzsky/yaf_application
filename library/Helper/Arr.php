<?php
/**
 * Created by PhpStorm.
 * User: kuangzhiqiang
 * Date: 16/9/13
 * Time: 18:14
 */

namespace Helper;


class Arr
{

    /**
     * 过滤数组，只返回数组中的某一个下标所对应的一维数组
     *
     * @param array $arr 原始数组
     * @param string $field_name 字段
     * @return  array
     */
    public static function filter($arr, $field_name)
    {
        $data = array();
        if (is_array($arr) && !empty($arr)) {
            foreach ($arr as $k => $v) {
                $data[] = isset($v[$field_name]) ? $v[$field_name] : '';
            }
        }
        return $data;
    }

    /**
     * 对数据数组按字段进行分组
     *
     * @param array $list
     * @param array $key_fields 用于分组的key,多个代表多维
     * @param array $val_fields 分组的值
     * @return array
     * @throws \AppException
     */
    static function toMap(array $list, array $key_fields = array(), array $val_fields = array())
    {
        $data = array();
        foreach ($list as $row) {
            if (empty($val_fields)) {
                $val_data = $row;
            } elseif (count($val_fields) === 1) {
                $val_key  = $val_fields[0];
                $val_data = isset($row[$val_key]) ? $row[$val_key] : null;
            } else {
                $val_data = array();
                foreach ($val_fields as $val_key) {
                    $val_data[$val_key] = isset($row[$val_key]) ? $row[$val_key] : null;
                }
            }

            $_node = &$data;
            $_path = "";
            foreach ($key_fields as $key) {
                if (!isset($row[$key])) {
                    throw new \AppException(sprintf("not found key fields [%s]", $key), \ErrorCode::INVALID_PARAMETER);
                }
                $_key = $row[$key];
                if (!isset($_node[$_key])) {
                    $_node[$_key] = [];
                }
                $_path .= ($_path ? "." : "") . str_replace(".", "_", $_key);
                $_node = &$_node[$_key];
            }
            if (!empty($_node)) {
                throw new \AppException(sprintf("keys repeat [%s]", $_path), \ErrorCode::INVALID_PARAMETER);
            }
            $_node = $val_data;
            unset($_node);
        }
        return $data;
    }

    /**
     * 对数据数组按字段进行分组
     *
     * @param array $list
     * @param array $key_fields 用于分组的key,多个代表多维
     * @param array $val_fields 分组的值
     * @return array
     * @throws \AppException
     */
    static function toGroup(array $list, array $key_fields = array(), array $val_fields = array())
    {
        $data = array();
        foreach ($list as $row) {
            if (empty($val_fields)) {
                $val_data = $row;
            } elseif (count($val_fields) === 1) {
                $val_key  = $val_fields[0];
                $val_data = isset($row[$val_key]) ? $row[$val_key] : null;
            } else {
                $val_data = array();
                foreach ($val_fields as $val_key) {
                    $val_data[$val_key] = isset($row[$val_key]) ? $row[$val_key] : null;
                }
            }

            $_node = &$data;
            foreach ($key_fields as $key) {
                if (!isset($row[$key])) {
                    throw new \AppException(sprintf("not found key fields [%s]", $key), \ErrorCode::INVALID_PARAMETER);
                }
                $_key = $row[$key];
                if (!isset($_node[$_key])) {
                    $_node[$_key] = [];
                }
                $_node = &$_node[$_key];
            }
            $_node[] = $val_data;
            unset($_node);
        }
        return $data;
    }

    public static function array_sort($arr, $col = "", $order = "SORT_ASC")
    {
        $new_array  = array();
        $sort_array = array();

        if (is_array($arr) && !empty($arr)) {
            foreach ($arr as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $kk => $vv) {
                        if ($kk == $col) {
                            $sort_array[$k] = $vv;
                        }
                    }
                } else {
                    $sort_array[$k] = $v;
                }
            }

            switch ($order) {
                case "SORT_ASC":
                    asort($sort_array);
                    break;
                case "SORT_DESC":
                    arsort($sort_array);
                    break;
                default :
                    return array();
                    break;
            }

            foreach ($sort_array as $k => $v) {
                $new_array[$k] = $arr[$k];
            }
        }

        return $new_array;
    }
}