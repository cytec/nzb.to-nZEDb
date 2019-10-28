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
$regex = false;
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
      case 'music':
        $action = "audio";
        $template = "apiresult.tpl";
        break;
      case 'audio':
        $action = "audio";
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
                $fname = preg_replace('/\`|\~|\!|\@|\#|\$|\%|\^|\&|\*|\(|\)|\+|\=|\[|\{|\]|\}|\||\\|\'|\<|\,|\>|\?|\/|\"|\;|\:|\_/', '', $fname);
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
function removeGarbage($garbage){
  $noshit = preg_replace('/\`|\~|\!|\@|\#|\$|\%|\^|\*|\(|\)|\+|\=|\[|\{|\]|\}|\||\\|\'|\<|\,|\>|\?|\/|\"|\;|\:/', '', $garbage);
  $noshit = trim($noshit);
  $noshit = preg_replace('/(\s+-\s+)|(\.+-\.+)/', '-', $noshit);
  $noshit = preg_replace('/\s+/','.',$noshit);
  $noshit = str_replace("-.", "-", $noshit);
  $noshit = str_replace(".-", "-", $noshit);
  $noshit = str_replace("..", ".", $noshit);
  $noshit = rtrim($noshit, '.');
  
  return $noshit;
}
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
$title=false;
$origsearch=false;

function getTVRAGEdata($rid) {
  if (!$title && !$origsearch) {
    global $logger;
    # nur solang wir noch keinen title haben
    if(!isset($_GET["imdbid"])){

      $tv = new TVMaze();
      

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
          $logger->log("TVMAZE did not return result, fallback to original searchquery : ".$rid."und ".$imdbid );
          }  
    }
    if(isset($_GET["imdbid"])){
        $logger->log("Setting imdbid for tvsearch");
        $imdbid="tt";
        $imdbid.=$_GET["imdbid"];
        }
    if($imdbid){
         $logger->logWithArray("TVMAZE returned: ", "INFO", array('imdbid'=>$imdbid, 'id' => $rid ?? 'keine id gesetzt'));
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
            global $regex; //"/".$regex."[\.\-]S(\d+)[\.\-]?(E(\d+))?([\.\-])/i";
            $regtit = removeGarbage($title);
            $regex = "/^".$regtit.".*?[\.\-]S(\d+)[\.\-]?(E(\d+))?([\.\-])/i";
          if(isset($_GET["season"])) {
            $season=$_GET["season"];
            $logger->log("Setze season: " .$season);
        
            if(strlen($season) == 1){

              $regex="/^".$regtit.".*?[\.\-]S(0".$season.")[\.\-]?(E(\d+))?([\.\-])/i";
              $title.= " S0".$season;

            }
              if(strlen($season) == 2){ 
              $title.= " S".$_GET["season"].")";
              $regex="/^".$regtit.".*?[\.\-]S(".$season.")[\.\-]?(E(\d+))?([\.\-])/i";
            }
          
              if(isset($_GET["ep"])){
              $logger->log("Setze Episode: " .$_GET["ep"]);
              $ep = $_GET["ep"];
              if(strlen($ep)==1){
                $title.="E0". $ep;
                if(strlen($season) == 1){$regex="/^".$regtit.".*?[\.\-]S(0".$season.")[\.\-]?(E(0".$ep."))?([\.\-])/i";}
                if(strlen($season) == 2){$regex="/^".$regtit.".*?[\.\-]S(".$season.")[\.\-]?(E(0".$ep."))?([\.\-])/i";}
              }
              else{
                $title.="E". $ep;
                if(strlen($season) == 1){$regex="/^".$regtit.".*?[\.\-]S(0".$season.")[\.\-]?(E(0".$ep."))?([\.\-])/i";}
                if(strlen($season) == 2){$regex="/^".$regtit.".*?[\.\-]S(".$season.")[\.\-]?(E(0".$ep."))?([\.\-])/i";}

             }
        }
    }
  }
    
    $title = removeGarbage($title);
    $logger->log("Final Search Term: " . $title . "with Regex" .$regex);
    return $title;
  }
         $origsearch= trim($rid);
         return $origsearch;
    }
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
  //prio to search per imdbid only do other searches if imdb not set
  if(isset($_GET["imdbid"]) && !$title){ $term = getTVRAGEData($_GET["imdbid"]) ?? $term;}
  if(isset($_GET["q"]) && !isset($_GET["imdbid"]) && !$title){$term = getTVRAGEdata($_GET["q"] ?? $term);}
  if(isset($_GET["rid"]) && !isset($_GET["imdbid"]) && !$title){$term = getTVRAGEdata($_GET["rid"] ?? $term);}
  if(isset($_GET["tvdbid"]) && !isset($_GET["imdbid"]) && !$title){$term = getTVRAGEdata($_GET["tvdbid"] ?? $term);}
  if(isset($_GET["tvmazeid"]) && !isset($_GET["imdbid"]) && !$title){$term = getTVRAGEdata($_GET["tvmazeid"] ?? $term);}
  
  $cat = $cat ?? 5000;
  $logger->log("We are looking for a TV Show");
} elseif ($action == "movie") {
  $template = "apiresult.tpl";
  $cat = $cat ?? 2000;
  if(isset($_GET["imdbid"]) && !$title){ $term = getIMDBData($_GET["imdbid"]) ?? $term;}
  if(isset($_GET["q"]) && !isset($_GET["imdbid"]) && !$title){$term = isset($_GET["q"]) ? $_GET["q"] : "overview";}
  if(isset($_GET["tmdbid"]) && !isset($_GET["imdbid"]) && !$title){ $term = isset($_GET["tmdbid"]) ? getTMDBData($_GET["tmdbid"]) : $term;}
  $logger->log("We are looking for a Movie");

} elseif ($action == "audio") {
  $template = "apiresult.tpl";
  $cat = $cat ?? 3000;
  if(!isset($_GET["artist"]) && !isset($_GET["album"])){
    $term = isset($_GET["q"]) ? $_GET["q"] : "overview";
    $logger->log("We are doing a audio search with catID " .$cat. " and term: ".$term);
  }
  if(isset($_GET["artist"]) && isset($_GET["album"])){
    $term=$_GET["artist"];
    $term.=" ".$_GET["album"];
    $logger->log("We are doing a Music-search with catID " .$cat. " and term: ".$term );
    }
} elseif ($action == "search") {
  $template = "apiresult.tpl";
  $cat = $cat ?? 1000;
  $term = isset($_GET["q"]) ? $_GET["q"] : "overview";
  $term = isset($_GET["imdbid"]) ? getIMDBData($_GET["imdbid"]) : $term;
  $logger->log("We are doing a global search");
}
//check and deliver from cache if found!

$cachename = sprintf("%s-%s-%s", $action, $term, $cat);

$logger->logWithArray("Request: ", "DEBUG", $_GET);
$logger->log("Cachename: " . $cachename, "DEBUG");

$cache->check($cachename, $apikey);
//else login and search nzb.to
$xx = $nzbto->login($user, $pass);
$rel = $nzbto->search($term, $cat);
//releases checke
function normalizeTitle($tit,$counter,$cat) {
  global $logger; 
    $nopass = $tit;
    $nospace = false;
    $tmpass = false;
    
    if(preg_match('/{{?(.*)}?/', $nopass, $matches)){
        $nopass = preg_replace("/{{?(.*)}?/", '', $nopass);
        $tmpass = str_replace(' ', '', $matches[0]);
    }
    $nospace = removeGarbage($nopass);
    $pattern = '/^\d{2}.' .str_replace(" ", ".", trim($nospace)). '/i';
    $nospace = preg_replace($pattern, str_replace(" ", ".", trim($nospace)), $nospace);


    if(!$tmpass){
      $logger->log("Rls #". $counter. " : " .$nospace . " <-- normalized Title - Cat.id" .$cat);
      
    }
    else{
      $logger->log("Rls #". $counter. " : " .$nospace . " <-- normalized Title,Stripped Password:".$tmpass);
     
  }
return $nospace;
}

function isMatch($rls, $cat, $term){
  global $logger;
  global $regex;
  
  //audio no filtering yet 
  if(substr($cat, 0, 1)==3 && trim($term) != "overview") {
    return true;
  }
  //Movies quiete accurate already 
  if(substr($cat, 0, 1)==2 && trim($term) != "overview") {
    $expr = "/.*" . preg_replace('/\s/','.',$term) . "(\s|\.)?(\d{4})?.*$/i";
    if(!preg_match($expr, $rls)){
      $logger->logWithArray("regex doesn't match term-MOV", "DEBUG", array("REGEX" => $expr, "RLS" => $rls));
      return false;
    }
    return true;
  }

  //TV damn accurate now
  if(substr($cat, 0, 1)==5 && trim($term) != "overview"){
    
    if(!preg_match($regex, $rls)){
      $logger->logWithArray("regex doesn't match term-TV", "DEBUG", array("regex" => $regex, "term" => $term, "cat" => $cat));

      return false;
    }
    return true;
  }
  return true;
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
