<?php
/**
 * Menu.php file
 * Required
 * - Menu entries for the Reports module
 *
 * @package Reports module
 */

$menu['Reports']['admin'] = array();

$menu_reports_RET = DBGet( DBQuery( "SELECT ID,TITLE
	FROM SAVED_REPORTS
	ORDER BY TITLE" ) );

// Add Saved Reports.
foreach ( (array) $menu_reports_RET as $report )
{
	$menu['Reports']['admin'][ 'Reports/RunReport.php&id=' . $report['ID'] ] = $report['TITLE'];
}

$menu['Reports']['admin'] += array(
	'Reports/Calculations.php' => dgettext( 'Reports', 'Calculations' ),
	'Reports/CalculationsReports.php' => dgettext( 'Reports', 'Calculations Reports' ),
	1 => _( 'Setup' ),
	'Reports/SavedReports.php' => dgettext( 'Reports', 'Saved Reports' ),
);
