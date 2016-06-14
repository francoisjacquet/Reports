<?php
/**
 * Run Saved Reports
 *
 * @package Reports
 */

$report_RET = DBGet( DBQuery( "SELECT ID,TITLE,PHP_SELF,SEARCH_PHP_SELF,SEARCH_VARS
	FROM SAVED_REPORTS WHERE ID='" . $_REQUEST['id'] . "'" ) );

$report = $report_RET[1];

/*$report['PHP_SELF'] = str_replace( '&amp;', '&', mb_substr( $report['PHP_SELF'], 20 ) );

/*if ( mb_strpos( $report['PHP_SELF'], '?search_modfunc=list' ) !== false )
{
	unset( $_ROSARIO['modules_search'] );
}*/

// Set $modname from $report['PHP_SELF']
/*if ( mb_strpos( $report['PHP_SELF'], '&' ) !== false )
{
	$vars = mb_substr( $report['PHP_SELF'], ( mb_strpos( $report['PHP_SELF'], '&' ) + 1 ) );

	$modname = mb_substr( $report['PHP_SELF'], 0, mb_strpos( $report['PHP_SELF'], '&' ) );
	
	$vars = explode( '&', $vars );

	foreach ( (array)$vars as $code )
	{
		$equals = mb_strpos( $code, '=' );

		// Array.
		if ( mb_strpos( $code, '[' ) !== false )
		{
			$code = "\$_REQUEST[" . ereg_replace(
				'/([^]])\[/',
				'\1][',
				mb_substr( $code, 0, $equals )
			) . "='" . urldecode( mb_substr( $code, $equals + 1 ) ) . "';";
		}
		else
		{
			$code = "\$_REQUEST['" . mb_substr( $code, 0, $equals ) . "']='" .
				urldecode( mb_substr( $code, $equals + 1 ) ) . "';";
		}

		eval( $code );
	}
}
else
	$modname = $report['PHP_SELF'];*/

$_SESSION['Search_PHP_SELF'] = $report['SEARCH_PHP_SELF'];

// RosarioSIS?
//$_SESSION['SEARCH_VARS'] = unserialize( $report['SEARCH_VARS'] );

// FJ security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html.
if ( mb_substr( $modname, -4, 4 ) !== '.php'
	|| mb_strpos( $modname, '..' ) !== false
	|| ! is_file( 'modules/' . $modname ) )
{
	require_once 'ProgramFunctions/HackingLog.fnc.php';
	HackingLog();
}
else
{
	//require_once( 'modules/' . $modname );

	// Load Report.
	echo '<script>
		var report_link = document.createElement("a");
		report_link.href = "' . $report['PHP_SELF'] . '";
		report_link.target = "body";
		ajaxLink(report_link);
	</script>';
}
