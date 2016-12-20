<?php
/**
 * Saved Reports (Setup)
 *
 * @package Reports
 */

// RosarioSIS 2.9: ajaxLink( 'Side.php' );
$reload_side = '<script>
	var side_link = document.createElement("a");
	side_link.href = "Side.php";
	side_link.target = "menu";
	ajaxLink(side_link);
</script>';

// Save New Report.
if ( $_REQUEST['modfunc'] === 'new'
	&& AllowEdit() )
{
	$report_id = DBGet( DBQuery( "SELECT " . db_seq_nextval( 'SAVED_REPORTS_SEQ' ) . ' AS ID' ) );

	$report_id = $report_id[1]['ID'];

	DBQuery( "INSERT INTO SAVED_REPORTS (ID,TITLE,STAFF_ID,PHP_SELF,SEARCH_PHP_SELF,SEARCH_VARS)
		values(
			'" . $report_id . "',
			'" . DBEscapeString( dgettext( 'Reports', 'Untitled' ) ) . "',
			'" . User( 'STAFF_ID' ) . "',
			'" . PreparePHP_SELF( $_SESSION['_REQUEST_vars'] ) . "',
			'" . $_SESSION['Search_PHP_SELF'] . "',
			'" . /*serialize( $_SESSION['Search_vars'] ) .*/ "')" );

	// FJ disable Publishing options.
	$modname = 'Reports/RunReport.php&id=' . $report_id;

	// Admin can Use Report.
	DBQuery( "INSERT INTO PROFILE_EXCEPTIONS (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
		values('1','" . $modname . "','Y','Y')" );

	unset( $_REQUEST['modfunc'] );
	unset( $_SESSION['_REQUEST_vars']['modfunc'] );

	// Reload Side.php Menu.
	echo $reload_side;
}

// Update Saved Report.
if ( isset( $_REQUEST['values'] )
	&& isset( $_POST['values'] )
	&& AllowEdit() )
{
	foreach ( (array)$_REQUEST['values'] as $id => $columns )
	{
		$sql = "UPDATE SAVED_REPORTS SET ";

		foreach ( (array)$columns as $column => $value )
		{
			$sql .= $column . "='" . $value . "',";
		}

		$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";

		DBQuery( $sql );
	}

	// Reload Side.php Menu.
	echo $reload_side;
}

// Add profile exceptions for the Saved Reports to appear in the menu.
if ( isset( $_REQUEST['profiles'] )
	&& isset( $_POST['profiles'] )
	&& AllowEdit() )
{
	$profiles_RET = DBGet( DBQuery( "SELECT ID,TITLE
		FROM USER_PROFILES" ) );

	$reports_RET = DBGet( DBQuery( "SELECT ID
		FROM SAVED_REPORTS" ) );

	foreach( (array)$reports_RET as $report_id )
	{
		$report_id = $report_id['ID'];

		$modname = 'Reports/RunReport.php&id=' . $report_id;

		if ( ! isset( $exceptions_RET[ $report_id ] ) )
		{
			$exceptions_RET[ $report_id ] = DBGet( DBQuery( "SELECT PROFILE_ID,CAN_USE,CAN_EDIT
				FROM PROFILE_EXCEPTIONS
				WHERE MODNAME='" . $modname . "'"), array(), array( 'PROFILE_ID' ) );
		}

		foreach ( (array)$profiles_RET as $profile )
		{
			$profile_id = $profile['ID'];

			if ( ! isset( $exceptions_RET[ $report_id ][ $profile_id ] ) )
			{
				DBQuery( "INSERT INTO PROFILE_EXCEPTIONS (PROFILE_ID,MODNAME)
					values('" . $profile_id . "','" . $modname . "')" );
			}

			if ( ! $_REQUEST['profiles'][ $report_id ][ $profile_id ] )
			{
				DBQuery( "UPDATE PROFILE_EXCEPTIONS
					SET CAN_USE='N',CAN_EDIT='N'
					WHERE PROFILE_ID='" . $profile_id . "'
					AND MODNAME='" . $modname . "'" );
			}
			else
			{
				DBQuery( "UPDATE PROFILE_EXCEPTIONS
					SET CAN_USE='Y',CAN_EDIT='Y'
					WHERE PROFILE_ID='" . $profile_id . "'
					AND MODNAME='". $modname . "'" );
			}

			/*if ( ! $_REQUEST['profiles'][ str_replace( '.', '_', $modname ) ] )
			{
				$update_profile = "UPDATE PROFILE_EXCEPTIONS SET ";

				if ( ! $_REQUEST['can_use'][ str_replace( '.', '_', $modname ) ] )
				{
					$update_profile .= "CAN_USE='N'";
				}

				$update_profile .= " WHERE PROFILE_ID='" . $profile_id . "'
					AND MODNAME='" . $modname . "'";

				DBQuery( $update_profile );
			}*/
		}
	}
}

DrawHeader( ProgramTitle() );

// Remove Saved Report.
if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Saved Report' ) ) )
	{
		DBQuery( "DELETE FROM SAVED_REPORTS
			WHERE ID='" . $_REQUEST['id'] . "'" );

		$modname = 'Reports/RunReport.php&id=' . $_REQUEST['id'];

		DBQuery( "DELETE FROM PROFILE_EXCEPTIONS
			WHERE MODNAME='" . $modname . "'" );

		unset( $_REQUEST['modfunc'] );
		unset( $_SESSION['_REQUEST_vars']['modfunc'] );

		// Reload Side.php Menu.
		echo $reload_side;
	}
}

// Display Saved Report.
if ( $_REQUEST['modfunc'] !== 'remove' )
{
	$saved_reports_RET = DBGet(
		DBQuery( "SELECT ID,TITLE,PHP_SELF,'' AS PUBLISHING
			FROM SAVED_REPORTS
			ORDER BY TITLE" ),
		array(
			'TITLE' => '_makeTextInput',
			'PHP_SELF' => '_makeProgram',
			// FJ disable Publishing options.
			//'PUBLISHING' => '_makePublishing',
		)
	);

	$columns = array(
		'TITLE' => _( 'Title' ),
		'PHP_SELF' => _( 'Program Title' ),
		// FJ disable Publishing options.
		//'PUBLISHING' => _('Publishing Options' ),
	);

	$link['remove']['link'] = "Modules.php?modname=". $_REQUEST['modname'] . "&modfunc=remove";

	$link['remove']['variables'] = array( 'id' => 'ID' );

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update" method="POST">';

	DrawHeader( '', SubmitButton( _( 'Save' ) ) );

	ListOutput( $saved_reports_RET, $columns, 'Saved Report', 'Saved Reports', $link );

	echo '<div class="center">' . SubmitButton( _( 'Save' ) ) . '</div>';

	echo '</form>';
}


/**
 * Make Text Input
 *
 * Local function
 *
 * @global $THIS_RET Current Return value
 *
 * @param  string $value  Value.
 * @param  string $column 'TITLE'.
 *
 * @return string Text Input
 */
function _makeTextInput( $value, $column )
{
	global $THIS_RET;

	if ( isset( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
		$id = 'new';

	if ( $value === dgettext( 'Reports', 'Untitled' ) )
	{
		$div = false;
	}
	else
		$div = true;

	$extra = 'maxlength="100"';

	$run_button = '';

	if ( $id !== 'new' )
	{
		$run_button = '<a href="Modules.php?modname=Reports/RunReport.php&id=' . $id . '" style="float: right;" title="' . _( 'Run' ) . '">
			<img src="assets/themes/' . Preferences( 'THEME' ) . '/btn/next.png" class="button" /></a>';
	}

	return '<div style="float:left;">' . TextInput(
		$value,
		'values[' . $id . '][' . $column . ']',
		'',
		$extra,
		$div
	) . '</div>' . $run_button;
}


/**
 * Make Program Title
 *
 * Local function
 *
 * @param  string $value  Value.
 * @param  string $column 'PHP_SELF'.
 *
 * @return string ProgramTitle( $modname )
 */
function _makeProgram( $value, $column )
{
	if ( strpos( $value, '&' ) )
	{
		$modname = mb_substr($value, 20, strpos( $value, '&' ) - 20 );
	}
	else
		$modname = mb_substr( $value, 20 );

	return ProgramTitle( $modname );
}


/**
 * Make Publishing options
 *
 * Local function
 *
 * @global $THIS_RET Current Return value
 *
 * @param  string $value  Value.
 * @param  string $column 'PUBLISHING'.
 *
 * @return string Publishing options
 */
function _makePublishing( $value, $column )
{
	global $THIS_RET;

	static $profiles_RET = null,
		$schools_RET;

	if ( ! $profiles_RET )
	{
		$profiles_RET = DBGet( DBQuery( "SELECT ID,TITLE
			FROM USER_PROFILES" ) );
	}

	$exceptions_RET = DBGet( DBQuery( "SELECT CAN_EDIT,CAN_USE,PROFILE_ID
		FROM PROFILE_EXCEPTIONS
		WHERE MODNAME='Reports/RunReport.php&id=" . $THIS_RET['ID'] . "'" ), array(), array( 'PROFILE_ID' ) );

	$return = '<table class="cellspacing-0"><tr><td colspan="4"><b>' .
		_( 'Profiles' ) . ': </b></td></tr>';

	$i = 0;

	foreach ( (array) $profiles_RET as $profile )
	{
		$i++;

		$return .= '<td>' .
			CheckboxInput(
				$exceptions_RET[ $profile['ID'] ][1]['CAN_USE'],
				'profiles[' . $THIS_RET['ID'] . '][' . $profile['ID'] . ']',
				$profile['TITLE'],
				'',
				true
			) .
			'</td>';

		if ( $i % 4 == 0
			&& $i !== count( $profiles_RET ) )
		{
			$return .= '</tr><tr>';
		}
	}

	for ( ; $i % 4 != 0; $i++ )
	{
		$return .= '<td></td>';
	}

	/*$return .= '</tr><tr><td colspan="2"><b><a href="#">' .
		_( 'Schools' ) . ': ...</a></b></td>';*/

	$return .= '</tr></table>';

	return $return;
}
