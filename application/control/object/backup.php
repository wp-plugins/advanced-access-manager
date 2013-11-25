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
class aam_Control_Object_Backup extends aam_Control_Object {

    const UID = 'backup';
    const ROLEBACK_TIMES = 3;

    private $_option = null;
    private $_roleback = false;

    public function save($backup = null) {
        //define the index
        if ($this->_roleback === false) {
            if (count($this->_option) < self::ROLEBACK_TIMES) {
                $index = count($this->_option); //next index in array
            } else { //shift the array and use the last cell
                $original = array_shift($this->_option);
                $this->_option[0] = $original;
                $index = (self::ROLEBACK_TIMES - 1);
            }

            //save the backup
            $this->_option[$index] = $backup;
        }

        $this->getSubject()->updateOption($this->_option, self::UID);
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

    public function roleback() {
        $counter = count($this->_option);
        if ($counter) {
            $this->_roleback = true;
            $option = $this->_option[$counter - 1];
            unset($this->_option[$counter - 1]);
        } else {
            $option = array();
        }

        return $option;
    }

    public function has() {
        return (count($this->_option) ? true : false);
    }

}
