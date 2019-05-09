<?php

if (!file_exists('./inc/config.inc.php')) {
  die('Please copy httpdocs/inc/config.inc.php.sample to httpdocs/inc/config.inc.php and change settings');
}

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

//fix for search in multiple categories! if this is selected only use the main category
$cat = isset($_GET["cat"]) ? $_GET["cat"] : false;
$tmpcat = explode("," , $cat);
if(count($tmpcat) > 1) {
  $cat = substr($tmpcat[0], 0, 1) . "000";
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
             	 
		  $password = trim($matches[1]);
		  
		 if (preg_match('/(.*)}}_{{/', $password, $pmatch)) {
	    		$password = preg_replace('/(.*)}}_{{/', '', $password);
	   	 }
          }
          if (preg_match('/filename="?(.*)"/', $result["header"], $matches)) {
            	
		  $fname = $matches[1];
          } 
		//check for {{password}} and empty space already in filename to prevent {{password}}_password error 
	  if (preg_match('/{{?(.*)}}/', $fname, $fmatch)) {
                
                $fname=preg_replace('/{{?(.*)}}/', '', $fname);	
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
	    $nzbcontent = $nzbto->appendPassword($nzbcontent, trim($password));
          }
          if($fname) {
            $logger->log("release downloaded: " . $fname);
            $downloadlog = new Logger("download.txt", null);
            $downloadlog->logWithArray("release downloaded", "INFO", array("user" => $uid, "fname" => $fname));
            file_put_contents(NZB_DIR . $fname , $nzbcontent);
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
  $logger->log("Final title: " . $title);
  return $title;
}


$term = "overview";
if($action == "tv") {
  $template = "apiresult.tpl";
  $term = isset($_GET["q"]) ? $_GET["q"] : "overview";
  $cat = ($cat != false) ? $cat : 5000;
  $logger->log("We are looking for a TV Show");
} elseif ($action == "movie") {
  $template = "apiresult.tpl";

  $cat = ($cat != false) ? $cat : 2000;

  $term = isset($_GET["q"]) ? $_GET["q"] : "overview";
  $term = isset($_GET["imdbid"]) ? getIMDBData($_GET["imdbid"]) : $term;

  $logger->log("We are looking for a Movie");
} elseif ($action == "search") {
  $template = "apiresult.tpl";

  $cat = ($cat != false) ? $cat : 1000;
  $term = isset($_GET["q"]) ? $_GET["q"] : "overview";

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

//for movies, check if movie start's with name from themoviedb
if(substr($cat, 0, 1) == 2 && trim($term) != "overview" || isset($_GET["imdbid"])) {
  $replace = array(" - ", " ", "_", ",", "!");
  $tosearch = $term;
  foreach ($replace as $repl) {
    $tosearch = str_replace($repl, ".", $tosearch);
  }
  foreach ($rel as $key => $value) {
    $expr = "/^" . $tosearch . "(\s|\.)(\d{4})?.*$/i";
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
