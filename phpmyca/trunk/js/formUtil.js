/**
 * Currently selected CA ID
 */
var caId = null;

/**
 * Take message passing actions when a CA ID is selected.
 *
 * When the CA ID is selected while creating a client or server certificate,
 * a url will be loaded into the message passing frame.  The called url will
 * generate the javascript necessary to auto-fill the new certificate's form
 * fields with information from the selected CA.
 *
 * @param elem
 *   The form element containing the CA id.
 * @param url
 *   Url to load into the message passing frame.
 * @return bool
 */
function caSelected(elem,url) {
	if (!elem.name || elem.name !== 'caId') { return false; }
	if (!isString(url)) { return false; }
	if (!elem.value) { return false; }
	if (elem.value == caId) { return true; }
	if (!messageFrameExists()) { return false; }
	// set current caId
	caId = elem.value;
	url += caId;
	// if it is self, just clear the form
	if (caId == 'self') {
		// get the current index so we can switch it back...
		var curIndex = elem.selectedIndex;
		clearForm(document.addcert);
		elem.selectedIndex = curIndex;
		return true;
		}
	top.frames.messages.location = url;
	return true;
	}

/**
 * Is provided argument a string?
 * @param mixed a
 * @return bool
 */
function isString(a) {
	return (typeof a == 'string');
	}

/**
 * Does the message passing iframe exist?
 * @return bool
 */
function messageFrameExists() {
	if (top.frames.length < 1) { return false; }
	return (!top.frames.messages) ? false : true;
	}

/**
 * Populate specified form field with specified value.
 * @param varName
 * @param varVal
 * @return bool
 */
function populateField(varName,varVal) {
	if (typeof(varName) !== 'string') { alert('1'); return false; }
	if (typeof(varVal) !== 'string') { alert('2'); return false; }
	var el = document.getElementsByName(varName)[0];
	if (!el) { return false; }
	el.value = varVal;
	return true;
	}
