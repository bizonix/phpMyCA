<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

// grab data if it is set
$cert_asn     =& $this->getVar('cert_asn');
$cert_pem     =& $this->getVar('cert_pem');
$cert_parse   =& $this->getVar('cert_parse');
$cert_key     =& $this->getVar('cert_key');
$cert_subject =& $this->getVar('cert_subject');
$cert_host    =& $this->getVar('cert_host');

// attempt to parse out filename they provided
$fileName = (isset($_FILES['cert']['name'])) ? ': ' . $_FILES['cert']['name'] : '';

// breadcrumb
$qs_back = $this->getMenuQs(MENU_UTILITIES);
if (is_string($cert_pem)) {
	$qs = $this->getActionQs(WA_ACTION_GET_DER) . '&cert=' . $cert_pem;
	$this->addMenuLink($qs,'Download DER','greenoutline');
	}
$this->addMenuLink('javascript:clearForm(document.viewcert);','Clear Form','greenoutline');
$this->addMenuLink('javascript:document.viewcert.submit();','Examine Cert','greenoutline');
$this->addMenuLink($qs_back,'Back','greenoutline');
?>
<?= $this->getPageHeader(false,true); ?>
<?= $this->getFormHeader('viewcert',null,null,true); ?>
<?= $this->getFormBreadCrumb(); ?>
<INPUT TYPE="hidden" NAME="<? echo WA_QS_CONFIRM; ?>" VALUE="yes">
<INPUT TYPE="hidden" NAME="MAX_FILE_SIZE" value="20000">
<P>
Upload a certificate or copy and paste the certificate into the form field below.
</P>
<TABLE>
	<TR>
		<TH COLSPAN="2">
			Upload Certficate: <INPUT TYPE="file" name="cert_file">
		</TH>
	</TR>
	<TR>
		<TH COLSPAN="2">
			hostname:port: <INPUT TYPE="text" NAME="cert_host" VALUE="<? if (is_string($cert_host)) { echo $cert_host; } ?>" SIZE="50">
		</TH>
	</TR>
	<TR>
		<TH COLSPAN="2">
			Copy and Paste Certificate
		</TH>
	</TR>
	<TR>
		<TD COLSPAN="2">
			<TEXTAREA NAME="cert" COLS="70" ROWS="20"><? if (is_string($cert_pem)) { echo $cert_pem; } ?></TEXTAREA>
		</TD>
	</TR>
<? if (is_array($cert_subject) and count($cert_subject) > 0) { ?>
	<TR>
		<TH COLSPAN="2">Output of openssl_x509_parse()</TH>
	</TR>
	<TR>
		<TD COLSPAN="2"><PRE><? print_r($cert_subject); ?></PRE></TD>
	</TR>
<? } ?>
<? if (is_array($cert_key)) { ?>
	<TR>
		<TH COLSPAN="2">Output of openssl_pkey_get_details()</TH>
	</TR>
	<TR>
		<TD COLSPAN="2"><PRE><? print_r($cert_key); ?></PRE></TD>
	</TR>
<? } ?>
<? if (is_array($cert_parse)) { ?>
	<TR>
		<TH COLSPAN="2">Output of phpmycaParse::parseCert()</TH>
	</TR>
	<TR>
		<TD COLSPAN="2"><PRE><? print_r($cert_parse); ?></PRE></TD>
	</TR>
<? } ?>
<? if (is_array($cert_asn)) { ?>
	<TR>
		<TH COLSPAN="2">Output of phpmycaParse::parseAsn()</TH>
	</TR>
	<TR>
		<TD COLSPAN="2"><PRE><? print_r($cert_asn); ?></PRE></TD>
	</TR>
<? } ?>
</TABLE>
<?= $this->getFormFooter(); ?>
<?= $this->getPageFooter(); ?>
