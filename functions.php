<?php
/**
 * Module Functions
 * (Loaded on each page)
 *
 * @package Reports module
 */


/**
 * Reports module Bottom Buttons.
 * Messaging new messages note.
 *
 * @uses Bottom.php|bottom_buttons hook
 *
 * @return true if bottom button, else false.
 */
function ReportsBottomButtons()
{
	if ( ! User( 'PROFILE' ) === 'admin'
		|| ! AllowEdit( 'Reports/SavedReports.php' )
		|| ! isset( $_SESSION['List_PHP_SELF'] ) )
	{
		return false;
	}

	?>
	<a href="Modules.php?modname=Reports/SavedReports.php&amp;modfunc=new" class="BottomButton">
		<img src="assets/themes/<?php echo Preferences( 'THEME' ); ?>/btn/download.png" />
		<span><?php echo dgettext( 'Reports', 'Save Report' ); ?></span>
	</a>
	<?php

	return true;
}

add_action( 'Bottom.php|bottom_buttons', 'ReportsBottomButtons', 0 );
