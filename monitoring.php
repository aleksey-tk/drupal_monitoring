<?php
/**
 * @file
 * This is monitoring page for Drupal installation. (6 and 7 versions are supported)
 *
 * This code will test:
 * - settings.php file
 * - mysql connection (using settings.php configuration)
 * - syslog writing capabilities
 * - file system writing capabilities
 * 
 * This script should be in the root directory of Drupal installation.
 *
 * Generally speaking, this script is ugly, contains repetitive code and will fail if webserver is not running. But it does its job. 
 *
 * The big plan is to rewrite this script to be more modular, but stille clean and simple, with security in mind.
 *
 */

// Error display should be disabled
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
define('KO_RESULT', 'KO');

// Separator 
define('SEPARATOR', '<br />');

// Headers to ensure proper html output and force encoding
header('Cache-Control: max-age=0');
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time()));
header('content-type: text/html; charset=utf-8');
print '<!DOCTYPE html>'."\n";
print '<html>';
print '<head>';
print '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
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

// Testing MySQL connection using Drupal settings (we assume that bootstrap configuration is OK)
$timestart = microtime(true);
print "Database connection (MySQL): ";
if (file_exists(DRUPAL_SETTINGS)) { require_once(DRUPAL_SETTINGS); }
if (!mysql_connect($databases['default']['default']['host'] . ':' . $databases['default']['default']['port'], $databases['default']['default']['username'], $databases['default']['default']['password'])) {
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
print "Syslog writing capabilities (syslog): ";
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
print "File system writing capabilities: ";
if ($test = tempnam(DRUPAL_ROOT . '/sites/default/files/', 'status_check_')) {
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