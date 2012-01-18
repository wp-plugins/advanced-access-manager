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

    //check which config has highest user level and overwrite lower
    if (mvb_Model_Helper::isLowerLevel($config, $m_config)) {
        $config->setMenu($m_config->getMenu());
        $config->setMetaboxes($m_config->getMetaboxes());
        if (count($m_config->getMenuOrder())) {
            $config->setMenuOrder($m_config->getMenuOrder());
        }
    }

    $caps = array_merge($config->getCapabilities(), $m_config->getCapabilities());
    $config->setCapabilities($caps);

    $rests = mvb_Model_Helper::array_merge_recursive($config->getRestrictions(), $m_config->getRestrictions());
    $config->setRestrictions($rests, FALSE);

    return $config;
}

function aam_set_current_user() {
    global $current_user;

    //overwrite user capabilities
    //TODO - Not optimized
    $config = mvb_Model_API::getUserAccessConfig($current_user->ID);
    $current_user->allcaps = $config->getCapabilities();
    if ($config->getUser() instanceof WP_User) {
        foreach ($config->getUser()->getRoles() as $role) {
            $current_user->allcaps[$role] = 1;
        }
    }
}

?>