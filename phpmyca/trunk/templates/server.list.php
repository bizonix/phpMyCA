<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

// get phpdboform object
$list =& $this->getVar('list');

// what is the id property?
$idProp   = $list->getIdProperty();
$linkProp = 0;
if (count($list->searchResults) > 0) {
	$ar = array_keys($list->searchResults[0]);
	$linkProp = $ar[1];
	}

// get base query string for viewing items
$qs_sign   = $this->getActionQs(WA_ACTION_SERVER_SIGN);
$qs_add    = $this->getActionQs(WA_ACTION_SERVER_ADD);
$qs_imp    = $this->getActionQs(WA_ACTION_SERVER_IMPORT);
$base_qs   = $this->getActionQs($list->actionQsView,0);
$qs_export = $this->getActionQs(WA_ACTION_SERVER_EXPORT_ALL);
$class     = '';

// footer links
$l = array();
$this->addMenuLink($qs_sign,'Generate Cert From CSR','greenoutline');
$this->addMenuLink($qs_imp,'Import Server Certificate','greenoutline');
$this->addMenuLink($qs_add,'Create Server Certificate','greenoutline');
if ($list->hitsTotal > 0) {
	$this->addMenuLink($qs_export,'Download All Certs','greenoutline');
	}
$this->addMenuLink('./','Main Menu','greenoutline');
?>
<?= $this->getPageHeader(); ?>
<TABLE ALIGN="center" WIDTH="100%">
<?= $this->getFormListSearch(&$list); ?>

<?= $this->getListSortableHeader(&$list); ?>
<?
if (is_array($list->searchResults)) {
foreach($list->searchResults as &$row) {
	$class = (substr($class,0,2) == 'on') ? 'off' : 'on';
	$cert =& new phpmycaCert($row,'server','user',false);
	if (!$cert->populated) { continue; }
	// expired or revoked?
	$expired = $cert->isExpired();
	$revoked = $cert->isRevoked();
	if ($expired) { $class .= ' expired'; }
	if ($revoked) { $class .= ' revoked'; }
	// expiring soon?
	if (!$expired and !$revoked) {
		if ($cert->isExpired(30)) {
			$class .= ' expire30';
			} elseif ($cert->isExpired(60)) {
			$class .= ' expire60';
			} elseif ($cert->isExpired(90)) {
			$class .= ' expire90';
			}
		}
?>
	<TR>
<? foreach($row as $prop => &$val) {
	if ($prop == $idProp) { continue; }
	$td = $val;
	if ($prop == $linkProp and is_numeric($cert->Id)) {
		if ($val == '') { $val = 'not set'; }
		$td = '<A HREF="' . $base_qs . $cert->Id . '">' . $val . '</A>';
		}
?>
		<TD CLASS="<?= $class; ?>"><?= $td; ?></TD>
<?	} ?>
	</TR>
<?	}
}
?>
<?= $this->getListPager(&$list); ?>
</TABLE>
<?= $this->getPageFooter(); ?>
