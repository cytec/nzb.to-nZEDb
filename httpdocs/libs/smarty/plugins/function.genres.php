<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.genres.php
 * Type:     function
 * Name:     genres
 * Version:  1.0
 * Date:     Jan 09, 2003
 * Purpose:  implode an array
 * Input:
 *         - from = array to implode (required)
 *         - delim = delimiter to implode with (optional, default none)
 *         - none = output when the array is empty (optional, default none)
 *         - pre = string to prepend to each array entry (optional, default none)
 *         - post = string to append to each array entry (optional, default none)
 *
 * Example:  $foo = array('a', 'b', 'c');
 *           {implode from=$foo delim="," pre="[" post="]" none="Empty list!"} 
 * 
 * Output:   [a],[b],[c]
 *        
 * Install:  Just drop into the plugin directory.
 *          
 * Author:   Cal Henderson <cal@iamcal.com>
 * -------------------------------------------------------------
 */
    function smarty_function_genres($params){
        $from = !empty($params['from']) ? $params['from'] : array();
        $pre = !empty($params['pre']) ? $params['pre'] : '';
        $post = !empty($params['post']) ? $params['post'] : '';
        $none = !empty($params['none']) ? $params['none'] : '';
        $delim = !empty($params['delim']) ? $params['delim'] : ',';
        if (!count($from)){
            return $none;
        }
        $src = array();
        foreach($from as $item){
            if ($item != ""){
                $src[] = $pre.$item.$post;
            }
        }
        return implode($delim, $src);
    }
?>