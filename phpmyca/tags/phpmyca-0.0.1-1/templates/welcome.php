<?
/**
 * phpmyca welcome screen
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');
?>
<?= $this->getPageHeader(); ?>
<TABLE ALIGN="center">
	<TR>
		<TH>
			<A HREF="<?= $this->getMenuQs(MENU_CERTS_CA); ?>">Manage Certificate Authorities</A>
		</TH>
		<TD>
			Actions related to the management of X.509 Certificate Authorities.
			List the CAs, create new CAs, list client certificates signed by CAs,
			revoke client certificates, etc...
		</TD>
	</TR>
	<TR>
		<TH>
			<A HREF="<?= $this->getMenuQs(MENU_CERTS_SERVER); ?>">Manage Server Certificates</A>
		</TH>
		<TD>
			Actions related to the management of server certificates.
			List the certs, create new certs, etc...
		</TD>
	</TR>
	<TR>
		<TH>
			<A HREF="<?= $this->getMenuQs(MENU_CERT_REQUESTS); ?>">Manage Certificate Requests</A>
		</TH>
		<TD>
			Generate and manage certificate requests.
		</TD>
	</TR>

	<TR>
		<TH>
			<A HREF="<?= $this->getMenuQs(MENU_CERTS_CLIENT); ?>">Manage Client Certificates</A>
		</TH>
		<TD>
			Actions related to the management of client certificates.
			List the certs, create new certs, etc...
		</TD>
	</TR>
	<TR>
		<TH>
			<A HREF="<?= $this->getMenuQs(MENU_UTILITIES); ?>">Certificate Utilities</A>
		</TH>
		<TD>
			Various certificate related utilities.
		</TD>
	</TR>
</TABLE>
<?= $this->getPageFooter(); ?>
