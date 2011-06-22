<?php

/**
 * Webconfig page class.
 *
 * @category   Framework
 * @package    Application
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/
 */

///////////////////////////////////////////////////////////////////////////////
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
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\framework\Logger as Logger;
use \clearos\framework\Config as Config;

clearos_load_language('framework');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Webconfig page class.
 *
 * This class provides the mechanism for managing the type of a webconfig
 * page.  The view of a given ClearOS App is managed by the app developer,
 * while the view of the following is managed by this class:
 *
 * - Header 
 * - Footer
 * - Menu
 * - Summary
 * - Status messages
 *
 * This class handles the data management (e.g. parsing menu meta data), 
 * while the theme developer handles the look and feel.  This class also 
 * provides the following variables for use in their overall webconfig page:
 *
 * - login (if logged in)
 * - full name (if logged in)
 * - hostname
 * - registration status
 * - locale
 * - OS name
 * - OS version
 * - theme
 * - theme mode
 * - app 16x16 icon
 * - app 32x32 icon
 * - success message (e.g. firewall rule has been deleted)
 * - warning message (e.g. OpenVPN is running, but the firewall is not allowing connections)
 * - page type (default, splash, wizard(?))
 * - page title
 *
 * This class also handles exceptions.  When an exception occurs in a
 * controller, further processing is halted.  The exception is then shown
 * via the handle_exception() method.
 *
 * @category   Framework
 * @package    Application
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/
 */

class MY_Page
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const TYPE_CONFIGURATION = 'configuration';
    const TYPE_REPORT = 'report';
    const TYPE_SPLASH = 'splash';
    const TYPE_WIZARD = 'wizard';

    const MODE_CONTROL_PANEL = 'control_panel';
    const MODE_NORMAL = 'normal';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $framework = NULL;
    public $data = array();
    public $form_only = FALSE;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Page constructor.
     */

    public function __construct()
    {
        Logger::profile_framework(__METHOD__, __LINE__, 'Page Class Initialized');

        $this->framework =& get_instance();
        $this->framework->lang->load('framework');
    }

    /**
     * Clears the cache directory
     */

    public function clear_cache()
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $menu_cache = CLEAROS_TEMP_DIR . '/menu_cache_' . $this->framework->session->userdata('session_id') . 
            $_SERVER['SERVER_PORT'];

        if ($handle = opendir(CLEAROS_TEMP_DIR)) {
            while (false !== ($file = readdir($handle))) {
                if (preg_match('/^menu_cache_/', $file))
                    unlink(CLEAROS_TEMP_DIR . '/' . $file);
            }
        }
    }

    /**
     * Loads the required theme files. 
     *
     * The theme hooks are loaded after the controller has been initialized.
     * - doctype.php
     * - head.php
     * - page.php
     * - widgets.php
     *
     * This is called by a CodeIgniter hook instead of the constructor since
     * the user session has not been initialized in the constructor.
     *
     * @access private
     * @return void
     */

    public function load_theme()
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $theme_files = array('doctype.php', 'head.php', 'page.php', 'widgets.php');
        $path = Config::get_theme_path($this->framework->session->userdata('theme'));

        foreach ($theme_files as $file) {
            Logger::profile_framework(__METHOD__, __LINE__, "Loading theme file $file");
            $full_path = $path . '/core/' . $file;

            if (file_exists($full_path))
                include $full_path;
            else
                echo "<p class='alert'>Theme file is missing: $file</p>";
        }
    }

    /**
     * Handles status added message.
     *
     * @return void
     */

    public function set_status_added()
    { 
        Logger::profile_framework(__METHOD__, __LINE__);

        $message = lang('framework_item_was_added');

        $this->framework->session->set_userdata('status_success', $message);
    }

    /**
     * Handles status deleted message.
     *
     * @return void
     */

    public function set_status_deleted()
    { 
        Logger::profile_framework(__METHOD__, __LINE__);

        $message = lang('framework_item_was_deleted');

        $this->framework->session->set_userdata('status_success', $message);
    }

    /**
     * Handles status disabled message.
     *
     * @return void
     */

    public function set_status_disabled()
    { 
        Logger::profile_framework(__METHOD__, __LINE__);

        $message = lang('framework_item_was_disabled');

        $this->framework->session->set_userdata('status_success', $message);
    }

    /**
     * Handles status enabled message.
     *
     * @return void
     */

    public function set_status_enabled()
    { 
        Logger::profile_framework(__METHOD__, __LINE__);

        $message = lang('framework_item_was_enabled');

        $this->framework->session->set_userdata('status_success', $message);
    }

    /**
     * Handles status updated message.
     *
     * @return void
     */

    public function set_status_updated()
    { 
        Logger::profile_framework(__METHOD__, __LINE__);

        $message = lang('framework_system_updated');

        $this->framework->session->set_userdata('status_success', $message);
    }

    /**
     * Handles a page message.
     *
     * @param string $message message
     * @param string $code    code
     * @param string $title   $title
     *
     * @return void
     */

    public function set_message($message, $code = 'warning', $title = NULL)
    { 
        Logger::profile_framework(__METHOD__, __LINE__);

        if (empty($title))
            $title = ($code === 'warning') ? lang('framework_warning') : lang('framework_information');

        $this->framework->session->set_userdata('message_code', $code);
        $this->framework->session->set_userdata('message_text', $message);
        $this->framework->session->set_userdata('message_title', $title);
    }

    /**
     * Redirects depending on theme mode.
     *
     * @param string $redirect redirect URL
     *
     * @return void
     */

    public function theme_redirect($redirect)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        // Non-intuitive, see view_forms for form_only discussion
        if ($this->form_only)
            return;

        if ($this->framework->session->userdata['theme_mode'] !== self::MODE_CONTROL_PANEL)
            redirect($redirect);
    }

    /**
     * Displays generic confirmation.
     *
     * @return view
     */

    public function view_confirm($message, $confirm, $cancel, $options = array())
    {
        Logger::profile_framework(__METHOD__, __LINE__);
   
        if (empty($this->data))
            $this->_load_meta_data();

        $app = $this->framework->uri->segment(1);

        $this->data['title'] = lang('framework_confirm');
        $this->data['app_view'] = theme_confirm($confirm, $cancel, $message, $options);
        $this->data['page_help'] = $this->_get_help_view($app);
        $this->data['page_summary'] = $this->_get_summary_view($app);
        $this->data['page_report'] = $this->_get_report_view($app);

        $this->_display_page();
    }

    /**
     * Displays delete confirmation.
     *
     * @return view
     */

    public function view_confirm_delete($confirm, $cancel, $items, $options = array())
    {
        Logger::profile_framework(__METHOD__, __LINE__);
   
        if (empty($this->data))
            $this->_load_meta_data();

        $app = $this->framework->uri->segment(1);

        $message = isset($options['message']) ? $options['message'] : lang('framework_are_you_sure_delete');

        $this->data['title'] = lang('framework_confirm_delete');
        $this->data['app_view'] = theme_confirm_delete($confirm, $cancel, $items, $message, $options);
        $this->data['page_help'] = $this->_get_help_view($app);
        $this->data['page_summary'] = $this->_get_summary_view($app);
        $this->data['page_report'] = $this->_get_report_view($app);

        $this->_display_page();
    }

    /**
     * Displays a page with a single form.
     *
     * @return view
     */

    public function view_form($form, $data, $title, $options = array())
    {
        Logger::profile_framework(__METHOD__, __LINE__);

/*
        // FIXME: what to do with help and summary widgets 
        if ($this->framework->session->userdata['theme_mode'] === self::MODE_CONTROL_PANEL) {
        }
*/

        if (empty($this->data))
            $this->_load_meta_data(array($form));

        $type = isset($options['type']) ? $options['type'] : MY_Page::TYPE_CONFIGURATION;

        $this->data['title'] = $title;
        $this->data['type'] = $type;

        // Non-intuitive: see view_forms for form_only explanation
        
        if ($this->form_only) {
            $this->framework->load->view($form, $data);
        } else {
            // More non-intuitive stuff.  When we are *not* running in "control panel" mode,
            // the user should see a full page summary once an action (e.g. adding a port
            // forward firewall) takes place.
            if ($this->framework->session->userdata['theme_mode'] !== self::MODE_CONTROL_PANEL) {
                $segments = preg_split('/\//', uri_string());
                $app_name = $segments[1];
                $controller = $segments[2];
                $action = $segments[3];

                $app_data = $this->_load_app_data();

                if (!$action && isset($app_data['controllers'][$controller]['title']))
                    redirect('/' . $app_name);
            }

            $this->data['app_view'] = $this->framework->load->view($form, $data, TRUE);
            $this->data['page_help'] = $this->_get_help_view($form);
            $this->data['page_summary'] = $this->_get_summary_view($form);
            $this->data['page_report'] = $this->_get_report_view($form);

            $this->_display_page();
        }
    }

    /**
     * Displays a page with multiple forms.
     *
     * @return view
     */

    public function view_forms($forms, $title)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $this->_load_meta_data($forms);

        $this->data['title'] = $title;

        // Control panel style
        //--------------------

        if ($this->framework->session->userdata['theme_mode'] === self::MODE_CONTROL_PANEL) {

            $app_data = $this->_load_app_data();

            foreach ($forms as $form) {
                $basename = preg_replace('/.*\//', '', $form);
                if (isset($$app_data['controllers'][$basename]['title']))
                    $data[$form]['title'] = $app_data['controllers'][$basename]['title'];
                else if (isset($$app_data['controllers'][$form]['title']))
                    $data[$form]['title'] = $app_data['controllers'][$form]['title'];
                else
                    $data[$form]['title'] = $form;
            }

            // Add common widgets
            $basename = preg_replace('/\/.*/', '', $form);
            $data[$basename . '/summary']['title'] = lang('framework_summary');
            $data[$basename . '/help']['title'] = lang('framework_help');

            $this->data['app_view'] = theme_control_panel($data);

        // Full desktop style
        //-------------------

        } else {
            // Non-intuitive, but this saves app developers from handling a 
            // useless variable in their controllers.  The form_only variable
            // is set to TRUE to indicate that only the raw form should be 
            // loaded (no headers, no footers, etc.).

            $this->form_only = TRUE; 

            ob_start();

            foreach ($forms as $form) {
                $basename = preg_replace('/.*\//', '', $form);

                // TODO: this is a hack for the "daemon" widget
                // This should be generalized of course
                if (preg_match('/\/index\//', $form)) {
                    $params = preg_replace('/.*index\//', '', $form);
                } else {
                    $params = '';
                }

                $this->framework->load->module($form);
                $this->framework->$basename->index($params);
            }

            $this->data['app_view'] = ob_get_clean();

            // Now we set form_only back to the default
            $this->form_only = FALSE; 

            $this->data['page_help'] = $this->_get_help_view($form);
            $this->data['page_summary'] = $this->_get_summary_view($form);
            $this->data['page_report'] = $this->_get_report_view($form);
        }

        $this->_display_page();
    }

    /**
     * Displays the exception view.
     *
     * @param Exception $exception exception
     *
     * @return void
     */

    public function view_exception($exception)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        if (empty($this->data))
            $this->_load_meta_data();

        if ($this->form_only) {
            echo "<div style='clear: both'>"; // FIXME - div should not be necessary
            echo infobox_critical(clearos_exception_message($exception));
            echo "</div>";
        } else {
            $this->data['type'] = MY_Page::TYPE_REPORT;
            $this->data['title'] = 'Ooops';
            $this->data['app_view'] = theme_dialog_warning(clearos_exception_message($exception));
            $this->_display_page();
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // F R I E N D  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////
    //
    // These are for internal framework use and not intended for app developers.
    //
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Display help box.
     *
     * @access private
     */

    public function view_help($form)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $this->data = array();
        $this->_load_meta_data();

        $this->data['title'] = lang('framework_help');
        $this->data['type'] = MY_Page::TYPE_CONFIGURATION;
        $this->data['app_view'] = $this->_get_help_view($form);

        $this->_display_page();
    }

    /**
     * Display report box.
     *
     * @access private
     */

    public function view_report($form)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $this->data = array();
        $this->_load_meta_data();

        $this->data['title'] = lang('framework_dashboard_report');
        $this->data['type'] = MY_Page::TYPE_CONFIGURATION;
        $this->data['app_view'] = $this->_get_report_view($form);

        $this->_display_page();
    }

    /**
     * Display summary box.
     *
     * @access private
     */

    public function view_summary($form)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $this->data = array();
        $this->_load_meta_data();

        $this->data['title'] = lang('framework_summary');
        $this->data['type'] = MY_Page::TYPE_CONFIGURATION;
        $this->data['app_view'] = $this->_get_summary_view($form);

        $this->_display_page();
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the HTML head section.
     *
     * @param array $page_data page data
     *
     * @return string HTML head section
     */

    protected function _build_page_head($page_data)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        // Adding hostname to the title is handy when managing multiple systems
        //---------------------------------------------------------------------

        $title = $page_data['title'];

        if ($this->framework->session->userdata('hostname'))
            $title = $this->framework->session->userdata('hostname') . " - " . $title;

        // Determine the version to use (trunk, 6.0, etc)
        //-----------------------------------------------

        $segments = explode('/', $_SERVER['PHP_SELF']);
        $app = $segments[2];

        $doc_base = clearos_app_base($app) . '/htdocs/';
        $app_url = Config::get_app_url($app);
        $theme_path = Config::get_theme_url($this->framework->session->userdata('theme'));

        // Add page-specific head links
        //-----------------------------

        $css =  $app . '.css';
        $js = $app . '.js.php';

        $page_auto_head = '';

        foreach ($page_data['javascript'] as $javascript)
            $page_auto_head .= "<script type='text/javascript' src='" . $javascript . "'></script>\n";

        if (file_exists($doc_base . '/' . $css))
            $page_auto_head .= "<link type='text/css' href='" . $app_url . '/' . $css ."' rel='stylesheet'>\n";

        // <html>
        //-------------------
        
        $head = "<html dir='" . $this->framework->session->userdata('textdir') . "'>\n\n";

        // <head> commom
        //-------------------

        $head .= "<!-- Head -->
<head>

<!-- Basic Head Information -->
<title>$title</title>
<meta http-equiv='Content-Type' content='text/html; charset=" . $this->framework->session->userdata('charset') . "'>

<!-- Jquery -->
<script type='text/javascript' src='/js/jquery-1.6.1.min.js'></script>
";
        // <head> extras defined in theme (head.php)
        //------------------------------------------

        $head .= theme_page_head($theme_path);

        // <head> extras defined in app
        //------------------------------------------

        if ($page_auto_head)
            $head .= "<!-- Page-specific Head -->\n$page_auto_head\n";

        // </head> all done
        //------------------------------------------

        $head .= "</head>\n\n";

        return $head;
    }

    /**
     * Displays the webconfig page.
     *
     * @return string HTML of webconfig page
     */

    protected function _display_page()
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        echo theme_page_doctype() . "\n";
        echo $this->_build_page_head($this->data);
        echo theme_page($this->data);
    }

    /**
     * Returns the help view.
     *
     * @return string HTML for help view
     */

    public function _get_help_view($form)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $data = $this->_load_app_data();

        // FIXME: Move this to a driver package
        if (empty($data['user_guide_url']))
            $data['user_guide_url'] = 'http://www.clearcenter.com/redirect/ClearOS_Enterprise/6.0.0/userguide/' . $data['basename'];

        if (empty($data['support_url']))
            $data['support_url'] = 'http://www.clearcenter.com/getsupport';

        return theme_help_box($data);
    }

    /**
     * Returns the report view.
     *
     * Returns NULL if no report exists for the given form.
     *
     * @return string HTML for report box
     */

    protected function _get_report_view($form)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $basename = preg_replace('/.*\//', '', $form);

        $this->framework->load->module($form);

        if (! method_exists($this->framework->$basename, 'report'))
            return;

        ob_start();
        $this->framework->$basename->report();
        $report = ob_get_clean();

        return $report;
    }

    /**
     * Returns the summary view.
     *
     * @return string HTML for summary view
     */

    public function _get_summary_view($form)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $data = $this->_load_app_data();

        $data['tooltip'] = (isset($data['controllers'][$form]['tooltip'])) ? $data['controllers'][$form]['tooltip'] : '';

        // FIXME: fake data here
        $data['subscription_expiration'] = 'August 1, 2011';

        return theme_summary_box($data);
    }

    /**
     * Returns app data in an array.
     *
     * @return array app meta data
     */

    protected function _load_app_data($app_name = NULL)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        if (is_null($app_name)) {
            $segments = explode('/', $_SERVER['PHP_SELF']);
            $app_name = $segments[2];
        }

		$app = array();
		$app_base = clearos_app_base($app_name);

        $info_file = $app_base . '/deploy/info.php';

        if (file_exists($info_file)) {

            // Load metadata file
            clearos_load_language($app_name);
            include $info_file;

            // Add timestamp
            $stat = stat($info_file);
            $app['modified'] = $stat['ctime'];
        }

		$icon_basename = $app_name . '_50x50.png';
		$icon_path = $app_base . '/htdocs/' . $icon_basename;

		if (file_exists($icon_path))
			$app['icon_path'] = clearos_app_htdocs($app_name) . '/' . $icon_basename;
		else
			$app['icon_path'] = '/assets/app_default_50x50.png';

		return $app;
    }

    /**
     * Returns menu data in an array.
     *
     * @return array menu meta data
     */

    protected function _load_menu_data()
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        // Create a list of apps to check
        //-------------------------------

        clearstatcache();

        $apps_list = array();
        $most_recent = 0;

        foreach (Config::$apps_paths as $path) {
            // TODO: remove - it's just a temporary workaround for a pre-release version
            $path = (preg_match('/apps$/', $path)) ? $path : $path . '/apps';

            $raw_list = scandir($path);

            foreach ($raw_list as $dir) {
                if (! preg_match('/^\./', $dir)) {
                    $info_file = clearos_app_base($dir) . '/deploy/info.php';
                    if (file_exists($info_file)) {
                        $apps_list[] = $dir;

                        $stat = stat($info_file);

                        if ($stat['ctime'] > $most_recent)
                            $most_recent = $stat['ctime'];
                    }
                }
            }
        }

        // If timestamps are okay, use the cache file
        //-------------------------------------------

        $menu_cache = CLEAROS_TEMP_DIR . '/menu_cache_' . $this->framework->session->userdata('session_id') . 
            $_SERVER['SERVER_PORT'];

        if (file_exists($menu_cache)) {
            $stat = stat($menu_cache);
            $cache_time = $stat['ctime'];

            if ($cache_time > $most_recent)
                return unserialize( file_get_contents($menu_cache) );
        }

        // Load menu order preferences
        //----------------------------

        $primary_order = array(
            lang('base_category_marketplace') => '010',
            lang('base_category_server')  => '020',
            lang('base_category_network') => '030',
            lang('base_category_gateway') => '040',
            lang('base_category_system')  => '050',
        );

        $secondary_order = array(
            lang('base_subcategory_settings') => '010',
            lang('base_subcategory_accounts') => '020',
        );

        // Create an array with the sort key
        //----------------------------------

        $sorted = array();

        foreach ($apps_list as $app_name) {
            $app = $this->_load_app_data($app_name);

            if (! isset($app['basename'])) 
                continue;

            // If menu is disabled, skip it
            if (isset($app['menu_enabled']) && (!$app['menu_enabled']))
                continue;

            // If this is just a library, skip it
            $views_dir = Config::get_app_base($app_name) . '/views';
            if (! is_dir($views_dir))
                continue;

            $primary_sort = empty($primary_order[$app['category']]) ? '500' : $primary_order[$app['category']];
            $secondary_sort = empty($secondary_order[$app['subcategory']]) ? $app['subcategory'] : $secondary_order[$app['subcategory']];
            $page_sort = empty($app['priority']) ? '500' : $app['priority'];

            $menu_info = array();

            $menu_info['/app/' . $app['basename']] = array(
                'title' => $app['name'],
                'category' => $app['category'],
                'subcategory' => $app['subcategory'],
            );

            $sorted[$primary_sort . '.' . $secondary_sort . '.' . $page_sort . '.' . $app['name']] = $menu_info;
        }

        // Use the sorted array to generate the menu array
        //------------------------------------------------

        ksort($sorted);

        $menu_data = array();

        foreach ($sorted as $sort_key => $sort_details) {
            foreach ($sort_details as $url => $details)
                $menu_data[$url] = $details;
        }

        // Cache the data and return it
        //-----------------------------

        file_put_contents($menu_cache, serialize($menu_data));

        return $menu_data;
    }

    /**
     * Loads the page meta data into the data class variable.
     *
     * @return void
     */

    protected function _load_meta_data($forms = NULL)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $view_data = $this->_load_view_data($forms);
        $menu_data['menus'] = $this->_load_menu_data();
        $session_data = $this->_load_session_data();

        $this->data = array_merge($this->data, $view_data, $session_data, $menu_data);
    }

    /**
     * Returns page session data in an array.
     *
     * @return array session meta data
     */

    protected function _load_session_data()
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $session_data = array();

        // Grab all the session values
        //----------------------------

        foreach ($this->framework->session->userdata as $key => $value)
            $session_data[$key] = $value;

        // The "status_success" message is a flash value... delete it
        //-----------------------------------------------------------

        if ($this->framework->session->userdata('status_success'))
            $this->framework->session->unset_userdata('status_success');

        return $session_data;
    }

    /**
     * Returns view page data in an array.
     *
     * @return array view meta data
     */

    protected function _load_view_data($forms = NULL)
    {
        Logger::profile_framework(__METHOD__, __LINE__);

        $view_data = array();

        // Page layout type
        //-----------------

        if (empty($this->data['type']))
            $view_data['type'] = MY_Page::TYPE_CONFIGURATION;

        // Javascript hooks
        //-----------------

        $view_data['javascript'] = array();

		// TODO: clean up logic - quick hack
        foreach ($forms as $form) {
            $segments = preg_split('/\//', $form);
			$app = $segments[0];

            if (isset($segments[1])) {
                $javascript_basename = $segments[1] . '.js.php';
				$javascript = clearos_app_base($app) . '/htdocs/' . $javascript_basename;

				if (file_exists($javascript)) {
					$app_url = Config::get_app_url($app);
					$view_data['javascript'][] = $app_url . '/' . $javascript_basename;
				}
			}

			$javascript_basename = $segments[0] . '.js.php';
			$javascript = clearos_app_base($app) . '/htdocs/' . $javascript_basename;

			if (file_exists($javascript)) {
				$app_url = Config::get_app_url($app);
				$view_data['javascript'][] = $app_url . '/' . $javascript_basename;
			}
        }

        return $view_data;
    }
}
