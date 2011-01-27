<?
/**
 * Strings used by phpmyca
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

// forms
define('WA_QS_ACTION',        'a');
define('WA_QS_CONFIRM',       'confirm');
define('WA_QS_DAYS',          'days');
define('WA_QS_ID',            'id');
define('WA_QS_MENU',          'menu');
define('WA_QS_ORDER',         'o');
define('WA_QS_PAGE',          'p');
define('WA_QS_SORT',          's');
define('WA_ACTION_PREVIOUS',  'ap');
// searching...
define('WA_QS_SEARCH_FIELDS', 'sf');
define('WA_QS_SEARCH_TERMS',  'st');
define('WA_QS_SEARCH_TYPES',  'stxt');
// misc
define('FRAME_MSG',   'frame_msg');
define('FRAME_HID',   'frame_hid');
define('WA_MAX_ROWS', 20);
define('MAX_XID_ADD', 10);
// CA Cert Action Strings
define('WA_ACTION_CA_ADD',            'caa');
define('WA_ACTION_CA_EDIT',           'cae');
define('WA_ACTION_CA_EXPORT',         'caex');
define('WA_ACTION_CA_EXPORT_ALL',     'caexa');
define('WA_ACTION_CA_IMPORT',         'caimp');
define('WA_ACTION_CA_LIST',           'cal');
define('WA_ACTION_CA_PKCS12',         'pkcs12');
define('WA_ACTION_CA_POPULATE_FORM',  'popcaform');
define('WA_ACTION_CA_REVOKE',         'carev');
define('WA_ACTION_CA_VIEW',           'cav');
// Client cert action strings
define('WA_ACTION_CLIENT_ADD',        'ca');
define('WA_ACTION_CLIENT_EDIT',       'ce');
define('WA_ACTION_CLIENT_EXPORT',     'cex');
define('WA_ACTION_CLIENT_EXPORT_ALL', 'cexa');
define('WA_ACTION_CLIENT_IMPORT',     'cimp');
define('WA_ACTION_CLIENT_LIST',       'cl');
define('WA_ACTION_CLIENT_PKCS12',     'pkcs12');
define('WA_ACTION_CLIENT_REVOKE',     'clrev');
define('WA_ACTION_CLIENT_SIGN',       'ccsr');
define('WA_ACTION_CLIENT_VIEW',       'cv');
// Server Cert Action Strings
define('WA_ACTION_SERVER_ADD',        'sa');
define('WA_ACTION_SERVER_EDIT',       'se');
define('WA_ACTION_SERVER_EXPORT',     'sex');
define('WA_ACTION_SERVER_EXPORT_ALL', 'sexa');
define('WA_ACTION_SERVER_IMPORT',     'simp');
define('WA_ACTION_SERVER_LIST',       'sl');
define('WA_ACTION_SERVER_PKCS12',     'pkcs12');
define('WA_ACTION_SERVER_REVOKE',     'srev');
define('WA_ACTION_SERVER_SIGN',       'scsr');
define('WA_ACTION_SERVER_VIEW',       'sv');
// Server CSR Action strings
define('WA_ACTION_CSR_SERVER_ADD',         'csrsa');
define('WA_ACTION_CSR_SERVER_CHANGE_PASS', 'csrscp');
define('WA_ACTION_CSR_SERVER_DECRYPT',     'csrsdec');
define('WA_ACTION_CSR_SERVER_DOWNLOAD',    'csrsdl');
define('WA_ACTION_CSR_SERVER_EDIT',        'csrse');
define('WA_ACTION_CSR_SERVER_ENCRYPT',     'csrsenc');
define('WA_ACTION_CSR_SERVER_LIST',        'csrsl');
define('WA_ACTION_CSR_SERVER_VIEW',        'csrsv');
// Data dump action strings
define('WA_QS_FORMAT',          'format');
define('WA_FORMAT_JAVASCRIPT',  'js');
define('WA_FORMAT_CSV',         'csv');
define('WA_FORMAT_XML',         'xml');
// Misc action strings
define('WA_ACTION_INSTALL',           'install');
define('WA_ACTION_BROWSER_IMPORT',    'bimp');
define('WA_ACTION_BUNDLE',            'gb');
define('WA_ACTION_CHANGE_PASS',       'cpw');
define('WA_ACTION_DECRYPT',           'dec');
define('WA_ACTION_ENCRYPT',          'enc');
// Menus
define('MENU_CERT_REQUESTS', 'csr');
define('MENU_CERTS_CA',      'ca');
define('MENU_CERTS_CLIENT',  'client');
define('MENU_CERTS_SERVER',  'server');
define('MENU_UTILITIES',     'utils');
?>
