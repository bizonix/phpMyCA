<?
/**
 * phpdboform extension for general purpose logging to files and/or databases
 * @package phpdbo-api
 * @subpackage phpdbo-log-api
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

/**
 * Define custom levels here...
 */
define('LOG_AUDIT', -1);
define('LOG_HIPAA', -2);

class phpdbolog extends phpdboform {

/**
 * Is database logging enabled?
 * @see getDatabaseEnabled(), setDatabaseEnabled()
 */
private $databaseEnabled = false;

/**
 * Log file name, if file logging is enabled.
 * @see getLogFile(), setLogFile()
 */
private $fileLog = false;

/**
 * Is file logging enabled?
 * @see getFileEnabled()
 */
private $fileEnabled = false;

/**
 * Optional log app
 * @see getLogApp(), setLogApp()
 */
private $logApp = null;

/**
 * Log level
 * @see getLogLevel(), setLogLevel()
 */
private $logLevel = LOG_NOTICE;

/**
 * Log file handle
 */
private $logFh = false;

/**
 * constructor - sets basic log properties
 */
function __construct() {
	parent::__construct();
	// generic log properties
	$this->propertyAdd('LogApp');
	$this->propertyAdd('LogDate');
	$this->propertyAdd('LogId');
	$this->propertyAdd('LogLevel');
	$this->propertyAdd('LogText');
	// id property
	$this->setIdProperty('LogId');
	// date properties
	$this->setPropertyIsDate('LogDate',true);
	// non-quoted properties
	$this->setPropertyIsQuoted('LogId',false);
	$this->setPropertyIsQuoted('LogLevel',false);
	// set max lengths
	$this->setPropertyMaxLength('LogApp',50);
	$this->setPropertyMaxLength('LogDate',19);
	$this->setPropertyMaxLength('LogId',10);
	$this->setPropertyMaxLength('LogLevel',2);
	$this->setPropertyMaxLength('LogText',255);
	// set properties when adding entries
	$p = array('LogDate','LogApp','LogLevel','LogText');
	$this->setPropertiesAdd($p);
	}

/**
 * destructor - close file handle if file logging is on...
 * @return void
 */
function __destruct() {
	if (is_resource($this->logFh)) {
		fclose($this->logFh);
		}
	}

/**
 * Wrapper to determine if database logging is enabled
 * @return bool
 */
public function getDatabaseEnabled() {
	return (is_bool($this->databaseEnabled)) ? $this->databaseEnabled : false;
	}

/**
 * Wrapper to determine if file logging is enabled
 * @return bool
 */
public function getFileEnabled() {
	return (is_bool($this->fileEnabled)) ? $this->fileEnabled : false;
	}

/**
 * Wrapper to get current log app name
 * @see setLogApp()
 * @return mixed
 */
public function getLogApp() {
	return $this->logApp;
	}

/**
 * Wrapper to get current log file name
 * @return string
 */
public function getLogFile() {
	return (is_string($this->fileLog)) ? $this->fileLog : '';
	}

/**
 * Get the current log level
 * @return int log level
 */
public function getLogLevel() {
	return ($this->isValidLevel($this->logLevel)) ? $this->logLevel : LOG_NOTICE;
	}

/**
 * Translate specified log level to log level name
 * @return string
 */
public function getLogLevelName($lvl=null) {
	switch($lvl) {
		case LOG_HIPAA:   return 'HIPAA';   break; // -2
		case LOG_AUDIT:   return 'AUDIT';   break; // -1
		case LOG_EMERG:   return 'EMERG';   break; // 0
		case LOG_ALERT:   return 'ALERT';   break; // 1
		case LOG_CRIT:    return 'CRIT';    break; // 2
		case LOG_ERR:     return 'ERR';     break; // 3
		case LOG_WARNING: return 'WARNING'; break; // 4
		case LOG_NOTICE:  return 'NOTICE';  break; // 5
		case LOG_INFO:    return 'INFO';    break; // 6
		case LOG_DEBUG:   return 'DEBUG';   break; // 7
		}
	return 'UNDEF';
	}

/**
 * Wrappers to writing specific log level entries
 * @param string $txt
 * @return bool
 */
public function log_audit($txt=null)   { return $this->write($txt,LOG_AUDIT);   }
public function log_debug($txt=null)   { return $this->write($txt,LOG_DEBUG);   }
public function log_hipaa($txt=null)   { return $this->write($txt,LOG_HIPAA);   }
public function log_info($txt=null)    { return $this->write($txt,LOG_INFO);    }
public function log_notice($txt=null)  { return $this->write($txt,LOG_NOTICE);  }
public function log_warning($txt=null) { return $this->write($txt,LOG_WARNING); }
public function log_err($txt=null)     { return $this->write($txt,LOG_ERR);     }
public function log_crit($txt=null)    { return $this->write($txt,LOG_CRIT);    }
public function log_alert($txt=null)   { return $this->write($txt,LOG_ALERT);   }
public function log_emerg($txt=null)   { return $this->write($txt,LOG_EMERG);   }

/**
 * Wrapper to set current log app name
 * @see getLogApp()
 * @return void
 */
public function setLogApp($txt=null) { $this->logApp = $txt; }

/**
 * Turn on database logging.
 * @return bool
 */
public function setLogDatabase() {
	if (!is_string($this->_databaseName))     { return false; }
	if (!is_string($this->_databaseTable))    { return false; }
	if (!is_array($this->_propertiesAdd) or count($this->_propertiesAdd) < 1) {
		return false;
		}
	if (!($this->requireDatabase() === true)) { return false; }
	$this->databaseEnabled = true;
	return true;
	}

/**
 * Set file to log to, which will enable file logging.
 * @param string $file
 * @return bool
 */
public function setLogFile($file=null) {
	if (!is_string($file) or strlen($file) < 1) { return false; }
	if (is_dir($file)) { return false; }
	$this->logFh = @fopen($file,'a');
	if (!is_resource($this->logFh)) { return false; }
	$this->fileLog = $file;
	$this->fileEnabled = true;
	return true;
	}

/**
 * Set log level
 * @see getLogLevel()
 * @param int $lvl
 * @return bool
 */
public function setLogLevel($lvl=null) {
	if (!$this->isValidLevel($lvl)) { return false; }
	$this->logLevel = $lvl;
	return true;
	}

/**
 * Hook to customize extended properties
 * @return void
 */
protected function customizeLogEntry() {
	return true;
	}

/**
 * Is either file or database logging enabled?
 * @return bool
 */
private function getLogEnabled() {
	return ($this->fileEnabled === true or $this->databaseEnabled === true);
	}

/**
 * Get current timestamp (for when logging to screen/file)
 * @return string datetime
 */
private function getTimeStamp() { return date('Y-m-d H:i:s'); }

/**
 * Is the specified logging level valid?
 * @param int $level
 * @return bool
 */
private function isValidLevel($lvl=null) {
	switch($lvl) {
		case LOG_HIPAA:   return true; break; // -2
		case LOG_AUDIT:   return true; break; // -1
		case LOG_EMERG:   return true; break; // 0
		case LOG_ALERT:   return true; break; // 1
		case LOG_CRIT:    return true; break; // 2
		case LOG_ERR:     return true; break; // 3
		case LOG_WARNING: return true; break; // 4
		case LOG_NOTICE:  return true; break; // 5
		case LOG_INFO:    return true; break; // 6
		case LOG_DEBUG:   return true; break; // 7
		}
	return false;
	}

/**
 * Convert currently populate entry to text
 * @return string
 */
private function propertiesToText() {
	$txt = array($this->getTimeStamp());
	if (is_string($this->getProperty('LogApp'))) {
		$txt[] = $this->getProperty('LogApp');
		}
	$txt[] = $this->getLogLevelName($this->getProperty('LogLevel')) . ':';
	$txt[] = $this->getProperty('LogText');
	return implode(' ',$txt);
	}

/**
 * Validate populated entry before attempting to stick it in the database.
 * @return bool
 */
private function validate() {
	$props = $this->getPropertiesAdd();
	if (!is_array($props) or count($props) < 1) { return false; }
	foreach($props as $prop) {
		$val = $this->getProperty($prop);
		if ($val === false) { continue; }
		// check date fields
		$isDate = $this->getPropertyIsDate($prop);
		if (!is_bool($isDate)) { return false; }
		if ($isDate) {
			if (!($this->_validateDateString($val) === true)) {
				return false;
				}
			continue;
			}
		// check quotes
		$isQuoted = $this->getPropertyQuoted($prop);
		if (!is_bool($isQuoted)) { return false; }
		if ($isQuoted === false) {
			if (!is_numeric($val)) { return false; }
			}
		// check max length
		$max = $this->getPropertyMaxLength($prop);
		if (!is_numeric($max)) { return false; }
		if (strlen($val) > $max) { return false; }
		}
	return true;
	}

/**
 * Write log entry to appropriate logs if priority is above current log level
 * Will write to standard output if logging is not enabled.
 * @param string $txt
 * @param int $prio
 * @return bool
 */
private function write($txt=null,$prio=null) {
	if (empty($txt))                 { return false; }
	if (!$this->isValidLevel($prio)) { return false; }
	// skip if prio is higher than current log level
	if ($prio > $this->getLogLevel()) { return true; }
	// set properties and call appropriate log types
	$this->resetProperties();
	if (is_string($this->getLogApp())) {
		$this->setProperty('LogApp',$this->getLogApp());
		}
	$this->setProperty('LogLevel', $prio);
	$this->setProperty('LogText',  $txt);
	$this->setProperty('LogDate', 'now()');
	$this->populated = true;
	// call customization hook
	$this->customizeLogEntry();
	// If logging is not enabled, output directly to the screen...
	if (!$this->getLogEnabled()) { return $this->writeScreen(); }
	//
	// Try file and/or db logging, return false if either fail...
	//
	$fail = 0;
	if ($this->getFileEnabled()) {
		$fail += ($this->writeFile()) ? 0 : 1;
		}
	if ($this->getDatabaseEnabled()) {
		$fail += ($this->writeDatabase()) ? 0 : 1;
		}
	return ($fail > 0) ? false : true;
	}

/**
 * Write log entry to log database
 */
private function writeDatabase($validate=true) {
	if (!$this->getDatabaseEnabled()) { return false; }
	if ($validate === true) {
		$rc = $this->validate();
		if (!($rc === true)) { return false; }
		}
	$fl = array();
	$vl = array();
	foreach($this->getPropertiesAdd() as $prop) {
		$val = $this->getProperty($prop);
		if ($val === false) { continue; }
		$field = $this->getPropertyField($prop);
		if (!is_string($field)) { return false; }
		$quoted = $this->getPropertyQuoted($prop);
		if (!is_bool($quoted)) { return false; }
		if ($quoted === false) {
			if (!is_numeric($val)) { return false; }
			}
		// handle unquoting date fields with now()
		if ($this->getPropertyIsDate($prop)) {
			if ($val == 'now()') { $quoted = false; }
			}
		$d = ($quoted) ? '"' : '';
		$fl[] = $field;
		$vl[] = $d . addslashes($val) . $d;
		}
	// something went horribly wrong
	if (count($fl) < 1) { return false; }
	if (!(count($fl) == count($vl))) { return false; }
	// generate sql statement
	$sql = 'insert into ' . $this->getDatabaseTable() . ' '
	. '(' . implode(',',$fl) . ') values(' . implode(',',$vl) . ')';
	return ($this->db->db_query($sql) === false) ? false : true;
	}

/**
 * Write log entry to log file
 * @return bool
 */
private function writeFile() {
	if (!$this->getFileEnabled()) { return false; }
	return fwrite($this->logFh,$this->propertiesToText() . "\n");
	}

/**
 * Write log entry directly to stdout, used as fallback if file/db not enabled
 * @return void
 */
private function writeScreen() {
	echo $this->propertiesToText() . "\n";
	}

}

?>