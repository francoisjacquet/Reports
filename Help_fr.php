<?php
/**
 * French Help texts
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
	<i>Rapports Sauvegardés</i> vous peret de sauver, renommer et supprimmer des rapports. Ces rapports consistent en virtuellement n'importe quelle page de RosarioSIS. Le bouton "Sauvegarder Rapport" permettant de sauvegarder un rapport apparaîtra dans le menu inférieur lorsque une liste d'Élèves / Utilisateurs est affichée.
</p>
<p>
	Une fois sauvegardé, le rapport apparaîtra sous le menu du module Rapports. Cela vous permettra d'accéder facilement au rapport ultérieurement et peut servir de raccourci pour n'importe quelle page.
</p>
HTML;

	$help['Reports/Calculations.php'] = <<<HTML
<p>
	<i>Calculs</i> vous permet de réaliser des calculs en combinant des fonctions basiques, des champs de RosarioSIS, des filtres et des répartitions au sein d'une équation.
</p>
<p>
	L'en-tête supérieur vous donnera des indications au moment d'écrire votre calcul. La boîte de gauche fournit des fonctions ainsi que des opérateurs mathématiques. La boîte de droite fournit des valuers de Temps, des champs de RosarioSIS et des constantes.
</p>
<p>
	Lorsque vous cliquez sur un(e) de ces fonctions / opérateurs / champs, il/elle sera ajouté(e) à la boîte Équation située en dessous.
</p>
<p>
	La boîte Équation contient 3 icônes et une liste déroulante:
	<ul>
		<li>la liste déroulante "Répartition" à appliquer aux résultats de l'équation.</li>
		<li>l'icône Retour arrière pour effacer le dernier memebre de l'équation;</li>
		<li>l'icône Lancer pour lancer le calcul de l'équation;</li>
		<li>et l'icône Disquette pour sauvegarder l'équation.</li>
	</ul>
</p>
<p>
	Lorsque vous ajoutez une fonction à l'équation, vous noterez un écran de Recherche qui s'ouvre: les listes déroulantes vous permettent alors de filtrer le résultat de la fonction. Vous pouvez ajouter un autre filtre en cliquant sur l'icóne plus (+). Veuillez noter qu'un même type de filtre ne peut-être répété pour la même fonction. Chaque écran de recherche correspond à une fonction. Si votre équation contient plus d'une fonction, un nouvel écran de recherche apparaîtra.
</p>
<p>
	En bas de l'écran, une liste d'Équations Sauvegardées peut-être présentée. Les équations sauegardées peuvent être utilisées au sein du programme <i>Rapports des Calculs</i>.
</p>
HTML;

endif;
