<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty relative date / time plugin
 *
 * Type:     modifier<br>
 * Name:     relative_datetime<br>
 * Date:     March 18, 2009
 * Purpose:  converts a date to a relative time
 * Input:    date to format
 * Example:  {$datetime|relative_datetime}
 * @author   Eric Lamb <eric@ericlamb.net>
 * @version 1.0
 * @param string
 * @return string
 */
function smarty_modifier_relative_datetime($timestamp)
{
    if(!$timestamp){
        return 'N/A';
    }

    if(!is_numeric($timestamp)) {
        $timestamp = (int)strtotime($timestamp);
    } else {
        $timestamp = (int)$timestamp;
    }

    // $timestamp = (int)strtotime($timestamp);
    $difference = time() - $timestamp;
    $periods = array("sec", "min", "Stunde", "Tag", "Woche","Monat", "Jahr", "jahrzehnt");
    $lengths = array("60","60","24","7","4.35","12","12","10");
    $total_lengths = count($lengths);

    if ($difference > 0) { // this was in the past
        $ending = "vor";
    } else { // this was in the future
        $difference = -$difference;
        $ending = " in";
    }
    //return;

    for($j = 0; $difference > $lengths[$j] && $total_lengths > $j; $j++) {
        $difference /= $lengths[$j];
    }

    $difference = round($difference);
    if($difference != 1) {
        if($periods[$j] == "Woche"){
            $periods[$j].= "n";
        //dirty fix for shows which air today...
        } else if ($periods[$j] == "Stunde" || $periods[$j] == "min" || $periods[$j] == "sec"){
            if($ending == "vor"){
                $periods[$j] = "heute";
                $ending = "";
                $difference = "";
            } else{
                $periods[$j] = "morgen";
                $ending = "";
                $difference = "";
            }

        } else {
            $periods[$j].= "en";
        }
    }


    $text = "$ending $difference $periods[$j]";

    return $text;
}
?>

