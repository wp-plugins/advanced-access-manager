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
class aam_Control_Object_Term extends aam_Control_Object {

    const UID = 'term';
    const ACTION_BROWSE = 'browse';
    const ACTION_EDIT = 'edit';

    private $_term = null;
    private $_option = array();

    public function save($params = null) {
        if (is_array($params)) {
            $this->getSubject()->updateOption(
                    $params, self::UID, $this->getTerm()->term_id
            );
        }
    }

    public function getAccessList($area) {
        if ($area == 'frontend') {
            $response = array(self::ACTION_BROWSE);
        } elseif ($area == 'backend') {
            $response = array(self::ACTION_BROWSE, self::ACTION_EDIT);
        } else {
            $response = array();
        }

        return apply_filters('aam_term_access_list', $response, $area);
    }

    public function getUID() {
        return self::UID;
    }

    public function init($object_id) {
        if ($object_id && empty($this->_option)) {
            //initialize term first
            $this->setTerm(get_term($object_id, $this->getTaxonomy($object_id)));
            if ($this->getTerm()) {
                $access = $this->getSubject()->readOption(
                        self::UID, $this->getTerm()->term_id
                );
                if (empty($access)) {
                    //try to get any parent restriction
                    $access = $this->inheritAccess($this->getTerm()->parent);
                }

                $this->setOption(
                        apply_filters('aam_term_access_option', $access, $this->getSubject())
                );
            } else {
                aam_Core_Console::write("Term {$object_id} does not exist");
            }
        }
    }

    public function delete() {
        return $this->getSubject()->deleteOption(
                        self::UID, $this->getTerm()->term_id
        );
    }

    private function inheritAccess($term_id) {
        $term = new aam_Control_Object_Term($this->getSubject(), $term_id);
        if ($term->getTerm()) {
            $access = $term->getOption();
            if (empty($access) && $term->getTerm()->parent) {
                $this->inheritAccess($term->getTerm()->parent);
            } elseif (!empty($access)) {
                $access['inherited'] = true;
            }
        } else {
            $access = array();
        }

        return $access;
    }

    private function getTaxonomy($object_id) {
        global $wpdb;

        $query = "SELECT taxonomy FROM {$wpdb->term_taxonomy} ";
        $query .= "WHERE term_id = {$object_id}";

        return $wpdb->get_var($query);
    }

    public function setTerm($term) {
        $this->_term = $term;
    }

    public function getTerm() {
        return $this->_term;
    }

    public function setOption($option) {
        $this->_option = (is_array($option) ? $option : array());
    }

    public function getOption() {
        return $this->_option;
    }

    public function has($area, $action) {
        $response = false;
        if (isset($this->_option['term'][$area][$action])) {
            $response = (intval($this->_option['term'][$area][$action]) ? true : false);
        }

        return $response;
    }

}
