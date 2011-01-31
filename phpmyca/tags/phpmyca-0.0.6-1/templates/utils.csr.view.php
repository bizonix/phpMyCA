<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

// grab data if it is set
$csr_pem     =& $this->getVar('csr_pem');
$csr_asn     =& $this->getVar('csr_asn');
$csr_key     =& $this->getVar('csr_key');
$csr_subject =& $this->getVar('csr_subject');

// attempt to parse out filename they provided
$fileName = (isset($_FILES['csr']['name'])) ? ': ' . $_FILES['csr']['name'] : '';

// breadcrumb
$qs_back = $this->getMenuQs(MENU_UTILITIES);
$this->addMenuLink('javascript:clearForm(document.viewcsr);','Clear Form','greenoutline');
$this->addMenuLink('javascript:document.viewcsr.submit();','Examine CSR','greenoutline');
$this->addMenuLink($qs_back,'Back','greenoutline');
?>
<?= $this->getPageHeader(false,true); ?>
<?= $this->getFormHeader('viewcsr',null,null,true); ?>
<?= $this->getFormBreadCrumb(); ?>
<INPUT TYPE="hidden" NAME="<? echo WA_QS_CONFIRM; ?>" VALUE="yes">
<INPUT TYPE="hidden" NAME="MAX_FILE_SIZE" value="20000">
<P>
Upload a CSR or copy and paste the CSR into the form field below.
</P>
<TABLE>
	<TR>
		<TH COLSPAN="2">
			Upload CSR: <INPUT TYPE="file" name="csr_file">
		</TH>
	</TR>
	<TR>
		<TH COLSPAN="2">
			Copy and Paste CSR
		</TH>
	</TR>
	<TR>
		<TD COLSPAN="2">
			<TEXTAREA NAME="csr" COLS="70" ROWS="15"><? if (is_string($csr_pem)) { echo $csr_pem; } ?></TEXTAREA>
		</TD>
	</TR>
<? if (is_array($csr_subject) and count($csr_subject) > 0) { ?>
	<TR>
		<TH COLSPAN="2">Output of openssl_csr_get_subject()</TH>
	</TR>
	<TR>
		<TD COLSPAN="2"><PRE><? print_r($csr_subject); ?></PRE></TD>
	</TR>
<? } ?>
<? if (is_array($csr_key)) { ?>
	<TR>
		<TH COLSPAN="2">Output of openssl_pkey_get_details()</TH>
	</TR>
	<TR>
		<TD COLSPAN="2"><PRE><? print_r($csr_key); ?></PRE></TD>
	</TR>
<? } ?>
<? if (is_array($csr_asn)) { ?>
	<TR>
		<TH COLSPAN="2">Output of phpmycaParse::parseAsn()</TH>
	</TR>
	<TR>
		<TD COLSPAN="2"><PRE><? print_r($csr_asn); ?></PRE></TD>
	</TR>
<? } ?>
</TABLE>
<?= $this->getFormFooter(); ?>
<?= $this->getPageFooter(); ?>
