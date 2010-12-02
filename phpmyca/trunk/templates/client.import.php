<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');
// breadcrumb
$qs_back = $this->getMenuQs(MENU_CERTS_CLIENT);

// footer links
$this->addMenuLink($qs_back,'Cancel','redoutline');
$this->addMenuLink('javascript:clearForm(document.importcert);','Clear Form','greenoutline');
$this->addMenuLink('javascript:document.importcert.submit();','Import Certificate','greenoutline');
?>
<?= $this->getPageHeader(false,true); ?>
<?= $this->getFormHeader('importcert',null,null,true); ?>
<?= $this->getFormBreadCrumb(); ?>
<INPUT TYPE="hidden" NAME="<? echo WA_QS_CONFIRM; ?>" VALUE="yes">
<INPUT TYPE="hidden" NAME="MAX_FILE_SIZE" value="20000">
<P>
To import the client certificate provide the relevant PEM encoded values via the
form fields below.  Values can either be provided by uploading the actual files
or by manually copying and pasting directly into the form text fields.  If a
private key is provided and is encrypted, enter the password required to
decrypt it.
</P>
<TABLE>
	<TR>
		<TH>
			Private Key Password
		</TH>
		<TD>
			<INPUT TYPE="password" NAME="pass" SIZE="30" MAXLENGTH="100" VALUE="<?= (isset($_POST['pass'])) ? $_POST['pass'] : ''; ?>">
		</TD>
	</TR>
	<TR>
		<TH COLSPAN="2">
			Upload Files Directly
		</TH>
	</TR>
	<TR>
		<TD>
			Certficate (required)
		</TD>
		<TD>
			<INPUT TYPE="file" name="cert_file">
		</TD>
	</TR>
	<TR>
		<TD>
			Private Key (required)
		</TD>
		<TD>
			<INPUT TYPE="file" name="key_file">
		</TD>
	</TR>
	<TR>
		<TD>
			CSR
		</TD>
		<TD>
			<INPUT TYPE="file" name="csr_file">
		</TD>
	</TR>
	<TR>
		<TH COLSPAN="2">Certficate (required)</TH>
	</TR>
	<TR>
		<TD COLSPAN="2">
			<TEXTAREA NAME="cert" COLS="70" ROWS="28"><? if (isset($_POST['cert'])) { echo $_POST['cert']; } ?></TEXTAREA>
		</TD>
	</TR>
	<TR>
		<TH COLSPAN="2">Private Key (required)</TH>
	</TR>
	<TR>
		<TD COLSPAN="2">
			<TEXTAREA NAME="key" COLS="70" ROWS="28"><? if (isset($_POST['key'])) { echo $_POST['key']; } ?></TEXTAREA>
		</TD>
	</TR>
	<TR>
		<TH COLSPAN="2">CSR</TH>
	</TR>
	<TR>
		<TD COLSPAN="2">
			<TEXTAREA NAME="csr" COLS="70" ROWS="28"><? if (isset($_POST['csr'])) { echo $_POST['csr']; } ?></TEXTAREA>
		</TD>
	</TR>
</TABLE>
<?= $this->getFormFooter(); ?>
<?= $this->getPageFooter(); ?>
