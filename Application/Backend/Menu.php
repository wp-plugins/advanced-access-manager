<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend menu manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Menu {

    /**
     * Get HTML content
     * 
     * @return string
     * 
     * @access public
     */
    public function getContent() {
        ob_start();
        require_once(dirname(__FILE__) . '/view/object/menu.phtml');
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Get subject's menu
     * 
     * Based on the list of capabilitis that current subject has, prepare
     * complete menu list and return it.
     * 
     * @return array
     * 
     * @access public
     * @global array  $menu
     */
    public function getMenu() {
        global $menu;
        
        $response = array();
        
        //let's create menu list with submenus
        foreach ($menu as $item) {
            if (preg_match('/^separator/', $item[2])) {
                continue; //skip separator
            }
            
            $submenu = $this->getSubmenu($item[2]);
            
            $allowed = AAM_Backend_View::getSubject()->hasCapability($item[1]);
            
            if ($allowed || count($submenu) > 0) {
                $response[] = array(
                    'name' => $this->filterMenuName($item[0]),
                    //add menu- prefix to define that this is the top level menu
                    //WordPress by default gives the same menu id to the first
                    //submenu
                    'id' => 'menu-' . $item[2],
                    'submenu' => $submenu,
                    'capability' => AAM_Backend_Helper::getHumanText($item[1])
                );
            }
        }

        return $response;
    }

    /**
     * Prepare filtered submenu
     * 
     * @param string $menu
     * 
     * @return array
     * 
     * @access public
     * @global array  $submenu
     */
    public function getSubmenu($menu) {
        global $submenu;
        
        $response = array();
        $subject = AAM_Backend_View::getSubject();
        
        if (isset($submenu[$menu])) {
            foreach ($submenu[$menu] as $item) {
                if ($subject->hasCapability($item[1])) {
                    $response[] = array(
                        'name' => $this->filterMenuName($item[0]),
                        'id' => $item[2],
                        'capability' => AAM_Backend_Helper::getHumanText(
                                $item[1]
                        )
                    );
                }
            }
        }

        return $response;
    }
    
    /**
     * Filter menu name
     * 
     * Strip any HTML tags from the menu name and also remove the trailing
     * numbers in case of Plugin or Comments menu name.
     * 
     * @param string $name
     * 
     * @return string
     * 
     * @access protected
     */
    protected function filterMenuName($name) {
        $filtered = trim(strip_tags($name));
        
        return preg_replace('/([\d]+)$/', '', $filtered);
    }

    /**
     * Check if the entire menu branch is restricted
     * 
     * @param array $menu
     * 
     * @return boolean
     * 
     * @access public
     */
    public function hasRestrictedAll($menu) {
        $object = AAM_Backend_View::getSubject()->getObject('menu');
        $response = $object->has($menu['id']);

        foreach ($menu['submenu'] as $submenu) {
            if ($object->has($submenu['id']) === false) {
                $response = false;
                break;
            }
        }

        return $response;
    }

    /**
     * Register Menu feature
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid' => 'admin_menu',
            'position' => 5,
            'title' => __('Backend Menu', AAM_KEY),
            'subjects' => array(
                'AAM_Core_Subject_Role', 'AAM_Core_Subject_User'
            ),
            'view' => __CLASS__
        ));
    }

}