<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

// results, if set
$debug_txt =& $this->getVar('debug_txt');
$pem_cert  =& $this->getVar('pem_cert');
$pem_key   =& $this->getVar('pem_key');

// breadcrumb
$qs_back = $this->getMenuQs(MENU_UTILITIES);
$this->addMenuLink($qs_back,'Cancel','redoutline');
$this->addMenuLink('javascript:clearForm(document.getcerts);','Clear Form','greenoutline');
$this->addMenuLink('javascript:document.getcerts.submit();','Check Certs','greenoutline');
?>
<?= $this->getPageHeader(false,true); ?>
<?= $this->getFormHeader('getcerts',null,null,true); ?>
<?= $this->getFormBreadCrumb(); ?>
<INPUT TYPE="hidden" NAME="MAX_FILE_SIZE" value="20000">
<P>
Upload the certificate and key or copy and paste them in the form fields below.
</P>
<TABLE>
	<TR>
		<TH COLSPAN="2">
			Upload Certficate: <INPUT TYPE="file" name="cert_file">
		</TH>
	</TR>
	<TR>
		<TH COLSPAN="2">
			Upload Key: <INPUT TYPE="file" name="key_file">
		</TH>
	</TR>
	<TR>
		<TH COLSPAN="2">
			Copy and Paste Certificate (<A ONCLICK="javascript:document.getcerts.cert.value='';">clear</A>)
		</TH>
	</TR>
	<TR>
		<TD COLSPAN="2">
			<TEXTAREA NAME="cert" COLS="70" ROWS="20"><? if (is_string($pem_cert)) { echo $pem_cert; } ?></TEXTAREA>
		</TD>
	</TR>
	<TR>
		<TH COLSPAN="2">
			Copy and Paste Key (<A ONCLICK="javascript:document.getcerts.key.value='';">clear</A>)
		</TH>
	</TR>
	<TR>
		<TD COLSPAN="2">
			<TEXTAREA NAME="key" COLS="70" ROWS="10"><? if (is_string($pem_key)) { echo $pem_key; } ?></TEXTAREA>
		</TD>
	</TR>
<? if (is_string($debug_txt)) { ?>
	<TR>
		<TH COLSPAN="2">
			Debug Information
		</TH>
	</TR>
	<TR>
		<TD COLSPAN="2">
<?= $debug_txt; ?>
		</TD>
	</TR>
<? } ?>
</TABLE>
<?= $this->getFormFooter(); ?>
<?= $this->getPageFooter(); ?>
