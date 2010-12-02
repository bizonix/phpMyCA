<?
/**
 * phpmyca Server Certificate handling class
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

require(WEBAPP_API . '/db/dbo.csr.server.php');

class phpmycaCsrServer extends phpmycaDboCsrServer {

var $actionQsExport    = false;
var $actionQsExportAll = false;
var $actionQsImport    = false;

/**
 * Constructor
 */
public function __construct() {
	parent::__construct();
	// Set searchable properties
	$p = array('CommonName','CountryName','CreateDate','Description',
	'EmailAddress','Id','LocalityName','OrgName','OrgUnitName','StateName');
	foreach($p as $prop) { $this->addSearchProperty($prop); }
	// default search property
	$this->setSearchPropertyDefault('CommonName');
	// default search order
	$this->setSearchOrderDefault('CommonName');
	// set action strings
	if (defined('WA_ACTION_CSR_SERVER_ADD')) {
		$this->actionQsAdd = WA_ACTION_CSR_SERVER_ADD;
		}
	if (defined('WA_ACTION_CSR_SERVER_EDIT')) {
		$this->actionQsEdit = WA_ACTION_CSR_SERVER_EDIT;
		}
	if (defined('WA_ACTION_CSR_SERVER_EXPORT')) {
		$this->actionQsExport = WA_ACTION_CSR_SERVER_EXPORT;
		}
	if (defined('WA_ACTION_CSR_SERVER_EXPORT_ALL')) {
		$this->actionQsExportAll = WA_ACTION_CSR_SERVER_EXPORT_ALL;
		}
	if (defined('WA_ACTION_CSR_SERVER_IMPORT')) {
		$this->actionQsImport = WA_ACTION_CSR_SERVER_IMPORT;
		}
	if (defined('WA_ACTION_CSR_SERVER_LIST')) {
		$this->actionQsList = WA_ACTION_CSR_SERVER_LIST;
		}
	if (defined('WA_ACTION_CSR_SERVER_VIEW')) {
		$this->actionQsView = WA_ACTION_CSR_SERVER_VIEW;
		}
	// set properties when adding server csrs
	$p = array('CommonName','CountryName','CSR','Description',
	'EmailAddress','LocalityName','OrgName','OrgUnitName',
	'PrivateKey','PublicKey','StateName');
	$this->setPropertiesAdd($p);
	// set properties when editing server certs
	$this->setPropertiesEdit(array('Description'));
	// set properties when listing server certs
	$p = array('CommonName','OrgName','CreateDate');
	$this->setPropertiesList($p);
	// set properties when viewing ca certs (all of them)
	$this->setPropertiesView($this->getPropertyList());
	}
}
?>
