<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');
// breadcrumb
$qs_back = $this->getMenuQs(MENU_CERTS_CA);

// message passing url when a CA is selected
$popUrl = $this->getPopulateFormDataQs(WA_ACTION_CA_POPULATE_FORM,0);

// Add in form utility javascript
$this->htmlJsAdd('js/formUtil.js');

// footer links
$this->addMenuLink($qs_back,'Cancel','redoutline');
$this->addMenuLink('javascript:clearForm(document.addcert);','Clear Form','greenoutline');
$this->addMenuLink('javascript:document.addcert.submit();','Create CA','greenoutline');
?>
<?= $this->getPageHeader(false,true); ?>
<?= $this->getFormHeader('addcert'); ?>
<?= $this->getFormBreadCrumb(); ?>
<INPUT TYPE="hidden" NAME="<? echo WA_QS_CONFIRM; ?>" VALUE="yes">
<P>
The new CA can either be self signed or issued as an intermediate cert by an
existing CA.  To issue this CA as an intermediate cert select the issuer
CA and provide the CA cert passphrase if required.
</P>
<TABLE>
	<COLGROUP><COL WIDTH="180px"></COLGROUP>
<? $val = (isset($_POST['caId'])) ? $_POST['caId'] : 'self'; ?>
	<TR>
		<TH>Issuer</TH>
		<TD>
			<?= $this->getFormSelectCa('caId',$val,'caSelected(this,\'' . $popUrl . '\');'); ?>
		</TD>
		<TD>
			Optional - generate as intermediate cert
		</TD>
	</TR>
<? $val = (isset($_POST['caPassPhrase'])) ? $_POST['caPassPhrase'] : ''; ?>
	<TR>
		<TH>Issuer Passphrase</TH>
		<TD COLSPAN="2">
			<INPUT TYPE="password" NAME="caPassPhrase" VALUE="<?= $val; ?>" SIZE="40" MAXLENGTH="64">
		</TD>
	</TR>
</TABLE>
<P>
Please provide the basic information needed to generate the CA certifcate
by filling in the form fields below.  Keep in mind that corrections or
changes cannot be made later without having to generate a new CA certificate
and all of the client certificates that might be signed with this CA certificate.
</P>
<TABLE>
	<COLGROUP><COL WIDTH="180px"></COLGROUP>
<? $val = (isset($_POST['CommonName'])) ? $_POST['CommonName'] : ''; ?>
	<TR>
		<TH>Name of CA</TH>
		<TD>
			<INPUT TYPE="text" NAME="CommonName" VALUE="<?= $val; ?>" SIZE="40" MAXLENGTH="64">
		</TD>
	    <TD>
			(commonName)
	    </TD>
	</TR>
<? $val = (isset($_POST['OrgName'])) ? $_POST['OrgName'] : ''; ?>
	<TR>
		<TH>Organization Name</TH>
		<TD>
			<INPUT TYPE="text" NAME="OrgName" VALUE="<?= $val; ?>" SIZE="40" MAXLENGTH="64">
		</TD>
		<TD>
			(organizationName)
		</TD>
	</TR>
<? $val = (isset($_POST['OrgUnitName'])) ? $_POST['OrgUnitName'] : ''; ?>
	<TR>
		<TH>Department Name</TH>
		<TD>
			<INPUT TYPE="text" NAME="OrgUnitName" VALUE="<?= $val; ?>" SIZE="40" MAXLENGTH="64">
		</TD>
		<TD>
			(organizationalUnitName)
		</TD>
	</TR>
</TABLE>
<P>
The physical location of the above entity.  Tthe country code which should be a
proper ISO 2 letter country code.
</P>
<TABLE>
	<COLGROUP><COL WIDTH="180px"></COLGROUP>
<? $val = (isset($_POST['LocalityName'])) ? $_POST['LocalityName'] : ''; ?>
	<TR>
		<TH>City</TH>
		<TD>
			<INPUT TYPE="text" NAME="LocalityName" VALUE="<?= $val; ?>" SIZE="40" MAXLENGTH="64">
		</TD>
		<TD>
			(localityName)
		</TD>
	</TR>
<? $val = (isset($_POST['CountryName'])) ? $_POST['CountryName'] : ''; ?>
	<TR>
		<TH>Country</TH>
		<TD>
			<INPUT TYPE="text" NAME="CountryName" VALUE="<?= $val; ?>" SIZE="2">
		</TD>
		<TD>
			(countryName)
		</TD>
	</TR>
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
<P>
Optionally specify a passphrase for the private key.
</P>
<TABLE>
	<COLGROUP><COL WIDTH="180px"></COLGROUP>
<? $val = (isset($_POST['PassPhrase'])) ? $_POST['PassPhrase'] : ''; ?>
	<TR>
		<TH>Passphrase</TH>
		<TD COLSPAN="2">
			<INPUT TYPE="password" NAME="PassPhrase" VALUE="<?= $val; ?>" SIZE="40" MAXLENGTH="64">
		</TD>
	</TR>
</TABLE>
<?= $this->getFormFooter(); ?>
<?= $this->getMessageFrame(); ?>
<script type="text/javascript">
// auto-populate on page load
var el = document.getElementsByName('caId')[0];
if (el) {
	var curVal = el.value;
	<?= 'var url = "' . $popUrl; ?>";
	caSelected(el,url);
	}
</script>
<?= $this->getPageFooter(); ?>
