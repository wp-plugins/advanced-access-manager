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
class aam_Control_Object_Capability extends aam_Control_Object {

    const UID = 'capability';

    private $_option = array();

    public function save($capabilities = null) {
        if (is_array($capabilities)) {
            foreach ($capabilities as $capability => $grant) {
                if (intval($grant)) {
                    $this->getSubject()->addCapability($capability);
                } else {
                    $this->getSubject()->removeCapability($capability);
                }
            }
        }
    }

    public function init($object_id) {
        if (empty($this->_option)) {
            $this->setOption($this->getSubject()->getCapabilities());
        }
    }

    public function backup() {
        return $this->getSubject()->getCapabilities();
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

    public function has($capability) {
        return $this->getSubject()->hasCapability($capability);
    }

}
