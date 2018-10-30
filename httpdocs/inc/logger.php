<?php class Logger {

    # Private key
    public static $logfile = 'nzbto2newznab.txt';
    public static $logdate = 'Y-m-d H:i:s';
		private $threshold = 1024;
	 	private $no_rotation = FALSE;

    public function __construct($file=null, $date=null) {
      if($file) {
        self::$logfile = LOG_DIR . $file;
      }
      if($date) {
        self::$logdate = $date;
      }
			$this->threshold = 1024;

    }

    public function logWithArray($line, $level="INFO", $array=false) {
      if($array) {
        $line .= " ";
        foreach ($array as $key => $value) {
          $line .= sprintf("%s='%s', ", $key, $value);
        }
      }
      $this->log($line, $level);
    }

    //implement logging do a databse
    public function logToSQL() {

    }

		//rotate log if log is bigger than threshold bytes
		private function _rotate() {
			if ($this->no_rotation) {
				return;
			}
			$threshold_bytes = $this->threshold* 1024;

			if( file_exists(self::$logfile) && filesize(self::$logfile) >= $threshold_bytes ) {
	      // rotate
	      $path_info = pathinfo(self::$logfile);
	      $base_directory = $path_info['dirname'];
	      $base_name = $path_info['basename'];
	      $num_map = array();
	      foreach( new DirectoryIterator($base_directory) as $fInfo) {
	    		if( $fInfo->isDot() || ! $fInfo->isFile() ) {
						continue;
					}
	    		if ( preg_match('/^'.$base_name.'\.?([0-9]*)$/', $fInfo->getFilename(), $matches) ) {
			      $num = $matches[1];
			      $file2move = $fInfo->getFilename();
			      if ($num == '') $num = -1;
			      $num_map[$num] = $file2move;
			    }
	      }
	      krsort($num_map);
	      foreach($num_map as $num => $file2move) {
			    $targetN = $num+1;
			    rename($base_directory.DIRECTORY_SEPARATOR.$file2move,$base_directory.DIRECTORY_SEPARATOR.$base_name.'.'.$targetN);
	      }
	    }
		}

    public function log($line, $level="INFO") {
      $date = new DateTime();
      $uldate = $date->format(self::$logdate);
			$this->_rotate();
      $file = fopen(self::$logfile, "a");
      fputs($file,
        $uldate .
        ", " . $level .
        ": " . $line ."\n"
      );
      fclose($file);
    }

}
?>
