<?php

/**
 * This file is part of the Froxlor project.
 * Copyright (c) 2003-2009 the SysCP Team (see authors).
 * Copyright (c) 2010 the Froxlor Team (see authors).
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at http://files.froxlor.org/misc/COPYING.txt
 *
 * @copyright  (c) the authors
 * @author     Florian Lippert <flo@syscp.org> (2003-2009)
 * @author     Froxlor team <team@froxlor.org> (2010-)
 * @license    GPLv2 http://files.froxlor.org/misc/COPYING.txt
 * @package    System
 *
 */

// define default theme for configurehint, etc.
$_deftheme = 'Sparkle';

// validate correct php version
if (version_compare("7.1.0", PHP_VERSION, ">=")) {
	// get hint-template
	$vendor_hint = file_get_contents(dirname(__DIR__) . '/templates/' . $_deftheme . '/misc/phprequirementfailed.tpl');
	// replace values
	$vendor_hint = str_replace("<FROXLOR_PHPMIN>", "7.1.0", $vendor_hint);
	$vendor_hint = str_replace("<CURRENT_VERSION>", PHP_VERSION, $vendor_hint);
	$vendor_hint = str_replace("<CURRENT_YEAR>", date('Y', time()), $vendor_hint);
	die($vendor_hint);
}

if (! file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
	// get hint-template
	$vendor_hint = file_get_contents(dirname(__DIR__) . '/templates/' . $_deftheme . '/misc/vendormissinghint.tpl');
	// replace values
	$vendor_hint = str_replace("<FROXLOR_INSTALL_DIR>", dirname(__DIR__), $vendor_hint);
	$vendor_hint = str_replace("<CURRENT_YEAR>", date('Y', time()), $vendor_hint);
	die($vendor_hint);
}

require dirname(__DIR__) . '/vendor/autoload.php';

use Froxlor\Database\Database;
use Froxlor\Settings;
use voku\helper\AntiXSS;
use Froxlor\PhpHelper;

header("Content-Type: text/html; charset=UTF-8");

// prevent Froxlor pages from being cached
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', time()));
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time()));

// Prevent inline - JS to be executed (i.e. XSS) in browsers which support this,
// Inline-JS is no longer allowed and used
// See: http://people.mozilla.org/~bsterne/content-security-policy/index.html
// New stuff see: https://www.owasp.org/index.php/List_of_useful_HTTP_headers and https://www.owasp.org/index.php/Content_Security_Policy
$csp_content = "default-src 'self'; script-src 'self'; connect-src 'self'; img-src 'self' data:; style-src 'self';";
header("Content-Security-Policy: " . $csp_content);
header("X-Content-Security-Policy: " . $csp_content);
header("X-WebKit-CSP: " . $csp_content);

header("X-XSS-Protection: 1; mode=block");

// Don't allow to load Froxlor in an iframe to prevent i.e. clickjacking
header("X-Frame-Options: DENY");

// Internet Explorer shall not guess the Content-Type, see:
// http://blogs.msdn.com/ie/archive/2008/07/02/ie8-security-part-v-comprehensive-protection.aspx
header("X-Content-Type-Options: nosniff");

// ensure that default timezone is set
if (function_exists("date_default_timezone_set") && function_exists("date_default_timezone_get")) {
	@date_default_timezone_set(@date_default_timezone_get());
}

/**
 * Register Globals Security Fix
 * - unsetting every variable registered in $_REQUEST and as variable itself
 */
foreach ($_REQUEST as $key => $value) {
	if (isset($$key)) {
		unset($$key);
	}
}

/**
 * check for xss attempts and clean important globals
 */
$antiXss = new AntiXSS();
// check $_GET
PhpHelper::cleanGlobal($_GET, $antiXss);
// check $_POST
PhpHelper::cleanGlobal($_POST, $antiXss);
// check $_COOKIE
PhpHelper::cleanGlobal($_COOKIE, $antiXss);

unset($_);
unset($value);
unset($key);

$filename = htmlentities(basename($_SERVER['SCRIPT_NAME']));

// check whether the userdata file exists
if (! file_exists(\Froxlor\Froxlor::getInstallDir() . '/lib/userdata.inc.php')) {
	$config_hint = file_get_contents(\Froxlor\Froxlor::getInstallDir() . '/templates/' . $_deftheme . '/misc/configurehint.tpl');
	$config_hint = str_replace("<CURRENT_YEAR>", date('Y', time()), $config_hint);
	die($config_hint);
}

// check whether we can read the userdata file
if (! is_readable(\Froxlor\Froxlor::getInstallDir() . '/lib/userdata.inc.php')) {
	// get possible owner
	$posixusername = posix_getpwuid(posix_getuid());
	$posixgroup = posix_getgrgid(posix_getgid());
	// get hint-template
	$owner_hint = file_get_contents(\Froxlor\Froxlor::getInstallDir() . '/templates/' . $_deftheme . '/misc/ownershiphint.tpl');
	// replace values
	$owner_hint = str_replace("<USER>", $posixusername['name'], $owner_hint);
	$owner_hint = str_replace("<GROUP>", $posixgroup['name'], $owner_hint);
	$owner_hint = str_replace("<FROXLOR_INSTALL_DIR>", \Froxlor\Froxlor::getInstallDir(), $owner_hint);
	$owner_hint = str_replace("<CURRENT_YEAR>", date('Y', time()), $owner_hint);
	// show
	die($owner_hint);
}

/**
 * Includes the Usersettings eg.
 * MySQL-Username/Passwort etc.
 */
require \Froxlor\Froxlor::getInstallDir() . '/lib/userdata.inc.php';

if (! isset($sql) || ! is_array($sql)) {
	$config_hint = file_get_contents(\Froxlor\Froxlor::getInstallDir() . '/templates/' . $_deftheme . '/misc/configurehint.tpl');
	$config_hint = str_replace("<CURRENT_YEAR>", date('Y', time()), $config_hint);
	die($config_hint);
}

/**
 * Includes the Functions
 */
@set_error_handler(array(
	'\\Froxlor\\PhpHelper',
	'phpErrHandler'
));

/**
 * Includes the MySQL-Tabledefinitions etc.
 */
require \Froxlor\Froxlor::getInstallDir() . '/lib/tables.inc.php';

/**
 * Create a new idna converter
 */
$idna_convert = new \Froxlor\Idna\IdnaWrapper();

/**
 * If Froxlor was called via HTTPS -> enforce it for the next time by settings HSTS header according to settings
 */
$is_ssl = false;
if (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) {
	$is_ssl = true;
	$maxage = Settings::Get('system.hsts_maxage');
	if (empty($maxage)) {
		$maxage = 0;
	}
	$hsts_header = "Strict-Transport-Security: max-age=" . $maxage;
	if (Settings::Get('system.hsts_incsub') == '1') {
		$hsts_header .= "; includeSubDomains";
	}
	if (Settings::Get('system.hsts_preload') == '1') {
		$hsts_header .= "; preload";
	}
	header($hsts_header);
}

/**
 * SESSION MANAGEMENT
 */
$remote_addr = $_SERVER['REMOTE_ADDR'];

if (empty($_SERVER['HTTP_USER_AGENT'])) {
	$http_user_agent = 'unknown';
} else {
	$http_user_agent = $_SERVER['HTTP_USER_AGENT'];
}
unset($userinfo);
unset($userid);
unset($customerid);
unset($adminid);
unset($s);

if (isset($_POST['s'])) {
	$s = $_POST['s'];
	$nosession = 0;
} elseif (isset($_GET['s'])) {
	$s = $_GET['s'];
	$nosession = 0;
} else {
	$s = '';
	$nosession = 1;
}

$timediff = time() - Settings::Get('session.sessiontimeout');
$del_stmt = Database::prepare("
	DELETE FROM `" . TABLE_PANEL_SESSIONS . "` WHERE `lastactivity` < :timediff
");
Database::pexecute($del_stmt, array(
	'timediff' => $timediff
));

$userinfo = array();

if (isset($s) && $s != "" && $nosession != 1) {
	ini_set("session.name", "s");
	ini_set("url_rewriter.tags", "");
	ini_set("session.use_cookies", false);
	ini_set("session.cookie_httponly", true);
	ini_set("session.cookie_secure", $is_ssl);
	session_id($s);
	session_start();
	$query = "SELECT `s`.*, `u`.* FROM `" . TABLE_PANEL_SESSIONS . "` `s` LEFT JOIN `";

	if (AREA == 'admin') {
		$query .= TABLE_PANEL_ADMINS . "` `u` ON (`s`.`userid` = `u`.`adminid`)";
		$adminsession = '1';
	} else {
		$query .= TABLE_PANEL_CUSTOMERS . "` `u` ON (`s`.`userid` = `u`.`customerid`)";
		$adminsession = '0';
	}

	$query .= " WHERE `s`.`hash` = :hash AND `s`.`ipaddress` = :ipaddr
		AND `s`.`useragent` = :ua AND `s`.`lastactivity` > :timediff
		AND `s`.`adminsession` = :adminsession
	";

	$userinfo_data = array(
		'hash' => $s,
		'ipaddr' => $remote_addr,
		'ua' => $http_user_agent,
		'timediff' => $timediff,
		'adminsession' => $adminsession
	);
	$userinfo_stmt = Database::prepare($query);
	$userinfo = Database::pexecute_first($userinfo_stmt, $userinfo_data);

	if ($userinfo && (($userinfo['adminsession'] == '1' && AREA == 'admin' && isset($userinfo['adminid'])) || ($userinfo['adminsession'] == '0' && (AREA == 'customer' || AREA == 'login') && isset($userinfo['customerid']))) && (! isset($userinfo['deactivated']) || $userinfo['deactivated'] != '1')) {
		$upd_stmt = Database::prepare("
			UPDATE `" . TABLE_PANEL_SESSIONS . "` SET
			`lastactivity` = :lastactive
			WHERE `hash` = :hash AND `adminsession` = :adminsession
		");
		$upd_data = array(
			'lastactive' => time(),
			'hash' => $s,
			'adminsession' => $adminsession
		);
		Database::pexecute($upd_stmt, $upd_data);
		$nosession = 0;
	} else {
		$nosession = 1;
	}
} else {
	$nosession = 1;
}

/**
 * Language Management
 */
$langs = array();
$languages = array();
$iso = array();

// query the whole table
$result_stmt = Database::query("SELECT * FROM `" . TABLE_PANEL_LANGUAGE . "`");

// presort languages
while ($row = $result_stmt->fetch(PDO::FETCH_ASSOC)) {
	$langs[$row['language']][] = $row;
	// check for row[iso] cause older froxlor
	// versions didn't have that and it will
	// lead to a lot of undefined variables
	// before the admin can even update
	if (isset($row['iso'])) {
		$iso[$row['iso']] = $row['language'];
	}
}

// buildup $languages for the login screen
foreach ($langs as $key => $value) {
	$languages[$key] = $key;
}

// set default language before anything else to
// ensure that we can display messages
$language = Settings::Get('panel.standardlanguage');

if (isset($userinfo['language']) && isset($languages[$userinfo['language']])) {
	// default: use language from session, #277
	$language = $userinfo['language'];
} else {
	if (! isset($userinfo['def_language']) || ! isset($languages[$userinfo['def_language']])) // this will always evaluat true, since it is the above statement inverted. @todo remove
	{
		if (isset($_GET['language']) && isset($languages[$_GET['language']])) {
			$language = $_GET['language'];
		} else {
			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				$accept_langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
				for ($i = 0; $i < count($accept_langs); $i ++) {
					// this only works for most common languages. some (uncommon) languages have a 3 letter iso-code.
					// to be able to use these also, we would have to depend on the intl extension for php (using Locale::lookup or similar)
					// as long as froxlor does not support any of these languages, we can leave it like that.
					if (isset($iso[substr($accept_langs[$i], 0, 2)])) {
						$language = $iso[substr($accept_langs[$i], 0, 2)];
						break;
					}
				}
				unset($iso);

				// if HTTP_ACCEPT_LANGUAGES has no valid langs, use default (very unlikely)
				if (! strlen($language) > 0) {
					$language = Settings::Get('panel.standardlanguage');
				}
			}
		}
	} else {
		$language = $userinfo['def_language'];
	}
}

// include every english language file we can get
foreach ($langs['English'] as $key => $value) {
	include_once \Froxlor\FileDir::makeSecurePath($value['file']);
}

// now include the selected language if its not english
if ($language != 'English') {
	foreach ($langs[$language] as $key => $value) {
		include_once \Froxlor\FileDir::makeSecurePath($value['file']);
	}
}

// last but not least include language references file
include_once \Froxlor\FileDir::makeSecurePath('lng/lng_references.php');

// Initialize our new link - class
$linker = new \Froxlor\UI\Linker('index.php', $s);

/**
 * global Theme-variable
 */
$theme = (Settings::Get('panel.default_theme') !== null) ? Settings::Get('panel.default_theme') : $_deftheme;

/**
 * overwrite with customer/admin theme if defined
 */
if (isset($userinfo['theme']) && $userinfo['theme'] != $theme) {
	$theme = $userinfo['theme'];
}

// Check if a different variant of the theme is used
$themevariant = "default";
if (preg_match("/([a-z0-9\.\-]+)_([a-z0-9\.\-]+)/i", $theme, $matches)) {
	$theme = $matches[1];
	$themevariant = $matches[2];
}

// check for existence of the theme
if (! file_exists('templates/' . $theme . '/config.json')) {
	// Fallback
	$theme = $_deftheme;
}

$_themeoptions = json_decode(file_get_contents('templates/' . $theme . '/config.json'), true);

// check for existence of variant in theme
if (! array_key_exists('variants', $_themeoptions) || ! array_key_exists($themevariant, $_themeoptions['variants'])) {
	$themevariant = "default";
}

// check for custom header-graphic
$hl_path = 'templates/' . $theme . '/assets/img';

// default is theme-image
$header_logo = $hl_path . '/logo.png';
$header_logo_login = $hl_path . '/logo.png';

if (Settings::Get('panel.logo_overridetheme') == 1 || Settings::Get('panel.logo_overridecustom') == 1) {
	// logo settings shall overwrite theme logo and possible custom logo
	$header_logo = Settings::Get('panel.logo_image_header') ?: $header_logo;
	$header_logo_login = Settings::Get('panel.logo_image_login') ?: $header_logo_login;
}
if (Settings::Get('panel.logo_overridecustom') == 0 && file_exists($hl_path . '/logo_custom.png')) {
	// custom theme image (logo_custom.png) is not being overwritten by logo_image_* setting
	$header_logo = $hl_path . '/logo_custom.png';
	$header_logo_login = $hl_path . '/logo_custom.png';
	if (file_exists($hl_path . '/logo_custom_login.png')) {
		$header_logo_login = $hl_path . '/logo_custom_login.png';
	}
}

/**
 * Redirects to index.php (login page) if no session exists
 */
if ($nosession == 1 && AREA != 'login') {
	unset($userinfo);
	$params = array(
		"script" => basename($_SERVER["SCRIPT_NAME"]),
		"qrystr" => $_SERVER["QUERY_STRING"]
	);
	\Froxlor\UI\Response::redirectTo('index.php', $params);
	exit();
}

/**
 * Initialize Template Engine
 */
$templatecache = array();

/**
 * Logic moved out of lng-file
 */
if (isset($userinfo['loginname']) && $userinfo['loginname'] != '') {
	$lng['menue']['main']['username'] .= $userinfo['loginname'];
	// Initialize logging
	$log = \Froxlor\FroxlorLogger::getInstanceOf($userinfo);
}

/**
 * Fills variables for navigation, header and footer
 */
$navigation = "";
if (AREA == 'admin' || AREA == 'customer') {
	if (\Froxlor\Froxlor::hasUpdates() || \Froxlor\Froxlor::hasDbUpdates()) {
		/*
		 * if froxlor-files have been updated
		 * but not yet configured by the admin
		 * we only show logout and the update-page
		 */
		$navigation_data = array(
			'admin' => array(
				'index' => array(
					'url' => 'admin_index.php',
					'label' => $lng['admin']['overview'],
					'elements' => array(
						array(
							'label' => $lng['menue']['main']['username']
						),
						array(
							'url' => 'admin_index.php?action=logout',
							'label' => $lng['login']['logout']
						)
					)
				),
				'server' => array(
					'label' => $lng['admin']['server'],
					'required_resources' => 'change_serversettings',
					'elements' => array(
						array(
							'url' => 'admin_updates.php?page=overview',
							'label' => $lng['update']['update'],
							'required_resources' => 'change_serversettings'
						)
					)
				)
			)
		);
		$navigation = \Froxlor\UI\HTML::buildNavigation($navigation_data['admin'], $userinfo);
	} else {
		$navigation_data = \Froxlor\PhpHelper::loadConfigArrayDir('lib/navigation/');
		$navigation = \Froxlor\UI\HTML::buildNavigation($navigation_data[AREA], $userinfo);
	}
	unset($navigation_data);
}

$js = "";
if (array_key_exists('js', $_themeoptions['variants'][$themevariant]) && is_array($_themeoptions['variants'][$themevariant]['js'])) {
	foreach ($_themeoptions['variants'][$themevariant]['js'] as $jsfile) {
		if (file_exists('templates/' . $theme . '/assets/js/' . $jsfile)) {
			$js .= '<script type="text/javascript" src="templates/' . $theme . '/assets/js/' . $jsfile . '"></script>' . "\n";
		}
	}
}

$css = "";
if (array_key_exists('css', $_themeoptions['variants'][$themevariant]) && is_array($_themeoptions['variants'][$themevariant]['css'])) {
	foreach ($_themeoptions['variants'][$themevariant]['css'] as $cssfile) {
		if (file_exists('templates/' . $theme . '/assets/css/' . $cssfile)) {
			$css .= '<link href="templates/' . $theme . '/assets/css/' . $cssfile . '" rel="stylesheet" type="text/css" />' . "\n";
		}
	}
}
eval("\$header = \"" . \Froxlor\UI\Template::getTemplate('header', '1') . "\";");

$current_year = date('Y', time());
$panel_imprint_url = Settings::Get('panel.imprint_url');
if (!empty($panel_imprint_url) && strtolower(substr($panel_imprint_url, 0, 4)) != 'http') {
	$panel_imprint_url = 'https://'.$panel_imprint_url;
}
$panel_terms_url = Settings::Get('panel.terms_url');
if (!empty($panel_terms_url) && strtolower(substr($panel_terms_url, 0, 4)) != 'http') {
	$panel_terms_url = 'https://'.$panel_terms_url;
}
$panel_privacy_url = Settings::Get('panel.privacy_url');
if (!empty($panel_privacy_url) && strtolower(substr($panel_privacy_url, 0, 4)) != 'http') {
	$panel_privacy_url = 'https://'.$panel_privacy_url;
}
eval("\$footer = \"" . \Froxlor\UI\Template::getTemplate('footer', '1') . "\";");

unset($js);
unset($css);

if (isset($_POST['action'])) {
	$action = trim(strip_tags($_POST['action']));
} elseif (isset($_GET['action'])) {
	$action = trim(strip_tags($_GET['action']));
} else {
	$action = '';
	// clear request data
	if (isset($_SESSION)) {
		unset($_SESSION['requestData']);
	}
}

if (isset($_POST['page'])) {
	$page = trim(strip_tags($_POST['page']));
} elseif (isset($_GET['page'])) {
	$page = trim(strip_tags($_GET['page']));
} else {
	$page = '';
}

if ($page == '') {
	$page = 'overview';
}

/**
 * Initialize the mailingsystem
 */
$mail = new \Froxlor\System\Mailer(true);
