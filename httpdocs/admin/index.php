<?php
require_once '../inc/config.inc.php';

$logfile = isset($_GET["file"]) ? $_GET["file"] : "nzbto2newznab.txt";
$loglevel = isset($_GET["level"]) ? $_GET["level"] : ".*";


$logcontent = file(LOG_DIR . $logfile, FILE_IGNORE_NEW_LINES);
$logcontent = array_reverse($logcontent);

foreach ($logcontent as $key => $value) {
  if(!preg_match("/.*" . $loglevel . ":.*/", $value)) {
    unset($logcontent[$key]);
  }
}
?>
<ul>
  <li><a href="?file=upload.txt">Uploads</a></li>
  <li><a href="?file=download.txt">Downloads</a></li>
  <li><a href="?file=nzbto2newznab.txt">Main Log</a></li>
</ul>
<pre>
<?php foreach ($logcontent as $line) {
  echo $line . "\n";
}; ?>
</pre>
