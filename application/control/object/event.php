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
class aam_Control_Object_Event extends aam_Control_Object {

    const UID = 'event';

    private $_option = array();

    public function save($events = null) {
        if (is_array($events)) {
            $this->getSubject()->updateOption($events, self::UID);
        }
    }

    public function backup() {
        return $this->getSubject()->readOption(self::UID, '', array());
    }

    public function getUID() {
        return self::UID;
    }

    public function setOption($option) {
        $this->_option = (is_array($option) ? $option : array());
    }

    public function getOption() {
        return $this->_option;
    }

}
