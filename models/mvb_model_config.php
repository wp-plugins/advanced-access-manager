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
 * Object Config Model Class
 * 
 * User Role or User Congif
 * 
 * @package AAM
 * @subpackage Models
 * @author Vasyl Martyniuk <martyniuk.vasyl@gmail.com>
 * @copyrights Copyright © 2011 Vasyl Martyniuk
 * @license GNU General Public License {@link http://www.gnu.org/licenses/}
 */
class mvb_Model_Config {

    /**
     * Access Config
     * 
     * @var object
     * @access protected
     */
    protected $access_config;

    /**
     * Admin Menu config
     * 
     * @var array
     * @access protected
     */
    protected $menu = array();

    /**
     * Metaboxes config
     * 
     * @var array 
     * @access protected
     */
    protected $metaboxes = array();

    /**
     * Capabilities list
     * 
     * @var array
     * @access protected
     */
    protected $capabilities = array();

    /**
     * Post & Taxonomy restrictions
     * 
     * @var array
     * @access protected
     */
    protected $restrictions = array();

    /**
     * Menu Order config
     * 
     * @var array
     * @access protected
     */
    protected $menu_order = array();

    /**
     * Initialize an config object
     * 
     * @param object $conf
     */
    public function __construct($conf = NULL) {

        if (is_object($conf)) {
            $this->setMenu($conf->menu);
            $this->setMetaboxes($conf->metaboxes);
            $this->setCapabilities($conf->capabilities);
            $this->setRestrictions($conf->restrictions);
            $this->setMenuOrder($conf->menu_order);
        }
    }

    /**
     * Is used to serialize config object properly in Cache is ON
     * 
     * @return array 
     */
    public function __sleep() {

        return array('menu', 'metaboxes', 'capabilities', 'restrictions', 'menu_order');
    }

    /**
     *
     * @return type 
     */
    public function compileConfig() {

        return (object) array(
                    'menu' => $this->menu,
                    'metaboxes' => $this->metaboxes,
                    'capabilities' => $this->capabilities,
                    'restrictions' => $this->restrictions,
                    'menu_order' => $this->menu_order
        );
    }

    /**
     * Load Access Config
     * 
     */
    public function loadAccessConfig() {
        $a_conf = stripslashes(mvb_Model_API::getBlogOption(WPACCESS_PREFIX . 'access_config', ''));
        if (trim($a_conf)) {
            require_once('Zend/Config.php');
            require_once('Zend/Config/Ini_Str.php');

            $this->access_config = new Zend_Config_Ini_Str($a_conf);
        }
    }

    /**
     * Redirect
     * 
     * @param string $area
     */
    public function doRedirect($area) {

      //  aam_debug($this->access_config->frontend->access->deny->redirect);
     //   die();
        switch ($area) {
            case 'backend':
                if (isset($this->access_config->backend->access->deny->redirect)) {
                    $redirect = $this->access_config->backend->access->deny->redirect;
                    $this->parseRedirect($redirect);
                } else {
                    do_action(WPACCESS_PREFIX . 'admin_redirect');
                    wp_die(mvb_Model_Label::get('restrict_message'));
                }
                break;

            case 'frontend':
                  if (isset($this->access_config->frontend->access->deny->redirect)) {
                    $redirect = $this->access_config->frontend->access->deny->redirect;
                    $this->parseRedirect($redirect);
                } else {
                    do_action(WPACCESS_PREFIX . 'front_redirect');
                    wp_redirect(home_url());
                }
                break;

            default:
                break;
        }
    }

    /**
     * Parse Redirect
     * 
     * @todo Delete in next release
     * @param mixed
     */
    protected function parseRedirect($redirect) {
        
        if (filter_var($redirect, FILTER_VALIDATE_URL)) {
            wp_safe_redirect($redirect);
        } elseif (is_int($redirect)) {
            wp_safe_redirect(get_post_permalink($redirect));
        } elseif (is_object($redirect) && isset($redirect->userFunc)) {
            $func = trim($redirect->userFunc);
            if (is_string($func) && is_callable($func)) {
                call_user_func($func);
            }
        }
        mvb_Model_Label::initLabels();
        Throw new Exception(mvb_Model_Label::get('LABEL_127'));
    }

    /**
     *
     * @param type $menu
     */
    public function setMenu($menu) {

        $this->menu = (is_array($menu) ? $menu : array());
    }

    /**
     *
     * @param type $metaboxes
     */
    public function setMetaboxes($metaboxes) {

        $this->metaboxes = (is_array($metaboxes) ? $metaboxes : array());
    }

    /**
     *
     * @param type $capabilities 
     */
    public function setCapabilities($capabilities) {

        $this->capabilities = (is_array($capabilities) ? $capabilities : array());
    }

    /**
     *
     * @param type $capability 
     */
    public function addCapability($capability) {

        if (!$this->hasCapability($capability)) {
            $this->capabilities[$capability] = 1;
        }
    }

    /**
     *
     * @param type $capability
     * @return type 
     */
    public function hasCapability($capability) {

        return (isset($this->capabilities[$capability]) ? TRUE : FALSE);
    }

    /**
     *
     * @param type $restrictions
     */
    public function setRestrictions($restrictions) {

        $this->restrictions = (is_array($restrictions) ? $restrictions : array());
    }

    /**
     *
     * @param type $type
     * @param type $id
     * @return type 
     */
    public function hasRestriction($type, $id) {

        $result = FALSE;

        switch ($type) {
            case 'post':
                $result = (isset($this->restrictions['posts'][$id]) ? TRUE : FALSE);
                break;

            case 'taxonomy':
                $result = (isset($this->restrictions['categories'][$id]) ? TRUE : FALSE);
                break;

            default:
                break;
        }

        return $result;
    }

    /**
     *
     * @param type $type
     * @param type $id
     * @return type 
     */
    public function getRestriction($type, $id) {

        $result = array();

        if ($this->hasRestriction($type, $id)) {
            switch ($type) {
                case 'post':
                    $result = $this->restrictions['posts'][$id];
                    break;

                case 'taxonomy':
                    $result = $this->restrictions['categories'][$id];
                    break;

                default:
                    break;
            }
        }

        return $result;
    }

    /**
     *
     * @param type $menu_order
     * @param type $merge 
     */
    public function setMenuOrder($menu_order, $merge = FALSE) {

        $this->menu_order = (is_array($menu_order) ? $menu_order : array());
    }

    /**
     *
     * @return type 
     */
    public function getMenu() {

        return $this->menu;
    }

    /**
     *
     * @return type 
     */
    public function getMetaboxes() {

        return $this->metaboxes;
    }

    /**
     *
     * @param type $metabox_id
     * @return type 
     */
    public function issetMetabox($metabox_id) {

        return (isset($this->metaboxes[$metabox_id]) ? TRUE : FALSE);
    }

    /**
     *
     * @return type 
     */
    public function getCapabilities() {

        return $this->capabilities;
    }

    /**
     *
     * @return type 
     */
    public function getRestrictions() {

        return $this->restrictions;
    }

    /**
     *
     * @return type 
     */
    public function getMenuOrder() {

        return $this->menu_order;
    }

    /**
     *
     * @return type 
     */
    public static function getDefaultConfig() {

        return (object) array(
                    'menu' => array(),
                    'metaboxes' => array(),
                    'capabilities' => array(),
                    'restrictions' => array(),
                    'menu_order' => array()
        );
    }

    /**
     *
     * @param object $m_conf 
     * @param bool $force_replace
     */
    public function mergeConfigs($m_conf, $force_replace = FALSE) {
        //TODO - Junk
        if ($force_replace) {
            $this->menu = $m_conf->getMenu();
            $this->metaboxes = $m_conf->getMetaboxes();
        } else {
            $this->menu = mvb_Model_Helper::array_merge_recursive($this->menu, $m_conf->getMenu());
            $this->metaboxes = mvb_Model_Helper::array_merge_recursive($this->metaboxes, $m_conf->getMetaboxes());
        }
        //capabilities
        $this->capabilities = array_merge($this->capabilities, $m_conf->getCapabilities());
        //restrictions
        //TODO - Role with lower level should be overwriten by higher
        $this->restrictions = mvb_Model_Helper::array_merge_recursive($this->restrictions, $m_conf->getRestrictions());
        //menu order
        $this->menu_order = (count($m_conf->getMenuOrder()) ? $m_conf->getMenuOrder() : $this->menu_order);
    }

}

?>