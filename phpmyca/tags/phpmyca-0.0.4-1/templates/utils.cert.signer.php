<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

// results, if set
$isSigner    =& $this->getVar('isSigner');
$pem_issuer  =& $this->getVar('pem_issuer');
$pem_subject =& $this->getVar('pem_subject');

// breadcrumb
$qs_back = $this->getMenuQs(MENU_UTILITIES);
$this->addMenuLink($qs_back,'Cancel','redoutline');
$this->addMenuLink('javascript:clearForm(document.getcerts);','Clear Form','greenoutline');
$this->addMenuLink('javascript:document.getcerts.submit();','Check Certs','greenoutline');
?>
<?= $this->getPageHeader(false,true); ?>
<?= $this->getFormHeader('getcerts',null,null,true); ?>
<?= $this->getFormBreadCrumb(); ?>
<P>
Provide the issuer and subject certs to test by uploading the certificates or
copying and pasting them into the form fields below.
</P>
<? if (is_string($isSigner)) { ?>
<P>
<? if ($isSigner == 'yes') { ?>
<b><font color="green">The issuer cert is the signer of the subject cert.</font></b>
<? } else { ?>
<b><font color="red">The issuer cert is NOT the signer of the subject cert.</font></b>
<? } ?>
</P>
<? } ?>
<TABLE>
	<TR>
		<TH COLSPAN="2">
			Upload Files Directly
		</TH>
	</TR>
	<TR>
		<TD>
			Issuer Certficate
		</TD>
		<TD>
			<INPUT TYPE="file" name="pem_issuer_file">
		</TD>
	</TR>
	<TR>
		<TD>
			Subject Certficate
		</TD>
		<TD>
			<INPUT TYPE="file" name="pem_subject_file">
		</TD>
	</TR>
	<TR>
		<TH COLSPAN="2">Issuer Cert (<a onclick="javascript:document.getcerts.pem_issuer.value='';">clear</a>)</TH>
	</TR>
	<TR>
		<TD COLSPAN="2">
			<TEXTAREA NAME="pem_issuer" COLS="70" ROWS="20"><? if (is_string($pem_issuer)) { echo $pem_issuer; } ?></TEXTAREA>
		</TD>
	</TR>
	<TR>
		<TH COLSPAN="2">Subject Cert (<a onclick="javascript:document.getcerts.pem_subject.value='';">clear</a>)</TH>
	</TR>
	<TR>
		<TD COLSPAN="2">
			<TEXTAREA NAME="pem_subject" COLS="70" ROWS="20"><? if (is_string($pem_subject)) { echo $pem_subject; } ?></TEXTAREA>
		</TD>
	</TR>

</TABLE>
<?= $this->getFormFooter(); ?>
<?= $this->getPageFooter(); ?>
