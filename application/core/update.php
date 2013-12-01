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
final class aam_Core_Update {

    /**
     * List of stages
     * 
     * @var array
     * 
     * @access private
     */
    private $_stages = array();

    /**
     * Constructoor
     * 
     * @return void
     * 
     * @access public
     */
    public function __construct() {
        //register update stages
        $this->_stages = apply_filters('aam_update_stages', array(
            array($this, 'clearRepository'),
            array($this, 'removeUpdate')
        ));
    }

    /**
     * Run the update if necessary
     * 
     * @return void
     * 
     * @access public
     */
    public function run() {
        foreach ($this->_stages as $stage) {
            //break the change if any stage failed
            if (call_user_func($stage) === false) {
                break;
            }
        }
    }

    /**
     * Clear the Extension Repository
     * 
     * This forces the system to retrieve the new set of extensions based on 
     * license key
     * 
     * @return boolean
     * 
     * @access public
     */
    public function clearRepository() {
        $response = true;
        if (aam_Core_API::getBlogOption('aam_repository')) {
            $response = aam_Core_API::deleteBlogOption('aam_repository');
        }

        return $response;
    }

    /**
     * Remove the update file
     * 
     * This will stop to run the update again
     * 
     * @return boolean
     * 
     * @access public
     */
    public function removeUpdate() {
        return unlink(__FILE__);
    }

}