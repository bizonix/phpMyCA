<?
/**
 * phpmyca CA Certificate handling class
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

require(WEBAPP_API . '/db/dbo.ca.php');

class phpmycaCaCert extends phpmycaDboCa {

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
	'FingerprintMD5','FingerprintSHA1','Id','LocalityName','OrgName',
	'OrgUnitName','ParentId','RevokeDate','ValidFrom','ValidTo');
	foreach($p as $prop) { $this->addSearchProperty($prop); }
	// default search property
	$this->setSearchPropertyDefault('OrgName');
	// default search order
	$this->setSearchOrderDefault('OrgName');
	// set action strings
	if (defined('WA_ACTION_CA_ADD')) {
		$this->actionQsAdd = WA_ACTION_CA_ADD;
		}
	if (defined('WA_ACTION_CA_EDIT')) {
		$this->actionQsEdit = WA_ACTION_CA_EDIT;
		}
	if (defined('WA_ACTION_CA_EXPORT')) {
		$this->actionQsExport = WA_ACTION_CA_EXPORT;
		}
	if (defined('WA_ACTION_CA_EXPORT_ALL')) {
		$this->actionQsExportAll = WA_ACTION_CA_EXPORT_ALL;
		}
	if (defined('WA_ACTION_CA_IMPORT')) {
		$this->actionQsImport = WA_ACTION_CA_IMPORT;
		}
	if (defined('WA_ACTION_CA_LIST')) {
		$this->actionQsList = WA_ACTION_CA_LIST;
		}
	if (defined('WA_ACTION_CA_VIEW')) {
		$this->actionQsView = WA_ACTION_CA_VIEW;
		}
	// set properties when adding ca certs
	$p = array('Certificate','CommonName','CountryName','CSR','Description',
	'LocalityName','OrgName','OrgUnitName','ParentId','PrivateKey',
	'SerialNumber','ValidFrom','ValidTo');
	$this->setPropertiesAdd($p);
	// set properties when editing ca certs
	$this->setPropertiesEdit(array('Description'));
	// set properties when listing ca certs
	$p = array('OrgName','CommonName','ValidTo');
	$this->setPropertiesList($p);
	// set properties when viewing ca certs (all of them)
	$this->setPropertiesView($this->getPropertyList());
	}

/**
 * Get array containing CA IDs of CA chain starting with specified ca id.
 * @param int $caId (starting ca id in chain)
 * @return mixed
 */
public function getCaChainIds($startId=null) {
	if (!is_numeric($startId) or $startId < 1) { return false; }
	$ret_ar = array($startId);
	$pid = $this->getIssuerCaId($startId);
	if (!is_numeric($pid)) { return false; }
	if ($pid > 0) {
		$ret_ar = array_merge($ret_ar,$this->getCaChainIds($pid));
		}
	return $ret_ar;
	}

/**
 * Get the issuer CA id of specified CA cert id.
 * @param int $caId
 * @return mixed
 */
public function getIssuerCaId($caId=null) {
	if (!is_numeric($caId) or $caId < 1) { return false; }
	$this->searchReset();
	$this->setSearchSelect('ParentId');
	$this->setSearchFilter('Id',$caId);
	$this->setSearchLimit(1);
	$rows = $this->query();
	if (!is_array($rows) or count($rows) < 1) { return false; }
	return $rows[0]['ParentId'];
	}

/**
 * Retrieve the last serial number issued by specified ca
 * @param int $caId
 * @return bool false on failures
 * @return int on success
 */
public function getLastSerialIssued($caId=null) {
	if (!is_numeric($caId) or $caId < 1) { return false; }
	$this->searchReset();
	$this->setSearchSelect('SerialLastIssued');
	$this->setSearchLimit(1);
	$this->setSearchFilter('Id',$caId);
	$rows = $this->query();
	if (!is_array($rows) or count($rows) < 1) { return false; }
	return $rows[0]['SerialLastIssued'];
	}

/**
 * Does a parsed cert meet import requirements?
 * @param array $parsedCert (returned by phpmycaCert::parseCert())
 * @return bool true on success
 * @return string error message on failures
 */
public function meetsImportRequirements(&$cert=null) {
	if (!is_array($cert)) { return 'invalid parsed cert data'; }
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
	$keys = array('Organization','OrganizationalUnit','Country','CommonName');
	$hits = 0;
	foreach($keys as $key) {
		if (array_key_exists($key,$cert['certificate']['subject'])) {
			$val = $cert['certificate']['subject'][$key];
			if (!is_array($val)) { continue; }
			foreach($val as $txt) {
				if (is_string($txt) and strlen($txt) > 1) { $hits++; }
				}
			}
		}
	if ($hits < 1) { return 'not enough subject information'; }
	$sig = $cert['signature'];
	if (!is_string($sig) or strlen($sig) < 1) {
		return 'signature is missing';
		}
	return true;
	}

/**
 * Update CA last serial issued by ca id
 * @param int $caId
 * @param int $serial
 * @return bool
 */
public function updateSerialByCaId($caId=null,$serial=null) {
	if (!is_numeric($caId)) { return false; }
	if (!is_numeric($serial)) { return false; }
	$this->resetProperties();
	if ($this->populateFromDb($caId) === false) { return false; }
	$this->setProperty('SerialLastIssued',$serial);
	return ($this->update() === true) ? true : false;
	}

}
?>
