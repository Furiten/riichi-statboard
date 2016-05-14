<?php

/**
 * Array helper class
 */
class ArrayHelpers
{
    /**
     * Make an array containing selected collection values as keys and collections as values
     * Example:
     * [
     *    ['a' => 1, 'b' => 2],
     *    ['a' => 3, 'b' => 4],
     *    ['a' => 5, 'b' => 6]
     * ]
     *  with elmKey = 'a' becomes:
     * [
     *   1 => ['a' => 1, 'b' => 2],
     *   3 => ['a' => 3, 'b' => 4],
     *   5 => ['a' => 5, 'b' => 6]
     * ]
     *
     * @throws Exception
     * @param array $array
     * @param string $elmKey
     * @param bool $append
     * @return array
     */
    public static function elm2Key(array $array, $elmKey, $append = false)
    {
        $result = [];

        if (!$array) {
            return $result;
        }

        foreach ($array as $k => $v) {
            if (!isset($v[$elmKey])) {
                throw new Exception('Wrong key');
            }
            if ($append) {
                $result[$v[$elmKey]] []= $v;
            } else {
                if (empty($result[$v[$elmKey]])) {
                    $result[$v[$elmKey]] = $v;
                }
            }
        }
        return $result;
    }
}
