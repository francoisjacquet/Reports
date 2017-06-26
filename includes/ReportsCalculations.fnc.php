<?php
/**
 * Report Calculations functions
 *
 * @package Reports
 * @subpackage includes
 */

/**
 * Make Query from requested QUERY string
 *
 * @example $query = _makeQuery( $_POST['query'] );
 *
 * @param  string $query Requested QUERY string.
 *
 * @return string Formatted Query
 */
function _makeQuery( $query )
{

	// REPLACE FIELDS / TIME VALUES TRANSLATIONS.
	$query = str_ireplace( _( 'Present' ), 'present', $query );

	$query = str_ireplace( _( 'Absent' ), 'absent', $query );

	$query = str_ireplace( _( 'Enrolled' ), 'enrolled', $query );

	$query = str_ireplace( dgettext( 'Reports', 'Student ID' ), 'student id', $query );

	$query = mb_strtolower( $query );

	// REMOVE HTML TAGS.
	// PHP Parse error:
	// syntax error, unexpected '<'
	// in /var/www/demonstration/modules/Reports/includes/ReportsCalculations.fnc.php(330) :
	// eval()'d code on line 1,
	// referer: https://www.rosariosis.org/demonstration/Modules.php?modname=Reports/Calculations.php
	$query = preg_replace( '/\<span id\="/', '<', $query );

	$query = str_replace( '</span>', '', $query );

	$query = preg_replace( '/\<img src\=[^\>]+\>/', '', $query );

	$query = preg_replace( '/"\>/', '>', $query );

	$query = str_replace( '<b>(</b>', '(', $query );

	$query = str_replace( '<b>)</b>', ')', $query );

	// REPLACE FUNCTION NAMES.
	$query = str_replace( 'stdev', 'stats_standard_deviation', $query ); // RosarioSIS?

	$query = str_replace( 'average-min', '_avg0', $query );

	$query = str_replace( 'average-max', '_avg1', $query );

	$query = str_replace( 'sum-min', '_su0', $query );

	$query = str_replace( 'sum-max', '_su1', $query );

	$query = str_replace( 'average', '_average', $query );

	$query = str_replace( 'sum', '_sum', $query );

	$query = str_replace( 'min', '_min', $query );

	$query = str_replace( 'max', '_max', $query );

	// Fix PHP error empty count().
	$query = str_replace( 'count(  )', 'count( array() )', $query );

	$query = preg_replace(
		"/([a-z_]+)\([ ]*\<[a-z]+([0-9]+)\>([a-z: ]+)\<[a-z0-9]+\>[ ]*\)/",
		"\\1(_getResults('\\3','\\2'))",
		$query
	);

	$query = preg_replace(
		"/([a-z01_]+)\([ ]*\<[a-z]+([0-9]+)\>([a-z: ]+)\<[a-z0-9]+\>[ ]*\)/",
		"\\1(_getResults('\\3','\\2','STUDENT_ID'))",
		$query
	);

	$query = preg_replace( '/\<start[0-9]+\>/', '', $query );

	$query = preg_replace( '/\<end[0-9]+\> */', '', $query );

	$query = trim( $query );

	if ( empty( $query ) )
	{
		$query = 'false';
	}

	$query = '$result = ' . $query . ';if(!$result) return 0; else return $result;';

	/*print_r( $_REQUEST );
	echo '<br />EVAL QUERY: ';
	echo '<pre>' . str_replace( '<', '&lt;', str_replace( '>', '&gt;', $query ) ).'</pre>';
	echo '<br />RESULTS: ' . $result;
	echo '<br />AVG PRES: ' . _average( _getResults( 'present', '2' ) );*/

	return $query;
}



/**
 * Get AJAX Results
 * 1 Result
 * or list of Results if Breakdown
 *
 * @example $results = _getAJAXResults( $query, $_REQUEST['modfunc'] );
 *
 * @param  string $query   Query, see _makeQuery()
 * @param  string $modfunc XMLHttpRequest|echoXMLHttpRequest
 *
 * @return string XML or ListOutput or float result(s)
 */
function _getAJAXResults( $query, $modfunc )
{
	global $_ROSARIO,
		$_runCalc_start_REQUEST,
		$num;

	$num = 1;

	$results = '';

	if ( isset( $_REQUEST['breakdown'] ) )
	{
		if ( $_REQUEST['breakdown'] == 'school' )
		{
			$var = 'school';

			$group = DBGet( DBQuery( "SELECT ID,TITLE
				FROM SCHOOLS
				WHERE SYEAR='" . UserSyear() . "'
				ORDER BY TITLE" ) );
		}
		elseif ( $_REQUEST['breakdown'] == 'grade' )
		{
			$var = 'grade';

			$schools = mb_substr( str_replace( ",", "','", User( 'SCHOOLS' ) ), 2, -2 );

			if ( ! isset( $_REQUEST['_search_all_schools'] )
				|| $_REQUEST['_search_all_schools'] != 'Y' )
			{
				$extra_schools = "WHERE SCHOOL_ID='" . UserSchool() . "' ";
			}
			elseif ( $schools )
			{
				$extra_schools = "WHERE SCHOOL_ID IN (" . $schools . ") ";
			}

			$group_RET = DBGet( DBQuery( "SELECT ID,TITLE,SHORT_NAME
				FROM SCHOOL_GRADELEVELS " . $extra_schools . "
				ORDER BY SORT_ORDER" ), array(), array( 'SHORT_NAME' ) );

			foreach ( (array) $group_RET as $short_name => $grades )
			{
				$i++;

				foreach ( (array) $grades as $grade )
				{
					$group[ $i ]['ID'] .= $grade['ID'] . ',';
				}

				$group[ $i ]['ID'] = mb_substr( $group[ $i ]['ID'], 0, -1 );

				$group[ $i ]['TITLE'] = $grades[1]['TITLE'];
			}
		}
		elseif ( $_REQUEST['breakdown'] == 'stuid' )
		{
			$var = 'stuid';

			$start_REQUEST = $_REQUEST;

			$_REQUEST = $_REQUEST['screen'][1];

			foreach ( (array) $_REQUEST as $key => $value )
			{
				// Is array
				// for example: ['cust[CUSTOM_XXX]'] or ['month_cust_begin[CUSTOM_XXX]']
				// => ['cust']['CUSTOM_XXX']
				if ( mb_strpos( $key, '[' ) !== false )
				{
					$_REQUEST[ mb_substr( $key, 0, mb_strpos( $key, '[' ) ) ][ mb_substr( $key, mb_strpos( $key, '[' ) + 1 ) ] = $value;

					unset( $_REQUEST[ $key ] );
				}
			}

			$extra .= appendSQL( '' );

			if ( isset( $_REQUEST['school'] ) )
			{
				$extra .= " AND ssm.SCHOOL_ID = '" . $_REQUEST['school'] . "' ";
			}

			$extra .= CustomFields( 'where' );

			$_REQUEST = $start_REQUEST;

			$group = DBGet( DBQuery( "SELECT s.STUDENT_ID AS ID,s.LAST_NAME||', '||s.FIRST_NAME AS TITLE
				FROM STUDENTS s,STUDENT_ENROLLMENT ssm
				WHERE s.STUDENT_ID=ssm.STUDENT_ID
				AND " . str_replace( 'SCHOOL_ID', 'ssm.SCHOOL_ID', $extra_schools ) . " ssm.SYEAR='" . UserSyear() . "'
				AND ('".DBDate()."' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL) " .
				$extra .
				' ORDER BY LAST_NAME,FIRST_NAME' ) );
		}
		elseif ( $_REQUEST['breakdown'] == 'age' )
		{
			$var = 'age';

			$schools = mb_substr( str_replace( ",", "','", User( 'SCHOOLS' ) ), 2, -2 );

			if ( ! isset( $_REQUEST['_search_all_schools'] )
				|| $_REQUEST['_search_all_schools'] != 'Y' )
			{
				$extra_schools = "SCHOOL_ID='" . UserSchool() . "' AND ";
			}
			elseif ( $schools )
			{
				$extra_schools = "SCHOOL_ID IN (" . $schools . ") AND ";
			}

			// http://www.sqlines.com/postgresql/how-to/datediff
			// SELECT (DATE_PART('day', CURRENT_DATE::timestamp - '2011-10-02'::timestamp) / 365.25)::int;
			$group_RET = DBGet( DBQuery( "SELECT DISTINCT( (DATE_PART('day', CURRENT_TIMESTAMP - s.CUSTOM_200000004::timestamp) / 365.25)::int ) AS AGE
				FROM STUDENTS s,STUDENT_ENROLLMENT ssm
				WHERE s.STUDENT_ID=ssm.STUDENT_ID
				AND " . str_replace( 'SCHOOL_ID', 'ssm.SCHOOL_ID', $extra_schools ) . " ssm.SYEAR='" . UserSyear() . "'
				ORDER BY AGE" ) );

			foreach ( (array) $group_RET as $age )
			{
				if ( $age['AGE'] )
				{
					$i++;

					$group[ $i ]['ID'] = $age['AGE'];

					$group[ $i ]['TITLE'] = sprintf( dgettext( 'Reports', '%s years' ), $age['AGE'] );
				}
			}
		}
		elseif ( mb_substr( $_REQUEST['breakdown'], 0, 6 ) === 'CUSTOM' )
		{
			/*if ( $_REQUEST['breakdown'] === 'CUSTOM_44' ) // RosarioSIS?
			{
				for ( $i = 1; $i <= 15; $i++ )
				{
					$_REQUEST['screen'][ $i ]['_search_all_schools'] = 'Y';
				}
			}*/

			$var = $_REQUEST['breakdown'];

			$field_id = mb_substr( $_REQUEST['breakdown'], 7 );

			$select_options_RET = DBGet( DBQuery( "SELECT SELECT_OPTIONS
				FROM CUSTOM_FIELDS WHERE ID='" . $field_id . "'" ) );

			$options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $select_options_RET[1]['SELECT_OPTIONS'] ) );

			// Add No Value.
			$group[0] = array( 'ID' => '!', 'TITLE' => _( 'No Value' ) );

			foreach ( (array) $options as $option )
			{
				$i++;

				$group[ $i ] = array( 'ID' => $option, 'TITLE' => $option );
			}
		}
		// End common part.
	}

	if ( $modfunc === 'XMLHttpRequest' )
	{
		if ( isset( $_REQUEST['breakdown'] ) )
		{
			foreach ( (array) $group as $value )
			{
				$num = 1;

				/*if ( isset( $_REQUEST['screen'] ) )
				{
					for ( $i = 1; $i <= 15; $i++ )
					{
						if ( mb_substr( $var, 0, 6 ) === 'CUSTOM' )
						{
							$_REQUEST['screen'][ $i ]['cust'][ $var ] = $value['ID'];
						}
						else
							$_REQUEST['screen'][ $i ][ $var ] = $value['ID'];
					}
				}*/

				// Add breakdown to each screen.
				foreach ( (array) $_REQUEST['screen'] as $key_num => $values )
				{
					if ( mb_substr( $var, 0, 6 ) === 'CUSTOM' )
					{
						$_REQUEST['screen'][ $key_num ]['cust'][ $var ] = $value['ID'];
					}
					else
						$_REQUEST['screen'][ $key_num ][ $var ] = $value['ID'];
				}

				// Build breakdown fake $_REQUEST var for appendSQL() if no screens.
				if ( mb_substr( $var, 0, 6 ) === 'CUSTOM' )
				{
					$_REQUEST['cust'][ $var ] = $value['ID'];
				}
				else
				{
					$_REQUEST[ $var ] = $value['ID'];
				}

				$val = eval( $query );

				// Float.
				if ( mb_strpos( $val, '.' ) !== false )
				{
					$val = ltrim( round( $val, 3 ), '0' );
				}

				$results .= '<result><id>' . $value['TITLE'] . '</id><title>' . $val . '</title></result>';
			}
		}
		else
		{
			$val = eval( $query );

			if ( mb_strpos( $val, '.' ) !== false )
			{
				$val = ltrim( round( $val, 3 ), '0' );
			}

			$results .= '<result><id>~</id><title>' . $val . '</title></result>';
		}
	}
	elseif ( $modfunc === 'echoXMLHttpRequest' )
	{
		if ( isset( $_REQUEST['breakdown'] ) )
		{
			$RET = array();

			foreach ( (array) $group as $value )
			{
				$num = 1;

				$row++;

				/*if ( isset( $_REQUEST['screen'] ) )
				{
					for ( $i = 1; $i <= 15; $i++ )
					{
						if ( mb_substr( $var, 0, 6 ) === 'CUSTOM' )
						{
							$_REQUEST['screen'][ $i ]['cust'][ $var ] = $value['ID'];
						}
						else
							$_REQUEST['screen'][ $i ][ $var ] = $value['ID'];
					}
				}*/

				// Add breakdown to each screen.
				foreach ( (array) $_REQUEST['screen'] as $key_num => $values )
				{
					if ( mb_substr( $var, 0, 6 ) === 'CUSTOM' )
					{
						$_REQUEST['screen'][ $key_num ]['cust'][ $var ] = $value['ID'];
					}
					else
						$_REQUEST['screen'][ $key_num ][ $var ] = $value['ID'];
				}

				// Build breakdown fake $_REQUEST var for appendSQL().
				if ( mb_substr( $var, 0, 6 ) === 'CUSTOM' )
				{
					$_REQUEST['cust'][ $var ] = $value['ID'];
				}
				else
				{
					$_REQUEST[ $var ] = $value['ID'];
				}

				$val = eval( $query );

				if ( mb_strpos( $val, '.' ) !== false )
				{
					$val = ltrim( round( $val, 3 ), '0' );
				}

				$RET[ $row ] = array( 'CATEGORY' => $value['TITLE'], 'VALUE' => $val );

				if ( $_REQUEST['graph']
					&& ( ! isset( $_ROSARIO['_createRCGraphs_max'] )
						|| $_ROSARIO['_createRCGraphs_max'] < $val ) )
				{
					$_ROSARIO['_createRCGraphs_max'] = $val;
				}
			}

			if ( $_REQUEST['breakdown'] === 'school' )
			{
				$cat_column = _( 'School' );
			}
			elseif ( $_REQUEST['breakdown'] === 'grade' )
			{
				$cat_column = _( 'Grade Level' );
			}
			elseif ( $_REQUEST['breakdown'] === 'age' )
			{
				$cat_column = _( 'Age' );
			}
			elseif ( $_REQUEST['breakdown'] === 'stuid' )
			{
				$cat_column = sprintf( _( '%s ID' ), Config( 'NAME' ) );
			}
			// Custom field.
			else
			{
				$field_id = mb_substr( $_REQUEST['breakdown'], 7 );

				$custom_RET = DBGet( DBQuery( "SELECT TITLE
					FROM CUSTOM_FIELDS WHERE ID='" . $field_id . "'" ) );

				$cat_column = $custom_RET[1]['TITLE'];
			}

			$columns = array( 'CATEGORY' => $cat_column, 'VALUE' => $_REQUEST['calc_title'] );

			if ( isset( $_REQUEST['graph'] ) )
			{
				_createRCGraphs( $RET );
			}

			$_REQUEST = $_runCalc_start_REQUEST;

			ob_start();

			ListOutput( $RET, $columns, '.', '.', array(), array(), array( 'search' => false ) );

			$results = ob_get_clean();
		}
		else
		{
			$val = eval( $query );

			if ( mb_strpos( $val, '.' ) !== false )
			{
				$val = ltrim( round( $val, 3 ), '0' );
			}

			$results = $val;
		}
	}

	return $results;
}


/**
 * Make Search Input
 *
 * @param  string $field text|numeric|select|date|radio|grade|school|timespan|test_no|other.
 *
 * @return string Search Input HTML
 */
function _makeSearchInput( $field )
{
	$div = false;

	$value = ( $_REQUEST['bottom_back'] == 'true'
		&& isset( $_SESSION['_REQUEST_vars']['cust'][ $field['COLUMN_NAME'] ] ) ) ?
		$_SESSION['_REQUEST_vars']['cust'][ $field['COLUMN_NAME'] ] :
		'';


	switch ( $field['TYPE'] )
	{
		case 'text':

			/*return "<INPUT type=text name=cust[{$field[COLUMN_NAME]}] size=30".(($_REQUEST['bottom_back']=='true' && $_SESSION['_REQUEST_vars']['cust'][$field['COLUMN_NAME']])?' value="'.$_SESSION['_REQUEST_vars']['cust'][$field['COLUMN_NAME']].'"':'').">";*/

			return TextInput(
				$value,
				'cust[' . $field['COLUMN_NAME'] . ']',
				'size="20"',
				$div
			);
		break;

		case 'numeric':

			/*return "<small>Between</small> <INPUT type=text name=cust_begin[{$field[COLUMN_NAME]}] size=3 maxlength=11".(($_REQUEST['bottom_back']=='true' && $_SESSION['_REQUEST_vars']['cust'][$field['COLUMN_NAME']])?' value="'.$_SESSION['_REQUEST_vars']['cust_begin'][$field['COLUMN_NAME']].'"':'')."> <small>&amp;</small> <INPUT type=text name=cust_end[{$field[COLUMN_NAME]}] size=3 maxlength=11".(($_REQUEST['bottom_back']=='true' && $_SESSION['_REQUEST_vars']['cust'][$field['COLUMN_NAME']])?' value="'.$_SESSION['_REQUEST_vars']['cust_end'][$field['COLUMN_NAME']].'"':'').">";*/

			return '<small>' . _( 'Between' ) . '</small> ' .
				TextInput(
					( $_REQUEST['bottom_back'] == 'true'
						&& isset( $_SESSION['_REQUEST_vars']['cust_begin'][ $field['COLUMN_NAME'] ] ) ) ?
						$_SESSION['_REQUEST_vars']['cust_begin'][ $field['COLUMN_NAME'] ] :
						'',
					'cust_begin[' . $field['COLUMN_NAME'] . ']',
					'',
					'size="3" maxlength="11"',
					$div
				) . '<small>&amp;</small>' .
				TextInput(
					( $_REQUEST['bottom_back'] == 'true'
						&& isset( $_SESSION['_REQUEST_vars']['cust_end'][ $field['COLUMN_NAME'] ] ) ) ?
						$_SESSION['_REQUEST_vars']['cust_end'][ $field['COLUMN_NAME'] ] :
						'',
					'cust_end[' . $field['COLUMN_NAME'] . ']',
					'',
					'size="3" maxlength="11"',
					$div
				);

		break;

		case 'select':

			$options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $field['SELECT_OPTIONS'] ) );

			/*if($_REQUEST['bottom_back']=='true' && $_SESSION['_REQUEST_vars']['cust'][$field['COLUMN_NAME']])
				$bb_option = $_SESSION['_REQUEST_vars']['cust'][$field['COLUMN_NAME']];
			else
				$bb_option = '';
			$return = "<SELECT name=cust[{$field[COLUMN_NAME]}] style='max-width:250;'><OPTION value=''>N/A</OPTION><OPTION value='!'".($bb_option=='!'?' SELECTED':'').">No Value</OPTION>";

			foreach ( (array) $options as $option )
			{
				//$return .= "<OPTION value=\"$option\"".(($field['COLUMN_NAME']=='CUSTOM_44' && $field['TITLE']=='District' && $option==$_SESSION['district'])?' SELECTED':'').($bb_option==$option?' SELECTED':'').">$option</OPTION>";
				$return .= '<option value="' . $option . '"' . ( $bb_option == $option ? ' selected' : '' ) . '>' .
					$option . '</option>';
			}

			$return .= '</SELECT>';*/

			foreach ( (array) $options as $option )
			{
				$options_with_keys[ $option ] = $option;
			}

			return SelectInput(
				$value,
				'cust[' . $field['COLUMN_NAME'] . ']',
				'',
				array( '!' => _( 'No Value' ) ) + $options_with_keys,
				'N/A',
				'style="max-width:250px;"',
				$div
			);

		break;

		case 'date':

			return '<small>' . _( 'Between' ) . '</small> '.
				PrepareDate(
					$value, // Was $bb_option?
					'_cust_begin[' . $field['COLUMN_NAME'] . ']',
					true,
					array( 'short' => true, 'C' => false )
				) .
				' <small>&amp;</small> ' .
				PrepareDate(
					'',
					'_cust_end[' . $field['COLUMN_NAME'] . ']',
					true,
					array( 'short' => true, 'C' => false )
				);

		break;

		case 'radio':

			/*return "<table clsss=cellpadding=0 cellspacing=0><tr><td width=30 align=center>
				<input name='cust[{$field[COLUMN_NAME]}]' type='radio' value='Y'".(($_REQUEST['bottom_back']=='true' && $_SESSION['_REQUEST_vars']['cust'][$field['COLUMN_NAME']]=='Y')?' CHECKED':'')." /> Yes
				</td><td width=25 align=center>
				<input name='cust[{$field[COLUMN_NAME]}]' type='radio' value='N'".(($_REQUEST['bottom_back']=='true' && $_SESSION['_REQUEST_vars']['cust'][$field['COLUMN_NAME']])?' CHECKED':'')." /> No
				</td></tr></table>";*/

			return RadioInput(
				$value,
				'cust[' . $field['COLUMN_NAME'] . ']',
				$title = '',
				array( 'Y' => _( 'Yes' ), 'N' => _( 'No' ) ),
				false,
				'',
				$div
			);

		break;

		case 'grade':

			$grades_RET = DBGet( DBQuery( "SELECT DISTINCT TITLE,ID,SORT_ORDER
				FROM SCHOOL_GRADELEVELS
				WHERE SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SORT_ORDER" ) );

			/*$return = '<SELECT name="grade"><OPTION value=""></OPTION>';
			foreach ($grades_RET as $grade)
				$return .= "<OPTION value=" . $grade['ID'] . ">".$grade['TITLE'].'</OPTION>';
			$return .= '</SELECT>';*/

			$grade_options = array();

			foreach ( (array) $grades_RET as $grade )
			{
				$grade_options[ $grade['ID'] ] = $grade['TITLE'];
			}

			return SelectInput(
				'',
				'grade',
				'',
				$grade_options,
				'N/A',
				'',
				$div
			);

		break;

		case 'schools':

			return CheckboxInput(
				'',
				'_search_all_schools',
				_( 'Search All Schools' ),
				'',
				true
			);

		break;

		case 'timespan':

			$start_date = date( 'Y-m' ) . '-01';

			$end_date = DBDate();

			return '<small>' . _( 'Between' ) . '</small> ' .
				PrepareDate(
					$start_date,
					'_start',
					true,
					array( 'short' => true, 'C' => false )
				) . ' <small>&amp;</small> ' .
				PrepareDate(
					$end_date,
					'_end',
					true,
					array( 'short' => true, 'C' => false )
				);

		break;

		/*case 'test_no':

			$select = SelectInput(
				'',
				'test_no[]',
				'',
				array(
					'1' => 1,
					'2' => 2,
					'3' => 3,
					'4' => 4,
					'5' => 5,
					'6' => 6,
					'7' => 7,
					'8' => 8,
					'9' => 9,
					'10' => 10,
					'0' => _( 'Final' ),
				),
				'N/A',
				'',
				$div
			);

			$select = "<select name='test_no[]'>";
			$vals = array('1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,'10'=>10,'0'=>'Final');
			$select .= '<OPTION value="">N/A</OPTION>';
			foreach ($vals as $i=>$val)
				$select .= "<OPTION value=$i>".$val.'</OPTION>';
			$select .= '</SELECT>';

			return '<small>' . _( 'Test Number' ) . '</small> ' . $select;

		break;*/

		case 'other':

			return '<input type="text" name="' . $field['COLUMN_NAME'] . '" size="20" />';

		break;
	}

}

function _getResults( $type, $number, $index = '' )
{
	global $num,
		$remote_type;

	$type = trim( $type );

	$start_REQUEST = $_REQUEST;

	if ( isset( $_REQUEST['screen'][ $num ] ) )
	{
		$_REQUEST = $_REQUEST['screen'][ $num ];
	}

	foreach ( (array) $_REQUEST as $key => $value )
	{
		// Is array
		// for example: ['cust[CUSTOM_XXX]'] or ['month_cust_begin[CUSTOM_XXX]']
		// => ['cust']['CUSTOM_XXX']
		if ( mb_strpos( $key, 'cust' ) !== false &&
			mb_strpos( $key, '[' ) !== false )
		{
			$_REQUEST[ mb_substr( $key, 0, mb_strpos( $key, '[' ) ) ][ mb_substr( $key, mb_strpos( $key, '[' ) + 1 ) ] = $value;

			unset( $_REQUEST[ $key ] );
		}
	}

	$min_max_date = DBGet( DBQuery( "SELECT to_char(min(SCHOOL_DATE),'dd-MON-yy') AS MIN_DATE,
		to_char(max(SCHOOL_DATE),'dd-MON-yy') AS MAX_DATE
		FROM ATTENDANCE_CALENDAR
		WHERE SYEAR='" . UserSyear() . "'" ) );

	if ( isset( $_REQUEST['year_start'] )
		&& isset( $_REQUEST['month_start'] )
		&& isset( $_REQUEST['day_start'] ) )
	{
		$start_date = $_REQUEST['year_start'] . '-' .
			$_REQUEST['month_start'] . '-' .
			$_REQUEST['day_start'];
	}
	else
		$start_date = $min_max_date[1]['MIN_DATE'];

	if ( isset( $_REQUEST['year_end'] )
		&& isset( $_REQUEST['month_end'] )
		&& isset( $_REQUEST['day_end'] ) )
	{
		$end_date = $_REQUEST['year_end'] . '-' .
			$_REQUEST['month_end'] . '-' .
			$_REQUEST['day_end'];
	}
	else
		$end_date = $min_max_date[1]['MAX_DATE'];

	$extra = appendSQL( '' );

	if ( isset( $_REQUEST['school'] ) )
	{
		$extra .= " AND ssm.SCHOOL_ID = '" . $_REQUEST['school'] . "' ";
	}

	$extra .= CustomFields( 'where' );

	if ( isset( $_REQUEST['age'] ) )
	{
		// We could have used AGE() function since PostgreSQL 8.4.
		$extra .= " AND (DATE_PART('day', CURRENT_TIMESTAMP - s.CUSTOM_200000004::timestamp) / 365.25)::int = " . $_REQUEST['age'] . ' ';
	}

	$schools = mb_substr( str_replace( ",", "','", User( 'SCHOOLS' ) ), 2, -2 );

	if ( isset( $_REQUEST['school'] ) )
	{
		$extra_schools = '';
	}
	elseif ( ! isset( $_REQUEST['_search_all_schools'] )
		|| $_REQUEST['_search_all_schools'] != 'Y' )
	{
		$extra_schools = "SCHOOL_ID='" . UserSchool() . "' AND ";
	}
	elseif ( $schools )
	{
		$extra_schools = "SCHOOL_ID IN (" . $schools . ") AND ";
	}

	$array = array();

	/*if(mb_substr($type,0,7)=='orchard')
	{
		$test_title = mb_substr($type,9,-6);
		$type = 'orchard_test';
	}*/

	switch ( $type )
	{
		case 'present':

			if ( ! mb_strpos( $extra, 'GROUP' ) )
			{
				$extra .= " GROUP BY ad.SCHOOL_DATE";
			}

			$present_RET = DBGet( DBQuery( "SELECT ad.SCHOOL_DATE,COALESCE(sum(ad.STATE_VALUE),0) AS STATE_VALUE
				FROM ATTENDANCE_DAY ad,STUDENT_ENROLLMENT ssm,STUDENTS s
				WHERE s.STUDENT_ID=ssm.STUDENT_ID
				AND " . str_replace( 'SCHOOL_ID', 'ssm.SCHOOL_ID', $extra_schools ) . " ad.STUDENT_ID=ssm.STUDENT_ID
				AND ssm.SYEAR='" . UserSyear() . "'
				AND ad.SYEAR=ssm.SYEAR
				AND ad.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
				AND (ad.SCHOOL_DATE BETWEEN ssm.START_DATE AND ssm.END_DATE
					OR (ssm.END_DATE IS NULL AND ssm.START_DATE <= ad.SCHOOL_DATE)) " .
				$extra ) );

			foreach ( (array) $present_RET as $present )
			{
				if ( $index )
				{
					$array[ $present[ $index ] ] = $present['STATE_VALUE'];
				}
				else
					$array[] = $present['STATE_VALUE'];
			}

		break;

		case 'absent':

			if ( ! mb_strpos( $extra, 'GROUP' ) )
			{
				$extra .= " GROUP BY ad.SCHOOL_DATE";
			}

			$absent_RET = DBGet( DBQuery( "SELECT ad.SCHOOL_DATE,COALESCE(sum(ad.STATE_VALUE-1)*-1,0) AS STATE_VALUE
				FROM ATTENDANCE_DAY ad,STUDENT_ENROLLMENT ssm,STUDENTS s
				WHERE s.STUDENT_ID=ssm.STUDENT_ID
				AND " . str_replace( 'SCHOOL_ID', 'ssm.SCHOOL_ID', $extra_schools ) . " ad.STUDENT_ID=ssm.STUDENT_ID
				AND ssm.SYEAR='" . UserSyear() . "'
				AND ad.SYEAR=ssm.SYEAR
				AND ad.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
				AND (ad.SCHOOL_DATE BETWEEN ssm.START_DATE AND ssm.END_DATE
					OR (ssm.END_DATE IS NULL AND ssm.START_DATE <= ad.SCHOOL_DATE)) " .
				$extra ) );

			foreach ( (array) $absent_RET as $absent )
			{
				if ( $index )
				{
					$array[ $absent[ $index ] ] = $absent['STATE_VALUE'];
				}
				else
					$array[] = $absent['STATE_VALUE'];
			}

		break;

		case 'enrolled':

			if ( ! mb_strpos( $extra, 'GROUP' ) )
			{
				$extra .= " GROUP BY ac.SCHOOL_DATE";
			}

			$enrolled_RET = DBGet( DBQuery( "SELECT ac.SCHOOL_DATE,count(*) AS STUDENTS
				FROM STUDENT_ENROLLMENT ssm,ATTENDANCE_CALENDAR ac,STUDENTS s
				WHERE s.STUDENT_ID=ssm.STUDENT_ID
				AND ssm.SYEAR='" . UserSyear() . "'
				AND ac.SYEAR=ssm.SYEAR
				AND ac.CALENDAR_ID=ssm.CALENDAR_ID
				AND " . str_replace( 'SCHOOL_ID', 'ssm.SCHOOL_ID', $extra_schools ) . " ssm.SCHOOL_ID=ac.SCHOOL_ID
				AND (ac.SCHOOL_DATE BETWEEN ssm.START_DATE AND ssm.END_DATE OR
					(ssm.END_DATE IS NULL AND ssm.START_DATE <= ac.SCHOOL_DATE))
				AND ac.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "' " .
				$extra ) );

			foreach ( (array) $enrolled_RET as $enrolled )
			{
				if ( $index )
				{
					$array[ $enrolled[ $index ] ] = $enrolled['STUDENTS'];
				}
				else
					$array[] = $enrolled['STUDENTS'];
			}

		break;

		case 'student id':

			$student_id_RET = DBGet( DBQuery( "SELECT ssm.STUDENT_ID
				FROM STUDENT_ENROLLMENT ssm,STUDENTS s
				WHERE s.STUDENT_ID=ssm.STUDENT_ID
				AND " . str_replace( 'SCHOOL_ID', 'ssm.SCHOOL_ID', $extra_schools ) . " ssm.SYEAR='" . UserSyear() . "'
				AND ('" . DBDate() . "' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL) " .
				$extra ) );

			foreach ( (array) $student_id_RET as $student_id )
			{
				if ( $index )
				{
					$array[ $student_id[ $index ] ] = $student_id['STUDENT_ID'];
				}
				else
					$array[] = $student_id['STUDENT_ID'];
			}

		break;

		/*case 'orchard_test':
			$schools = substr(str_replace(",","','",User('SCHOOLS')),2,-2);

			if($_REQUEST['school'])
				$extra_schools = '';
			elseif($_REQUEST['_search_all_schools']!='Y')
				$extra_schools = " AND SCHOOL_ID='".UserSchool()."' ";
			elseif($schools)
				$extra_schools = " AND SCHOOL_ID IN (".$schools.") ";
			else
				$extra_schools = '';

			$RET = DBGet(DBQuery("SELECT ssm.STUDENT_ID FROM STUDENT_ENROLLMENT ssm,STUDENTS s WHERE s.STUDENT_ID=ssm.STUDENT_ID AND ssm.SYEAR='".UserSyear()."' ".str_replace('SCHOOL_ID','ssm.SCHOOL_ID',$extra_schools)." ".$extra));
			if(count($RET))
			{
				foreach ($RET as $student)
				{
					$student_ids .= $student['STUDENT_ID'].',';
				}
				$student_ids = substr($student_ids,0,-1);

				$students_RET = DBGet(DBQuery("SELECT id,SCHOOL_ID FROM orchardstudent where externalid IN (".$student_ids.")",'mysql'),array(),array('SCHOOL_ID'));
				$remote_type = false;
			}
			else
				$students_RET = array();
			if(count($students_RET))
			{
				$student_ids = array();
				foreach ($students_RET as $school_id=>$students)
				{
					foreach ($students as $student)
						$student_ids[$student['SCHOOL_ID']] .= $student['ID'].',';
				}
				foreach ($student_ids as $i=>$value)
					$student_ids[$i] = substr($value,0,-1);
				$tests_RET = DBGet(DBQuery("SELECT testid from orchardtest where name like '%$test_title%'",'mysql'));
				foreach ($tests_RET as $test)
					$test_ids .= $test['TESTID'].',';
				$test_ids = substr($test_ids,0,-1);
				if(substr($start_date,7,2)<50)
					$start = '20'.substr($start_date,7,2).MonthNWSwitch(substr($start_date,3,3),'tonum').substr($start_date,0,2).'000000';
				else
					$start = '19'.substr($start_date,7,2).MonthNWSwitch(substr($start_date,3,3),'tonum').substr($start_date,0,2).'000000';
				$end = '20'.substr($end_date,7,2).MonthNWSwitch(substr($end_date,3,3),'tonum').substr($end_date,0,2).'999999';
				foreach ($student_ids as $school_id=>$student_ids_list)
				{
					$RET = DBGet(DBQuery("SELECT correct,total,studentid from orchardtestrecord where slgtime BETWEEN '$start' AND '$end' AND productcode IN ($test_ids) and studentid IN ($student_ids_list) AND SCHOOL_ID='$school_id' ORDER BY STUDENTID,SLGTIME ASC",'mysql'));
					$remote_type = false;
					$student_test_count = array();

					foreach ($RET as $i=>$value)
					{
						if(isset($_REQUEST['test_no']))
							$student_test_count[$value['STUDENTID']]++;
						if(isset($_REQUEST['test_no']) && in_array('0',$_REQUEST['test_no']) && $value['STUDENTID']!=$RET[($i+1)]['STUDENTID'])
						{
							if($index!='')
								$array[$value[$index]][] = ($value['CORRECT']*100)/$value['TOTAL'];
							else
								$array[] = ($value['CORRECT']*100)/$value['TOTAL'];
						}
						elseif(!isset($_REQUEST['test_no']) || in_array($student_test_count[$value['STUDENTID']],$_REQUEST['test_no']))
						{
							if($index!='')
								$array[$value[$index]][] = ($value['CORRECT']*100)/$value['TOTAL'];
							else
								$array[] = ($value['CORRECT']*100)/$value['TOTAL'];
						}
					}
				}
			}
			else
				$array = array();
		break;*/

		/*case 'time on task':
			$schools = substr(str_replace(",","','",User('SCHOOLS')),2,-2);

			if($_REQUEST['school'])
				$extra_schools = '';
			elseif($_REQUEST['_search_all_schools']!='Y')
				$extra_schools = " AND SCHOOL_ID='".UserSchool()."' ";
			elseif($schools)
				$extra_schools = " AND SCHOOL_ID IN (".$schools.") ";
			else
				$extra_schools = '';

			$RET = DBGet(DBQuery("SELECT ssm.STUDENT_ID FROM STUDENT_ENROLLMENT ssm,STUDENTS s WHERE s.STUDENT_ID=ssm.STUDENT_ID AND ssm.SYEAR='".UserSyear()."' ".str_replace('SCHOOL_ID','ssm.SCHOOL_ID',$extra_schools)." ".$extra));
			if(count($RET))
			{
				foreach ($RET as $student)
				{
					$student_ids .= $student['STUDENT_ID'].',';
				}
				$student_ids = substr($student_ids,0,-1);

				$students_RET = DBGet(DBQuery("SELECT id,SCHOOL_ID FROM orchardstudent where externalid IN (".$student_ids.")",'mysql'),array(),array('SCHOOL_ID'));
				$remote_type = false;
			}
			else
				$students_RET = array();
			if(count($students_RET))
			{
				$student_ids = array();
				foreach ($students_RET as $school_id=>$students)
				{
					foreach ($students as $student)
						$student_ids[$student['SCHOOL_ID']] .= $student['ID'].',';
				}
				foreach ($student_ids as $i=>$value)
					$student_ids[$i] = substr($value,0,-1);
				foreach ($student_ids as $school_id=>$student_ids_list)
				{
					$RET = DBGet(DBQuery("SELECT studentid,sum(tot) as tot from orchardtimeontask where studentid IN ($student_ids_list) and SCHOOL_ID='$school_id' group by studentid",'mysql'));
					$remote_type = false;

					foreach ($RET as $value)
						$array[] = $value['TOT'];
				}
			}
			else
				$array = array();
		break;*/

	}

	$_REQUEST = $start_REQUEST;

	//var_dump($array);

	// Screen++.
	$num++;

	return $array;
}


/**
 * Make Text Input
 *
 * Local function
 *
 * DBGet() callback function
 *
 * @global $THIS_RET Current Return row
 *
 * @param  string $value  Value.
 * @param  string $column 'TITLE'.
 *
 * @return string Text Input
 */
function _makeText( $value, $column )
{
	global $THIS_RET;

	return TextInput( $value, 'values[' . $THIS_RET['ID'] . '][' . $column . ']' );
}


/**
 * Make URL
 *
 * Local function
 *
 * DBGet() callback function
 *
 * @uses _makeScreens()
 *
 * @global $screen for _makeScreens()
 *
 * @param  string $value  Value.
 * @param  string $column 'URL'.
 *
 * @return string URL
 */
function _makeURL( $value, $column )
{
	global $screen,
		$_ROSARIO;

	/*$value = urldecode( $value );

	$start = mb_strpos( $value, 'query=' ) + 6;

	$url = $value;

	$vars = mb_substr( $url, ( mb_strpos( $url, '?' ) + 1 ) );

	$vars = str_replace( '&amp;', '&', $vars );

	$vars = explode( '&', $vars );

	$screen = array();

	foreach ( (array) $vars as $code )
	{
		// Array
		if ( mb_strpos( $code, '[' ) !== false )
		{
			$code = str_replace( 'cust[', 'cust][', $code );

			$code = str_replace( 'test_no[', 'test_no][', $code );

			$equals = strpos( $code, '=' );

			$code = "\$_REQUEST['" . mb_substr( $code, 0, $equals ) . "']='" . mb_substr( $code, $equals + 1 ) . "';";

			eval( $code );
		}
	}*/

	$url = urldecode( $value );

	$start = mb_strpos( $url, 'query=' ) + 6;

	$vars = mb_substr( $url, ( mb_strpos( $url, '?' ) + 1 ) );

	$modname = mb_substr( $url, 0, mb_strpos( $url, '?' ) );

	$vars = str_replace( '&amp;', '&', $vars );

	$vars = explode( '&', $vars );

	$save_REQUEST = $_REQUEST;

	$_REQUEST = array();

	foreach ( (array) $vars as $code )
	{
		$equals = mb_strpos( $code, '=' );

		if ( mb_strpos( $code, '[' ) !== false )
		{
			$code = "\$_REQUEST[" . preg_replace(
				'/([^]])\[/',
				'\1][',
				mb_substr( $code, 0, $equals )
			) . "='" . mb_substr( $code, $equals + 1 ) . "';";
		}
		else
		{
			$code = "\$_REQUEST['" . mb_substr( $code, 0, $equals ) . "']='" .
				mb_substr( $code, $equals + 1 ) . "';";
		}

		eval( $code );
	}

	$screens_titles = _makeScreens( mb_substr(
		$url,
		$start,
		( mb_strpos( $url, '<img' ) - $start )
	) );

	if ( $save_REQUEST['modname'] == 'Reports/Calculations.php' )
	{
		$_REQUEST = $save_REQUEST;
	}

	return $screens_titles;
}


/**
 * Create RC Graph
 * Adds a relevance bar after the result.
 *
 * Local function
 *
 * @see _getAJAXResults()
 *
 * @global $_ROSARIO uses $_ROSARIO['_createRCGraphs_max']
 *
 * @param  array $RET Results array
 */
function _createRCGraphs( & $RET )
{
	global $_ROSARIO;

	$last = count( $RET );

	$scale = ( 100 / $_ROSARIO['_createRCGraphs_max'] );

	for ( $i = 1; $i <= $last; $i++ )
	{
		$RET[ $i ]['VALUE'] = $RET[ $i ]['VALUE'] . '<!--' . ( (int) ( $RET[ $i ]['VALUE'] * $scale ) ) . '-->
			<div class="bar relevance" style="width: ' . ( (int) ( $RET[ $i ]['VALUE'] * $scale ) ) . 'px; display: inline-block;"></div>';
	}
}


/**
 * Make Screens
 *
 * Local function
 *
 * @see _makeURL()
 *
 * @static $fields_RET
 *
 * @param  string $equation Equation
 *
 * @return string           Equation
 */
function _makeScreens( $equation )
{
	static $fields_RET;

	$equation = mb_strtolower( stripslashes( $equation ) );

	$screen_count = 0;

	while ( $pos = mb_strpos( $equation, '<b>)</b>' ) )
	{
		$screen_count++;

		if ( isset( $_REQUEST['screen'][ $screen_count ] ) )
		{
			$screen = $_REQUEST['screen'][ $screen_count ];
		}
		else
			$screen = false;

		$extra = '';

		// Grade Level.
		if ( isset( $screen['grade'] ) )
		{
			$extra .= _( 'Grade Level' ) . ': ' . GetGrade( $screen['grade'] ) . '; ';
		}

		// All Schools.
		if ( isset( $screen['_search_all_schools'] ) )
		{
			$extra .= _( 'All Schools' ) . '; ';
		}

		// Student ID.
		if ( isset( $screen['stuid'] ) )
		{
			$extra .= _( 'Student ID' ) . ': ' . $screen['stuid'] . '; ';
		}

		// Last name (starts with, case insensitive).
		if ( isset( $screen['last'] ) )
		{
			$extra .= _( 'Last Name starts with' ) . ': ' . $screen['last'] . '; ';
		}

		// First name (starts with, case insensitive).
		if ( isset( $screen['first'] ) )
		{
			$extra .= _( 'First Name starts with' ) . ': ' . $screen['first'] . '; ';
		}

		// Date.
		if ( isset( $screen['month_start'] )
			&& isset( $screen['month_end'] ) )
		{
			$extra .= '<i class="size-1">' . _( 'Between' ) .
				ProperDate(
					$screen['day_start'] . '-' .
					$screen['month_start'] . '-' .
					$screen['year_start'],
					'short'
				) .	' &amp; ' .
				ProperDate(
					$screen['day_end'] . '-' .
					$screen['month_end'] . '-' .
					$screen['year_end'],
					'short'
				) . '</i>; ';
		}
		else


		// Test No.
		/*if ( isset( $screen[ $screen_count ]['test_no'] )
			&& in_array( '0', $screen[ $screen_count ]['test_no'] ) )
		{
			$extra .= '<i class="size-1">' . _( 'Final Test' ) . '</i>; ';
		}
		elseif ( isset( $screen[ $screen_count ]['test_no'] ) )
		{
			foreach ( (array)$screen[ $screen_count ]['test_no'] as $test_no )
			{
				$extra .= '<span class="size-1"><i>' . _( 'Test No.' ) . '</i>: ' .
					$test_no . '</span>; ';
			}
		}*/

		// Custom
		if ( isset( $screen['cust'] ) )
		{
			if ( ! $fields_RET )
			{
				$fields_RET = DBGet( DBQuery( "SELECT ID,TITLE
					FROM CUSTOM_FIELDS
					WHERE TYPE='select'
					ORDER BY TITLE" ) );
			}

			//foreach ($_REQUEST['screen'][$screen_count]['cust'] as $field=>$value)
			foreach ( (array) $fields_RET as $field )
			{
				if ( isset( $screen['cust'][ 'CUSTOM_' . $field['ID'] ] ) )
				{
					$extra .= $field['TITLE'] . ': ' . $screen['cust'][ 'CUSTOM_' . $field['ID'] ] . '; ';
				}
			}
		}

		if ( ! empty( $extra ) )
		{
			$extra = mb_substr( $extra, 0, -2 );
		}

		$equation = mb_substr( $equation, 0, $pos ) . $extra . ' <strong>)</strong>' .
			mb_substr( $equation, $pos + 8 );
	}

	return $equation;
}


/**
 * Average Calculus
 *
 * Local function
 *
 * @param  array $array Array of numbers. Defaults to null.
 *
 * @return float Average
 */
function _average( $array = null )
{
	$i = 0;

	if ( ! $array )
	{
		return 0;
	}

	foreach ( (array) $array as $elem )
	{
		$i++;

		$sum += $elem;
	}

	return $sum / $i;
}


/**
 * Sum Calculus
 *
 * Local function
 *
 * @param  array $array Array of numbers. Defaults to null.
 *
 * @return float Sum
 */
function _sum( $array = null )
{
	if ( ! $array )
	{
		return 0;
	}

	$sum = 0;

	foreach ( (array) $array as $elem )
	{
		$sum += $elem;
	}

	return $sum;
}


/**
 * Minimum Calculus
 *
 * Local function
 *
 * @param  array $array Array of numbers. Defaults to null.
 *
 * @return float Minimum
 */
function _min( $array = null )
{
	if ( ! $array )
	{
		return 0;
	}

	$min = $array[0];

	foreach ( (array) $array as $elem )
	{
		if ( $elem < $min )
		{
			$min = $elem;
		}
	}

	return $min;
}


/**
 * Maximum Calculus
 *
 * Local function
 *
 * @param  array $array Array of numbers. Defaults to null.
 *
 * @return float Maximum
 */
function _max( $array = null )
{
	if ( ! $array )
	{
		return 0;
	}

	$max = $array[0];

	foreach ( (array) $array as $elem )
	{
		if ( $elem > $max )
		{
			$max = $elem;
		}
	}

	return $max;
}


/**
 * Sum min Calculus
 *
 * Local function
 *
 * @uses _sum()
 *
 * @param  array $array Array of Student IDs containing numbers. Defaults to null.
 *
 * @return float Sum min
 */
function _su0( $arr = null )
{
	/*print_r( $arr );

	echo "\n\n";*/

	if ( ! $array )
	{
		return 0;
	}

	foreach ( (array) $arr as $student_id => $array )
	{
		$min = is_array( $array ) ? $array[ key( $array ) ] : $array;

		foreach ( (array) $array as $elem )
		{
			if ( $elem < $min )
			{
				$min = $elem;
			}
		}

		$total_array[] = $min;
	}

	return _sum( $total_array );
}


/**
 * Sum max Calculus
 *
 * Local function
 *
 * @uses _sum()
 *
 * @param  array $array Array of Student IDs containing numbers. Defaults to null.
 *
 * @return float Sum max
 */
function _su1( $arr = null )
{
	/*print_r( $arr );

	echo "\n\n";*/

	if ( ! $array )
	{
		return 0;
	}

	foreach ( (array) $arr as $student_id => $array )
	{
		$max = is_array( $array ) ? $array[ key( $array ) ] : $array;

		foreach ( (array)$array as $elem )
		{
			if ( $elem > $max )
			{
				$max = $elem;
			}
		}

		$total_array[] = $max;
	}

	return _sum( $total_array );
}


/**
 * Average min Calculus
 *
 * Local function
 *
 * @uses _average()
 *
 * @param  array $array Array of Student IDs containing numbers. Defaults to null.
 *
 * @return float Average min
 */
function _avg0( $arr = null )
{
	/*print_r( $arr );

	echo "\n\n";*/

	if ( ! $array )
	{
		return 0;
	}

	foreach ( (array) $arr as $student_id => $array )
	{
		$min = is_array( $array ) ? $array[ key( $array ) ] : $array;

		foreach ( (array) $array as $elem )
		{
			if ( $elem < $min )
			{
				$min = $elem;
			}
		}

		$total_array[] = $min;
	}

	return _average( $total_array );
}


/**
 * Average max Calculus
 *
 * Local function
 *
 * @uses _average()
 *
 * @param  array $array Array of Student IDs containing numbers. Defaults to null.
 *
 * @return float Average max
 */
function _avg1( $arr = null )
{
	/*print_r( $arr );

	echo "\n\n";*/

	if ( ! $array )
	{
		return 0;
	}

	foreach ( (array) $arr as $student_id => $array )
	{
		$max = is_array( $array ) ? $array[ key( $array ) ] : $array;

		foreach ( (array) $array as $elem )
		{
			if ( $elem > $max )
			{
				$max = $elem;
			}
		}

		$total_array[] = $max;
	}

	return _average( $total_array );
}

