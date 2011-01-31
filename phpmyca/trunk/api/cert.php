<?
/**
 * Generic x509 certificate class
 *
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

class phpmycaCert {

/**
 * Properties that may or may not be relevant, depending on cert type.
 */
public $Certificate      = null;
public $CommonName       = null;
public $CountryName      = null;
public $CreateDate       = null;
public $CSR              = null;
public $Description      = null;
public $EmailAddress     = null;
public $FingerprintMD5   = null;
public $FingerprintSHA1  = null;
public $Id               = null;
public $LocalityName     = null;
public $OrgName          = null;
public $OrgUnitName      = null;
public $ParentId         = null;
public $PrivateKey       = null;
public $PublicKey        = null;
public $RevokeDate       = null;
public $SerialLastIssued = null;
public $SerialNumber     = null;
public $StateName        = null;
public $ValidFrom        = null;
public $ValidTo          = null;

/**
 * The certificate issuer - (phpmycaCert object)
 * @var object
 * @see setIssuer()
 */
public $issuer = null;

/**
 * Has the instance been populated with certificate data?
 * @var bool
 */
public $populated = false;

/**
 * Constructor
 *
 * Optionally populate the cert with provided data source.  If the cert type
 * cannot be derived from $cert, $type and $source must be specified.
 * @param mixed $cert (optional)
 * @param string $type (optional) ca | client | server
 * @param string $source (optional) phpmycaDboCa | phpmycaDboClient
 * @param bool $validate (optional) validate the input - default true
 */
public function __construct(&$cert=null, $type=null, $source=null,
                           $validate=true) {
	// By default, if we can determine the cert type from $cert, ignore
	// what the user specified ;)
	if (is_object($cert) or is_array($cert)) {
		if ($cert instanceof phpmycaDboCa) {
			$type   = 'ca';
			$source = 'phpmycaDboCa';
			} elseif ($cert instanceof phpmycaDboClient) {
			$type = 'client';
			$source = 'phpmycaDboClient';
			} elseif ($cert instanceof phpmycaDboServer) {
			$type = 'server';
			$source = 'phpmycaDboServer';
			} elseif (is_array($cert) and count($cert)) {
			$source = 'user';
			}
		}
	$this->populate($cert,$type,$source,$validate);
	}

/**
 * Populate the issuer.
 * @param phpmycaCert $cert
 * @return bool
 */
public function setIssuer(&$cert=null) {
	if (!($cert instanceof phpmycaCert)) { return false; }
	if (!$cert->populated) { return false; }
	$this->issuer = $cert;
	return true;
	}

/**
 * Get array containing all possible property names.
 */
public function getPropertyNames() {
	return array('Certificate', 'CommonName', 'CountryName', 'CreateDate',
	'CSR', 'Description', 'EmailAddress', 'FingerprintMD5', 'FingerprintSHA1',
	'Id', 'LocalityName', 'OrgName', 'OrgUnitName', 'ParentId', 'PrivateKey',
	'PublicKey', 'RevokeDate', 'SerialLastIssued', 'SerialNumber', 'StateName',
	'ValidFrom', 'ValidTo');
	}

/**
 * Does the populated cert have a certificate signing request?
 * @return bool
 */
public function hasCsr() {
	if (!$this->populated) { return false; }
	return (strpos($this->CSR,'CERTIFICATE REQUEST') === false) ? false : true;
	}

/**
 * Does the populated cert have a private key?
 * @return bool
 */
public function hasPrivateKey() {
	if (!$this->populated) { return false; }
	return (strpos($this->PrivateKey,'PRIVATE KEY') === false) ? false : true;
	}

/**
 * Does the populated cert have a public key?
 * @return bool
 */
public function hasPublicKey() {
	if (!$this->populated) { return false; }
	return (strpos($this->PublicKey,'PUBLIC KEY') === false) ? false : true;
	}

/**
 * Is the private key of the populated object encrypted?
 * @return bool
 */
public function isEncrypted() {
	if (!$this->hasPrivateKey()) { return false; }
	return (strpos($this->PrivateKey,'ENCRYPTED') === false) ? false : true;
	}

/**
 * Is populated cert expired?
 * @param $days
 *   Optionally specify number of days in the future to check
 * @return boolean
 */
public function isExpired($days = null) {
	if (!$this->populated) { return false; }
	$now = time();
	if (is_numeric($days) and $days > 0) {
		$now += (60 * 60 * 24) * $days;
		}
	$expireDate = $this->ValidTo;
	$expireTime = ($expireDate) ? strtotime($expireDate) : false;
	return ($expireTime && ($now >= $expireTime));
	}

/**
 * Can the populated cert be revoked?
 * @return bool
 */
public function isRevokable() {
	return ($this->hasPrivateKey() && !$this->isRevoked());
	}

/**
 * Is populated cert revoked?
 * @return boolean
 */
public function isRevoked() {
	if (!$this->populated) { return false; }
	if ($this->RevokeDate == '0000-00-00 00:00:00') { return false; }
	$revokeDate = $this->RevokeDate;
	$revokeTime = ($revokeDate) ? strtotime($revokeDate) : false;
	return ($revokeTime && (time() >= $revokeTime));
	}

/**
 * Populate this instance from provided input.
 *
 * See __construct() for a description of the parameters.
 *
 * @param mixed $cert
 * @param string $type
 * @param string $source
 * @param bool $validate
 * @return bool
 */
public function populate(&$cert=null, $type=null, $source=null, $validate=true) {
	$this->populated = false;
	if ($validate === true) {
		if (!$this->validatePopulateInput($cert,$type,$source)) { return false; }
		}
	$props =& $this->getPropertyNames();
	// null out existing properties
	foreach($props as $prop) { $this->$prop = null; }
	// The cert is either an array or a phpmycadbo object, iterate accordingly.
	if (is_array($cert)) {
		foreach($cert as $prop => $val) {
			if (in_array($prop,$props)) {
				$this->$prop = $val;
				}
			}
		} else {
		foreach($props as $prop) {
			if ($cert->getProperty($prop)) {
				$this->$prop = $cert->getProperty($prop);
				}
			}
		}
	$this->populated = true;
	return true;
	}

/**
 * Validate password of private key of currently populated cert.
 * @param string $pass
 * @return bool
 */
public function validatePassphrase($pass=null) {
	if (!$this->isEncrypted()) { return false; }
	$key = openssl_pkey_get_private($this->PrivateKey,$pass);
	if ($key === false) { return false; }
	unset($key);
	return true;
	}

/**
 * Get array of required property names when populating certificate by type.
 * @param string $type ca | client | server
 * @return mixed array on success, false on invalid type
 */
private function getRequiredPropertyNames($type=null) {
	if (!$this->isValidType($type)) { return false; }
	// start with properties required in all certs
	$props = array('Certificate','CommonName','CreateDate','FingerprintMD5',
	'FingerPrintSHA1','Id','ParentId','RevokeDate','SerialNumber','ValidFrom',
	'ValidTo');
	// Add in properties that are unique to the type.
	switch($type) {
		case 'ca':
			$props[] = 'CountryName';
			$props[] = 'OrgName';
			$props[] = 'OrgUnitName';
		break;
		case 'client':
			$props[] = 'EmailAddress';
		break;
		}
	natsort($props);
	return $props;
	}

/**
 * Is the specified certificate type valid?
 * @param string $type
 * @return bool
 */
private function isValidType($type=null) {
	return ($type == 'ca' || $type == 'client' || $type == 'server');
	}

/**
 * Is the specified array/type a valid source?
 * @param array $input
 * @param string $type
 * @return bool
 */
private function validateArrayInput(&$input=null, $type=null) {
	if (!is_array($input) or count($input) < 1) { return false; }
	if (!$this->isValidType($type)) { return false; }
	$props = $this->getRequiredPropertyNames($type);
	if (!is_array($props) or count($props) < 1) { return false; }
	foreach($props as $prop) {
		if (!isset($input[$prop])) { return false; }
		}
	unset($props);
	return true;
	}

/**
 * Validate input used to populate a certificate.
 * @param mixed $cert
 * @param string $type
 * @param string $source
 * @return bool
 */
private function validatePopulateInput(&$cert=null, $type=null, $source=null) {
	if (!$this->isValidType($type)) { return false; }
	switch($source) {
		case 'phpmycaDboCa':
		case 'phpmycaDboClient':
		case 'phpmycaDboServer':
			if (!($cert instanceof $source)) { return false; }
			return $cert->populated;
		break;
		case 'user':
			return $this->validateArrayInput($cert,$type);
		break;
		}
	return false;
	}

}

?>
