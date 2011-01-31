<?
/**
 * Cert utilities index
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
		case WA_ACTION_CERT_VIEW:
			die(getPageCertView());
		break;
		case WA_ACTION_CSR_VIEW:
			die(getPageCsrView());
		break;
		case WA_ACTION_DEBUG_SIGNER:
			die(getPageDebugSigner());
		break;
		case WA_ACTION_GET_DER:
			die(uploadDer());
		break;
		case WA_ACTION_IS_SIGNER:
			die(getPageIsCertSigner());
		break;
		case WA_ACTION_PKCS12_VIEW:
			die(getPagePkcs12View());
		break;
		}
	}
$_WA->html->setPageTitle('Certificate Utilities');
$_WA->html->loadTemplate('utils.main.menu.php');
?>
