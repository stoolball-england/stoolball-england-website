var relatedItemEditor = 
{
	/**
	 * Divider for field values
	 */
	Divider : '###',
	
	Init : function()
	{
		if (document.getElementsByTagName)
		{
			var divs = document.getElementsByTagName('div');
			var editors = new Array();
			var totalDivs = divs.length;
			var editorTest = new RegExp(/\brelatedItemEditor\b/);
			for (var i = 0; i < divs.length; i++)
			{
				// identify related item editors
				if (editorTest.test(divs[i].className))
				{
					editors[editors.length] = divs[i];
				}
			}
			
			// for each editor...
			var totalEditors = editors.length;
			for (var i = 0; i < totalEditors; i++)
			{
				// add explanation for accessibility
				var para = document.createElement('p');
				para.appendChild(document.createTextNode('In the following table, buttons labelled \'Add\' or \'Delete\' update the table without refreshing the page.'));
				para.className = 'aural';
				editors[i].insertBefore(para, editors[i].firstChild);
				
				// get inputs
				var inputs = editors[i].getElementsByTagName('input');
				var totalInputs = inputs.length;
				for (var j = 0; j < totalInputs; j++)
				{
					// identify buttons
					if (inputs[j].getAttribute('type') == 'submit')
					{
						base2.DOM.bind(inputs[j]);
						var buttonValue = inputs[j].getAttribute('value');
						if (buttonValue == 'Delete')
						{
							inputs[j].addEventListener('click', relatedItemEditor.DeleteItem, false);
						}
						else if (buttonValue == 'Add')
						{
							inputs[j].addEventListener('click', relatedItemEditor.AddItem, false);
						}
					}
				}
			}
		}		
	},
	
	DeleteItem : function(e)
	{
		// Get the context of the deleted row
		var tableRow = e.target.parentNode.parentNode;
		var addRow = tableRow.parentNode.lastChild;
		var addButton = addRow.lastChild.firstChild;
		var addRowEnabled = false;
		addButton.setAttribute('disabled', 'disabled'); // don't click add while deleting

		// Delete the table row
		tableRow = tableRow.parentNode.removeChild(tableRow);
		
		// Check addRow for unique dropdowns and restore values
		var len = addRow.childNodes.length;
		var selects;
		var lenj;
		for (var i = 0; i < len; i++)
		{
			selects = addRow.childNodes[i].getElementsByTagName('select');
			lenj = selects.length;
			for (var j = 0; j < lenj; j++)
			{
				base2.DOM.bind(selects[j]);
				if (selects[j].hasClass('unique'))
				{
					relatedItemEditor.RestoreDropdownValue(tableRow, i, selects[j]);
					if (!addRowEnabled) 
					{
						base2.DOM.bind(addRow);
						relatedItemEditor.EnableRow(addRow, true);
						addRowEnabled = true;
					}
				}
			}
		}
		
		// Cancel the click event
		e.preventDefault();
		
		// Restore add button
		addButton.removeAttribute('disabled');
	},
	
	/**
	 * When a unique value is deleted, add it back to the choices
	 */
	RestoreDropdownValue : function(tableRow, cellIndex, dropdown)
	{
		var data = tableRow.childNodes[cellIndex].firstChild;
		if (data && data.nodeType == 1 && data.tagName == 'INPUT')
		{
			var value = data.getAttribute('value');
			if (value.length > 0)
			{
				var pos = value.indexOf(relatedItemEditor.Divider);
				if (pos)
				{
					var displayName = value.substr(pos+relatedItemEditor.Divider.length);
					
					var opt = document.createElement('option');
					opt.setAttribute('value', value);
					opt.appendChild(document.createTextNode(displayName));
	
					// List should be sorted alphabetically, insert at right place
					var options = dropdown.childNodes.length;
					for (var i = 0; i < options; i++)
					{
						var optText = dropdown.childNodes[i].firstChild;
						if (optText == null || optText.nodeValue < displayName) continue;
						dropdown.insertBefore(opt, dropdown.childNodes[i]);
						break;
					}
					if (opt.parentNode == null) dropdown.appendChild(opt);
				}
			}
		}
	},
	
	AddItem : function(e)
	{
		// Get the context of the row being added
		var buttonId = e.target.getAttribute('id');
		var prefix = buttonId.substr(0, buttonId.indexOf('AddRelated'));
		var addRow = e.target.parentNode.parentNode;
		base2.DOM.bind(addRow);
		var copyRow = addRow.cloneNode(true);
		base2.DOM.bind(copyRow);
		var headerRow = addRow.parentNode.parentNode.getElementsByTagName('tr')[0];
		var validMessage = '';
		var resetDropdowns = new Array();
		var removeOptions = new Array();
		
		// Work out next-highest unused increment
		var internal = document.getElementById(prefix + 'InternalButtons');
		var increment = 1;
		if (internal)
		{
			var buttonIds = internal.getAttribute('value').split('; ');
			var buttons = buttonIds.length;
			var deletePrefix = prefix + 'DeleteRelated';
			var prefixLen = deletePrefix.length;
			for (var i = 0; i < buttons; i++)
			{
				if (buttonIds[i].substr(0,prefixLen) == deletePrefix)
				{
					var value = parseInt(buttonIds[i].substr(prefixLen));
					if (value >= increment) increment = value+1;
				}
			}
		}
		
		// Process cols in order
		var cols = addRow.childNodes;
		var lenCols = cols.length;
		var lenNodes;
		var current;
		var copyElem;
		var numeric = /^[0-9]+$/;

		for (var col = 0; col < lenCols; col++)
		{
			// Process elements in cell
			lenNodes = cols[col].childNodes.length;
			for (var node = 0; node < lenNodes; node++)
			{
				current = cols[col].childNodes[node];
				if (current.nodeType == 1)
				{
					switch (current.tagName)
					{
						case 'INPUT':

							copyElem = copyRow.childNodes[col].childNodes[node];
							switch (current.getAttribute('type'))
							{
								case 'text':
									
									// Check for validity
									base2.DOM.bind(current);
									if (current.hasClass('required') && current.value == '')
									{
										validMessage += headerRow.childNodes[col].firstChild.nodeValue + ' is required\n';
									}
									else if (current.hasClass('numeric') && !numeric.exec(current.value))
									{
										validMessage += headerRow.childNodes[col].firstChild.nodeValue + ' should be a number. For example, \'2\', not \'two\'\n';
									}
									
									copyElem.setAttribute('id', current.getAttribute('id') + increment);
									copyElem.setAttribute('name', current.getAttribute('id'));
									
									break;
									
								case 'submit':
									
									// Change the button
									copyElem.setAttribute('value', 'Delete');
									var newId = prefix + 'DeleteRelated' + increment;
									copyElem.setAttribute('id', newId);
									copyElem.setAttribute('name', newId);
									copyElem.removeEventListener('click', relatedItemEditor.AddItem, false);
									copyElem.addEventListener('click', relatedItemEditor.DeleteItem, false);
									
									// Register new internal button
									if (internal) internal.setAttribute('value', internal.getAttribute('value') + '; ' + newId);
									internal = document.getElementById('InternalButtons');
									if (internal) internal.setAttribute('value', internal.getAttribute('value') + '; ' + newId);
									
									// Create an id field
									var id = document.createElement('input');
									id.setAttribute('type', 'hidden');
									id.setAttribute('id', prefix + 'RelatedId' + increment);
									id.setAttribute('name', id.getAttribute('id'));
									id.setAttribute('value', increment);
									copyElem.parentNode.appendChild(id);
									
									break;
							}

						break;

						case 'SELECT':
	
							// check for validity
							base2.DOM.bind(current);
							if (current.hasClass('required') && current.childNodes[current.selectedIndex].firstChild == null)
							{
								validMessage += headerRow.childNodes[col].firstChild.nodeValue + ' is required\n';
							}
										
							// update new dropdown
							copyElem = copyRow.childNodes[col].childNodes[node]
							copyElem.childNodes[current.selectedIndex].selected = true;
							copyElem.setAttribute('id', copyElem.getAttribute('id') + increment);
							copyElem.setAttribute('name', copyElem.getAttribute('id'));
							
							if (current.hasClass('unique'))
							{
								removeOptions[removeOptions.length] = current.childNodes[current.selectedIndex];
								if (current.childNodes.length == 2 && lenCols == 2)
								{
									// Assuming blank row, no options will remain
									relatedItemEditor.EnableRow(addRow, false);
								}
							}
							
							// Clear add row dropdown
							resetDropdowns[resetDropdowns.length] = current;
				
						break;
					}
				}
			}
		}

		// If invalid, drop out now
		if (validMessage.length)
		{
			alert('To add a row, please check the following:\n\n' + validMessage);
			e.preventDefault();
			relatedItemEditor.EnableRow(addRow, true);
			return;
		}
		
		// Clear text options (copied automatically) 
		inputs = addRow.getElementsByTagName('input');
		len = inputs.length;
		for (var i = 0; i < len; i++)
		{
			switch (inputs[i].getAttribute('type'))
			{
				case 'text':
					inputs[i].value = '';
					break;
			}
		}
		
		//... and reset dropdowns
		len = resetDropdowns.length;
		for (var i = 0; i < len; i++) resetDropdowns[i].childNodes[0].selected = true;

		len = removeOptions.length;
		for (var i = 0; i < len; i++) removeOptions[i].parentNode.removeChild(removeOptions[i]);
		
		// if display only, convert dropdowns
		if (copyRow.hasClass('display'))
		{
			relatedItemEditor.ConvertToDisplayRow(copyRow, addRow.parentNode);
		}		

		// add row, if not done by ConvertToDisplayRow
		if (copyRow.parentNode == null) addRow.parentNode.insertBefore(copyRow, addRow);
		
		if (!relatedItemEditor.FocusRow(addRow))
		{
			relatedItemEditor.FocusRow(addRow.previousSibling);
		}

		// Cancel the click event
		e.preventDefault();
	},
	
	/**
	 * Enable or disable a table row with form controls
	 * @param HtmlRow row
	 * @param bool enable
	 */
	EnableRow : function(row, enable)
	{
		if (enable)
		{
			row.removeClass('unavailable');
		}
		else
		{
			row.addClass('unavailable');
		}
		
		var cols = row.childNodes.length;
		var node;
		var nodes;
		for (var i = 0; i < cols; i++)
		{
			nodes = row.childNodes[i].childNodes.length;
			for (var j = 0; j < nodes; j++)
			{
				node = row.childNodes[i].childNodes[j];
				if (node.nodeType == 1)
				{
					if (node.tagName == 'INPUT' || node.tagName == 'SELECT')
					{
						if (enable)
						{
							node.removeAttribute('disabled');
						}
						else
						{
							node.setAttribute('disabled', 'disabled');
						}
					}
				}
			}
		}
		
	},
	
	/**
	 * Reset focus to first focusable element in row
	 * @param HtmlRow row
	 * @return bool
	 */
	FocusRow : function(row)
	{
		var len = row.childNodes.length;
		var focused = false;
		for (var i = 0; i < len; i++)
		{
			var childLen = row.childNodes[i].childNodes.length;
			for (var j = 0; j < childLen; j++)
			{
				if (row.childNodes[i].childNodes[j].focus && row.childNodes[i].childNodes[j].getAttribute('disabled') == null) 
				{
					row.childNodes[i].childNodes[j].focus();	
					focused = true;
					break;
				}
			}
			if (focused) break;
		}
		return focused;
	},
	
	/**
	 * Convert an editable row to a display-only row
	 * @param HtmlRow row
	 * @param HtmlTbody
	 */
	ConvertToDisplayRow : function(row, rowGroup)
	{
		var cols = row.childNodes.length;
		var node;
		var nodes;
		var text;
		for (var i = 0; i < cols; i++)
		{
			nodes = row.childNodes[i].childNodes.length;
			for (var j = 0; j < nodes; j++)
			{
				node = row.childNodes[i].childNodes[j];
				if (node.nodeType == 1)
				{
					if (node.tagName == 'SELECT')
					{
						node.parentNode.removeChild(node);
						
						var hidden = document.createElement('input');
						hidden.setAttribute('type', 'hidden');
						hidden.setAttribute('id', node.getAttribute('id'));
						hidden.setAttribute('name', node.getAttribute('name'));
						
						// Get text node from dropdown
						text = node.childNodes[node.selectedIndex].removeChild(node.childNodes[node.selectedIndex].firstChild);
						var text2 = text.cloneNode(false);

						var value = node.childNodes[node.selectedIndex].getAttribute('value');
						if (value.indexOf(relatedItemEditor.Divider) > -1)
						{
							hidden.setAttribute('value', value);
						}
						else
						{
							hidden.setAttribute('value', value + relatedItemEditor.Divider + text.nodeValue);
						}
						
						row.childNodes[i].appendChild(hidden);
						row.childNodes[i].appendChild(text2);
					}
				}
			}
		}
		
		// Sort the row alphabetically
		if (text != null)
		{
			nodes = rowGroup.childNodes.length;
			
			for (var i = 0; i < nodes; i++)
			{
				var rowText = rowGroup.childNodes[i].firstChild.lastChild.nodeValue;
				if (rowText < text.nodeValue) continue;
				rowGroup.insertBefore(row, rowGroup.childNodes[i]);
				break;
			}
			if (row.parentNode == null) rowGroup.appendChild(row);
		}
					
	}
}

base2.DOM.bind(document);
document.addEventListener('DOMContentLoaded', relatedItemEditor.Init, false);