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
$base_qs   = $this->getActionQs($list->actionQsView,0);
$qs_add    = $this->getActionQs(WA_ACTION_CLIENT_ADD);
$qs_imp    = $this->getActionQs(WA_ACTION_CLIENT_IMPORT);
$qs_export = $this->getActionQs(WA_ACTION_CLIENT_EXPORT_ALL);
$class     = '';

// footer links
$l = array();
$this->addMenuLink($qs_imp,'Import Client Certificate','greenoutline');
$this->addMenuLink($qs_add,'Create Client Certificate','greenoutline');
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
	$list->resetProperties();
	$list->populateFromArray($row);
	$id = ($list->getProperty('Id')) ? $list->getProperty('Id') : false;
	// expired or revoked?
	$expired = $list->isExpired();
	$revoked = $list->isRevoked();
	if ($expired) { $class .= ' expired'; }
	if ($revoked) { $class .= ' revoked'; }
	// expiring soon?
	if (!$expired and !$revoked) {
		if ($list->isExpired(30)) {
			$class .= ' expire30';
			} elseif ($list->isExpired(60)) {
			$class .= ' expire60';
			} elseif ($list->isExpired(90)) {
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
