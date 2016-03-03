<?php
// https://github.com/panique/php-long-polling/blob/master/server/server.php
/**
 * Infinite looping PHP long-poller.
 * NOTE: this is unauthenticated so put it behing e.g. htaccess and HTTPS
 */

// enable cors
cors();

dirPoller("data");

function dirPoller($datadir) {
  // hang for a maximum of 30 seconds
  $live_for = isset($_GET['live_for']) ? min((int)$_GET['live_for'], 180) : 30;
  // tell PHP it can timeout after 30 seconds
  set_time_limit($live_for);
  ini_set("max_execution_time", $live_for);
  // start the wait-poll loop
  while ($live_for--) {
    // if ajax request has send a timestamp, then $last_ajax_call = timestamp, else $last_ajax_call = null
    $last_ajax_call = isset($_GET['timestamp']) ? (int)$_GET['timestamp'] : null;
    // get timestamp of when file has been changed the last time
    $last_change_in_data_files = dirTimestamp($datadir);
    // if no timestamp delivered via ajax or data.txt has been changed SINCE last ajax timestamp
    if ($last_ajax_call == null || $last_change_in_data_files > $last_ajax_call) {
        // get content of data.txt
        $data = dirFiles($datadir);
        // put data.txt's content and timestamp of last data.txt change into array
        $result = array(
            'files' => $data,
            'timestamp' => $last_change_in_data_files
        );
        // encode to JSON, render the result (for AJAX)
        $json = json_encode($result);
        echo $json;
        // leave this loop step
        break;
    } else {
        // wait for 1 sec (not very sexy as this blocks the PHP/Apache process, but that's how it goes)
        sleep(1);
        continue;
    }
  }
}

// Open a directory, and read its contents
// http://www.w3schools.com/php/func_directory_readdir.asp
function dirFiles($datadir) {
  $files = array();
  if ($dh = opendir($datadir)){
    while (($filename = readdir($dh)) !== false){
      if (endsWith($filename, ".txt")) {
        $filepath = $datadir . "/" . $filename;
        $files[$filename] = file_get_contents($filepath);
      }
    }
    closedir($dh);
  }
  return $files;
}

function dirTimestamp($datadir) {
  // PHP caches file data, like requesting the size of a file, by default. clearstatcache() clears that cache
  clearstatcache();
  $timestamp = 0;
  if ($dh = opendir($datadir)){
    while (($filename = readdir($dh)) !== false){
      if (endsWith($filename, ".txt")) {
        $filepath = $datadir . "/" . $filename;
        $file_timestamp = filemtime($filepath);
        if ($file_timestamp > $timestamp) {
          $timestamp = $file_timestamp;
        }
      }
    }
    closedir($dh);
  }
  return $timestamp;
}


// test if a string ends with another string
// http://stackoverflow.com/a/619725/2131094
function endsWith($string, $test) {
  $strlen = strlen($string);
  $testlen = strlen($test);
  if ($testlen > $strlen) return false;
  return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
}

function cors() {
    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }
    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        exit(0);
    }
}
?>
