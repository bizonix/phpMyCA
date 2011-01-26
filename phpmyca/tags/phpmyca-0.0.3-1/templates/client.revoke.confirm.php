<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

$data =& $this->getVar('data');
if (!($data instanceof phpmycaClientCert)) {
	$m = 'Required data is missing, cannot continue.';
	die($this->getPageError($m));
	}

$qs_back   = $this->getActionQs($data->actionQsView);


// footer links
$this->addMenuLink($qs_back,'Cancel','redoutline');
$this->addMenuLink('javascript:document.revokecert.submit();','Revoke','greenoutline');
?>
<?= $this->getPageHeader(false,true); ?>
<?= $this->getFormHeader('revokecert'); ?>
<?= $this->getFormBreadCrumb(); ?>
<INPUT TYPE="hidden" NAME="<? echo WA_QS_CONFIRM; ?>" VALUE="yes">
<P>
Are you absolutely certain you want to revoke the certificate for <?= $data->getProperty('CommonName'); ?>?
This process is not reversible.
</P>
<?= $this->getFormFooter(); ?>
<?= $this->getPageFooter(); ?>
