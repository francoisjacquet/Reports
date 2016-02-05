<?php
/**
 * Calculations Reports
 *
 * @package Reports
 */

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'run' )
{
	DrawHeader(
		'',
		'<form action="' . PreparePHP_SELF() . '" method="GET">' . SubmitButton( _( 'Go' ) ) . '</form>'
	);

	$max_col = $max_row = 0;

	foreach ( (array) $_REQUEST['text'] as $row => $cells )
	{
		if ( $row > $max_row )
		{
			$max_row = $row;
		}

		foreach ( (array) $cells as $col => $value )
		{
			if ( $col > $max_col )
			{
				$max_col = $col;
			}
		}
	}

	foreach ( (array) $_REQUEST['calculation'] as $row => $cells )
	{
		if ( $row > $max_row )
		{
			$max_row = $row;
		}

		foreach ( (array) $cells as $col => $value )
		{
			if ( $col > $max_col )
			{
				$max_col = $col;
			}
		}
	}

	echo '<br />';

	echo '<table class="width-100p cellpadding-5">';

	for ( $row = 1; $row <= $max_row; $row++ )
	{
		echo '<tr>';

		for ( $col = 1; $col <= $max_col; $col++ )
		{
			if ( $_REQUEST['text'][ $row ][ $col ] == 'Title' )
			{
				unset( $_REQUEST['text'][ $row ][ $col ] );
			}

			if ( isset( $_REQUEST['text'][ $row ][ $col ] )
				|| isset( $_REQUEST['calculation'][ $row ][ $col ] ) )
			{
				// CHECK FOR EMPTY CELLS BENEATH THIS ONE
				// THIS CELL SHOULD EXPAND INTO THESE EMPTY CELLS WITH ROWSPAN.
				$rowspan = 1;

				for ( $i = ( $row + 1 ); $i <= $max_row; $i++ )
				{
					if ( ( ! isset( $_REQUEST['text'][ $i ][ $col ] )
							|| $_REQUEST['text'][ $i ][ $col ] == 'Title' )
						&& ! isset( $_REQUEST['calculation'][ $i ][ $col ] ) )
					{
						$rowspan++;
					}
				}

				echo '<td rowspan="' . $rowspan . '" class="valign-top center">';

				if ( isset( $_REQUEST['calculation'][ $row ][ $col ] ) )
				{
					$calc = _runCalc(
						$_REQUEST['calculation'][ $row ][ $col ],
						$_REQUEST['breakdown'][ $row ][ $col ],
						$_REQUEST['graph'][ $row ][ $col ]
					);

					echo '<b>' . $_REQUEST['text'][ $row ][ $col ] . ': ' .
						$_ROSARIO[ 'CalcTitle' . $_REQUEST['calculation'][ $row ][ $col ] ] . '</b><br />' .
						$calc;
				}

				echo '</td>';
			}
			else
			{
				// CHECK TO SEE IF THERE IS A FULL CELL ABOVE THIS ONE
				// IF SO, DON'T INCLUDE CELL -- ABOVE CELL HAS A ROWSPAN.
				$before = false;

				for ( $i = $row; $i >= 1; $i-- )
				{
					if ( isset( $_REQUEST['text'][ $i ][ $col ] )
						|| isset( $_REQUEST['calculation'][ $i ][ $col ] ) )
					{
						$before = true;
					}
				}

				if ( ! $before )
				{
					echo '<td></td>';
				}
			}
		}

		echo '</tr>';
	}

	echo '</table>';
}

if ( ! $_REQUEST['modfunc'] )
{
	$height = 110;

	$width = 210;

	$top_offset = 10;

	$left_offset = 10;

?>
<script>
var cols = 1;
var rows = 1;

function addCol()
{
	cols++;

	for ( row = 1; row <= rows; row++ )
	{
		addCell( cols, row );
	}

	button_left = parseInt(document.getElementById('add_col').style.left);

	document.getElementById('add_col').style.left = (button_left + <?php echo $width; ?>) + 'px';
}

function addRow()
{
	rows++;

	for ( col = 1; col <= cols; col++ )
	{
		addCell( col, rows );
	}

	button_top = parseInt(document.getElementById('add_row').style.top);

	document.getElementById('add_row').style.top = (button_top + <?php echo $height; ?>) + 'px';
}

function addCell( col, row )
{
	width = <?php echo $width; ?>;
	height = <?php echo $height; ?>;

	x = <?php echo $left_offset; ?> + width * (col-1);
	y = <?php echo $top_offset; ?> + height * (row-1);

	if ( row % 2 != 0 )
		color = "<?php echo Preferences( 'HEADER' ); ?>";
	else
		color = 'FFFFFF';

	document.getElementById('main_div').innerHTML = document.getElementById('main_div').innerHTML +
		replaceAll(
			document.getElementById('new_cell').innerHTML
				.replace('9876',y)
				.replace('6789',x)
				.replace('FFFFFF',color)
				.replace('ffffff',color)
				.replace('rgb(255, 255, 255)','#'+color)
				.replace('div_id','id'),
			'cell_id',
			'['+row+']['+col+']'
		);
}

function replaceAll( haystack, needle, replacement )
{
	haystack = haystack.replace(needle,replacement);

	if( haystack.match( needle ) )
		haystack = replaceAll(haystack,needle,replacement);

	return haystack;
}
</script>

<?php
	$text = '<input type="text" name="textcell_id" size="20" placeholder="' . _( 'Title' ) . '" required />';

	$calculations_RET = DBGet( DBQuery( "SELECT ID,TITLE
		FROM SAVED_CALCULATIONS
		ORDER BY TITLE" ) );

	$calculations_options = array();

	foreach ( (array) $calculations_RET as $calculation )
	{
		$calculations_options[ $calculation['ID'] ] = $calculation['TITLE'];
	}

	$calculation_select = SelectInput(
		'',
		'calculationcell_id',
		'',
		$calculations_options,
		dgettext( 'Reports', 'Calculation' ),
		'required style="width:' . ( $width - 7 ) . ';"'
	);

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
		'stuid' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
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
		'breakdowncell_id',
		'',
		$breakdown_options,
		dgettext( 'Reports', 'Breakdown' ),
		'style="max-width:150px;"'
	);

	$graph = CheckboxInput( '', 'graphcell_id', dgettext( 'Reports', 'Graph Results' ), '', true );

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=run" method="GET">';

	DrawHeader( '', SubmitButton( _( 'Go' ) ) );

	echo '<div id="main_div" style="position: relative; height: 1000px;">';

	echo '<div id="add_col" style="position:absolute;top:' . ( $top_offset + 15 ) . 'px;left:' . ( $left_offset + 15 + $width ) . 'px;">' .
		button( 'add', dgettext( 'Reports', 'Add Column' ), '# onclick="addCol();"' ) . '</div>';

	echo '<div id="add_row" style="position:absolute;top:' . ( $top_offset + 15 + $height ) . 'px;left:' . ( $left_offset + 15 ) . 'px;">' .
	button( 'add', dgettext( 'Reports', 'Add Row' ), '# onclick="addRow();"' ) . '</div>';

	echo '</div></form>';

	echo '<div id="new_cell" style="position:absolute;display:none;">
		<div div_id="cellcell_id" style="position:absolute;width:' . $width .
			'px;height:' . $height . 'px;top:9876px;left:6789px;padding:5px;border-style:solid solid solid solid;border-width:1px;background-color:#fff;">' .
			$text . '<br />' .
			$calculation_select . '<br />' .
			$breakdown . '<br />' .
			$graph . '</div>';

	echo '<script>addCell(1,1);</script>';
}


/**
 * Run Calculation
 *
 * Local function
 *
 * @uses modules/Reports/Calculations.php file, echoXMLHttpRequest modfunc
 *
 * @example echo _runCalc( $_REQUEST['calculation'][ $row ][ $col ], $_REQUEST['breakdown'][ $row ][ $col ], $_REQUEST['graph'][ $row ][ $col ]	);
 *
 * @param  string $calculation_id Calculation ID
 * @param  string $breakdown      Breakdown column
 * @param  string $graph          Graph column
 *
 * @return string Calculation
 */
function _runCalc( $calculation_id, $breakdown, $graph )
{
	global $_ROSARIO,
		$_runCalc_start_REQUEST;

	static $num,
		$_runCalc_num;

	if ( ! isset( $num ) )
	{
		$num = 0;
	}

	if ( ! isset( $_runCalc_num ) )
	{
		$_runCalc_num = $num;
	}

	$_runCalc_start_REQUEST = $_REQUEST;

	require_once 'modules/Reports/includes/ReportsCalculations.fnc.php';

	if ( ! isset( $_ROSARIO[ 'Calc' . $calculation_id ] ) )
	{
		$url_RET = DBGet( DBQuery( "SELECT URL,TITLE
			FROM SAVED_CALCULATIONS
			WHERE ID='" . $calculation_id . "'" ), array( 'URL' => '_makeURL' ) );

		$_ROSARIO[ 'CalcTitle' . $calculation_id ] = $url_RET[1]['URL'];

		/*$url = $url_RET[1]['URL'];

		$url = urldecode( $url );

		$vars = mb_substr( $url, ( mb_strpos( $url, '?' ) + 1 ) );

		$modname = mb_substr( $url, 0, mb_strpos( $url, '?' ) );

		$vars = str_replace( '&amp;', '&', $vars );

		$vars = explode( '&', $vars );

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
		}*/

		$_ROSARIO[ 'Calc' . $calculation_id ] = $_REQUEST;
	}
	else
		$_REQUEST = $_ROSARIO[ 'Calc' . $calculation_id ];

	if ( $breakdown )
	{
		$_REQUEST['breakdown'] = $breakdown;
	}

	/*if ( $_REQUEST['breakdown'] == 'CUSTOM_44' ) // RosarioSIS?
	{
		for ( $i = 1; $i <= 15; $i++ )
		{
			$_REQUEST['screen'][ $i ]['_search_all_schools'] = 'Y';
		}
	}*/

	$_REQUEST['graph'] = $graph;

	$num = $_runCalc_num;

	// So Calculations.php doesn't include the functions within this function.
	/*$_REQUEST['modfunc'] = 'Reports/CalculationsReports.php';

	$_REQUEST['modfunc'] = 'echoXMLHttpRequest';

	$return = require 'modules/Reports/Calculations.php';*/

	$query = _makeQuery( isset( $_POST['query'] ) ? $_POST['query'] : $_REQUEST['query'] );

	$return = _getAJAXResults( $query, 'echoXMLHttpRequest' );

	$_REQUEST = $_runCalc_start_REQUEST;

	return $return;
}
