<?php
/**
 * Creator: jpinkney
 */

/*
 *
 * This always need to be required when using this API
 *
 */
require_once 'TVMazeIncludes.php';

/*
 * Create a new Client object that will allow us to access all the api's functionality
 */
$Client = new JPinkney\TVMaze\Client;

/*
 * List of some methods that you can use. Others will be included in more formal documentation
 */
$Client->TVMaze->search('Arrow');
$Client->TVMaze->singleSearch('The Walking Dead');
$Client->TVMaze->getShowBySiteID('TVRage', 33272);
