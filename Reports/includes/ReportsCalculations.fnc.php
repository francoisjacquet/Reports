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
	$query = preg_replace( '/<span id="?/', '<', $query );

	$query = str_replace( '</span>', '', $query );

	$query = preg_replace( '/<img src=[^>]+>/', '', $query );

	$query = preg_replace( '/"?>/', '>', $query );

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

	$query = preg_replace(
		"/([a-z_]+)\([ ]*<[a-z]+([0-9]+)>([a-z: ]+)<[a-z0-9]+>[ ]*\)/",
		"\\1(_getResults('\\3','\\2'))",
		$query
	);

	$query = preg_replace(
		"/([a-z01_]+)\([ ]*<[a-z]+([0-9]+)>([a-z: ]+)<[a-z0-9]+>[ ]*\)/",
		"\\1(_getResults('\\3','\\2','STUDENT_ID'))",
		$query
	);

	$query = preg_replace( '/<start[0-9]+>/', '', $query );

	$query = preg_replace( '/<end[0-9]+> */', '', $query );

	if ( empty( trim( $query ) ) )
	{
		$query = 'false';
	}

	$query = '$result = ' . $query . ';if(!$result) return 0; else return $result;';

	/*print_r( $_REQUEST );
	echo '<br />EVAL QUERY: ';
	echo '<br />RESULTS: ' . $result;
	echo '<br />AVG PRES: ' . _average( _getResults( 'present', '2' ) );
	echo '<pre>' . str_replace( '<', '&lt;', str_replace( '>', '&gt;', $query ) ).'</pre>';*/

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
		$_runCalc_start_REQUEST;

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

			if ( isset( $_REQUEST['_search_all_schools'] )
				&& $_REQUEST['_search_all_schools'] != 'Y' )
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
				if ( mb_substr( $key, 0, 5 ) === 'cust[' )
				{
					$_REQUEST['cust'][ mb_substr( $key, 5 ) ] = $value;

					unset( $_REQUEST[ $key ] );
				}
				elseif ( mb_substr( $key, 0, 8 ) === 'test_no[' )
				{
					$_REQUEST['test_no'][ mb_substr( $key, 8 ) ] = $value;

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
				AND " . str_replace( 'SCHOOL_ID', 'ssm.SCHOOL_ID', $extra_schools ) . " ssm.SYEAR='" . UserSyear() . "' " .
				$extra .
				' ORDER BY LAST_NAME,FIRST_NAME' ) );
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

			$options = explode( '<br />', nl2br( $select_options_RET[1]['SELECT_OPTIONS'] ) );

			$group[0] = array();

			foreach ( (array) $options as $option )
			{
				$group[] = array( 'ID' => $option, 'TITLE' => $option );
			}

			unset( $group[0] );
		}
		// End common part.
	}

	if ( $modfunc === 'XMLHttpRequest' )
	{
		if ( isset( $_REQUEST['breakdown'] ) )
		{
			$start_num = $num;

			foreach ( (array) $group as $value )
			{
				if ( isset( $_REQUEST['screen'] ) )
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
				}
	/*			foreach($_REQUEST['screen'] as $key_num=>$values)
				{
					if(substr($var,0,6)=='CUSTOM')
						$_REQUEST['screen'][$key_num]['cust'][$var] = $value['ID'];
					else
						$_REQUEST['screen'][$key_num][$var] = $value['ID'];
				}
	*/
				$num = $start_num;

				$val = eval( $query );

				// Float
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

			$start_num = $num;

			foreach ( (array) $group as $value )
			{
				$row++;

				if ( isset( $_REQUEST['screen'] ) )
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
				}

				$num = $start_num;

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
				$cat_column = _( 'Grade' );
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

			$columns = array( 'CATEGORY' => $cat_column, 'VALUE' => $_ROSARIO['CalcTitle'] );

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
				'size="30"',
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

			$options = explode( '<br />', nl2br( $field['SELECT_OPTIONS'] ) );

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

			return SelectInput(
				$value,
				'cust[' . $field['COLUMN_NAME'] . ']',
				array( '!' => _( 'No Value' ) ) + $options,
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
			foreach($grades_RET as $grade)
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

			$start_date = '01-' . mb_strtoupper( date( 'M-y' ) );

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
			foreach($vals as $i=>$val)
				$select .= "<OPTION value=$i>".$val.'</OPTION>';
			$select .= '</SELECT>';

			return '<small>' . _( 'Test Number' ) . '</small> ' . $select;

		break;*/

		case 'other':

			return '<input type="text" name="' . $field['COLUMN_NAME'] . ' size="30" />';

		break;
	}

}

function _getResults( $type, $number, $index = '' )
{
	global $num,
		$remote_type;

	$type = trim( $type );

	$num++;

	$start_REQUEST = $_REQUEST;

	if ( isset( $_REQUEST['screen'] ) )
	{
		$_REQUEST = $_REQUEST['screen'][ $num ];
	}

	foreach ( (array) $_REQUEST as $key => $value )
	{
		if ( mb_substr( $key, 0, 5 ) == 'cust[' )
		{
			$_REQUEST['cust'][ mb_substr( $key, 5 ) ] = $value;

			unset( $_REQUEST[ $key ]);
		}
		elseif ( mb_substr( $key, 0, 8 ) == 'test_no[' )
		{
			$_REQUEST['test_no'][ mb_substr( $key, 8 ) ] = $value;

			unset( $_REQUEST[ $key ] );
		}
	}

	$min_max_date = DBGet( DBQuery( "SELECT to_char(min(SCHOOL_DATE),'dd-MON-yy') AS MIN_DATE,
		to_char(max(SCHOOL_DATE),'dd-MON-yy') AS MAX_DATE
		FROM ATTENDANCE_CALENDAR
		WHERE SYEAR='" . UserSyear() . "'" ) );

	if ( isset( $_REQUEST['day_start'] )
		&& isset( $_REQUEST['month_start'] )
		&& isset( $_REQUEST['year_start'] ) )
	{
		$start_date = $_REQUEST['day_start'] . '-' .
			$_REQUEST['month_start'] . '-' .
			$_REQUEST['year_start'];
	}
	else
		$start_date = $min_max_date[1]['MIN_DATE'];

	if ( isset( $_REQUEST['day_end'] )
		&& isset( $_REQUEST['month_end'] )
		&& isset( $_REQUEST['year_end'] ) )
	{
		$end_date = $_REQUEST['day_end'] . '-' .
			$_REQUEST['month_end'] . '-' .
			$_REQUEST['year_end'];
	}
	else
		$end_date = $min_max_date[1]['MAX_DATE'];

	$extra = appendSQL( '' );

	if ( isset( $_REQUEST['school'] ) )
	{
		$extra .= " AND ssm.SCHOOL_ID = '" . $_REQUEST['school'] . "' ";
	}

	$extra .= CustomFields( 'where' );

	$schools = mb_substr( str_replace( ",", "','", User( 'SCHOOLS' ) ), 2, -2 );

	if ( isset( $_REQUEST['school'] ) )
	{
		$extra_schools = '';
	}
	elseif ( isset( $_REQUEST['_search_all_schools'] )
		&& $_REQUEST['_search_all_schools'] != 'Y' )
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
				foreach($RET as $student)
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
				foreach($students_RET as $school_id=>$students)
				{
					foreach($students as $student)
						$student_ids[$student['SCHOOL_ID']] .= $student['ID'].',';
				}
				foreach($student_ids as $i=>$value)
					$student_ids[$i] = substr($value,0,-1);
				$tests_RET = DBGet(DBQuery("SELECT testid from orchardtest where name like '%$test_title%'",'mysql'));
				foreach($tests_RET as $test)
					$test_ids .= $test['TESTID'].',';
				$test_ids = substr($test_ids,0,-1);
				if(substr($start_date,7,2)<50)
					$start = '20'.substr($start_date,7,2).MonthNWSwitch(substr($start_date,3,3),'tonum').substr($start_date,0,2).'000000';
				else
					$start = '19'.substr($start_date,7,2).MonthNWSwitch(substr($start_date,3,3),'tonum').substr($start_date,0,2).'000000';
				$end = '20'.substr($end_date,7,2).MonthNWSwitch(substr($end_date,3,3),'tonum').substr($end_date,0,2).'999999';
				foreach($student_ids as $school_id=>$student_ids_list)
				{
					$RET = DBGet(DBQuery("SELECT correct,total,studentid from orchardtestrecord where slgtime BETWEEN '$start' AND '$end' AND productcode IN ($test_ids) and studentid IN ($student_ids_list) AND SCHOOL_ID='$school_id' ORDER BY STUDENTID,SLGTIME ASC",'mysql'));
					$remote_type = false;
					$student_test_count = array();

					foreach($RET as $i=>$value)
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
				foreach($RET as $student)
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
				foreach($students_RET as $school_id=>$students)
				{
					foreach($students as $student)
						$student_ids[$student['SCHOOL_ID']] .= $student['ID'].',';
				}
				foreach($student_ids as $i=>$value)
					$student_ids[$i] = substr($value,0,-1);
				foreach($student_ids as $school_id=>$student_ids_list)
				{
					$RET = DBGet(DBQuery("SELECT studentid,sum(tot) as tot from orchardtimeontask where studentid IN ($student_ids_list) and SCHOOL_ID='$school_id' group by studentid",'mysql'));
					$remote_type = false;

					foreach($RET as $value)
						$array[] = $value['TOT'];
				}
			}
			else
				$array = array();
		break;*/

	}

	$_REQUEST = $start_REQUEST;

	//var_dump($array);

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
	global $screen;

	$value = urldecode( $value );

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
	}

	return _makeScreens( substr(
		$value,
		$start,
		( mb_strpos( mb_strtolower( $value ), '<img' ) - $start )
	) );
}


/**
 * Create RC Graph
 *
 * Local function
 *
 * @see _getAJAXResults()
 *
 * @global $_ROSARIO uses $_ROSARIO['_createRCGraphs_max']
 *
 * @param  array $RET [description]
 *
 * @return [type]      [description]
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
 * @global $screen
 * @global $fields_RET, see Calculations.php
 *
 * @param  string $equation Equation
 *
 * @return string           Equation
 */
function _makeScreens( $equation )
{
	global $screen,
		$fields_RET;

	$equation = mb_strtolower( stripslashes( $equation ) );

	while ( $pos = mb_strpos( $equation, '<b>)</b>' ) ) // RosarioSIS?
	{
		$screen_count++;

		// Date
		if ( isset( $screen[ $screen_count ]['month_start'] )
			&& isset( $screen[ $screen_count ]['month_end'] ) )
		{
			$extra = '<i class="size-1">' . _( 'Between' ) .
				ProperDate(
					$screen[ $screen_count ]['day_start'] . '-' .
					$screen[ $screen_count ]['month_start'] . '-' .
					$screen[ $screen_count ]['year_start'],
					'short'
				) .	' &amp; ' .
				ProperDate(
					$screen[ $screen_count ]['day_end'] . '-' .
					$screen[ $screen_count ]['month_end'] . '-' .
					$screen[ $screen_count ]['year_end'],
					'short'
				) . '</i>; ';
		}
		else
			$extra = '';

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
		if ( isset( $screen[ $screen_count ]['cust'] ) )
		{
			//foreach($screen[$screen_count]['cust'] as $field=>$value)
			foreach ( (array)$fields_RET as $field )
			{
				if ( isset( $screen[ $screen_count ]['cust'][ 'CUSTOM_' . $field['ID'] ] ) )
				{
					$extra .= $field['TITLE'] . ': ' . $screen[ $screen_count ]['cust'][ 'CUSTOM_' . $field['ID'] ] . '; ';
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
 * @param  array $array Array of numbers
 *
 * @return float Average
 */
function _average( $array )
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
 * @param  array $array Array of numbers
 *
 * @return float Sum
 */
function _sum( $array )
{
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
 * @param  array $array Array of numbers
 *
 * @return float Minimum
 */
function _min( $array )
{
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
 * @param  array $array Array of numbers
 *
 * @return float Maximum
 */
function _max( $array )
{
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
 * @param  array $array Array of Student IDs containing numbers
 *
 * @return float Sum min
 */
function _su0( $arr )
{
	/*print_r( $arr );

	echo "\n\n";*/

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
 * @param  array $array Array of Student IDs containing numbers
 *
 * @return float Sum max
 */
function _su1( $arr )
{
	/*print_r( $arr );

	echo "\n\n";*/

	foreach ( (array)$arr as $student_id => $array )
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
 * @param  array $array Array of Student IDs containing numbers
 *
 * @return float Average min
 */
function _avg0( $arr )
{
	/*print_r( $arr );

	echo "\n\n";*/

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
 * @param  array $array Array of Student IDs containing numbers
 *
 * @return float Average max
 */
function _avg1( $arr )
{
	/*print_r( $arr );

	echo "\n\n";*/

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
