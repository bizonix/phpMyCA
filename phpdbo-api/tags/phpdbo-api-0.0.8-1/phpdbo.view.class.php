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
 * Framework for generating form views with multiple phpdbo search objects
 *
 * @package phpdbo-api
 * @subpackage phpdbo-form-api
 */
class phpdboview {

/**
 * Database connection object
 */
var $db = false;

/**
 * Optionally set id field for objects, ie the auto_increment column.
 * @see setIdProperty(), getIdProperty()
 */
var $_idProperty = false;

/**
 * Array containing object properties and mapping information
 * @see propertyAdd(), propertyList(), propertyDelete(), propertySet(),
 * @see propertyGet()
 */
var $_properties = array();

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
 * Array containing property keys, properties of each property ;)
 * @see propertyKeyAdd(), propertyKeyGet(), propertyKeySet()
 */
var $_propertyKeys = array('desc','fieldName','isDate','isQuoted','isReadOnly',
                           'isUnique','maxLength','value');


/**
 * Vars related to searching
 */
var $_searchEnabled = false;
var $_searchFilters = false;
var $_searchLimit   = false;
var $_searchOrder   = false;
var $_searchSelects = false;

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
 * Tracks names of search objects
 * @see addSearchObject()
 */
var $_searchObjects    = array();

/**
 * Contains sql join columns
 * @see addSearchJoin()
 */
var $_searchJoins      = array();

/**
 * Database settings
 * @var string
 * @see getDatabase(), setDatabase(), getDatabaseTable(), setDatabaseTable()
 */
var $_databaseName  = array();
var $_databaseTable = array();

/**
 * Let forms know this is a view object
 */
var $isView = true;

/**
 * Constructor - add special property keys
 */
function phpdboview() {
	$this->propertyKeyAdd('select_method');
	$this->propertyKeyAdd('alias');
	}

/**
 * Add an individual property name visible to html forms when adding objects.
 * @see setPropertiesAdd(), getPropertiesAdd().
 */
function addPropertyAdd($alias,$prop) {
	if (!$this->isProperty($alias,$prop)) { return false; }
	if (!is_array($this->_propertiesAdd)) { return false; }
	$this->_propertiesAdd[] = array($alias,$prop);
	return true;
	}

/**
 * Add an individual property name visible to html forms when deleting objects.
 * @see setPropertiesDelete(), getPropertiesDelete().
 */
function addPropertyDelete($alias,$prop) {
	if (!$this->isProperty($alias,$prop)) { return false; }
	if (!is_array($this->_propertiesDelete)) { return false; }
	$this->_propertiesDelete[] = array($alias,$prop);
	return true;
	}

/**
 * Add an individual property name visible to html forms when editing objects.
 * @see setPropertiesEdit(), getPropertiesEdit().
 */
function addPropertyEdit($alias,$prop) {
	if (!$this->isProperty($alias,$prop)) { return false; }
	if (!is_array($this->_propertiesEdit)) { return false; }
	$this->_propertiesEdit[] = array($alias,$prop);
	return true;
	}

/**
 * Add an individual property name visible to html forms when listing objects.
 * @see setPropertiesList(), getPropertiesList().
 */
function addPropertyList($alias,$prop) {
	if (!$this->isProperty($alias,$prop)) { return false; }
	if (!is_array($this->_propertiesList)) { return false; }
	$this->_propertiesList[] = array($alias,$prop);
	return true;
	}

/**
 * Add an individual property name visible to html forms when viewing objects.
 * @see setPropertiesView(), getPropertiesView().
 */
function addPropertyView($alias,$prop) {
	if (!$this->isProperty($alias,$prop)) { return false; }
	if (!is_array($this->_propertiesView)) { return false; }
	$this->_propertiesView[] = array($alias,$prop);
	return true;
	}

/**
 * Populate the _searchObjects array with a search object by alias
 * @param string alias - alias for the search object
 * @param object obj - the phpdbo object to search
 * @return bool
 */
function addSearchObject($alias=false,&$obj) {
	if (!($this->_searchEnabled === true)) { return false; }
	if ($this->isSearchObject($alias)) {
		return $this->_searchDisable();
		}
	if (!is_string($alias)) { return $this->_searchDisable(); }
	if (!is_object($obj)) { return $this->_searchDisable(); }
	// add to search objects for remaining calls to work...
	$this->_searchObjects[] = $alias;

	foreach($obj->_properties as $prop => $props) {
		if (!$this->propertyAdd($alias,$prop,false,false,$props)) {
			return false;
			}
		}
	// add database/table settings
	$db = $obj->getDatabase(false);
	$this->setDatabase($alias,$db);
	$tb = $obj->getDatabaseTable(false,false);
	$this->setDatabaseTable($alias,$tb);
	return true;
	}

/**
 * Used to define searchable property names for html forms
 */
function addSearchProperty($alias=false,$prop) {
	if (!$this->isSearchObject($alias))   { return false; }
	if (!$this->isProperty($alias,$prop)) { return false; }
	if (!is_array($this->_searchProperties)) { return false; }
	$this->_searchProperties[] = array($alias,$prop);
	return true;
	}

function addSearchJoin($alias1=false,$prop1=false,$alias2=false,$prop2=false) {
	if (!$this->isSearchObject($alias1)) { return $this->_searchDisable(); }
	if (!$this->isSearchObject($alias2)) { return $this->_searchDisable(); }
	if (!$this->isProperty($alias1,$prop1)) { return $this->_searchDisable(); }
	if (!$this->isProperty($alias2,$prop2)) { return $this->_searchDisable(); }
	$this->_searchJoins[] = $alias1 . '.'
	. $this->getPropertyField($alias1,$prop1) . ' = '
	. $alias2 . '.' . $this->getPropertyField($alias2,$prop2);
	return true;
	}

/**
 * Populate an object when adding it from a posted form.
 * @todo port
 */
function doQueryAdd() {
	return false;
	}

/**
 * Populate an object when editing it from a posted form.
 * @todo port
 */
function doQueryEdit($id=false) {
	return false;
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
	if (!is_numeric($this->hitsMax) or $this->hitsMax < 0) {
		return 'must set hitsMax before doQueryList()';
		}
	// add list properties
	$list_props = $this->getPropertiesList();
	if (!is_array($list_props) or count($list_props) < 1) {
		return 'list properties have not been defined';
		}
	foreach($list_props as $props) {
		$alias = $props[0];
		$prop  = $props[1];
		if (!$this->setSearchSelect($alias,$prop)) {
			return 'setSearchSelect(' . $alias . ',' . $prop . ') failed';
			}
		}
	// set the default order column if possible
	if (!is_array($this->_searchOrder) or count($this->_searchOrder) < 1) {
		$p = $this->getSearchOrderDefault();
		if (is_array($p) and count($p) == 2) {
			$this->setSearchOrder($p[0],$p[1]);
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
	// Apply paging and limits, unless hitsMax is 0
	//
	if ($this->hitsMax == 0) {
		$this->pageCurrent = 1;
		$this->pageNext = 1;
		$this->pagePrevious = 1;
		$this->pageLast = 1;
		$limit = $this->hitsTotal;
		$this->setSearchLimit($limit);
		} else {
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
		}
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
 * @todo port
 */
function doQueryView($id=false) {
	return false;
	}

/**
 * Wrapper to get database name
 */
function getDatabase($alias=false,$s=true) {
	if (!$this->isSearchObject($alias)) { return false; }
	$t = $this->_databaseName[$alias]; return ($s) ? addslashes($t) : $t;
	}

/**
 * Wrapper to get table name
 */
function getDatabaseTable($alias=false,$s=true,$add_db=true) {
	if (!$this->isSearchObject($alias)) { return false; }
	if ($add_db) {
		$db = $this->getDatabase($alias,false);
		$t  = $db . '.' . $this->_databaseTable[$alias];
		} else {
		$t = $this->_databaseTable[$alias];
		}
	return ($s) ? addslashes($t) : $t;
	}

/**
 * Get property that is considered the id (unique) field.
 * @return array id property
 */
function getIdProperty() {
	return $this->_idProperty;
	}

/**
 * Get the value of the specified alias/property.
 */
function getProperty($alias,$prop) {
	return $this->_propertyKeyGet($alias,$prop,'value');
	}

/**
 * Get the description of the specified property name.
 * @param string $prop - property name
 * @return string property
 * @return bool false on errors
 */
function getPropertyDescription($alias,$prop,$s=true) {
	if (!$this->isProperty($alias,$prop)) { return false; }
	return $this->_propertyKeyGet($alias,$prop,'desc');
	}

/**
 * Get the column name that corresponds to the specified property alias/name.
 * @param string $alias - alias name
 * @param string $prop - property name
 * @param bool $s - addslash() the returned string?
 * @return string field name
 * @return bool false on errors
 */
function getPropertyField($alias,$prop,$s=true) {
	if (!$this->isProperty($alias,$prop)) { return false; }
	$f = $this->_propertyKeyGet($alias,$prop,'fieldName');
	return ($s) ? addslashes($f) : $f;
	}

/**
 * Is the property a date field?
 * @param string alias name
 * @param string property name
 * @return string error message on failures
 * @return bool
 */
function getPropertyIsDate($alias,$prop) {
	if (!$this->isProperty($alias,$prop)) { return 'not a property'; }
	return $this->_propertyKeyGet($alias,$prop,'isDate');
	}

/**
 * Is the property read-only?
 * @param string alias name
 * @param string property name
 * @return string error message on failures
 * @return bool
 */
function getPropertyIsReadOnly($alias,$prop) {
	if (!$this->isProperty($alias,$prop)) { return 'not a property'; }
	return $this->_propertyKeyGet($alias,$prop,'isReadOnly');
	}

/**
 * When updating/querying a property in the database, is the field quoted?
 *
 * When updating the table, does the specified data need to be
 * quoted or not?  Any caller needs to verify the returned result is
 * boolean before trusting it...
 * @param string alias name
 * @param string property name
 * @return string on errors
 * @return true if quoted
 * @return false if not quoted
 */
function getPropertyQuoted($alias,$prop) {
	if (!$this->isProperty($alias,$prop)) { return 'is not a property'; }
	$f = $this->_propertyKeyGet($alias,$prop,'isQuoted');
	if (!is_bool($f)) { return 'invalid isQuoted setting'; }
	return $f;
	}

/**
 * Is the property part of a unique key?
 * @param string alias name
 * @param string property name
 * @return string error message on failures
 * @return bool
 */
function getPropertyIsUnique($alias,$prop) {
	if (!$this->isProperty($alias,$prop)) { return 'not a property'; }
	return $this->_propertyKeyGet($alias,$prop,'isUnique');
	}

/**
 * Get array containing all property alias/names
 */
function getPropertyList() {
	if (!is_array($this->_properties)) { return false; }
	$ar = array();
	foreach($this->_properties as $alias => $props) {
		foreach($props as $prop) {
			$ar[] = array($alias,$prop);
			}
		}
	return $ar;
	}

/**
 * Get the maximum string length of a property.
 * @param string alias name
 * @param string $prop - property name
 * @return int maximum string length
 * @return bool false on errors
 */
function getPropertyMaxLength($alias,$prop) {
	if (!$this->isProperty($alias,$prop)) { return false; }
	return $this->_propertyKeyGet($alias,$prop,'maxLength');
	}

/**
 * Retrieve custom method name for selecting properties
 * @see setPropertySelect()
 */
function getPropertySelect($alias,$prop) {
	return $this->propertyKeyGet($alias,$prop,'select_method');
	}

/**
 * Get the value of the specified property.
 * @deprecated use getProperty() instead
 */
function getPropertyValue($alias,$prop) {
	return $this->getProperty($alias,$prop);
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
 * Is the property valid?
 */
function isProperty($alias=false,$prop=false) {
	if (!$this->isSearchObject($alias)) { return false; }
	if (!is_string($prop) or $prop == '') { return false; }
	if (!array_key_exists($alias,$this->_properties)) { return false; }
	if (!is_array($this->_properties[$alias])) { return false; }
	if (!array_key_exists($prop,$this->_properties[$alias])) { return false; }
	if (!is_array($this->_propertyKeys)) { return false; }
	foreach($this->_propertyKeys as $key) {
		if (!array_key_exists($key,$this->_properties[$alias][$prop])) {
			return false;
			}
		}
	return true;
	}

/**
 *
 */
function isSearchObject($alias) {
	if (!($this->_searchEnabled === true)) { return false; }
	if (!is_string($alias)) { return false; }
	if (!is_array($this->_searchObjects))   { return false; }
	return in_array($alias,$this->_searchObjects);
	}

/**
 * Add a property definition
 * @param string $alias property alias name
 * @param string $prop property name
 * @param string $field property field name
 * @param string $desc property description
 * @param array $props additional properties ;)
 * @return bool
 */
function propertyAdd($alias=false,$prop=false,$field=false,$desc=false,
                     $props=false) {
	if (!$this->isSearchObject($alias)) { return false; }
	if (!is_array($this->_properties))  { return false; }
	if (!is_string($prop))              { return false; }
	if (!array_key_exists($alias,$this->_properties)) {
		$this->_properties[$alias] = array();
		}
	if (!is_array($this->_properties[$alias])) { return false; }
	if (array_key_exists($prop,$this->_properties[$alias])) { return false; }
	if (!is_array($this->_propertyKeys)) { return false; }
	// Populate with defaults
	$this->_properties[$alias][$prop] = array();
	foreach($this->_propertyKeys as $key) {
		$this->_properties[$alias][$prop][$key] = false;
		}
	if (is_string($field)) {
		$this->_properties[$alias][$prop]['fieldName'] = $field;
		} else {
		$this->_properties[$alias][$prop]['fieldName'] = $prop;
		}
	if (is_string($desc)) {
		$this->_properties[$alias][$prop]['desc'] = $desc;
		} else {
		$this->_properties[$alias][$prop]['desc'] = $prop;
		}
	$this->_properties[$alias][$prop]['isQuoted']  = true;
	$this->_properties[$alias][$prop]['maxLength'] = 1;
	if (is_array($props)) {
		foreach($props as $tag => $val) {
			switch($tag) {
				case 'desc':
					$this->setPropertyDescription($alias,$prop,$val);
				break;
				case 'fieldName':
					$this->setPropertyField($alias,$prop,$val);
				break;
				case 'isDate':
					$this->setPropertyIsDate($alias,$prop,$val);
				break;
				case 'isQuoted':
					$this->setPropertyIsQuoted($alias,$prop,$val);
				break;
				case 'isReadOnly':
					$this->setPropertyIsReadOnly($alias,$prop,$val);
				break;
				case 'isUnique':
					$this->setPropertyIsUnique($alias,$prop,$val);
				break;
				default:
					// allow custom properties
					if (is_string($tag)) {
						$this->_properties[$alias][$prop][$tag] = $val;
						}
				break;
				}
			}
		}
	// hack in alias name as a prop
	$this->_properties[$alias][$prop]['alias'] = $alias;
	return $this->isProperty($alias,$prop);
	}

/**
 * Add a custom property key
 * @param string key name
 * @return bool
 */
function propertyKeyAdd($key=false) {
	if (!is_string($key)) { return false; }
	if (!is_array($this->_propertyKeys)) { return false; }
	if (in_array($key,$this->_propertyKeys)) { return true; }
	$this->_propertyKeys[] = $key;
	// Add in default value for any existing properties
	if (is_array($this->_properties) and count($this->_properties) > 0) {
		foreach($this->_properties as $prop => $keys) {
			$this->_properties[$prop][$key] = false;
			}
		}
	return true;
	}

/**
 * Wrapper to retrieve a property key
 * @param string alias name
 * @param string property name
 * @param string key name
 * @return bool false
 * @return string value
 */
function propertyKeyGet($alias,$prop,$key) {
	return $this->_propertyKeyGet($alias,$prop,$key);
	}

/**
 * Wrapper to set a property key
 * @param string alias name
 * @param string property name
 * @param string key name
 * @param string key value
 * @return bool
 */
function propertyKeySet($alias,$prop,$key,$val) {
	return $this->_propertyKeySet($alias,$prop,$key,$val);
	}

/**
 * Perform a search and return the results in a hashed array.
 * @return array results (hashed array with property names/values)
 * @return string error message on any failures.
 * @param bool setHitCounts - will populate searchHitsTotal/Current if true
 * @see searchReset(), setFilter(), setLimit(), setSelect()
 */
function query($debug=false) {
	if (!is_array($this->_searchSelects) or count($this->_searchSelects) < 1) {
		return 'Must use setSearchSelect() before searching';
		}
	if (!($this->_searchEnabled === true)) {
		return 'Searching has been disabled, possibly due to errors.';
		}
	if (!($this->requireDatabase() === true)) { return 'db connection failed'; }
	// Generate database aliases string
	$dbstrings = array();
	$m = 'cannot determine db name for alias: ';
	foreach($this->_searchObjects as $alias) {
		$db = $this->getDatabaseTable($alias);
		if (!is_string($db)) { return $m . $alias; }
		$dbstrings[] = $db . ' as ' . $alias;
		}
	if (count($dbstrings) < 1) {
		return 'no aliases have been defined';
		}
	// start generating sql string
	$sql = 'select ' . implode(', ',$this->_searchSelects) . ' '
	. 'from ' . implode(', ',$dbstrings) . ' ';
	// determine join/filter counts
	$joins = 0;
	if (is_array($this->_searchJoins) and count($this->_searchJoins) > 0) {
		$joins = count($this->_searchJoins);
		}
	$filters = 0;
	if (is_array($this->_searchFilters) and count($this->_searchFilters) > 0) {
		$filters = count($this->_searchFilters);
		}
	($joins > 0 or $filters > 0) ? $where = 'where ' : $where = '';
	$sql .= $where;
	// add in joins if needed
	if ($joins > 0) {
		$sql .= '(' . implode(' and ',$this->_searchJoins) . ') ';
		}
	// add in search parameters
	if ($filters > 0) {
		if ($joins > 0) { $sql .= ' and '; }
		$sql .= '(' . implode(' and ',$this->_searchFilters) . ') ';
		}
	if (is_array($this->_searchOrder) and count($this->_searchOrder) > 0) {
		$sql .= 'order by ' . implode(',',$this->_searchOrder) . ' ';
		}
	if (!($this->_searchLimit === false)) {
		$sql .= 'limit ' . $this->_searchLimit;
		}
	//if ($debug) { die($sql); }
	$qid = $this->db->db_query($sql);
	if ($qid === false) {
		if ($debug === true) {
			echo '<p>SQL STATEMENT:<br/>' . $sql . '</p>';
			die('<p>SQL ERROR:<br/>' . $this->db->db_error() . '</p>');
			}
		return 'SQL ERROR: ' . $this->db->db_error();
		}
	return $this->db->query_hash_rows($qid);
	}

/**
 * When querying with paging applied, get the total hits (without limits).
 * Expects valid search parameters to exist.
 * @return string error message on failures
 * @return int hits total on success
 */
function queryHitsTotal($debug=false) {
	if (!is_array($this->_searchSelects) or count($this->_searchSelects) < 1) {
		return 'Must use setSearchSelect() before searching';
		}
	if (!($this->_searchEnabled === true)) {
		return 'Searching has been disabled, possibly due to errors.';
		}
	if (!($this->requireDatabase() === true)) { return 'db connection failed'; }
	// we query by counting the id column, verify we can get it...
	$props = $this->getIdProperty();
	$m = 'cannot determine id property for total hits query';
	if (!is_array($props) or count($props) !== 2) { return $m; }
	$id_alias = $props[0];
	$id_prop  = $props[1];
	if (!$this->isProperty($id_alias,$id_prop)) {
		return $m;
		}
	$id_field = $this->getPropertyField($id_alias,$id_prop);
	// Generate database aliases string
	$dbstrings = array();
	$m = 'cannot determine db name for alias: ';
	foreach($this->_searchObjects as $alias) {
		$db = $this->getDatabaseTable($alias);
		if (!is_string($db)) { return $m . $alias; }
		$dbstrings[] = $db . ' as ' . $alias;
		}
	if (count($dbstrings) < 1) {
		return 'no aliases have been defined';
		}
	// start generating sql string
	$sql = 'select count(' . $id_alias . '.'  . $id_field . ') '
	. 'from ' . implode(', ',$dbstrings) . ' ';
	// determine join/filter counts
	$joins = 0;
	if (is_array($this->_searchJoins) and count($this->_searchJoins) > 0) {
		$joins = count($this->_searchJoins);
		}
	$filters = 0;
	if (is_array($this->_searchFilters) and count($this->_searchFilters) > 0) {
		$filters = count($this->_searchFilters);
		}
	($joins > 0 or $filters > 0) ? $where = 'where ' : $where = '';
	$sql .= $where;
	// add in joins if needed
	if ($joins > 0) {
		$sql .= '(' . implode(' and ',$this->_searchJoins) . ') ';
		}
	// add in search parameters
	if ($filters > 0) {
		if ($joins > 0) { $sql .= ' and '; }
		$sql .= '(' . implode(' and ',$this->_searchFilters) . ') ';
		}
	$qid = $this->db->db_query($sql);
	if ($qid === false) {
		if ($debug === true) {
			echo '<p>SQL STATEMENT:<br/>' . $sql . '</p>';
			die('<p>SQL ERROR:<br/>' . $this->db->db_error() . '</p>');
			}
		return 'SQL ERROR: ' . $this->db->db_error();
		}
	return $this->db->db_result($qid,0,0);
	}

/**
 * Check for the database connection object.
 */
function requireDatabase() {
	if (is_object($this->db) and $this->db->connected === true) { return true; }
	return false;
	}

/**
 * Initialize a new search.
 */
function searchReset() {
	$this->_searchEnabled    = true;
	$this->_searchFilters    = array();
	$this->_searchJoins      = array();
	$this->_searchLimit      = false;
	$this->_searchOrder      = array();
	$this->_searchSelects    = array();
	$this->searchHitsTotal   = 0;
	$this->searchHitsCurrent = 0;
	$this->_searchObjects     = array();
	return true;
	}

/**
 * Wrappers for database settings.
 */
function setDatabase($alias,$txt=null) {
	if (!$this->isSearchObject($alias)) { return false; }
	if (empty($txt)) { return false; }
	$this->_databaseName[$alias] = $txt;
	return true;
	}
function setDatabaseTable($alias,$table=null) {
	if (!$this->isSearchObject($alias)) { return false; }
	if (empty($table)) { return false; }
	$this->_databaseTable[$alias] = $table;
	return true;
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
 * Set the id field by property alias/name
 */
function setIdProperty($alias,$prop) {
	if (!$this->isProperty($alias,$prop)) { return false; }
	if (!($this->_idProperty === false)) { return false; }
	$this->_idProperty = array($alias,$prop);
	return true;
	}

/**
 * Wrapper methods to set property keys
 */
function setProperty($alias,$prop,$val) {
	return $this->_propertyKeySet($alias,$prop,'value',$val);
	}
function setPropertyDescription($alias,$prop,$val) {
	if (!is_string($val)) { return false; }
	return $this->_propertyKeySet($alias,$prop,'desc',$val);
	}
function setPropertyField($alias,$prop,$val) {
	if (!is_string($val)) { return false; }
	return $this->_propertyKeySet($alias,$prop,'fieldName',$val);
	}
function setPropertyIsDate($alias,$prop,$val) {
	if (!is_bool($val)) { return false; }
	return $this->_propertyKeySet($alias,$prop,'isDate',$val);
	}
function setPropertyIsQuoted($alias,$prop,$val) {
	if (!is_bool($val)) { return false; }
	return $this->_propertyKeySet($alias,$prop,'isQuoted',$val);
	}
function setPropertyIsReadOnly($alias,$prop,$val) {
	if (!is_bool($val)) { return false; }
	return $this->_propertyKeySet($alias,$prop,'isReadOnly',$val);
	}
function setPropertyIsUnique($alias,$prop,$val) {
	if (!is_bool($val)) { return false; }
	return $this->_propertyKeySet($alias,$prop,'isUnique',$val);
	}
function setPropertyMaxLength($alias,$prop,$val) {
	if (!is_numeric($val) or $val < 1) { return false; }
	return $this->_propertyKeySet($alias,$prop,'maxLength',$val);
	}
/** @deprecated use setProperty() instead */
function setPropertyValue($alias,$prop,$val) {
	return $this->setProperty($alias,$prop,$val);
	}

/**
 * Set property names visible to html forms when adding objects.
 * @param array $props - array of property names
 * @return bool
 * @see addPropertyAdd(), getPropertiesAdd()
 */
function setPropertiesAdd($add_props) {
	if (!is_array($this->_propertiesAdd)) { return false; }
	if (!is_array($add_props) or count($add_props) < 1) { return false; }
	foreach($add_props as $props) {
		if (!is_array($props) or count($props) !== 2) { return false; }
		$alias = $props[0];
		$prop  = $props[1];
		if (!$this->isProperty($alias,$prop)) { return false; }
		$this->_propertiesAdd[] = $props;
		}
	return true;
	}

/**
 * Set property names visible to html forms when deleting objects.
 * @param array $props - array of property names
 * @return bool
 * @see addPropertyDelete(), getPropertiesDelete()
 */
function setPropertiesDelete($del_props) {
	if (!is_array($this->_propertiesDelete)) { return false; }
	if (!is_array($del_props) or count($del_props) < 1) { return false; }
	foreach($del_props as $props) {
		if (!is_array($props) or count($props) !== 2) { return false; }
		$alias = $props[0];
		$prop  = $props[1];
		if (!$this->isProperty($alias,$prop)) { return false; }
		$this->_propertiesDelete[] = $props;
		}
	return true;
	}

/**
 * Set property names visible to html forms when editing objects.
 * @param array $props - array of property names
 * @return bool
 * @see addPropertyEdit(), getPropertiesEdit()
 */
function setPropertiesEdit($edit_props) {
	if (!is_array($this->_propertiesEdit)) { return false; }
	if (!is_array($edit_props) or count($edit_props) < 1) { return false; }
	foreach($edit_props as $props) {
		if (!is_array($props) or count($props) !== 2) { return false; }
		$alias = $props[0];
		$prop  = $props[1];
		if (!$this->isProperty($alias,$prop)) { return false; }
		$this->_propertiesEdit[] = $props;
		}
	return true;
	}

/**
 * Set property names visible to html forms when listing objects.
 * @param array $list_props - array of property alias/name arrays
 * @return bool
 * @see addPropertyList(), getPropertiesList()
 */
function setPropertiesList($list_props) {
	if (!is_array($this->_propertiesList)) { return false; }
	if (!is_array($list_props) or count($list_props) < 1) { return false; }
	foreach($list_props as $props) {
		if (!is_array($props) or count($props) !== 2) { return false; }
		$alias = $props[0];
		$prop  = $props[1];
		if (!$this->isProperty($alias,$prop)) { return false; }
		$this->_propertiesList[] = $props;
		}
	return true;
	}

/**
 * Set property names visible to html forms when viewing objects.
 * @param array $props - array of property names
 * @return bool
 * @see addPropertyView(), getPropertiesView()
 */
function setPropertiesView($view_props) {
	if (!is_array($this->_propertiesView)) { return false; }
	if (!is_array($view_props) or count($view_props) < 1) { return false; }
	foreach($view_props as $props) {
		if (!is_array($props) or count($props) !== 2) { return false; }
		$alias = $props[0];
		$prop  = $props[1];
		if (!$this->isProperty($alias,$prop)) { return false; }
		$this->_propertiesView[] = $props;
		}
	return true;
	}

/**
 * Set a method name that will be called with adding or editing a property
 * @see getPropertySelect()
 */
function setPropertySelect($alias,$prop,$method) {
	if (!$this->isProperty($alias,$prop)) { return false; }
	if (!is_string($method) or strlen($method) < 1) { return false; }
	return $this->propertyKeySet($alias,$prop,'select_method',$method);
	}

/**
 * Set search filters
 *
 * Use to filter searches by property name/property value pairs.
 *
 * Adds the specified property name and search term to the sql where statement.
 * If an error is detected (invalid property name) searching will be disabled.
 *
 * @param string $search_object
 * @param string $prop_name
 * @param string $prop_value
 * @param string $search_type (optional) =|like|>|<
 * @return bool
 */
function setSearchFilter($alias=null,$prop_name=null,$prop_value=null,
                         $search_type='=') {
	if (!($this->_searchEnabled === true)) { return false; }
	if (!$this->isProperty($alias,$prop_name)) {
		return $this->_searchDisable();
		}
	if (!is_array($this->_searchFilters)) {
		return $this->_searchDisable();
		}
	$q = $this->getPropertyQuoted($alias,$prop_name);
	if (!is_bool($q)) { return $this->_searchDisable(); }
	if ($this->getPropertyIsDate($alias,$prop_name) === true) {
		if ($prop_value == 'now()') { $q = false; }
		}
	($q) ? $d = '"' : $d = '';
	switch($search_type) {
		case '!=':
		case '=':
		case '>':
		case '<':
		break;
		case 'not in':
		case 'in':
			if (!is_array($prop_value) or count($prop_value) < 1) {
				return $this->_searchDisable();
				}
		break;
		case 'not like':
		case 'like':
			if (!$q) { return $this->_searchDisable(); }
		break;
		}
	if ($search_type == 'in' or $search_type == 'not in') {
		for($j=0; $j < count($prop_value); $j++) {
			$prop_value[$j] = $d . addslashes($prop_value[$j]) . $d;
			}
		$this->_searchFilters[] = $alias . '.'
		. $this->getPropertyField($alias,$prop_name)
		. ' ' . $search_type . '(' . $d . implode(',',$prop_value) . $d . ')';
		} else {
		$this->_searchFilters[] = $alias . '.'
		. $this->getPropertyField($alias,$prop_name)
		. ' ' . $search_type . ' ' . $d . addslashes($prop_value) . $d;
		}
	return true;
	}

/**
 * Set search limits
 *
 * Can provide an int as the limit or comma separated min,max limit format.
 *
 * @param int limit
 * @param string limit - min,max
 * @return bool
 */
function setSearchLimit($limit=null) {
	if (!($this->_searchEnabled === true)) { return false; }
	if (is_numeric($limit)) {
		if ($limit < 1) { return $this->_searchDisable(); }
		$this->_searchLimit = $limit;
		return true;
		}
	$junk = explode(',',$limit);
	if (!is_array($junk) or !(count($junk) == 2)) {
		return $this->_searchDisable();
		}
	$start = $junk[0];
	$max   = $junk[1];
	if (!is_numeric($start) or $start < 1) { return $this->_searchDisable(); }
	if (!is_numeric($max) or $max < 1) { return $this->_searchDisable(); }
	$this->_searchLimit = $start . ',' . $max;
	return true;
	}

/**
 * Add optional sort columns to the search by property name.
 *
 * If an error is detected (invalid property name) searching will be disabled.
 *
 * @param string search object name
 * @param string prop_name
 * @return bool
 */
function setSearchOrder($alias=null,$prop_name=null,$sort=null) {
	if (!$this->isProperty($alias,$prop_name)) {
		return $this->_searchDisable();
		}
	if (!is_array($this->_searchOrder)) {
		return $this->_searchDisable();
		}
	switch($sort) {
		case 'ASC':
		case 'DESC':
			$sort = ' ' . $sort;
		break;
		default:
			$sort = '';
		break;
		}
	$this->_searchOrder[] = $alias . '.'
	. $this->getPropertyField($alias,$prop_name) . $sort;
	return true;
	}

/**
 * Set the default search order property name
 */
function setSearchOrderDefault($alias,$prop) {
	if (!$this->isProperty($alias,$prop)) { return false; }
	$this->_searchOrderDefault = array($alias,$prop);
	return true;
	}

/**
 * Set the default search property name
 */
function setSearchPropertyDefault($alias,$prop) {
	if (!$this->isProperty($alias,$prop)) { return false; }
	$this->_searchPropertyDefault = array($alias,$prop);
	return true;
	}

/**
 * Add to the columns that will be returned by a search.
 *
 * If an error is detected (invalid property name) searching will be disabled.
 *
 * @param string alias
 * @param string search object name
 * @param string prop_name
 * @return bool
 */
function setSearchSelect($alias=null,$prop_name=null,$distinct=false) {
	if (!($this->_searchEnabled === true)) { return false; }
	if (!$this->isProperty($alias,$prop_name)) {
		return $this->_searchDisable();
		}
	if (!is_array($this->_searchSelects)) {
		return $this->_searchDisable();
		}
	if ($distinct === true) {
		$this->_searchSelects[] = 'distinct '
		. $alias . '.' . $this->getPropertyField($alias,$prop_name) . ' as '
		. addslashes($prop_name);
		} else {
		$this->_searchSelects[] = $alias . '.'
		. $this->getPropertyField($alias,$prop_name)
		. ' as ' . addslashes($alias . $prop_name);
		}
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
	// Inject id property if it exists
	$idProp = $this->getIdProperty();
	$ret_ar = array();
	if (is_array($idProp) and count($idProp) == 2) {
		$alias = $idProp[0];
		$prop  = $idProp[1];
		if ($this->isProperty($alias,$prop)) {
			$ret_ar[] = $idProp;
			}
		}
	foreach($this->_propertiesList as $prop) {
		$ret_ar[] = $prop;
		}
	return $ret_ar;
	}

/**
 * Retrieve list of property names available to forms when viewing objects
 * @see addPropertyView(), setPropertiesView()
 */
function getPropertiesView() {
	return $this->_propertiesView;
	}

/**
 * Get a property key
 */
function _propertyKeyGet($alias,$prop,$key=false) {
	if (!$this->isProperty($alias,$prop)) { return false; }
	if (!array_key_exists($key,$this->_properties[$alias][$prop])) { return false; }
	return $this->_properties[$alias][$prop][$key];
	}

/**
 * Set a property key to specified value.
 */
function _propertyKeySet($alias,$prop=false,$key=false,$txt=false) {
	if (!$this->isProperty($alias,$prop)) { return false; }
	$this->_properties[$alias][$prop][$key] = $txt;
	return true;
	}

/**
 * Disable searching, used to prevent search attempts when errors are detected.
 * @return bool false
 */
function _searchDisable() {
	$this->_searchEnabled = false;
	return false;
	}

/**
 * Validate a date string.
 * @return bool
 */
function _validateDateString($val='') {
	if ($val == '') { return false; }
	if ($val == 'now()') { return true; }
	if ($val == '0000-00-00 00:00:00') { return true; }
	if (strlen($val) !== 19) { return false; }
	return checkdate(substr($val,5,2),substr($val,8,2),substr($val,0,4));
	}

}
?>