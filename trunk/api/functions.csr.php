<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

/**
 * Process requests generate a server CSR
 */
function getPageCsrServerAdd() {
	global $_WA;
	$_WA->html->setPageTitle('Create Server CSR');
	// Have they selected an existing server or new server?
	$serverId = $_WA->html->getRequestVar('serverId');
	if (!(is_numeric($serverId) or $serverId == 'New')) {
		die($_WA->html->loadTemplate('csr.server.select.php'));
		}
	// If they selected an existing server, only fill in values
	// if they are not already set.
	if (is_numeric($serverId)) {
		$f = array('CommonName','OrgName','OrgUnitName','EmailAddress',
		'LocalityName','StateName','CountryName');
		$_WA->moduleRequired('server');
		$_WA->server->searchReset();
		foreach ($f as $field) {
			$_WA->server->setSearchSelect($field);
			}
		$_WA->server->setSearchFilter('Id',$serverId);
		$_WA->server->setSearchLimit('1');
		$q = $_WA->server->query();
		if (is_array($q) and count($q) == '1') {
			foreach($f as $field) {
				if (!isset($_POST[$field])) {
					$_POST[$field] = $q[0][$field];
					}
				}
			}
		}
	// If they have not submitted yet, dump them to the form.
	if ($_WA->html->getRequestVar(WA_QS_CONFIRM) !== 'yes') {
		die($_WA->html->loadTemplate('csr.server.add.php'));
		}
	$rc = $_WA->actionServerCsrAdd();
	if (!($rc === true)) {
		$_WA->html->errorMsgSet($rc);
		die($_WA->html->loadTemplate('csr.server.add.php'));
		}
	// Success ;)
	$_WA->html->setPageTitle('Generate Server Certificate Results');
	$qs = $_WA->html->getMenuQs(MENU_CERT_REQUESTS);
	$_WA->html->addMenuLink($qs,'Return','greenoutline');
	$h   = array();
	$h[] = $_WA->html->getPageHeader();
	$h[] = 'Congratulations, the server CSR has been added successfully.';
	$h[]  = $_WA->html->getPageFooter();
	die(implode("\n",$h) . "\n");
	}

?>
