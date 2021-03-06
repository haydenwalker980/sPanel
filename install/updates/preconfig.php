<?php

/**
 * This file is part of the Froxlor project.
 * Copyright (c) 2010 the Froxlor Team (see authors).
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at http://files.froxlor.org/misc/COPYING.txt
 *
 * @copyright  (c) the authors
 * @author     Froxlor team <team@froxlor.org> (2010-)
 * @license    GPLv2 http://files.froxlor.org/misc/COPYING.txt
 * @package    Language
 *
 */

/**
 * Function getPreConfig
 *
 * outputs various content before the update process
 * can be continued (asks for agreement whatever is being asked)
 *
 * @param string $current_version
 * @param int $current_db_version
 *
 * @return string
 */
function getPreConfig($current_version, $current_db_version)
{
	$has_preconfig = false;
	$return = '<div class="preconfig"><h3 class="red">PLEASE NOTE - Important update notifications</h3>';

	include_once \Froxlor\FileDir::makeCorrectFile(dirname(__FILE__) . '/preconfig/0.9/preconfig_0.9.inc.php');
	parseAndOutputPreconfig($has_preconfig, $return, $current_version, $current_db_version);

	include_once \Froxlor\FileDir::makeCorrectFile(dirname(__FILE__) . '/preconfig/0.10/preconfig_0.10.inc.php');
	parseAndOutputPreconfig2($has_preconfig, $return, $current_version, $current_db_version);

	$return .= '<br /><br />' . \Froxlor\UI\HTML::makecheckbox('update_changesagreed', '<strong>I have read the update notifications above and I am aware of the changes made to my system.</strong>', '1', true, '0', true);
	$return .= '</div>';
	$return .= '<input type="hidden" name="update_preconfig" value="1" />';

	if ($has_preconfig) {
		return $return;
	} else {
		return '';
	}
}

function versionInUpdate($current_version, $version_to_check)
{
	if (! \Froxlor\Froxlor::isFroxlor()) {
		return true;
	}

	return (\Froxlor\Froxlor::versionCompare2($current_version, $version_to_check) == - 1 ? true : false);
}
