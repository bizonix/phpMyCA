<?
/**
 * phpmyca - certificate utilities functions
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

/**
 * Process request and draw page to examine a cert.
 * @return void
 */
function getPageCertView() {
	global $_WA;
	$_WA->html->setPageTitle('Examine Certificate');
	// Prepopulate data we will be pulling
	$cert_asn     = false;
	$cert_subject = false;
	$cert_key     = false;
	$cert_parse   = false;
	$cert_host    = false;
	$cert_pem     = false;
	$pem = false;
	// Did they specify a hostname:port?  Attempt to fetch it.
	if (isset($_POST['cert_host']) and strlen($_POST['cert_host']) > 0) {
		$cert_host = $_POST['cert_host'];
		$pem = getPemFromHost($_POST['cert_host']);
		}
	// Check to see if they have provided a file.
	if (!is_string($pem)) {
		$pem = $_WA->html->parseCertificate('cert_file','cert');
		}
	// Ok, did they provide a hostname port, or file/cut-and-paste?
	if (is_string($pem)) {
		$_WA->moduleRequired('cert');
		$cert_subject = openssl_x509_parse($pem,false);
		$cert_parse = $_WA->cert->parseCert($pem);
		$key = openssl_pkey_get_public($pem);
		if (is_resource($key)) {
			$cert_key = openssl_pkey_get_details($key);
			}
		$der = $_WA->cert->pemToDer($pem);
		if (!($der === false)) {
			$asn = $_WA->cert->parseAsn($der);
			if (is_array($asn)) {
				$cert_asn = $asn;
				}
			unset($asn);
			}
		unset($der);
		}
	$_WA->html->setVar('cert_asn',     &$cert_asn);
	$_WA->html->setVar('cert_pem',     &$pem);
	$_WA->html->setVar('cert_subject', &$cert_subject);
	$_WA->html->setVar('cert_key',     &$cert_key);
	$_WA->html->setVar('cert_parse',   &$cert_parse);
	$_WA->html->setVar('cert_host',    &$cert_host);
	die($_WA->html->loadTemplate('utils.cert.view.php'));
	}

/**
 * Process request and draw page to examine a csr.
 * @return void
 */
function getPageCsrView() {
	global $_WA;
	$_WA->html->setPageTitle('Examine Certificate Signing Request');
	// Prepopulate data we will be pulling
	$csr_subject = false;
	$csr_key     = false;
	$csr_asn     = false;
	// Check to see if they have provided a file.
	$csr_pem = $_WA->html->parseCertificateRequest('csr_file','csr');
	if (is_string($csr_pem)) {
		$_WA->moduleRequired('cert');
		$csr_subject = openssl_csr_get_subject($csr_pem,false);
		$junk = preg_split('/(-----((BEGIN)|(END)) CERTIFICATE REQUEST-----)/',$csr_pem);
		if (isset($junk[1])) {
			$enc = base64_decode($junk[1]);
			$csr_asn = $_WA->cert->parseAsn($enc);
			}
		$key = openssl_csr_get_public_key($csr_pem);
		if (is_resource($key)) {
			$csr_key = openssl_pkey_get_details($key);
			}
		}
	$_WA->html->setVar('csr_pem',     &$csr_pem);
	$_WA->html->setVar('csr_subject', &$csr_subject);
	$_WA->html->setVar('csr_key',     &$csr_key);
	$_WA->html->setVar('csr_asn',     &$csr_asn);
	die($_WA->html->loadTemplate('utils.csr.view.php'));
	}

/**
 * Process request and draw page to debug a signature
 * @return void
 */
function getPageDebugSigner() {
	global $_WA;
	$_WA->html->setPageTitle('Signature Debug');
	// Prepopulate data we will be pulling
	$pem_cert  = false;
	$pem_key   = false;
	$debug_txt = false;
	// Check to see if they have provided a file.
	$pem_cert = $_WA->html->parseCertificate('cert_file','cert');
	$pem_key  = $_WA->html->parsePublicKey('key_file','key');
	// Set the template vars now so input will be saved even if errors happen
	$_WA->html->setVar('pem_cert',  &$pem_cert);
	$_WA->html->setVar('pem_key',   &$pem_key);
	//
	// Attempt to process the certificate
	//
	if (is_string($pem_cert)) {
		$_WA->moduleRequired('cert');
		$data = $_WA->cert->parseCert($pem_cert);
		if (!is_array($data)) {
			$_WA->html->errorMsgSet('Failed to parse certificate.');
			die($_WA->html->loadTemplate('utils.debug.signer.php'));
			}
		if (!isset($data['signature'])) {
			$_WA->html->errorMsgSet('Failed to locate certificate signature.');
			die($_WA->html->loadTemplate('utils.debug.signer.php'));
			}
		$eSig = trim($data['signature']);
		$debug_txt = '<P>Encrypted Signature:' . "\n"
		. '<PRE>' . print_r($eSig,true) . '</PRE></P>';
		if (!isset($data['signatureAlgorithm'])) {
			$_WA->html->setVar('debug_txt', &$debug_txt);
			$_WA->html->errorMsgSet('Failed to determine signature algorithm.');
			die($_WA->html->loadTemplate('utils.debug.signer.php'));
			}
		$algo = trim($data['signatureAlgorithm']);
		$debug_txt .= '<P>Signature Algorithm: ' . $algo . '</P>';
		// get public key resource
		$pubKey = openssl_pkey_get_public($pem_key);
		if ($pubKey === false) {
			$_WA->html->errorMsgSet('Failed to load public key.');
			$_WA->html->setVar('debug_txt', &$debug_txt);
			die($_WA->html->loadTemplate('utils.debug.signer.php'));
			}
		// attempt to decode using both types of padding
		$dSig = false;
		$t1 = openssl_public_decrypt($eSig,$dSig,$pubKey);
		if ($t1 === true) {
			$debug_txt .= '<P>Public key WAS used to encrypt the signature.</P>';
			} else {
			$debug_txt .= '<P>Public key was NOT used to encrypt the signature</P>';
			$_WA->html->setVar('debug_txt', &$debug_txt);
			die($_WA->html->loadTemplate('utils.debug.signer.php'));
			}
		$asn = $_WA->cert->parseAsn($dSig);
		if (!is_array($asn)) {
			$_WA->html->errorMsgSet('Failed to parse ASN data: ' . $asn);
			$_WA->html->setVar('debug_txt', &$debug_txt);
			die($_WA->html->loadTemplate('utils.debug.signer.php'));
			}
		$debug_txt .= '<P>Parsed ASN Signature<BR />'
		. '<PRE>' . print_r($asn,true) . '</PRE></P>';
		if (!isset($asn[1][1][1]) or !is_string($asn[1][1][1])) {
			$_WA->html->setVar('debug_txt', &$debug_txt);
			$_WA->html->errorMsgSet('Failed to correctly parse the signature.');
			die($_WA->html->loadTemplate('utils.debug.signer.php'));
			}
		$sighash = bin2hex($asn[1][1][1]);
		$der = $_WA->cert->pemToDer($pem_cert);
		if ($der === false) {
			$_WA->html->setVar('debug_txt', &$debug_txt);
			$_WA->html->errorMsgSet('Failed to convert cert to DER.');
			die($_WA->html->loadTemplate('utils.debug.signer.php'));
			}
		$origSig = $_WA->cert->stripSignerAsn($der);
		if ($origSig === false) {
			$_WA->html->setVar('debug_txt', &$debug_txt);
			$_WA->html->errorMsgSet('Failed to extract sig from DER.');
			die($_WA->html->loadTemplate('utils.debug.signer.php'));
			}
		switch($algo) {
			case '1.2.840.10045.4.3.3':
				$certhash = hash('sha384',$origSig);
			break;
			case '1.2.840.113549.1.1.11':
				$certhash = hash('sha256',$origSig);
			break;
			case 'md2WithRSAEncryption':
				$certhash = hash('md2',$origSig);
			break;
			case 'md5withRSAEncryption':
				$certhash = hash('md5',$origSig);
			break;
			case 'SHA-1WithRSAEncryption':
				$certhash = hash('sha1',$origSig);
			break;
			default:
				$_WA->html->setVar('debug_txt', &$debug_txt);
				$_WA->html->errorMsgSet('Unknown hash algorithm: ' . $algo);
				die($_WA->html->loadTemplate('utils.debug.signer.php'));
			break;
			}
		$debug_txt .= '<P>Certificate Signature Hash: ' . $sighash . '<br />'
		.             'Duplicated Signature Hash:  ' . $certhash . '</P>';
		if ($sighash == $certhash) {
			$debug_txt .= '<P>The signature validated.</P>';
			} else {
			$debug_txt .= '<P>The signature did NOT validate.</P>';
			}
		}
	$_WA->html->setVar('debug_txt', &$debug_txt);
	die($_WA->html->loadTemplate('utils.debug.signer.php'));
	}

/**
 * Process request and draw page to determine if one cert signed another
 * @return void
 */
function getPageIsCertSigner() {
	global $_WA;
	$_WA->html->setPageTitle('Is Cert Signer?');
	// Prepopulate data we will be pulling
	$pem_issuer  = $_WA->html->parseCertificate('pem_issuer_file','pem_issuer');
	$pem_subject = $_WA->html->parseCertificate('pem_subject_file','pem_subject');
	// Check to see if they have provided the certs
	if (!$pem_issuer or !$pem_subject) {
		die($_WA->html->loadTemplate('utils.cert.signer.php'));
		}
	$_WA->html->setVar('pem_issuer',$pem_issuer);
	$_WA->html->setVar('pem_subject',$pem_subject);
	// Did they give us valid certs?
	$_WA->moduleRequired('cert');
	$issuer_ar = $_WA->cert->parseCert($pem_issuer);
	if (!is_array($issuer_ar)) {
		$_WA->html->errorMsgSet('Invalid issuer cert.');
		die($_WA->html->loadTemplate('utils.cert.signer.php'));
		}
	$subject_ar = $_WA->cert->parseCert($pem_subject);
	if (!is_array($subject_ar)) {
		$_WA->html->errorMsgSet('Invalid subject cert.');
		die($_WA->html->loadTemplate('utils.cert.signer.php'));
		}
	// Do the deed...
	$rc = $_WA->cert->isCertSigner($pem_subject,$pem_issuer);
	if (!is_bool($rc)) {
		$_WA->html->errorMsgSet($rc);
		die($_WA->html->loadTemplate('utils.cert.signer.php'));
		}
	$isSigner = ($rc === true) ? 'yes' : 'no';
	$_WA->html->setVar('isSigner',&$isSigner);
	die($_WA->html->loadTemplate('utils.cert.signer.php'));
	}

/**
 * Process request and draw page to examine a pkcs12 file.
 * @return void
 */
function getPagePkcs12View() {
	global $_WA;
	$_WA->html->setPageTitle('Examine PKCS12');
	$pkcs    = $_WA->html->getMultiFormData('pkcs12_file');
	$expPass = (isset($_POST['expPass'])) ? $_POST['expPass'] : false;
	if ($pkcs === false or $expPass === false) {
		die($_WA->html->loadTemplate('utils.pkcs12.upload.php'));
		}
	// prepopulate needed data
	$cert_pem    = false;
	$cert_key    = false;
	$certs_extra = false;
	// Attempt to read it...
	$rc = openssl_pkcs12_read($pkcs,$certs,$expPass);
	if (!($rc === true) or !is_array($certs) or count($certs) < 1) {
		$_WA->html->errorMsgSet('Failed to read PKCS12 certificate store.');
		die($_WA->html->loadTemplate('utils.pkcs12.upload.php'));
		}
	$_WA->html->setVar('cert_pem',    &$certs['cert']);
	$_WA->html->setVar('cert_key',    &$certs['pkey']);
	$_WA->html->setVar('certs_extra', &$certs['extracerts']);
	die($_WA->html->loadTemplate('utils.pkcs12.view.php'));
	}

/**
 * Attempt to extract PEM certificate via openssl s_client
 * @param string $hostAndPort
 * @return bool false on failures
 * @return string PEM formatted certificate on success
 */
function getPemFromHost($hostAndPort=null) {
	if (empty($hostAndPort)) { return false; }
	$cmd = '/usr/bin/openssl';
	if (!is_executable($cmd)) { return false; }
	$cli = 'echo | ' . $cmd . ' s_client -connect '
	. escapeshellarg($hostAndPort) . ' 2>&1 | '
	. 'sed -ne \'/-BEGIN CERTIFICATE-/,/-END CERTIFICATE-/p\'';
	$junk = exec($cli,$pem,$rc);
	if (!is_array($pem) or count($pem) < 3) {
		return false;
		}
	if ($pem[0] !== '-----BEGIN CERTIFICATE-----') {
		return false;
		}
	if ($pem[(count($pem) - 1)] !== '-----END CERTIFICATE-----') {
		return false;
		}
	return implode("\n",$pem);
	}

/**
 * Process request to convert PEM cert to DER format and upload to browser
 * @return void
 */
function uploadDer() {
	global $_WA;
	$txt = (isset($_REQUEST['cert'])) ? $_REQUEST['cert'] : false;
	$pem = $_WA->html->extractPemBlock($txt,'CERTIFICATE');
	if (!is_string($pem)) { die(); }
	$_WA->moduleRequired('cert');
	$der = $_WA->cert->pemToDer($pem);
	if (!is_string($der)) { die(); }
	header('Pragma: private');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Cache-Control: private');
	header('Content-Description: File Transfer');
	header('Content-Type: application/x-509-ca-cert');
	header('Content-Disposition: attachment; filename="cert.der"');
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: ' . strlen($der));
	die($der);
	}

?>
