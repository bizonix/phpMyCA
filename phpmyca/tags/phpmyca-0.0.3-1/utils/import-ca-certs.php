#!/usr/bin/php
<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
require('/etc/phpmyca/phpmyca.php');

/**
 * Get help
 * @return void
 */
function getHelp() {
	global $argv;
	return $argv[0] . ' dirname';
	}

/**
 * Return array of filenames to import
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
 * Parse PEM encoded certificate from specified file.
 * @param string $fileName
 * @return mixed
 */
function parseCertificate($fileName=null) {
	global $_WA;
	if (!is_string($fileName) or !is_file($fileName)) { return false; }
	if (!is_readable($fileName)) { return false; }
	$fh = fopen($fileName,'rb');
	if ($fh === false) { return false; }
	$txt = fread($fh,filesize($fileName));
	fclose($fh);
	return $_WA->html->extractPemBlock($txt,'CERTIFICATE');
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

$_WA->moduleRequired('html,ca');
echo 'located ' . count($allFiles) . ' candidate files' . "\n";

// Track totals
$certFiles     = array();
$dupeCerts     = array();
$nonCertFiles  = array();
$noIssuers     = array();
$errorCerts    = array();
$importedCerts = array();
$multiIssuers  = array();

// Locate valid certs
foreach($allFiles as $file) {
	$pem = parseCertificate($file);
	if (is_string($pem)) {
		$certFiles[] = array('pem' => $pem, 'file' => $file);
		} else {
		$nonCertFiles[] = $file;
		}
	}
if (count($certFiles) < 1) {
	die('no valid certificates located' . "\n");
	}
echo 'attempting to import ' . count($certFiles) . ' certificates' . "\n";

// Loop through and do the deed...
while(count($certFiles) > 0) {
	$cert = array_shift($certFiles);
	echo $cert['file'] . "\n";
	$rc = $_WA->actionCaImport($cert['pem']);
	if ($rc === true) {
		$importedCerts[] = $cert;
		continue;
		}
	// what kind of error are we talking about here?
	$pos = strpos($rc,'The specified cert is not a CA certificate');
	if (!($pos === false)) { continue; }
	$pos = strpos($rc,'SQL ERROR: Duplicate entry');
	if (!($pos === false)) {
		$dupeCerts[] = $cert['file'];
		continue;
		}
	$pos = strpos($rc,'The CA cert that signed this certificate');
	if (!($pos === false)) {
		$noIssuers[] = $cert['file'];
		continue;
		}
	$pos = strpos($rc,'multiple possible signers exist');
	if (!($pos === false)) {
		$multiIssuers[] = $cert['file'];
		continue;
		}
	$cert['error'] = $rc;
	$errorCerts[] = $cert;
	}

echo 'imported:      ' . count($importedCerts) . "\n";
echo 'duplicates:    ' . count($dupeCerts) . "\n";
echo 'no CA:         ' . count($noIssuers) . "\n";
echo 'multi signers: ' . count($multiIssuers) . "\n";
echo 'errors:        ' . count($errorCerts) . "\n";

if (count($noIssuers) > 0) {
	echo "\ncertificates with no issuer:\n";
	echo implode("\n",$noIssuers) . "\n";
	}

if (count($multiIssuers) > 0) {
	echo "\ncertificates with multiple possible signers:\n";
	echo implode("\n",$multiIssuers) . "\n";
	}

if (count($errorCerts) > 0) {
	echo "\n";
	foreach($errorCerts as $cert) {
		echo $cert['file'] . "\n";
		echo $cert['error'] . "\n";
		}
	}
?>
