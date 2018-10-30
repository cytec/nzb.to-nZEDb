<?php

class Cache {

  private $cpath = false;
  private $ctime = CACHE_DURATION; //defaults to 1h
  private $crepl = "%TOPSECRETAPIKEY%"; //replacement for apikey in cahed file
  private $logger = null;

  public function __construct($ctime=CACHE_DURATION) {
    $this->ctime = $ctime;
    $this->cpath = CACHE_DIR;
    $this->logger = new Logger();
  }

  public function getPath($file) {
    $file = md5($file);
    return $this->cpath . $file;
  }

  public function check($file, $apikey) {
    if(DISABLE_CACHE) {
      return false;
    }
    $file = md5($file);
    if($this->ctime > 0) {
      $cfile = $this->cpath . $file;
      if(is_file($cfile)) {
        // Cache vorhanden
        $cacheage = filemtime($cfile);
        $now      = time();
        $diff     = ($now - $cacheage);
        if($diff < $this->ctime) {
          // Use Cache
          $this->logger->log("result still in cache, returning cached version from: " . $file);
          header('Content-Type: application/xml; charset=utf-8');
          $content = file_get_contents($cfile);
          $content = str_replace($this->crepl, $apikey, $content);
          die($content);
        } else {
          $this->logger->log("cached version " . $file . " to old, deleting and requesting new content");
          unlink($cfile);
        }
      } else {
        $this->logger->log("no cached version found");
      }
      return $cfile;
    }
    return false;
  }

  public function write($file, $data, $apikey) {
    if(DISABLE_CACHE) {
      return false;
    }
    $file = md5($file);
    $cfile = $this->cpath . $file;
    $data = str_replace($apikey, $this->crepl, $data);
    file_put_contents($cfile, $data);
    $this->logger->log("result cached for the next " . ($this->ctime/60) . " minutes in file: " . $file);
  }
}

?>
