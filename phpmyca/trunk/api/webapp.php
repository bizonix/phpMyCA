<?
/**
 * phpmyca webapp object
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

/**
 * Required includes
 */
require(WEBAPP_API . '/strings.php');
require(WEBAPP_API . '/db/dbo.php');

/**
 * Main webapp class
 */
class webapp {

/**
 * Webapp object modules
 */
var $ca        = null;
var $cert      = null;
var $client    = null;
var $csrserver = null;
var $html      = null;
var $server    = null;

/**
 * Classes of objects this webapp will use
 */
private $classCa        = 'phpmycaCaCert';
private $classCert      = 'phpmycaCert';
private $classClient    = 'phpmycaClientCert';
private $classCsrServer = 'phpmycaCsrServer';
private $classHtml      = 'webappHtml';
private $classServer    = 'phpmycaServerCert';

/**
 * runtime vars
 */
private $webappLoaded = false;

/**
 * Constructor - load minimally required modules
 */
public function __construct() {
	// run sanity check
	$this->sanityCheck();
	// minimally require the html module
	// minimally require the html and session modules
	$this->moduleRequired('html');
	if (defined('WEBAPP_CSS_URI')) {
		$this->html->htmlCssAdd(WEBAPP_CSS_URI);
		}
	if (defined('WEBAPP_JS_URI')) {
		$this->html->htmlJsAdd(WEBAPP_JS_URI);
		}
	if (defined('WEBAPP_DOCTYPE')) {
		$this->html->htmlDoctypeSet(WEBAPP_DOCTYPE);
		}
	if (defined('WEBAPP_CHARSET')) {
		$this->html->htmlCharsetSet(WEBAPP_CHARSET);
		}
	if (defined('WEBAPP_META')) {
		$this->html->htmlMetaAdd(WEBAPP_META);
		}
	if (defined('WEBAPP_NAME')) {
		$this->html->setPageTitle(WEBAPP_NAME);
		}
	$this->webappLoaded = true;
	}

/**
 * Add a CA cert from POST data
 * Post variable possibilities:
 * CommonName, OrgName, OrgUnitName, LocalityName, CountryName, Days,
 * PassPhrase
 * @return void
 */
public function actionCaAdd() {
	// Validate days
	$days = $_POST['Days'];
	if (!is_numeric($days) or $days < 1) {
		return 'Must specify a valid number of days.';
		}
	// Normalize/validate variables
	$caId = (isset($_POST['caId'])) ? $_POST['caId'] : 'self';
	$caPassPhrase = (isset($_POST['caPassPhrase']))
	? stripslashes(trim($_POST['caPassPhrase'])) : false;
	$CommonName = (isset($_POST['CommonName']))
	? stripslashes(trim($_POST['CommonName'])) : false;
	$OrgName    = (isset($_POST['OrgName']))
	? stripslashes(trim($_POST['OrgName'])) : false;
	$OrgUnitName = (isset($_POST['OrgUnitName']))
	? stripslashes(trim($_POST['OrgUnitName'])) : false;
	$LocalityName = (isset($_POST['LocalityName']))
	? stripslashes(trim($_POST['LocalityName'])) : false;
	$CountryName = (isset($_POST['CountryName']))
	? stripslashes(trim($_POST['CountryName'])) : false;
	$PassPhrase = (isset($_POST['PassPhrase']))
	? stripslashes(trim($_POST['PassPhrase'])) : false;
	if (!is_string($caPassPhrase) or strlen($caPassPhrase) < 1) {
		$caPassPhrase = null;
		}
	if (!is_string($PassPhrase) or strlen($PassPhrase) < 1) {
		$PassPhrase = null;
		}
	// Required
	if (!is_string($CommonName) or strlen($CommonName) < 1) {
		return 'Must specify a valid Name (commonName).';
		}
	if (!is_string($OrgName) or strlen($OrgName) < 1) {
		return 'Must specify a valid Organization Name.';
		}
	if (!is_string($OrgUnitName) or strlen($OrgUnitName) < 1) {
		return 'Must specify a valid Department Name.';
		}
	if (!is_string($CountryName) or strlen($CountryName) !== 2) {
		return 'Must specify a valid Country Name.';
		}
	// Create dn args
	$dnargs = array();
	// required
	$dnargs['commonName']             = $CommonName;
	$dnargs['countryName']            = $CountryName;
	$dnargs['organizationName']       = $OrgName;
	$dnargs['organizationalUnitName'] = $OrgUnitName;
	// optional
	if (is_string($LocalityName) and strlen($LocalityName) > 0) {
		$dnargs['localityName'] = $LocalityName;
		}
	$cfgargs = array();
	$cfgargs['config'] = OPENSSL_CONF;
	$cfgargs['x509_extensions'] = 'v3_ca';
	// Generate private key
	$privkey = openssl_pkey_new($cfgargs);
	if ($privkey === false) {
		return 'Failed to generate private key: ' . openssl_error_string();
		}
	// Issue CSR with newly generated key.
	$csr = openssl_csr_new($dnargs,$privkey,$cfgargs);
	if ($csr === false) {
		return 'Failed to generate CSR: ' . openssl_error_string();
		}
	$certSerialNumber = 0;
	// Did the user specify an issuing CA?
	if (is_numeric($caId)) {
		$this->moduleRequired('ca');
		$this->ca->resetProperties();
		$ca = $this->ca->queryById($caId);
		if (!is_array($ca)) {
			return 'Failed to locate the specified CA.';
			}
		if (!isset($ca['PrivateKey']) or !is_string($ca['PrivateKey'])) {
			return 'Cannot sign certs with 3rd party CAs.';
			}
		if (!isset($ca['ValidTo']) or !is_string($ca['ValidTo'])) {
			return 'Cannot determine if issuer cert is still valid.';
			}
		if ($ca['ValidTo'] < date('Y-m-d H:i:s')) {
			return 'Issuer cert is expired.';
			}
		if (!isset($ca['SerialLastIssued']) or
		    !is_numeric($ca['SerialLastIssued'])) {
			return 'Cannot determine issuer\'s last serial number.';
			}
		$caCertPem        = $ca['Certificate'];
		$caPrivateKeyPem  = $ca['PrivateKey'];
		$caLastSerial     = $ca['SerialLastIssued'];
		$certSerialNumber = $caLastSerial + 1;
		$pKey = array($caPrivateKeyPem,$caPassPhrase);
		$signedCsr = openssl_csr_sign($csr,$caCertPem,$pKey,$days,
		                              $cfgargs,$certSerialNumber);
		} else {
		// Self sign the csr
		$signedCsr = openssl_csr_sign($csr,null,$privkey,$days,$cfgargs,
		$certSerialNumber);
		}
	if ($signedCsr === false) {
		// ignore 0E06D06C
		$errors = openssl_error_string();
		$junk = explode(':',$errors);
		if ($junk[1] !== '0E06D06C') {
			return 'Failed to sign the cert request: ' . $errors;
			}
		}
	// Export the cert
	$rc = openssl_x509_export($signedCsr,$certPem);
	if ($rc === false) {
		$errors = openssl_error_string();
		return 'Failed to export the x509 certificate: ' . $errors;
		}
	// Export the private key
	$rc = openssl_pkey_export($privkey,$privkeyPem,$PassPhrase,$cfgargs);
	if ($rc === false) {
		$errors = openssl_error_string();
		return 'Failed to export the private key: ' . $errors;
		}
	// Export the csr
	$rc = openssl_csr_export($csr,$csrPem);
	if ($rc === false) {
		$errors = openssl_error_string();
		return 'Failed to export the csr: ' . $errors;
		}
	// Call upon actionCaImport to import it into the database
	$rc = $this->actionCaImport($certPem,$privkeyPem,$PassPhrase,$csrPem);
	if (!($rc === true)) {
		return 'Failed to import the CA cert: ' . $rc;
		}
	// Do we need to increment the issuer last serial number?
	if (is_numeric($caId) and $certSerialNumber > 0) {
		if (!($this->ca->updateSerialByCaId($caId,$certSerialNumber) === true)) {
			$m = 'WARNING - The certificate was issued but the attempt to '
			. 'increment the last serial number by the issuing CA failed.  '
			. 'This may cause duplicate serial numbers to be issued by the CA '
			. 'that signed this certificate.';
			return $m;
			}
		}
	return true;
	}

/**
 * Export (download) all CA certs in PEM encoded form
 * @return void
 */
public function actionCaExportCerts() {
	$this->moduleRequired('ca');
	$this->ca->searchReset();
	$this->ca->setSearchSelect('Id');
	$this->ca->setSearchSelect('Certificate');
	$this->ca->setSearchSelect('CSR');
	$this->ca->setSearchSelect('PrivateKey');
	$q = $this->ca->query();
	if (!is_array($q) or count($q) < 1) {
		$this->html->errorMsgSet('No CA certificates were located.');
		die($this->getPageCaList());
		}
	if (!class_exists('ZipArchive')) {
		$this->html->errorMsgSet('ZipArchive is required, cannot continue.');
		die($this->getPageCaList());
		}
	$tmpfile = tempnam(sys_get_temp_dir(),'certExport');
	$zip     = new ZipArchive();
	$rc = $zip->open($tmpfile,ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
	if (!($rc === true)) {
		$this->html->errorMsgSet('Failed to create zip file resource.');
		die($this->getPageCaList());
		}
	$files = array();
	foreach($q as $row) {
		$id = $row['Id'];
		$crt = $row['Certificate'];
		$csr = $row['CSR'];
		$key = $row['PrivateKey'];
		$fn = 'ca-cert-' . $id . '.pem';
		$txt = '';
		if (is_string($crt) and strlen($crt) > 0) {
			$txt .= $crt . "\r\n\r\n";
			}
		if (is_string($key) and strlen($key) > 0) {
			$txt .= $key . "\r\n\r\n";
			}
		if (is_string($csr) and strlen($csr) > 0) {
			$txt .= $csr . "\r\n\r\n";
			}
		$rc = $zip->addFromString($fn,$txt);
		if (!($rc === true)) {
			$zip->close();
			$this->html->errorMsgSet('Failed to add zip file: ' . $fn);
			die($this->getPageCaList());
			}
		}
	$rc = $zip->close();
	if (!($rc === true)) {
		$this->html->errorMsgSet('Failed to create zip file: ' . $tmpfile);
		die($this->getPageCaList());
		}
	// Stream file to client
	header('Content-Type: application/zip');
	header('Content-Length: ' . filesize($tmpfile));
	header('Content-Disposition: attachment; filename="ca-certs.zip"');
	readfile($tmpfile);
	unlink($tmpfile);
	die();
	}

/**
 * Import a CA cert
 * @param string $cert - PEM encoded certificate - required
 * @param string $privKey - PEM encoded private key - optional
 * @param string $passPhrase - optional
 * @param string $certRequest - PEM encoded CSR - optional
 * @return bool true on success
 * @return string error message on failures
 */
public function actionCaImport($pemCert=null,$privKey=null,$passPhrase=null,
                             $certRequest=null) {
	$this->moduleRequired('ca,cert');
	// check arguments
	if (!is_string($pemCert) or strlen($pemCert) < 1) {
		return 'Must provide a valid PEM encoded CA certificate.';
		}
	// normalize arguments
	$privKey     = (is_string($privKey) and strlen($privKey) > 0)
	             ? $privKey : false;
	$passPhrase  = (is_string($passPhrase) and strlen($passPhrase) > 0)
	               ? $passPhrase : '';
	$certRequest = (is_string($certRequest) and strlen($certRequest) > 0)
	               ? $certRequest : false;
	// parse the cert
	$pc = $this->cert->parseCert($pemCert);
	if (!is_array($pc)) { return 'Failed to parse certificate.'; }
	$rc = $this->ca->meetsImportRequirements($pc);
	if (!($rc === true)) {
		return 'Cert does not meet import requirements: ' . $rc;
		}
	$rc = $this->cert->parsedCertIsCa($pc);
	if (!($rc === true)) {
		return 'The specified cert is not a CA certificate.';
		}
	if (!is_numeric($pc['certificate']['serialNumber'])) {
		return 'Invalid certificate serial number.';
		} else {
		$serialNumber = $pc['certificate']['serialNumber'];
		}
	$validFrom = gmdate('Y-m-d H:i:s',
                        $pc['certificate']['validity']['notbefore']);
	if ($validFrom === false) {
		return 'Failed to determine validFrom date.';
		}
	$validTo = gmdate('Y-m-d H:i:s',
                      $pc['certificate']['validity']['notafter']);
	if ($validTo === false) {
		return 'Failed to determine validTo date.';
		}
	// extract needed public key objects
	$pubKeyRes = openssl_pkey_get_public($pemCert);
	if ($pubKeyRes === false) {
		return 'Failed to extract public key.';
		}
	$ar = openssl_pkey_get_details($pubKeyRes);
	if (!is_array($ar) or !isset($ar['key'])) {
		return 'Failed to obtain PEM formatted public key.';
		} else {
		$pubKey = $ar['key'];
		}
	// Locate the issuer if it is not a self-signed cert
	$isSelfSigned = $this->cert->isCertSigner($pemCert,$pemCert);
	if (!($isSelfSigned === true)) {
		$ca = $this->getSignerId($pemCert);
		if (!is_array($ca) or count($ca) < 1) {
			$issuer = $pc['certificate']['issuer'];
			$m = 'The CA cert that signed this certificate could not be '
			. 'located.  Import the CA Certificate that matches the '
			. 'information listed below and try again.';
			$out = print_r($issuer,true);
			$m .= '<P><PRE>' . $out . '</PRE></P>';
			return $m;
			}
		if (count($ca) > 1) {
			$m = '<P>This CA cannot be imported because multiple possible '
			. 'signers exist.  The possible issuers are listed below.</P>';
			$qs_base = $this->html->getActionQs(WA_ACTION_CA_VIEW,0);
			foreach($ca as $cert) {
				$qs = $qs_base . $cert['Id'];
				$cn = $cert['CommonName'];
				$on = $cert['OrgName'];
				$ou = $cert['OrgUnitName'];
				if (!is_string($cn)) { $cn = 'not set'; }
				if (!is_string($on)) { $on = 'not set'; }
				if (!is_string($ou)) { $ou = 'not set'; }
				$hr = '<A HREF="' . $qs . '" TARGET="viewCa' . $cert['Id']
				. '">' . $cn . '</A>';
				$m .= '<P>commonName: ' . $hr . '<BR />'
				. 'organizationName: ' . $on . '<BR />'
				. 'organizationalUnit: ' . $ou . '</P>';
				}
			return $m;
			}
		$caId = (isset($ca[0]['Id'])) ? $ca[0]['Id'] : false;
		if (!is_numeric($caId) or $caId < 1) {
			return 'Failed to locate issuing CA id.';
			}
		} else {
		$caId = 0;
		}
	// Do the dates match up with the ca?  Only give a warning if the
	// expiration dates don't jive.
	if ($caId > 0) {
		$this->ca->resetProperties();
		if ($this->ca->populateFromDb($caId) === false) {
			return 'Failed to locate issuer information.';
			}
		$caValidTo = $this->ca->getProperty('ValidTo');
		if (substr($validTo,0,10) > substr($caValidTo,0,10)) {
			$m = 'WARNING: The certificate expiration date is invalid, the '
			. 'issuer certficate expires ' . $caValidTo . ', this certificate '
			. 'expires ' . $validTo . '.';
			$this->html->errorMsgSet($m);
			}
		}
	// Was a private key specified?
	$pKey = false;
	if (is_string($privKey)) {
		$pKey = openssl_pkey_get_private($privKey,$passPhrase);
		if ($pKey === false) {
			return 'Private key or password is invalid.';
			}
		if (!openssl_x509_check_private_key($pemCert,$pKey)) {
			return 'Private key does not belong to cert.';
			}
		}
	// Did they include a csr?
	if (is_string($certRequest)) {
		$csrPubKey = openssl_csr_get_public_key($certRequest);
		if ($csrPubKey === false) {
			return 'Failed to extract public key from CSR.';
			}
		if (openssl_pkey_get_details($pubKeyRes) !==
	        openssl_pkey_get_details($csrPubKey)) {
			return 'CSR and cert do not match.';
			}
		}
	// Import the cert into the database
	$this->ca->resetProperties();
	// required properties
	$this->ca->setProperty('Certificate',      $pemCert);
	$this->ca->setProperty('CreateDate',       'now()');
	$this->ca->setProperty('Description',      'imported');
	$this->ca->setProperty('FingerprintMD5',   $pc['fingerprints']['md5']);
	$this->ca->setProperty('FingerprintSHA1',  $pc['fingerprints']['sha1']);
	$this->ca->setProperty('ParentId',         $caId);
	$this->ca->setProperty('PublicKey',        $pubKey);
	$this->ca->setProperty('SerialLastIssued', '0');
	$this->ca->setProperty('SerialNumber',     $serialNumber);
	$this->ca->setProperty('ValidFrom',        $validFrom);
	$this->ca->setProperty('ValidTo',          $validTo);
	// optional properties
	if (is_string($privKey)) {
		$this->ca->setProperty('PrivateKey', $privKey);
		}
	if (is_string($certRequest)) {
		$this->ca->setProperty('CSR', $certRequest);
		}
	// optional subject properties
	$sub = $pc['certificate']['subject'];
	if (isset($sub['CommonName'])) {
		$val = $sub['CommonName'];
		if (is_array($val) and count($val) > 0) {
			$this->ca->setProperty('CommonName',implode("\n",$val));
			}
		}
	if (isset($sub['Country'])) {
		$val = $sub['Country'];
		if (is_array($val) and count($val) > 0) {
			$this->ca->setProperty('CountryName',implode("\n",$val));
			}
		}
	if (isset($sub['Location'])) {
		$val = $sub['Location'];
		if (is_array($val) and count($val) > 0) {
			$this->ca->setProperty('LocalityName',implode("\n",$val));
			}
		}
	if (isset($sub['Organization'])) {
		$val = $sub['Organization'];
		if (is_array($val) and count($val) > 0) {
			$this->ca->setProperty('OrgName',implode("\n",$val));
			}
		}
	if (isset($sub['OrganizationalUnit'])) {
		$val = $sub['OrganizationalUnit'];
		if (is_array($val) and count($val) > 0) {
			$this->ca->setProperty('OrgUnitName',implode("\n",$val));
			}
		}
	// Do the deed...
	$this->ca->populated = true;
	$rc = $this->ca->add();
	return ($rc === true) ? $rc : 'Import Failed: ' . $rc;
	}

/**
 * Add a client cert from POST data
 * Post variable possibilities: caId, caPassPhrase, CommonName, OrgName,
 * OrgUnitName, EmailAddress, LocalityName, StateName, CountryName,
 * Days, PassPhrase.  Optionally provide a CSR, otherwise one will be generated.
 * @param string $csr (optional)
 * @return void
 */
public function actionClientAdd($csr=null) {
	$this->moduleRequired('ca');
	// Normalize/validate variables
	$caId         = (isset($_POST['caId'])) ? $_POST['caId'] : false;
	$caPassPhrase = (isset($_POST['caPassPhrase']))
	? stripslashes(trim($_POST['caPassPhrase'])) : false;
	$CommonName   = (isset($_POST['CommonName']))
	? stripslashes(trim($_POST['CommonName'])) : false;
	$OrgName      = (isset($_POST['OrgName']))
	? stripslashes(trim($_POST['OrgName'])) : false;
	$OrgUnitName  = (isset($_POST['OrgUnitName']))
	? stripslashes(trim($_POST['OrgUnitName'])) : false;
	$Days = (isset($_POST['Days'])) ? $_POST['Days'] : false;
	$PassPhrase   = (isset($_POST['PassPhrase']))
	? stripslashes(trim($_POST['PassPhrase'])) : false;
	if (!is_string($caPassPhrase) or strlen($caPassPhrase) < 1) {
		$caPassPhrase = null;
		}
	if (!is_string($PassPhrase) or strlen($PassPhrase) < 1) {
		$PassPhrase = null;
		}
	// Validate required
	if (!is_numeric($caId) or $caId < 1) {
		return 'Must specify valid Certificate Authority.';
		}
	if (!is_numeric($Days) or $Days < 1) {
		return 'Must specify valid number of days.';
		}
	// Required
	if (!is_string($CommonName) or strlen($CommonName) < 1) {
		return 'Must specify a valid Name (commonName).';
		}
	// Create dn args
	$dnargs = array();
	// required
	$dnargs['commonName']   = $CommonName;
	$dnargs['emailAddress'] = $CommonName;
	if (is_string($OrgName) and strlen($OrgName) > 0) {
		$dnargs['organizationName'] = $OrgName;
		}
	if (is_string($OrgUnitName) and strlen($OrgUnitName) > 0) {
		$dnargs['organizationalUnitName'] = $OrgUnitName;
		}
	$cfgargs = array();
	$cfgargs['config'] = OPENSSL_CONF;
	$cfgargs['x509_extensions'] = 'v3_client';
	// Generate private key
	$privkey = openssl_pkey_new($cfgargs);
	if ($privkey === false) {
		return 'Failed to generate private key: ' . openssl_error_string();
		}
	// Issue CSR with newly generated key.
	$csr = openssl_csr_new($dnargs,$privkey,$cfgargs);
	if ($csr === false) {
		return 'Failed to generate CSR: ' . openssl_error_string();
		}
	//
	// Sign with the specified CA
	//
	$this->ca->resetProperties();
	$ca = $this->ca->queryById($caId);
	if (!is_array($ca)) {
		return 'Failed to locate the specified CA.';
		}
	if (!isset($ca['PrivateKey']) or !is_string($ca['PrivateKey'])) {
		return 'Cannot issue certs from 3rd party CAs.';
		}
	if (!isset($ca['ValidTo']) or !is_string($ca['ValidTo'])) {
		return 'Cannot determine if CA cert is still valid.';
		}
	if ($ca['ValidTo'] < date('Y-m-d H:i:s')) {
		return 'CA is expired.';
		}
	if (!isset($ca['SerialLastIssued']) or
	    !is_numeric($ca['SerialLastIssued'])) {
		return 'Cannot determine last serial number issued by CA.';
		}
	$caCertPem       = $ca['Certificate'];
	$caPrivateKeyPem = $ca['PrivateKey'];
	$caLastSerial    = $ca['SerialLastIssued'];
	$SerialNumber    = $caLastSerial + 1;
	$pKey = array($caPrivateKeyPem,$caPassPhrase);
	$signedCsr = openssl_csr_sign($csr,$caCertPem,$pKey,$Days,
	                              $cfgargs,$SerialNumber);
	if ($signedCsr === false) {
		// ignore 0E06D06C
		$errors = openssl_error_string();
		$junk = explode(':',$errors);
		if ($junk[1] !== '0E06D06C') {
			return 'Failed to sign the cert request: ' . $errors;
			}
		}
	// Export the cert
	$rc = openssl_x509_export($signedCsr,$certPem);
	if ($rc === false) {
		$errors = openssl_error_string();
		return 'Failed to export the x509 certificate: ' . $errors;
		}
	// Export the private key
	$rc = openssl_pkey_export($privkey,$privkeyPem,$PassPhrase,$cfgargs);
	if ($rc === false) {
		$errors = openssl_error_string();
		return 'Failed to export the private key: ' . $errors;
		}
	// Export the csr
	$rc = openssl_csr_export($csr,$csrPem);
	if ($rc === false) {
		$errors = openssl_error_string();
		return 'Failed to export the csr: ' . $errors;
		}
	// Call upon actionClientImport to import it into the database
	$rc = $this->actionClientImport($certPem,$privkeyPem,$PassPhrase,$csrPem);
	if (!($rc === true)) {
		return 'Failed to import the server cert: ' . $rc;
		}
	return true;
	}

/**
 * Export (download) all client certs in PEM encoded form
 * @return void
 */
public function actionClientExportCerts() {
	$this->moduleRequired('client');
	$this->client->searchReset();
	$this->client->setSearchSelect('Id');
	$this->client->setSearchSelect('Certificate');
	$this->client->setSearchSelect('CSR');
	$this->client->setSearchSelect('PrivateKey');
	$q = $this->client->query();
	if (!is_array($q) or count($q) < 1) {
		$this->html->errorMsgSet('No client certificates were located.');
		die($this->getPageClientList());
		}
	if (!class_exists('ZipArchive')) {
		$this->html->errorMsgSet('ZipArchive is required, cannot continue.');
		die($this->getPageClientList());
		}
	$tmpfile = tempnam(sys_get_temp_dir(),'certExport');
	$zip     = new ZipArchive();
	$rc = $zip->open($tmpfile,ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
	if (!($rc === true)) {
		$this->html->errorMsgSet('Failed to create zip file resource.');
		die($this->getPageClientList());
		}
	$files = array();
	foreach($q as $row) {
		$id  = $row['Id'];
		$crt = $row['Certificate'];
		$csr = $row['CSR'];
		$key = $row['PrivateKey'];
		$fn  = 'client-cert-' . $id . '.pem';
		$txt = '';
		if (is_string($crt) and strlen($crt) > 0) {
			$txt .= $crt . "\r\n\r\n";
			}
		if (is_string($key) and strlen($key) > 0) {
			$txt .= $key . "\r\n\r\n";
			}
		if (is_string($csr) and strlen($csr) > 0) {
			$txt .= $csr . "\r\n\r\n";
			}
		$rc = $zip->addFromString($fn,$txt);
		if (!($rc === true)) {
			$zip->close();
			$this->html->errorMsgSet('Failed to add zip file: ' . $fn);
			die($this->getPageClientList());
			}
		}
	$rc = $zip->close();
	if (!($rc === true)) {
		$this->html->errorMsgSet('Failed to create zip file: ' . $tmpfile);
		die($this->getPageClientList());
		}
	// Stream file to client
	header('Content-Type: application/zip');
	header('Content-Length: ' . filesize($tmpfile));
	header('Content-Disposition: attachment; filename="client-certs.zip"');
	readfile($tmpfile);
	unlink($tmpfile);
	die();
	}

/**
 * Import a client cert
 * @param string $cert - PEM encoded certificate - required
 * @param string $privKey - PEM encoded private key - optional
 * @param string $passPhrase - optional
 * @param string $certRequest - PEM encoded CSR - optional
 * @return bool true on success
 * @return string error message on failures
 */
public function actionClientImport($pemCert=null,$privKey=null,$passPhrase=null,
                                 $certRequest=null) {
	$this->moduleRequired('ca,cert,client');
	// check arguments
	if (!is_string($pemCert) or strlen($pemCert) < 1) {
		return 'Must provide a valid PEM encoded certificate.';
		}
	// normalize arguments
	$privKey     = (is_string($privKey) and strlen($privKey) > 0)
	             ? $privKey : false;
	$passPhrase  = (is_string($passPhrase) and strlen($passPhrase) > 0)
	               ? $passPhrase : '';
	$certRequest = (is_string($certRequest) and strlen($certRequest) > 0)
	               ? $certRequest : false;
	// parse the cert
	$pc = $this->cert->parseCert($pemCert);
	if (!is_array($pc)) { return 'Failed to parse certificate.'; }
	$rc = $this->client->meetsImportRequirements($pc);
	if (!($rc === true)) {
		return 'Cert does not meet import requirements: ' . $rc;
		}
	// no self-signed certs
	$isSelfSigned = $this->cert->isCertSigner($pemCert,$pemCert);
	if ($isSelfSigned === true) {
		return 'Will not import self-signed certificates.';
		}
	$rc = $this->cert->parsedCertIsClient($pc);
	if (!($rc === true)) {
		return 'The specified cert is not a client certificate.';
		}
	if (!is_numeric($pc['certificate']['serialNumber'])) {
		return 'Invalid certificate serial number.';
		} else {
		$serialNumber = $pc['certificate']['serialNumber'];
		}
	$validFrom = gmdate('Y-m-d H:i:s',
                        $pc['certificate']['validity']['notbefore']);
	if ($validFrom === false) {
		return 'Failed to determine validFrom date.';
		}
	$validTo = gmdate('Y-m-d H:i:s',
                      $pc['certificate']['validity']['notafter']);
	if ($validTo === false) {
		return 'Failed to determine validTo date.';
		}
	// extract needed public key objects
	$pubKeyRes = openssl_pkey_get_public($pemCert);
	if ($pubKeyRes === false) {
		return 'Failed to extract public key.';
		}
	$ar = openssl_pkey_get_details($pubKeyRes);
	if (!is_array($ar) or !isset($ar['key'])) {
		return 'Failed to obtain PEM formatted public key.';
		} else {
		$pubKey = $ar['key'];
		}
	// Locate issuer
	$ca = $this->getSignerId($pemCert);
	if (!is_array($ca) or count($ca) < 1) {
		$issuer = $pc['certificate']['issuer'];
		$m = 'The CA cert that signed this certificate could not be '
		. 'located.  Import the CA Certificate that matches the '
		. 'information listed below and try again.';
		$out = print_r($issuer,true);
		$m .= '<P><PRE>' . $out . '</PRE></P>';
		return $m;
		}
	if (count($ca) > 1) {
		$m = 'This certificate cannot be imported because multiple possible '
		. 'signers exist.';
		return $m;
		}
	$caId = (isset($ca[0]['Id'])) ? $ca[0]['Id'] : false;
	if (!is_numeric($caId) or $caId < 1) {
		return 'Failed to locate issuing CA id.';
		}
	// Validate expiration date of CA cert.  Only give a warning if the
	// expiration dates don't jive.
	$this->ca->resetProperties();
	if ($this->ca->populateFromDb($caId) === false) {
		return 'Failed to locate issuer information.';
		}
	$caValidTo = $this->ca->getProperty('ValidTo');
	if (substr($validTo,0,10) > substr($caValidTo,0,10)) {
		$m = 'WARNING: The certificate expiration date is invalid, the issuer '
		. 'certficate expires ' . $caValidTo . ', this certificate expires '
		. $validTo . '.';
		$this->html->errorMsgSet($m);
		}
	// Determine the last serial number issued by the ca in case the
	// serial number of the current certificate is higher and we need
	// to bump the ca last serial issued.
	$caLastSerial = $this->ca->getLastSerialIssued($caId);
	if ($caLastSerial === false or !is_numeric($caLastSerial)) {
		return 'Failed to determine CA last serial issued.';
		}
	// Validate the private key
	if (is_string($privKey)) {
		$pKey = openssl_pkey_get_private($privKey,$passPhrase);
		if ($pKey === false) {
			return 'Private key or password is invalid.';
			}
		if (!openssl_x509_check_private_key($pemCert,$pKey)) {
			return 'Private key does not belong to cert.';
			}
		}
	// Did they include a csr?
	if (is_string($certRequest)) {
		$csrPubKey = openssl_csr_get_public_key($certRequest);
		if ($csrPubKey === false) {
			return 'Failed to extract public key from CSR.';
			}
		if (openssl_pkey_get_details($pubKeyRes) !==
	        openssl_pkey_get_details($csrPubKey)) {
			return 'CSR and cert do not match.';
			}
		}
	// Import the cert into the database
	$this->client->resetProperties();
	// required properties
	$this->client->setProperty('Certificate',     $pemCert);
	$this->client->setProperty('CommonName',      implode("\n",$pc['certificate']['subject']['CommonName']));
	$this->client->setProperty('CreateDate',      'now()');
	$this->client->setProperty('Description',     'imported');
	$this->client->setProperty('FingerprintMD5',  $pc['fingerprints']['md5']);
	$this->client->setProperty('FingerprintSHA1', $pc['fingerprints']['sha1']);
	$this->client->setProperty('ParentId',        $caId);
	$this->client->setProperty('PrivateKey',      $privKey);
	$this->client->setProperty('PublicKey',       $pubKey);
	$this->client->setProperty('SerialNumber',    $serialNumber);
	$this->client->setProperty('ValidFrom',       $validFrom);
	$this->client->setProperty('ValidTo',         $validTo);
	// optional properties
	if (is_string($certRequest)) {
		$this->client->setProperty('CSR', $certRequest);
		}
	// optional subject properties
	$sub = $pc['certificate']['subject'];
	if (isset($sub['emailAddress'])) {
		$val = $sub['emailAddress'];
		if (is_array($val) and count($val) > 0) {
			$this->client->setProperty('EmailAddress',implode("\n",$val));
			}
		}
	if (isset($sub['Organization'])) {
		$val = $sub['Organization'];
		if (is_array($val) and count($val) > 0) {
			$this->client->setProperty('OrgName',implode("\n",$val));
			}
		}
	if (isset($sub['OrganizationalUnit'])) {
		$val = $sub['OrganizationalUnit'];
		if (is_array($val) and count($val) > 0) {
			$this->client->setProperty('OrgUnitName',implode("\n",$val));
			}
		}
	// Do the deed...
	$this->client->populated = true;
	$rc = $this->client->add();
	if (!($rc === true)) { return 'Import Failed: ' . $rc; }
	// Do we need to bump the CA's last serial issued?
	if ($serialNumber > $caLastSerial) {
		if (!($this->ca->updateSerialByCaId($caId,$serialNumber) === true)) {
			return $m;
			}
		}
	return true;
	}

/**
 * Redirect to webapp home
 */
public function actionHome() {
	die(header('Location: ./'));
	}

/**
 * Add a server cert from POST data
 * Post variable possibilities: caId, caPassPhrase, CommonName, OrgName,
 * OrgUnitName, EmailAddress, LocalityName, StateName, CountryName,
 * Days, PassPhrase.  Optionally provide a CSR, otherwise one will be generated.
 * @param string $csr (optional)
 * @return void
 */
public function actionServerAdd($csr=null) {
	$this->moduleRequired('ca');
	// Normalize/validate variables
	$caId         = (isset($_POST['caId'])) ? $_POST['caId'] : false;
	$caPassPhrase = (isset($_POST['caPassPhrase']))
	? stripslashes(trim($_POST['caPassPhrase'])) : false;
	$CommonName   = (isset($_POST['CommonName']))
	? stripslashes(trim($_POST['CommonName'])) : false;
	$OrgName      = (isset($_POST['OrgName']))
	? stripslashes(trim($_POST['OrgName'])) : false;
	$OrgUnitName  = (isset($_POST['OrgUnitName']))
	? stripslashes(trim($_POST['OrgUnitName'])) : false;
	$EmailAddress = (isset($_POST['EmailAddress']))
	? stripslashes(trim($_POST['EmailAddress'])) : false;
	$LocalityName = (isset($_POST['LocalityName']))
	? stripslashes(trim($_POST['LocalityName'])) : false;
	$StateName    = (isset($_POST['StateName']))
	? stripslashes(trim($_POST['StateName'])) : false;
	$CountryName  = (isset($_POST['CountryName']))
	? stripslashes(trim($_POST['CountryName'])) : false;
	$Days = (isset($_POST['Days'])) ? $_POST['Days'] : false;
	$PassPhrase   = (isset($_POST['PassPhrase']))
	? stripslashes(trim($_POST['PassPhrase'])) : false;
	if (!is_string($caPassPhrase) or strlen($caPassPhrase) < 1) {
		$caPassPhrase = null;
		}
	if (!is_string($PassPhrase) or strlen($PassPhrase) < 1) {
		$PassPhrase = null;
		}
	// Validate required
	if (!is_numeric($caId) or $caId < 1) {
		return 'Must specify valid Certificate Authority.';
		}
	if (!is_numeric($Days) or $Days < 1) {
		return 'Must specify valid number of days.';
		}
	// Required
	if (!is_string($CommonName) or strlen($CommonName) < 1) {
		return 'Must specify a valid Name (commonName).';
		}
	// Create dn args
	$dnargs = array();
	// required
	$dnargs['commonName'] = $CommonName;
	// optional
	if (is_string($EmailAddress) and strlen($EmailAddress) > 0) {
		$dnargs['emailAddress'] = $EmailAddress;
		}
	if (is_string($CountryName) and strlen($CountryName) > 0) {
		$dnargs['countryName'] = $CountryName;
		}
	if (is_string($OrgName) and strlen($OrgName) > 0) {
		$dnargs['organizationName'] = $OrgName;
		}
	if (is_string($OrgUnitName) and strlen($OrgUnitName) > 0) {
		$dnargs['organizationalUnitName'] = $OrgUnitName;
		}
	if (is_string($LocalityName) and strlen($LocalityName) > 0) {
		$dnargs['localityName'] = $LocalityName;
		}
	if (is_string($StateName) and strlen($StateName) > 0) {
		$dnargs['stateOrProvinceName'] = $StateName;
		}
	$cfgargs = array();
	$cfgargs['config'] = OPENSSL_CONF;
	$cfgargs['x509_extensions'] = 'v3_server';
	// Generate private key
	$privkey = openssl_pkey_new($cfgargs);
	if ($privkey === false) {
		return 'Failed to generate private key: ' . openssl_error_string();
		}
	// Issue CSR with newly generated key.
	$csr = openssl_csr_new($dnargs,$privkey,$cfgargs);
	if ($csr === false) {
		return 'Failed to generate CSR: ' . openssl_error_string();
		}
	//
	// Sign with the specified CA
	//
	$this->ca->resetProperties();
	$ca = $this->ca->queryById($caId);
	if (!is_array($ca)) {
		return 'Failed to locate the specified CA.';
		}
	if (!isset($ca['PrivateKey']) or !is_string($ca['PrivateKey'])) {
		return 'Cannot issue certs from 3rd party CAs.';
		}
	if (!isset($ca['ValidTo']) or !is_string($ca['ValidTo'])) {
		return 'Cannot determine if CA cert is still valid.';
		}
	if ($ca['ValidTo'] < date('Y-m-d H:i:s')) {
		return 'CA is expired.';
		}
	if (!isset($ca['SerialLastIssued']) or
	    !is_numeric($ca['SerialLastIssued'])) {
		return 'Cannot determine last serial number issued by CA.';
		}
	$caCertPem       = $ca['Certificate'];
	$caPrivateKeyPem = $ca['PrivateKey'];
	$caLastSerial    = $ca['SerialLastIssued'];
	$SerialNumber    = $caLastSerial + 1;
	$pKey = array($caPrivateKeyPem,$caPassPhrase);
	$signedCsr = openssl_csr_sign($csr,$caCertPem,$pKey,$Days,
	                              $cfgargs,$SerialNumber);
	if ($signedCsr === false) {
		// ignore 0E06D06C
		$errors = openssl_error_string();
		$junk = explode(':',$errors);
		if ($junk[1] !== '0E06D06C') {
			return 'Failed to sign the cert request: ' . $errors;
			}
		}
	// Export the cert
	$rc = openssl_x509_export($signedCsr,$certPem);
	if ($rc === false) {
		$errors = openssl_error_string();
		return 'Failed to export the x509 certificate: ' . $errors;
		}
	// Export the private key
	$rc = openssl_pkey_export($privkey,$privkeyPem,$PassPhrase,$cfgargs);
	if ($rc === false) {
		$errors = openssl_error_string();
		return 'Failed to export the private key: ' . $errors;
		}
	// Export the csr
	$rc = openssl_csr_export($csr,$csrPem);
	if ($rc === false) {
		$errors = openssl_error_string();
		return 'Failed to export the csr: ' . $errors;
		}
	// Call upon actionServerImport to import it into the database
	$rc = $this->actionServerImport($certPem,$privkeyPem,$PassPhrase,$csrPem);
	if (!($rc === true)) {
		return 'Failed to import the server cert: ' . $rc;
		}
	return true;
	}

/**
 * Add a server certificate request from POST data
 * Post variable possibilities: CommonName, OrgName,
 * OrgUnitName, EmailAddress, LocalityName, StateName, CountryName,
 * PassPhrase, ExportPassPhrase
 * @return void
 */
public function actionServerCsrAdd() {
	$this->moduleRequired('ca');
	// Normalize/validate variables
	$CommonName   = (isset($_POST['CommonName']))
	? stripslashes(trim($_POST['CommonName'])) : false;
	$OrgName      = (isset($_POST['OrgName']))
	? stripslashes(trim($_POST['OrgName'])) : false;
	$OrgUnitName  = (isset($_POST['OrgUnitName']))
	? stripslashes(trim($_POST['OrgUnitName'])) : false;
	$EmailAddress = (isset($_POST['EmailAddress']))
	? stripslashes(trim($_POST['EmailAddress'])) : false;
	$LocalityName = (isset($_POST['LocalityName']))
	? stripslashes(trim($_POST['LocalityName'])) : false;
	$StateName    = (isset($_POST['StateName']))
	? stripslashes(trim($_POST['StateName'])) : false;
	$CountryName  = (isset($_POST['CountryName']))
	? stripslashes(trim($_POST['CountryName'])) : false;
	$PassPhrase   = (isset($_POST['PassPhrase']))
	? stripslashes(trim($_POST['PassPhrase'])) : false;
	$ExportPassPhrase = (isset($_POST['ExportPassPhrase']))
	? stripslashes(trim($_POST['ExportPassPhrase'])) : false;
	if (!is_string($PassPhrase) or strlen($PassPhrase) < 1) {
		$PassPhrase = null;
		}
	if (!is_string($ExportPassPhrase) or strlen($ExportPassPhrase) < 1) {
		$ExportPassPhrase = null;
		}
	// Validate required
	if (!is_string($CommonName)) {
		return 'Must specify a valid Host name.';
		}
	if (!is_string($OrgName)) {
		return 'Must specify a valid Organization name.';
		}
	if (!is_string($OrgUnitName)) {
		return 'Must specify a valid Organizational Unit name.';
		}
	if (!is_string($EmailAddress)) {
		return 'Must specify a valid Email Address.';
		}
	if (!is_string($LocalityName)) {
		return 'Must specify a valid City name.';
		}
	if (!is_string($StateName)) {
		return 'Must specify a valid State name.';
		}
	if (!is_string($CountryName) or strlen($CountryName) !== 2) {
		return 'Must specify a valid Country name.';
		}
	// Create dn args
	$dnargs = array();
	$dnargs['commonName']             = $CommonName;
	$dnargs['emailAddress']           = $EmailAddress;
	$dnargs['countryName']            = $CountryName;
	$dnargs['organizationName']       = $OrgName;
	$dnargs['organizationalUnitName'] = $OrgUnitName;
	$dnargs['localityName']           = $LocalityName;
	$dnargs['stateOrProvinceName']    = $StateName;
	$cfgargs = array();
	$cfgargs['config'] = OPENSSL_CONF;
	$cfgargs['x509_extensions'] = 'v3_server';
	// Generate private key
	$privkey = openssl_pkey_new($cfgargs);
	if ($privkey === false) {
		return 'Failed to generate private key: ' . openssl_error_string();
		}
	// Issue CSR with newly generated key.  If an export passphrase was
	// requested, add it.
	if (!empty($ExportPassPhrase)) {
		$cfgargs['encrypt_key'] = $ExportPassPhrase;
		}
	$csr = openssl_csr_new($dnargs,$privkey,$cfgargs);
	if ($csr === false) {
		return 'Failed to generate CSR: ' . openssl_error_string();
		}
	// Export the private key
	$rc = openssl_pkey_export($privkey,$privkeyPem,$PassPhrase,$cfgargs);
	if ($rc === false) {
		$errors = openssl_error_string();
		return 'Failed to export the private key: ' . $errors;
		}
	// Export the public key
	$junk = openssl_pkey_get_details(openssl_csr_get_public_key($csr));
	if (!is_array($junk) or !isset($junk['key'])) {
		return 'Failed to extract public key.';
		}
	$pubkeyPem = $junk['key'];
	// Export the csr
	$rc = openssl_csr_export($csr,$csrPem);
	if ($rc === false) {
		$errors = openssl_error_string();
		return 'Failed to export the csr: ' . $errors;
		}
	//
	// Do the insert
	//
	$this->moduleRequired('csrserver');
	$this->csrserver->resetProperties();
	$this->csrserver->setProperty('CommonName',   $CommonName);
	$this->csrserver->setProperty('CountryName',  $CountryName);
	$this->csrserver->setProperty('CreateDate',   'now()');
	$this->csrserver->setProperty('CSR',          $csrPem);
	// $this->csrserver->setProperty('Description',  );
	$this->csrserver->setProperty('EmailAddress', $EmailAddress);
	$this->csrserver->setProperty('LocalityName', $LocalityName);
	$this->csrserver->setProperty('OrgName',      $OrgName);
	$this->csrserver->setProperty('OrgUnitName',  $OrgUnitName);
	$this->csrserver->setProperty('PrivateKey',   $privkeyPem);
	$this->csrserver->setProperty('PublicKey',    $pubkeyPem);
	$this->csrserver->setProperty('StateName',    $StateName);
	$this->csrserver->populated = true;
	$rc = $this->csrserver->add();
	if (!($rc === true)) { return 'Insert Failed: ' . $rc; }
	return true;
	}

/**
 * Sign a server cert from user provided csr
 * Post variable possibilities: caId, caPassPhrase, Days, PassPhrase.
 * @param string $csr (required)
 * @return void
 */
public function actionServerCsrSign() {
	$this->moduleRequired('ca');
	// Normalize/validate variables
	$caId         = (isset($_POST['caId'])) ? $_POST['caId'] : false;
	$caPassPhrase = (isset($_POST['caPassPhrase']))
	? stripslashes(trim($_POST['caPassPhrase'])) : false;
	$CommonName   = (isset($_POST['CommonName']))
	? stripslashes(trim($_POST['CommonName'])) : false;
	$csr          = (isset($_POST['csr'])) ? $_POST['csr'] : false;
	$Days = (isset($_POST['Days'])) ? $_POST['Days'] : false;
	if (!is_string($caPassPhrase) or strlen($caPassPhrase) < 1) {
		$caPassPhrase = null;
		}
	// Validate required
	if (!is_numeric($caId) or $caId < 1) {
		return 'Must specify valid Certificate Authority.';
		}
	if (!is_string($csr) or strlen($csr) < 1) {
		return 'Must provide PEM encoded CSR.';
		}
	$dnargs = openssl_csr_get_subject($csr,false);
	if (!is_array($dnargs) or !isset($dnargs['commonName'])) {
		return 'Invalid or no CSR specified.';
		}
	if (!is_numeric($Days) or $Days < 1) {
		return 'Must specify valid number of days.';
		}
	$cfgargs = array();
	$cfgargs['config'] = OPENSSL_CONF;
	$cfgargs['x509_extensions'] = 'v3_server';
	//
	// Sign with the specified CA
	//
	$this->ca->resetProperties();
	$ca = $this->ca->queryById($caId);
	if (!is_array($ca)) {
		return 'Failed to locate the specified CA.';
		}
	if (!isset($ca['PrivateKey']) or !is_string($ca['PrivateKey'])) {
		return 'Cannot issue certs from 3rd party CAs.';
		}
	if (!isset($ca['ValidTo']) or !is_string($ca['ValidTo'])) {
		return 'Cannot determine if CA cert is still valid.';
		}
	if ($ca['ValidTo'] < date('Y-m-d H:i:s')) {
		return 'CA is expired.';
		}
	if (!isset($ca['SerialLastIssued']) or
	    !is_numeric($ca['SerialLastIssued'])) {
		return 'Cannot determine last serial number issued by CA.';
		}
	$caCertPem       = $ca['Certificate'];
	$caPrivateKeyPem = $ca['PrivateKey'];
	$caLastSerial    = $ca['SerialLastIssued'];
	$SerialNumber    = $caLastSerial + 1;
	$pKey = array($caPrivateKeyPem,$caPassPhrase);
	$signedCsr = openssl_csr_sign($csr,$caCertPem,$pKey,$Days,
	                              $cfgargs,$SerialNumber);
	if ($signedCsr === false) {
		// ignore 0E06D06C
		$errors = openssl_error_string();
		$junk = explode(':',$errors);
		if ($junk[1] !== '0E06D06C') {
			return 'Failed to sign the cert request: ' . $errors;
			}
		}
	// Export the cert
	$rc = openssl_x509_export($signedCsr,$certPem);
	if ($rc === false) {
		$errors = openssl_error_string();
		return 'Failed to export the x509 certificate: ' . $errors;
		}
	// Call upon actionServerImport to import it into the database
	$rc = $this->actionServerImport($certPem,null,null,$csr);
	if (!($rc === true)) {
		return 'Failed to import the server cert: ' . $rc;
		}
	return true;
	}

/**
 * Export (download) all server certs in PEM encoded form
 * @return void
 */
public function actionServerExportCerts() {
	$this->moduleRequired('server');
	$this->server->searchReset();
	$this->server->setSearchSelect('Id');
	$this->server->setSearchSelect('Certificate');
	$this->server->setSearchSelect('CSR');
	$this->server->setSearchSelect('PrivateKey');
	$q = $this->server->query();
	if (!is_array($q) or count($q) < 1) {
		$this->html->errorMsgSet('No server certificates were located.');
		die($this->getPageServerList());
		}
	if (!class_exists('ZipArchive')) {
		$this->html->errorMsgSet('ZipArchive is required, cannot continue.');
		die($this->getPageServerList());
		}
	$tmpfile = tempnam(sys_get_temp_dir(),'certExport');
	$zip     = new ZipArchive();
	$rc = $zip->open($tmpfile,ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
	if (!($rc === true)) {
		$this->html->errorMsgSet('Failed to create zip file resource.');
		die($this->getPageServerList());
		}
	$files = array();
	foreach($q as $row) {
		$id  = $row['Id'];
		$crt = $row['Certificate'];
		$csr = $row['CSR'];
		$key = $row['PrivateKey'];
		$fn  = 'server-cert-' . $id . '.pem';
		$txt = '';
		if (is_string($crt) and strlen($crt) > 0) {
			$txt .= $crt . "\r\n\r\n";
			}
		if (is_string($key) and strlen($key) > 0) {
			$txt .= $key . "\r\n\r\n";
			}
		if (is_string($csr) and strlen($csr) > 0) {
			$txt .= $csr . "\r\n\r\n";
			}
		$rc = $zip->addFromString($fn,$txt);
		if (!($rc === true)) {
			$zip->close();
			$this->html->errorMsgSet('Failed to add zip file: ' . $fn);
			die($this->getPageServerList());
			}
		}
	$rc = $zip->close();
	if (!($rc === true)) {
		$this->html->errorMsgSet('Failed to create zip file: ' . $tmpfile);
		die($this->getPageServerList());
		}
	// Stream file to client
	header('Content-Type: application/zip');
	header('Content-Length: ' . filesize($tmpfile));
	header('Content-Disposition: attachment; filename="server-certs.zip"');
	readfile($tmpfile);
	unlink($tmpfile);
	die();
	}

/**
 * Import a server cert
 * @param string $cert - PEM encoded certificate - required
 * @param string $privKey - PEM encoded private key - optional
 * @param string $passPhrase - optional
 * @param string $certRequest - PEM encoded CSR - optional
 * @return bool true on success
 * @return string error message on failures
 */
public function actionServerImport($pemCert=null,$privKey=null,$passPhrase=null,
                                 $certRequest=null) {
	$this->moduleRequired('ca,cert,server');
	// check arguments
	if (!is_string($pemCert) or strlen($pemCert) < 1) {
		return 'Must provide a valid PEM encoded CA certificate.';
		}
	// normalize arguments
	$privKey     = (is_string($privKey) and strlen($privKey) > 0)
	             ? $privKey : false;
	$passPhrase  = (is_string($passPhrase) and strlen($passPhrase) > 0)
	               ? $passPhrase : '';
	$certRequest = (is_string($certRequest) and strlen($certRequest) > 0)
	               ? $certRequest : false;
	// parse the cert
	$pc = $this->cert->parseCert($pemCert);
	if (!is_array($pc)) { return 'Failed to parse certificate.'; }
	$rc = $this->server->meetsImportRequirements($pc);
	if (!($rc === true)) {
		return 'Cert does not meet import requirements: ' . $rc;
		}
	// no self-signed certs
	$isSelfSigned = $this->cert->isCertSigner($pemCert,$pemCert);
	if ($isSelfSigned === true) {
		return 'Will not import self-signed certificates.';
		}
	$rc = $this->cert->parsedCertIsSslServer($pc);
	if (!($rc === true)) {
		return 'The specified cert is not a SSL server certificate.';
		}
	if (!is_numeric($pc['certificate']['serialNumber'])) {
		return 'Invalid certificate serial number.';
		} else {
		$serialNumber = $pc['certificate']['serialNumber'];
		}
	$validFrom = gmdate('Y-m-d H:i:s',
                        $pc['certificate']['validity']['notbefore']);
	if ($validFrom === false) {
		return 'Failed to determine validFrom date.';
		}
	$validTo = gmdate('Y-m-d H:i:s',
                      $pc['certificate']['validity']['notafter']);
	if ($validTo === false) {
		return 'Failed to determine validTo date.';
		}
	// extract needed public key objects
	$pubKeyRes = openssl_pkey_get_public($pemCert);
	if ($pubKeyRes === false) {
		return 'Failed to extract public key.';
		}
	$ar = openssl_pkey_get_details($pubKeyRes);
	if (!is_array($ar) or !isset($ar['key'])) {
		return 'Failed to obtain PEM formatted public key.';
		} else {
		$pubKey = $ar['key'];
		}
	// Locate issuer
	$ca = $this->getSignerId($pemCert);
	if (!is_array($ca) or count($ca) < 1) {
		$issuer = $pc['certificate']['issuer'];
		$m = 'The CA cert that signed this certificate could not be '
		. 'located.  Import the CA Certificate that matches the '
		. 'information listed below and try again.';
		$out = print_r($issuer,true);
		$m .= '<P><PRE>' . $out . '</PRE></P>';
		return $m;
		}
	if (count($ca) > 1) {
		$m = 'This certificate cannot be imported because multiple possible '
		. 'signers exist.';
		return $m;
		}
	$caId = (isset($ca[0]['Id'])) ? $ca[0]['Id'] : false;
	if (!is_numeric($caId) or $caId < 1) {
		return 'Failed to locate issuing CA id.';
		}
	// Validate expiration date of CA cert.  Only warn if the expiration dates
	// don't jive.
	$this->ca->resetProperties();
	if ($this->ca->populateFromDb($caId) === false) {
		return 'Failed to locate issuer information.';
		}
	$caValidTo = $this->ca->getProperty('ValidTo');
	if (substr($validTo,0,10) > substr($caValidTo,0,10)) {
		$m = 'WARNING: The certificate expiration date is invalid, the issuer '
		. 'certficate expires ' . $caValidTo . ', this certificate expires '
		. $validTo . '.';
		$this->html->errorMsgSet($m);
		}
	// Determine the last serial number issued by the ca in case the
	// serial number of the current certificate is higher and we need
	// to bump the ca last serial issued.
	$caLastSerial = $this->ca->getLastSerialIssued($caId);
	if ($caLastSerial === false or !is_numeric($caLastSerial)) {
		return 'Failed to determine CA last serial issued.';
		}
	// Validate the private key
	if (is_string($privKey)) {
		$pKey = openssl_pkey_get_private($privKey,$passPhrase);
		if ($pKey === false) {
			return 'Private key or password is invalid.';
			}
		if (!openssl_x509_check_private_key($pemCert,$pKey)) {
			return 'Private key does not belong to cert.';
			}
		}
	// Did they include a csr?
	if (is_string($certRequest)) {
		$csrPubKey = openssl_csr_get_public_key($certRequest);
		if ($csrPubKey === false) {
			return 'Failed to extract public key from CSR.';
			}
		if (openssl_pkey_get_details($pubKeyRes) !==
	        openssl_pkey_get_details($csrPubKey)) {
			return 'CSR and cert do not match.';
			}
		}
	// Import the cert into the database
	$this->server->resetProperties();
	// required properties
	$this->server->setProperty('Certificate',     $pemCert);
	$this->server->setProperty('CommonName',      implode("\n",$pc['certificate']['subject']['CommonName']));
	$this->server->setProperty('CreateDate',      'now()');
	$this->server->setProperty('Description',     'imported');
	$this->server->setProperty('FingerprintMD5',  $pc['fingerprints']['md5']);
	$this->server->setProperty('FingerprintSHA1', $pc['fingerprints']['sha1']);
	$this->server->setProperty('ParentId',        $caId);
	$this->server->setProperty('PrivateKey',      $privKey);
	$this->server->setProperty('PublicKey',       $pubKey);
	$this->server->setProperty('SerialNumber',    $serialNumber);
	$this->server->setProperty('ValidFrom',       $validFrom);
	$this->server->setProperty('ValidTo',         $validTo);
	// optional properties
	if (is_string($certRequest)) {
		$this->server->setProperty('CSR', $certRequest);
		}
	// optional subject properties
	$sub = $pc['certificate']['subject'];
	if (isset($sub['Country'])) {
		$val = $sub['Country'];
		if (is_array($val) and count($val) > 0) {
			$this->server->setProperty('CountryName',implode("\n",$val));
			}
		}
	if (isset($sub['emailAddress'])) {
		$val = $sub['emailAddress'];
		if (is_array($val) and count($val) > 0) {
			$this->server->setProperty('EmailAddress',implode("\n",$val));
			}
		}
	if (isset($sub['Location'])) {
		$val = $sub['Location'];
		if (is_array($val) and count($val) > 0) {
			$this->server->setProperty('LocalityName',implode("\n",$val));
			}
		}
	if (isset($sub['Organization'])) {
		$val = $sub['Organization'];
		if (is_array($val) and count($val) > 0) {
			$this->server->setProperty('OrgName',implode("\n",$val));
			}
		}
	if (isset($sub['OrganizationalUnit'])) {
		$val = $sub['OrganizationalUnit'];
		if (is_array($val) and count($val) > 0) {
			$this->server->setProperty('OrgUnitName',implode("\n",$val));
			}
		}
	if (isset($sub['stateOrProvinceName'])) {
		$val = $sub['stateOrProvinceName'];
		if (is_array($val) and count($val) > 0) {
			$this->server->setProperty('StateName',implode("\n",$val));
			}
		}
	// Do the deed...
	$this->server->populated = true;
	$rc = $this->server->add();
	if (!($rc === true)) { return 'Import Failed: ' . $rc; }
	// Do we need to bump the CA's last serial issued?
	if ($serialNumber > $caLastSerial) {
		if (!($this->ca->updateSerialByCaId($caId,$serialNumber) === true)) {
			return $m;
			}
		}
	return true;
	}

/**
 * Change encrypted private key pass phrase
 * @param string $certType - ca | client | server
 * @return void
 */
public function changeKeyPass($certType=null) {
	switch($certType) {
		case 'ca':
			$diePage = 'getPageCaView';
			$x509ext = 'v3_ca';
		break;
		case 'client':
			$diePage = 'getPageClientView';
			$x509ext = 'v3_client';
		break;
		case 'server':
			$diePage = 'getPageServerView';
			$x509ext = 'v3_server';
		break;
		default:
			$m = 'Unkown certificate type.';
			die($this->html->getPageError($m));
		break;
		}
	$this->moduleRequired($certType);
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->$diePage());
		}
	$this->$certType->resetProperties();
	if ($this->$certType->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->$diePage());
		}
	// Is the private key already encrypted?
	$pkey = $this->$certType->getProperty('PrivateKey');
	if ($pkey === false) {
		$this->html->errorMsgSet('Failed to locate the private key.');
		die($this->$diePage());
		}
	if (strpos($pkey,'ENCRYPTED') === false) {
		$this->html->errorMsgSet('The private key is not encrypted.');
		die($this->$diePage());
		}
	// Have they entered the old/new passwords?
	$this->html->setPageTitle('Enter Private Key Pass Phrase');
	$this->html->setVar('data',&$this->$certType);
	$keyPass = (isset($_POST['keyPass'])) ? $_POST['keyPass'] : false;
	$newPass = (isset($_POST['newPass'])) ? $_POST['newPass'] : false;
	if (!$keyPass or !$newPass) {
		die($this->html->loadTemplate('change.passphrase.php'));
		}
	// Attempt to decrypt...
	$cfg = array();
	$cfg['config'] = OPENSSL_CONF;
	$cfg['x509_extensions'] = $x509ext;
	$key = openssl_pkey_get_private($pkey,$keyPass);
	if ($key === false) {
		$this->html->errorMsgSet('The specified current pass phrase is invalid.');
		die($this->html->loadTemplate('change.passphrase.php'));
		}
	$rc = openssl_pkey_export($key,$pem,$newPass,$cfg);
	if ($rc === false) {
		$this->html->errorMsgSet('Failed to encrypt private key.');
		die($this->html->loadTemplate('change.passphrase.php'));
		}
	// Update the database entry.
	$this->$certType->setProperty('PrivateKey',$pem);
	$rc = $this->$certType->update();
	if (!($rc === true)) {
		$this->html->errorMsgSet($rc);
		die($this->html->loadTemplate('change.passphrase.php'));
		}
	die($this->$diePage());
	}

/**
 * Convert specified date to number of days
 * @param string $date
 * @return mixed
 */
public function dateToDays($date=null) {
	if (is_null($date) or $date == '') { return false; }
	$ts = strtotime($date);
	if ($ts === false) { return false; }
	$ts_now = time();
	return round(($ts - $ts_now) / (60 * 60 * 24)) ;
	}

/**
 * Decrypt private key of posted certificate id/certificate type
 * @param string $certType - ca | client | server
 * @return void
 */
public function decryptPrivateKey($certType=null) {
	switch($certType) {
		case 'ca':
			$diePage = 'getPageCaView';
			$x509ext = 'v3_ca';
		break;
		case 'client':
			$diePage = 'getPageClientView';
			$x509ext = 'v3_client';
		break;
		case 'csrserver':
			$diePage = 'getPageCsrServerView';
			$x509ext = 'v3_server';
		break;
		case 'server':
			$diePage = 'getPageServerView';
			$x509ext = 'v3_server';
		break;
		default:
			$m = 'Unkown certificate type.';
			die($this->html->getPageError($m));
		break;
		}
	$this->moduleRequired($certType);
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->$diePage());
		}
	$this->$certType->resetProperties();
	if ($this->$certType->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->$diePage());
		}
	// Is the private key even encrypted?
	$pkey = $this->$certType->getProperty('PrivateKey');
	if ($pkey === false) {
		$this->html->errorMsgSet('Failed to locate the private key.');
		die($this->$diePage());
		}
	if (strpos($pkey,'ENCRYPTED') === false) {
		$this->html->errorMsgSet('The private key is not encrypted.');
		die($this->$diePage());
		}
	// Have they entered the password?
	$this->html->setPageTitle('Enter Private Key Pass Phrase');
	$this->html->setVar('data',&$this->$certType);

	$keyPass = (isset($_POST['keyPass'])) ? $_POST['keyPass'] : false;
	if (!is_string($keyPass) or strlen($keyPass) < 1) {
		die($this->html->loadTemplate('get.passphrase.php'));
		}
	// Attempt to decrypt...
	$cfg = array();
	$cfg['config'] = OPENSSL_CONF;
	$cfg['x509_extensions'] = $x509ext;
	$key = openssl_pkey_get_private($pkey,$keyPass);
	if ($key === false) {
		$this->html->errorMsgSet('Invalid password, try again.');
		die($this->html->loadTemplate('get.passphrase.php'));
		}
	$rc = openssl_pkey_export($key,$pem,null,$cfg);
	if ($rc === false) {
		$this->html->errorMsgSet('Failed to decrypt private key.');
		die($this->html->loadTemplate('get.passphrase.php'));
		}
	// Update the database entry.
	$this->$certType->setProperty('PrivateKey',$pem);
	$rc = $this->$certType->update();
	if (!($rc === true)) {
		$this->html->errorMsgSet($rc);
		die($this->html->loadTemplate('get.passphrase.php'));
		}
	die($this->$diePage());
	}

/**
 * Obtain pem CA bundle of issuers for specified certificate id
 * @param string $certType - ca | client | server
 * @return void
 */
public function downloadCaBundle($certType=null) {
	switch($certType) {
		case 'ca':
			$diePage = 'getPageCaView';
		break;
		case 'client':
			$diePage = 'getPageClientView';
		break;
		case 'server':
			$diePage = 'getPageServerView';
		break;
		default:
			$m = 'Unkown certificate type.';
			die($this->html->getPageError($m));
		break;
		}
	$this->moduleRequired($certType);
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->$diePage());
		}
	$this->$certType->resetProperties();
	if ($this->$certType->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->$diePage());
		}
	$pid = $this->$certType->getProperty('ParentId');
	if (!is_numeric($pid) or $pid < 1) {
		$this->html->errorMsgSet('Failed to locate issuer id.');
		die($this->$diePage());
		}
	$bundle = $this->getCaBundle($pid);
	if (!is_string($bundle)) {
		$this->html->errorMsgSet('Failed to obtain CA Chain.');
		die($this->$diePage());
		}
	die($this->uploadCaBundle($bundle));
	}

/**
 * Encrypt private key of posted certificate id/certificate type
 * @param string $certType - ca | client | server
 * @return void
 */
public function encryptPrivateKey($certType=null) {
	switch($certType) {
		case 'ca':
			$diePage = 'getPageCaView';
			$x509ext = 'v3_ca';
		break;
		case 'client':
			$diePage = 'getPageClientView';
			$x509ext = 'v3_client';
		break;
		case 'csrserver':
			$diePage = 'getPageCsrServerView';
			$x509ext = 'v3_server';
		break;
		case 'server':
			$diePage = 'getPageServerView';
			$x509ext = 'v3_server';
		break;
		default:
			$m = 'Unkown certificate type.';
			die($this->html->getPageError($m));
		break;
		}
	$this->moduleRequired($certType);
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->$diePage());
		}
	$this->$certType->resetProperties();
	if ($this->$certType->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->$diePage());
		}
	// Is the private key already encrypted?
	$pkey = $this->$certType->getProperty('PrivateKey');
	if ($pkey === false) {
		$this->html->errorMsgSet('Failed to locate the private key.');
		die($this->$diePage());
		}
	if (!(strpos($pkey,'ENCRYPTED') === false)) {
		$this->html->errorMsgSet('The private key is already encrypted.');
		die($this->$diePage());
		}
	// Have they entered the password?
	$this->html->setPageTitle('Enter Private Key Pass Phrase');
	$this->html->setVar('data',&$this->$certType);

	$keyPass = (isset($_POST['keyPass'])) ? $_POST['keyPass'] : false;
	if (!is_string($keyPass) or strlen($keyPass) < 1) {
		die($this->html->loadTemplate('get.passphrase.php'));
		}
	// Attempt to decrypt...
	$cfg = array();
	$cfg['config'] = OPENSSL_CONF;
	$cfg['x509_extensions'] = $x509ext;
	$key = openssl_pkey_get_private($pkey,null);
	if ($key === false) {
		$this->html->errorMsgSet('Failed to obtain private key.');
		die($this->html->loadTemplate('get.passphrase.php'));
		}
	$rc = openssl_pkey_export($key,$pem,$keyPass,$cfg);
	if ($rc === false) {
		$this->html->errorMsgSet('Failed to encrypt private key.');
		die($this->html->loadTemplate('get.passphrase.php'));
		}
	// Update the database entry.
	$this->$certType->setProperty('PrivateKey',$pem);
	$rc = $this->$certType->update();
	if (!($rc === true)) {
		$this->html->errorMsgSet($rc);
		die($this->html->loadTemplate('get.passphrase.php'));
		}
	die($this->$diePage());
	}

/**
 * Load a menu if it exists
 * @param string $menu
 * @return void
 */
public function getMenu($menu=null) {
	global $_WA;
	if (!$this->isMenu($menu)) { die('access denied'); }
	if (is_file(WEBAPP_API . '/strings.' . $menu . '.php')) {
		include(WEBAPP_API . '/strings.' . $menu . '.php');
		}
	if (is_file(WEBAPP_API . '/functions.' . $menu . '.php')) {
		include(WEBAPP_API . '/functions.' . $menu . '.php');
		}
	die(include(WEBAPP_DIR . '/index.' . $menu . '.php'));
	}

/**
 * Import (upload) a CA certificate to the browser
 * @return void
 */
public function getPageCaBrowserImport() {
	$this->html->setPageTitle('CA Certificate Browser Import');
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->html->loadTemplate('ca.view.php'));
		}
	$this->moduleRequired('ca');
	$this->ca->resetProperties();
	if ($this->ca->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->html->loadTemplate('ca.view.php'));
		}
	$content = $this->ca->getProperty('Certificate');
	header('Content-Type: application/x-x509-ca-cert');
	header('Content-Length: ' . strlen($content));
	die($content);
	}

/**
 * Get list of ca certificates
 * @return void
 */
public function getPageCaList() {
	$this->html->setPageTitle('List CA Certificates');
	$this->moduleRequired('ca');
	$this->ca->searchReset();
	// apply user input (search, sort)
	$this->setUserInput(&$this->ca);
	// do the query
	$this->ca->doQueryList();
	$this->html->setVar('list',&$this->ca);
	die($this->html->loadTemplate('ca.list.php'));
	}

/**
 * Process requests to obtain CA pkcs12 file.
 * @return void
 */
function getPageCaPkcs12() {
	$this->html->setPageTitle('Get PKCS12 Certificate');
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->html->loadTemplate('ca.view.php'));
		}
	$this->moduleRequired('ca');
	$this->ca->resetProperties();
	if ($this->ca->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->html->loadTemplate('ca.view.php'));
		}
	$this->html->setVar('data',&$this->ca);
	// Have they been given the chance to enter the private key password?
	$conf    = (isset($_POST[WA_QS_CONFIRM])) ? $_POST[WA_QS_CONFIRM] : false;
	$keyPass = (isset($_POST['keyPass'])) ? $_POST['keyPass']         : null;
	$expPass = (isset($_POST['expPass'])) ? $_POST['expPass']         : false;
	if ($conf !== 'yes' or $expPass === false) {
		die($this->html->loadTemplate('ca.pkcs12.php'));
		}
	// Get down to bidness
	$cert = $this->ca->getProperty('Certificate');
	$pk   = $this->ca->getProperty('PrivateKey');
	// Get and decrypt the private key...
	$pkey = openssl_pkey_get_private($pk,$keyPass);
	if ($pkey === false) {
		$this->html->errorMsgSet('Invalid pass phrase for private key.');
		die($this->html->loadTemplate('ca.pkcs12.php'));
		}
	// Extra args - name of certificate for import and chain CA certificates
	$certs = array();
	$certName = 'CA Certificate - ' . $this->ca->getProperty('CommonName');
	// Obtain chain of issuer certificate ids.
	$issuerIds = $this->ca->getCaChainIds($this->ca->getProperty('ParentId'));
	if (is_array($issuerIds) and count($issuerIds) > 0) {
		foreach($issuerIds as $id) {
			$pem = $this->ca->getPemCertById($id);
			if (is_string($pem)) {
				$certs[] = trim($pem);
				}
			}
		}
	if (is_array($certs) and count($certs) > 0) {
		$certs = implode("\n",$certs);
		} else {
		$certs = '';
		}
	$extraArgs = array('extracerts' => $certs, 'friendly_name' => $certName);
	$rc = openssl_pkcs12_export($cert,$pkcs12,$pkey,$expPass,$extraArgs);
	if (!($rc === true)) {
		$this->html->errorMsgSet('Failed to export PKCS12 Certficate Store.');
		die($this->html->loadTemplate('ca.pkcs12.php'));
		}
	header('Pragma: private');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Cache-Control: private');
	header('Content-Description: File Transfer');
	header('Content-Type: application/x-pkcs12');
	header('Content-Disposition: attachment; filename="ca.p12"');
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: ' . strlen($pkcs12));
	die($pkcs12);
	}

/**
 * Revoke a CA certificate.
 * @return void
 */
public function getPageCaRevoke() {
	$this->html->setPageTitle('Revoke CA Certificate');
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->getPageCaView());
		}
	$this->moduleRequired('ca,client,server');
	$this->ca->resetProperties();
	if ($this->ca->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->getPageCaView());
		}
	// Is it already revoked?
	if ($this->ca->isRevoked()) {
		$this->html->errorMsgSet('The certificate is already revoked.');
		die($this->getPageCaView());
		}
	// Is it already expired?
	if ($this->ca->isExpired()) {
		$this->html->errorMsgSet('Certificate is expired, will not revoke.');
		die($this->getPageCaView());
		}
	// Get list of other ca certs this ca has signed.
	$caCerts = $this->ca->getIssuerSubjects($id);
	if (!is_array($caCerts)) {
		$msg = 'Failed to query CA certs signed by this CA, will not '
		     . 'continue.';
		$this->html->errorMsgSet($msg);
		die($this->getPageCaView());
		}
	$this->html->setVar('caCerts',&$caCerts);
	// Get list of client certs this ca has signed
	$clientCerts = $this->client->getIssuerSubjects($id);
	if (!is_array($clientCerts)) {
		$msg = 'Failed to query client certs signed by this CA, will not '
		     . 'continue.';
		$this->html->errorMsgSet($msg);
		die($this->getPageCaView());
		}
	$this->html->setVar('clientCerts',&$clientCerts);
	// Get list of server certs this ca has signed
	$serverCerts = $this->server->getIssuerSubjects($id);
	if (!is_array($serverCerts)) {
		$msg = 'Failed to query server certs signed by this CA, will not '
		     . 'continue.';
		$this->html->errorMsgSet($msg);
		die($this->getPageCaView());
		}
	$this->html->setVar('serverCerts',&$serverCerts);
	// Have they confirmed?
	if ($this->html->getRequestVar(WA_QS_CONFIRM) !== 'yes') {
		$this->html->setVar('data',&$this->ca);
		die($this->html->loadTemplate('ca.revoke.confirm.php'));
		}
	// Get on wit it
	$this->ca->setProperty('RevokeDate','now()');
	$rc = $this->ca->update();
	if (!($rc === true)) {
		$this->html->errorMsgSet($rc);
		}
	die($this->getPageCaView());
	}

/**
 * View a CA certificate.
 * @return void
 */
public function getPageCaView() {
	$this->html->setPageTitle('View CA Certificate');
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->html->loadTemplate('ca.view.php'));
		}
	$this->moduleRequired('ca,server,client');
	$this->ca->resetProperties();
	if ($this->ca->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->html->loadTemplate('ca.view.php'));
		}
	// locate the issuer if this is not self signed.
	$pid = $this->ca->getProperty('ParentId');
	$issuer = false;
	if ($pid > 0) {
		$issuer = new $this->classCa();
		$issuer->setDatabaseHost(WEBAPP_DB_HOST);
		$issuer->setDatabase(WEBAPP_DB_NAME);
		$issuer->setDatabaseUser(WEBAPP_DB_USER);
		$issuer->setDatabasePass(WEBAPP_DB_PASS);
		$issuer->resetProperties();
		if ($issuer->populateFromDb($pid) === false) {
			$this->html->errorMsgSet('Failed to locate issuer information.');
			die($this->html->loadTemplate('ca.view.php'));
			}
		}
	// query all of the ca certs this ca has signed...
	$this->ca->searchReset();
	$this->ca->setSearchSelect('Id');
	$this->ca->setSearchSelect('CommonName');
	$this->ca->setSearchSelect('ValidTo');
	$this->ca->setSearchOrder('CommonName');
	$this->ca->setSearchFilter('ParentId',$id);
	$signedCaCerts = $this->ca->query();
	// query all of the client certs this ca has signed...
	$this->client->searchReset();
	$this->client->setSearchSelect('Id');
	$this->client->setSearchSelect('CommonName');
	$this->client->setSearchSelect('ValidTo');
	$this->client->setSearchOrder('CommonName');
	$this->client->setSearchFilter('ParentId',$id);
	$signedClientCerts = $this->client->query();
	// query all of the server certs this ca has signed...
	$this->server->searchReset();
	$this->server->setSearchSelect('Id');
	$this->server->setSearchSelect('CommonName');
	$this->server->setSearchSelect('ValidTo');
	$this->server->setSearchOrder('CommonName');
	$this->server->setSearchFilter('ParentId',$id);
	$signedServerCerts = $this->server->query();
	$this->html->setVar('data',&$this->ca);
	$this->html->setVar('issuer',&$issuer);
	$this->html->setVar('signedCaCerts',&$signedCaCerts);
	$this->html->setVar('signedClientCerts',&$signedClientCerts);
	$this->html->setVar('signedServerCerts',&$signedServerCerts);
	die($this->html->loadTemplate('ca.view.php'));
	}

/**
 * Get list of client certificates
 * @return void
 */
public function getPageClientList() {
	$this->html->setPageTitle('List Client Certificates');
	$this->moduleRequired('client');
	$this->client->searchReset();
	// apply user input (search, sort)
	$this->setUserInput(&$this->client);
	// do the query
	$this->client->doQueryList();
	$this->html->setVar('list',&$this->client);
	die($this->html->loadTemplate('client.list.php'));
	}

/**
 * Process requests to obtain pkcs12 file.
 * @return void
 */
function getPageClientPkcs12() {
	$this->html->setPageTitle('Get PKCS12 Certificate');
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->html->loadTemplate('client.view.php'));
		}
	$this->moduleRequired('client,ca');
	$this->client->resetProperties();
	if ($this->client->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->html->loadTemplate('client.view.php'));
		}
	$this->html->setVar('data',&$this->client);
	// Have they been given the chance to enter the private key password?
	$conf    = (isset($_POST[WA_QS_CONFIRM])) ? $_POST[WA_QS_CONFIRM] : false;
	$keyPass = (isset($_POST['keyPass'])) ? $_POST['keyPass']         : null;
	$expPass = (isset($_POST['expPass'])) ? $_POST['expPass']         : false;
	if ($conf !== 'yes' or $expPass === false) {
		die($this->html->loadTemplate('client.pkcs12.php'));
		}
	// Get down to bidness
	$cert = $this->client->getProperty('Certificate');
	$pk   = $this->client->getProperty('PrivateKey');
	// Get and decrypt the private key...
	$pkey = openssl_pkey_get_private($pk,$keyPass);
	if ($pkey === false) {
		$this->html->errorMsgSet('Invalid pass phrase for private key.');
		die($this->html->loadTemplate('client.pkcs12.php'));
		}
	// Extra args - name of certificate for import and chain CA certificates
	$certs = array();
	$certName = 'Client Certificate - ' . $this->client->getProperty('CommonName');
	// Obtain chain of issuer certificate ids.
	$issuerIds = $this->ca->getCaChainIds($this->client->getProperty('ParentId'));
	if (is_array($issuerIds) and count($issuerIds) > 0) {
		foreach($issuerIds as $id) {
			$pem = $this->ca->getPemCertById($id);
			if (is_string($pem)) {
				$certs[] = trim($pem);
				}
			}
		}
	if (is_array($certs) and count($certs) > 0) {
		$certs = implode("\n",$certs);
		} else {
		$certs = '';
		}
	$extraArgs = array('extracerts' => $certs, 'friendly_name' => $certName);
	$rc = openssl_pkcs12_export($cert,$pkcs12,$pkey,$expPass,$extraArgs);
	if (!($rc === true)) {
		$this->html->errorMsgSet('Failed to export PKCS12 Certficate Store.');
		die($this->html->loadTemplate('client.pkcs12.php'));
		}
	header('Pragma: private');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Cache-Control: private');
	header('Content-Description: File Transfer');
	header('Content-Type: application/x-pkcs12');
	header('Content-Disposition: attachment; filename="client.p12"');
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: ' . strlen($pkcs12));
	die($pkcs12);
	}

/**
 * Revoke a client certificate.
 * @return void
 */
public function getPageClientRevoke() {
	$this->html->setPageTitle('Revoke Client Certificate');
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->getPageClientView());
		}
	$this->moduleRequired('client');
	$this->client->resetProperties();
	if ($this->client->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->getPageClientView());
		}
	// Is it already revoked?
	if ($this->client->isRevoked()) {
		$this->html->errorMsgSet('The certificate is already revoked.');
		die($this->getPageClientView());
		}
	// Is it already expired?
	if ($this->client->isExpired()) {
		$this->html->errorMsgSet('Certificate is expired, will not revoke.');
		die($this->getPageClientView());
		}
	// Have they confirmed?
	if ($this->html->getRequestVar(WA_QS_CONFIRM) !== 'yes') {
		$this->html->setVar('data',&$this->client);
		die($this->html->loadTemplate('client.revoke.confirm.php'));
		}
	// Get on wit it
	$this->client->setProperty('RevokeDate','now()');
	$rc = $this->client->update();
	if (!($rc === true)) {
		$this->html->errorMsgSet($rc);
		}
	die($this->getPageClientView());
	}

/**
 * View a client certificate.
 * @return void
 */
public function getPageClientView() {
	$this->html->setPageTitle('View Client Certificate');
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->html->loadTemplate('client.view.php'));
		}
	$this->moduleRequired('ca,client');
	// locate client cert
	$this->client->resetProperties();
	if ($this->client->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->html->loadTemplate('client.view.php'));
		}
	// locate issuer cert
	$this->ca->resetProperties();
	$pid = $this->client->getProperty('ParentId');
	if ($this->ca->populateFromDb($pid) === false) {
		$this->html->errorMsgSet('Failed to locate issuer information.');
		die($this->html->loadTemplate('client.view.php'));
		}
	$this->html->setVar('data',&$this->client);
	$this->html->setVar('issuer',&$this->ca);
	die($this->html->loadTemplate('client.view.php'));
	}

/**
 * Get list of server certificate requests
 * @return void
 */
public function getPageCsrServerList() {
	$this->html->setPageTitle('List Server Certificate Requests');
	$this->moduleRequired('csrserver');
	$this->csrserver->searchReset();
	// apply user input (search, sort)
	$this->setUserInput(&$this->csrserver);
	// do the query
	$this->csrserver->doQueryList();
	$this->html->setVar('list',&$this->csrserver);
	die($this->html->loadTemplate('csr.server.list.php'));
	}

/**
 * View a server certificate request
 * @return void
 */
public function getPageCsrServerView() {
	$this->html->setPageTitle('View Server Certificate Request');
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate request id.');
		die($this->html->loadTemplate('csr.server.view.php'));
		}
	$this->moduleRequired('csrserver');
	$this->csrserver->resetProperties();
	if ($this->csrserver->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate request: ' . $id);
		die($this->html->loadTemplate('csr.server.view.php'));
		}
	$this->html->setVar('data',&$this->csrserver);
	die($this->html->loadTemplate('csr.server.view.php'));
	}

/**
 * Import (upload) a server certificate to the browser
 * @return void
 */
public function getPageServerBrowserImport() {
	$this->html->setPageTitle('Server Certificate Import');
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->html->loadTemplate('server.view.php'));
		}
	$this->moduleRequired('server');
	$this->server->resetProperties();
	if ($this->server->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->html->loadTemplate('server.view.php'));
		}
	$content = $this->server->getProperty('Certificate');
	header('Content-Description: File Transfer');
	header('Content-Disposition: attachment; filename="server.cer"');
	header('Content-Type: application/x-x509-ca-cert');
	header('Content-Length: ' . strlen($content));
	die($content);
	}

/**
 * Get list of server certificates
 * @return void
 */
public function getPageServerList() {
	$this->html->setPageTitle('List Server Certificates');
	$this->moduleRequired('server');
	$this->server->searchReset();
	// apply user input (search, sort)
	$this->setUserInput(&$this->server);
	// do the query
	$this->server->doQueryList();
	$this->html->setVar('list',&$this->server);
	die($this->html->loadTemplate('server.list.php'));
	}

/**
 * Process requests to obtain pkcs12 file.
 * @return void
 */
function getPageServerPkcs12() {
	$this->html->setPageTitle('Get PKCS12 Certificate');
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->html->loadTemplate('client.view.php'));
		}
	$this->moduleRequired('server,ca');
	$this->server->resetProperties();
	if ($this->server->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->html->loadTemplate('server.view.php'));
		}
	$this->html->setVar('data',&$this->server);
	// Have they been given the chance to enter the private key password?
	$conf    = (isset($_POST[WA_QS_CONFIRM])) ? $_POST[WA_QS_CONFIRM] : false;
	$keyPass = (isset($_POST['keyPass'])) ? $_POST['keyPass']         : null;
	$expPass = (isset($_POST['expPass'])) ? $_POST['expPass']         : false;
	if ($conf !== 'yes' or $expPass === false) {
		die($this->html->loadTemplate('server.pkcs12.php'));
		}
	// Get down to bidness
	$cert = $this->server->getProperty('Certificate');
	$pk   = $this->server->getProperty('PrivateKey');
	// Get and decrypt the private key...
	$pkey = openssl_pkey_get_private($pk,$keyPass);
	if ($pkey === false) {
		$this->html->errorMsgSet('Invalid pass phrase for private key.');
		die($this->html->loadTemplate('server.pkcs12.php'));
		}
	// Extra args - name of certificate for import and chain CA certificates
	$certs = array();
	$serverName = $this->server->getProperty('CommonName');
	$certName = 'Server Certificate - ' . $serverName;
	// Obtain chain of issuer certificate ids.
	$issuerIds = $this->ca->getCaChainIds($this->server->getProperty('ParentId'));
	if (is_array($issuerIds) and count($issuerIds) > 0) {
		foreach($issuerIds as $id) {
			$pem = $this->ca->getPemCertById($id);
			if (is_string($pem)) {
				$certs[] = trim($pem);
				}
			}
		}
	if (is_array($certs) and count($certs) > 0) {
		$certs = implode("\n",$certs);
		} else {
		$certs = '';
		}
	$extraArgs = array('extracerts' => $certs, 'friendly_name' => $certName);
	$rc = openssl_pkcs12_export($cert,$pkcs12,$pkey,$expPass,$extraArgs);
	if (!($rc === true)) {
		$this->html->errorMsgSet('Failed to export PKCS12 Certficate Store.');
		die($this->html->loadTemplate('server.pkcs12.php'));
		}
	header('Pragma: private');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Cache-Control: private');
	header('Content-Description: File Transfer');
	header('Content-Type: application/x-pkcs12');
	header('Content-Disposition: attachment; filename="' . $serverName . '.p12"');
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: ' . strlen($pkcs12));
	die($pkcs12);
	}

/**
 * Revoke a server certificate.
 * @return void
 */
public function getPageServerRevoke() {
	$this->html->setPageTitle('Revoke Server Certificate');
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->getPageServerView());
		}
	$this->moduleRequired('server');
	$this->server->resetProperties();
	if ($this->server->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->getPageServerView());
		}
	// Is it already revoked?
	if ($this->server->isRevoked()) {
		$this->html->errorMsgSet('The certificate is already revoked.');
		die($this->getPageServerView());
		}
	// Is it already expired?
	if ($this->server->isExpired()) {
		$this->html->errorMsgSet('Certificate is expired, will not revoke.');
		die($this->getPageServerView());
		}
	// Have they confirmed?
	if ($this->html->getRequestVar(WA_QS_CONFIRM) !== 'yes') {
		$this->html->setVar('data',&$this->server);
		die($this->html->loadTemplate('server.revoke.confirm.php'));
		}
	// Get on wit it
	$this->server->setProperty('RevokeDate','now()');
	$rc = $this->server->update();
	if (!($rc === true)) {
		$this->html->errorMsgSet($rc);
		}
	die($this->getPageServerView());
	}

/**
 * View a server certificate.
 * @return void
 */
public function getPageServerView() {
	$this->html->setPageTitle('View Server Certificate');
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->html->loadTemplate('server.view.php'));
		}
	$this->moduleRequired('ca,server');
	$this->server->resetProperties();
	if ($this->server->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->html->loadTemplate('server.view.php'));
		}
	$this->ca->resetProperties();
	$pid = $this->server->getProperty('ParentId');
	if ($this->ca->populateFromDb($pid) === false) {
		$this->html->errorMsgSet('Failed to locate issuer information.');
		die($this->html->loadTemplate('server.view.php'));
		}
	$this->html->setVar('data',&$this->server);
	$this->html->setVar('issuer',&$this->ca);
	die($this->html->loadTemplate('server.view.php'));
	}

/**
 * Generate needed information to display the welcome page.
 * @return void
 */
public function getPageWelcome() {
	$this->html->setPageTitle('phpMyCA Main Menu');
	die($this->html->loadTemplate('welcome.php'));
	}

/**
 * Is the requested menu valid?
 * @param string $menu
 * @return bool
 */
public function isMenu($menu=null) {
	if (!is_string($menu)) { return false; }
	if (!(strpos($menu,'/') === false)) { return false; }
	return (is_file(WEBAPP_DIR . '/index.' . $menu . '.php'));
	}

/**
 * Attempt to load specified module(s) and die on any failures
 * Accepts single module name, comma separated list of module names, or array
 * of module names.  If a failure is experienced loading any of the modules,
 * a die() is called with an error message.
 * @param mixed $modules
 * @return void
 */
public function moduleRequired($modules=null) {
	$msg = 'FATAL ERROR: REQUIRED WEBAPP MODULE MIA';
	if (empty($modules)) { die($msg); }
	if (!is_array($modules)) {
		$modules = explode(',',$modules);
		}
	if (!is_array($modules) or count($modules) < 1) {
		die($msg);
		}
	foreach($modules as $module) {
		if (!($this->moduleLoad($module) === true)) {
			die($msg . ': ' . $module);
			}
		}
	}

/**
 * Populate a form in the top frame with data from specified CA.
 * @return void
 */
public function populateCaFormData() {
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) { die('INVALID ID'); }
	$this->moduleRequired('ca');
	$this->ca->resetProperties();
	$data = $this->ca->queryById($id);
	if (!is_array($data)) { die('INVALID ID'); }
	// these are the properties we will populate.
	$props = array('OrgName','OrgUnitName','LocalityName','StateName',
	'CountryName');
	$h = array();
	$h[] = '<script type="text/javascript">';
	foreach($props as $prop) {
		$val = (isset($data[$prop])) ? $data[$prop] : '';
		if (!is_string($val)) { $val = ''; }
		$h[] = 'top.populateField("' . $prop . '","' . $val . '");';
		}
	// try to fill in the total days...
	$days = $this->dateToDays($data['ValidTo']);
	if (is_numeric($days) and $days > 2) {
		$days--;
		} else {
		$days = '';
		}
	$h[] = 'top.populateField("Days","' . $days . '");';
	$h[] = '</script>';
	die(implode("\n",$h) . "\n");
	}

/**
 * If the webapp has not been minimally instantiated, die with an error message
 * @return void
 */
public function requireWebapp() {
	if (!($this->webappLoaded === true)) {
		die('FATAL ERROR: WEBAPP IS MIA');
		}
	}

/**
 * Obtain chain of PEM issuer certs for specified CA id.
 * @param int $caId
 * @return mixed
 * @see uploadCaBundle()
 */
private function getCaBundle($caId=null) {
	if (!is_numeric($caId) or $caId < 1) { return false; }
	$this->moduleRequired('ca');
	$issuerIds = $this->ca->getCaChainIds($caId);
	if (!is_array($issuerIds) or count($issuerIds) < 1) { return false; }
	$certs = array();
	foreach($issuerIds as $id) {
		$pem = $this->ca->getPemCertById($id);
		if (is_string($pem)) {
			$certs[] = trim($pem);
			}
		}
	if (count($certs) < 1) { return false; }
	return implode("\n",$certs) . "\n";
	}

/**
 * Locate the signer id of a certificate
 * @param string $pemCert
 * @return int ca_id on success
 * @return bool false on failures
 */
private function getSignerId(&$pemCert=null) {
	$this->moduleRequired('ca,cert');
	if (!is_string($pemCert)) { return false; }
	// Look up all CA certs
	$this->ca->searchReset();
	$this->ca->setSearchSelect('Id');
	$this->ca->setSearchSelect('Certificate');
	$this->ca->setSearchSelect('CommonName');
	$this->ca->setSearchSelect('OrgName');
	$this->ca->setSearchSelect('OrgUnitName');
	$this->ca->setSearchOrder('OrgName');
	$caCerts = $this->ca->query();
	if (!is_array($caCerts) or count($caCerts) < 1) { return false; }
	// In an ideal world every ca cert would be unique, but we don't live
	// in that world.  We might have multiple ca's that can sign the same
	// cert, so we store all hits in an array to return the candidates.
	$hits = array();
	foreach($caCerts as $row) {
		if ($this->cert->isCertSigner($pemCert,$row['Certificate']) === true) {
			$hits[] = $row;
			}
		}
	return (count($hits) > 0) ? $hits : false;
	}

/**
 * Load a webapp module
 * @param string $module
 * @return bool
 */
private function moduleLoad($module=null) {
	$db_required = false;
	$inc = false;
	switch($module) {
		case 'ca':
			$o = 'ca';
			$class = $this->classCa;
			$db_required = true;
			$inc = WEBAPP_API . '/ca.php';
		break;
		case 'cert':
			$o = 'cert';
			$class = $this->classCert;
			$inc = WEBAPP_API . '/cert.php';
		break;
		case 'client':
			$o = 'client';
			$class = $this->classClient;
			$inc = WEBAPP_API . '/client.php';
			$db_required = true;
		break;
		case 'csrserver':
			$o = 'csrserver';
			$class = $this->classCsrServer;
			$inc = WEBAPP_API . '/csr.server.php';
			$db_required = true;
		break;
		case 'html':
			$o = 'html';
			$class = $this->classHtml;
			$inc = WEBAPP_API . '/html.php';
		break;
		case 'server':
			$o = 'server';
			$class = $this->classServer;
			$inc = WEBAPP_API . '/server.php';
			$db_required = true;
		break;
		default:
			return false;
		break;
		}
	if (!isset($o) or !is_string($o)) { return false; }
	if (!isset($class) or !is_string($class)) { return false; }
	if (is_a($this->$o,$class)) { return true; }
	// Require the include if needed...
	if (is_string($inc) and is_file($inc)) { require($inc); }
	if (!class_exists($class,false))   { return false; }
	if (!property_exists($this,$o)) { return false; }
	$this->$o = new $class();
	if ($db_required === true) {
		$this->$o->setDatabaseHost(WEBAPP_DB_HOST);
		$this->$o->setDatabase(WEBAPP_DB_NAME);
		$this->$o->setDatabaseUser(WEBAPP_DB_USER);
		$this->$o->setDatabasePass(WEBAPP_DB_PASS);
		}
	if (!is_object($this->$o)) { return false; }
	return true;
	}

/**
 * Validate required settings or die()
 * @return void
 */
private function sanityCheck() {
	$err = 'FATAL ERROR: ';
	// required by webapp
	if (!defined('WEBAPP_DIR'))            { die($err . 'WEBAPP_DIR IS MIA');    }
	if (!defined('WEBAPP_API'))            { die($err . 'WEBAPP_API IS MIA');    }
	if (!defined('WEBAPP_TMP'))            { die($err . 'WEBAPP_TMP IS MIA');    }
	if (!defined('WA_QS_ACTION'))          { die($err . 'WA_QS_ACTION IS MIA');    }
	// misc
	if (!defined('WA_MAX_ROWS'))           { die($err . 'WA_MAX_ROWS IS MIA'); }
	if (!is_numeric(WA_MAX_ROWS) or WA_MAX_ROWS < 0) {
		die($err . 'WA_MAX ROWS IS INVALID');
		}
	}

/**
 * Apply user search input to specified phpdboform object
 */
private function setUserInput(&$obj=null) {
	if (!is_a($obj,'phpdboform')) { return false; }
	$p = $this->html->crumbGet(WA_QS_PAGE);
	if (is_numeric($p) and $p > 0) {
		$obj->pageCurrent = $p;
		} else {
		$obj->pageCurrent = 1;
		}
	// order
	$o = $this->html->crumbGet(WA_QS_ORDER);
	// sort
	$s = ($this->html->crumbGet(WA_QS_SORT) == 'd') ? 'DESC' : 'ASC';
	if (is_numeric($o)) {
		if (is_array($obj->_propertiesList) and
		    array_key_exists($o,$obj->_propertiesList)) {
			$obj->setSearchOrder($obj->_propertiesList[$o],$s);
			}
		}
	// limits
	$obj->hitsMax = WA_MAX_ROWS;
	// searches
	if ($this->html->searches > 0) {
		for($j=0; $j < $this->html->searches; $j++) {
			$field = $this->html->getSearchField($j);
			$type  = $this->html->getSearchType($j);
			$term  = $this->html->getSearchTerm($j);
			if ($field == '' or $type == '' or $term == '') { continue; }
			if (!$obj->isProperty($field)) { continue; }
			switch($type) {
				case 'contains':
					$term = '%' . $term . '%';
					$obj->setSearchFilter($field,$term,'like');
				break;
				case 'equals':
					$obj->setSearchFilter($field,$term);
				break;
				case 'begins with':
					$term = $term . '%';
					$obj->setSearchFilter($field,$term,'like');
				break;
				case 'ends with':
					$term = $term . '%';
					$obj->setSearchFilter($field,$term,'like');
				break;
				case 'less than':
					$obj->setSearchFilter($field,$term,'<');
				break;
				case 'greater than':
					$obj->setSearchFilter($field,$term,'>');
				break;
				case 'does not equal':
					$obj->setSearchFilter($field,$term,'!=');
				break;
				case 'does not contain':
					$term = '%' . $term . '%';
					$obj->setSearchFilter($field,$term,'not like');
				break;
				case 'does not begin with':
					$term = $term . '%';
					$obj->setSearchFilter($field,$term,'not like');
				break;
				case 'does not end with':
					$term = '%' . $term;
					$obj->setSearchFilter($field,$term,'not like');
				break;
				default:
					continue;
				break;
				}
			}
		}
	return true;
	}

/**
 * Send bundle of pem certs to browser for download.
 * @param string $bundle
 * @return mixed
 */
private function uploadCaBundle($bundle=null) {
	if (!is_string($bundle) or strlen($bundle) < 1) { return false; }
	header('Pragma: private');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Cache-Control: private');
	header('Content-Description: File Transfer');
	header('Content-Type: application/x-x509-ca-cert');
	header('Content-Disposition: attachment; filename="CAChain.pem"');
	header('Content-Length: ' . strlen($bundle));
	die($bundle);
	}

/**
 * Send CSR to browser for download
 * @param string certType: ca | client | server | csrserver
 * @return void
 */
public function uploadCertificateRequest($certType=null) {
	switch($certType) {
		case 'ca':
			$diePage = 'getPageCaView';
		break;
		case 'client':
			$diePage = 'getPageClientView';
		break;
		case 'csrserver':
			$diePage = 'getPageCsrServerView';
		break;
		case 'server':
			$diePage = 'getPageServerView';
		break;
		default:
			$m = 'Unkown certificate type.';
			die($this->html->getPageError($m));
		break;
		}
	$this->moduleRequired($certType);
	$id = $this->html->crumbGet(WA_QS_ID);
	if (!is_numeric($id) or $id < 1) {
		$this->html->errorMsgSet('Must specify valid certificate id.');
		die($this->$diePage());
		}
	$this->$certType->resetProperties();
	if ($this->$certType->populateFromDb($id) === false) {
		$this->html->errorMsgSet('Failed to locate the specified certificate.');
		die($this->$diePage());
		}
	$csr = $this->$certType->getProperty('CSR');
	if (!is_string($csr) or strlen($csr) < 1) {
		$this->html->errorMsgSet('CSR could not be located.');
		die($this->$diePage());
		}
	header('Pragma: private');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Cache-Control: private');
	header('Content-Description: File Transfer');
	header('Content-Type: application/x-x509-ca-cert');
	header('Content-Disposition: attachment; filename="server.csr"');
	header('Content-Length: ' . strlen($csr));
	die($csr);
	}
}
?>
