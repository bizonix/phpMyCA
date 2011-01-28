<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

$data =& $this->getVar('data');
if (!is_a($data,'phpmycaServerCert')) {
	$m = 'Required data is missing, cannot continue.';
	die($this->getPageError($m));
	}

$server =& $this->getVar('server');
if (!($server instanceof phpmycaCert)) {
	$m = 'Server cert is missing, cannot continue.';
	die($this->getPageError($m));
	}

if (is_a($this->getVar('issuer'),'phpmycaCaCert')) {
	$issuer =& $this->getVar('issuer');
	} else {
	$issuer = false;
	}

$hasContact = (!empty($server->CountryName) or
               !empty($server->EmailAddress) or
               !empty($server->LocalityName) or
               !empty($server->OrgName) or
               !empty($server->OrgUnitName) or
               !empty($server->StateName));
$qs_back     = $this->getActionQs($data->actionQsList);
$qs_edit     = $this->getActionQs($data->actionQsEdit);
$qs_issuer   = $this->getMenuQs(MENU_CERTS_CA)
             . '&' . WA_QS_ACTION . '=' . WA_ACTION_CA_VIEW
             . '&' . WA_QS_ID . '=' . $server->ParentId;
$qs_bundle   = $this->getActionQs(WA_ACTION_BUNDLE);
$qs_pkcs12   = $this->getActionQs(WA_ACTION_SERVER_PKCS12);
$qs_download = $this->getActionQs(WA_ACTION_BROWSER_IMPORT);
$qs_revoke   = $this->getActionQs(WA_ACTION_SERVER_REVOKE);

// expired or revoked?
$expired = ($server->isExpired());
$revoked = ($server->isRevoked());
// set class for expired
$expireClass = '';
if (!$expired and !$revoked) {
	if ($server->isExpired(30)) {
		$expireClass = ' class="expire30"';
		} elseif ($server->isExpired(60)) {
		$expireClass = ' class="expire60"';
		} elseif ($server->isExpired(90)) {
		$expireClass = ' class="expire90"';
		}
	}

// footer links
if (!$expired and !$revoked) {
	if ($server->isRevokable()) {
		$this->addMenuLink($qs_revoke,'Revoke','redoutline');
		}
	$this->addMenuLink($qs_download,'Download Cert','greenoutline');
	if ($server->hasPrivateKey()) {
		if ($server->isEncrypted()) {
			$qs = $this->getActionQs(WA_ACTION_CHANGE_PASS);
			$this->addMenuLink($qs,'Change Private Key Password','greenoutline');
			$qs = $this->getActionQs(WA_ACTION_DECRYPT);
			$this->addMenuLink($qs,'Decrypt Private Key','greenoutline');
			} else {
			$qs = $this->getActionQs(WA_ACTION_ENCRYPT);
			$this->addMenuLink($qs,'Encrypt Private Key','greenoutline');
			}
		}
	$this->addMenuLink($qs_bundle,'Get CA Chain','greenoutline');
	$this->addMenuLink($qs_pkcs12,'Get PKCS12','greenoutline');
	}
$this->addMenuLink($qs_back,'Back','greenoutline');
?>
<?= $this->getPageHeader(); ?>
<TABLE ALIGN="center">
	<TR>
		<TH>Certificate ID</TH>
		<TD>
			<?= $server->Id . "\n"; ?>
		</TD>
	</TR>
	<TR>
		<TH>Description</TH>
		<TD>
			<?= $server->Description . "\n"; ?>
		</TD>
	</TR>
	<TR>
		<TH>Server (commonName)</TH>
		<TD>
			<?= $server->CommonName . "\n"; ?>
		</TD>
	</TR>
<? if ($revoked) { ?>
	<TR>
		<TH>Date Revoked</TH>
		<TD>
			<?= $server->RevokeDate; ?>
		</TD>
	</TR>
<? } else { ?>
	<TR>
		<TH>Date Valid</TH>
		<TD<?= $expireClass; ?>>
			<?= $server->ValidFrom . ' to ' . $server->ValidTo . "\n"; ?>
		</TD>
	</TR>
<? } ?>
<? if ($hasContact) { ?>
	<TR>
		<TH COLSPAN="2">Contact Information</TH>
	</TR>
<? if ($server->EmailAddress) { ?>
	<TR>
		<TH>Email Address</TH>
		<TD><?= $server->EmailAddress; ?></TD>
	</TR>
<? } ?>
<? if ($server->OrgName) { ?>
	<TR>
		<TH>Organization</TH>
		<TD><?= $server->OrgName; ?></TD>
	</TR>
<? } ?>
<? die('IAMHERE: ' . __FILE__ . ' line: ' . __LINE__); ?>
<? if ($data->getProperty('OrgUnitName')) { ?>
	<TR>
		<TH>Organizational Unit</TH>
		<TD><?= nl2br($data->getProperty('OrgUnitName')); ?></TD>
	</TR>
<? } ?>
<? if ($data->getProperty('LocalityName')) { ?>
	<TR>
		<TH>Location</TH>
		<TD><?= nl2br($data->getProperty('LocalityName')); ?></TD>
	</TR>
<? } ?>
<? if ($data->getProperty('StateName')) { ?>
	<TR>
		<TH>State/Province</TH>
		<TD><?= $data->getProperty('StateName'); ?></TD>
	</TR>
<? } ?>
<? if ($data->getProperty('CountryName')) { ?>
	<TR>
		<TH>Country</TH>
		<TD><?= $data->getProperty('CountryName'); ?></TD>
	</TR>
<? } ?>
<? } ?>
	<TR>
		<TH COLSPAN="2">Fingerprints</TH>
	</TR>
	<TR>
		<TH>MD5</TH>
		<TD>
			<?= $data->getProperty('FingerprintMD5') . "\n"; ?>
		</TD>
	</TR>
	<TR>
		<TH>SHA1</TH>
		<TD>
			<?= $data->getProperty('FingerprintSHA1') . "\n"; ?>
		</TD>
	</TR>
	<TR>
		<TH>Serial Number</TH>
		<TD>
			<?= $data->getProperty('SerialNumber') . "\n"; ?>
		</TD>
	</TR>
	<TR>
		<TH>Created</TH>
		<TD>
			<?= $data->getProperty('CreateDate') . "\n"; ?>
		</TD>
	</TR>
<? if (!$issuer) { ?>
	<TR>
		<TH>Issuer</TH>
		<TD>
			Self Signed
		</TD>
	</TR>
<? } ?>
</TABLE>

<? if ($issuer) { ?>
<?
$id  = 'tog_' . $this->getNumber();
$hr = '<A HREF="javascript:void(0)" ONCLICK="toggleDisplay(\'' . $id . '\')">'
    . 'Issuer</A>';
$targ  = '_viewCaCert' . $issuer->getProperty('Id');
$ca_cn = ($issuer->getProperty('CommonName')) ? $issuer->getProperty('CommonName') : 'not set';
$ca_hr = '<A TARGET="' . $targ . '" HREF="' . $qs_issuer . '">'
       . $ca_cn . '</A>';
?>
<DIV ID="dataCategory"><?= $hr; ?></DIV>
<DIV ID="<?= $id; ?>" STYLE="display: none">
<TABLE ALIGN="center">
	<TR>
		<TH>
			commonName
		</TH>
		<TD>
			<?= $ca_hr; ?>
		</TD>
	</TR>
<? if ($issuer->getProperty('OrgName')) { ?>
	<TR>
		<TH>
			Organization
		</TH>
		<TD>
			<?= $issuer->getProperty('OrgName'); ?>
		</TD>
	</TR>
<? } ?>
<? if ($issuer->getProperty('OrgUnitName')) { ?>
	<TR>
		<TH>
			Organizational Unit
		</TH>
		<TD>
			<?= $issuer->getProperty('OrgUnitName'); ?>
		</TD>
	</TR>
<? } ?>
<? if ($issuer->getProperty('ValidFrom') and $issuer->getProperty('ValidTo')) { ?>
	<TR>
		<TH>
			Date Valid
		</TH>
		<TD>
			<?= $issuer->getProperty('ValidFrom'); ?> to <?= $issuer->getProperty('ValidTo'); ?>
		</TD>
	</TR>
<? } ?>
</TABLE>
</DIV>
<? } ?>

<?
$id  = 'tog_' . $this->getNumber();
$hr = '<A HREF="javascript:void(0)" ONCLICK="toggleDisplay(\'' . $id . '\')">'
    . 'Certificate</A>';
?>
<DIV ID="dataCategory"><?= $hr; ?></DIV>
<DIV ID="<?= $id; ?>" STYLE="display: none">
<TABLE ALIGN="center">
	<TR>
		<TD>
			<PRE><?= $data->getProperty('Certificate') . "\n"; ?></PRE>
		</TD>
	</TR>
</TABLE>
</DIV>
<?
if ($data->hasPrivateKey()) {
$id  = 'tog_' . $this->getNumber();
$hr = '<A HREF="javascript:void(0)" ONCLICK="toggleDisplay(\'' . $id . '\')">'
    . 'Private Key</A>';
?>
<DIV ID="dataCategory"><?= $hr; ?></DIV>
<DIV ID="<?= $id; ?>" STYLE="display: none">
<TABLE ALIGN="center">
	<TR>
		<TD>
			<PRE><?= $data->getProperty('PrivateKey') . "\n"; ?></PRE>
		</TD>
	</TR>
</TABLE>
</DIV>
<? } ?>
<?
if ($data->hasPublicKey()) {
$id  = 'tog_' . $this->getNumber();
$hr = '<A HREF="javascript:void(0)" ONCLICK="toggleDisplay(\'' . $id . '\')">'
    . 'Public Key</A>';
?>
<DIV ID="dataCategory"><?= $hr; ?></DIV>
<DIV ID="<?= $id; ?>" STYLE="display: none">
<TABLE ALIGN="center">
	<TR>
		<TD>
			<PRE><?= $data->getProperty('PublicKey') . "\n"; ?></PRE>
		</TD>
	</TR>
</TABLE>
</DIV>
<? } ?>
<? if ($data->hasCsr()) { ?>
<?
$id  = 'tog_' . $this->getNumber();
$hr = '<A HREF="javascript:void(0)" ONCLICK="toggleDisplay(\'' . $id . '\')">'
    . 'Certificate Request</A>';
?>
<DIV ID="dataCategory"><?= $hr; ?></DIV>
<DIV ID="<?= $id; ?>" STYLE="display: none">
<TABLE ALIGN="center">
	<TR>
		<TD>
			<PRE><?= $data->getProperty('CSR') . "\n"; ?></PRE>
		</TD>
	</TR>
</TABLE>
</DIV>
<? } ?>
<?= $this->getPageFooter(); ?>
