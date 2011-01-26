<?
/**
 * phpmyca generic message
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');
?>
<?= $this->getPageHeader(); ?>
<?= $this->getVar('msg'); ?>
<?= $this->getPageFooter(); ?>
