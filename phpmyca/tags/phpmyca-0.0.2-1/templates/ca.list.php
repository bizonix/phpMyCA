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
$qs_add    = $this->getActionQs(WA_ACTION_CA_ADD);
$qs_imp    = $this->getActionQs(WA_ACTION_CA_IMPORT);
$base_qs   = $this->getActionQs($list->actionQsView,0);
$qs_export = $this->getActionQs(WA_ACTION_CA_EXPORT_ALL);
$class     = '';

// for comparing expiration/revocation dates
$now   = time();
$day   = 60 * 60 * 24;
$now30 = $now + ($day * 30);
$now60 = $now + ($day * 60);
$now90 = $now + ($day * 90);

// footer links
$l  = array();
$this->addMenuLink($qs_imp,'Import CA Certificate','greenoutline');
$this->addMenuLink($qs_add,'Create CA Certificate','greenoutline');
if ($list->hitsTotal > 0) {
	$this->addMenuLink($qs_export,'Download All Certs','greenoutline');
	}
$this->addMenuLink('./','Main Menu','greenoutline');
?>
<?= $this->getPageHeader(); ?>
<TABLE ALIGN="center" WIDTH="100%">
<?= $this->getFormListSearch($list); ?>

<?= $this->getListSortableHeader(&$list); ?>
<?
if (is_array($list->searchResults)) {
foreach($list->searchResults as $row) {
	$class = ($class == 'on') ? 'off' : 'on';
	$id = (isset($row[$idProp])) ? $row[$idProp] : false;
	// expired or revoked?
	$t          = (isset($row['ValidTo']));
	$expireDate = ($t) ? strtotime($row['ValidTo']) : false;
	$expired    = ($t && ($now > $expireDate));
	$t          = (isset($row['RevokeDate']));
	$revokeDate = ($t) ? strtotime('RevokeDate') : false;
	$revoked = ($t && ($now > $revokeDate));
	if ($expired) { $class .= ' expired'; }
	if ($revoked) { $class .= ' revoked'; }
	// expiring soon?
	if (!$expired and !$revoked and $expireDate) {
		if ($now30 > $expireDate) {
			$class .= ' expire30';
			} elseif ($now60 > $expireDate) {
			$class .= ' expire60';
			} elseif ($now90 > $expireDate) {
			$class .= ' expire90';
			}
		}
?>
	<TR>
<? foreach($row as $prop => $val) {
	if ($prop == $idProp) { continue; }
	$td = $val;
	if ($prop == $linkProp and is_numeric($id)) {
		if ($val == '') { $val = 'not set'; }
		$td = '<A HREF="' . $base_qs . $id . '">' . $val . '</A>';
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
