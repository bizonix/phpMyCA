<?
/**
 * Certificate utilities main menu
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');
// footer links
$this->addMenuLink('./','Main Menu','greenoutline');
?>
<?= $this->getPageHeader(); ?>
<TABLE ALIGN="center">
	<TR>
		<TH>
			<A HREF="<?= $this->getActionQs(WA_ACTION_CERT_VIEW); ?>">Examine Certificate</A>
		</TH>
		<TD>
			Examine details of an X509 certificate.
		</TD>
	</TR>
	<TR>
		<TH>
			<A HREF="<?= $this->getActionQs(WA_ACTION_PKCS12_VIEW); ?>">Examine PKCS12</A>
		</TH>
		<TD>
			Examine contents of a PKCS12 file.
		</TD>
	</TR>
	<TR>
		<TH>
			<A HREF="<?= $this->getActionQs(WA_ACTION_IS_SIGNER); ?>">Is Cert Signer?</A>
		</TH>
		<TD>
			Input two certificates and determine if one signed the other.
		</TD>
	</TR>
	<TR>
		<TH>
			<A HREF="<?= $this->getActionQs(WA_ACTION_CSR_VIEW); ?>">Examine CSR</A>
		</TH>
		<TD>
			Examine details of a certificate signing request.
		</TD>
	</TR>
	<TR>
		<TH>
			<A HREF="<?= $this->getActionQs(WA_ACTION_DEBUG_SIGNER); ?>">Signature Debug</A>
		</TH>
		<TD>
			Attempt to decrypt signature of certificate with provided key.
		</TD>
	</TR>
</TABLE>
<?= $this->getPageFooter(); ?>
