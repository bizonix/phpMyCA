<?
/**
 * phpmyca server certificate db interaction class
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

class phpmycaDboCsrServer extends phpmycadbo {

/**
 * Constructor
 */
function __construct() {
	parent::__construct();
	$this->setDatabaseTable('csr_servers');
	// define object properties
	$this->propertyAdd('CommonName',       'commonName');
	$this->propertyAdd('CountryName',      'countryName');
	$this->propertyAdd('CreateDate',       'create_date');
	$this->propertyAdd('CSR',              'cert_request');
	$this->propertyAdd('Description',      'csr_desc');
	$this->propertyAdd('EmailAddress',     'emailAddress');
	$this->propertyAdd('Id',               'csr_id');
	$this->propertyAdd('LocalityName',     'localityName');
	$this->propertyAdd('OrgName',          'organizationName');
	$this->propertyAdd('OrgUnitName',      'organizationalUnitName');
	$this->propertyAdd('PrivateKey',       'cert_private_key');
	$this->propertyAdd('PublicKey',        'cert_public_key');
	$this->propertyAdd('StateName',        'stateOrProvinceName');
	// set id property
	$this->setIdProperty('Id');
	// set date properties
	$this->setPropertyIsDate('CreateDate', true);
	// set numeric properties
	$this->setPropertyIsQuoted('Id',               false);
	// set max lengths
	$this->setPropertyMaxLength('CommonName',         255);
	$this->setPropertyMaxLength('CountryName',          2);
	$this->setPropertyMaxLength('CreateDate',          19);
	$this->setPropertyMaxLength('CSR',              65535);
	$this->setPropertyMaxLength('Description',        128);
	$this->setPropertyMaxLength('EmailAddress',       255);
	$this->setPropertyMaxLength('Id',                  10);
	$this->setPropertyMaxLength('LocalityName',        64);
	$this->setPropertyMaxLength('OrgName',            255);
	$this->setPropertyMaxLength('OrgUnitName',      65535);
	$this->setPropertyMaxLength('PrivateKey',       65535);
	$this->setPropertyMaxLength('PublicKey',        65535);
	$this->setPropertyMaxLength('StateName',           64);
	}

}
?>
