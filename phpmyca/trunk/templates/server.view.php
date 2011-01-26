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
if (is_a($this->getVar('issuer'),'phpmycaCaCert')) {
	$issuer =& $this->getVar('issuer');
	} else {
	$issuer = false;
	}

$hasContact = ($data->getProperty('CountryName') or
               $data->getProperty('EmailAddress') or
               $data->getProperty('LocalityName') or
               $data->getProperty('OrgName') or
               $data->getProperty('OrgUnitName') or
               $data->getProperty('StateName'));
$qs_back    = $this->getActionQs($data->actionQsList);
$qs_edit    = $this->getActionQs($data->actionQsEdit);
$qs_issuer  = $this->getMenuQs(MENU_CERTS_CA)
            . '&' . WA_QS_ACTION . '=' . WA_ACTION_CA_VIEW
            . '&' . WA_QS_ID . '=' . $data->getProperty('ParentId');
$qs_bundle  = $this->getActionQs(WA_ACTION_BUNDLE);
$qs_pkcs12  = $this->getActionQs(WA_ACTION_SERVER_PKCS12);

$isEncrypted = (strpos($data->getProperty('PrivateKey'),'ENCRYPTED') === false) ? false : true;

// expired or revoked?
$expireDate = $data->getProperty('ValidTo');
$revokeDate = $data->getProperty('RevokeDate');
$expireTime = ($expireDate) ? strtotime($expireDate) : false;
$revokeTime = ($revokeDate) ? strtotime($revokeDate) : false;
$now        = time();
$day        = 60 * 60 * 24;
$now30      = $now + ($day * 30);
$now60      = $now + ($day * 60);
$now90      = $now + ($day * 90);
$expired    = ($expireTime && ($now > $expireTime));
$revoked    = ($revokeTime && ($now > $revokeTime));
// set class for expired
$expireClass = '';
if (!$expired and !$revoked and $expireTime) {
	if ($now30 > $expireTime) {
		$expireClass = ' class="expire30"';
		} elseif ($now60 > $expireTime) {
		$expireClass = ' class="expire60"';
		} elseif ($now90 > $expireTime) {
		$expireClass = ' class="expire90"';
		}
	}

$qs_download = $this->getActionQs(WA_ACTION_BROWSER_IMPORT);

// footer links
$this->addMenuLink($qs_download,'Download Cert','greenoutline');
if ($data->getProperty('PrivateKey')) {
	if ($isEncrypted) {
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
$this->addMenuLink($qs_edit,'Edit','greenoutline');
$this->addMenuLink($qs_back,'Back','greenoutline');
?>
<?= $this->getPageHeader(); ?>
<TABLE ALIGN="center">
	<TR>
		<TH>Certificate ID</TH>
		<TD>
			<?= $data->getProperty('Id') . "\n"; ?>
		</TD>
	</TR>
	<TR>
		<TH>Description</TH>
		<TD>
			<?= $data->getProperty('Description') . "\n"; ?>
		</TD>
	</TR>
	<TR>
		<TH>Server (commonName)</TH>
		<TD>
			<?= $data->getProperty('CommonName') . "\n"; ?>
		</TD>
	</TR>
<? if ($revoked) { ?>
	<TR>
		<TH>Date Revoked</TH>
		<TD>
			<?= $data->getProperty('RevokeDate'); ?>
		</TD>
	</TR>
<? } else { ?>
	<TR>
		<TH>Date Valid</TH>
		<TD<?= $expireClass; ?>>
			<?= $data->getProperty('ValidFrom') . ' to ' . $data->getProperty('ValidTo') . "\n"; ?>
		</TD>
	</TR>
<? } ?>
<? if ($hasContact) { ?>
	<TR>
		<TH COLSPAN="2">Contact Information</TH>
	</TR>
<? if ($data->getProperty('EmailAddress')) { ?>
	<TR>
		<TH>Email Address</TH>
		<TD><?= $data->getProperty('EmailAddress'); ?></TD>
	</TR>
<? } ?>
<? if ($data->getProperty('OrgName')) { ?>
	<TR>
		<TH>Organization</TH>
		<TD><?= $data->getProperty('OrgName'); ?></TD>
	</TR>
<? } ?>
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
if ($data->getProperty('PrivateKey')) {
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
if ($data->getProperty('PublicKey')) {
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
<? if ($data->getProperty('CSR')) { ?>
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
