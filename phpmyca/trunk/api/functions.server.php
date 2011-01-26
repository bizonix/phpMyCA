<?
/**
 * phpmyca - server certificates functions
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

/**
 * Process requests to add a server certificate
 */
function getPageAdd() {
	global $_WA;
	$_WA->html->setPageTitle('Create Server Certificate');
	// If they have not submitted yet, dump them to the form.
	if ($_WA->html->getRequestVar(WA_QS_CONFIRM) !== 'yes') {
		die($_WA->html->loadTemplate('server.add.php'));
		}
	$rc = $_WA->actionServerAdd();
	if (!($rc === true)) {
		$_WA->html->errorMsgSet($rc);
		die($_WA->html->loadTemplate('server.add.php'));
		}
	// Success ;)
	$_WA->html->setPageTitle('Add Server Certificate Results');
	$qs = $_WA->html->getMenuQs(MENU_CERTS_SERVER);
	$_WA->html->addMenuLink($qs,'Return','greenoutline');
	$h   = array();
	$h[] = $_WA->html->getPageHeader();
	$h[] = 'Congratulations, the server certificate has been added successfully.';
	$h[]  = $_WA->html->getPageFooter();
	die(implode("\n",$h) . "\n");
	}

/**
 * Process requests to generate a server certificate from a CSR.
 * @return void
 */
function getPageCsrSign() {
	global $_WA;
	$_WA->html->setPageTitle('Generate Certificate from CSR');
	$conf = (isset($_POST[WA_QS_CONFIRM]))    ? $_POST[WA_QS_CONFIRM]  : false;
	$csr  = (isset($_POST['csr']))            ? $_POST['csr']          : false;
	//
	// Have they provided a valid csr yet?
	//
	if (!is_string($csr) or strlen($csr) < 1) {
		die($_WA->html->loadTemplate('get.csr.php'));
		}
	//
	// Validate the csr
	//
	$info = openssl_csr_get_subject($csr,false);
	if (!is_array($info) or !isset($info['commonName'])) {
		$_WA->html->errorMsgSet('Could not decode the CSR');
		die($_WA->html->loadTemplate('get.csr.php'));
		}
	//
	// Fields required for the next phase...
	//
	$caId   = (isset($_POST['caId'])) ? $_POST['caId'] : false;
	$days   = (isset($_POST['Days'])) ? $_POST['Days'] : false;
	$test1  = (is_numeric($caId) and $caId > 0);
	$test2  = (is_numeric($days) and $days > 0);
	if (!$test1 or !$test2) {
		die($_WA->html->loadTemplate('server.sign.php'));
		}
	$rc = $_WA->actionServerCsrSign();
	if (!($rc === true)) {
		$_WA->html->errorMsgSet($rc);
		die($_WA->html->loadTemplate('server.sign.php'));
		}
	// Success ;)
	$_WA->html->setPageTitle('Sign Certificate Results');
	$qs = $_WA->html->getMenuQs(MENU_CERTS_SERVER);
	$_WA->html->addMenuLink($qs,'Return','greenoutline');
	$h   = array();
	$h[] = $_WA->html->getPageHeader();
	$h[] = 'Congratulations, the certificate has been signed and imported '
	     . 'successfully.';
	$h[]  = $_WA->html->getPageFooter();
	die(implode("\n",$h) . "\n");
	}

/**
 * Process requests to import a server certificate.
 * @return void
 */
function getPageImport() {
	global $_WA;
	$_WA->html->setPageTitle('Import Server Certificate');
	$conf = (isset($_POST[WA_QS_CONFIRM])) ? $_POST[WA_QS_CONFIRM] : false;
	$pem  = $_WA->html->parseCertificate('cert_file','cert');
	$key  = $_WA->html->parsePrivateKey('key_file','key');
	$pass = (isset($_POST['pass'])) ? $_POST['pass'] : false;
	$csr  = $_WA->html->parseCertificateRequest('csr_file','csr');
	if ($conf !== 'yes' or $pem === false) {
		die($_WA->html->loadTemplate('server.import.php'));
		}
	$rc = $_WA->actionServerImport(&$pem,&$key,&$pass,&$csr);
	if (!($rc === true)) {
		$_WA->html->errorMsgSet($rc);
		die($_WA->html->loadTemplate('server.import.php'));
		}
	// Success ;)
	$_WA->html->setPageTitle('Certificate Import Results');
	$qs = $_WA->html->getActionQs(WA_ACTION_SERVER_IMPORT);
	$_WA->html->addMenuLink($qs,'Import Another','greenoutline');
	$qs = $_WA->html->getMenuQs(MENU_CERTS_SERVER);
	$_WA->html->addMenuLink($qs,'Return','greenoutline');
	$h   = array();
	$h[] = $_WA->html->getPageHeader();
	$h[] = 'Congratulations, the certificate has been imported successfully.';
	$h[]  = $_WA->html->getPageFooter();
	die(implode("\n",$h) . "\n");
	}
?>
