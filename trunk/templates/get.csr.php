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

// footer links
$this->addMenuLink($qs_back,'Cancel','redoutline');
$this->addMenuLink('javascript:clearForm(document.signcert);','Clear Form','greenoutline');
$this->addMenuLink('javascript:document.signcert.submit();','Continue','greenoutline');
?>
<?= $this->getPageHeader(false,true); ?>
<?= $this->getFormHeader('signcert'); ?>
<?= $this->getFormBreadCrumb(); ?>
<INPUT TYPE="hidden" NAME="<? echo WA_QS_CONFIRM; ?>" VALUE="yes">
<P>
Please provide PEM formatted CSR in the field below.</P>
<TABLE>
	<COLGROUP><COL WIDTH="180px"></COLGROUP>
	<TR>
		<TH>CSR (required)</th>
	</TR>
	<TR>
		<TD>
			<TEXTAREA NAME="csr" COLS="70" ROWS="28"><? if (isset($_POST['csr'])) { echo $_POST['csr']; } ?></TEXTAREA>
		</TD>
	</TR>
</TABLE>
<?= $this->getFormFooter(); ?>
<?= $this->getPageFooter(); ?>
