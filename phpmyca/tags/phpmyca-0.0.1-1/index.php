<?
/**
 * phpmyca traffic director
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
require('/etc/phpmyca/phpmyca.php');

// verify webapp is loaded
if (!is_a($_WA,'webapp') or !method_exists($_WA,'requireWebapp')) {
	die('ERROR: webapp object failed to instantiate');
	}
$_WA->requireWebapp();

// Did the user request a menu?
if (is_string($_WA->html->getMenuRequest())) {
	if ($_WA->isMenu($_WA->html->getMenuRequest())) {
		die($_WA->getMenu($_WA->html->getMenuRequest()));
		}
	}

// Fall through to including the main menu.
die($_WA->getPageWelcome());
?>
