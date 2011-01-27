<?
/**
 * certDbo class - base class for all phpmyca db objects
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

class phpmycadbo extends phpdboform {

/**
 * Constructor
 */
public function __construct() {
	parent::__construct();
	}

/**
 * Generic method to add currently populated object to database
 * @param bool $validate
 * @return bool true on success
 * @return string error message on failures
 */
public function add($validate=true) {
	// validate
	if (!($validate === false)) {
		$rc = $this->validate(true);
		if (!($rc === true)) { return $rc; }
		}
	if (!($this->requireDatabase() === true)) { return 'db connect failure'; }
	// Can we determine the idproperty?
	$idProp = $this->getIdProperty();
	if ($idProp === false) { return 'Cannot determine ID property.'; }
	// Generate the sql statement, only adding fields that exist...
	foreach($this->getPropertyList() as $prop) {
		$val = $this->getProperty($prop);
		if ($val === false) { continue; }
		$desc = $this->getPropertyDescription($prop);
		$field = $this->getPropertyField($prop);
		if (!is_string($field)) {
			return 'Failed to get field name for: ' . $desc;
			}
		$quoted = $this->getPropertyQuoted($prop);
		if (!is_bool($quoted)) {
			return 'Failed to determine if ' . $desc . ' is quoted.';
			}
		if ($quoted === false) {
			if (!is_numeric($val)) {
				return $desc . ' is not quoted and not numeric';
				}
			}
		// Handle unquoting date fields with now()...
		if ($this->getPropertyIsDate($prop)) {
			if ($val == 'now()') { $quoted = false; }
			}
		($quoted) ? $d = '"' : $d = '';
		$fl[] = $field;
		$vl[] = $d . addslashes($val) . $d;
		}
	// Something went horribly wrong
	if (count($fl) < 1) { return 'Properties were not properly populated.'; }
	if (!(count($fl) == count($vl))) { return 'field/value mismatch'; }
	// Generate the sql statement.
	$sql = 'insert into ' . $this->getDatabaseTable() . ' '
	. '(' . implode(',',$fl) . ') ' . 'values(' . implode(',',$vl) . ')';
	// Do the insert
	if ($this->db->db_query($sql) === false) {
		return 'SQL ERROR: ' . $this->db->db_error();
		}
	// Plug in the resulting id
	$id = $this->db->db_insert_id();
	if (!is_numeric($id) or $id < 1) {
		return 'Failed to obtain ID after insert.';
		}
	$this->setProperty($idProp,$id);
	return true;
	}

/**
 * Obtain array of certificates that have been issued by specified signer id
 * @param int $id
 * @return mixed
 *   bool false on errors, array on success
 */
public function getIssuerSubjects($id = null) {
	if (!is_numeric($id) or $id < 1) { return false; }
	$this->searchReset();
	foreach($this->getPropertyList() as $prop) {
		$this->setSearchSelect($prop);
		}
	$this->setSearchFilter('ParentId',$id);
	$this->setSearchOrder('Id');
	$rows = $this->query();
	return (is_array($rows)) ? $rows : false;
	}

/**
 * Obtain PEM encoded certificate of specified certificate id
 * @param int $certId
 * @return mixed
 */
public function getPemCertById($certId=null) {
	if (!is_numeric($certId) or $certId < 1) { return false; }
	$this->searchReset();
	$this->setSearchSelect('Certificate');
	$this->setSearchFilter('Id',$certId);
	$this->setSearchLimit(1);
	$rows = $this->query();
	if (!is_array($rows) or count($rows) < 1) { return false; }
	return $rows[0]['Certificate'];
	}

/**
 * Is populated cert expired?
 * @param $days
 *   Optionally specify number of days in the future to check
 * @return boolean
 */
public function isExpired($days = null) {
	if (!$this->populated) { return false; }
	$now = time();
	if (is_numeric($days) and $days > 0) {
		$now += (60 * 60 * 24) * $days;
		}
	$expireDate = $this->getProperty('ValidTo');
	$expireTime = ($expireDate) ? strtotime($expireDate) : false;
	return ($expireTime && ($now >= $expireTime));
	}

/**
 * Is populated cert revoked?
 * @return boolean
 */
public function isRevoked() {
	if (!$this->populated) { return false; }
	$revokeDate = $this->getProperty('RevokeDate');
	$revokeTime = ($revokeDate) ? strtotime($revokeDate) : false;
	return ($revokeTime && (time() >= $revokeTime));
	}

/**
 * Populate new object from specified array (instead of db)
 * @param $props
 *   Array with keys (property names) and values (property values)
 * @return bool
 */
public function populateFromArray(&$ar=null) {
	if ($this->populated) { return false; }
	if (!is_array($ar) or !count($ar)) { return false; }
	$this->resetProperties();
	foreach($ar as $prop => $val) {
		$this->setProperty($prop,$val);
		}
	$this->populated = true;
	return true;
	}

/**
 * Generic method to update currently populate object
 * @param bool $validate - pass it through validation first
 * @return string error message on failures
 * @return bool true on success.
 */
public function update($validate=true) {
	// validate first...
	if (!($validate === false)) {
		$rc = $this->validate();
		if (!($rc === true)) { return $rc; }
		}
	if (!($this->requireDatabase() === true)) { return 'db connect failure'; }
	// need the id property
	$idProp = $this->getIdProperty();
	$id = $this->getProperty($idProp);
	if ($id === false) { return 'id not set'; }
	$idField = $this->getPropertyField($idProp);
	// look up the current object for comparison
	$curObj = $this->queryById($id);
	if (!is_array($curObj)) { return 'failed to locate existing member'; }
	// Store actual updates here...
	$updates = array();
	foreach($this->getPropertyList() as $prop) {
		$val = $this->getProperty($prop);
		$desc = $this->getPropertyDescription($prop);
		if ($val === false) { continue; }
		if ($val == $curObj[$prop]) { continue; }
		// passed muster, add it to the updates array
		$f = $this->getPropertyField($prop);
		$quoted = $this->getPropertyQuoted($prop);
		// unquote date fields that need it....
		if ($this->getPropertyIsDate($prop)) {
			if (strtolower($val) == 'now()') { $quoted = false; }
			}
		($quoted) ? $d = '"' : $d = '';
		$updates[] = $f . '=' . $d . addslashes($val) . $d;
		}
	// Hmm?
	if (count($updates) < 1) { return true; }
	// Generate sql statement
	$d = ($this->getPropertyQuoted($idProp)) ? '"' : '';
	$sql = 'update ' . $this->getDatabaseTable() . ' '
	. 'set ' . implode(', ',$updates) . ' '
	. 'where ' . $idField . '=' . $d . addslashes($id) . $d . ' limit 1';
	// Do the update
	if ($this->db->db_query($sql) === false) {
		return 'SQL ERROR: ' . $this->db->db_error();
		}
	return true;
	}

/**
 * Generic validation of currently populated object.
 * @param bool $skipId - skip validation of id property (on adds)
 * @return bool true on success
 * @return string error message on failures
 */
public function validate($skipId=false) {
	if (!is_bool($skipId)) { return 'skipId argument is invalid'; }
	if (!($this->populated === true)) { return 'object is not populated'; }
	$props = $this->getPropertyList();
	if (!is_array($props) or count($props) < 1) {
		return 'no properties have been defined';
		}
	if ($skipId === false) {
		$idProp = $this->getIdProperty();
		if (!$this->isProperty($idProp)) {
			return 'cannot determine ID property';
			}
		}
	// loop through each property and validate non-empty values
	foreach($props as $prop) {
		$val = $this->getProperty($prop);
		$desc = $this->getPropertyDescription($prop);
		if ($val === false) { continue; }
		if ($this->getPropertyIsDate($prop)) {
			if (!($this->_validateDateString($val) === true)) {
				$m = 'prop(' . $desc . ') not valid date: '
				. $val;
				return $m;
				}
			continue;
			}
		$quoted = $this->getPropertyQuoted($prop);
		if (!is_bool($quoted)) {
			$m = 'prop(' . $desc . ') getPropertyQuoted()';
			return $m;
			}
		if ($quoted === false) {
			if (!is_numeric($val)) {
				$m = 'prop(' . $desc . ') non-numeric value: ' . $val;
				return $m;
				}
			}
		// check max length
		$len = strlen($val);
		$max = $this->getPropertyMaxLength($prop);
		if ($len > $max) {
			$m = 'prop(' . $desc . ') length of ' . $len
			. ' > max length of ' . $max;
			return $m;
			}
		}
	return true;
	}

}

?>