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
class aam_Control_Object_ConfigPress extends aam_Control_Object {

    const UID = 'configpress';

    private $_option = '';
    private $_config = '';
    private $_tree = null;

    public function save($config_press = null) {
        if (is_writable(AAM_TEMP_DIR)) {
            $filename = $this->getOption();
            if (!$filename) { //file already was created
                $filename = sha1(uniqid('aam'));
                aam_Core_API::updateBlogOption('aam_' . self::UID, $filename);
            }
            $response = file_put_contents(
                    AAM_TEMP_DIR . $filename, stripcslashes($config_press)
            );
        } else {
            $response = false;
            aam_Core_Console::write('Temp directory is not writable');
        }

        return $response;
    }

    public function getUID(){
        return self::UID;
    }

    public function init($object_id) {
        if (empty($this->_option)) {
            $filename = aam_Core_API::getBlogOption('aam_' . self::UID, '');
            if ($filename && file_exists(AAM_TEMP_DIR . $filename)) {
                $this->setOption($filename);
                $this->setConfig(file_get_contents(AAM_TEMP_DIR . $filename));
                $this->parseConfig(AAM_TEMP_DIR . $filename);
            }
        }
    }

    protected function parseConfig($filename) {
        //include third party library
        require_once(AAM_LIBRARY_DIR . 'Zend/Exception.php');
        require_once(AAM_LIBRARY_DIR . 'Zend/Config/Exception.php');
        require_once(AAM_LIBRARY_DIR . 'Zend/Config.php');
        require_once(AAM_LIBRARY_DIR . 'Zend/Config/Ini.php');
        //parse ini file
        try {
            $this->setTree(new Zend_Config_Ini($filename));
        } catch (Zend_Config_Exception $e) {
            aam_Core_Console::write($e->getMessage());
        }
    }

    protected function parseParam($param, $default) {
        if (is_object($param) && isset($param->userFunc)) {
            $func = trim($param->userFunc);
            if (is_string($func) && is_callable($func)) {
                $response = call_user_func($func);
            } else {
                aam_Core_Console::write("ConfigPress userFunc {$func} failure");
                $response = $default;
            }
        } else {
            $response = $param;
        }

        return $response;
    }

    public function getParam($param, $default = NULL) {
        $tree = $this->getTree();
        foreach (explode('.', $param) as $section) {
            if (isset($tree->{$section})) {
                $tree = $tree->{$section};
            } else {
                $tree = $default;
                break;
            }
        }

        return $this->parseParam($tree, $default);
    }

    public function setOption($option) {
        $this->_option = $option;
    }

    public function getOption() {
        return $this->_option;
    }

    public function setConfig($config) {
        $this->_config = $config;
    }

    public function getConfig() {
        return $this->_config;
    }

    public function setTree($tree) {
        $this->_tree = $tree;
    }

    public function getTree() {
        return $this->_tree;
    }

}
