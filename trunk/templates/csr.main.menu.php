<?
/**
 * Certificate Requests main menu
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');
// footer links
$this->addMenuLink('./','Main Menu','greenoutline');
?>
<?= $this->getPageHeader(); ?>
<TABLE ALIGN="center">
	<TR>
		<TH>
			<A HREF="<?= $this->getActionQs(WA_ACTION_CSR_SERVER_LIST); ?>">List Server Requests</A>
		</TH>
		<TD>
			List existing server certificate signing requests.
		</TD>
	</TR>
</TABLE>
<?= $this->getPageFooter(); ?>
