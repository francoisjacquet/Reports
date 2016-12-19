<?php
/**
 * Calculations
 *
 * @package Reports
 */

if ( $_REQUEST['modname'] !== 'misc/Export.php'
	&& $_REQUEST['modname'] !== 'Reports/CalculationsReports.php' )
{
	require_once 'modules/Reports/includes/ReportsCalculations.fnc.php';
}

// AJAX.
if ( $_REQUEST['modfunc'] === 'XMLHttpRequest' )
{
	$query = _makeQuery( $_POST['query'] );

	//print_r($_REQUEST);
	/*echo '<br />EVAL QUERY: '.
	echo '<br />RESULTS: '.$result;
	echo '<br />AVG PRES: '._average(_getResults('present','2'));
	echo '<PRE>'.str_replace('<','&lt;',str_replace('>','&gt;',$query)).'</PRE>';*/

	$results = _getAJAXResults( $query, $_REQUEST['modfunc'] );


	header( "Content-Type: text/xml\n\n" );

	echo '<?xml version="1.0" encoding="UTF-8"?>';
	echo '<results>';

	echo $results;

	echo '</results>';

	exit;
}
// Save AJAX.
elseif ( $_REQUEST['modfunc'] === 'saveXMLHttpRequest' )
{
	$_REQUEST['query'] = $_POST['query'];

	$location = PreparePHP_SELF();

	DBQuery( "INSERT INTO SAVED_CALCULATIONS (ID,TITLE,URL)
		values(
			" . db_seq_nextval( 'SAVED_CALCULATIONS_SEQ' ) . ",
			'" . $_REQUEST['calc_title'] . "',
			'" . $location . "'
		)" );

	header( "Content-Type: text/xml\n\n" );

	echo '<?xml version="1.0" encoding="UTF-8"?>';

	echo '<results>';

	// Do NOT translate 'Saved', used by assets/ajax.js.
	echo '<result><id>~</id><title>' . 'Saved' . '</title></result>';

	echo '</results>';

	exit;
}
// Echo AJAX.
elseif ( $_REQUEST['modfunc'] === 'echoXMLHttpRequest' )
{
	$query = _makeQuery( $_POST['query'] );

	//print_r($_REQUEST);
	/*echo '<br />EVAL QUERY: '.
	echo '<br />RESULTS: '.$result;
	echo '<br />AVG PRES: '._average(_getResults('present','2'));
	echo '<PRE>'.str_replace('<','&lt;',str_replace('>','&gt;',$query)).'</PRE>';*/

	$results = _getAJAXResults( $query, $_REQUEST['modfunc'] );

	return $results;
}
// Remove Equation.
elseif ( $_REQUEST['modfunc'] === 'remove' )
{
	if ( ! isset( $_REQUEST['delete_ok'] )
		&& ! isset( $_REQUEST['delete_cancel'] ) )
	{
		DrawHeader( ProgramTitle() );
	}

	if ( DeletePrompt( dgettext( 'Reports', 'Saved Equation' ) ) )
	{
		DBQuery( "DELETE FROM SAVED_CALCULATIONS
			WHERE ID='" . $_REQUEST['id'] . "'" );

		unset( $_REQUEST['modfunc'] );
		unset( $_SESSION['_REQUEST_vars']['modfunc'] );
		unset( $_SESSION['_REQUEST_vars']['id'] );
	}
}

// Update Equations.
if ( $_REQUEST['modfunc'] === 'update_equations'
	&& isset( $_REQUEST['values'] )
	&& isset( $_POST['values'] )
	&& AllowEdit() )
{
	foreach ( (array)$_REQUEST['values'] as $id => $columns )
	{
		$sql = "UPDATE SAVED_CALCULATIONS SET ";

		foreach ( (array)$columns as $column => $value )
		{
			$sql .= $column . "='" . $value . "',";
		}

		$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";

		DBQuery( $sql );
	}
}

if ( $_REQUEST['modfunc'] !== 'remove' )
{
	echo '<script src="modules/Reports/functions.js"></script>';

	echo '<script src="modules/Reports/assets/ajax.js"></script>';

	DrawHeader( ProgramTitle() );

	DrawHeader( '<div id="status_div">&nbsp;</div>' );

	$field_categories = array(
		'',
		dgettext( 'Reports', 'Time Values' ),
		Config( 'NAME' ) . ' ' . _( 'Fields' ),
		// 'Orchard ' . _('Fields'),
		dgettext( 'Reports', 'Constants' ),
	);

	$items = array(
		'function' => array(
			'sum',
			'average',
			'count',
			'max',
			'min',
			'average-max',
			'average-min',
			'sum-max',
			'sum-min',
			// 'stdev',
		),
		'operator' => array( '+', '-', '*', '/', '(', ')' ),
		'field' => array(
			'~',
			_( 'Present' ),
			_( 'Absent' ),
			_( 'Enrolled' ),
			'~',
			dgettext( 'Reports', 'Student ID' ),
		),
	);

	// Numeric Fields.
	$numeric_RET = DBGet( DBQuery( "SELECT ID,CATEGORY_ID,TITLE
		FROM CUSTOM_FIELDS
		WHERE TYPE='numeric'" ), array(), array( 'CATEGORY_ID' ) );

	foreach ( (array) $numeric_RET as $category_id => $fields )
	{
		if ( AllowUse( 'Modules.php?modname=Students/Student.php&category_id=' . $category_id ) )
		{
			foreach ( (array) $fields as $field )
			{
				$items['field'][] = Config( 'NAME' ) . ': ' . $field['TITLE'];
			}
		}
	}

	// RosarioSIS?
	/*$items['field'][] = '~';

	$items['field'][] = _( 'Time on Task' );

	$subjects = array(
		_( 'Math' ),
		_( 'Language Arts' ),
		_( 'Social Studies' ),
		_( 'Science' ),
		_( 'Biology' ),
	);

	foreach ( (array)$subjects as $test )
	{
		$items['field'][] = 'Orchard: ' . $test . ' ' . _( 'Score' );
	}*/

	$items['field'][] = '~';

	for ( $i = 0; $i <= 9; $i++ )
	{
		$items['field'][] = $i;
	}

	$items['field'][] = '0';
	$items['field'][] = '.';

	//$items['field'] += array('~','IL Time','~','0','1','2','3','4','5','6','7','8','9')
	echo '<br />';

	echo '<table class="width-100p"><tr class="st"><td class="valign-top">';

	$content = '<table class="width-100p"><tr><td class="center valign-top"><b>' .
		dgettext( 'Reports', 'Functions' ) . '</b><br />';

	$type = 'function';

	foreach ( (array) $items['function'] as $item )
	{
		$content .= DrawTab(
			$item,
			'#" onclick="insertItem(\'' . $item . '\',\'' . $type . '\'); return false;'
		);
	}

	$content .= '</td><td class="center valign-top" style="border-left: solid 1px #000;"><b>' .
		dgettext( 'Reports', 'Operators' ) . '</b><br />';

	$type = 'operator';

	$j = 0;

	foreach ( (array) $items['operator'] as $item )
	{
		$content .= DrawTab(
			$item,
			'#" onclick="insertItem(\''.$item.'\',\''.$type.'\'); return false;'
		);

		$j++;

	}

	$content .= '</td></tr></table>';

	echo PopTable( 'header', dgettext( 'Reports', 'Functions' ) . ' &amp; ' . dgettext( 'Reports', 'Operators' ) );

	echo $content;

	echo PopTable( 'footer' );

	echo '</td><td class="valign-top">';

	$content = '<table class="width-100p"><tr><td>';

	$type = 'field';

	foreach ( (array) $items['field'] as $item )
	{
		if ( $item == '~' )
		{
			if ( $cat_count != 0 )
			{
				$content .= '</td><td style="border-left: solid 1px #000;"></td>';
			}

			$cat_count++;

			$content .= '<td class="center valign-top"><b>' . $field_categories[ $cat_count ] . '</b><br />';

			if ( $field_categories[ $cat_count ] == dgettext( 'Reports', 'Constants' ) )
			{
				$content .= '<table class="cellspacing-0"><tr>';

				for ( $i = 7; $i <= 9; $i++ )
				{
					$content .= '<td style="width:15px;" class="center">' .
						DrawTab(
							$i,
							'#" onclick="insertItem(\'' . $i . '\',\'' .$type . '\'); return false;'
						) . '</td>';
				}

				$content .= '</tr><tr>';

				for ( $i = 4; $i <= 6; $i++ )
				{
					$content .= '<td style="width:15px;" class="center">' .
						DrawTab(
							$i,
							'#" onclick="insertItem(\''.$i.'\',\''.$type.'\'); return false;'
						) . '</td>';
				}

				$content .= '</tr><tr>';

				for ( $i = 1; $i <= 3; $i++ )
				{
					$content .= '<td style="width:15px;" class="center">' .
						DrawTab(
							$i,
							'#" onclick="insertItem(\'' . $i . '\',\'' . $type . '\'); return false;'
						) . '</td>';
				}

				$content .= '</tr><tr><td class="center">' .
					DrawTab(
						'.',
						'#" onclick="insertItem(\'.\',\'' . $type . '\'); return false;'
					) . '</td><td class="center">' .
					DrawTab(
						'0',
						'#" onclick="insertItem(\'0\',\''.$type.'\'); return false;'
					) . '</td><td></td></tr>';

				$content .= '</table>';

				break;
			}
			else
				continue;
		}

		$content .= DrawTab(
			$item,
			'#" onclick="insertItem(\'' . $item.'\',\'' . $type . '\'); return false;'
		);
	}

	$content .= '</td></tr></table>';

	echo PopTable( 'header', _( 'Fields' ) );

	echo $content;

	echo PopTable( 'footer' );

	echo '</td></tr></table><br />';

	$birthdate_RET = DBGet( DBQuery( "SELECT 1
		FROM CUSTOM_FIELDS
		WHERE TYPE='date'
		AND ID='200000004'" ) );

	$fields_RET = DBGet( DBQuery( "SELECT ID,TITLE
		FROM CUSTOM_FIELDS
		WHERE TYPE='select'
		ORDER BY TITLE" ) );

	$breakdown_options = array(
		'school' => _( 'School' ),
		'grade' => _( 'Grade Level' ),
		'stuid' => dgettext( 'Reports', 'Student ID' ),
	);

	// Check Birthdate original field exists and is DATE.
	if ( $birthdate_RET )
	{
		$breakdown_options['age'] = _( 'Age' );
	}

	foreach ( (array) $fields_RET as $field )
	{
		$breakdown_options[ 'CUSTOM_' . $field['ID'] ] = ParseMLField( $field['TITLE'], $locale );
	}

	$breakdown = SelectInput(
		'',
		'breakdown',
		'',
		$breakdown_options,
		dgettext( 'Reports', 'Breakdown' )
	);

	echo PopTable( 'header', dgettext( 'Reports', 'Equation' ), 'style="margin:0 0;"' );

	echo '<table width=100%><tr><td class="align-right">' .
		$breakdown .
		'<a href="#" onclick="backspace(); return false;">
			<img src="modules/Reports/assets/backspace.gif" title="' . dgettext( 'Reports', 'Back' ) . '" class="alignImg" />
		</a>
		<a href="#" onclick="runQuery(); return false;">
			<img src="modules/Reports/assets/run_key.gif" title="' . dgettext( 'Reports', 'Run' ) . '" class="alignImg" />
		</a>
		<a href="#" onclick="document.getElementById(\'save_screen\').style.display=\'inline-block\'; return false;">
			<img src="modules/Reports/assets/save_key.gif" id="saveimage" title="' . _( 'Save' ) . '" class="alignImg" />
		</a>
	</td></tr><tr><td class="width-100p">
		<div id="equation_div">
			<img src="modules/Reports/assets/blinking_cursor.gif" />
		</div>
	</td></tr></table>
	<div id="XMLHttpRequestResult"></div>';

	echo PopTable( 'footer' );

	echo '<div id="search_screen" style="visibility:hidden; display: inline-block;">
		<img src="modules/Reports/assets/arrow_up.gif" />
		<div style="border: solid 2px #CCBBCC;" id="search_contents"></div>
	</div>';

	echo '<div id="hidden_search_contents"><form action="#" name="_searchform_">';

	for ( $i = 1; $i <= 10; $i++ )
	{
		echo '<div div_id="search_contents' . $i . '"></div>';
	}

	echo '</form></div>';

	echo '<form action="#" name="main_form">';

	echo '<input type="hidden" name="query" /><input type="hidden" name="breakdown" />';

	echo '<div style="visibility:hidden;" id="hidden_permanent_search_contents"></div>';

	echo '<div id="save_screen" style="display:none;">
		<img src="modules/Reports/assets/arrow_up.gif" />
		<div style="border: solid 2px #CCBBCC;" id="save_content">';

	echo '<table class="cellpadding-5"><tr>
		<td>' . TextInput(
			'',
			'calc_title',
			_( 'Title' ),
			'size="15" maxlength="100" onkeypress="if(event.keyCode==13){saveQuery();return false;}"',
			false
		) . '</td><td>' .
		SubmitButton(
			_( 'Save' ),
			'calc_save',
			'onclick="saveQuery(); return false;"'
		) . '</td></tr></table>';

	echo '</div></div></form>';

	$equations_RET = DBGet( DBQuery( "SELECT ID,TITLE,URL
		FROM SAVED_CALCULATIONS
		ORDER BY TITLE" ), array( 'TITLE' => '_makeText', 'URL' => '_makeURL' ) );

	if ( $equations_RET )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=update_equations" method="POST">';

		$columns = array( 'TITLE' => _( 'Title' ), 'URL' => dgettext( 'Reports', 'Equation' ) );

		$link['remove']['link'] = "Modules.php?modname=" . $_REQUEST['modname'] . "&modfunc=remove";

		$link['remove']['variables'] = array( 'id' => 'ID' );

		ListOutput( $equations_RET, $columns, 'Saved Equation', 'Saved Equations', $link );

		echo '<div class="center">' . SubmitButton( _( 'Save' ) ) . '</div>';
		echo '</form>';

		echo '<br />';
	}

	// Preferences Student Fields Search.
	$search_fields_RET = DBGet( DBQuery( "SELECT 'CUSTOM_'||cf.ID AS COLUMN_NAME,cf.TYPE,cf.TITLE,cf.SELECT_OPTIONS
		FROM PROGRAM_USER_CONFIG puc,CUSTOM_FIELDS cf
		WHERE puc.TITLE=cast(cf.ID AS TEXT)
		AND puc.PROGRAM='StudentFieldsSearch'
		AND puc.USER_ID='" . User( 'STAFF_ID' ) . "'
		AND puc.VALUE='Y'" ) );

	if ( ! $search_fields_RET )
	{
		$search_fields_RET = DBGet( DBQuery( "SELECT 'CUSTOM_'||cf.ID AS COLUMN_NAME,cf.TYPE,cf.TITLE,cf.SELECT_OPTIONS
			FROM CUSTOM_FIELDS cf
			WHERE cf.ID IN ('200000000','200000001')" ) ); // Gender & Ethnicity.
	}

	$search_fields_RET[] = array(
		'COLUMN_NAME' => 'first',
		'TYPE' => 'other',
		'TITLE' => _( 'First Name' ),
	);

	$search_fields_RET[] = array(
		'COLUMN_NAME' => 'last',
		'TYPE' => 'other',
		'TITLE' => _( 'Last Name' ),
	);

	$search_fields_RET[] = array(
		'COLUMN_NAME' => 'stuid',
		'TYPE' => 'other',
		'TITLE' => dgettext( 'Reports', 'Student ID' ),
	);

	$search_fields_RET[] = array(
		'COLUMN_NAME' => 'schools',
		'TYPE' => 'schools',
		'TITLE' => _( 'Schools' ),
	);

	$fields_select = '<select div_id="_id_" name="itemname" onchange="switchSearchInput(this);">';

	foreach ( (array) $search_fields_RET as $field )
	{
		$fields_select .= '<option value="' . $field['COLUMN_NAME'] . '">' . ParseMLField( $field['TITLE'] ) . '</option>';
	}

	$fields_select .= '<option value="grade" selected>'. _( 'Grade Level' ) . '</option>';

	$fields_select .= '</select>';

	$search_fields_RET[] = array(
		'COLUMN_NAME' => 'grade',
		'TYPE' => 'grade',
		'TITLE' => _( 'Grade Level' ),
	);

	echo '<div id="hidden_search_inputtimespan" style="visibility:hidden;">
		<table><tr><td colspan="4">' .
		_makeSearchInput(
			array(
				'COLUMN_NAME' => 'timespan',
				'TYPE' => 'timespan',
				'TITLE' => dgettext( 'Reports', 'Between' ),
			)
		) . '</td>
	</tr></table></div>';

	/*echo '<div id="hidden_search_inputtestno" style="visibility:hidden;">
		<table><tr><td>' .
		button( 'add', '', '"#" onclick="newNoItem(); return false;"' ) . '</td><td>' .
		button( 'remove', '', '"#" onclick="removeSearchItem(\'_id_\'); return false;"' ) .
		'</td><td colspan="2">' .
		_makeSearchInput(
			array(
				'COLUMN_NAME' => 'test_no',
				'TYPE' => 'test_no',
				'TITLE' => dgettext( 'Reports', 'Test Number' ),
			)
		) . '</td>
	</tr></table></div>';*/

	foreach ( (array) $search_fields_RET as $field )
	{
		echo '<div id="hidden_search_input' . $field['COLUMN_NAME'] . '" style="visibility:hidden;">
			<table><tr><td>' .
			button( 'add', '', '"#" onclick="newSearchItem(); return false;"' ) . '</td><td>' .
			button( 'remove', '', '"#" onclick="removeSearchItem(\'_id_\'); return false;"' ) . '</td><td>' .
			$fields_select . '</td><td>' .
			_makeSearchInput( $field ) . '</td>
		</tr></table></div>';
	}

	/**
	 * Statuses / errors translations
	 *
	 * @see functions.js
	 */
	echo '<div id="status_choose_field" style="display:none;"><b style="color:green;">' .
		dgettext( 'Reports', 'Please choose a field.' ) . '</b></div>';

	echo '<div id="status_error_function_field" style="display:none;"><b style="color:red;">' .
		dgettext( 'Reports', 'A function cannot be placed here; choose a field instead.' ) . '</b></div>';

	echo '<div id="status_error_function_operator" style="display:none;"><b style="color:red;">' .
		dgettext( 'Reports', 'A function cannot be placed here; choose an operator instead.' ) . '</b></div>';

	echo '<div id="status_choose_operator_or_constant" style="display:none;"><b style="color:green;">' .
		dgettext( 'Reports', 'Please choose an operator or another constant.' ) . '</b></div>';

	echo '<div id="status_choose_operator" style="display:none;"><b style="color:green;">' .
		dgettext( 'Reports', 'Please choose an operator.' ) . '</b></div>';

	echo '<div id="status_error_field_function" style="display:none;"><b style="color:red;">' .
		dgettext( 'Reports', 'A field cannot be placed here; choose a function instead.' ) . '</b></div>';

	echo '<div id="status_error_field_operator" style="display:none;"><b style="color:red;">' .
		dgettext( 'Reports', 'A field cannot be placed here; choose an operator instead.' ) . '</b></div>';

	echo '<div id="status_error_operator_field" style="display:none;"><b style="color:red;">' .
		dgettext( 'Reports', 'An operator cannot be placed here; choose a field instead.' ) . '</b></div>';

	echo '<div id="status_error_operator_function" style="display:none;"><b style="color:red;">' .
		dgettext( 'Reports', 'An operator cannot be placed here; choose a function instead.' ) . '</b></div>';

	echo '<div id="status_choose_function" style="display:none;"><b style="color:green;">' .
		dgettext( 'Reports', 'Please choose a function.' ) . '</b></div>';

	echo '<div id="status_choose_operator_or_save" style="display:none;"><b style="color:green;">' .
		dgettext( 'Reports', 'Please choose an operator or press save to finish.' ) . '</b></div>';

	echo '<div id="status_error_operator_field" style="display:none;"><b style="color:red;">' .
		dgettext( 'Reports', 'An operator cannot be placed here; choose a field instead.' ) . '</b></div>';
}
