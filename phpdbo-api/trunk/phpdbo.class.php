<?
/**
 * @package phpdbo-api
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

/**
 * Utility framework for querying objects in a database.
 *
 * @package phpdbo-api
 */
class phpdbo {

/**
 * Database settings
 * @var string
 * @see getDatabase(), setDatabase(), setDatabaseHost(), setDatabaseUser(),
 * @see setDatabasePass(), getDatabaseTable(), setDatabaseTable(),
 * @see getDatabasePort(), setDatabasePort()
 */
var $_databaseHost  = false;
var $_databaseName  = false;
var $_databaseUser  = false;
var $_databasePass  = false;
var $_databasePort  = false;
var $_databaseTable = false;

/**
 * Array containing object properties and mapping information
 * @see propertyAdd(), propertyList(), propertyDelete(), propertySet(),
 * @see propertyGet()
 */
var $_properties = array();

/**
 * Array containing property keys, properties of each property ;)
 * @see propertyKeyAdd(), propertyKeyGet(), propertyKeySet()
 */
var $_propertyKeys = array('desc','fieldName','isDate','isQuoted','isReadOnly',
                           'isUnique','maxLength','value');

/**
 * Optionally set id field for objects, ie the auto_increment column.
 * @see setIdProperty(), getIdProperty()
 */
var $_idProperty = false;

/**
 * Database connection object information
 */
var $_db_class = 'phpmydb';
var $db        = false;

/**
 * Vars related to searching
 */
var $_searchEnabled = false;
var $_searchFilters = false;
var $_searchLimit   = false;
var $_searchOrder   = false;
var $_searchSelects = false;

/**
 * Manually set FROM clause for joins etc...
 * @see setSearchFromClause()
 */
var $_searchFroms = array();

/**
 * Runtime, do not set.
 */
var $populated = false;

/**
 * Wrapper to get database name
 */
function getDatabase($s=true) {
	$t = $this->_databaseName; return ($s) ? $this->slasher($t) : $t;
	}

/**
 * Wrapper to get database port
 */
function getDatabasePort() {
	return $this->_databasePort;
	}

/**
 * Wrapper to get table name
 */
function getDatabaseTable($s=true,$add_db=true) {
	if ($add_db) {
		$db = $this->getDatabase(false);
		$t  = $db . '.' . $this->_databaseTable;
		} else {
		$t = $this->_databaseTable;
		}
	return ($s) ? $this->slasher($t) : $t;
	}

/**
 * Obtain the FROM clause for the current query.
 * @return mixed
 */
private function getFromClause() {
	if (is_array($this->_searchFroms) and count($this->_searchFroms)) {
		return 'FROM ' . implode(', ', $this->_searchFroms);
		} else {
		return 'FROM ' . $this->getDatabaseTable();
		}
	}

/**
 * Get property that is considered the id (unique) field.
 * @return false on errors
 * @return string property name
 */
function getIdProperty() {
	if (!is_string($this->_idProperty)) { return false; }
	if (!$this->isProperty($this->_idProperty)) { return false; }
	return $this->_idProperty;
	}

/**
 * Get the value of the specified property.
 */
function getProperty($prop) {
	return $this->_propertyKeyGet($prop,'value');
	}

/**
 * Get sorted list of all property names
 */
function getPropertyList() {
	if (!is_array($this->_properties)) { return false; }
	$t = array();
	foreach($this->_properties as $prop => $junk) {
		$t[] = $prop;
		}
	sort($t);
	return $t;
	}

/**
 * Get the description of the specified property name.
 * @param string $prop - property name
 * @return string property
 * @return bool false on errors
 */
function getPropertyDescription($prop,$s=true) {
	if (!$this->isProperty($prop)) { return false; }
	return $this->_propertyKeyGet($prop,'desc');
	}

/**
 * Get the table column name that corresponds to the specified property name.
 * @param string $prop - property name
 * @param bool $s - addslash() the returned string?
 * @return string field name
 * @return bool false on errors
 */
function getPropertyField($prop,$s=true) {
	if (!$this->isProperty($prop)) { return false; }
	$f = $this->_propertyKeyGet($prop,'fieldName');
	return ($s) ? $this->slasher($f) : $f;
	}

/**
 * Is the property a date field?
 * @param string property name
 * @return string error message on failures
 * @return bool
 */
function getPropertyIsDate($prop) {
	if (!$this->isProperty($prop)) { return 'not a property'; }
	return $this->_propertyKeyGet($prop,'isDate');
	}

/**
 * Is the property read-only?
 * @param string property name
 * @return string error message on failures
 * @return bool
 */
function getPropertyIsReadOnly($prop) {
	if (!$this->isProperty($prop)) { return 'not a property'; }
	return $this->_propertyKeyGet($prop,'isReadOnly');
	}

/**
 * Is the property part of a unique key?
 * @param string property name
 * @return string error message on failures
 * @return bool
 */
function getPropertyIsUnique($prop) {
	if (!$this->isProperty($prop)) { return 'not a property'; }
	return $this->_propertyKeyGet($prop,'isUnique');
	}

/**
 * Get the maximum string length of a property.
 * @param string $prop - property name
 * @return int maximum string length
 * @return bool false on errors
 */
function getPropertyMaxLength($prop) {
	if (!$this->isProperty($prop)) { return false; }
	return $this->_propertyKeyGet($prop,'maxLength');
	}

/**
 * When updating/querying a property in the database, is the field quoted?
 *
 * When updating the table, does the specified data need to be
 * quoted or not?  Any caller needs to verify the returned result is
 * boolean before trusting it...
 *
 * @return string on errors
 * @return true if quoted
 * @return false if not quoted
 */
function getPropertyQuoted($prop) {
	if (!$this->isProperty($prop)) { return 'is not a property'; }
	$f = $this->_properties[$prop]['isQuoted'];
	if (!is_bool($f)) { return 'invalid isQuoted setting'; }
	return $f;
	}

/**
 * Get the value of the specified property.
 * @deprecated use getProperty() instead
 */
function getPropertyValue($prop) {
	return $this->_propertyKeyGet($prop,'value');
	}

/**
 * Get list sql statement of currently populated object.
 * @return string
 */
public function getListSqlStatement() {
	if (!is_array($this->_searchSelects) or count($this->_searchSelects) < 1) {
		return 'Must use setSearchSelect() before searching';
		}
	if (!($this->_searchEnabled === true)) {
		return 'Searching has been disabled, possibly due to errors.';
		}
	$sql = 'SELECT ' . implode(', ', $this->_searchSelects) . ' '
	. $this->getFromClause() . ' ';
	if (is_array($this->_searchFilters) and count($this->_searchFilters) > 0) {
		$sql .= 'WHERE ' . implode(' AND ',$this->_searchFilters) . ' ';
		}
	if (is_array($this->_searchOrder) and count($this->_searchOrder) > 0) {
		$sql .= 'ORDER BY ' . implode(',',$this->_searchOrder) . ' ';
		}
	return $sql;
	}

/**
 * Is the property valid?
 */
function isProperty($prop=false) {
	if ($prop === false) { return false; }
	if (!is_array($this->_properties)) { return false; }
	if (!array_key_exists($prop,$this->_properties)) { return false; }
	if (!is_array($this->_propertyKeys)) { return false; }
	foreach($this->_propertyKeys as $key) {
		if (!array_key_exists($key,$this->_properties[$prop])) {
			return false;
			}
		}
	return true;
	}

/**
 * Populate the current object from database
 * Requires the current object to not be populated.  The id property field
 * must have been set via setIdProperty().
 * @param mixed $id to look up
 * @return bool
 */
function populateFromDb($id=null) {
	if ($this->populated === true) { return false; }
	$this->resetProperties();
	$ar = $this->queryById($id);
	if (!is_array($ar)) { return false; }
	$props = $this->getPropertyList();
	if (!is_array($props)) { return false; }
	foreach($props as &$prop) {
		if (!array_key_exists($prop, $ar)) { return false; }
		$this->setProperty($prop, $ar[$prop]);
		}
	$this->populated = true;
	return true;
	}

/**
 * Add a property definition
 * @param string $prop property name
 * @param string $field property field name
 * @param string $desc property description
 * @param array $props additional properties ;)
 * @return bool
 */
function propertyAdd($prop=false,$field=false,$desc=false,$props=false) {
	if (!is_array($this->_properties)) { return false; }
	if (!is_string($prop))             { return false; }
	if (array_key_exists($prop,$this->_properties)) { return false; }
	if (!is_array($this->_propertyKeys)) { return false; }
	// Populate with defaults
	$this->_properties[$prop] = array();
	foreach($this->_propertyKeys as $key) {
		$this->_properties[$prop][$key] = false;
		}
	if (is_string($field)) {
		$this->_properties[$prop]['fieldName'] = $field;
		} else {
		$this->_properties[$prop]['fieldName'] = $prop;
		}
	if (is_string($desc)) {
		$this->_properties[$prop]['desc'] = $desc;
		} else {
		$this->_properties[$prop]['desc'] = $prop;
		}
	$this->_properties[$prop]['isQuoted']  = true;
	$this->_properties[$prop]['maxLength'] = 1;
	if (is_array($props)) {
		foreach($props as $tag => $val) {
			switch($tag) {
				case 'desc':
					$this->setPropertyDescription($prop,$val);
				break;
				case 'fieldName':
					$this->setPropertyField($prop,$val);
				break;
				case 'isDate':
					$this->setPropertyIsDate($prop,$val);
				break;
				case 'isQuoted':
					$this->setPropertyIsQuoted($prop,$val);
				break;
				case 'isReadOnly':
					$this->setPropertyIsReadOnly($prop,$val);
				break;
				case 'isUnique':
					$this->setPropertyIsUnique($prop,$val);
				break;
				default:
					// allow custom properties
					if (is_string($tag)) {
						$this->_properties[$prop][$tag] = $val;
						}
				break;
				}
			}
		}
	return $this->isProperty($prop);
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
 * @param string property name
 * @param string key name
 * @return bool false
 * @return string value
 */
function propertyKeyGet($prop,$key) {
	return $this->_propertyKeyGet($prop,$key);
	}

/**
 * Wrapper to set a property key
 * @param string property name
 * @param string key name
 * @param string key value
 * @return bool
 */
function propertyKeySet($prop,$key,$val) {
	return $this->_propertyKeySet($prop,$key,$val);
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
	$sql = 'SELECT ' . implode(', ', $this->_searchSelects) . ' '
	. $this->getFromClause() . ' ';
	if (is_array($this->_searchFilters) and count($this->_searchFilters) > 0) {
		$sql .= 'WHERE ' . implode(' AND ',$this->_searchFilters) . ' ';
		}
	if (is_array($this->_searchOrder) and count($this->_searchOrder) > 0) {
		$sql .= 'ORDER BY ' . implode(',',$this->_searchOrder) . ' ';
		}
	if (!($this->_searchLimit === false)) {
		$sql .= 'LIMIT ' . $this->_searchLimit;
		}
	if ($debug) {
		echo $sql . "\n";
		}
	$qid = $this->db->db_query($sql);
	if ($qid === false) {
		if ($debug === true) {
			echo 'SQL STATEMENT: ' . $sql . "\n";
			die('SQL ERROR: ' . $this->db->db_error() . "\n");
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
		return 'queryHitsTotal() requires setSearchSelect()';
		}
	if (!($this->_searchEnabled === true)) {
		return 'queryHitsTotal() cannot run, searching has been disabled';
		}
	if (!($this->requireDatabase() === true)) { return 'db connection failed'; }
	$prop = $this->getIdProperty();
	if (!$this->isProperty($prop)) {
		return 'queryHitsTotal() requires setIdProperty()';
		}
	$sql = 'SELECT count(' . $this->getPropertyField($prop) . ') '
	. $this->getFromClause();
	if (is_array($this->_searchFilters) and count($this->_searchFilters) > 0) {
		$sql .= ' WHERE ' . implode(' and ',$this->_searchFilters);
		}
	if ($debug) {
		echo $sql . "\n";
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
 * Query an object by id and return it in an array with property keys
 * @param int $id
 * @return bool false on errors or object does not exist
 * @return array containing object and with property names as keys
 */
public function queryById($id=null) {
	if (!$this->requireDatabase()) { return false; }
	$id_prop = $this->getIdProperty();
	if (!$this->isProperty($id_prop)) { return false; }
	$q = $this->getPropertyQuoted($id_prop);
	if (!is_bool($q)) { return false; }
	($q) ? $d = '"' : $d = '';
	// Select everything...
	$selects = array();
	$props = $this->getPropertyList();
	if (!is_array($props) or !count($props)) { return false; }
	foreach($props as &$prop) {
		$selects[] = $this->getPropertyField($prop) . ' AS `'
		. $this->slasher($prop) . '`';
		}
	$sql = 'SELECT ' . implode(', ', $selects) . $this->getFromClause() . ' '
	. 'WHERE ' . $this->getPropertyField($id_prop) . ' = '
	. $d . $this->slasher($id) . $d . ' limit 1';
	$qid = $this->db->db_query($sql);
	if ($qid === false or !($this->db->db_num_rows($qid) == 1)) {
		return false;
		}
	$row = $this->db->db_fetch_array($qid);
	foreach($props as &$prop) {
		if (!array_key_exists($prop, $row)) { return false; }
		}
	unset($props);
	return $row;
	}

/**
 * Attempt to autoload the database connection object
 */
function requireDatabase() {
	if (is_object($this->db) and $this->db->connected === true) { return true; }
	if (!is_object($this->db)) {
		if (!is_string($this->_db_class)) {
			return '_db_class not set';
			}
		if (!class_exists($this->_db_class,false)) {
			return 'db class is MIA';
			}
		$this->db = new $this->_db_class();
		if (!is_object($this->db)) { return 'failed to load db class'; }
		}
	$rc = $this->db->db_connect($this->_databaseHost,$this->_databaseName,
	                            $this->_databaseUser,$this->_databasePass,
	                            $this->_databasePort);
	if ($rc === false) {
		return 'failed to connect to database';
		}
	return $this->db->connected;
	}

/**
 * Reset property values, for running multiple populate calls.
 */
function resetProperties() {
	foreach($this->getPropertyList() as $prop) {
		$this->setProperty($prop,false);
		}
	$this->populated = false;
	return true;
	}

/**
 * Initialize a new search.
 */
function searchReset() {
	$this->_searchSelects    = array();
	$this->_searchFilters    = array();
	$this->_searchOrder      = array();
	$this->_searchLimit      = false;
	$this->searchHitsTotal   = 0;
	$this->searchHitsCurrent = 0;
	$this->_searchEnabled    = true;
	return true;
	}

/**
 * Wrappers for database settings.
 */
function setDatabase($txt=null) {
	if (empty($txt)) { return false; }
	$this->_databaseName = $txt;
	return true;
	}
function setDatabaseClass($txt=null) {
	if (empty($txt)) { return false; }
	$this->_db_class = $txt;
	return true;
	}
function setDatabaseHost($txt=null) {
	if (empty($txt)) { return false; }
	$this->_databaseHost = $txt;
	return true;
	}
function setDatabasePass($txt=null) {
	if (empty($txt)) { return false; }
	$this->_databasePass = $txt;
	return true;
	}
function setDatabasePort($txt=null) {
	if (!is_numeric($txt)) { return false; }
	$this->_databasePort = $txt;
	return true;
	}
function setDatabaseUser($txt=null) {
	if (empty($txt)) { return false; }
	$this->_databaseUser = $txt;
	return true;
	}
function setDatabaseTable($table=null) {
	if (empty($table)) { return false; }
	$this->_databaseTable = $table;
	return true;
	}

/**
 * Set the id field by property name
 */
function setIdProperty($prop) {
	if (!$this->isProperty($prop)) { return false; }
	if (!($this->_idProperty === false)) { return false; }
	$this->_idProperty = $prop;
	}

/**
 * Wrapper methods to set property keys
 */
function setProperty($prop,$val) {
	return $this->_propertyKeySet($prop,'value',$val);
	}
function setPropertyDescription($prop,$val) {
	if (!is_string($val)) { return false; }
	return $this->_propertyKeySet($prop,'desc',$val);
	}
function setPropertyField($prop,$val) {
	if (!is_string($val)) { return false; }
	return $this->_propertyKeySet($prop,'fieldName',$val);
	}
function setPropertyIsDate($prop, $val = true) {
	if (!is_bool($val)) { return false; }
	return $this->_propertyKeySet($prop,'isDate',$val);
	}
function setPropertyIsNumeric($prop) {
	return $this->_propertyKeySet($prop, 'isQuoted', false);
	}
function setPropertyIsQuoted($prop,$val) {
	if (!is_bool($val)) { return false; }
	return $this->_propertyKeySet($prop,'isQuoted',$val);
	}
function setPropertyIsReadOnly($prop,$val) {
	if (!is_bool($val)) { return false; }
	return $this->_propertyKeySet($prop,'isReadOnly',$val);
	}
function setPropertyIsUnique($prop,$val) {
	if (!is_bool($val)) { return false; }
	return $this->_propertyKeySet($prop,'isUnique',$val);
	}
function setPropertyMaxLength($prop,$val) {
	if (!is_numeric($val) or $val < 1) { return false; }
	return $this->_propertyKeySet($prop,'maxLength',$val);
	}
/** @deprecated use setProperty() instead */
function setPropertyValue($prop,$val) {
	return $this->_propertyKeySet($prop,'value',$val);
	}

/**
 * Set search filters
 *
 * Use to filter searches by property name/property value pairs.
 *
 * Adds the specified property name and search term to the sql where statement.
 * If an error is detected (invalid property name) searching will be disabled.
 *
 * @param string $prop_name
 * @param string $prop_value
 * @param string $search_type (optional) =|like|>|<
 * @return bool
 */
function setSearchFilter($prop_name=null,$prop_value=null,$search_type='=') {
	if (!($this->_searchEnabled === true)) { return false; }
	if (!$this->isProperty($prop_name)) {
		return $this->_searchDisable();
		}
	if (!is_array($this->_searchFilters)) {
		return $this->_searchDisable();
		}
	$q = $this->getPropertyQuoted($prop_name);
	if (!is_bool($q)) { return $this->_searchDisable(); }
	if ($this->getPropertyIsDate($prop_name) === true) {
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
			$prop_value[$j] = $d . $this->slasher($prop_value[$j]) . $d;
			}
		$this->_searchFilters[] = $this->getPropertyField($prop_name) . ' '
		. $search_type . '(' . implode(',',$prop_value) . ')';
		} else {
		$this->_searchFilters[] = $this->getPropertyField($prop_name) . ' '
		. $search_type . ' ' . $d . $this->slasher($prop_value) . $d;
		}
	return true;
	}

/**
 * Manually add clauses to the search filters.
 *
 * Adds the specified clause to the sql where statement, without checking it,
 * translating field names, etc...
 *
 * @param string $clause
 * @return bool
 */
function setSearchFilterClause($clause=false) {
	if (!($this->_searchEnabled === true)) { return false; }
	if ($clause === false) { return false;}
	if (!is_array($this->_searchFilters)) {
		return $this->_searchDisable();
		}
	$this->_searchFilters[] = $clause;
	return true;
	}

/**
 * Manually specify search FROM clause(s)
 *
 * If set, the query will use values from these clauses instead of the
 * defaults. These clauses will not be checked, field names translated,
 * etc.
 *
 * @param string $clause
 * @return bool
 */
function setSearchFromClause($clause = null) {
	if (!($this->_searchEnabled === true)) { return false; }
	if (!is_string($clause) or !strlen($clause)) { return false; }
	if (!is_array($this->_searchFroms)) { return false; }
	$this->_searchFroms[] = $clause;
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
	if (!is_numeric($start) or $start < 0) { return $this->_searchDisable(); }
	if (!is_numeric($max) or $max < 1) { return $this->_searchDisable(); }
	$this->_searchLimit = $start . ',' . $max;
	return true;
	}

/**
 * Add optional sort columns to the search by property name.
 *
 * If an error is detected (invalid property name) searching will be disabled.
 *
 * @param string prop_name
 * @return bool
 */
function setSearchOrder($prop_name=null,$sort=null) {
	if (!($this->_searchEnabled === true)) { return false; }
	// special case - random results
	if (strtolower($prop_name) == 'random') {
		$this->_searchOrder[] = 'RAND()';
		return true;
		}
	// otherwise the search property has to actually exist!
	if (!$this->isProperty($prop_name)) {
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
	$this->_searchOrder[] = $this->getPropertyField($prop_name) . $sort;
	return true;
	}

/**
 * Add to the columns that will be returned by a search.
 *
 * If an error is detected (invalid property name) searching will be disabled.
 *
 * @param string prop_name
 * @return bool
 */
function setSearchSelect($prop_name=null,$distinct=false) {
	if (!($this->_searchEnabled === true)) { return false; }
	if (!$this->isProperty($prop_name)) {
		return $this->_searchDisable();
		}
	if (!is_array($this->_searchSelects)) {
		return $this->_searchDisable();
		}
	if ($distinct === true) {
		$this->_searchSelects[] = 'DISTINCT '
		. $this->getPropertyField($prop_name) . ' AS '
		. $this->slasher($prop_name);
		} else {
		$this->_searchSelects[] = $this->getPropertyField($prop_name) . ' AS '
		. "`" . $this->slasher($prop_name) . "`";
		}
	return true;
	}

/**
 * If db connected, use mysql_real_escape_string(), otherwise addslashes()
 * @param $txt
 */
function slasher($txt) {
	return (is_object($this->db) and $this->db->connected) ?
	mysql_real_escape_string($txt) : addslashes($txt);
	}

/**
 * Get a property key
 */
function _propertyKeyGet($prop,$key=false) {
	if (!$this->isProperty($prop)) { return false; }
	if (!array_key_exists($key,$this->_properties[$prop])) { return false; }
	return $this->_properties[$prop][$key];
	}
/**
 * Set a property key to specified value.
 */
function _propertyKeySet($prop=false,$key=false,$txt=false) {
	if (!$this->isProperty($prop)) { return false; }
	$this->_properties[$prop][$key] = $txt;
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
