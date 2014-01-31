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
abstract class aam_View_Abstract {

    /**
     *
     * @var type
     */
    static private $_subject = null;
    
    /**
     * List of all Warnings
     * 
     * @var array
     * 
     * @access private 
     */
    private $_warnings = array();

    /**
     *
     */
    public function __construct() {
        if (is_null(self::$_subject)) {
            $subject_class = 'aam_Control_Subject_' . ucfirst(
                trim(aam_Core_Request::request('subject'), '')
            );
            if (class_exists($subject_class)){
                $this->setSubject(new $subject_class(
                    aam_Core_Request::request('subject_id')
                ));
            }
        }
    }

    /**
     *
     * @return type
     */
    public function getSubject() {
        return self::$_subject;
    }

    /**
     *
     * @param aam_Control_Subject $subject
     */
    public function setSubject(aam_Control_Subject $subject) {
        self::$_subject = $subject;
    }

    /**
     *
     * @param type $tmpl_path
     * @return type
     */
    public function loadTemplate($tmpl_path) {
        ob_start();
        require_once($tmpl_path);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
    
    /**
     * Check if there is any AAM Warning
     * 
     * @return boolean
     * 
     * @access public
     */
    public function checkWarnings(){
        //check if wp-content/aam folder exists & is writable
        if (!file_exists(AAM_TEMP_DIR)){
            $this->_warnings[] = __('Folder wp-content/aam does not exist');
        } elseif(!is_writable(AAM_TEMP_DIR)){
            $this->_warnings[] = __('Folder wp-content/aam is not writable');
        }
        
        //check if plugins/advanced-access-manager/extension is writable
        if (!is_writable(AAM_BASE_DIR . 'extension')){
            $this->_warnings[] = __(
                    'Folder advanced-access-manager/extension is not writable'
            );
        }
        
        return (count($this->_warnings) ? true : false);
    }
    
    /**
     * Return warnings
     * 
     * @return array
     * 
     * @access public
     */
    public function getWarnings(){
        return $this->_warnings;
    }

}