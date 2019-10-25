<?php
/**
 * Creator: jpinkney
 * Date: 9/15/15
 * Time: 2:12 PM
 */

namespace JPinkney\TVMaze;

/**
 * Class Crew
 *
 * @package JPinkney\TVMaze
 */
class Crew {

	/**
	 * @var
	 */
	public $type;

	/**
	 * @param $crew_data
	 */
	public function __construct($crew_data){
		$this->type = $crew_data['type'];
	}

}