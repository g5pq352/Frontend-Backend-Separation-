<?php

if (!function_exists('h')) {
    /**
     * 全域 HTML 跳脫函式
     * @param string|null $string
     * @return string
     */
    function hsc($string)
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}