<?
/**
 * Client Certs index
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

switch($_WA->html->getActionRequest()) {
	case WA_ACTION_BUNDLE:
		die($_WA->downloadCaBundle('client'));
	break;
	case WA_ACTION_CHANGE_PASS:
		die($_WA->changeKeyPass('client'));
	break;
	case WA_ACTION_CLIENT_ADD:
		die(getPageAdd());
	break;
	case WA_ACTION_CLIENT_EDIT:
		die('not implemented yet');
	break;
	case WA_ACTION_CLIENT_EXPORT:
		die('not implemented yet');
	break;
	case WA_ACTION_CLIENT_EXPORT_ALL:
		die($_WA->actionClientExportCerts());
	break;
	case WA_ACTION_CLIENT_IMPORT:
		die(getPageImport());
	break;
	case WA_ACTION_CLIENT_PKCS12:
		die($_WA->getPageClientPkcs12());
	break;
	case WA_ACTION_CLIENT_SIGN:
		die('not implemented yet');
		die(getPageCsrSign());
	break;
	case WA_ACTION_CLIENT_VIEW:
		die($_WA->getPageClientView());
	break;
	// decrypt private key password
	case WA_ACTION_DECRYPT:
		die($_WA->decryptPrivateKey('client'));
	break;
	// encrypt private key
	case WA_ACTION_ENCRYPT:
		die($_WA->encryptPrivateKey('client'));
	break;
	default:
		die($_WA->getPageClientList());
	break;
	}
?>
