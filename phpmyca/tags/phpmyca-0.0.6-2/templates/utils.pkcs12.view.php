<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

// grab data if it is set
$cert_pem     =& $this->getVar('cert_pem');
$cert_key     =& $this->getVar('cert_key');
$certs_extra  =& $this->getVar('certs_extra');

// breadcrumb
$qs_back = $this->getActionQs(WA_ACTION_PKCS12_VIEW);
$this->addMenuLink($qs_back,'Back','greenoutline');
?>
<?= $this->getPageHeader(false,true); ?>
<TABLE>
	<TR>
		<TH COLSPAN="2">Certificate</TH>
	</TR>
<? $val = (is_string($cert_pem)) ? $cert_pem : ''; ?>
	<TR>
		<TD COLSPAN="2"><PRE><?= $val; ?></PRE></TD>
	</TR>
	<TR>
		<TH COLSPAN="2">Private Key</TH>
	</TR>
<? $val = (is_string($cert_key)) ? $cert_key : ''; ?>
	<TR>
		<TD COLSPAN="2"><PRE><?= $val; ?></PRE></TD>
	</TR>
<? if (is_array($certs_extra) and count($certs_extra) > 0) {
foreach($certs_extra as $cert) { ?>
	<TR>
		<TH COLSPAN="2">Extra Cert</TH>
	</TR>
	<TR>
		<TD COLSPAN="2"><PRE><?= $cert; ?></PRE></TD>
	</TR>
<?	}
}
?>
</TABLE>
<?= $this->getPageFooter(); ?>
