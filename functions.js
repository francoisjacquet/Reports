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

// Shorten document.getElementById().
var byId = function( id ) {
	return document.getElementById( id );
};

function insertItem( title, type )
{
	switch( type )
	{
		case 'function':
			if ( InsertFunction )
			{
				i++;
				reg = new RegExp("<img src=[^>]+>");
				byId('equation_div').innerHTML = byId('equation_div').innerHTML.toLowerCase().replace(reg,'<span id="start'+i+'"></span>' + title + '<b>(</b> <img src="modules/Reports/assets/blinking_cursor.gif" /> <b>)</b><span id="end'+i+'"></span>');
				InsertFunction = false;
				InsertConstant = false;
				InsertField = true;
				byId('status_div').innerHTML = byId('status_choose_field').innerHTML;
				InFunction = true;
			}
			else
			{
				if ( InsertField )
					byId('status_div').innerHTML = byId('status_error_function_field').innerHTML;
				else if ( InsertOperator )
					byId('status_div').innerHTML = byId('status_error_function_operator').innerHTML;
			}
		break;

		case 'field':
			if ( InsertField || ( InsertConstant && title.length == 1 ) )
			{
				i++;
				reg = new RegExp("<img src=[^>]+>");
				byId('equation_div').innerHTML = byId('equation_div').innerHTML.toLowerCase().replace(reg,'<span id="start'+i+'"></span>'+title + '<span id="end'+i+'"></span> <img src="modules/Reports/assets/blinking_cursor.gif" /> ');

				if ( title.length == 1 )
				{
					byId('status_div').innerHTML = byId('status_choose_operator').innerHTML;
					InsertFunction = false;
					InsertConstant = true;
					InsertOperator = true;
					InsertField = false;
				}
				else
				{
					byId('status_div').innerHTML = byId('status_choose_operator_or_constant').innerHTML;
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
									byId('hidden_permanent_search_contents').innerHTML += '<input type="hidden" name="screen['+SearchScreenCount+']['+elem.getAttribute('name')+']" value="'+elem.value+'" />';
								else if (elem.options)
									byId('hidden_permanent_search_contents').innerHTML += '<input type="hidden" name="screen['+SearchScreenCount+']['+elem.getAttribute('name')+']" value="'+elem.options[elem.selectedIndex].value+'" />';
							}
						}
					}

					SearchScreenCount++;

					// PLACE SEARCH SCREEN
					//byId("search_screen").style.top = getYPos('end'+(i-1));
					var startOffset = $( '#start' + ( i - 1 ) ).position();
					$("#search_screen").css( 'margin-left', startOffset.left + 15 );
					byId('search_contents').innerHTML = replaceAll(byId('hidden_search_contents').innerHTML,'div_id','id').replace('_searchform_','searchform'+SearchScreenCount);
					if ( title=='Present' || title=='Absent' || title=='Enrolled' || title.substring( 0, 8 ) == 'Orchard:' )
					{
						SearchItemCount++;
						byId('search_contents'+SearchItemCount).innerHTML = '<div id="search_item'+SearchItemCount+'">' + byId('hidden_search_inputtimespan').innerHTML.replace('div_id="_id_"','id="item'+SearchItemCount+'"').replace('_id_','item'+SearchItemCount) + '</div>';
					}
					/*if ( title.substring( 0, 8 ) == 'Orchard:' )
					{
						SearchItemCount++;
						byId('search_contents'+SearchItemCount).innerHTML = '<div id="search_item'+SearchItemCount+'">' + byId('hidden_search_inputtestno').innerHTML.replace('div_id="_id_"','id="item'+SearchItemCount+'"').replace('_id_','item'+SearchItemCount).replace('test_no[]','test_no['+SearchItemCount+']') + '</div>';
					}*/
					newSearchItem();
					byId('search_screen').style.visibility = 'visible';
				}
			}
			else
			{
				if ( InsertFunction )
					byId('status_div').innerHTML = byId('status_error_field_function').innerHTML;
				else if ( InsertOperator )
					byId('status_div').innerHTML = byId('status_error_field_operator').innerHTML;
			}
		break;

		case 'operator':
			if ( InsertOperator || ( title == '(' && InsertFunction ) )
			{
				if ( title == '(' && !InsertFunction )
				{
					if ( InsertField )
						byId('status_div').innerHTML = byId('status_error_operator_field').innerHTML;
					else if ( InsertFunction )
						byId('status_div').innerHTML = byId('status_error_operator_function').innerHTML;
					break;
				}
				i++;
				reg = new RegExp("<img src=[^>]+>");
				if ( title == ')' && InFunction === true )
				{
					byId('equation_div').innerHTML = byId('equation_div').innerHTML.toLowerCase().replace(reg,'') + ' <img src="modules/Reports/assets/blinking_cursor.gif" /> ';
					InFunction = false;
				}
				else if ( title == '(' || title == ')' )
					byId('equation_div').innerHTML = byId('equation_div').innerHTML.toLowerCase().replace(reg,'<span id="start'+i+'"></span>' + title + ' <span id="end'+i+'"></span><img src="modules/Reports/assets/blinking_cursor.gif" /> ');
				else
					byId('equation_div').innerHTML = byId('equation_div').innerHTML.toLowerCase().replace(reg,'<span id="start'+i+'"></span>' + title + ' <span id="end'+i+'"></span><img src="modules/Reports/assets/blinking_cursor.gif" /> ');
				InsertFunction = true;
				InsertConstant = true;
				InsertOperator = false;
				InsertField = false;
				if ( title != ')' )
					byId('status_div').innerHTML = byId('status_choose_function').innerHTML;
				else
				{
					byId('status_div').innerHTML = byId('status_choose_operator_or_save').innerHTML;
					InsertOperator = true;
					InsertFunction = false;
					InsertField = false;
					// ENABLE SAVE BUTTONS
				}
			}
			else
			{
				if ( InsertField )
					byId('status_div').innerHTML = byId('status_error_operator_field').innerHTML;
				else if ( InsertFunction )
					byId('status_div').innerHTML = byId('status_error_operator_function').innerHTML;
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
	inner = replaceAll(byId('equation_div').innerHTML.toLowerCase(),reg,'<span id="$1">');
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
		byId('status_div').innerHTML = byId('status_choose_function').innerHTML;
	}
	// FIELD HAS BEEN DELETED
	else if ( deleted.indexOf( '<b>)</b>' ) != '-1' )
	{
		after = '<img src="modules/Reports/assets/blinking_cursor.gif" /> <b>)</b>';
		InsertFunction = false;
		InsertConstant = false;
		InsertField = true;
		byId('status_div').innerHTML = byId('status_choose_field').innerHTML;
		InFunction = true;
	}
	// CONSTANT HAS BEEN DELETED
	else if ( deleted.search( search ) != -1 )
	{
		after = '<img src="modules/Reports/assets/blinking_cursor.gif" />';
		byId('status_div').innerHTML = byId('status_choose_operator_or_constant').innerHTML;
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
		byId('status_div').innerHTML = byId('status_choose_operator').innerHTML;
	}

	byId('equation_div').innerHTML = inner.substr(0,inner.lastIndexOf('<span id="start')) + after;
}

function getYPos( id )
{
	var y = 0;
	if ( document.layers )
		y = document.layers[id].pageY;
	else if ( document.all || document.getElementById )
	{
		var cell = document.all ? document.all[id] : byId(id);
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
		var cell = document.all ? document.all[id] : byId(id);
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

	var searchItemHTML = byId('hidden_search_inputgrade').innerHTML
			.replace('div_id="_id_"','id="item'+SearchItemCount+'"')
			.replace('_id_','item'+SearchItemCount);

	byId('search_contents'+SearchItemCount).innerHTML = '<div id="search_item'+SearchItemCount+'">' +
		searchItemHTML + '</div>';
}

function removeSearchItem( id )
{
	id = id.substr(4);
	if ( id == 1 )
		byId('search_contents'+id).innerHTML = '<a href="#" onclick="newSearchItem(); this.remove(); return false;"><img src="modules/Reports/assets/add_button.gif" /></a>';
	else
		byId('search_contents'+id).innerHTML = '';
}

/*function newNoItem()
{
	SearchItemCount++;
	byId('search_contents'+SearchItemCount).innerHTML = '<div id="search_item'+SearchItemCount+'">' + byId('hidden_search_inputtestno').innerHTML.replace('div_id="_id_"','id=item'+SearchItemCount).replace('_id_','item'+SearchItemCount).replace('test_no[]','test_no['+SearchItemCount+']') + '</DIV>';
}*/

function switchSearchInput( select )
{
	id = select.id.substr(4);
	value = select.options[select.selectedIndex].value;
	se = select.selectedIndex;
	byId('search_item'+id).innerHTML = byId('hidden_search_input'+value).innerHTML.replace('div_id="_id_"','id=item'+id);
	byId('item'+id).selectedIndex = se;
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
		document.forms['main_form'].elements['breakdown'].value = byId('breakdown').options[byId('breakdown').selectedIndex].value;

		for(elemindex = 0;elemindex<elems.length;elemindex++)
		{
			elem = document.forms[formname].elements[elemindex];
			if ( elem.name != 'itemname' )
			{
				if ( elem.options )
					byId('hidden_permanent_search_contents').innerHTML += '<input type="hidden" name="screen['+ SearchScreenCount +']['+elem.getAttribute('name')+']" value="'+elem.options[elem.selectedIndex].value+'" />';
				else if ( elem.value )
					byId('hidden_permanent_search_contents').innerHTML += '<input type="hidden" name="screen['+ SearchScreenCount +']['+elem.getAttribute('name')+']" value="'+elem.value+'" />';

			console.log(byId('hidden_permanent_search_contents').innerHTML);}
		}
	}

	document.forms.main_form.query.value = byId('equation_div').innerHTML;
	byId('search_screen').style.visibility = 'hidden';
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
		document.forms['main_form'].elements['breakdown'].value = byId('breakdown').options[byId('breakdown').selectedIndex].value;
		for(elemindex = 0;elemindex<elems.length;elemindex++)
		{
			elem = document.forms[formname].elements[elemindex];
			if (elem.name!='itemname')
			{
				if (elem.options)
					byId('hidden_permanent_search_contents').innerHTML += '<input type="hidden" name="screen['+ SearchScreenCount +']['+elem.getAttribute('name')+']" value="'+elem.options[elem.selectedIndex].value+'" />';
				else if (elem.value)
					byId('hidden_permanent_search_contents').innerHTML += '<input type="hidden" name="screen['+ SearchScreenCount +']['+elem.getAttribute('name')+']" value="'+elem.value+'" />';
			}
		}
		SearchScreenCount++;
	}

	// Reset:
	SearchScreenCount = 0;

	document.forms.main_form.query.value = byId('equation_div').innerHTML.replace("+",'%2B');
	byId('equation_div').innerHTML = '';
	byId('search_screen').style.visibility = 'hidden';
	byId('save_screen').style.visibility = 'hidden';
	SendXMLRequest('main_form','saveXMLHttpRequest');

	byId('hidden_permanent_search_contents').innerHTML = '';
	byId('equation_div').innerHTML = '';
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
