<?
/**
 * phpmyca Server Certificate handling class
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

require(WEBAPP_API . '/db/dbo.server.php');

class phpmycaServerCert extends phpmycaDboServer {

var $actionQsExport    = false;
var $actionQsExportAll = false;
var $actionQsImport    = false;

/**
 * Constructor
 */
public function __construct() {
	parent::__construct();
	// Set searchable properties
	$p = array('CommonName','CountryName','CreateDate','Description',
	'EmailAddress','FingerprintMD5','FingerprintSHA1','Id','LocalityName',
	'OrgName','OrgUnitName','ParentId','RevokeDate','SerialNumber','StateName',
	'ValidFrom','ValidTo');
	foreach($p as $prop) { $this->addSearchProperty($prop); }
	// default search property
	$this->setSearchPropertyDefault('CommonName');
	// default search order
	$this->setSearchOrderDefault('CommonName');
	// set action strings
	if (defined('WA_ACTION_SERVER_ADD')) {
		$this->actionQsAdd = WA_ACTION_SERVER_ADD;
		}
	if (defined('WA_ACTION_SERVER_EDIT')) {
		$this->actionQsEdit = WA_ACTION_SERVER_EDIT;
		}
	if (defined('WA_ACTION_SERVER_EXPORT')) {
		$this->actionQsExport = WA_ACTION_SERVER_EXPORT;
		}
	if (defined('WA_ACTION_SERVER_EXPORT_ALL')) {
		$this->actionQsExportAll = WA_ACTION_SERVER_EXPORT_ALL;
		}
	if (defined('WA_ACTION_SERVER_IMPORT')) {
		$this->actionQsImport = WA_ACTION_SERVER_IMPORT;
		}
	if (defined('WA_ACTION_SERVER_LIST')) {
		$this->actionQsList = WA_ACTION_SERVER_LIST;
		}
	if (defined('WA_ACTION_SERVER_VIEW')) {
		$this->actionQsView = WA_ACTION_SERVER_VIEW;
		}
	// set properties when adding server certs
	$p = array('Certificate','CommonName','CountryName','CSR','Description',
	'EmailAddress','LocalityName','OrgName','OrgUnitName','ParentId',
	'PrivateKey','StateName','ValidFrom','ValidTo');
	$this->setPropertiesAdd($p);
	// set properties when editing server certs
	$this->setPropertiesEdit(array('Description'));
	// set properties when listing server certs
	$p = array('CommonName','OrgName','ValidTo');
	$this->setPropertiesList($p);
	// set properties when viewing ca certs (all of them)
	$this->setPropertiesView($this->getPropertyList());
	}

/**
 * Does a parsed cert meet import requirements?
 * @param array $parsedCert (returned by phpmycaCert::parseCert())
 * @return bool true on success
 * @return string error message on failures
 */
public function meetsImportRequirements(&$cert=null) {
	if (!is_array($cert)) { return 'is not an array'; }
	// top level keys that must be set...
	$keys = array('certificate','signature','fingerprints');
	foreach($keys as $key) {
		if (!array_key_exists($key,$cert)) {
			return 'missing attribute: ' . $key;
			}
		}
	// top level certificate keys that must be set
	$keys = array('version','serialNumber','validity','subject');
	foreach($keys as $key) {
		if (!array_key_exists($key,$cert['certificate'])) {
			return 'missing certificate attribute: ' . $key;
			}
		}
	// required fingerprints
	$fp = $cert['fingerprints'];
	if (!array_key_exists('md5',$fp))  { return 'md5 fingerprint is missing';  }
	if (!array_key_exists('sha1',$fp)) { return 'sha1 fingerprint is missing'; }
	if (strlen($fp['md5']) !== 32)     { return 'md5 fingerprint is invalid';  }
	if (strlen($fp['sha1']) !== 40)    { return 'sha1 fingerprint is invalid'; }
	// validity
	$keys = array('notbefore','notafter');
	foreach($keys as $key) {
		if (!array_key_exists($key,$cert['certificate']['validity'])) {
			return 'validity attribute missing: ' . $key;
			}
		if (!is_numeric($cert['certificate']['validity'][$key])) {
			return 'attribute not numeric: ' . $key;
			}
		}
	// subject
	if (!array_key_exists('CommonName',$cert['certificate']['subject'])) {
		return 'commonName not set';
		}
	$cn = $cert['certificate']['subject']['CommonName'];
	if (!is_array($cn) or count($cn) < 1) { return 'commonName not set'; }
	if (!is_string($cn[0]) or strlen($cn[0]) < 1) {
		return 'commonName not set';
		}
	$sig = $cert['signature'];
	if (!is_string($sig) or strlen($sig) < 1) {
		return 'signature is missing';
		}
	return true;
	}

}
?>
