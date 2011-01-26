<?
/**
 * Server Certs index
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
	case WA_ACTION_BROWSER_IMPORT:
		die($_WA->getPageServerBrowserImport());
	break;
	case WA_ACTION_BUNDLE:
		die($_WA->downloadCaBundle('server'));
	break;
	case WA_ACTION_CHANGE_PASS:
		die($_WA->changeKeyPass('server'));
	break;
	// decrypt private key password
	case WA_ACTION_DECRYPT:
		die($_WA->decryptPrivateKey('server'));
	break;
	// encrypt private key
	case WA_ACTION_ENCRYPT:
		die($_WA->encryptPrivateKey('server'));
	break;
	case WA_ACTION_SERVER_ADD:
		die(getPageAdd());
	break;
	case WA_ACTION_SERVER_EDIT:
		die('not implemented yet');
	break;
	case WA_ACTION_SERVER_EXPORT:
		die('not implemented yet');
	break;
	case WA_ACTION_SERVER_EXPORT_ALL:
		die($_WA->actionServerExportCerts());
	break;
	case WA_ACTION_SERVER_IMPORT:
		die(getPageImport());
	break;
	case WA_ACTION_SERVER_PKCS12:
		die($_WA->getPageServerPkcs12());
	break;
	case WA_ACTION_SERVER_REVOKE:
		die($_WA->getPageServerRevoke());
	break;
	case WA_ACTION_SERVER_SIGN:
		die(getPageCsrSign());
	break;
	case WA_ACTION_SERVER_VIEW:
		die($_WA->getPageServerView());
	break;
	default:
		die($_WA->getPageServerList());
	break;
	}
?>
