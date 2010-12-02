/**
 * @package    phpmyca
 * @author     Mike Green <mdgreen@gmail.com>
 * @copyright  Copyright (c) 2010, Mike Green
 * @license    http://opensource.org/licenses/gpl-2.0.php GPLv2
 */
/**
 * Toggle the showing of a category
 * @param string elementId
 * @return void
 */
function toggleDisplay(elemId) {
	var o = document.getElementById(elemId);
	var curDisplay = 'none';
	var newDisplay;
	if (o) {
		newDisplay = (o.style.display == 'block') ? 'none' : 'block';
		o.style.display = newDisplay;
		}
	}

/**
 * Clear all but hidden form fields
 * @param object formObj
 * @return void
 */
function clearForm(formObj) {
	if (typeof(formObj) !== 'object') { return; }
	var formElements = formObj.elements;
	var i, fieldType;
	if (formElements.length < 1) { return; }
	for (var i = 0; i < formElements.length; i++) {
		switch(formElements[i].type.toLowerCase()) {
			case 'file':
			case 'text':
			case 'textarea':
			case 'password':
				formElements[i].value = "";
			break;
			case 'select-one':
			case 'select-multi':
				formElements[i].selectedIndex = -1;
			break;
			}
		}
	}