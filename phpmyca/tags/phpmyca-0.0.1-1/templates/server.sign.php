<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');
// breadcrumb
$qs_back = $this->getMenuQs(MENU_CERTS_SERVER);

// generate some displayable information from provided csr
$csr = (isset($_POST['csr'])) ? $_POST['csr'] : false;
$dnconfig = false;
if (is_string($csr)) {
	$dnconfig = openssl_csr_get_subject($csr,false);
	}

// footer links
$this->addMenuLink($qs_back,'Cancel','redoutline');
$this->addMenuLink('javascript:document.signcert.submit();','Generate Certificate','greenoutline');
?>
<?= $this->getPageHeader(); ?>
<?= $this->getFormHeader('signcert'); ?>
<?= $this->getFormBreadCrumb(); ?>
<INPUT TYPE="hidden" NAME="<? echo WA_QS_CONFIRM; ?>" VALUE="yes">
<? $val = (isset($_POST['csr'])) ? $_POST['csr'] : ''; ?>
<INPUT TYPE="hidden" NAME="csr" value="<?= $val; ?>">
<P>
Please provide the basic information needed to generate the server certifcate
by filling in the form fields below.</P>
<TABLE>
	<COLGROUP><COL WIDTH="180px"></COLGROUP>
<? $val = (isset($_POST['caId'])) ? $_POST['caId'] : false; ?>
	<TR>
		<TH>Signing Certificate Authority</TH>
		<TD COLSPAN="2">
			<?= $this->getFormSelectCa('caId',$val); ?>
		</TD>
	</TR>
<? $val = (isset($_POST['caPassPhrase'])) ? $_POST['caPassPhrase'] : ''; ?>
	<TR>
		<TH>Certificate Authority Passphrase</TH>
		<TD>
			<INPUT TYPE="password" NAME="caPassPhrase" VALUE="<?= $val; ?>" SIZE="40" MAXLENGTH="64">
		</TD>
	    <TD>
			Enter the CA passphrase, if needed.
	    </TD>
	</TR>
<? if (isset($dnconfig['commonName'])) { ?>
	<TR>
		<TH>Host Name</TH>
		<TD><?= $dnconfig['commonName']; ?></TD>
		<TD>commonName</TD>
	</TR>
<? } ?>
<? if (isset($dnconfig['organizationName'])) { ?>
	<TR>
		<TH>Host Name</TH>
		<TD><?= $dnconfig['organizationName']; ?></TD>
		<TD>organizationName</TD>
	</TR>
<? } ?>
<? if (isset($dnconfig['organizationalUnitName'])) { ?>
	<TR>
		<TH>Department Name</TH>
		<TD><?= $dnconfig['organizationalUnitName']; ?></TD>
		<TD>organizationalUnitName</TD>
	</TR>
<? } ?>
<? if (isset($dnconfig['emailAddress'])) { ?>
	<TR>
		<TH>Contact Email Address</TH>
		<TD><?= $dnconfig['emailAddress']; ?></TD>
		<TD>emailAddress</TD>
	</TR>
<? } ?>
<? if (isset($dnconfig['localityName'])) { ?>
	<TR>
		<TH>City</TH>
		<TD><?= $dnconfig['localityName']; ?></TD>
		<TD>localityName</TD>
	</TR>
<? } ?>
<? if (isset($dnconfig['stateOrProvinceName'])) { ?>
	<TR>
		<TH>State/Province</TH>
		<TD><?= $dnconfig['stateOrProvinceName']; ?></TD>
		<TD>stateOrProvinceName</TD>
	</TR>
<? } ?>
<? if (isset($dnconfig['countryName'])) { ?>
	<TR>
		<TH>Country</TH>
		<TD><?= $dnconfig['countryName']; ?></TD>
		<TD>countryName</TD>
	</TR>
<? } ?>
<? $val = (isset($_POST['Days'])) ? $_POST['Days'] : ''; ?>
	<TR>
		<TH>Days Valid</TH>
		<TD>
			<INPUT TYPE="text" NAME="Days" VALUE="<?= $val; ?>" SIZE="40" MAXLENGTH="5">
		</TD>
		<TD>
			Number of days this certificate is valid
		</TD>
	</TR>
</TABLE>
<?= $this->getFormFooter(); ?>
<?= $this->getPageFooter(); ?>
