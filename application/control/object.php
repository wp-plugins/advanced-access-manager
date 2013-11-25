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
abstract class aam_Control_Object {

    private $_subject = null;

    public function __construct(aam_Control_Subject $subject, $object_id) {
        $this->setSubject($subject);
        $this->init($object_id);
    }

    public function init($object_id) {
        if (empty($this->_option)) {
            $this->setOption(
                    $this->getSubject()->readOption($this->getUID(), $object_id)
            );
        }
    }

    abstract public function getUID();

    abstract public function setOption($option);

    abstract public function getOption();

    abstract public function save($params = array());

    public function setSubject(aam_Control_Subject $subject) {
        $this->_subject = $subject;
    }

    public function getSubject() {
        return $this->_subject;
    }

}
