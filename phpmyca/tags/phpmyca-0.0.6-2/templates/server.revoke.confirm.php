<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

$cert =& $this->getVar('cert');
if (!($cert instanceof phpmycaCert)) {
	$m = 'Required data is missing, cannot continue.';
	die($this->getPageError($m));
	}
$issuer =& $this->getVar('issuer');
if (!($cert instanceof phpmycaCert)) {
	$m = 'Issuer data is missing, cannot continue.';
	die($this->getPageError($m));
	}

$qs_back   = $this->getActionQs(WA_ACTION_SERVER_VIEW);

// footer links
$this->addMenuLink($qs_back,'Cancel','redoutline');
$this->addMenuLink('javascript:document.revokecert.submit();','Revoke','greenoutline');
?>
<?= $this->getPageHeader(false,true); ?>
<?= $this->getFormHeader('revokecert'); ?>
<?= $this->getFormBreadCrumb(); ?>
<INPUT TYPE="hidden" NAME="<? echo WA_QS_CONFIRM; ?>" VALUE="yes">
<? if ($issuer->isEncrypted()) { ?>
<TABLE ALIGN="center" WIDTH="100%">
<? $val = (isset($_POST['caPassPhrase'])) ? $_POST['caPassPhrase'] : ''; ?>
	<TR>
		<TH>Issuer Passphrase</TH>
		<TD>
			<INPUT TYPE="password" NAME="caPassPhrase" VALUE="<?= $val; ?>" SIZE="40" MAXLENGTH="64">
		</TD>
	</TR>
</TABLE>
<? } ?>
<P>
Are you absolutely certain you want to revoke the certificate for <?= $cert->CommonName; ?>?
This process is not reversible.
</P>
<?= $this->getFormFooter(); ?>
<?= $this->getPageFooter(); ?>
