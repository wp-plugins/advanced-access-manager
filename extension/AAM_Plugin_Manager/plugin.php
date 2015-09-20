<?php

/**
  Copyright (C) <2013-2014>  Vasyl Martyniuk <support@wpaam.com>

  This program is commercial software: you are not allowed to redistribute it
  and/or modify. Unauthorized copying of this file, via any medium is strictly
  prohibited.
  For any questions or concerns contact Vasyl Martyniuk <support@wpaam.com>
 */

/**
 *
 * @package AAM
 * @author Vasyl Martyniuk <support@wpaam.com>
 * @copyright Copyright C 2013 Vasyl Martyniuk
 * @license GNU General Public License {@link http://www.gnu.org/licenses/}
 */
class aam_Control_Object_Plugin extends aam_Control_Object {

    /**
     * Object Unique ID
     */
    const UID = 'plugin';

    /**
     * List of options
     *
     * @var array
     *
     * @access private
     */
    private $_option = array();

    /**
     * @inheritdoc
     */
    public function save($menu = null) {
        if (is_array($menu)) {
            $this->getSubject()->updateOption($menu, self::UID);
            //set flag that this subject has custom settings
            $this->getSubject()->setFlag(aam_Control_Subject::FLAG_MODIFIED);
        }
    }

    /**
     * @inheritdoc
     */
    public function cacheObject(){
        return false;
    }

    /**
     *
     * @return type
     */
    public function getUID() {
        return self::UID;
    }

    /**
     *
     * @param type $option
     */
    public function setOption($option) {
        $this->_option = (is_array($option) ? $option : array());
    }

    /**
     *
     * @return type
     */
    public function getOption() {
        return $this->_option;
    }

    /**
     * 
     * @param type $plugin
     * @param type $action
     * 
     * @return type
     */
    public function has($plugin, $action) {
        if (isset($this->_option[$plugin][$action])) {
            $response = ($this->_option[$plugin][$action] ? true : false);
        } else {
            $response = null;
        }

        return $response;
    }

}