<?php

namespace JPinkney\TVMaze;

Class AKA
{
	/**
	 * @param $aka_data
	 */
	public function __construct($aka_data)
	{
		$this->akas = '';
		
		if(!empty($aka_data['name'])) {
			$this->akas = $aka_data['name'];
		}
	}
}
