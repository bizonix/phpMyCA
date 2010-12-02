<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
// verify webapp is loaded
if (!is_a($_WA,'webapp') or !method_exists($_WA,'requireWebapp')) {
	die('ERROR: webapp object failed to instantiate');
	}
$_WA->requireWebapp();

// Did the user request an action?
if (is_string($_WA->html->getActionRequest())) {
	switch($_WA->html->getActionRequest()) {
		// Add server csr
		case WA_ACTION_CSR_SERVER_ADD:
			die(getPageCsrServerAdd());
		break;
		// Change private key password on server csr
		case WA_ACTION_CSR_SERVER_CHANGE_PASS:
			die('Not implemented yet.');
		break;
		// Decrypt private key on server csr
		case WA_ACTION_CSR_SERVER_DECRYPT:
			die($_WA->decryptPrivateKey('csrserver'));
		break;
		// Download certificate request
		case WA_ACTION_CSR_SERVER_DOWNLOAD:
			die($_WA->uploadCertificateRequest('csrserver'));
		break;
		// Encrypt private key on server csr
		case WA_ACTION_CSR_SERVER_ENCRYPT:
		die($_WA->encryptPrivateKey('csrserver'));
		break;
		// List server csrs
		case WA_ACTION_CSR_SERVER_LIST:
			die($_WA->getPageCsrServerList());
		break;
		// View server csr
		case WA_ACTION_CSR_SERVER_VIEW:
			die($_WA->getPageCsrServerView());
		break;
		}
	}

$_WA->html->setPageTitle('Certificate Requests');
$_WA->html->loadTemplate('csr.main.menu.php');
?>
