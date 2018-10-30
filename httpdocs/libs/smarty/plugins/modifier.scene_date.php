<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty scene date plugin
 *
 * Type:     modifier<br>
 * Name:     scene_date<br>
 * Date:     March 18, 2009
 * Purpose:  converts a timestamp to a date with a offset given in days
 * Input:    date to format
 * Example:  {$timestamp|scene_date:"7"}
 * @author   cytec <iamcytec@googlemail.com>
 * @version 1.0
 * @param string
 * @return string
 */
function smarty_modifier_scene_date($timestamp, $offset = 0, $format = false)
{
    if (!$timestamp) {
        return 'N/A';
    }

    if (!is_numeric($timestamp)) {
        $timestamp = (int)strtotime($timestamp);
    } else {
        $timestamp = (int)$timestamp;
    }

    $time_to_add = (int)$offset * 24 * 60 * 60;
    $timestamp = $timestamp - $time_to_add;

    if (!$format) {
        return $timestamp;
    } else {
        return strftime($format, $timestamp);
    }
}


