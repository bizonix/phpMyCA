<?
/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
(basename($_SERVER['PHP_SELF']) == basename(__FILE__)) && die('Access Denied');

/**
 * Webapp class for defining properties of webapp users
 */
class webappHtml {

/**
 * Action requested by user.
 * @see getActionRequest()
 */
private $actionRequest = null;

/**
 * Breadcrumb variables
 * @see crumbAdd(), crumbDelete(), crumbSet(), crumbGet()
 * @see setBreadCrumbs(), getBreadCrumbs()
 */
private $breadCrumbs = array();

/**
 * hold error messages
 * @see errorMsgGet(), errorMsgSet()
 */
private $errorMsg = null;

/**
 * html character set
 * @see htmlCharsetSet(), htmlCharsetGet()
 */
private $htmlCharset = null;

/**
 * Array containing css links to include in page headers
 * @see htmlCssAdd(), htmlCssGet()
 */
private $htmlCss = array();

/**
 * html doctype
 * @see htmlDoctypeSet(), htmlDoctypeGet()
 */
private $htmlDoctype = null;

/**
 * Array containing js links to include in page headers
 * @see htmlJsAdd(), htmlJsGet()
 */
private $htmlJs = array();

/**
 * Array containing html meta tags to include in page headers
 * @see htmlMetaAdd(), htmlMetaGet()
 */
private $htmlMeta = array();

/**
 * Array of menu links to be used to generate href lists
 * @see addMenuLink(), getMenuLinks()
 */
private $menuLinks = array();

/**
 * Page title
 * @see getPageTitle(), setPageTitle()
 */
private $pageTitle     = null;

/**
 * Search variables, populated from breadcrumbs by setBreadCrumbs
 */
public  $searches     = 0;
private $searchFields = null;
private $searchTerms  = null;
private $searchTypes  = null;

/**
 * Template Variables
 * @see getVar(), setVar(), varIsSet()
 */
private $templateVars = array();

/**
 * Constructor
 */
public function webappHtml() {
	$this->parseRequestVariables();
	$this->crumbAdd(WA_QS_ACTION);
	$this->crumbAdd(WA_ACTION_PREVIOUS);
	$this->crumbAdd(WA_QS_DAYS);
	$this->crumbAdd(WA_QS_ID);
	$this->crumbAdd(WA_QS_MENU);
	$this->crumbAdd(WA_QS_ORDER);
	$this->crumbAdd(WA_QS_PAGE);
	$this->crumbAdd(WA_QS_SORT);
	$this->crumbAdd(WA_QS_SEARCH_FIELDS);
	$this->crumbAdd(WA_QS_SEARCH_TERMS);
	$this->crumbAdd(WA_QS_SEARCH_TYPES);
	$this->setBreadCrumbs();
	}

/**
 * Add a link to be included when getPageFooter() is called.
 * @see getMenuLinks()
 * @param string $href
 * @param string $text
 * @param string $class
 * @return void
 */
public function addMenuLink($href=null,$text=null,$class=null) {
	if (!is_array($this->menuLinks)) { return; }
	$this->menuLinks[] = array($href,$text,$class);
	}

/**
 * Add a variable to the breadcrumb trail.
 * @param string $varname
 * @return bool
 * @see crumbDelete(), getBreadCrumbs()
 */
public function crumbAdd($crumb=null) {
	if ($this->crumbExists($crumb)) { return true; }
	if (!is_array($this->breadCrumbs)) { return false; }
	if (!is_string($crumb)) { return false; }
	if (strlen($crumb) < 1 or strlen($crumb) > 20) { return false; }
	if (array_key_exists($crumb,$this->breadCrumbs)) { return true;  }
	$this->breadCrumbs[$crumb] = null;
	return true;
	}

/**
 * Remove a variable from the breadcrumb trail.
 * @param string $crumb
 * @return bool
 * @see crumbAdd()
 */
public function crumbDelete($crumb=null) {
	if (!$this->crumbExists($crumb)) { return true; }
	if (!is_array($this->breadCrumbs)) { return false; }
	if (!is_string($crumb)) { return false; }
	$c = array();
	foreach($this->breadCrumbs as $key => $data) {
		if ($key == $crumb) { continue; }
		$c[$key] = $data;
		}
	$this->breadCrumbs = $c;
	return true;
	}

/**
 * Does a breadcrumb exist?
 * @param string $crumb
 * @return bool
 */
public function crumbExists($crumb=null) {
	if (!is_array($this->breadCrumbs)) { return false; }
	return array_key_exists($crumb,$this->breadCrumbs);
	}

/**
 * Retrieve value of specified bread crumb.
 * @param string $crumb
 * @return mixed
 */
function crumbGet($crumb) {
	if (!$this->crumbExists($crumb)) { return false; }
	return $this->breadCrumbs[$crumb];
	}

/**
 * Set specified breadcrumb to specified value
 * @param string $crumb
 * @param string $data
 * @return bool
 */
public function crumbSet($crumb=null,$data=null) {
	if (!$this->crumbExists($crumb)) { return false; }
	$this->breadCrumbs[$crumb] = $data;
	return true;
	}

/**
 * Set error message
 * @param string $txt
 * @return void
 * @see errorMsgSet()
 */
public function errorMsgGet() {
	return (is_string($this->errorMsg)) ? $this->errorMsg : false;
	}

/**
 * Set error message
 * @param string $txt
 * @return void
 * @see errorMsgGet()
 */
public function errorMsgSet($txt=null) {
	$this->errorMsg = $txt;
	}

/**
 * Extract PEM encoded block
 * Used to extract particular PEM encoded block of text when multiple
 * exist with a string.
 * @param string $pemText
 * @param string $blockType
 * @return mixed
 */
public function extractPemBlock($pemText=null,$blockType=null) {
	if (!is_string($pemText) or !is_string($blockType)) { return false; }
	$pr = '/(-----((BEGIN)|(END)) ' . $blockType . '-----)/';
	$split = preg_split($pr,$pemText);
	if (!isset($split[1])) { return false; }
	return '-----BEGIN ' . $blockType . '-----' . "\n"
	. trim($split[1]) . "\n" . '-----END ' . $blockType . '-----' . "\n";
	}

/**
 * Obtain requested action
 * @return string $action_request on success
 * @return bool false on failures or no action request
 */
public function getActionRequest() {
	return (is_string($this->actionRequest)) ? $this->actionRequest : false;
	}

/**
 * Retrieve action query string along with breadcrumb and optionally id
 * If ID is specified, it will replace whatever is currently in the breadcrumb.
 * If ID is false, ID will be removed completely from the query string.
 * If ID is 0, a partial ID= will be returned on the tail of the query string.
 * If ID is null (default), the ID will be added if it is in the breadcrumb.
 * @param string $action (required)
 * @param mixed $id - (optional) if set, will append id to query string
 * @return string query string
 * @return bool false
 */
public function getActionQs($action=null,$id=null) {
	if (!is_string($action) or strlen($action) < 1) { return false; }
	// exclude WA_QS_ACTION if it is already in the bread crumb
	$exclude = array(WA_QS_ACTION);
	$tmp_ar  = array(WA_QS_ACTION . '=' . $action);
	// exclude id from breadcrumb it was specified
	if ($id === false) { $exclude[] = WA_QS_ID; }
	// replace id from breadcrumb
	if (strlen((string) $id) > 0) { $exclude[] = WA_QS_ID; }
	foreach($this->getBreadCrumbs($exclude) as $crumb => $data) {
		$tmp_ar[] = $crumb . '=' . urlencode($data);
		}
	// Add user specified id if needed...
	if (strlen((string) $id) > 0) {
		if ((string) $id == '0') {
			$tmp_ar[] = WA_QS_ID . '=';
			} else {
			$tmp_ar[] = WA_QS_ID . '=' . urlencode($id);
			}
		}
	return './?' . implode('&',$tmp_ar);
	}

/**
 * Get a unique number for numbering hidden divs etc...
 * @return int
 */
public function getNumber() {
	static $num = 0;
	return $num++;
	}

/**
 * Get array of breadcrumbs with empty/optional crumbs stripped out.
 * @param array $exclude_crumbs - crumbs to optionally strip out
 * @return array crumbs
 */
public function getBreadCrumbs($exclude=null) {
	if (!is_array($this->breadCrumbs)) { return array(); }
	$c = array();
	foreach($this->breadCrumbs as $crumb => $data) {
		if (is_array($exclude) and in_array($crumb,$exclude)) { continue; }
		if ($data == null) { continue; }
		if (is_array($data)) {
			foreach($data as $key => $txt) {
				$c[$crumb . '[' . $key . ']'] = $txt;
				}
			} else {
			$c[$crumb] = $data;
			}
		}
	return $c;
	}

/**
 * Generate form-based breadcrumb with HIDDEN inputs
 * @param string $action - optionally specify form action
 * @param string $id - optionally specified ID
 * @return string html
 */
public function getFormBreadCrumb($action=null,$id=null) {
	$exclude = array();
	$hidden  = array();
	if (!($action === null)) {
		$exclude[] = WA_QS_ACTION;
		$hidden[WA_QS_ACTION] = $action;
		}
	if (!($id === null)) {
		$exclude[] = WA_QS_ID;
		$hidden[WA_QS_ID] = $id;
		}
	foreach($this->getBreadCrumbs($exclude) as $crumb => $data) {
		$hidden[$crumb] = $data;
		}
	if (count($hidden) < 1) { return "\n"; }
	$h = array();
	foreach($hidden as $crumb => $data) {
		$h[] = '<INPUT TYPE="hidden" NAME="' . $crumb . '" VALUE="'
		. urlencode($data) . '">';
		}
	return implode("\n",$h) . "\n";
	}

/**
 * Get standard html form footer
 * @return mixed
 */
public function getFormFooter() {
	return "</FORM>\n";
	}

/**
 * Get standard html form header
 * @param string $form_id = optional form id
 * @param string $on_submit: optional javascript onSubmit() function to call
 * @param string $action = optional form action
 * @param bool $multipart - sets enctype multipart/form-data
 * @return mixed
 */
public function getFormHeader($form_id=null,$on_submit=null,$action='./',
                              $multipart=false) {
	$id = '';
	if (is_string($form_id)) { $id = ' ID="' . $form_id . '" NAME="'
		. $form_id . '"';
		}
	$onSubmit = '';
	if (is_string($on_submit)) {
		$onSubmit = ' onSubmit="' . $on_submit . '"';
		}
	$a = ($action === null) ? './' : $action;
	$h = '<FORM' . $id . $onSubmit . ' METHOD="post" ACTION="' . $a . '"';
	if ($multipart === true) {
		$h .= ' enctype="multipart/form-data"';
		}
	$h .= '>';
	return $h . "\n";
	}

/**
 * Get form to search list object
 * @param object phpdboform object
 * @return string html form
 */
public function getFormListSearch(&$fo) {
	if (!is_a($fo,'phpdboform')) { return "\n"; }
	// populate needed vars
	$reset_qs  = $this->getQsStripSearch();
	$search_qs = 'javascript:document.search_form.submit()';
	$reset_txt = '<IMG SRC="images/off16.png" BORDER="0" HEIGHT="16" '
	. 'WIDTH="16" ALT="Reset Search">';
	$search_txt = '<IMG SRC="images/query16.png" BORDER="0" HEIGHT="16" '
	. 'WIDTH="16" ALT="Search">';
	$href_ar   = array();
	$href_ar[] = '<A HREF="' . $search_qs . '">' . $search_txt . '</A>';
	$href_ar[] = '<A HREF="' . $reset_qs . '">' . $reset_txt . '</A>';
	$links = implode('',$href_ar);
	$next = 0;
	$colSpan = count($fo->_propertiesList);

	// generate html
	$h   = array();
	$h[] = '<TR>';
	$h[] = '<TD ALIGN="right" COLSPAN="' . $colSpan . '">';
	$h[] = $this->getFormHeader('search_form');
	$h[] = $this->getFormBreadCrumb();
	// existing searches...
	for($next=0; $next < $this->searches; $next++) {
		$h[] = '<SELECT NAME="' . WA_QS_SEARCH_FIELDS . '[' . $next . ']">';
		$def = $this->getSearchField($next);
		$h[] = $this->getFormSelectSearchProperty($fo,$def);
		$h[] = '</SELECT>';
		$h[] = '<SELECT NAME="' . WA_QS_SEARCH_TYPES . '[' . $next . ']">';
		$def = $this->getSearchType($next);
		$h[] = $this->getFormSelectSearchType($def);
		$h[] = '</SELECT>';
		$def = $this->getSearchTerm($next);
		$h[] = '<INPUT TYPE="text" name="' . WA_QS_SEARCH_TERMS . '[' . $next . ']" '
		     . 'LENGTH="30" MAXLENGTH="50" VALUE="' . $def . '" '
		     . 'onChange="document.search_form.submit()">';
		$h[] = $links . '<BR />';
		}
	// next search in line...
	$h[] = '<SELECT NAME="' . WA_QS_SEARCH_FIELDS . '[' . $next . ']">';
	$h[] = $this->getFormSelectSearchProperty($fo);
	$h[] = '</SELECT>';
	$h[] = '<SELECT NAME="' . WA_QS_SEARCH_TYPES . '[' . $next . ']">';
	$h[] = $this->getFormSelectSearchType();
	$h[] = '</SELECT>';
	$h[] = '<INPUT TYPE="text" name="' . WA_QS_SEARCH_TERMS . '[' . $next . ']" '
	     . 'LENGTH="30" MAXLENGTH="50" VALUE="" '
	     . 'onChange="document.search_form.submit()">';
	$h[] = $links;
	$h[] = $this->getFormFooter();
	$h[] = '</TD>';
	$h[] = '</TR>';
	return implode("\n",$h) . "\n";
	}

/**
 * Get form selection widget to select a letter from the alphabet
 * @param string $selectName (required)
 * @param string $default (optional)
 * @return string html selection widget
 */
public function getFormSelectLetter($selectName=null,$default=null) {
	if (empty($selectName)) { return "\n"; }
	$letters = explode(' ','A B C D E F G H I J K L M N O P Q R S T U V W X Y Z');
	$h   = array('<SELECT NAME="' . $selectName . '">');
	foreach($letters as $val) {
		$sel = ($val == $default) ? ' selected' : '';
		$h[] = '<OPTION VALUE="' . $val . '"' . $sel . '>' . $val . '</OPTION>';
		}
	$h[] = '</SELECT>';
	return implode("\n",$h) . "\n";
	}

/**
 * Get javascript error alert with message.
 * @param string $msg
 * @return string javascript alert message
 */
public function getJsErrorAlert($msg=null) {
	if (!is_string($msg)) { $msg = 'Unspecified Error'; }
	$h   = array('<SCRIPT TYPE="text/javascript">');
	$h[] = 'alert("' . addslashes($msg) . '");';
	$h[] = '</SCRIPT>';
	return implode("\n",$h) . "\n";
	}

/**
 * Get javascript to redirect top document to specified url
 * @return string javascript code
 */
public function getJsReloadTop($uri=null) {
	if (!is_string($uri)) {
		$m = 'Cannot redirect to invalid or empty URI.';
		die($this->getJsErrorAlert($m));
		}
	$h   = array('<SCRIPT TYPE="text/javascript">');
	$h[] = 'top.location = "' . urldecode($uri) . '";';
	$h[] = '</SCRIPT>';
	return implode("\n",$h) . "\n";
	}

/**
 * Get paging index that allows scrolling through pages of list results
 * Returns html row containing clickable page number links to navigate
 * through specified phpdboform search results.
 * @param object $form - phpdboform object
 * @return string html
 */
public function getListPager(&$form=null) {
	// sanity checks
	if (!is_a($form,'phpdboform'))         { return "\n"; }
	// need _propertiesList to determine the colspan
	if (!is_array($form->_propertiesList)) { return "\n"; }
	// required info
	$colSpan     = count($form->_propertiesList);
	$hitsCurrent = $form->hitsCurrent;
	$hitsMax     = $form->hitsMax;
	$hitsTotal   = $form->hitsTotal;
	$pageCurrent = $form->pageCurrent;
	$pageLast    = $form->pageLast;
	$pageNext    = $form->pageNext;
	$pagePrev    = $form->pagePrevious;
	if ($colSpan < 1)              { return "\n"; }
	if (!is_numeric($hitsCurrent)) { return "\n"; }
	if (!is_numeric($hitsMax))     { return "\n"; }
	if (!is_numeric($hitsTotal))   { return "\n"; }
	if (!is_numeric($pageCurrent)) { return "\n"; }
	if (!is_numeric($pageLast))    { return "\n"; }
	if (!is_numeric($pageNext))    { return "\n"; }
	if (!is_numeric($pagePrev))    { return "\n"; }
	// initialize the breadcrumb, excluding page
	$c = $this->getBreadCrumbs(array(WA_QS_PAGE));
	if (!is_array($c) or count($c) < 1)    { return "\n"; }
	$crumbs = array();
	foreach($c as $var => $val) {
		$crumbs[] = urlencode($var) . '=' . urlencode($val);
		}
	// generate base query string
	$base_qs = './?' . implode('&',$crumbs) . '&' . WA_QS_PAGE . '=';
	// generate output
	$h   = array('<!-- begin getListPager() output -->');
	$h[] = '<TR>';
	$h[] = '<TD COLSPAN="' . $colSpan . '" ALIGN="right">';
	// generate totals descriptive text
	if ($pageLast < 2) {
		$totals_txt = "Page 1 of 1 ($hitsTotal hits)";
		} else {
		$totals_txt = "Page $pageCurrent of $pageLast ($hitsTotal hits)&nbsp;&nbsp;";
		}
	// text containing clickable links
	$links_txt = '';
	// we have less than two pages of results
	if ($pageLast > 1) {
		// first page link
		if ($pageCurrent > 1) {
			$qs = $base_qs . '1';
			$href = '<A HREF="' . $qs . '">&lt;&lt;</A>&nbsp;&nbsp;';
			} else {
			$href = '&lt;&lt;&nbsp;&nbsp;';
			}
		$links_txt .= $href;
		// previous page link
		if ($pagePrev > 0) {
			$qs = $base_qs . $pagePrev;
			$href = '<A HREF="' . $qs . '">&lt;</A>&nbsp;&nbsp;';
			} else {
			$href  = '&lt;&nbsp;';
			}
		$links_txt .= $href;
		// next page link
		if ($pageCurrent < $pageLast) {
			$qs = $base_qs . $pageNext;
			$nextHref = '<A HREF="' . $qs . '">&gt;</A>&nbsp;';
			} else {
			$nextHref = '&gt;&nbsp;';
			}
		// last page link
		if ($pageCurrent < $pageLast) {
			$qs = $base_qs . $pageLast;
			$lastHref = '&nbsp;<A HREF="' . $qs . '">&gt;&gt;</A>&nbsp;';
			} else {
			$lastHref = '&nbsp;&gt;&gt;&nbsp;';
			}
		// don't list out hundreds of pages, max jump back or forward to 10
		$max_back = $pageCurrent - 10;
		$max_forw = $pageCurrent + 10;
		if ($max_back < 1) {
			$diff     = abs($max_back) + 1;
			$max_back = ($max_back + $diff);
			$max_forw = $max_forw + $diff;
			}
		if (($max_forw > $pageLast) and ($pageLast > 10)) {
			$diff     = $pageLast - $max_forw;
			$max_back = $max_back + $diff;
			$max_forw = $pageLast;
			}
		if ($max_forw > $pageLast) { $max_forw = $pageLast; }
		if ($max_back < 1) { $max_back = 1; }
		for ($j=$max_back; $j <= $max_forw; $j++ ) {
			$nurl = $base_qs . $j;
			$num_text = $j;
			if ("$j" !== "$pageCurrent") {
				$links_txt .= '<A HREF="' . $nurl . '">' . $num_text . '</A>&nbsp;';
				} else {
				$links_txt .= '<U>' . $num_text . '</U>&nbsp;';
				}
			}
		// next page and last links
		$links_txt .= $nextHref . $lastHref;
		}
	$h[] = $totals_txt . $links_txt;
	$h[] = '</TD>';
	$h[] = '</TR>';
	$h[] = '<!-- end getListPager() output -->';
	return implode("\n",$h) . "\n";
	}

/**
 * Get sortable list header table row
 * Returns html table row with headers that are clickable in order to sort
 * a phpdboform list.  Each clickable link will include the breadcrumb.
 * @param object $form - phpdboform object
 * @return string html
 */
public function getListSortableHeader(&$form=null) {
	// sanity checks
	if (!is_a($form,'phpdboform'))         { return "\n"; }
	if (!is_array($form->_propertiesList)) { return "\n"; }
	if (count($form->_propertiesList) < 1) { return "\n"; }
	// initialize the breadcrumb, excluding sort, order, and page
	$c = $this->getBreadCrumbs(array(WA_QS_SORT,WA_QS_ORDER,WA_QS_PAGE));
	if (!is_array($c) or count($c) < 1)    { return "\n"; }
	$crumbs = array();
	foreach($c as $var => $val) {
		$crumbs[] = urlencode($var) . '=' . urlencode($val);
		}
	// what is the current order/sort?
	$cur_o = $this->crumbGet(WA_QS_ORDER);
	$cur_s = $this->crumbGet(WA_QS_SORT);
	// generate the html
	$h  = array('<TR>');
	foreach($form->_propertiesList as $num => $prop) {
		$qs = './?' . implode('&',$crumbs) . '&' . WA_QS_ORDER . '=' . $num;
		// only set extra direction on currently sorted property
		if ($num == $cur_o) {
			if ($cur_s !== 'd') {
				$qs .= '&' . WA_QS_SORT . '=d';
				}
			}
		$ln = '<A HREF="' . $qs . '">'
		    . $form->getPropertyDescription($prop) . '</A>';
		$h[] = '<TH>' . $ln . '</TH>';
		}
	$h[] = '</TR>';
	return implode("\n",$h) . "\n";
	}

/**
 * Get array of links for menus
 * @see addMenuLink()
 * @return mixed
 */
public function getMenuLinks() {
	if (!is_array($this->menuLinks) or count($this->menuLinks) < 1) {
		return false;
		}
	return $this->menuLinks;
	}

/**
 * Retrieve menu query string
 * @param string $menu (required)
 * @return string query string
 */
public function getMenuQs($menu=null) {
	return './?' . WA_QS_MENU . '=' . $menu;
	}

/**
 * Obtain requested menu
 * @return string $menu_request on success
 * @return bool false on failures or no action request
 */
public function getMenuRequest() {
	return $this->crumbGet(WA_QS_MENU);
	}

/**
 * Obtain post data from either an uploaded file or form field.
 * Handles multi-part form submits where data can be submitted either in
 * form fields or uploaded files.  Input derived from uploaded files will
 * take precedence.
 * @param $fileInputName
 * @param $formInputName
 * @return mixed
 */
public function getMultiFormData($fileInputName=null,$formInputName=null) {
	// Try input from uploaded files first.
	if (is_string($fileInputName)) {
		$txt = $this->getUploadedFile($fileInputName,'string');
		if (!($txt === false)) { return $txt; }
		}
	// Try plain jane form field
	if (is_string($formInputName)) {
		return (isset($_POST[$formInputName])) ? $_POST[$formInputName] : false;
		}
	return false;
	}

/**
 * Get error message page
 * @param string error message (optional)
 * @return void
 */
public function getPageError($msg=null) {
	$msg = (empty($msg)) ? 'Unknown Error Encountered' : $msg;
	$this->setVar('msg',$msg);
	$this->setPageTitle('Error');
	$this->loadTemplate('message.php');
	}

/**
 * Get generic message page
 * @param string $msg (optional)
 * @param string $title (optional)
 * @return void
 */
public function getPageMessage($msg=null,$title=null) {
	$msg = (empty($msg)) ? 'Unspecified Message' : $msg;
	$title = (empty($title)) ? 'Unspecified Message' : $title;
	$this->setVar('msg',$msg);
	$this->setPageTitle($title);
	$this->loadTemplate('message.php');
	}

/**
 * Get page footer
 * @param array $footerLinks optional array of links to include in footer
 * @return mixed
 */
public function getPageFooter() {
	$h   = array('</DIV> <!-- contentContainer -->');
	if (is_array($this->getMenuLinks()) and count($this->getMenuLinks()) > 0) {
		$h[] = '<DIV ID="linkButtonsContainer">';
		foreach($this->getMenuLinks() as $lnk) {
			$c = (isset($lnk[2])) ? ' CLASS="' . $lnk[2] . '"' : '';
			$h[] = '<A HREF="' . $lnk[0] . '"' . $c . '>' . $lnk[1] . '</A>';
			}
		$h[] = '</DIV> <!-- linkButtonsContainer -->';
		}
	$h[] = '</DIV> <!-- pageContainer -->';
	$h[] = '</BODY>';
	$h[] = '</HTML>';
	return implode("\n",$h) . "\n";
	}

/**
 * Get page header
 * @param bool $skipBody - if true, will return everything before </HEAD>
 * @return mixed
 */
public function getPageHeader($skipBody=false,$addLinks=false) {
	$h = array();
	$dt = $this->htmlDoctypeGet();
	if (is_string($dt)) { $h[] = $dt; }
	$h[] = '<HTML>';
	$h[] = '<HEAD>';
	// set charset if specified
	$t = $this->htmlCharsetGet();
	if (is_string($t)) {
		$h[] = '<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset='
		. $t . '">';
		}
	// add meta headers if specified
	$t = $this->htmlMetaGet();
	if (is_array($t) and count($t) > 0) {
		foreach($t as $txt) {
			$h[] = $txt;
			}
		}
	// add css includes if specified
	$t = $this->htmlCssGet();
	if (is_array($t) and count($t) > 0) {
		foreach($t as $txt) {
			$h[] = '<LINK REL="StyleSheet" HREF="' . $txt . '" TYPE="text/css" '
			. 'MEDIA="screen,print">';
			}
		}
	// add javascript includes if specified
	$t = $this->htmlJsGet();
	if (is_array($t) and count($t) > 0) {
		foreach($t as $txt) {
			$h[] = '<SCRIPT TYPE="text/javascript" SRC="' . $txt . '"></SCRIPT>';
			}
		}
	// add page title if set
	if (is_string($this->getPageTitle())) {
		$h[] = '<TITLE>' . $this->getPageTitle() . '</TITLE>';
		}
	if ($skipBody === true) {
		return implode("\n",$h) . "\n";
		}
	$h[] = '</HEAD>';
	$h[] = '<BODY>';
	$h[] = '<DIV ID="pageContainer">';
	// add any error messages if present
	if (is_string($this->errorMsgGet())) {
		$h[] = '<DIV ID="errorContainer">' . $this->errorMsgGet() . '</DIV>';
		}
	$h[] = '<DIV ID="titleContainer">';
	if (is_string($this->getPageTitle())) {
		$title = $this->getPageTitle();
		} else {
		$title = WEBAPP_NAME;
		}
	$h[] = '<A HREF="./">' . $title . '</A>';
	$h[] = '</DIV>';
	if ($addLinks === true and is_array($this->getMenuLinks()) and
	    count($this->getMenuLinks()) > 0) {
		$h[] = '<DIV ID="linkButtonsContainer">';
		foreach($this->getMenuLinks() as $lnk) {
			$c = (isset($lnk[2])) ? ' CLASS="' . $lnk[2] . '"' : '';
			$h[] = '<A HREF="' . $lnk[0] . '"' . $c . '>' . $lnk[1] . '</A>';
			}
		$h[] = '</DIV> <!-- linkButtonsContainer -->';
		}
	$h[] = '<DIV ID="contentContainer">';
	return implode("\n",$h) . "\n";
	}

/**
 * Get page title.
 * @return string
 * @see setPageTitle()
 */
public function getPageTitle() {
	return (is_string($this->pageTitle)) ? $this->pageTitle : false;
	}

/**
 * Get specified request variable
 * @param string $varname
 * @param bool $postOnly
 * @return bool false on failures
 * @return mixed request variable
 */
public function getRequestVar($varname=null,$postOnly=false) {
	if (empty($varname) or !is_string($varname)) { return false; }
	if (!is_bool($postOnly)) { return false; }
	// required to be a POST variable
	if ($postOnly === true) {
		if (!isset($_POST)) { return false; }
		if (!is_array($_POST)) { return false; }
		if (!isset($_POST[$varname])) { return false; }
		return trim($_POST[$varname]);
		}
	if (!isset($_REQUEST)) { return false; }
	if (!is_array($_REQUEST)) { return false; }
	if (isset($_REQUEST[$varname])) {
		if (is_array($_REQUEST[$varname])) {
			return $_REQUEST[$varname];
			} else {
			return trim($_REQUEST[$varname]);
			}
		}
	return false;
	}

/**
 * Wrapper function to get search properties
 */
public function getSearchField($num=null) {
	return (isset($this->searchFields[$num])) ? $this->searchFields[$num] : '';
	}
public function getSearchType($num=null) {
	return (isset($this->searchTypes[$num])) ? $this->searchTypes[$num] : '';
	}
public function getSearchTerm($num=null) {
	return (isset($this->searchTerms[$num])) ? $this->searchTerms[$num] : '';
	}

/**
 * Retrieve contents of form uploaded file.
 * @param string $fileName (input type="file" name="xxxx")
 * @param string $returnType array | string | path
 * @return mixed
 */
public function getUploadedFile($fileName=null,$returnType=null) {
	if (!is_string($fileName)) { return false; }
	switch($returnType) {
		case 'array';
		case 'string';
		case 'path';
		break;
		default:
			return false;
		break;
		}
	if (!isset($_FILES)) { return false; }
	if (!isset($_FILES[$fileName])) { return false; }
	// keys required
	$keys = array('name','type','tmp_name','size','error');
	foreach($keys as $key) {
		if (!isset($_FILES[$fileName][$key])) { return false; }
		}
	if ($_FILES[$fileName]['error'] > 0) { return false; }
	if ($_FILES[$fileName]['size'] < 1)  { return false; }
	if ($returnType == 'path') { return $_FILES[$fileName]['tmp_name']; }
	$content = file($_FILES[$fileName]['tmp_name']);
	if (!is_array($content) or count($content) < 1) { return false; }
	if ($returnType == 'array') {
		return $content;
		} else {
		return implode('',$content);
		}
	}

/**
 * Get specified template variable
 * @return mixed
 * @see setVar(), varIsSet()
 */
public function getVar($varname=null) {
	if (!is_array($this->templateVars)) { return false; }
	if (!array_key_exists($varname,$this->templateVars)) { return false; }
	return $this->templateVars[$varname];
	}

/**
 * Get the html charset
 * @return mixed
 * @see htmlCharsetSet()
 */
public function htmlCharsetGet() {
	return (is_string($this->htmlCharset)) ? $this->htmlCharset : false;
	}

/**
 * Set the html charset
 * @param string $txt
 * @return void
 * @see htmlCharsetGet()
 */
public function htmlCharsetSet($txt=null) {
	$this->htmlCharset = $txt;
	}

/**
 * Add url for css includes
 * @param string $uri
 * @return bool
 * @see htmlCssGet(), htmlCssReset()
 */
public function htmlCssAdd($uri=null) {
	if (!is_array($this->htmlCss)) { return false; }
	if (!is_string($uri) or strlen($uri) < 1) { return false; }
	if (in_array($uri,$this->htmlCss)) { return true; }
	$this->htmlCss[] = $uri;
	}

/**
 * Get array of urls for css includes
 * @return array
 */
public function htmlCssGet() {
	return (is_array($this->htmlCss)) ? $this->htmlCss : array();
	}

/**
 * Reset htmlCss to a blank slate
 * @return void
 */
public function htmlCssReset() { $this->htmlCss = array(); }

/**
 * Get the html doctype
 * @return mixed
 */
public function htmlDoctypeGet() {
	return (is_string($this->htmlDoctype)) ? $this->htmlDoctype : false;
	}

/**
 * Set the html doctype
 * @param string $txt
 * @return void
 * @see htmlDoctypeGet()
 */
public function htmlDoctypeSet($txt=null) {
	$this->htmlDoctype = $txt;
	}

/**
 * Add url to array of urls for js includes
 * @param string $uri
 * @return bool
 */
public function htmlJsAdd($uri=null) {
	if (!is_array($this->htmlJs)) { return false; }
	if (!is_string($uri) or strlen($uri) < 1) { return false; }
	if (in_array($uri,$this->htmlJs)) { return true; }
	$this->htmlJs[] = $uri;
	}

/**
 * Get array of urls for js includes
 * @return array
 */
public function htmlJsGet() {
	return (is_array($this->htmlJs)) ? $this->htmlJs : array();
	}

/**
 * Add meta for header includes
 * @param string $txt
 * @return bool
 */
public function htmlMetaAdd($txt=null) {
	if (!is_array($this->htmlMeta)) { return false; }
	if (!is_string($txt) or strlen($txt) < 1) { return false; }
	if (in_array($txt,$this->htmlMeta)) { return true; }
	$this->htmlMeta[] = $txt;
	}

/**
 * Set array of meta for header includes
 * @return array
 */
public function htmlMetaGet() {
	return (is_array($this->htmlMeta)) ? $this->htmlMeta : array();
	}

/**
 * Include specified template
 * @param string $template
 * @return void
 */
public function loadTemplate($template=null) {
	if (!is_file(WEBAPP_TMP . '/' . $template)) {
		die('FATAL ERROR: TEMPLATE IS MIA: ' . $template);
		}
	die(include(WEBAPP_TMP . '/' . $template));
	}

/**
 * Parse PEM formatted certificate block from uploaded form data.
 * Will look for input from either an uploaded file (inputFileName)
 * or form input field (inputFieldName).  Uploaded files will take
 * precedence.  Will handle input with multiple PEM encoded blocks
 * within and return only the PEM encoded certificate.
 * @param string $inputFileName
 * @param string $inputFieldName
 * @return mixed
 */
public function parseCertificate($inputFileName=null,$inputFieldName=null) {
	$txt = $this->getMultiFormData($inputFileName,$inputFieldName);
	if ($txt === false) { return false; }
	return $this->extractPemBlock($txt,'CERTIFICATE');
	}

/**
 * Parse PEM formatted certificate request from uploaded form data.
 * Will look for input from either an uploaded file (inputFileName)
 * or form input field (inputFieldName).  Uploaded files will take
 * precedence.  Will handle input with multiple PEM encoded blocks
 * within and return only the PEM encoded certificate request.
 * @param string $inputFileName
 * @param string $inputFieldName
 * @return mixed
 */
public function parseCertificateRequest($inputFileName=null,$inputFieldName=null) {
	$txt = $this->getMultiFormData($inputFileName,$inputFieldName);
	if ($txt === false) { return false; }
	// can be either NEW CERTIFICATE REQUEST or CERTIFICATE REQUEST
	$csr = $this->extractPemBlock($txt,'NEW CERTIFICATE REQUEST');
	if (is_string($csr)) { return $csr; }
	return $this->extractPemBlock($txt,'CERTIFICATE REQUEST');
	}

/**
 * Parse PEM formatted private key from uploaded form data.
 * Will look for input from either an uploaded file (inputFileName)
 * or form input field (inputFieldName).  Uploaded files will take
 * precedence.  Will handle input with multiple PEM encoded blocks
 * within and return only the PEM encoded certificate.
 * @param string $inputFileName
 * @param string $inputFieldName
 * @return mixed
 */
public function parsePrivateKey($inputFileName=null,$inputFieldName=null) {
	$txt = $this->getMultiFormData($inputFileName,$inputFieldName);
	if ($txt === false) { return false; }
	return $this->extractPemBlock($txt,'RSA PRIVATE KEY');
	}

/**
 * Parse PEM formatted public key from uploaded form data.
 * Will look for input from either an uploaded file (inputFileName)
 * or form input field (inputFieldName).  Uploaded files will take
 * precedence.  Will handle input with multiple PEM encoded blocks
 * within and return only the PEM encoded certificate.
 * @param string $inputFileName
 * @param string $inputFieldName
 * @return mixed
 */
public function parsePublicKey($inputFileName=null,$inputFieldName=null) {
	$txt = $this->getMultiFormData($inputFileName,$inputFieldName);
	if ($txt === false) { return false; }
	return $this->extractPemBlock($txt,'PUBLIC KEY');
	}

/**
 * Set title of page.
 * @param string $txt
 * @see getPageTitle()
 * @return void
 */
public function setPageTitle($txt=null) {
	if (!empty($txt)) { $this->pageTitle = $txt; }
	}

/**
 * Set specified template variable
 * @return bool
 * @see getVar(), varIsSet(), showVar()
 */
public function setVar($varname=null,$data=false) {
	if (!is_array($this->templateVars)) { return false; }
	if (empty($varname)) { return false; }
	$this->templateVars[$varname] =& $data;
	return true;
	}

/**
 * Echo specified template var to the screen
 * @param string $varname
 * @return void
 */
public function showVar($varname) { echo $this->getVar($varname); }

/**
 * Is specified var name set and not empty?
 * @param string $varname
 * @return bool
 */
public function varIsSet($varname=null) {
	if (empty($varname)) { return false; }
	$data = $this->getVar($varname);
	if ($data === false) { return false; }
	if (is_string($data) and strlen($data) > 0) { return true; }
	if (is_array($data) and count($data) > 0) { return true; }
	if (is_object($data)) { return true; }
	return false;
	}

/**
 * Get form element to select signing ca
 * @param string $selectName
 * @param string $default
 * @return string html selection widget
 */
public function getFormSelectCa($selectName=null,$default=null) {
	global $_WA;
	if (empty($selectName)) { return "\n"; }
    $_WA->moduleRequired('ca');
	$_WA->ca->searchReset();
	$_WA->ca->setSearchSelect('Id');
	$_WA->ca->setSearchSelect('CommonName');
	$_WA->ca->setSearchSelect('OrgName');
	$_WA->ca->setSearchSelect('OrgUnitName');
	$_WA->ca->setSearchOrder('OrgName');
	$_WA->ca->setSearchOrder('OrgUnitName');
	// Can't use 3rd party CA's to sign with ;)
	$f_pk = $_WA->ca->getPropertyField('PrivateKey');
	$f_vt = $_WA->ca->getPropertyField('ValidTo');
	$_WA->ca->setSearchFilterClause($f_pk . ' is not null');
	$_WA->ca->setSearchFilterClause('date(now()) <= ' . $f_vt);
	$q = $_WA->ca->query();
	if (!is_array($q) or count($q) < 1) { return "\n"; }
	// If default is "self", hack in an entry for it ;)
	if ($default == 'self') {
		$ar = array();
		$ar['Id'] = 'self';
		$ar['CommonName'] = 'Self Signed';
		$ar['OrgName'] = 'Self Signed';
		$ar['OrgUnitName'] = 'Self Signed';
		$q[] = $ar;
		}
	$h = array();
	$h[] = '<SELECT NAME="' . $selectName . '">';
	foreach($q as $row) {
		$id = $row['Id'];
		$txt = array();
		$cn  = $row['CommonName'];
		$on  = $row['OrgName'];
		$ou  = $row['OrgUnitName'];
		$s   = ($default == $id) ? ' selected' : '';
		if ($cn) { $txt[] = $cn; }
		if ($on) { $txt[] = $on; }
		if ($ou) { $txt[] = $ou; }
		$txt = implode(' :: ',$txt);
		$h[] = '<OPTION VALUE="' . $id . '"' . $s . '>' . $txt . '</OPTION>';
		}
	$h[] = '</SELECT>';
	return implode("\n",$h) . "\n";
	}

/**
 * Get search properties selection options.
 * @param object phpdboform object
 * @param string default search property (optional)
 * @return string html select options
 */
private function getFormSelectSearchProperty(&$fo,$default=false) {
	if (!is_a($fo,'phpdboform')) { return "\n"; }
	if (!$fo->isProperty($default)) {
		$default = $fo->getSearchPropertyDefault();
		}
	$props = $fo->getSearchProperties();
	if (!is_array($props) or count($props) < 1) { return "\n"; }
	$h = array();
	foreach($props as $prop) {
		if (!$fo->isProperty($prop)) { continue; }
		$sel = ($prop == $default) ? ' selected' : '';
		$h[] = '<OPTION VALUE="' . $prop . '"' . $sel . '>'
		     . $fo->getPropertyDescription($prop) . '</OPTION>';
		}
	return implode("\n",$h) . "\n";
	}

/**
 * Get search type selection options.
 * @param string default search type (optional)
 * @return string html select options
 */
private function getFormSelectSearchType($default='equals') {
	$search_types =
	array('equals','contains','begins with','ends with',
	      'less than','greater than','does not equal',
	      'does not contain','does not begin with',
	      'does not end with');
	$h = array();
	foreach($search_types as $type) {
		$sel = ($default == $type) ? ' selected' : '';
		$h[] = '<OPTION VALUE="' . $type . '"' . $sel . '>' . $type . '</OPTION>';
		}
	return implode("\n",$h) . "\n";
	}

/**
 * Get form element to select from new or existing server certificates
 * @param string $selectName
 * @param string $default
 * @return string html selection widget
 */
private function getFormSelectServerId($selectName=null,$default=null) {
	global $_WA;
	if (empty($selectName)) { return "\n"; }
	if (empty($default) or $default == '') { $default = 'New'; }
    $_WA->moduleRequired('server');
	$_WA->server->searchReset();
	$_WA->server->setSearchSelect('Id');
	$_WA->server->setSearchSelect('CommonName');
	$q = $_WA->server->query();
	if (!is_array($q) or count($q) < 1) { return "\n"; }
	// prepend an option for "new"
	$junk = array('Id' => 'New','CommonName' => 'New');
	array_unshift($q,$junk);
	$h = array();
	$h[] = '<SELECT NAME="' . $selectName . '">';
	foreach($q as $row) {
		$id = $row['Id'];
		$s   = ($default == $id) ? ' selected' : '';
		$txt = $row['CommonName'];
		$h[] = '<OPTION VALUE="' . $id . '"' . $s . '>' . $txt . '</OPTION>';
		}
	$h[] = '</SELECT>';
	return implode("\n",$h) . "\n";
	}

/**
 * Get query string with existing search terms stripped off
 * @return string querystring
 */
private function getQsStripSearch() {
	$x = array(WA_QS_SEARCH_FIELDS,WA_QS_SEARCH_TERMS,WA_QS_SEARCH_TYPES,
	WA_QS_PAGE,WA_QS_ORDER,WA_QS_SORT);
	$tmp_ar = array();
	foreach($this->getBreadCrumbs($x) as $crumb => $data) {
		$tmp_ar[] = $crumb . '=' . urlencode($data);
		}
	return './?' . implode('&',$tmp_ar);
	}

/**
 * Parse request variables and plug them into their proper places
 * @return bool
 */
private function parseRequestVariables() {
	if (!isset($_REQUEST))    { return false; }
	if (!is_array($_REQUEST)) { return false; }
	if (count($_REQUEST) < 1) { return false; }
	// validate required defines
	$str = $this->getRequestVar(WA_QS_ACTION);
	if (ctype_alnum($str) and strlen($str) < 21) {
		$this->actionRequest = $str;
		}
	return true;
	}

/**
 * Set breadcrumb trail
 * @return bool
 */
private function setBreadCrumbs() {
	if (!is_array($this->breadCrumbs)) { return false; }
	if (count($this->breadCrumbs) < 1) { return true;  }
	foreach($this->breadCrumbs as $crumb => $data) {
		$this->breadCrumbs[$crumb] = $this->getRequestVar($crumb);
		}
	// glean searches
	$searches = 0;
	// prune out empty entries...
	$fields = array(); $types = array(); $terms = array();
	$ar = $this->crumbGet(WA_QS_SEARCH_FIELDS);
	if (is_array($ar) and count($ar) > 0) {
		foreach($ar as $key => $data) {
			if ($data === '') { continue; }
			$fields[$key] = $data;
			}
		}
	$ar = $this->crumbGet(WA_QS_SEARCH_TYPES);
	if (is_array($ar) and count($ar) > 0) {
		foreach($ar as $key => $data) {
			if ($data === '') { continue; }
			$types[$key] = $data;
			}
		}
	$ar = $this->crumbGet(WA_QS_SEARCH_TERMS);
	if (is_array($ar) and count($ar) > 0) {
		foreach($ar as $key => $data) {
			if ($data === '') { continue; }
			$terms[$key] = $data;
			}
		}
	$t1 = (count($fields) !== count($types));
	$t2 = (count($fields) !== count($terms));
	if ($t1 or $t2) {
		$fields = array(); $types = array(); $terms = array();
		}
	$this->searchFields = $fields;
	$this->searchTerms  = $terms;
	$this->searchTypes  = $types;
	$this->searches     = count($fields);
	return true;
	}

}

?>
