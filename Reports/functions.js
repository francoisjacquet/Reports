/**
 * Reports Javascript functions
 *
 * @package Reports
 * @subpackage JS
 */

var InsertFunction = true,
	InsertOperator = false,
	InsertField = false,
	InsertConstant = false;
var i = 0,
	SearchItemCount = 0,
	SearchScreenCount = 0;

function insertItem( title, type )
{
	switch( type )
	{
		case 'function':
			if ( InsertFunction )
			{
				i++;
				reg = new RegExp("<img src=[^>]+>");
				document.getElementById('equation_div').innerHTML = document.getElementById('equation_div').innerHTML.toLowerCase().replace(reg,'<span id="start'+i+'"></span>'+title + '<b>(</b> <img src="modules/Reports/assets/blinking_cursor.gif" /> <b>)</b><span id="end'+i+'"></span>');
				InsertFunction = false;
				InsertConstant = false;
				InsertField = true;
				document.getElementById('status_div').innerHTML = document.getElementById('status_choose_field').innerHTML;
				InFunction = true;
			}
			else
			{
				if ( InsertField )
					document.getElementById('status_div').innerHTML = document.getElementById('status_error_function_field').innerHTML;
				else if ( InsertOperator )
					document.getElementById('status_div').innerHTML = document.getElementById('status_error_function_operator').innerHTML;
			}
		break;

		case 'field':
			if ( InsertField || ( InsertConstant && title.length == 1 ) )
			{
				i++;
				reg = new RegExp("<img src=[^>]+>");
				document.getElementById('equation_div').innerHTML = document.getElementById('equation_div').innerHTML.toLowerCase().replace(reg,'<span id="start'+i+'"></span>'+title + '<span id="end'+i+'"></span> <img src="modules/Reports/assets/blinking_cursor.gif" /> ');

				if ( title.length == 1 )
				{
					document.getElementById('status_div').innerHTML = document.getElementById('status_choose_operator').innerHTML;
					InsertFunction = false;
					InsertConstant = true;
					InsertOperator = true;
					InsertField = false;
				}
				else
				{
					document.getElementById('status_div').innerHTML = document.getElementById('status_choose_operator_or_constant').innerHTML;
					InsertField = false;
					InsertOperator = true;
					if (InFunction)
						insertItem(')','operator');
					SearchItemCount = 0;
					if ( SearchScreenCount > 0 )
					{
						formname = 'searchform'+SearchScreenCount;
						elems = document.forms[formname].elements;
						for(elemindex = 0;elemindex<elems.length;elemindex++)
						{
							elem = document.forms[formname].elements[elemindex];
							if (elem.name!='itemname')
							{
								if (elem.value)
									document.getElementById('hidden_permanent_search_contents').innerHTML = document.getElementById('hidden_permanent_search_contents').innerHTML + '<input type="hidden" name="screen['+SearchScreenCount+']['+elem.getAttribute('name')+']" value="'+elem.value+'" />';
								else if (elem.options)
									document.getElementById('hidden_permanent_search_contents').innerHTML = document.getElementById('hidden_permanent_search_contents').innerHTML + '<input type="hidden" name="screen['+SearchScreenCount+']['+elem.getAttribute('name')+']" value="'+elem.options[elem.selectedIndex].value+'" />';
							}
						}
					}
					SearchScreenCount++;
					// PLACE SEARCH SCREEN
					document.getElementById("search_screen").style.top = getYPos('end'+(i-1)) + 20;
					document.getElementById("search_screen").style.left = getXPos('start'+(i-1)) + (getXPos('end'+(i-1)) - getXPos('start'+(i-1)))/2;
					document.getElementById('search_contents').innerHTML = replaceAll(document.getElementById('hidden_search_contents').innerHTML,'div_id','id').replace('_searchform_','searchform'+SearchScreenCount);
					if ( title=='Present' || title=='Absent' || title=='Enrolled' || title.substring( 0, 8 ) == 'Orchard:' )
					{
						SearchItemCount++;
						document.getElementById('search_contents'+SearchItemCount).innerHTML = '<div id="search_item'+SearchItemCount+'">' + document.getElementById('hidden_search_inputtimespan').innerHTML.replace('div_id="_id_"','id="item'+SearchItemCount+'"').replace('_id_','item'+SearchItemCount) + '</div>';
					}
					/*if ( title.substring( 0, 8 ) == 'Orchard:' )
					{
						SearchItemCount++;
						document.getElementById('search_contents'+SearchItemCount).innerHTML = '<div id="search_item'+SearchItemCount+'">' + document.getElementById('hidden_search_inputtestno').innerHTML.replace('div_id="_id_"','id="item'+SearchItemCount+'"').replace('_id_','item'+SearchItemCount).replace('test_no[]','test_no['+SearchItemCount+']') + '</div>';
					}*/
					newSearchItem();
					document.getElementById('search_screen').style.visibility = 'visible';
				}
			}
			else
			{
				if ( InsertFunction )
					document.getElementById('status_div').innerHTML = document.getElementById('status_error_field_function').innerHTML;
				else if ( InsertOperator )
					document.getElementById('status_div').innerHTML = document.getElementById('status_error_field_operator').innerHTML;
			}
		break;

		case 'operator':
			if ( InsertOperator || ( title == '(' && InsertFunction ) )
			{
				if ( title == '(' && !InsertFunction )
				{
					if ( InsertField )
						document.getElementById('status_div').innerHTML = document.getElementById('status_error_operator_field').innerHTML;
					else if ( InsertFunction )
						document.getElementById('status_div').innerHTML = document.getElementById('status_error_operator_function').innerHTML;
					break;
				}
				i++;
				reg = new RegExp("<img src=[^>]+>");
				if ( title == ')' && InFunction === true )
				{
					document.getElementById('equation_div').innerHTML = document.getElementById('equation_div').innerHTML.toLowerCase().replace(reg,'') + ' <img src="modules/Reports/assets/blinking_cursor.gif" /> ';
					InFunction = false;
				}
				else if ( title == '(' || title == ')' )
					document.getElementById('equation_div').innerHTML = document.getElementById('equation_div').innerHTML.toLowerCase().replace(reg,'<span id="start'+i+'"></span>' + title + ' <span id="end'+i+'"></span><img src="modules/Reports/assets/blinking_cursor.gif" /> ');
				else
					document.getElementById('equation_div').innerHTML = document.getElementById('equation_div').innerHTML.toLowerCase().replace(reg,'<span id="start'+i+'"></span>' + title + ' <span id="end'+i+'"></span><img src="modules/Reports/assets/blinking_cursor.gif" /> ');
				InsertFunction = true;
				InsertConstant = true;
				InsertOperator = false;
				InsertField = false;
				if ( title != ')' )
					document.getElementById('status_div').innerHTML = document.getElementById('status_choose_function').innerHTML;
				else
				{
					document.getElementById('status_div').innerHTML = document.getElementById('status_choose_operator_or_save').innerHTML;
					InsertOperator = true;
					InsertFunction = false;
					InsertField = false;
					// ENABLE SAVE BUTTONS
				}
			}
			else
			{
				if ( InsertField )
					document.getElementById('status_div').innerHTML = document.getElementById('status_error_operator_field').innerHTML;
				else if ( InsertFunction )
					document.getElementById('status_div').innerHTML = document.getElementById('status_error_operator_function').innerHTML;
			}
		break;
	}
}

function doOnKeyPress( key )
{
	return false;
}

function backspace()
{
	reg = new RegExp('<span id="([a-z0-9]+)">');
	inner = replaceAll(document.getElementById('equation_div').innerHTML.toLowerCase(),reg,'<span id="$1">');
	deleted = inner.substr(inner.lastIndexOf('<span id="start'));
	search = '>[0-9.]+<span id="end';
	// FUNCTION HAS BEEN DELETED
	if ( deleted.indexOf( '<b>(</b>' ) != '-1' )
	{
		after = ' <img src="modules/Reports/assets/blinking_cursor.gif" />';
		InsertFunction = true;
		InsertConstant = true;
		InsertOperator = false;
		InsertField = false;
		document.getElementById('status_div').innerHTML = document.getElementById('status_choose_function').innerHTML;
	}
	// FIELD HAS BEEN DELETED
	else if ( deleted.indexOf( '<b>)</b>' ) != '-1' )
	{
		after = '<img src="modules/Reports/assets/blinking_cursor.gif" /> <b>)</b>';
		InsertFunction = false;
		InsertConstant = false;
		InsertField = true;
		document.getElementById('status_div').innerHTML = document.getElementById('status_choose_field').innerHTML;
		InFunction = true;
	}
	// CONSTANT HAS BEEN DELETED
	else if ( deleted.search( search ) != -1 )
	{
		after = '<img src="modules/Reports/assets/blinking_cursor.gif" />';
		document.getElementById('status_div').innerHTML = document.getElementById('status_choose_operator_or_constant').innerHTML;
		InsertFunction = false;
		InsertConstant = true;
		InsertOperator = true;
		InsertField = false;
	}
	// OPERATOR HAS BEEN DELETED
	else
	{
		after = ' <img src="modules/Reports/assets/blinking_cursor.gif" />';
		InsertField = false;
		InsertOperator = true;
		document.getElementById('status_div').innerHTML = document.getElementById('status_choose_operator').innerHTML;
	}
	
	document.getElementById('equation_div').innerHTML = inner.substr(0,inner.lastIndexOf('<span id="start')) + after;
}

function getYPos( id )
{
	var y = 0;
	if ( document.layers )
		y = document.layers[id].pageY;
	else if ( document.all || document.getElementById )
	{
		var cell = document.all ? document.all[id] : document.getElementById(id);
		while(cell)
		{
			y += cell.offsetTop;
			cell = cell.offsetParent;
		}
	}
	return y;
}

function getXPos( id )
{
	var x = 0;
	if ( document.layers )
		x = document.layers[id].pageX;
	else if ( document.all || document.getElementById )
	{
		var cell = document.all ? document.all[id] : document.getElementById(id);
		while(cell)
		{
			x += cell.offsetLeft;
			cell = cell.offsetParent;
		}
	}
	return x;
}

function newSearchItem()
{
	SearchItemCount++;
	document.getElementById(
		'search_contents'+SearchItemCount).innerHTML = '<div id="search_item'+SearchItemCount+'">' +
			document.getElementById('hidden_search_inputgrade').innerHTML.replace('div_id="_id_"','id="item'+SearchItemCount+'"').replace('_id_','item'+SearchItemCount) +
			'</div>';
}

function removeSearchItem( id )
{
	id = id.substr(4);
	if ( id == 2 )
		document.getElementById('search_contents'+id).innerHTML = '<a href="#" onclick="newSearchItem(); return false;"><img src="modules/Reports/assets/add_button.gif" /></a>';
	else
		document.getElementById('search_contents'+id).innerHTML = '';
}

/*function newNoItem()
{
	SearchItemCount++;
	document.getElementById('search_contents'+SearchItemCount).innerHTML = '<div id="search_item'+SearchItemCount+'">' + document.getElementById('hidden_search_inputtestno').innerHTML.replace('div_id="_id_"','id=item'+SearchItemCount).replace('_id_','item'+SearchItemCount).replace('test_no[]','test_no['+SearchItemCount+']') + '</DIV>';
}*/

function switchSearchInput( select )
{
	id = select.id.substr(4);
	value = select.options[select.selectedIndex].value;
	se = select.selectedIndex;
	document.getElementById('search_item'+id).innerHTML = document.getElementById('hidden_search_input'+value).innerHTML.replace('div_id="_id_"','id=item'+id);
	document.getElementById('item'+id).selectedIndex = se;
}

function runQuery()
{
	if ( SearchScreenCount > 0 )
	{
		formname = 'searchform'+SearchScreenCount;
		elems = document.forms[formname].elements;

		existing_elems = document.forms['main_form'].elements;
		for(elemindex = 0;elemindex<existing_elems.length;elemindex++)
		{
			if ( existing_elems[elemindex].name.substr(0,existing_elems[elemindex].name.indexOf(']')) == 'screen['+SearchScreenCount)
				document.forms['main_form'].elements[elemindex].value='';
		}
		document.forms['main_form'].elements['breakdown'].value = document.getElementById('breakdown').options[document.getElementById('breakdown').selectedIndex].value;
		for(elemindex = 0;elemindex<elems.length;elemindex++)
		{
			elem = document.forms[formname].elements[elemindex];
			if ( elem.name != 'itemname' )
			{
				if ( elem.options )
					document.getElementById('hidden_permanent_search_contents').innerHTML = document.getElementById('hidden_permanent_search_contents').innerHTML + '<input type="hidden" name="screen['+SearchScreenCount+']['+elem.getAttribute('name')+']" value="'+elem.options[elem.selectedIndex].value+'" />';
				else if ( elem.value )
					document.getElementById('hidden_permanent_search_contents').innerHTML = document.getElementById('hidden_permanent_search_contents').innerHTML + '<input type="hidden" name="screen['+SearchScreenCount+']['+elem.getAttribute('name')+']" value="'+elem.value+'" />';
			}
		}
	}
	document.forms.main_form.query.value = document.getElementById('equation_div').innerHTML;
	document.getElementById('search_screen').style.visibility = 'hidden';
	SendXMLRequest('main_form','XMLHttpRequest');
}

function saveQuery()
{
	if ( SearchScreenCount > 0 )
	{
		formname = 'searchform'+SearchScreenCount;
		elems = document.forms[formname].elements;

		existing_elems = document.forms['main_form'].elements;
		for(elemindex = 0;elemindex<existing_elems.length;elemindex++)
		{
			if ( existing_elems[elemindex].name.substr(0,existing_elems[elemindex].name.indexOf(']')) == 'screen['+SearchScreenCount)
				document.forms['main_form'].elements[elemindex].value='';
		}
		document.forms['main_form'].elements['breakdown'].value = document.getElementById('breakdown').options[document.getElementById('breakdown').selectedIndex].value;
		for(elemindex = 0;elemindex<elems.length;elemindex++)
		{
			elem = document.forms[formname].elements[elemindex];
			if (elem.name!='itemname')
			{
				if (elem.options)
					document.getElementById('hidden_permanent_search_contents').innerHTML = document.getElementById('hidden_permanent_search_contents').innerHTML + '<input type="hidden" name="screen['+SearchScreenCount+']['+elem.getAttribute('name')+']" value="'+elem.options[elem.selectedIndex].value+'" />';
				else if (elem.value)
					document.getElementById('hidden_permanent_search_contents').innerHTML = document.getElementById('hidden_permanent_search_contents').innerHTML + '<input type="hidden" name="screen['+SearchScreenCount+']['+elem.getAttribute('name')+']" value="'+elem.value+'" />';
			}
		}
	}
	document.forms.main_form.query.value = document.getElementById('equation_div').innerHTML;
	document.getElementById('equation_div').innerHTML = '';
	document.getElementById('search_screen').style.visibility = 'hidden';
	document.getElementById('save_screen').style.visibility = 'hidden';
	SendXMLRequest('main_form','saveXMLHttpRequest');
}

function replaceAll( Source, stringToFind, stringToReplace ) {

	var temp = Source,
		index = temp.indexOf(stringToFind);

	while(index != -1){

		temp = temp.replace(stringToFind,stringToReplace);

		index = temp.indexOf(stringToFind);
	}

	return temp;
}