<?php
/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 *
 * @package AAM
 * @author Vasyl Martyniuk <support@wpaam.com>
 * @copyright Copyright C 2013 Vasyl Martyniuk
 * @license GNU General Public License {@link http://www.gnu.org/licenses/}
 */
class aam_View_Menu extends aam_View_Abstract {

    /**
     *
     * @return type
     */
    public function content() {
        return $this->loadTemplate(dirname(__FILE__) . '/tmpl/menu.phtml');
    }

    /**
     *
     * @global type $menu
     * @global type $submenu
     * @return type
     */
    public function getMenu() {
        global $menu, $submenu;

        $response = array();
        $capability = $this->getSubject()->getObject(aam_Control_Object_Capability::UID);
        //let's create menu list with submenus
        foreach ($menu as $menu_item) {
            if (!preg_match('/^separator/', $menu_item[2])
                                            && $capability->has($menu_item[1])) {
                $item = array(
                    'name' => $this->removeHTML($menu_item[0]),
                    'id' => $menu_item[2]
                );
                if (isset($submenu[$menu_item[2]])) {
                    $item['submenu'] = array();
                    foreach ($submenu[$menu_item[2]] as $submenu_item) {
                        if ($capability->has($submenu_item[1])) {
                            $item['submenu'][] = array(
                                'name' => $this->removeHTML($submenu_item[0]),
                                'id' => $submenu_item[2]
                            );
                        }
                    }
                }
                $response[] = $item;
            }
        }

        return $response;
    }

    /**
     *
     * @param type $text
     * @return type
     */
    public function removeHTML($text) {
        // Return clean content
        return preg_replace(array("'<span[^>]*?>.*?</span[^>]*?>'si"), '', $text);
    }

}