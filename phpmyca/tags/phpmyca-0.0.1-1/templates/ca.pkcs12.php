<?
/**
 * phpmyca - obtain pkcs12 of ca certificate
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

$data =& $this->getVar('data');
if (!is_a($data,'phpmycaCaCert')) {
	$m = 'Required data is missing, cannot continue.';
	die($this->getPageError($m));
	}

$qs_back    = $this->getActionQs($data->actionQsView);

// footer links
$this->addMenuLink($qs_back,'Back','greenoutline');
$this->addMenuLink('javascript:clearForm(document.getcert);','Clear Form','greenoutline');
$this->addMenuLink('javascript:document.getcert.submit();','Download','greenoutline');
?>
<?= $this->getPageHeader(); ?>
<?= $this->getFormHeader('getcert'); ?>
<?= $this->getFormBreadCrumb(); ?>
<INPUT TYPE="hidden" NAME="<? echo WA_QS_CONFIRM; ?>" VALUE="yes">
<P>
If the private key is encrypted enter the pass phrase in the form field below.
The Export Pass Phrase is used to unlock the pkc12 certificate
store, when importing it into a browser, for example.
</P>
<TABLE ALIGN="center">
<? $val = (isset($_POST['keyPass'])) ? $_POST['keyPass'] : ''; ?>
	<TR>
		<TH>Private Key Pass Phrase</TH>
		<TD>
			<INPUT TYPE="password" NAME="keyPass" VALUE="<?= $val; ?>" SIZE="40" MAXLENGTH="64">
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
