<?php
/**
 * Spanish Help texts
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
 * @package Reportes
 * @subpackage Help
 */

// REPORTS ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['Reports/SavedReports.php'] = <<<HTML
<p>
	<i>Reportes Guardados</i> le permite guardar, renombrar y supprimir reportes. Estos reportes pueden ser virtualmente cualquiera página de RosarioSIS. El botón "Guardar Reporte" que permite guardar un reporte aparece en el menú inferior una vez une lista de Estudiantes / Usuarios está generada.
</p>
<p>
	Una vez guardado, el reporte aparecera en el menú del módulo Reportes. Le permitirá acceder facilmente al reporte y puede actuar como atajo hacia cualquiera página.
</p>
HTML;

	$help['Reports/Calculations.php'] = <<<HTML
<p>
	<i>Cálculos</i> le permite realizar cálculos combinando funciones básicas, campos de RosarioSIS, análisis y búsquedas en una ecuación.
</p>
<p>
	El encabezado de arriba le dará tips al momento de escribir su cálculo. La caja de la izquierda propone funciones y operadores matemáticos. La caja de la derecha propone valores de Tiempo, campos de RosarioSIS y constantes.
</p>
<p>
	Hagá clic sobre cualquiera de estas funciones / ooperadores / campos para agregarlo a la Ecuación abajo.
</p>
<p>
	El cuardo de la Ecuación presenta 3 iconos y una lista desplegable:
	<ul>
		<li>la lista desplegable "Análisis" aplica a los resultados de la ecuación.</li>
		<li>el icono Retroceso para borrar el último miembro de la ecuacíon;</li>
		<li>el icono Correr para correr el cálculo de la ecuación;</li>
		<li>y el icono Floppy para guardar la ecuación.</li>
	</ul>
</p>
<p>
	Cuando se agrega una función a la ecuación, notará una pantalla de Búsqueda que aparece de repente: las listas desplegables permiten filtrar los resultados de la función. Se puede agregar otro filtro haciendo clic sobre el icono más (+). Por favor nota que un mismo tipo de filtro ne puede ser repetido para la misma función. Cada pantalla de búsqueda corresponde a una función. Si su ecuación contiene más de una función, una nueva pantalla de búsqueda aparecerá.
</p>
<p>
	En la parte inferior de la pantalla, una lista de Ecuaciónes Guardadas puede aparecer. Las ecuaciónes guardadas pueden ser usadas en el programa <i>Reportes de Calculaciones</i>.
</p>
HTML;

endif;
