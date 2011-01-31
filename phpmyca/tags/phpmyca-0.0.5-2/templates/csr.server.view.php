<?
/**
 * phpmyca - view server certificate
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

$data =& $this->getVar('data');
if (!is_a($data,'phpmycaCsrServer')) {
	$m = 'Required data is missing, cannot continue.';
	die($this->getPageError($m));
	}

$hasContact = ($data->getProperty('CountryName') or
               $data->getProperty('EmailAddress') or
               $data->getProperty('LocalityName') or
               $data->getProperty('OrgName') or
               $data->getProperty('OrgUnitName') or
               $data->getProperty('StateName'));
$qs_back    = $this->getActionQs($data->actionQsList);
$qs_edit    = $this->getActionQs($data->actionQsEdit);
$isEncrypted = (strpos($data->getProperty('PrivateKey'),'ENCRYPTED') === false) ? false : true;

$qs_download = $this->getActionQs(WA_ACTION_CSR_SERVER_DOWNLOAD);

// footer links
$this->addMenuLink($qs_download,'Download CSR','greenoutline');
if ($data->getProperty('PrivateKey')) {
	if ($isEncrypted) {
		$qs = $this->getActionQs(WA_ACTION_CSR_SERVER_CHANGE_PASS);
		$this->addMenuLink($qs,'Change Private Key Password','greenoutline');
		$qs = $this->getActionQs(WA_ACTION_CSR_SERVER_DECRYPT);
		$this->addMenuLink($qs,'Decrypt Private Key','greenoutline');
		} else {
		$qs = $this->getActionQs(WA_ACTION_CSR_SERVER_ENCRYPT);
		$this->addMenuLink($qs,'Encrypt Private Key','greenoutline');
		}
	}
$this->addMenuLink($qs_edit,'Edit','greenoutline');
$this->addMenuLink($qs_back,'Back','greenoutline');
?>
<?= $this->getPageHeader(); ?>
<TABLE ALIGN="center">
	<TR>
		<TH>CSR ID</TH>
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
		<TH>Created</TH>
		<TD>
			<?= $data->getProperty('CreateDate') . "\n"; ?>
		</TD>
	</TR>
</TABLE>

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
