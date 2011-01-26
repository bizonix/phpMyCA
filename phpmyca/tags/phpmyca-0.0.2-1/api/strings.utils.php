<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
/**
 * Strings used by utilities menu
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

define('WA_ACTION_IS_SIGNER',    'is');
define('WA_ACTION_CERT_VIEW',    'certv');
define('WA_ACTION_CSR_VIEW',     'csrv');
define('WA_ACTION_DEBUG_SIGNER', 'dbsigner');
define('WA_ACTION_GET_DER',      'gder');
define('WA_ACTION_PKCS12_VIEW',  'pkcs12v');
?>
