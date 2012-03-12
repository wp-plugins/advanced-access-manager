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

function aam_debug($what) {
    echo '<pre>';
    print_r($what);
    echo '</pre>';
}

function init_wpaccess() {
    global $mvb_wpAccess;

    $mvb_wpAccess = new mvb_WPAccess();
}

/**
 * Autoloader for project Advanced Access Manager
 * 
 * Try to load a class if prefix is mvb_
 * 
 * @param string $class_name 
 */
function mvb_autoload($class_name) {

    $parts = preg_split('/_/', $class_name);
    if ($parts[0] == 'mvb') {
        //check what type of class do we need to load
        switch ($parts[1]) {
            case 'Model':
                $path = WPACCESS_BASE_DIR . 'models/';
                break;

            case 'Abstract':
                $path = WPACCESS_BASE_DIR . 'models/abstract/';
                break;

            default:
                $path = '';
                break;
        }
        $file_path = $path . strtolower($class_name) . '.php';

        require_once($file_path);
    }
}

spl_autoload_register('mvb_autoload');

/**
 * Merget to configs
 * 
 * @param object $config
 * @param object $m_config 
 */
function mvb_merge_configs($config, $m_config) {

    if (!count($config->getMenu())) {
        $config->setMenu($m_config->getMenu());
    }

    if (!count($config->getMetaboxes())) {
        $config->setMetaboxes($m_config->getMetaboxes());
    }

    if (!count($config->getMenuOrder())) {
        $config->setMenuOrder($m_config->getMenuOrder());
    }

//    if (mvb_Model_Helper::isLowerLevel($config, $m_config)) {
//    }

    $caps = array_merge($config->getCapabilities(), $m_config->getCapabilities());
    $config->setCapabilities($caps);

    $rests = mvb_Model_Helper::array_merge_recursive($m_config->getRestrictions(), $config->getRestrictions());
    $config->setRestrictions($rests, FALSE);

    $excludes = mvb_Model_Helper::array_merge_recursive($config->getExcludes(), $m_config->getExcludes());
    $config->setExcludes($excludes);

    return $config;
}

function aam_set_current_user() {
    global $current_user;

    //overwrite user capabilities
    //TODO - Not optimized
    $config = mvb_Model_API::getUserAccessConfig($current_user->ID);

    if ($config instanceof mvb_Model_UserConfig) {
        $current_user->allcaps = $config->getCapabilities();
        if ($config->getUser() instanceof WP_User) {
            foreach ($config->getUser()->getRoles() as $role) {
                $current_user->allcaps[$role] = 1;
            }
        }
    }
}

function mvb_warning() {
    echo "<div id='mvb-warning' class='updated fade'>
        <p><strong>" . __('Advaned Access Manager will not work properly in fact other Error Handler detected.') . "</strong></p></div>";
}

//autoloading is not working during error handling
require_once('models/mvb_model_errorhandler.php');

if (set_error_handler('mvb_Model_errorHandler::handle')) {
    add_action('admin_notices', 'mvb_warning');
}
register_shutdown_function('mvb_Model_errorHandler::fatalHandler');
?>