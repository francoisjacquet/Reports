<?php
/**
 * English Help texts
 *
 * Texts are organized by:
 * - Module
 * - Profile
 *
 * Please use this file as a model to translate the texts to your language
 * The new resulting Help file should be named after the following convention:
 * Help_[two letters language code].php
 *
 * @author François Jacquet
 *
 * @uses Heredoc syntax
 * @see  http://php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc
 *
 * @package Reports
 * @subpackage Help
 */

// REPORTS ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['Reports/SavedReports.php'] = <<<HTML
<p>
	<i>Saved Reports</i> allows you to save, rename and delete reports. Those reports can virtually consist of any page within RosarioSIS. The "Save Report" button allowing you to save a report will appear in the Bottom frame whenever a Student / User list is displayed.
</p>
<p>
	Once saved, the report will appear under the Reports module's menu. This will allow you to easily run the report again and can act as a shortcut to any page.
</p>
HTML;

	$help['Reports/Calculations.php'] = <<<HTML
<p>
	<i>Calculations</i> allows you to perform calculations by combinating some basic functions, RosarioSIS fields, breakdown and search screens into an equation.
</p>
<p>
	The top header will give you hints while writing your calculus. The left box will provide you with functions and mathematical operators. The right box will provide you with Time values, RosarioSIS fields and constants.
</p>
<p>
	Clicking on one of those function / operator / field will add it to the Equation box below.
</p>
<p>
	The Equation box features 3 icons and a dropdown list:
	<ul>
		<li>the "Breakdown" dropdown list to apply to the equation results.</li>
		<li>the Backspace icon to erase the last member of the equation;</li>
		<li>the Run icon to run the equation;</li>
		<li>and the Floppy icon to save the equation.</li>
	</ul>
</p>
<p>
	When adding a function to the equation, you will notice a Search screen popping up: the dropdown lists will allow you to filter (add filter by clicking on the plus icon (+)) the results of the function. Each search screen corresponds to a function. If your equation contains more than one function, a new search screen will pop up.
</p>
<p>
	At the bottom of the screen, a list of Saved Equations may appear. Saved equations can be used within the <i>Calculations Reports</i> program.
</p>
HTML;

endif;
