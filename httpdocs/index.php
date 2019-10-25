<?php

if (!file_exists('./inc/config.inc.php')) {
  die('Please copy httpdocs/inc/config.inc.php.sample to httpdocs/inc/config.inc.php and change settings');
}
require __DIR__ . '/inc/vendor/autoload.php';
include './inc/config.inc.php';
include INC_DIR . '/logger.php';
include INC_DIR . '/page.php';
include INC_DIR . '/nzbto.php';
include INC_DIR . '/security.php';
include INC_DIR . '/cache.php';




$page = new Page();
$logger = new Logger("nzbto2newznab.txt", null);
$cache = new Cache();
$hash = new Security();
// tvmaze class
use JPinkney\TVMaze\Client as TVMaze;

if(isset($_GET["page"]) && $_GET["page"] != "api" || !isset($_GET["page"])) {
  if($page->isPostBack() && $_POST["username"] != "" && $_POST["password"] != "")  {
    $key = $hash->encrypt_mcrypt(sprintf("%s-/-%s", trim($_POST["username"]), trim($_POST["password"])));
    $key = urlencode($key);
    $page->smarty->assign('key', $key);
    $page->content = $page->smarty->fetch("index.tpl");
    die($page->renderHTML());
  }
  $page->content = $page->smarty->fetch("index.tpl");
  die($page->renderHTML());
}

$apikey = isset($_GET["apikey"]) ? $_GET["apikey"] : false;

//fix for search in multiple categories! 

$cat = isset($_GET["cat"]) ? $_GET["cat"] : false;
 $tmpcat = explode("," , $cat);
if(count($tmpcat) > 1) {
  $multi = true;
} 

$extended = isset($_GET["extended"]) ? $_GET["extended"] : 0;

$uid = "";
$action = "";

$user = $uid;
$pass = "";

$logger->logWithArray("Request: ", "DEBUG", $_GET);
if($apikey){
  // urldecode apikey if its double encoded!
  if (strpos($apikey, "%")) {
    $apikey = urldecode($apikey);
  }
  if (preg_match("@^[a-zA-Z0-9%]*$@", $apikey)) {
    $apikey = urldecode($apikey);
  }
  $encrypt = $hash->decrypt_mcrypt($apikey);
  // die($encrypt);
  $arr = explode("-/-", $encrypt);
  if(count($arr) != 2) {
    $logger->logWithArray("unable to decrypt apikey expected -/- in decryted key ", "ERROR", array("apikey"=>$apikey));
		//Incorrect user credentials
    $page->showApiError(100);
  }
  $uid = $user = $arr[0];
  $pass = $arr[1];
  $logger->log("decrypt successfull username: " . $uid);
} else {
  $page->showApiError(200);
}

$apikey = urlencode($apikey);

$nzbto = new NZBTO($uid);
$action = "tv";
if (isset($_GET["t"])) {
    switch ($_GET["t"]) {
      case 'caps':
        $action = 'caps';
        $template = "apicaps.tpl";
        $page->content = $page->smarty->fetch($template);
        die($page->render());
        break;
      case 'tv':
        $action = "tv";
        $template = "apiresult.tpl";
        break;
      case 'search':
        $action = "search";
        $template = "apiresult.tpl";
        break;
      case 'movie':
        $action = "movie";
        $template = "apiresult.tpl";
        break;
      case 'tvsearch':
        $action = "tv";
        $template = "apiresult.tpl";
        break;
      case 'get':
        $nzbto->login($user, $pass);
        $result = $nzbto->downloadNZB($_GET["guid"]);
        if($result) {
	//change to check for empty whitespaces in passwords and strip them from name into the nzbheader
          $password = false;
          header('Content-type: application/x-nzb');
          if (preg_match('/{{?(.*)}}/', $result["header"], $matches)) {
            $trimit = $matches[1];
	    $password = trim($trimit);
		 if (preg_match('/(.*)}}_{{/', $password, $pmatch)) {
	    		$password = preg_replace('/(.*)}}_{{/', '', $password);
	   	 }
          }
          if (preg_match('/filename="?(.*)"/', $result["header"], $matches)) {
            $fname = $matches[1];
          } //check for {{password}} and empty space already in filename to prevent {{password}}_password error 
          if (preg_match('/{{?(.*)}/', $fname, $fmatch)) {
                
                $fname = preg_replace('/{{?(.*)}}/', '', $fname);
                $fname = preg_replace('/\`|\~|\!|\@|\#|\$|\%|\^|\&|\*|\(|\)|\+|\=|\[|\{|\]|\}|\||\\|\'|\<|\,|\>|\?|\/|\""|\;|\:\_/', '', $fname);
                $fname = preg_replace('/\s/', '.', $fname);	
                }
     
          //remove the category prefix shit!
          if(substr($fname, 0, 3) == "TV_") {
            $fname = substr($fname, 3, strlen($fname));
          }
          if(substr($fname, 0, 6) == "Filme_") {
            $fname = substr($fname, 6, strlen($fname));
          }

          header('Content-Disposition: attachment; filename="'.$fname.'"');
          $nzbcontent = $result["body"];
          if($password) {
               $nzbcontent = $nzbto->appendPassword($nzbcontent, trim(preg_replace('/\s|\s+/','',$password)));
          }
          if($fname) {
            $logger->log("release downloaded: " . $fname);
            $downloadlog = new Logger("download.txt", null);
            $downloadlog->logWithArray("release downloaded", "INFO", array("user" => $uid, "fname" => $fname, "password" => $password));
            file_put_contents(NZB_DIR . $fname, $nzbcontent);
          }
          die($nzbcontent);
        }
				//Incorrect user credentials
        die($page->showApiError(100));
        break;
      default:
        $action = false;
        $template = "apicaps.tpl";
        break;
    }
}

/*
 *  try to get German Title from themoviedb or omdb
 *  Original Title as Fallback
 *  needed for CouchPotato Search
 */
function getIMDBData($imdbid) {
  global $logger;
  $logger->log("Requesting Name from themoviedb");
  $url  = "http://api.themoviedb.org/3/movie/tt".$imdbid."?api_key=".TMDB_KEY."&append_to_response=alternative_titles&language=de";
  $url  = "http://api.themoviedb.org/3/movie/tt".$imdbid."?api_key=".TMDB_KEY."&language=de";
  $logger->log($url);
  $json = file_get_contents($url);
  $data = json_decode($json);
  $title= false;

  if(!$title) {
    $title= $data->{'title'};
  }
  $logger->logWithArray("Title returned from themoviedb: ", "INFO", array('imdbid'=>$imdbid, 'title' => $title));

  if(!$title) {
    $logger->log("no return from themoviedb, trying omdbapi now...");
    $url  = "http://www.omdbapi.com/?type=movie&i=tt".$imdbid;
    $json = file_get_contents($url);
    $data = json_decode($json);
    $title= $data->{'Title'};
  }
  $logger->log("Cutting title to remove german subtitle this gives us more results");
  $pattern = '/[:-]/';
  if(preg_match($pattern, $title, $matches)){
        $results = preg_split($pattern,$title);
        $title = trim($results[0]);
        }
  $logger->log("Final title: " . $title);
  return $title;
}
//tv id search
function getTVRAGEdata($rid) {
    $tv = new TVMaze();
    global $logger;
    if(isset($_GET["q"])){
            $logger->log("Requesting imdbid via from TVMAZE with Term: " .$rid);
            $tvmazeShow = $tv->TVMaze->singleSearch("$rid");
            $imdbid = $tvmazeShow[0]->externalIDs['imdb'];
            }
    if(isset($_GET["rid"])){
            $logger->log("Requesting imdbid via TVRAGEid from TVMAZE");
            $tvmazeShow = $tv->TVMaze->getShowBySiteID("tvrage",$rid);
            $imdbid = $tvmazeShow->externalIDs['imdb'];
            }
    if(isset($_GET["tvdbid"])){
            $logger->log("Requesting imdbid via TVDBid from TVMAZE");
            $tvmazeShow = $tv->TVMaze->getShowBySiteID("thetvdb",$rid);
            $imdbid = $tvmazeShow->externalIDs['imdb'];
            }
    if(isset($_GET["tvmazeid"])){
            $logger->log("Requesting imdbid via TVMAZEid from TVMAZE");
            $tvmazeShow = $tv->TVMaze->getShowByShowID($rid);
            $imdbid = $tvmazeShow[0]->externalIDs['imdb'];
            }
    if(!$imdbid){
        $logger->log("TVMAZE did not return result, fallback to original searchquery");
        
    }
    if(isset($_GET["imdbid"])){
        $logger->log("Setting imdbid for tvsearch");
        $imdbid="tt";
        $imdbid.=$_GET["imdbid"];
        }
    if($imdbid){
         $logger->logWithArray("TVMAZE returned: ", "INFO", array('imdbid'=>$imdbid, 'id' => $rid));
         $title=false;
         if(!$title){
            $IMDB = new IMDB('http://de.imdb.com/title/'.$imdbid); 
            if ($IMDB->isReady) {
            $title=$IMDB->getTitle();
            }
            $logger->logWithArray("Title returned from imdbTV: ", "INFO", array('imdbid'=>$imdbid, 'title' => $title));
          
            if(!$title) {
                $logger->log("no return from imdb, trying omdbapi now...");
                $url  = "http://www.omdbapi.com/?type=tv&i=tt".$imdbid;
                $json = file_get_contents($url);
                $data = json_decode($json);
                $title= $data->{'Title'};
            }// falls ein deutscher untertitel vorhanden ist ohne diesen suchen (gibt mehr passende results)
            $pattern = '/[:-]/';
            if(preg_match($pattern, $title, $matches)){
                 $results = preg_split($pattern,$title);
                 $title = trim($results[0]);//nur wenn per tvmaze gesucht als array.
                 
            }
           
            
    if (isset($_GET["season"])) {
        $logger->log("Setze season: " .$_GET["season"]);
        
        if(strlen($_GET["season"])==1){
            $title.= " S0".$_GET["season"];
            }
            else if(strlen($_GET["season"])== 2){ 
        $title.= " S".$_GET["season"];
        }
    }
        if(isset($_GET["ep"])){
            $logger->log("Setze Episode: " .$_GET["ep"]);
            $ep = $_GET["ep"];
            if(strlen($ep)==1){
                $title.="E0". $ep;
            }
            else{
                $title.="E". $ep;
            }
        }
    }
    
    $title=preg_replace('/\`|\~|\!|\@|\#|\$|\%|\^|\&|\*|\(|\)|\+|\=|\[|\{|\]|\}|\||\\|\'|\<|\,|\>|\?|\/|\""|\;|\:/', '', $title);
    $logger->log("Final Search Term: " . $title);
    return preg_replace('/\s/','.',$title);
  }
         $origsearch= trim($_GET["q"]);
         return $origsearch;
    }
  

//tmdbid search
function getTMDBData($tmdbid) {
  global $logger;
  $logger->log("Requesting Name from themoviedb");
  $url  = "http://api.themoviedb.org/3/movie/".$tmdbid."?api_key=".TMDB_KEY."&append_to_response=alternative_titles&language=de";
  $url  = "http://api.themoviedb.org/3/movie/".$tmdbid."?api_key=".TMDB_KEY."&language=de";
  $logger->log($url);
  $json = file_get_contents($url);
  $data = json_decode($json);
  $title= false;

  if(!$title) {
    $title= $data->{'title'};
  }
  $logger->logWithArray("Title returned from themoviedb: ", "INFO", array('imdbid'=>$imdbid, 'title' => $title));
  
  $logger->log("Final title: " . $title);
  return $title;
}

$term = "overview";
if($action == "tv") {
  $template = "apiresult.tpl";
  if(!isset($_GET["rid"])||!isset($_GET["season"])||!isset($_GET["imdbid"])||!isset($_GET["tvmazeid"])||!isset($_GET["tvdbid"]))
    {
    $term = isset($_GET["q"]) ? getTVRAGEdata($_GET["q"]) : $term;
    }
  // checking for tvrage search
  $term = isset($_GET["season"]) ? getTVRAGEdata($_GET["q"]) : $term;
  $term = isset($_GET["rid"]) ? getTVRAGEData($_GET["rid"]) : $term;
  $term = isset($_GET["tvdbid"]) ? getTVRAGEData($_GET["tvdbid"]) : $term;
  $term = isset($_GET["tvmazeid"]) ? getTVRAGEData($_GET["tvmazeid"]) : $term;
  $term = isset($_GET["imdbid"]) ? getTVRAGEData($_GET["imdbid"]) : $term;
  
  $cat = ($cat != false) ? $cat : 5000;
  $logger->log("We are looking for a TV Show");
} elseif ($action == "movie") {
  $template = "apiresult.tpl";

  $cat = ($cat != false) ? $cat : 2000;
  $term = isset($_GET["q"]) ? $_GET["q"] : "overview";
  $term = isset($_GET["imdbid"]) ? getIMDBData($_GET["imdbid"]) : $term;
  $term = isset($_GET["tmdbid"]) ? getTMDBData($_GET["tmdbid"]) : $term;

  

  $logger->log("We are looking for a Movie");
} elseif ($action == "search") {
  $template = "apiresult.tpl";
  $cat = ($cat != false) ? $cat : 1000;
  $term = isset($_GET["q"]) ? $_GET["q"] : "overview";
  $term = isset($_GET["imdbid"]) ? getIMDBData($_GET["imdbid"]) : $term;
  $logger->log("We are doing a global search");
}

//check and deliver from cache if found!
$cachename = sprintf("%s-%s", $action, $term);

$logger->logWithArray("Request: ", "DEBUG", $_GET);
$logger->log("Cachename: " . $cachename, "DEBUG");

$cache->check($cachename, $apikey);
//else login and search nzb.to
$xx = $nzbto->login($user, $pass);
$rel = $nzbto->search($term, $cat);
//releases checke
(int) $i = 0;
foreach ($rel as $key => $value){
            $nopass = $value["searchname"];
            $nospace = false;
            
            if(preg_match('/{{?(.*)}?/', $nopass, $matches)){
                $nopass = preg_replace("/{{?(.*)}?/", '', $nopass);
                $rel[$key]["searchname"]= $nopass;
                $matches[0] = str_replace(' ', '', $matches[0]);
                $logger->log("Stripped pw".$matches[0]." new rlsname:" .$value["searchname"]);
            }
            $nospace = preg_replace('/\`|\~|\!|\@|\#|\$|\%|\^|\&|\*|\(|\)|\+|\=|\[|\{|\]|\}|\||\\|\'|\<|\,|\>|\?|\/|\""|\;|\:/', '', $nopass);
          //  $logger->log("Stripped spaces new rlsname-1:" .$nospace);
            $nospace=preg_replace('/(\s?-\s)|(\.-\.)|(\s)/', '.', $nospace);
          //  $logger->log("Stripped spaces new rlsname-2:" .$nospace);
           // $nospace = preg_replace('/\s/', '.', $nospace);
            $nospace = str_replace("..", ".", $nospace);
            $pattern = '/^\d{2}.' .str_replace(" ", ".", trim($term)). '/i';
            //$logger->log("Checking for leading numbers with regex:" .$pattern);
            $nospace = preg_replace($pattern, str_replace(" ", ".", trim($term)), $nospace);
            
           // $logger->log("Stripped spaces new rlsname-3:" .$nospace);
            $rel[$key]["searchname"]= $nospace;
            $logger->log("Rls #". ++$i. " : " .$nospace . " Title normalized.");
            //unset($rel[$key]);
        
}
//for movies, check if movie start's with name from themoviedb
if(substr($cat, 0, 1) == 2 && trim($term) != "overview" || isset($_GET["imdbid"])) {

  
  foreach ($rel as $key => $value) {
   
    $expr = "/.*" . preg_replace('/\s/','.',$term) . "(\s|\.)(\d{4})?.*$/i";
    if(!preg_match($expr, $value["searchname"])){
      $logger->logWithArray("regex doesn't match term", "DEBUG", array("regex" => $expr, "term" => $value["searchname"]));
      unset($rel[$key]);
    };
  }
}
//for tvshows, check if tvshow start's with name from imdbakas
if(substr($cat, 0, 1) == 5 && trim($term) != "overview" | isset($_GET["rid"]) | isset($_GET["q"]) | isset($_GET["season"])) {
  $replace = array(" - ", "_", ",", "!");
  $tosearch = $term;
  foreach ($replace as $repl) {
    $tosearch = str_replace($repl, ".", $tosearch);
  }
  foreach ($rel as $key => $value) {
    $expr = "/^" . $tosearch . "(E\d{2}).*$|(\s\d{4}).*$|(S\d{2}).*$|(\sS\d{2}E\d{2}).*$|(.*STAFFEL\s\d*).*$|(.*SEASON\s\d*).*$/i";
    if(isset($_GET["ep"])&isset($_GET["season"])&isset($_GET["rid"])){
        $expr = "/^" . str_replace(" ",".*",$tosearch) . ".*$/i";
        }  
    if(!preg_match($expr, $value["searchname"])){
      $logger->logWithArray("regex doesn't match term", "DEBUG", array("regex" => $expr, "term" => $value["searchname"]));
      unset($rel[$key]);
    };
  }
}
$logger->log("Search returned " . count($rel) . " results", "DEBUG");

$page->smarty->assign('title', 'nzb.to ' . $action . ' -> ' . $term);
$page->smarty->assign('releases', $rel);
$page->smarty->assign('uid', $uid);
$page->smarty->assign('rsstoken', $apikey);
$page->smarty->assign('extended', $extended);
$page->smarty->assign('serverroot', API_BASE);

$page->content = $page->smarty->fetch($template);
//write content to cache file
$cache->write($cachename, $page->content, $apikey);
//show content
$page->render();
?>
