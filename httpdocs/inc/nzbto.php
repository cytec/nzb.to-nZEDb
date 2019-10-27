<?php
include INC_DIR . '/simple_html_dom.php';
$multi = false;
class NZBTO {
  private $baseURL = "http://giesn3ivtzp5z2us.onion/";

	private $useProxy = true;
	private $proxy = TORPROXY;
	private $proxyType = 7;
  private $cfg = array("cookie" => "cookie.txt", "user-agent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:20.0) Gecko/20100101 Firefox/20.0");

  private $catIDMap = array(
    "TV" => 5000,
    "TV+WEB" => 5000,
    "Filme" => 2000,
    "Spiele" => 1000,
    "Musik" => 3000,
    "Books" => 3030,
    "Software" => 4000
  );

  public function __construct($user=false) {
    if($user) {
      $this->cfg["cookie"] = CROOT . 'cookies' . DS . $user . ".txt";
    } else {
      $this->cfg["cookie"] = CROOT . 'cookies' . DS . "cookie.txt";
    }
  }

  public function login($user, $pass) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    curl_setopt($curl, CURLOPT_URL, $this->baseURL . "login.php");
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    //curl_setopt($curl, CURLOPT_HTTPGET, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
    curl_setopt($curl, CURLOPT_PROXYTYPE, 7);
    // curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cfg["cookie"]);
		curl_setopt($curl, CURLOPT_REFERER, $this->baseURL . "login");
	  curl_setopt($curl, CURLOPT_HEADER, true);
	  curl_setopt($curl, CURLOPT_POST, true);
	  curl_setopt($curl, CURLOPT_VERBOSE, true);

    $postdata = array(
      'action' => 'login.php',
      'username'=> trim($user),
      'password'=> trim($pass),
      'bind_ip' => '0',
      'Submit' => '.%3AEinloggen%3A.',
      'ret_url' => '',
    );

    curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
    $result = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

    curl_close($curl);
    $header = substr($result, 0, $header_size);
    $body   = substr($result, $header_size);
    $return = array("header" => $header,"body" => $body);

    if($status == 303 || strpos($header, $this->baseURL . "index.php")) {
      return true;
    } else {
      return false;
    }
  }

  public function getDetails($nid) {
    echo $this->baseURL . "/popupdetails.php?n=". $nid;
    $result = $this->getUrl($this->baseURL . "/popupdetails.php?n=". $nid);
    $re = "/.*Passwort:<\\/strong><\\/span>&nbsp;([^<]*)<br \\/>/";
    preg_match($re, $result["body"], $matches);
    if($result) {
      return $result["body"];
    }
  }

  public function getUrl($url, $ref="http://nzb.to/login", $mode="GET", $args=array()) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cfg["cookie"]);
    curl_setopt($ch, CURLOPT_USERAGENT, $this->cfg["user-agent"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cfg["cookie"]);
    if($mode == "POST") {
      curl_setopt($ch, CURLOPT_POST, true);
      if(count($args)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
      }
    }

		curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
		curl_setopt($ch, CURLOPT_PROXYTYPE, 7);

    $result      = curl_exec($ch);
    $status      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $header = substr($result, 0, $header_size);
    $body   = substr($result, $header_size);
    $return = array("header" => $header,"body" => $body);
    if($status == 200) {
      return $return;
    } else {
      return false;
    }
  }

  public function downloadNZB($nid) {
    $result = $this->getURL($this->baseURL . '/download.php?nid='.$nid);
    if($result) {
      return $result;
    }
  }

  public function converterFileSize($size) {
    $s = explode(" ", $size);
    switch($s[1]) {
      case 'KB': $size = $s[0] * 1024; break;
      case 'MB': $size = ($s[0] * 1024) * 1024; break;
      case 'GB': $size = (($s[0] * 1024) * 1024) * 1024; break;
    }
    return round($size);
  }

  public function appendPassword($nzbcontent, $nzbpass){
      $cur_nzb_pass = 0;
      $mynzb = simplexml_load_string($nzbcontent);

      if ($mynzb->head) {
          foreach ($mynzb->head->meta as $key) {
              if ($key->attributes() == "password" && $key == $cur_nzb_pass) {
                  $cur_nzb_pass = $key;
              }
              if ($key->attributes() == "title") {
                  $title = $key;
              }
          }
      } else {
          $mynzb->addChild("head");
      }


      if (!$cur_nzb_pass && $nzbpass) {
          $new_nzb = $mynzb->head->addChild("meta", $nzbpass);
          $new_nzb->addAttribute("type", "password");
      }

      return $mynzb->asXML();
  }
//anpassen für multicat suche
  public function search($term="overview", $multicat="5000,5045") {
    //if more than one category given split into array and sort
    //$multicat = "5000,5030,5045";
      $catarr = explode(',', $multicat);
      sort($catarr);


    //final vor for schleife deklarieren um mehrere suchen im array zu speichern
    $final= array();
    // counter for normalizeTitle()
    $counter=1;
    foreach($catarr as $catID){
        
      
        $url = $this->baseURL . "?p=list&cat=13";
        switch ($catID) {
          case 2000:
            //Movies
            $url = $this->baseURL . "?p=list&cat=9";
            break;
           case 2045:
            //UHD Movies
            $url = $this->baseURL .  "?p=list&cat=9&sa_Video-Format=134217728";
            break;
          case 2050:
            //3D Movies
            $url = $this->baseURL .  "?p=list&cat=9&sa_Video-Format=67108864";
            break;
          case 2060:
            //Bluray Movies
            $url = $this->baseURL . "?p=list&cat=9&sa_Video-Format=458800";
            break;
          case 2070:
            //X265
            $url = $this->baseURL . "?p=list&cat=9&sa_Video-Format=268435456";
            break;
          case 2080:
            //MP4 TVCAP
            $url = $this->baseURL . "?p=list&cat=9&sa_Video-Format=209715";
            break;
          case 3000:
            //Audiosearch 
            $url = $this->baseURL . "?p=list&cat=10";
            break;
          case 3010:
            //MP3 
            $url = $this->baseURL . "?p=list&cat=10&sa_Audio-Format=4";
            break;
          case 3030: 
            //Audiobooks
            $url = $this->baseURL . "?p=list&cat=4&sa_Book-Typ=2";
            break;
          case 3040:
            //FLAC
            $url = $this->baseURL . "?p=list&cat=10&sa_Audio-Format=256";
            break;            
          case 5000:
            //TV
            $url = $this->baseURL . "?p=list&cat=13";
            break;
          case 5030:
            //TV Series
            $url = $this->baseURL . "?p=list&cat=13&sa_Video-Genre=3221225407";
            break;
          case 5045:
            // UHD TV Series
            $url = $this->baseURL . "?p=list&cat=13&sa_Video-Format=134217728";
            break;
          case 5080:
            //TV Doku
            $url = $this->baseURL . "?p=list&cat=13&sa_Video-Genre=536870976";
            break;
            // search everything!
          default:
            $url = $this->baseURL . "?p=list";
            break;
        }
        if($term && $term != "overview") {
          $url = $url . '&q=' . urlencode($term);
        }
    
        $result = $this->getUrl($url);
        if($result) {
          $html = str_get_html($result["body"]);
          $table = $html->find('.dataTabular');
          if($table && count($table)) {
            $table = $html->find('.dataTabular')[0];
          } else {
            if (!$multi){
                return $final;
                }
           break;
          }
          if(count($table) > 0) {
            $tbody = $table->find('tbody[id*=tbody-]');
    
            foreach($tbody as $element) {
              $current = array();
              $tr = $element->find('tr');
    
              $pid   = str_replace("tbody-","",$element->getAttribute('id'));
              $current["guid"] = str_replace("tbody-","",$element->getAttribute('id'));
    
              $title = $tr[0]->find('.fleft a');
              $title = normalizeTitle($title[0]->plaintext,$counter, $catID);
              $current["searchname"] = $title;
              $counter++;
    
              $poster= $tr[0]->find('.fleft a');
              $poster= trim($poster[1]->plaintext);
              $current["fromname"] = $poster;
    
              $datum = $tr[0]->find('.final span',0)->getAttribute('title');
              $datum = str_replace("Genaues Datum/Zeit: ", "", $datum);
              setlocale(LC_TIME, "en_US");
              $datum = strftime("%a, %d %b %Y %T +0200",strtotime($datum));
              $current["adddate"]  = $datum;
    
              $size  = $tr[1]->find('.fileSize');
              $size  = $size[0]->plaintext;
              $size  = str_replace("(","", $size);
              $size  = str_replace(")","", $size);
              $size  = $this->converterFileSize($size);
              $current["size"] = $size;
    
              $category   = $tr[0]->children(1);
              $category   = $category->find('a');
              $category   = $category[0]->plaintext;
              $current["category_name"] = $category;
    
              //set extended data
              $current["totalpart"] = "50";
              // $current["poster"] = "nzbto@nzb.to";
              $current["prematch"] = "0";
              $current["grabs"] = "0";
              $current["comments"] = "0";
              $current["passwordstatus"] = "0";
              $current["group_name"] = "";
              $current["category"] = $this->catIDMap[$category];
              $current["category"] = $catID;
              $current["rageID"] = -1;
              $current["imdbID"] = "";
              $current["tvtitle"] = "";
              $current["tvairdate"] = "";
              $current["season"] = "";
              $current["season"] = "";
              $current["episode"] = "";
              $current["postdate"] = $datum;
              if (isMatch($title,$catID,$term)) {
                array_push($final, $current);
              }
              
            }
        }
      }
    }
    return $final;
  }


}
?>
