#!/usr/bin/php
<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
/**
 * Get help
 * @return void
 */
function getHelp() {
	global $argv;
	return $argv[0] . ' dirname';
	}

/**
 * Convert specified file from DER to PEM format
 * @param string $srcFile
 * @return bool
 */
function der2Pem($srcFile=null) {
	if (!is_file($srcFile) or !is_readable($srcFile)) { return false; }
	$dstFile = $srcFile . '.pem';
	$fh = fopen($srcFile,'rb');
	if ($fh === false) { return false; }
	$der = fread($fh,filesize($srcFile));
	fclose($fh);
	if ($der === false) { return false; }
	$out = '-----BEGIN CERTIFICATE-----' . "\n"
	. wordwrap(base64_encode($der),64,"\n",true) . "\n"
	. '-----END CERTIFICATE-----' . "\n";
	$fh = fopen($dstFile,'wb');
	if ($fh === false) { return false; }
	$rc = fwrite($fh,$out);
	fclose($fh);
	return ($rc === false) ? false : true;
	}

/**
 * Return array of filenames to convert
 * @param string $dirName
 * @return mixed
 */
function getFileList($dirName=null) {
	if (!is_string($dirName))   { return false; }
	if (!is_dir($dirName))      { return false; }
	if (!is_readable($dirName)) { return false; }
	clearstatcache();
	$ret_ar = array();
	if (!$dh = opendir($dirName)) { return false; }
	while(false !== ($file = readdir($dh))) {
		if ($file == '..' or $file == '.') { continue; }
		$cur_file = $dirName . '/' . $file;
		if (is_dir($cur_file)) {
			$tmp_ar = getFileList($cur_file);
			if (is_array($tmp_ar)) {
				$ret_ar = array_merge($ret_ar,$tmp_ar);
				}
			}
		$ret_ar[] = $cur_file;
		}
	closedir($dh);
	natcasesort($ret_ar);
	return $ret_ar;
	}

/**
 * Main Flow Starts Here
 */
if ($argc !== 2)       { die(getHelp() . "\n"); }
if (!is_dir($argv[1])) { die(getHelp() . "\n"); }
$dir = $argv[1];

$allFiles = getFileList($dir);
if (!is_array($allFiles) or count($allFiles) < 1) {
	die('no files located' . "\n");
	}

// tracking
$convertedFiles = array();
$errorFiles     = array();

while(count($allFiles) > 0) {
	$file = array_shift($allFiles);
	$rc = der2Pem($file);
	if ($rc === true) {
		$convertedFiles[] = $file;
		} else {
		$errorFiles[] = $file;
		}
	}

echo 'converted: ' . count($convertedFiles) . "\n";
echo 'errors:    ' . count($errorFiles) . "\n";

if (count($errorFiles) > 0) {
	echo "\nErrors during conversion:\n";
	echo implode("\n",$errorFiles) . "\n";
	}
?>
