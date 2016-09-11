<?php

namespace Helper;

/**
 * Description of Sql Helper
 *
 * @author kuangzhiqiang
 */
class Sql
{

    /**
     * 生成查询的条件和绑定数组
     * @param string $field 字段名
     * @param string|array $values 值，IN的时候传数组
     * @param string $op 操作符 =|>|<|like...
     * @return array                array($condition, $bind)
     */
    public static function buildBindCondition($field, $values, $op = null)
    {
        $condition = '';
        $bind_data = array();
        if (is_array($values) && !empty($values))
        {
            if (is_null($op))
            {
                $op = "IN";
            }
            $i      = 0;
            $in_arr = array();
            foreach ($values as $v)
            {
                $i++;
                $k          = str_replace('.', '_', ":{$field}_{$i}");
                $in_arr[$k] = $v;
            }
            switch (trim(strtolower($op)))
            {
                case 'in':
                case 'not in':
                    $condition .= " {$field} {$op} (" . implode(',', array_keys($in_arr)) . ") ";
                    break;
                case 'between':
                    $condition .= " {$field} {$op} " . implode(' AND ', array_keys($in_arr)) . " ";
                    break;
            }
            $bind_data += $in_arr;
        }
        else
        {
            if (is_null($op) || strtolower($op) == 'equal')
            {
                $op = "=";
            }
            $k = str_replace('.', '_', ":{$field}");
            $condition .= " {$field} {$op} {$k} ";
            $bind_data[$k] = $values;
        }

        return array($condition, $bind_data);
    }

    /**
     * 拼接limit字符串
     * @param int $page_size
     * @param int $page_index
     * @return string
     */
    public static function buildLimit($page_size, $page_index = 0)
    {
        $limit = '';
        if ($page_size > 0)
        {
            // 每页条数不可大于最大限定值
            $page_size = $page_size <= PAGE_SIZE_MAX ? (int) $page_size : PAGE_SIZE_MAX;
            $limit .= ' LIMIT ' . $page_size;
            if ($page_index < 0)
            {
                $page_index = 0;
            }
            $offset = $page_index * $page_size;
            $limit .= ' OFFSET ' . ($offset >= 0 ? $offset : 0);
        }
        return $limit;
    }

    /**
     * 拼接排序字符串
     * @param array $sort_options
     * @param string $default
     * @return string
     */
    public static function buildSort(array $sort_options, $default = '')
    {
        $order_by = '';
        if (!empty($sort_options))
        {
            $order_by .= ' ORDER BY ';
            if (!empty($sort_options))
            {
                $i = 0;
                foreach ($sort_options as $sort => $order)
                {
                    if (in_array(strtolower($order), array('asc', 'desc')))
                    {
                        if ($i !== 0)
                        {
                            $order_by .= ',';
                        }
                        $order_by .= "{$sort} {$order}";
                    }
                    $i++;
                }
            }
        }
        elseif ($default !== '')
        {
            $order_by .= ' ORDER BY ' . $default;
        }
        return $order_by;
    }

    /**
     * 生成sql可执行的字符串
     * @param string $string
     * @return \String
     */
    static public function string($string)
    {
        static $_str = array();
        if (!isset($_str[$string]))
        {
            $_str[$string] = function() use ($string)
            {
                return $string;
            };
        }
        return $_str[$string];
    }

}
