<?php
/**
 * @file
 * This is monitoring page for Drupal 7 installation.
 *
 * This code will test:
 * - settings.php file
 * - mysql connection (using settings.php configuration)
 * - syslog writing capabilities
 * - file system writing capabilities
 * - Memcached availability
 * 
 * This script should be in the root directory of Drupal installation (near index.php)
 *
 */

/**
 * Register our shutdown function so that no other shutdown functions run before this one.
 * This shutdown function calls exit(), immediately short-circuiting any other shutdown functions,
 * such as those registered by the devel.module for statistics.
 */
register_shutdown_function('status_shutdown');
function status_shutdown() {
  exit();
}

// Error display should be disabled. Enable it to debug this script.
ini_set('display_errors', 0);

// Root directory of Drupal installation.
define('DRUPAL_ROOT', getcwd());

// Drupal settings.php filepath. We assume that installation is not multisite enabled
define('DRUPAL_SETTINGS', DRUPAL_ROOT . '/sites/default/settings.php');

// OK indicator
define('OK_TEXT', 'OK');

// KO indicator
define('KO_TEXT', 'KO');

// OK result indicator
define('OK_RESULT', 'ALL OK');

// KO result indicator
define('KO_RESULT', 'SOME KO');

// Separator 
define('SEPARATOR', '<br />');

/**
 * Bootstrap Drupal. Full mode, takes some time.
 */
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// No cache, please
header('Cache-Control: max-age=0');
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time()));

// Forcing UTF-8 encoding
header('content-type: text/html; charset=utf-8');

print '<!DOCTYPE html>'."\n";
print '<html>';
print '<head>';
print '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';

// Page will be refreshed automatically every 300 seconds
print '<meta http-equiv="refresh" content="300">';
print '</head><body>';

// Variable to keep tracking amount of errors
$result = 0;

// Checking for Drupal settings.php
$timestart = microtime(true);
print "Configuration file exists (settings.php): ";
if (!file_exists(DRUPAL_SETTINGS)) {
  print KO_TEXT;
  $result++;
} 
else { 
  print OK_TEXT;
}
$timeend = microtime(true);
$time = $timeend - $timestart;
$time_result = (float) number_format($time, 3);
print ' (' . $time_result . 's)';

print SEPARATOR;

// Testing MySQL connection using Drupal settings
$timestart = microtime(true);
print "Database connections is working (MySQL): ";

// Querying for admin user in users table. Should always exist for installed Drupal.
$query = db_select('users', 'u')
  ->fields('u')
  ->condition('u.uid', 1, '=');
$result_query = $query->execute();
if (!$account = $result_query->fetchAssoc()) {
  print KO_TEXT;
  $result++;
} 
else { 
  print OK_TEXT;
}
$timeend = microtime(true);
$time = $timeend - $timestart;
$time_result = (float) number_format($time, 3);
print ' (' . $time_result . 's)';

print SEPARATOR;

// Testing syslog writing capabilities
$timestart = microtime(true);
print "Syslog is available (syslog): ";
if (!syslog(LOG_NOTICE, 'Status check. Supervision page.')) {
  print KO_TEXT;
  $result++;
} 
else { 
  print OK_TEXT;
}
$timeend = microtime(true);
$time = $timeend - $timestart;
$time_result = (float) number_format($time, 3);
print ' (' . $time_result . 's)';

print SEPARATOR;

// Testing file system writing capabilities
$timestart = microtime(true);
print "File system writable : ";
if ($test = tempnam(variable_get('file_directory_path', conf_path() . '/files'), 'status_check_')) {
  print OK_TEXT;
  // Deleting test file
  unlink($test);
} 
else { 
  print KO_TEXT;
  $result++;
}
$timeend = microtime(true);
$time = $timeend - $timestart;
$time_result = (float) number_format($time, 3);
print ' (' . $time_result . 's)';

print SEPARATOR;

print "Memcache: ";
$timestart = microtime(true);
$memcache_error = 0;
// Testing memcached
if (isset($conf['cache_backends']) && isset($conf['memcache_servers'])) {
  
  // Confirm that valid path to memcache.inc is defined
  $memcache_check = count(array_filter($conf['cache_backends'], function($path){
    return (strrpos($path, "memcache.inc") && file_exists($path));
  }));
  
  // Only continue if memcache is configured in the $conf array
  if ($memcache_check > 0) {
    // Select PECL memcache/memcached library to use
    $preferred = variable_get('memcache_extension', NULL);
    if (isset($preferred) && class_exists($preferred)) {
      $extension = $preferred;
    }
    // If no extension is set, default to Memcache.
    elseif (class_exists('Memcache')) {
      $extension = 'Memcache';
    }
    elseif (class_exists('Memcached')) {
      $extension = 'Memcached';
    }
    else {
      print KO_TEXT; print ' (No extension)';
      $result++; $memcache_error++;
    }
    
    // Test server connections
    if ($extension) {
      $memcache_errors = array();
      foreach ($conf['memcache_servers'] as $address => $bin) {
        list($ip, $port) = explode(':', $address);
        if ($extension == 'Memcache') {
          if (!memcache_connect($ip, $port)) {
             print KO_TEXT; print ' (Cannot connect to Memcache)';
             $result++; $memcache_error++;
          }
        }
        elseif ($extension == 'Memcached') {
          $m = new Memcached();
          $m->addServer($ip, $port);
          if ($m->getVersion() == FALSE) {
             print KO_TEXT; print ' (Cannot connect to Memcached)';
             $result++; $memcache_error++;
          }
        }
      }
    }
    if ($memcache_error == 0) {
      print OK_TEXT;
    }
  }
}
else {
  print KO_TEXT; print ' (Not configured)';
  $result++;
}
$timeend = microtime(true);
$time = $timeend - $timestart;
$time_result = (float) number_format($time, 3);
print ' (' . $time_result . 's)';

// Ending
print SEPARATOR . SEPARATOR;

// Result display
if ($result > 0) {
  print KO_RESULT;
}
else {
  print OK_RESULT;
}

print SEPARATOR;
print '</body></html>';

// Exit completely
exit();

?>