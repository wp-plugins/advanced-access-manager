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
 * Capabilities Manager
 * 
 * @package AAM
 * @subpackage Model
 */
class mvb_Model_Manager_Capability {

    /**
     *
     * @global array $submenu
     * @param string $tmpl
     * @param mvb_Model_Manager $parent
     * @return string 
     */
    public static function render($tmpl, $parent) {

        $all_caps = mvb_Model_API::getAllCapabilities();
        $list = '';
        if (is_array($all_caps) && count($all_caps)) {
            ksort($all_caps);
            $list_tmpl = mvb_Model_Template::retrieveSub(
                            'CAPABILITY_LIST', $tmpl
            );
            $item_tmpl = mvb_Model_Template::retrieveSub(
                            'CAPABILITY_ITEM', $list_tmpl
            );
            $conf = mvb_Model_ConfigPress::getOption('aam.delete_capabilities');
            $allow_delete = ($conf == 'true' ? TRUE : FALSE);
            foreach ($all_caps as $cap => $dumy) {
                $list .= self::renderRow($cap, $item_tmpl, $parent, $allow_delete);
            }
            $content = mvb_Model_Template::replaceSub(
                            'CAPABILITY_LIST', $list, $tmpl
            );
            $content = mvb_Model_Template::replaceSub(
                            'CAPABILITY_LIST_EMPTY', '', $content
            );
        } else {
            $empty = mvb_Model_Template::retrieveSub(
                            'CAPABILITY_LIST_EMPTY', $tmpl
            );
            $content = mvb_Model_Template::replaceSub(
                            'CAPABILITY_LIST', '', $tmpl
            );
            $content = mvb_Model_Template::replaceSub(
                            'CAPABILITY_LIST_EMPTY', $empty, $content
            );
        }

        return $content;
    }

    public static function renderRow($cap, $tmpl, $parent, $allow_delete) {
        
        $desc = str_replace("\n", '<br/>', mvb_Model_Label::get($cap));
        if (!$desc){
            $desc = mvb_Model_Label::get('LABEL_117');
        }
        $markers = array(
            '###title###' => $cap,
            '###description###' => $desc,
            '###checked###' => ($parent->getConfig()->hasCapability($cap) ? 'checked' : ''),
            '###cap_name###' => mvb_Model_Helper::getHumanTitle($cap)
        );
        $content = mvb_Model_Template::updateMarkers($markers, $tmpl);
        if ($allow_delete) {
            $del_tmpl = mvb_Model_Template::retrieveSub(
                            'CAPABILITY_DELETE', $content
            );
            $content = mvb_Model_Template::replaceSub(
                            'CAPABILITY_DELETE', $del_tmpl, $content
            );
        } else {
            $content = mvb_Model_Template::replaceSub(
                            'CAPABILITY_DELETE', '', $content
            );
        }
        
        return $content;
    }
}

?>