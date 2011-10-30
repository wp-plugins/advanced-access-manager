<?php

/*
  Copyright (C) <2011>  Vasyl Martyniuk <martyniuk.vasyl@gmail.com>

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */

/*
 * Core constants
 */
define('AUTHOR_PREFIX', 'mvb_');
define('WPACCESS_PREFIX', 'wpaccess_');
define('WPACCESS_BASE_DIR', dirname(__FILE__) . '/');
define('WPACCESS_DIRNAME', basename(WPACCESS_BASE_DIR));
/*
 * Plugin constants
 */
define('WPACCESS_BASE_URL', WP_PLUGIN_URL . '/' . WPACCESS_DIRNAME . '/');
define('WPACCESS_ADMIN_ROLE', 'administrator');
define('WPACCESS_SADMIN_ROLE', 'super_admin');
define('WPACCESS_RESTRICTION_LIMIT', 5);
define('WPACCESS_APPLY_LIMIT', 5);
define('WPACCESS_TOP_LEVEL', 10);

define('WPACCESS_TEMPLATE_DIR', WPACCESS_BASE_DIR . 'view/html/');
define('WPACCESS_CSS_URL', WPACCESS_BASE_URL . 'view/css/');
define('WPACCESS_JS_URL', WPACCESS_BASE_URL . 'view/js/');

define('WPACCESS_RESTRICT_NO', 0);
define('WPACCESS_RESTRICT_BACK', 1);
define('WPACCESS_RESTRICT_FRONT', 2);
define('WPACCESS_RESTRICT_BOTH', 3);

define('WPACCESS_FTIME_MESSAGE', WPACCESS_PREFIX . 'first_time');

$path = WPACCESS_BASE_DIR . 'module/';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);


load_plugin_textdomain('aam', false, WPACCESS_DIRNAME . '/langs');

//load general files
require_once('mvb_functions.php');
require_once('mvb_labels.php');

//load auther's private core library
if (!class_exists('mvb_corePlugin')) {
    require_once('core/class-mvb_coreplugin.php');
}
if (!class_exists('mvb_coreTemplate')) {
    require_once('core/class-mvb_coretemplate.php');
}
if (!class_exists('phpQuery')) {
    require_once('view/phpQuery/phpQuery.php');
}

//load additional classes
require_once('module/class-module_ajax.php');
require_once('module/class-module_roles.php');
require_once('module/class-module_user.php');
require_once('module/class-module_filtermenu.php');
require_once('module/class-module_filtermetabox.php');
require_once('module/class-module_optionmanager.php');

/*
 * Default capabilities for new Role
 */
$defCapabilities = array(
    'read' => 1,
    'level_0' => 1
);
?>