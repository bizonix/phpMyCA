<?
/**
 * phpmyca - upload pkcs12 file
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

$qs_back  = $this->getMenuQs(MENU_UTILITIES);

// footer links
$this->addMenuLink($qs_back,'Back','greenoutline');
$this->addMenuLink('javascript:clearForm(document.getcert);','Clear Form','greenoutline');
$this->addMenuLink('javascript:document.getcert.submit();','Continue','greenoutline');
?>
<?= $this->getPageHeader(); ?>
<?= $this->getFormHeader('getcert',null,null,true); ?>
<?= $this->getFormBreadCrumb(); ?>
<INPUT TYPE="hidden" NAME="MAX_FILE_SIZE" value="20000">
<P>
If the private key is encrypted enter the pass phrase in the form field below.
The Export Pass Phrase is used to unlock the pkc12 certificate
store, when importing it into a browser, for example.
</P>
<TABLE ALIGN="center">
	<TR>
		<TH>
			PKCS12 File
		</TH>
		<TD>
			<INPUT TYPE="file" name="pkcs12_file">
		</TD>
	</TR>
<? $val = (isset($_POST['expPass'])) ? $_POST['expPass'] : ''; ?>
	<TR>
		<TH>Export Pass Phrase</TH>
		<TD>
			<INPUT TYPE="password" NAME="expPass" VALUE="<?= $val; ?>" SIZE="40" MAXLENGTH="64">
		</TD>
	</TR>
</TABLE>
<?= $this->getFormFooter(); ?>
<?= $this->getPageFooter(); ?>
