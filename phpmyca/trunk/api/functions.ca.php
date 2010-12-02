<?
/**
 * phpmyca - CA certificates functions
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

/**
 * Process requests to add a ca certificate
 */
function getPageAdd() {
	global $_WA;
	$_WA->html->setPageTitle('Create CA');
	// If they have not submitted yet, dump them to the form.
	if ($_WA->html->getRequestVar(WA_QS_CONFIRM) !== 'yes') {
		die($_WA->html->loadTemplate('ca.add.php'));
		}
	$rc = $_WA->actionCaAdd();
	if (!($rc === true)) {
		$_WA->html->errorMsgSet($rc);
		die($_WA->html->loadTemplate('ca.add.php'));
		}
	// Success ;)
	$_WA->html->setPageTitle('Add CA Results');
	$qs = $_WA->html->getMenuQs(MENU_CERTS_CA);
	$_WA->html->addMenuLink($qs,'Return','greenoutline');
	$h   = array();
	$h[] = $_WA->html->getPageHeader();
	$h[] = 'Congratulations, the CA has been added successfully.';
	$h[]  = $_WA->html->getPageFooter();
	die(implode("\n",$h) . "\n");
	}

/**
 * Process requests to import a ca certificate.
 * @return void
 */
function getPageImport() {
	global $_WA;
	$_WA->html->setPageTitle('Import CA Certificate');
	// If they have not submitted yet, dump them to the form...
	$pem  = $_WA->html->parseCertificate('cert_file','cert');
	$key  = $_WA->html->parsePrivateKey('key_file','key');
	$pass = (isset($_POST['pass'])) ? $_POST['pass'] : false;
	$csr  = $_WA->html->parseCertificateRequest('csr_file','csr');
	$test = ($pem === false) ? false : true;
	if ($_WA->html->getRequestVar(WA_QS_CONFIRM) !== 'yes' or !$test) {
		die($_WA->html->loadTemplate('ca.import.php'));
		}
	$rc = $_WA->actionCaImport(&$pem,&$key,&$pass,&$csr);
	if (!($rc === true)) {
		$_WA->html->errorMsgSet($rc);
		die($_WA->html->loadTemplate('ca.import.php'));
		}
	// Success ;)
	$_WA->html->setPageTitle('CA Import Results');
	$qs = $_WA->html->getActionQs(WA_ACTION_CA_IMPORT);
	$_WA->html->addMenuLink($qs,'Import Another','greenoutline');
	$qs = $_WA->html->getMenuQs(MENU_CERTS_CA);
	$_WA->html->addMenuLink($qs,'Return','greenoutline');
	$h   = array();
	$h[] = $_WA->html->getPageHeader();
	$h[] = 'Congratulations, the CA has been imported successfully.';
	$h[]  = $_WA->html->getPageFooter();
	die(implode("\n",$h) . "\n");
	}

?>
