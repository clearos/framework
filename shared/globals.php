<?php

/**
 * ClearOS framework core settings and functions.
 *
 * The functions and environment in this file are shared by both the base API
 * and the CodeIgniter engine. 
 *
 * @category   Framework
 * @package    Shared
 * @subpackage Helpers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/framework/
 */

//////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\framework\Apps as Apps;
use \clearos\framework\Config as Config;
use \clearos\framework\Error as Error;
use \clearos\framework\Lang as Lang;
use \clearos\framework\Logger as Logger;
use \clearos\framework\Themes as Themes;

require_once 'libraries/Apps.php';
require_once 'libraries/Config.php';
require_once 'libraries/Error.php';
require_once 'libraries/Lang.php';
require_once 'libraries/Logger.php';
require_once 'libraries/Themes.php';

///////////////////////////////////////////////////////////////////////////////
// G L O B A L  C O N S T A N T S
///////////////////////////////////////////////////////////////////////////////

define('CLEAROS_ERROR', -1);
define('CLEAROS_WARNING', -2);
define('CLEAROS_INFO', -4);
define('CLEAROS_DEBUG', -8);

define('CLEAROS_TEMP_DIR', '/var/clearos/framework/tmp');
define('CLEAROS_CACHE_DIR', '/var/clearos/framework/cache');

///////////////////////////////////////////////////////////////////////////////
// G L O B A L  I N I T I A L I Z A T I O N
///////////////////////////////////////////////////////////////////////////////

// The date_default_timezone_set must be called or the time zone must be set
// in PHP's configuration when date() functions are called.  On a ClearOS 
// system, the default time zone for the system is correct.

$zone = 'UTC'; // default

if (file_exists('/etc/sysconfig/clock')) {
    $lines = preg_split("/\n/", file_get_contents('/etc/sysconfig/clock'));

    foreach ($lines as $line) {
        $matches = array();
        if (preg_match('/^ZONE="(.*)"/', $line, $matches)) {
            $zone = $matches[1];
            break;
        }
    }
}

@date_default_timezone_set($zone);

// Set error and exception handlers
//---------------------------------

set_error_handler('_clearos_error_handler');
set_exception_handler('_clearos_exception_handler');

// Logging
//--------

if (Config::$debug_mode) {
    @ini_set('display_errors', TRUE); 
    @ini_set('display_startup_error', TRUE);
    @ini_set('log_errors', TRUE);
    @ini_set('error_log', Config::$debug_log);
}

///////////////////////////////////////////////////////////////////////////////
// G L O B A L  F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

/**
 * Returns the app htdocs URL
 *
 * @param string $app app
 *
 * @return string app URL 
 */

function clearos_app_htdocs($app = NULL)
{
    if (is_null($app))
        $app = uri_string();

    return Config::get_app_url($app);
}

/**
 * Returns the app base path
 *
 * @param string $app app
 *
 * @return string base path for the given app
 */

function clearos_app_base($app)
{
    return Config::get_app_base($app);
}

/**
 * Checks the existence of an app library.
 *
 * @param string $app app name
 *
 * @return boolean TRUE if app is installed
 */

function clearos_app_installed($app)
{
    $base = Config::get_app_base($app);

    if ((!empty($base) && file_exists($base . '/controllers')))
        return TRUE;
    else
        return FALSE;
}

/**
 * Returns ClearOS base version.
 *
 * @return integer base version, 0 if version is unknown
 */

function clearos_version()
{
    $contents = file_get_contents('/etc/clearos-release');
    $matches = array();

    $osinfo = explode(" release ", $contents);

    if (count($osinfo) != 2)
        return 0;

    $version = preg_replace('/\..*/', '', $osinfo[1]);

    return $version;
}

/**
 * Checks to see if the request is coming from the console.
 *
 * @return boolean TRUE if request is coming from the console
 */

function clearos_console()
{
    $is_alt_port = (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] == '82')) ? TRUE : FALSE;
    $is_localhost = (isset($_SERVER['REMOTE_ADDR']) && (preg_match('/^(::1)|(127.0.0.1)$/', $_SERVER['REMOTE_ADDR'])));

    if ($is_alt_port && $is_localhost)
        return TRUE;
    else
        return FALSE;
}

/**
 * Returns the installed driver for given driver engine.
 *
 * @param string $driver_engine driver engine name
 *
 * @return string driver name
 */

function clearos_driver($driver_engine)
{
    if ($driver_engine === 'summary')
        return Config::get_summary_driver();
    else if ($driver_engine === 'reports')
        return Config::get_reports_driver();
}

/**
 * Returns installed API information.
 *
 * @return array installed API information
 */

function clearos_get_apis() {
    return Apps::get_list(TRUE, TRUE);
}

/**
 * Returns installed app information.
 *
 * Only apps with a UI and menu are returned.  In other words, what
 * a normal user sees through the web-based administration interface.
 *
 * @return array installed app information
 */

function clearos_get_apps() {
    return Apps::get_list();
}

/**
 * Returns themes information.
 *
 * @return array thems information
 */

function clearos_get_themes()
{
    return Themes::get_list();
}

/**
 * Returns the error code from any Exception object
 *
 * This function makes it possible to return the error code from
 * an Exception object regardless if it is ours (derived from Engine_Exception),
 * or if comes from some other third-party code (with only getCode()).
 *
 * @param object $exception exception object
 *
 * @return integer exception code
 */

function clearos_exception_code($exception)
{
    if (is_object($exception)) {
        if 
            (method_exists($exception, 'get_code')) return $exception->get_code();
        else if 
            (method_exists($exception, 'getCode')) return $exception->getCode();
    }

    return -1; // TODO - what to return if there is no method to get error code
}

/**
 * Returns the error message from any Exception object
 *
 * This function makes it possible to return the error message from
 * an Exception object regardless if it is ours (derived from Engine_Exception),
 * or if comes from some other third-party code (with only getMessage()).
 *
 * @param object $exception exception object
 *
 * @return string exception message
 */

function clearos_exception_message($exception)
{
    if (is_object($exception)) {
        if 
            (method_exists($exception, 'get_message')) return $exception->get_message();
        else if 
            (method_exists($exception, 'getMessage')) return $exception->getMessage();
    }

    return '';
}

/**
 * Returns debug state.
 *
 * @return boolean TRUE if boolean is valid
 */

function clearos_is_debug()
{
    if (Config::$debug_mode)
        return TRUE;
    else
        return FALSE;
}

/**
 * Common boolean validation for ClearOS.
 *
 * @param string $boolean boolean
 *
 * @return boolean TRUE if boolean is valid
 */

function clearos_is_valid_boolean($boolean)
{
    if (is_bool($boolean) || preg_match('/^(on|yes|TRUE|1|off|no|FALSE|0)$/i', $boolean))
        return TRUE;
    else
        return FALSE;
}

/**
 * Checks to see if a library is installed.
 *
 * @param string $library library
 *
 * @return boolean TRUE if library is installed
 */

function clearos_library_installed($library)
{
    list($app, $library) = preg_split('/\//', $library, 2);

    $library_file = clearos_app_base($app) . "/libraries/$library.php";

    if (file_exists($library_file))
        return TRUE;
    else
        return FALSE;
}

/**
 * Loads a language file.
 *
 * CodeIgniter defines the global lang() function for translations.  If the
 * CodeIgniter framework is already initialized, its lang() framework is used.
 * If the CodeIgniter framework is not in use, then the following provides this 
 * same functionality for the API without having to pull in big chunks of the 
 * CodeIgniter framework.
 *
 * @param string $lang_file language file
 *
 * @return void
 */

function clearos_load_language($lang_file)
{
    global $clearos_lang;

    // Define the lang() function if it does not exist
    //------------------------------------------------

    if (! function_exists('lang')) {

        // Create lang object
        $clearos_lang = new Lang();

        /**
         * Translation lookup
         *
         * @param string $key language key
         *
         * @return string translation
         */

        function lang($key)
        {
            global $clearos_lang;

            $translation = $clearos_lang->line($key);

            $translation = (!empty($translation)) ? $translation : '**' . $key . '**';

            return $translation;
        }
    }

    // Load language - CodeIgniter access, or direct access
    //-----------------------------------------------------

    if (defined('BASEPATH')) {
        include_once Config::get_framework_path() . '/system/core/CodeIgniter.php';
        $framework =& get_instance();
        $framework->lang->load($lang_file, '', FALSE, $lang_file);
    } else if (isset($clearos_lang)) {
        $clearos_lang->load($lang_file);
    }
}

/**
 * Pulls in a library.
 *
 * This function makes it possible to load different library versions -
 * a very useful feature in development environments.
 *
 * @param string $library library path
 *
 * @return boolean TRUE if library file exists
 */

function clearos_load_library($library)
{
    list($app, $library) = preg_split('/\//', $library, 2);

    $library_file = clearos_app_base($app) . "/libraries/$library.php";

    if (file_exists($library_file)) {
        include_once clearos_app_base($app) . "/libraries/$library.php";
        return TRUE;
    } else {
        return FALSE;
    }
}

/**
 * Logs message to syslog.
 *
 * @param string $tag     syslog tag
 * @param string $message log message
 *
 * @return void
 */

function clearos_log($tag, $message)
{
    Logger::syslog($tag, $message);
}

/**
 * Generates profiling data. 
 *
 * @param string $method  method name
 * @param string $line    line number
 * @param string $message additional profiling information
 *
 * @return void
 */

function clearos_profile($method, $line, $message = NULL)
{
    Logger::profile($method, $line, $message);
}

/**
 * Returns file path to the theme.
 *
 * @param string $theme theme name
 *
 * @return string theme URL
 */

function clearos_theme_path($theme)
{
    return Config::get_theme_path($theme);
}

/**
 * Returns URL path to the theme.
 *
 * @param string $theme theme name
 *
 * @return string theme URL
 */

function clearos_theme_url($theme)
{
    return Config::get_theme_url($theme);
}

///////////////////////////////////////////////////////////////////////////////
// G L O B A L  E R R O R  A N D  E X C E P T I O N  H A N D L E R S
///////////////////////////////////////////////////////////////////////////////

/** 
 * Error handler used by set_error_handler().
 *
 * @param integer $errno   error number
 * @param string  $errmsg  error message
 * @param string  $file    file name where occurred
 * @param integer $line    line in file where the error occurred
 * @param array   $context entire context where error was triggered
 * 
 * @access private
 * @return void
 */

function _clearos_error_handler($errno, $errmsg, $file, $line, $context)
{
    // If developer requests error suppression, then do so
    //----------------------------------------------------

    if (error_reporting() === 0)
        return;

    // Log the error
    //--------------

    $error = new Error($errno, $errmsg, $file, $line, $context, Error::TYPE_ERROR, FALSE);
    Logger::log($error);

    // Show error on standard out if running from command line
    //--------------------------------------------------------

    if (preg_match('/cli/', php_sapi_name())) {
        $errstring = $error->get_code_string();
        echo "$errstring: $errmsg - $file ($line)\n";
    }
}

/**
 * Exception handler used by set_exception_handler().
 * 
 * @param Exception $exception exception object
 *
 * @access private
 * @return void
 */

function _clearos_exception_handler(Exception $exception)
{
    // Log the exception
    //------------------

    Logger::log_exception($exception, FALSE);

    // Show error on standard out if running from command line
    //--------------------------------------------------------

    if (preg_match('/cli/', php_sapi_name()))
        echo 'Fatal - uncaught exception: ' . $exception->getMessage() . "\n";
    else
        echo '<div>Ooooops: ' . $exception->getMessage() . '</div>';
}
