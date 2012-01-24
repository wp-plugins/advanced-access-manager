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
 * Labels Model Class
 * 
 * @package AAM
 * @subpackage Models
 * @author Vasyl Martyniuk <martyniuk.vasyl@gmail.com>
 * @copyrights Copyright © 2011 Vasyl Martyniuk
 * @license GNU General Public License {@link http://www.gnu.org/licenses/}
 */
class mvb_Model_AHMLabel {

    /**
     * Labels container
     * 
     * @var array
     * @access public
     */
    public static $labels = array();

    /**
     * Initialize Labels with current language
     * 
     * @return void
     */
    public static function initLabels() {
        self::$labels['LABEL_1'] = __('Advanced Health Manager', 'ahm');
        self::$labels['LABEL_2'] = __('Option List', 'ahm');
        self::$labels['LABEL_3'] = __('Alert', 'aam');
        self::$labels['LABEL_4'] = __('You have a JavaScript Error on a page', 'aam');
        self::$labels['LABEL_5'] = __('Please read <a href="http://wordpress.org/extend/plugins/advanced-access-manager/faq/" target="_blank">FAQ</a> for more information', 'aam');
        self::$labels['LABEL_6'] = __('Options updated successfully', 'aam');
        self::$labels['LABEL_7'] = __('Click to toggle', 'aam');
        self::$labels['LABEL_8'] = __('System Log', 'ahm');
        self::$labels['LABEL_9'] = __('Configurations', 'ahm');
        self::$labels['LABEL_10'] = __('', 'ahm');
        self::$labels['LABEL_11'] = __('General', 'ahm');
    }

    /**
     * Get label from store
     * 
     * @param string $label
     * @return string|bool
     */
    public static function get($label) {

        return (isset(self::$labels[$label]) ? self::$labels[$label] : FALSE);
    }

}

?>
