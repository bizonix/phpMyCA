<?
/**
 * CA Certs index
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
switch($_WA->html->getActionRequest()) {
	case WA_ACTION_BROWSER_IMPORT:
		die($_WA->getPageCaBrowserImport());
	break;
	case WA_ACTION_BUNDLE:
		die($_WA->downloadCaBundle('ca'));
	break;
	case WA_ACTION_CA_ADD:
		die(getPageAdd());
	break;
	case WA_ACTION_CA_EDIT:
		die('not implemented yet');
	break;
	case WA_ACTION_CA_EXPORT:
		die('not implemented yet');
	break;
	case WA_ACTION_CA_EXPORT_ALL:
		die($_WA->actionCaExportCerts());
	break;
	case WA_ACTION_CA_IMPORT:
		die(getPageImport());
	break;
	case WA_ACTION_CA_PKCS12:
		die($_WA->getPageCaPkcs12());
	break;
	case WA_ACTION_CA_REVOKE:
		die($_WA->getPageCaRevoke());
	break;
	case WA_ACTION_CA_VIEW:
		die($_WA->getPageCaView());
	break;
	case WA_ACTION_CHANGE_PASS:
		die($_WA->changeKeyPass('ca'));
	break;
	// decrypt private key password
	case WA_ACTION_DECRYPT:
		die($_WA->decryptPrivateKey('ca'));
	break;
	// encrypt private key
	case WA_ACTION_ENCRYPT:
		die($_WA->encryptPrivateKey('ca'));
	break;
	default:
		die($_WA->getPageCaList());
	break;
	}
?>
