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

/**
 * Access Script Model Class
 * 
 * Access Script
 * 
 * @package AAM
 * @subpackage Models
 * @author Vasyl Martyniuk <martyniuk.vasyl@gmail.com>
 * @copyrights Copyright © 2011 Vasyl Martyniuk
 * @license GNU General Public License {@link http://www.gnu.org/licenses/}
 */
class mvb_Model_ConfigPress {

    protected static $config = NULL;

    protected static function getConfig() {

        if (self::$config == NULL) {
            require_once('Zend/Config.php');
            require_once('Zend/Config/Ini.php');
            self::$config = new Zend_Config_Ini(WPACCESS_BASE_DIR . 'config.ini');
        }

        return self::$config;
    }

    public static function saveConfig($config) {

        $file = WPACCESS_BASE_DIR . 'config.ini';
        if (is_writable($file) || chmod($file, 0755)) {
            file_put_contents($file, $config);
        }
        //also save to db as backup
        $default_blog = mvb_Model_API::getBlog(1);
        mvb_Model_API::updateBlogOption(
                WPACCESS_PREFIX . 'config_press', 
                $config, 
                $default_blog
        );
    }

    public static function readConfig() {
        
        $file = WPACCESS_BASE_DIR . 'config.ini';
        
        if (is_readable($file)){
            $config = file_get_contents(WPACCESS_BASE_DIR . 'config.ini');
        }else{
            $config = FALSE;
        }
        
        

        return $config;
    }

    /**
     * Redirect
     * 
     * @param string $area
     */
    public static function doRedirect() {

        if (is_admin()) {
            $redirect = self::getOption('backend.access');
            if (isset($redirect->deny->redirect)) {
                self::parseRedirect($redirect->deny->redirect);
            }
        } else {
            $redirect = self::getOption('frontend.access');
            if (isset($redirect->deny->redirect)) {
                self::parseRedirect($redirect->deny->redirect);
            }
        }

        if (isset($redirect->deny->message)) {
            $message = self::parseParam($redirect->deny->message);
        } else {
            mvb_Model_Label::initLabels();
            $message = mvb_Model_Label::get('LABEL_127');
        }
        wp_die($message);
    }

    /**
     * Parse Redirect
     * 
     * @todo Delete in next release
     * @param mixed
     */
    protected static function parseRedirect($redirect) {

        if (filter_var($redirect, FILTER_VALIDATE_URL)) {
            wp_redirect($redirect);
            exit;
        } elseif (is_int($redirect)) {
            wp_redirect(get_post_permalink($redirect));
            exit;
        } else {
            self::parseParam($param);
        }
    }

    protected static function parseParam($param) {

        $result = FALSE;
        if (is_object($param) && isset($param->userFunc)) {
            $func = trim($param->userFunc);
            if (is_string($func) && is_callable($func)) {
                $result = call_user_func($func);
            }
        } else {
            $result = $param;
        }

        return $result;
    }

    public static function getOption($option, $default = NULL) {

        $tree = self::getConfig();
        foreach (explode('.', $option) as $param) {
            if (isset($tree->{$param})) {
                $tree = $tree->{$param};
            } else {
                $tree = $default;
                break;
            }
        }

        return $tree;
    }

    protected static function traceOption($level, $param = NULL) {
        static $levels;

        aam_debug($level);
        if (empty($levels)) {
            $levels = explode('.', $param);
        }

        $param = array_shift($levels);
        $result = NULL;


        if (isset($level->{$param})) {
            $result = (count($levels) ? self::traceOption($level->{$param}) : $level->{$param});
        }

        return $result;
    }

}

?>