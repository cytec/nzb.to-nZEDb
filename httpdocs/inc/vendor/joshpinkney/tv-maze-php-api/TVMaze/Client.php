<?php

/**
 * User: jpinkney
 * Date: 9/15/15
 * Time: 2:15 PM
 */

namespace JPinkney\TVMaze;

/**
 * This is the file that you are going to include in each of your new projects
 */

/* - Enable these when desired and pass options through __construct
use JPinkney\TVMaze\TVProduction;
use JPinkney\TVMaze\TVShow;
use JPinkney\TVMaze\Actor;
use JPinkney\TVMaze\Character;
use JPinkney\TVMaze\Crew;
use JPinkney\TVMaze\Episode;
use JPinkney\TVMaze\AKA;
*/

/**
 * Class Client
 *
 * @package JPinkney\TVMaze
 */
class Client
{
	/**
	 * @var TVMaze
	 */
	public $TVMaze;

	public function __construct()
	{
		$this->TVMaze = new TVMaze();
	}
}
