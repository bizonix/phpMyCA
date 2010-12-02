<?
/**
 * phpmyca certificate parsing and manipulation class
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

class phpmycaCert {

/**
 * Object Identifers to name array.
 *
 * @var array
 */
var $_oids = array(
	'1.2.840.10040.4.3'      => 'id-dsa-with-sha-1',
	'1.2.840.10045.4.3.3'    => 'sha384ECDSA',
	'1.2.840.113533.7.65.0'  => 'entrust-version-extension',
	'1.2.840.113549.1.9.1'   => 'Email',
	'1.2.840.113549.1.1.1'   => 'RSAEncryption',
	'1.2.840.113549.1.1.2'   => 'md2WithRSAEncryption',
	'1.2.840.113549.1.1.4'   => 'md5withRSAEncryption',
	'1.2.840.113549.1.1.5'   => 'SHA-1WithRSAEncryption',
	'1.2.840.113549.2.5'     => 'md5withRSAEncryption',
	'1.3.6.1.4.1.311.20.2'   => 'ms-enroll-certtype-extension',
	'1.3.6.1.4.1.311.21.1'   => 'ms-certsrv-ca-version',
	'1.3.6.1.5.5.7.1.1'      => 'id-pe-authorityInfoAccess',
	'1.3.6.1.5.5.7.1.12'     => 'id-pe-logotype',
	'1.3.6.1.5.5.7.2.1'      => 'CPS',
	'1.3.6.1.5.5.7.3.1'      => 'id_kp_serverAuth',
	'1.3.6.1.5.5.7.3.2'      => 'id_kp_clientAuth',
	'2.5.4.3'                => 'CommonName',
	'2.5.4.4'                => 'Surname',
	'2.5.4.6'                => 'Country',
	'2.5.4.7'                => 'Location',
	'2.5.4.8'                => 'StateOrProvince',
	'2.5.4.9'                => 'StreetAddress',
	'2.5.4.10'               => 'Organization',
	'2.5.4.11'               => 'OrganizationalUnit',
	'2.5.4.12'               => 'Title',
	'2.5.4.20'               => 'TelephoneNumber',
	'2.5.4.42'               => 'GivenName',
	'2.5.29.14'              => 'id-ce-subjectKeyIdentifier',
	'2.5.29.15'              => 'id-ce-keyUsage',
	'2.5.29.17'              => 'id-ce-subjectAltName',
	'2.5.29.19'              => 'id-ce-basicConstraints',
	'2.5.29.31'              => 'id-ce-CRLDistributionPoints',
	'2.5.29.32'              => 'id-ce-certificatePolicies',
	'2.5.29.35'              => 'id-ce-authorityKeyIdentifier',
	'2.5.29.37'              => 'id-ce-extKeyUsage',
	'2.16.840.1.113730.1.1'  => 'netscape-cert-type',
	'2.16.840.1.113730.1.2'  => 'netscape-base-url',
	'2.16.840.1.113730.1.3'  => 'netscape-revocation-url',
	'2.16.840.1.113730.1.4'  => 'netscape-ca-revocation-url',
	'2.16.840.1.113730.1.7'  => 'netscape-cert-renewal-url',
	'2.16.840.1.113730.1.8'  => 'netscape-ca-policy-url',
	'2.16.840.1.113730.1.12' => 'netscape-ssl-server-name',
	'2.16.840.1.113730.1.13' => 'netscape-comment',
	'2.16.840.1.113733.1.7.1.1' => 'verisign-user-notices',
	'2.16.840.1.113733.1.7.23.3' => 'class-3-cert-policy',
	'2.16.840.1.113730.4.1'      => 'nsSGC');

/**
 * Determine if a cert was used to sign another cert
 * @param string $cert - pem encoded signed cert
 * @param string $caCert - pem encoded signer cert
 * @return mixed
 */
public function isCertSigner(&$cert=null,&$caCert=null) {
	if (!is_string($cert))   { return 'invalid subject cert'; }
	if (!is_string($caCert)) { return 'invalid issuer cert'; }
	$data = $this->parseCert($cert);
	if (!is_array($data))  { return 'parse failed on subject cert'; }
	if (!isset($data['signature'])) {
		return 'failed to locate subject cert signature';
		}
	if (!isset($data['signatureAlgorithm'])) {
		return 'failed to determine subject cert signature algorithm';
		}
	$eSig = trim($data['signature']);
	$algo = trim($data['signatureAlgorithm']);
	$pubKey = openssl_pkey_get_public($caCert);
	if ($pubKey === false) {
		return 'failed to extract subject cert public key';
		}
	// If the ca public key cannot decrypt the subject sig, not the signer.
	// Try both padding types.
	$t1 = @openssl_public_decrypt($eSig,$dSig,$pubKey);
	if (!($t1 === true)) {
		return false;
		}
	$asn = $this->parseAsn($dSig);
	if (!is_array($asn)) {
		return 'failed to parse the decrypted signature of the subject cert';
		}
	if (!isset($asn[1][1][1])) { return 'failed to parse asn signature'; }
	// Not sure why some certs are not being read correctly, account
	// for that here.
	if (!is_string($asn[1][1][1])) { return false; }
	$sighash = bin2hex($asn[1][1][1]);
	$der = $this->pemToDer($cert);
	if ($der === false) {
		return 'failed to convert subject cert to DER encoding';
		}
	$origSig = $this->stripSignerAsn($der);
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
			return 'unknown hash algorithm: ' . $algo;
		break;
		}
	return ($sighash == $certhash);
	}

/**
 * Attempt to parse ASN.1 formated data.
 * @param string $data  ASN.1 formated data
 * @return array  Array contained the extracted values.
 */
public function parseAsn($data) {
	$result = array();
	while (strlen($data) > 1) {
		$class = ord($data[0]);
		switch ($class) {
			case 0x30:
				// Sequence
				$len = ord($data[1]);
				$bytes = 0;
				if ($len & 0x80) {
					$bytes = $len & 0x0f;
					$len = 0;
					for ($i = 0; $i < $bytes; $i++) {
						$len = ($len << 8) | ord($data[$i + 2]);
						}
					}
				$sequence_data = substr($data, 2 + $bytes, $len);
				$data = substr($data, 2 + $bytes + $len);
				$values = $this->parseAsn($sequence_data);
				if (!is_array($values) || is_string($values[0])) {
					$values = array($values);
					}
				$sequence_values = array();
				$i = 0;
				foreach ($values as $val) {
					if ($val[0] == 'extension') {
						$sequence_values['extensions'][] = $val;
						} else {
						$sequence_values[$i++] = $val;
						}
					}
				$result[] = array('sequence', $sequence_values);
			break;
			case 0x31:
				// Set of
				$len = ord($data[1]);
				$bytes = 0;
				if ($len & 0x80) {
					$bytes = $len & 0x0f;
					$len = 0;
					for ($i = 0; $i < $bytes; $i++) {
						$len = ($len << 8) | ord($data[$i + 2]);
						}
					}
				$sequence_data = substr($data, 2 + $bytes, $len);
				$data = substr($data, 2 + $bytes + $len);
				$result[] = array('set', $this->parseAsn($sequence_data));
			break;
			case 0x01:
				// Boolean type
				$boolean_value = (ord($data[2]) == 0xff);
				$data = substr($data, 3);
				$result[] = array('boolean', $boolean_value);
			break;
			case 0x02:
				// Integer type
				$len = ord($data[1]);
				$bytes = 0;
				if ($len & 0x80) {
					$bytes = $len & 0x0f;
					$len = 0;
					for ($i = 0; $i < $bytes; $i++) {
						$len = ($len << 8) | ord($data[$i + 2]);
						}
					}
				$integer_data = substr($data, 2 + $bytes, $len);
				$data = substr($data, 2 + $bytes + $len);
				$value = 0;
				if ($len <= 4) {
					/* Method works fine for small integers */
					for ($i = 0; $i < strlen($integer_data); $i++) {
						$value = ($value << 8) | ord($integer_data[$i]);
						}
					} else {
					/* Method works for arbitrary length integers */
					if (function_exists('bcadd')) {
						for ($i = 0; $i < strlen($integer_data); $i++) {
							$value = bcadd(bcmul($value, 256), ord($integer_data[$i]));
							}
						} else {
						$value = -1;
						}
					}
				$result[] = array('integer(' . $len . ')', $value);
			break;
			case 0x03:
				// Bitstring type
				$len = ord($data[1]);
				$bytes = 0;
				if ($len & 0x80) {
					$bytes = $len & 0x0f;
					$len = 0;
					for ($i = 0; $i < $bytes; $i++) {
						$len = ($len << 8) | @ord($data[$i + 2]);
						}
					}
				$bitstring_data = substr($data, 3 + $bytes, $len);
				$data = substr($data, 2 + $bytes + $len);
				$result[] = array('bit string', $bitstring_data);
			break;
			case 0x04:
				// Octetstring type
				$len = ord($data[1]);
				$bytes = 0;
				if ($len & 0x80) {
					$bytes = $len & 0x0f;
					$len = 0;
					for ($i = 0; $i < $bytes; $i++) {
						$len = ($len << 8) | ord($data[$i + 2]);
						}
					}
				$octectstring_data = substr($data, 2 + $bytes, $len);
				$data = substr($data, 2 + $bytes + $len);
				$result[] = array('octet string', $octectstring_data);
			break;
			case 0x05:
				// Null type
				$data = substr($data, 2);
				$result[] = array('null', null);
			break;
			case 0x06:
				// Object identifier type
				$len = ord($data[1]);
				$bytes = 0;
				if ($len & 0x80) {
					$bytes = $len & 0x0f;
					$len = 0;
					for ($i = 0; $i < $bytes; $i++) {
						$len = ($len << 8) | ord($data[$i + 2]);
						}
					}
				$oid_data = substr($data, 2 + $bytes, $len);
				$data = substr($data, 2 + $bytes + $len);
				// Unpack the OID
				$plain  = floor(@ord($oid_data[0]) / 40);
				$plain .= '.' . @ord($oid_data[0]) % 40;
				$value = 0;
				$i = 1;
				while ($i < strlen($oid_data)) {
					$value = $value << 7;
					$value = $value | (ord($oid_data[$i]) & 0x7f);
					if (!(ord($oid_data[$i]) & 0x80)) {
						$plain .= '.' . $value;
						$value = 0;
						}
					$i++;
					}
				if (isset($this->_oids[$plain])) {
					$result[] = array('oid', $this->_oids[$plain]);
					} else {
					$result[] = array('oid', $plain);
					}
			break;
			case 0x12:
			case 0x13:
			case 0x14:
			case 0x15:
			case 0x16:
			case 0x81:
			case 0x80:
			case 0x86:
				// Character string type
				$len = ord($data[1]);
				$bytes = 0;
				if ($len & 0x80) {
					$bytes = $len & 0x0f;
					$len = 0;
					for ($i = 0; $i < $bytes; $i++) {
						$len = ($len << 8) | ord($data[$i + 2]);
						}
					}
				$string_data = substr($data, 2 + $bytes, $len);
				$data = substr($data, 2 + $bytes + $len);
				$result[] = array('string', $string_data);
			break;
			case 0x17:
				// Time types
				$len = ord($data[1]);
				$bytes = 0;
				if ($len & 0x80) {
					$bytes = $len & 0x0f;
					$len = 0;
					for ($i = 0; $i < $bytes; $i++) {
						$len = ($len << 8) | ord($data[$i + 2]);
						}
					}
				$time_data = substr($data, 2 + $bytes, $len);
				$data = substr($data, 2 + $bytes + $len);
				$result[] = array('utctime', $time_data);
			break;
			case 0xa3:
			case 0x82:
				// X509v3 extensions?
				$len = ord($data[1]);
				$bytes = 0;
				if ($len & 0x80) {
					$bytes = $len & 0x0f;
					$len = 0;
					for ($i = 0; $i < $bytes; $i++) {
						$len = ($len << 8) | ord($data[$i + 2]);
						}
					}
				$sequence_data = substr($data, 2 + $bytes, $len);
				$data = substr($data, 2 + $bytes + $len);
				$result[] = array('extension', 'X509v3 extensions');
				$result[] = $this->parseAsn($sequence_data);
			break;
			case 0xa0:
				// Extensions
				$extension_data = substr($data, 0, 2);
				$data = substr($data, 2);
				$result[] = array('extension', dechex($extension_data));
			break;
			case 0xe6:
				$extension_data = substr($data, 0, 1);
				$data = substr($data, 1);
				$result[] = array('extension', dechex($extension_data));
			break;
			case 0xa1:
				$extension_data = substr($data, 0, 1);
				$data = substr($data, 6);
				$result[] = array('extension', dechex($extension_data));
			break;
			case 0xdb:
			case 0xdc:
				// crl distribution urls?
				$ar = $this->parseAsn(substr($data,2));
				if (is_array($ar) and isset($ar[0]) and isset($ar[1])) {
					if ($ar[0] == 'string') {
						$result[] = array('string', $ar[1]);
						$data = '';
						continue;
						}
					}
				// Unknown
				$tmp = array();
				$tmp['class']     = $class;
				$tmp['class-hex'] = dechex($class);
				$tmp['data']      = $data;
				$tmp['data-hex']  = bin2hex($data);
				$result[] = array('UNKNOWN', $tmp);
				$data = '';
			break;
			case 0x0c:
				// UTF-8 string
				$data = utf8_decode($data);
				$len = ord($data[1]);
				$bytes = 0;
				if ($len & 0x80) {
					$bytes = $len & 0x0f;
					$len = 0;
					for ($i = 0; $i < $bytes; $i++) {
						$len = ($len << 8) | ord($data[$i + 2]);
						}
					}
				$string_data = substr($data, 2 + $bytes, $len);
				$data = substr($data, 2 + $bytes + $len);
				$result[] = array('string', $string_data);
			break;
			default:
				// Unknown
				$tmp = array();
				$tmp['class']     = $class;
				$tmp['class-hex'] = dechex($class);
				$tmp['data']      = $data;
				$tmp['data-hex']  = bin2hex($data);
				$result[] = array('UNKNOWN', $tmp);
				$data = '';
			break;
			}
		}
	return (count($result) > 1) ? $result : array_pop($result);
	}

/**
 * Extract the contents of a PEM format certificate to an array.
 * @param string $cert  PEM format certificate
 * @return array  Array containing all extractable information about
 *                the certificate.
 */
public function parseCert($cert) {
	$raw_cert  = $this->pemToDer($cert);
	$cert_data = $this->parseAsn($raw_cert);
	if (!is_array($cert_data) or !isset($cert_data[1])) {
		return false;
		}
	if ($cert_data[0] !== 'sequence' or !is_array($cert_data[1])) {
		return false;
		}
	// special handling for v1 certs...
	if (!isset($cert_data[1][0][1][6])) {
		unset($cert_data);
		return $this->parseCertV1($cert);
		}
	$cd = array();
	$cd['fingerprints']['md5'] = md5($raw_cert);
	$cd['fingerprints']['sha1'] = sha1($raw_cert);
	$cd['certificate']['extensions'] = array();
	$cd['certificate']['version'] = $cert_data[1][0][1][0][1] + 1;
	$cd['certificate']['serialNumber'] = $cert_data[1][0][1][1][1];
	$cd['certificate']['signature'] = $cert_data[1][0][1][2][1][0][1];
	$cd['certificate']['issuer'] = $cert_data[1][0][1][3][1];
	$cd['certificate']['validity'] = $cert_data[1][0][1][4][1];
	$cd['certificate']['subject'] = @$cert_data[1][0][1][5][1];
	$cd['certificate']['subjectPublicKeyInfo'] = $cert_data[1][0][1][6][1];
	$cd['signatureAlgorithm'] = $cert_data[1][1][1][0][1];
	$cd['signature'] = $cert_data[1][2][1];
	$cd['signature-hex'] = bin2hex($cert_data[1][2][1]);
	// issuer - attributes can have multiple values
	$issuer = array();
	foreach ($cd['certificate']['issuer'] as $value) {
		$tag = $value[1][1][0][1];
		if (!array_key_exists($tag,$issuer)) { $issuer[$tag] = array(); }
		$issuer[$tag][] =  $value[1][1][1][1];
		}
	$cd['certificate']['issuer'] = $issuer;
	// subject - attributes can have multiple values

	$subject = array();
	foreach ($cd['certificate']['subject'] as $value) {
		$tag = $value[1][1][0][1];
		if (!array_key_exists($tag,$subject)) { $subject[$tag] = array(); }
		$subject[$tag][] = $value[1][1][1][1];
		}
	$cd['certificate']['subject'] = $subject;
	// validity
	$vals = $cd['certificate']['validity'];
	$cd['certificate']['validity'] = array();
	$cd['certificate']['validity']['notbefore'] = $vals[0][1];
	$cd['certificate']['validity']['notafter'] = $vals[1][1];
	foreach ($cd['certificate']['validity'] as $key => $val) {
		$year = substr($val, 0, 2);
		$month = substr($val, 2, 2);
		$day = substr($val, 4, 2);
		$hour = substr($val, 6, 2);
		$minute = substr($val, 8, 2);
		if (($val[11] == '-') || ($val[9] == '+')) {
			// handle time zone offset here
			$seconds = 0;
			} elseif (strtoupper($val[11]) == 'Z') {
			$seconds = 0;
			} else {
			$seconds = substr($val, 10, 2);
			if (($val[11] == '-') || ($val[9] == '+')) {
				// handle time zone offset here
				}
			}
		$cd['certificate']['validity'][$key] = gmmktime ($hour, $minute, $seconds, $month, $day, $year);
		}
	// Split the Public Key into components.
	$subjectPublicKeyInfo = array();
	$subjectPublicKeyInfo['algorithm'] = $cd['certificate']['subjectPublicKeyInfo'][0][1][0][1];
	if ($subjectPublicKeyInfo['algorithm'] == 'RSAEncryption') {
		$subjectPublicKey = $this->parseAsn($cd['certificate']['subjectPublicKeyInfo'][1][1]);
		$subjectPublicKeyInfo['subjectPublicKey']['modulus'] = $subjectPublicKey[1][0][1];
		$subjectPublicKeyInfo['subjectPublicKey']['publicExponent'] = $subjectPublicKey[1][1][1];
		}
	$cd['certificate']['subjectPublicKeyInfo'] = $subjectPublicKeyInfo;
	if (isset($cert_data[1][0][1][7]) && is_array($cert_data[1][0][1][7][1])) {
		$cd['certificate']['extensions'] = $this->parseExtensions($cert_data[1][0][1][7][1]);
		}
	return $cd;
	}

/**
 * Parse asn extracted contents of a v1 cert.
 * @param array $cert PEM format certificate
 * @return array Array containing all extractable information about the
 *               certificate.
 */
public function parseCertV1($cert=null) {
	$raw_cert  = $this->pemToDer($cert);
	$cert_data = $this->parseAsn($raw_cert);
	if (!is_array($cert_data) or !isset($cert_data[1])) {
		return false;
		}
	if ($cert_data[0] !== 'sequence' or !is_array($cert_data[1])) {
		return false;
		}
	// this is a v3 cert ;)
	if (isset($cert_data[1][0][1][6])) {
		return false;
		}
	$cd = array();
	$cd['fingerprints']['md5'] = md5($raw_cert);
	$cd['fingerprints']['sha1'] = sha1($raw_cert);
	$cd['certificate']['version'] = 1;
	$cd['certificate']['serialNumber'] = $cert_data[1][0][1][0][1];
	$cd['certificate']['signature'] = $cert_data[1][0][1][1][1][0][1];
	$cd['certificate']['issuer'] = $cert_data[1][0][1][2][1];
	$cd['certificate']['validity'] = $cert_data[1][0][1][3][1];
	$cd['certificate']['subject'] = @$cert_data[1][0][1][4][1];
	$cd['certificate']['subjectPublicKeyInfo'] = $cert_data[1][0][1][5][1];
	$cd['signatureAlgorithm'] = $cert_data[1][1][1][0][1];
	$cd['signature'] = $cert_data[1][2][1];
	// sanitize the values we have...
	// issuer - attributes can have multiple values
	$issuer = array();
	foreach ($cd['certificate']['issuer'] as $value) {
		if (!is_array($value)) { continue; }
		$tag = $value[1][1][0][1];
		if (!array_key_exists($tag,$issuer)) { $issuer[$tag] = array(); }
		$issuer[$tag][] =  $value[1][1][1][1];
		}
	$cd['certificate']['issuer'] = $issuer;
	// subject - attributes can have multiple values
	$subject = array();
	foreach ($cd['certificate']['subject'] as $value) {
		$tag = $value[1][1][0][1];
		if (!array_key_exists($tag,$subject)) { $subject[$tag] = array(); }
		$subject[$tag][] = $value[1][1][1][1];
		}
	$cd['certificate']['subject'] = $subject;
	// validity
	$vals = $cd['certificate']['validity'];
	$cd['certificate']['validity'] = array();
	$cd['certificate']['validity']['notbefore'] = $vals[0][1];
	$cd['certificate']['validity']['notafter'] = $vals[1][1];
	foreach ($cd['certificate']['validity'] as $key => $val) {
		$year = substr($val, 0, 2);
		$month = substr($val, 2, 2);
		$day = substr($val, 4, 2);
		$hour = substr($val, 6, 2);
		$minute = substr($val, 8, 2);
		if (($val[11] == '-') || ($val[9] == '+')) {
			// handle time zone offset here
			$seconds = 0;
			} elseif (strtoupper($val[11]) == 'Z') {
			$seconds = 0;
			} else {
			$seconds = substr($val, 10, 2);
			if (($val[11] == '-') || ($val[9] == '+')) {
				// handle time zone offset here
				}
			}
		$cd['certificate']['validity'][$key] = gmmktime ($hour, $minute, $seconds, $month, $day, $year);
		}
	// Split the Public Key into components.
	$subjectPublicKeyInfo = array();
	$subjectPublicKeyInfo['algorithm'] = $cd['certificate']['subjectPublicKeyInfo'][0][1][0][1];
	if ($subjectPublicKeyInfo['algorithm'] == 'RSAEncryption') {
		$subjectPublicKey = $this->parseAsn($cd['certificate']['subjectPublicKeyInfo'][1][1]);
		$subjectPublicKeyInfo['subjectPublicKey']['modulus'] = $subjectPublicKey[1][0][1];
		$subjectPublicKeyInfo['subjectPublicKey']['publicExponent'] = $subjectPublicKey[1][1][1];
		}
	$cd['certificate']['subjectPublicKeyInfo'] = $subjectPublicKeyInfo;
	$cd['certificate']['extensions'] = array();
	return $cd;
	}

/**
 * Is a parsed cert a CA cert?
 * @param array $cert (obtained via $this->parseCert)
 * @return bool
 */
public function parsedCertIsCa(&$cert=null) {
	if (!is_array($cert)) { return false; }
	if (!isset($cert['certificate']['version'])) { return false; }
	if (!is_numeric($cert['certificate']['version'])) { return false; }
	// below v3 we can't know...
	if ($cert['certificate']['version'] < 3) { return true; }
	// we have a v3 cert...  Try netscape cert type first.
	if (isset($cert['certificate']['extensions']['supported']['netscape-cert-type'])) {
		$pos = strpos($cert['certificate']['extensions']['supported']['netscape-cert-type'],'SSL CA');
		if (!($pos === false)) { return true; }
		}
	//
	// Require CA:TRUE basicConstraint.  Cannot use keyUsage of keyCertSign
	// because not all issuers set them (cacert.org for example)
	//
	if (!isset($cert['certificate']['extensions']['supported']['id-ce-basicConstraints'])) {
		return false;
		}
	$bc = $cert['certificate']['extensions']['supported']['id-ce-basicConstraints'];
	if (!isset($bc['CA'])) { return false; }
	return ($bc['CA'] === true);
	}

/**
 * Is a parsed cert a client cert?
 * @param array $cert (obtained via $this->parseCert)
 * @return bool
 */
public function parsedCertIsClient(&$cert=null) {
	if ($this->parsedCertIsCa(&$cert) === true) { return false; }
	if ($this->parsedCertIsSslServer(&$cert) === true) { return false; }
	// below v3 we can't know...
	if ($cert['certificate']['version'] < 3) { return true; }
	// check id-ce-keyUsage for digitalSignature, keyEncipherment, and dataEncipherment
	if (isset($cert['certificate']['extensions']['supported']['id-ce-keyUsage']['usages'])) {
		$u = $cert['certificate']['extensions']['supported']['id-ce-keyUsage']['usages'];
		if (in_array('digitalSignature',$u) and
		    in_array('keyEncipherment',$u) and
		    in_array('dataEncipherment',$u)) {
				return true;
			}
		}
	// check id-ce-extKeyUsage
	if (isset($cert['certificate']['extensions']['supported']['id-ce-extKeyUsage'])) {
		$u = $cert['certificate']['extensions']['supported']['id-ce-extKeyUsage'];
		if (in_array('id_kp_clientAuth',$u)) { return true; }
		}
	return false;
	}

/**
 * Is a parsed cert an ssl server cert?
 * @param array $cert (obtained via $this->parseCert)
 * @return bool
 */
public function parsedCertIsSslServer(&$cert=null) {
	if (!is_array($cert)) { return false; }
	if (!isset($cert['certificate']['version'])) { return false; }
	if (!is_numeric($cert['certificate']['version'])) { return false; }
	// below v3 we can't know...
	if ($cert['certificate']['version'] < 3) { return true; }
	if ($this->parsedCertIsCa($cert) === true) { return false; }
	// We have to have either netscape-cert-type, id-ce-keyUsage, or
	// id-cd-extKeyUsage to know anything...
	$nsCertType = (isset($cert['certificate']['extensions']['supported']['netscape-cert-type']));
	if ($nsCertType) {
		$nsCertType = $cert['certificate']['extensions']['supported']['netscape-cert-type'];
		if (!is_string($nsCertType) or strlen($nsCertType) < 1) { $nsCertType = false; }
		}
	$keyUsage = (isset($cert['certificate']['extensions']['supported']['id-ce-keyUsage']['usages']));
	$extUsage = (isset($cert['certificate']['extensions']['supported']['id-ce-extKeyUsage']));
	if ($keyUsage) {
		$keyUsage = $cert['certificate']['extensions']['supported']['id-ce-keyUsage']['usages'];
		if (!is_array($keyUsage) or count($keyUsage) < 1) { $keyUsage = false; }
		}
	if ($extUsage) {
		$extUsage = $cert['certificate']['extensions']['supported']['id-ce-extKeyUsage'];
		if (!is_array($extUsage) or count($extUsage) < 1) { $extUsage = false; }
		}
	if (!$nsCertType and !$keyUsage and !$extUsage) { return false; }
	// Try keyUsage
	if (!($keyUsage === false)) {
		if (!in_array('keyEncipherment',$keyUsage)) { return false; }
		}
	// Try extUsage
	if (!($extUsage === false)) {
		if (in_array('id_kp_serverAuth',$extUsage)) {
			return true;
			}
		}
	// Try netscape-cert-type
	if (!($nsCertType === false)) {
		$pos = strpos($nsCertType,'SSL server');
		if (!($pos === false)) { return true; }
		}
	return false;
	}

/**
 * Parse extensions into two arrays - supported and unsupported
 * @param array $extensions (output from parseAsn())
 * @return array
 */
public function parseExtensions($extensions=null) {
	$ra     = array();
	$e      = 'errors';
	$s      = 'supported';
	$n      = 'not-supported';
	$ra[$n] = array();
	$ra[$s] = array();
	$ra[$e] = array();
	if (!is_array($extensions)) { return $ra; }
	foreach($extensions as $ext) {
		if (!is_array($ext) or !isset($ext[1][0][1])) {
			$ra[$e][] = $ext;
			continue;
			}
		$oid = $ext[1][0][1];
		switch($oid) {
			case 'id-ce-basicConstraints':
				$icb = array();
				$valKey = (isset($ext[1][2])) ? 2 : 1;
				$icb = array();
				$icb['critical'] = ($valKey == 2) ? true : false;
				$icb['CA'] = 'UNKNOWN';
				$icb['pathlen'] = 'UNKNOWN';
				$settings = $this->parseAsn($ext[1][$valKey][1]);
				if (is_array($settings)) {
					if (isset($settings[1][0][1])) {
						$icb['CA'] = ($settings[1][0][1] == 1) ? true : false;
						}
					if (isset($settings[1][1][1])) {
						$icb['pathlen'] = $settings[1][1][1];
						}
					}
				$ra[$s][$oid] = $icb;
			break;
			case 'id-ce-certificatePolicies':
				$data = $this->parseAsn($ext[1][1][1]);
				if (isset($data[1][0][1][1][1][0][1][1][1])) {
					$cps = $data[1][0][1][1][1][0][1][1][1];
					$ra[$s][$oid] = $cps;
					} else {
					$ra[$e][$oid] = $ext;
					}
			break;
			case 'id-ce-CRLDistributionPoints':
				if (!isset($ext[1][1][1])) {
					$ra[$n][$oid] = $ext;
					continue;
					}
				$val = $this->parseAsn($ext[1][1][1]);
				if (!is_array($val[1]) or count($val[1]) < 1) {
					$ra[$e][$oid] = $ext;
					continue;
					}
				$urls = array();
				foreach($val[1] as $ar) {
					if (!isset($ar[1][0][1])) {
						$ra[$e][$oid] = $ext;
						break;
						}
					if (is_string($ar[1][0][1])) {
						$urls[] = urldecode($ar[1][0][1]);
						}
					}
				if (count($urls) < 1) {
					$ra[$e][$oid] = $ext;
					} else {
					$ra[$s][$oid] = $urls;
					}
			break;
			case 'id-ce-extKeyUsage':
				$val = $this->parseAsn($ext[1][1][1]);
				$val = $val[1];
				if (!is_array($val) or count($val) < 1) {
					$ra[$e][$oid] = $ext;
					break;
					}
				$usages = array();
				foreach($val as $usage) {
					if (!isset($usage[1])) {
						$ra[$e][$oid] = $ext;
						break;
						}
					$usages[] = $usage[1];
					}
				if (count($usages) < 1) {
					$ra[$e][$oid] = $ext;
					break;
					}
				$ra[$s][$oid] = $usages;
			break;
			case 'id-ce-keyUsage':
				$ar = array();
				$ar['critical'] = false;
				if (isset($ext[1][2][1])) {
					$ar['critical'] = true;
					$val = $this->parseAsn($ext[1][2][1]);
					} else {
					$val = $this->parseAsn($ext[1][1][1]);
					}
				if (!is_array($val) or !isset($val[1])) {
					$ra[$n][$oid] = $ext;
					continue;
					}
				$val  = intval(ord($val[1]));
				$uses = array();
				while($val > 1) {
					if ($val >= 128) { $uses[] = 'digitalSignature'; $val -= 128; }
					if ($val >=  64) { $uses[] = 'nonRepudiation';   $val -=  64; }
					if ($val >=  32) { $uses[] = 'keyEncipherment';  $val -=  32; }
					if ($val >=  16) { $uses[] = 'dataEncipherment'; $val -=  16; }
					if ($val >=   8) { $uses[] = 'keyAgreement';     $val -=   8; }
					if ($val >=   4) { $uses[] = 'keyCertSign';      $val -=   4; }
					if ($val >=   2) { $uses[] = 'cRLSign';          $val -=   2; }
					}
				$ar['usages'] = $uses;
				$ra[$s][$oid] = $ar;
			break;
			case 'id-ce-subjectKeyIdentifier':
				if (!isset($ext[1][1][1])) {
					$ra[$n][$oid] = $ext;
					continue;
					}
				$val = $this->parseAsn($ext[1][1][1]);
				$val = $val[1];
				$newVal = '';
				for ($i = 0; $i < strlen($val); $i++) {
					$newVal .= sprintf('%02x:', ord($val[$i]));
					}
				$newVal = substr($newVal,0,-1);
				$ra[$s][$oid] = $newVal;
			break;
			case 'netscape-base-url':
			case 'netscape-ca-policy-url':
			case 'netscape-ca-revocation-url':
			case 'netscape-cert-renewal-url':
			case 'netscape-revocation-url':
				$val = $this->parseAsn($ext[1][1][1]);
				$ra[$s][$oid] = urldecode($val[1]);
			break;
			case 'netscape-comment':
			case 'netscape-ssl-server-name':
				$val = $this->parseAsn($ext[1][1][1]);
				$ra[$s][$oid] = $val[1];
			break;
			case 'netscape-cert-type':
				if (!isset($ext[1][1][1])) {
					$ra[$n][$oid] = $ext;
					continue;
					}
				$val = $this->parseAsn($ext[1][1][1]);
				$val = ord($val[1]);
				$newVal = '';
				if ($val & 0x80) {
					$newVal .= empty($newVal) ? 'SSL client' : ', SSL client';
					}
				if ($val & 0x40) {
					$newVal .= empty($newVal) ? 'SSL server' : ', SSL server';
					}
				if ($val & 0x20) {
					$newVal .= empty($newVal) ? 'S/MIME' : ', S/MIME';
					}
				if ($val & 0x10) {
					$newVal .= empty($newVal) ? 'Object Signing' : ', Object Signing';
					}
				if ($val & 0x04) {
					$newVal .= empty($newVal) ? 'SSL CA' : ', SSL CA';
					}
				if ($val & 0x02) {
					$newVal .= empty($newVal) ? 'S/MIME CA' : ', S/MIME CA';
					}
				if ($val & 0x01) {
					$newVal .= empty($newVal) ? 'Object Signing CA' : ', Object Signing CA';
					}
				$ra[$s][$oid] = $newVal;
			break;
			// ignore these
			case 'entrust-version-extension':
			case 'id-ce-authorityKeyIdentifier':
			case 'id-ce-subjectAltName':
			case 'id-pe-authorityInfoAccess':
			case 'id-pe-logotype':
			case 'ms-certsrv-ca-version':
			case 'ms-enroll-certtype-extension':
			break;
			default:
				$ra[$n][$oid] = $ext;
			break;
			}
		}
	if (count($ra[$e]) < 1) { unset($ra[$e]); }
	if (count($ra[$n]) < 1) { unset($ra[$n]); }
	return $ra;
	//case 'id-ce-CRLDistributionPoints':
	//	$cd['certificate']['extensions'][$oid] = $this->parseAsn($val[1]);
	//break;
	//case 'netscape-base-url':
	//case 'netscape-ca-policy-url':
	//case 'netscape-ca-revocation-url':
	//case 'netscape-cert-renewal-url':
	//case 'netscape-comment':
	//case 'netscape-revocation-url':
	//case 'netscape-ssl-server-name':
	//	$val = $this->parseAsn($ext[1]);
	//	$ra[$s][$oid] = $val[1];
	//break;
	}

/**
 * Convert pem encoded certificate to DER encoding
 * @return string $derEncoded on success
 * @return bool false on failures
 */
public function pemToDer($pem=null) {
	if (!is_string($pem)) { return false; }
	$cert_split = preg_split('/(-----((BEGIN)|(END)) CERTIFICATE-----)/',$pem);
	if (!isset($cert_split[1])) { return false; }
	return base64_decode($cert_split[1]);
	}

/**
 * Strip signing data from DER encoding certificate and return the results
 * @param string $der
 * @return string $derCert on success
 * @return bool false on failures
 */
public function stripSignerAsn($der=null) {
	if (!is_string($der)) { return false; }
	if (strlen($der) < 8) { return false; }
	$cursor = 4;
	$len   = ord($der[($cursor + 1)]);
	$bytes = 0;
	if ($len & 0x80) {
		$bytes = $len & 0x0f;
		$len   = 0;
		for($i = 0; $i < $bytes; $i++) {
			$len = ($len << 8) | ord($der[$cursor + $i + 2]);
			}
		}
	return substr($der,4,$len + 4);
	}

}

?>
