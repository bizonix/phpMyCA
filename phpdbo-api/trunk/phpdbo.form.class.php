<?
/**
 * @package phpdbo-api
 * @subpackage phpdbo-form-api
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

/**
 * Utility framework for generating forms from objects in a database.
 *
 * @package phpdbo-api
 * @subpackage phpdbo-form-api
 */
class phpdboform extends phpdbo {

/**
 * When adding objects, what property names are needed?
 * @see setPropertiesAdd(), addPropertyAdd(), getPropertiesAdd()
 */
var $_propertiesAdd = array();

/**
 * When deleting objects, what property names are needed?
 * @see setPropertiesDelete(), addPropertyDelete()
 * @see getPropertiesDelete()
 */
var $_propertiesDelete = array();

/**
 * When editing objects, what property names are needed?
 * @see setPropertiesEdit(), addPropertyEdit(), getPropertiesEdit()
 */
var $_propertiesEdit = array();

/**
 * When listing objects, what property names are needed?
 * @see setPropertiesList(), addPropertyList(), getPropertiesList()
 */
var $_propertiesList = array();

/**
 * When viewing objects, what property names are needed?
 * @see setPropertiesView(), addPropertyView(), getPropertiesView()
 */
var $_propertiesView = array();

/**
 * Contains searchable form property names.
 * @see addSearchProperty(), getSearchProperties()
 */
var $_searchProperties = array();

/**
 * Contains default search property name
 * @see setSearchPropertyDefault(), getSearchPropertyDefault()
 */
var $_searchPropertyDefault = false;

/**
 * Contains default search order property name
 * @see getSearchOrderDefault(), setSearchOrderDefault()
 */
var $_searchOrderDefault;

/**
 * Optional form info (instructions, help, etc)
 * @see setFormInfo(), getFormInfo()
 */
var $_formInfo      = false;

/**
 * Optional form title
 * @see setFormTitle(), getFormTitle()
 */
var $_formTitle     = false;

/**
 * Action query strings
 */
var $actionQsAdd    = false;
var $actionQsDelete = false;
var $actionQsEdit   = false;
var $actionQsList   = false;
var $actionQsView   = false;

/**
 * Placeholders for hit counts
 */
var $hitsCurrent = 0;
var $hitsMax     = 0;
var $hitsTotal   = 0;

/**
 * Place holders for page numbers
 */
var $pageCurrent  = 1;
var $pageLast     = 1;
var $pageNext     = 1;
var $pagePrevious = 1;

/**
 * Contains search results
 */
var $searchResults = false;

/**
 * Constructor - add special property keys
 */
function phpdboform() {
	$this->propertyKeyAdd('select_method');
	$this->propertyKeyAdd('view_method');
	}

/**
 * Add an individual property name visible to html forms when adding objects.
 * @see setPropertiesAdd(), getPropertiesAdd().
 */
function addPropertyAdd($prop) {
	if (!$this->isProperty($prop)) { return false; }
	if (!is_array($this->_propertiesAdd)) { return false; }
	$this->_propertiesAdd[] = $prop;
	return true;
	}

/**
 * Add an individual property name visible to html forms when deleting objects.
 * @see setPropertiesDelete(), getPropertiesDelete().
 */
function addPropertyDelete($prop) {
	if (!$this->isProperty($prop)) { return false; }
	if (!is_array($this->_propertiesDelete)) { return false; }
	$this->_propertiesDelete[] = $prop;
	return true;
	}

/**
 * Add an individual property name visible to html forms when editing objects.
 * @see setPropertiesEdit(), getPropertiesEdit().
 */
function addPropertyEdit($prop) {
	if (!$this->isProperty($prop)) { return false; }
	if (!is_array($this->_propertiesEdit)) { return false; }
	$this->_propertiesEdit[] = $prop;
	return true;
	}

/**
 * Add an individual property name visible to html forms when listing objects.
 * @see setPropertiesList(), getPropertiesList().
 */
function addPropertyList($prop) {
	if (!$this->isProperty($prop)) { return false; }
	if (!is_array($this->_propertiesList)) { return false; }
	$this->_propertiesList[] = $prop;
	return true;
	}

/**
 * Add an individual property name visible to html forms when viewing objects.
 * @see setPropertiesView(), getPropertiesView().
 */
function addPropertyView($prop) {
	if (!$this->isProperty($prop)) { return false; }
	if (!is_array($this->_propertiesView)) { return false; }
	$this->_propertiesView[] = $prop;
	return true;
	}

/**
 * Used to define searchable property names for html forms
 */
function addSearchProperty($prop) {
	if (!$this->isProperty($prop)) { return false; }
	if (!is_array($this->_searchProperties)) { return false; }
	if (in_array($prop,$this->_searchProperties)) { return true; }
	$this->_searchProperties[] = $prop;
	return true;
	}

/**
 * Populate an object when adding it from a posted form.
 */
function doQueryAdd() {
	$this->resetProperties();
	$props = $this->getPropertiesAdd();
	if (is_array($props) and count($props) > 0) {
		foreach($props as $prop) {
			if (array_key_exists($prop,$_REQUEST)) {
				if (is_string($_REQUEST[$prop])) {
					$this->setProperty($prop,trim($_REQUEST[$prop]));
					} else {
					$this->setProperty($prop,$_REQUEST[$prop]);
					}
				} else {
				$this->setProperty($prop,'');
				}
			}
		}
	return true;
	}

/**
 * Populate an object when editing it from a posted form.
 */
function doQueryEdit($id=false) {
	$this->resetProperties();
	if (!($this->populateFromDb($id) === true)) {
		return 'failed to populate form information';
		}
	// Fill in any information provided via an earlier submit
	$props = $this->getPropertiesEdit();
	if (is_array($props) and count($props) > 0) {
		foreach($props as $prop) {
			if (array_key_exists($prop,$_REQUEST)) {
				$this->setProperty($prop,trim($_REQUEST[$prop]));
				}
			}
		}
	return true;
	}

/**
 * Perform a list query for searchable list forms.
 * @return string error message on failures
 * @return true on success.
 */
function doQueryList() {
	if (!($this->_searchEnabled === true)) {
		return 'searching has been disabled, possibly due to errors';
		}
	// sanity check on vars needed for paging
	if (!is_numeric($this->pageCurrent) or $this->pageCurrent < 1) {
		return 'must set pageCurrent before doQueryList()';
		}
	if (!is_numeric($this->hitsMax) or $this->hitsMax < 1) {
		return 'must set hitsMax before doQueryList()';
		}
	// add list properties
	$props = $this->getPropertiesList();
	if (!is_array($props) or count($props) < 1) {
		return 'list properties have not been defined';
		}
	foreach($props as $prop) {
		if (!$this->setSearchSelect($prop)) {
			return 'setSearchSelect(' . $prop . ') failed';
			}
		}
	// set the default order column if possible
	if (!is_array($this->_searchOrder) or count($this->_searchOrder) < 1) {
		$junk = explode(' ',$this->getSearchOrderDefault());
		$prop = $junk[0];
		if ($this->isProperty($prop)) {
			if (count($junk) == 2) {
				$this->setSearchOrder($prop,$junk[1]);
				} else {
				$this->setSearchOrder($prop);
				}
			}
		}
	// Get the total hits count
	$hits = $this->queryHitsTotal();
	if (!is_numeric($hits)) {
		return 'queryHitsTotal() failed: ' . $hits;
		}
	$this->hitsTotal = $hits;
	// Not much point to going further if no hits ;)
	if ($hits < 1) {
		$this->hitsCurrent = 0;
		$this->searchResults = array();
		return true;
		}
	//
	// Apply paging and limits
	//
	$pagesMax     = ceil($this->hitsTotal / $this->hitsMax);
	if ($this->pageCurrent > $pagesMax) {
		$this->pageCurrent = $pagesMax - 1;
		}
	$pageCurrent    = $this->pageCurrent;
	$this->pageNext = $this->pageCurrent + 1;
	$this->pagePrevious = $this->pageCurrent - 1;
	if ($this->pageNext > $pagesMax) {
		$this->pageNext = $pagesMax;
		}
	if ($this->pagePrevious < 1) {
		$this->pagePrevious = 0;
		}
	$this->pageLast = $pagesMax;
	if ($this->pageCurrent > 1) {
		$limit = ($this->hitsMax * ($this->pageCurrent - 1)) . ','
		. $this->hitsMax;
		} else {
		$limit = $this->hitsMax;
		}
	$this->setSearchLimit($limit);
	// Do the query
	$rows = $this->query();
	if (!is_array($rows)) {
		$this->hitsCurrent = 0;
		$this->hitsTotal   = 0;
		return 'query() failed: ' . $rows;
		}
	$this->hitsCurrent = count($rows);
	$this->searchResults = $rows;
	return true;
	}

/**
 * Perform query to view an object
 * @return string error message on failures
 * @return true on success.
 */
function doQueryView($id=false) {
	$idProp = $this->getIdProperty();
	if (!is_string($idProp)) {
		return 'must setIdProperty() before doQueryView()';
		}
	// get view properties
	$props = $this->getPropertiesView();
	if (!is_array($props) or count($props) < 1) {
		return 'view properties have not been defined';
		}
	// populate object
	$ar = $this->queryById($id);
	if (!is_array($ar)) {
		return 'failed to query by id';
		}
	if (!array_key_exists($idProp,$ar)) {
		return 'id field is not in results';
		}
	$this->setProperty($idProp,stripslashes($ar[$idProp]));
	foreach($props as $prop) {
		if (!array_key_exists($prop,$ar)) {
			return 'property missing from query: ' . $prop;
			}
		$this->setProperty($prop,stripslashes($ar[$prop]));
		}
	$this->populated = true;
	return true;
	}

/**
 * Retrieve optional form info.
 * @see setFormInfo()
 */
function getFormInfo() {
	return (is_string($this->_formInfo)) ? $this->_formInfo : false;
	}

/**
 * Retrieve optional form title.
 * @see setFormTitle()
 */
function getFormTitle() {
	return (is_string($this->_formTitle)) ? $this->_formTitle : '';
	}

/**
 * Retrieve list of property names available to forms when adding objects
 * @see addPropertyAdd(), setPropertiesAdd()
 */
function getPropertiesAdd() {
	return $this->_propertiesAdd;
	}

/**
 * Retrieve list of property names available to forms when deleting objects
 * @see addPropertyDelete(), setPropertiesDelete()
 */
function getPropertiesDelete() {
	return $this->_propertiesDelete;
	}

/**
 * Retrieve list of property names available to forms when editing objects
 * @see addPropertyEdit(), setPropertiesEdit()
 */
function getPropertiesEdit() {
	return $this->_propertiesEdit;
	}

/**
 * Retrieve list of property names available to forms when listing objects
 * @see addPropertyList(), setPropertiesList()
 */
function getPropertiesList() {
	if ($this->isProperty($this->getIdProperty())) {
		return array_merge(array($this->getIdProperty()),$this->_propertiesList);
		}
	return $this->_propertiesList;
	}

/**
 * Retrieve list of property names available to forms when viewing objects
 * @see addPropertyView(), setPropertiesView()
 */
function getPropertiesView() {
	return $this->_propertiesView;
	}

/**
 * Retrieve custom method name for selecting properties
 * @deprecated - use getPropertySelectMethod() instead
 */
function getPropertySelect($prop) {
	return $this->getPropertySelectMethod($prop);
	}

/**
 * Retrieve custom method name for selecting (add/edit) properties
 * @see setPropertySelectMethod()
 */
function getPropertySelectMethod($prop) {
	return $this->propertyKeyGet($prop,'select_method');
	}

/**
 * Retrieve custom method name for viewing properties
 * @see setPropertyViewMethod()
 */
function getPropertyViewMethod($prop) {
	return $this->propertyKeyGet($prop,'view_method');
	}

/**
 * Retrieve the default search property order name
 */
function getSearchOrderDefault() {
	return $this->_searchOrderDefault;
	}

/**
 * Use to retrieve list of search property names
 */
function getSearchProperties() {
	return $this->_searchProperties;
	}

/**
 * Retrieve the default search property name
 */
function getSearchPropertyDefault() {
	return $this->_searchPropertyDefault;
	}

/**
 * Set optional form info.
 */
function setFormInfo($t) { $this->_formInfo = $t; }

/**
 * Set optional form title.
 */
function setFormTitle($t) { $this->_formTitle = $t; }

/**
 * Set property names visible to html forms when adding objects.
 * @param array $props - array of property names
 * @return bool
 * @see addPropertyAdd(), getPropertiesAdd()
 */
function setPropertiesAdd($props) {
	if (!is_array($props)) { return false; }
	foreach($props as $prop) {
		if (!$this->isProperty($prop)) { return false; }
		}
	$this->_propertiesAdd = $props;
	return true;
	}

/**
 * Set property names visible to html forms when deleting objects.
 * @param array $props - array of property names
 * @return bool
 * @see addPropertyDelete(), getPropertiesDelete()
 */
function setPropertiesDelete($props) {
	if (!is_array($props)) { return false; }
	foreach($props as $prop) {
		if (!$this->isProperty($prop)) { return false; }
		}
	$this->_propertiesDelete = $props;
	return true;
	}

/**
 * Set property names visible to html forms when editing objects.
 * @param array $props - array of property names
 * @return bool
 * @see addPropertyEdit(), getPropertiesEdit()
 */
function setPropertiesEdit($props) {
	if (!is_array($props)) { return false; }
	foreach($props as $prop) {
		if (!$this->isProperty($prop)) { return false; }
		}
	$this->_propertiesEdit = $props;
	return true;
	}

/**
 * Set property names visible to html forms when listing objects.
 * @param array $props - array of property names
 * @return bool
 * @see addPropertyList(), getPropertiesList()
 */
function setPropertiesList($props) {
	if (!is_array($props)) { return false; }
	foreach($props as $prop) {
		if (!$this->isProperty($prop)) { return false; }
		}
	$this->_propertiesList = $props;
	return true;
	}

/**
 * Set property names visible to html forms when viewing objects.
 * @param array $props - array of property names
 * @return bool
 * @see addPropertyView(), getPropertiesView()
 */
function setPropertiesView($props) {
	if (!is_array($props)) { return false; }
	foreach($props as $prop) {
		if (!$this->isProperty($prop)) { return false; }
		}
	$this->_propertiesView = $props;
	return true;
	}

/**
 * Set a method name that will be called with adding or editing a property
 * @deprecated - use setPropertySelectMethod() instead
 */
function setPropertySelect($prop,$method) {
	return $this->setPropertySelectMethod($prop,$method);
	}

/**
 * Set a method name that will be called with adding or editing a property
 * @see getPropertySelectMethod()
 */
function setPropertySelectMethod($prop,$method) {
	if (!$this->isProperty($prop)) { return false; }
	if (!is_string($method) or strlen($method) < 1) { return false; }
	return $this->propertyKeySet($prop,'select_method',$method);
	}

/**
 * Set custom method name that will be called with viewing a property
 * @see getPropertyViewMethod()
 */
function setPropertyViewMethod($prop,$method) {
	if (!$this->isProperty($prop)) { return false; }
	if (!is_string($method) or strlen($method) < 1) { return false; }
	return $this->propertyKeySet($prop,'view_method',$method);
	}

/**
 * Set the default search order property name
 */
function setSearchOrderDefault($prop) {
	return $this->_searchOrderDefault = $prop;
	}

/**
 * Set the default search property name
 */
function setSearchPropertyDefault($prop) {
	return $this->_searchPropertyDefault = $prop;
	}

}

?>
