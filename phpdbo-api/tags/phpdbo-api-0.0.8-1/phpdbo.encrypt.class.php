<?
/**
 * Extend phpdbo to handle aes encrypted fields.
 */
class phpdboenc extends phpdbo {

/**
 * Constructor - add custom encryption properties
 */
public function __construct() {
	$this->propertyKeyAdd('isAES');
	$this->propertyKeyAdd('secret');
	}

/**
 * Get the table column name that corresponds to the specified property name.
 * @param string $prop - property name
 * @param bool $s - ignored
 * @return string field name
 * @return bool false on errors
 */
public function getPropertyField($prop, $s = true) {
	if (!$this->isProperty($prop)) { return false; }
	$db_col = $this->_propertyKeyGet($prop, 'fieldName');
	if ($this->getPropertyIsAES($prop)) {
		$f = 'AES_DECRYPT(`' . $this->slasher($db_col) . '`, "'
		. $this->slasher($this->getPropertySecret($prop)) . '")';
		} else {
		$f = $this->slasher($db_col);
		}
	return $f;
	}

/**
 * Is specified property AES encrypted?
 * @param string prop
 * @return bool
 */
public function getPropertyIsAES($prop = null) {
	return $this->propertyKeyGet($prop, 'isAES');
	}

/**
 * Get encryption secret of specified property key
 * @param string prop
 * @return bool
 */
private function getPropertySecret($prop = null) {
	return $this->propertyKeyGet($prop, 'secret');
	}

/**
 * Set property to be AES encrypted in database
 * @param string prop
 * @param bool isAES
 * @return bool
 */
public function setPropertyIsAES($prop = null, $val = true) {
	return $this->propertyKeySet($prop, 'isAES', $val);
	}

/**
 * Set property encryption secret
 * @param string prop
 * @param string secret
 * @return bool
 */
public function setPropertySecret($prop = null, $val = true) {
	return $this->propertyKeySet($prop, 'secret', $val);
	}

}

?>
