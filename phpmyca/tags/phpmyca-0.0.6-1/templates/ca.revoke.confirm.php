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
$caCerts     =& $this->getVar('caCerts');
$clientCerts =& $this->getVar('clientCerts');
$serverCerts =& $this->getVar('serverCerts');

$qs_back   = $this->getActionQs(WA_ACTION_CA_VIEW);

// Are there any subject certs?
$hasCaCerts     = (is_array($caCerts) and count($caCerts) > 0);
$hasClientCerts = (is_array($clientCerts) and count($clientCerts) > 0);
$hasServerCerts = (is_array($serverCerts) and count($serverCerts) > 0);
$hasSubjects    = ($hasCaCerts or $hasClientCerts or $hasServerCerts);
$class = '';

// footer links
$this->addMenuLink($qs_back,'Cancel','redoutline');
$this->addMenuLink('javascript:document.revokecert.submit();','Revoke','greenoutline');
?>
<?= $this->getPageHeader(false,true); ?>
<?= $this->getFormHeader('revokecert'); ?>
<?= $this->getFormBreadCrumb(); ?>
<INPUT TYPE="hidden" NAME="<? echo WA_QS_CONFIRM; ?>" VALUE="yes">
<? if ($cert->isEncrypted()) { ?>
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
<? if ($hasSubjects) { ?>
<P>
The following certificates have been signed by this CA or its intermediate
certificates.  As a result the certificates listed below will no longer be
considered trustworthy by properly configured clients.
</P>
<? } ?>

<? if ($hasCaCerts) { ?>
<TABLE ALIGN="center" WIDTH="100%">
	<TR>
		<TH COLSPAN="3">CA Certificates Signed by this CA or Intermediates</TH>
	</TR>
	<TR>
		<TH>Name</TH>
		<TH>Org</TH>
		<TH>Org Unit</TH>
	</TR>
<? foreach($caCerts as $c) {
	$class = ($class == 'on') ? 'off' : 'on'; ?>
	<TR>
		<TD CLASS="<?= $class; ?>">
			<?= $c->CommonName; ?>
		</TD>
		<TD CLASS="<?= $class; ?>">
			<?= $c->OrgName; ?>
		</TD>
		<TD CLASS="<?= $class; ?>">
			<?= substr($c->OrgUnitName,0,50); ?>
		</TD>
	</TR>
<? } ?>
</TABLE>
<? } ?>

<? if ($hasServerCerts) { ?>
<TABLE ALIGN="center" WIDTH="100%">
	<TR>
		<TH COLSPAN="3">Server Certificates Signed by this CA or Intermediates</TH>
	</TR>
	<TR>
		<TH>Name</TH>
		<TH>Org</TH>
		<TH>Org Unit</TH>
	</TR>
<? foreach($serverCerts as &$c) {
	$class = ($class == 'on') ? 'off' : 'on'; ?>
	<TR>
		<TD CLASS="<?= $class; ?>">
			<?= $c->CommonName; ?>
		</TD>
		<TD CLASS="<?= $class; ?>">
			<?= $c->OrgName; ?>
		</TD>
		<TD CLASS="<?= $class; ?>">
			<?= $c->OrgUnitName; ?>
		</TD>
	</TR>
<? } ?>
</TABLE>
<? } ?>

<? if ($hasClientCerts) { ?>
<TABLE ALIGN="center" WIDTH="100%">
	<TR>
		<TH COLSPAN="3">Client Certificates Signed by this CA or Intermediates</TH>
	</TR>
	<TR>
		<TH>Name</TH>
		<TH>Org</TH>
		<TH>Org Unit</TH>
	</TR>
<? foreach($clientCerts as &$c) {
	$class = ($class == 'on') ? 'off' : 'on'; ?>
	<TR>
		<TD CLASS="<?= $class; ?>">
			<?= $c->CommonName; ?>
		</TD>
		<TD CLASS="<?= $class; ?>">
			<?= $c->OrgName; ?>
		</TD>
		<TD CLASS="<?= $class; ?>">
			<?= $c->OrgUnitName; ?>
		</TD>
	</TR>
<? } ?>
</TABLE>
<? } ?>
<?= $this->getFormFooter(); ?>
<?= $this->getPageFooter(); ?>
