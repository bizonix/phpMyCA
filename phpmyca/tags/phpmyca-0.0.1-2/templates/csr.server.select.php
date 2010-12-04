<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');
// breadcrumb
$qs_back = $this->getMenuQs(MENU_CERT_REQUESTS);

// footer links
$this->addMenuLink($qs_back,'Cancel','redoutline');
$this->addMenuLink('javascript:clearForm(document.addcert);','Clear Form','greenoutline');
$this->addMenuLink('javascript:document.addcert.submit();','Continue','greenoutline');
?>
<?= $this->getPageHeader(false,true); ?>
<?= $this->getFormHeader('addcert'); ?>
<?= $this->getFormBreadCrumb(); ?>
<P>
The information for the new certificate request can be populated from an
existing server certificate or by manually entering it.  Select an existing
server certificate or select "New" to manually enter the required information.
</P>
<TABLE>
	<COLGROUP><COL WIDTH="180px"></COLGROUP>
<? $val = (isset($_POST['serverId'])) ? $_POST['serverId'] : false; ?>
	<TR>
		<TH>Select Server</TH>
		<TD COLSPAN="2">
			<?= $this->getFormSelectServerId('serverId',$val); ?>
		</TD>
	</TR>
</TABLE>
<?= $this->getFormFooter(); ?>
<?= $this->getPageFooter(); ?>
