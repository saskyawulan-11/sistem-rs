<?php

if (!function_exists('formatNumberWithK')) {
    /**
     * Format number with 'k' suffix for thousands
     * 
     * @param int|float $number
     * @param int $decimals
     * @return string
     */
    function formatNumberWithK($number, $decimals = 1)
    {
        if ($number >= 1000) {
            return number_format($number / 1000, $decimals) . 'k';
        }
        return (string) $number;
    }
}

if (!function_exists('formatNumberWithM')) {
    /**
     * Format number with 'M' suffix for millions
     * 
     * @param int|float $number
     * @param int $decimals
     * @return string
     */
    function formatNumberWithM($number, $decimals = 1)
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, $decimals) . 'M';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, $decimals) . 'k';
        }
        return (string) $number;
    }
}
