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
class aam_Core_Extension {

    const STATUS_FAILED = 'failed';
    const STATUS_INSTALLED = 'installed';

    /**
     * Basedir to Extentions repository
     *
     * @var string
     *
     * @access private
     */
    private $_basedir = '';
    private $_cache = array();
    private $_parent = null;
    private $_repository = array();

    /**
     * Consturctor
     *
     * @return void
     *
     * @access public
     */
    public function __construct(aam $parent) {
        $this->setParent($parent);
        $this->_basedir = AAM_BASE_DIR . 'extension';
    }

    /**
     * Load active extensions
     *
     * @return void
     *
     * @access public
     */
    public function load() {

        //iterate through each active extension and load it
        foreach (scandir($this->_basedir) as $module) {
            if (!in_array($module, array('.', '..'))) {
                $this->bootstrapExtension($module);
            }
        }
    }

    public function download() {
        require_once ABSPATH . 'wp-admin/includes/file.php';

        //initialize Filesystem
        WP_Filesystem();

        //check is extension config is specified
        $config_press = $this->_parent->getUser()->getObject(
                aam_Control_Object_ConfigPress::UID
        );
        $extensions = $config_press->getParam(
                'aam.extension'
        );

        if ($extensions instanceof Zend_Config) {
            //get the list of downloaded extensions
            $this->_repository = aam_Core_API::getBlogOption('aam_repository', array());
            foreach ($extensions as $license) {
                if (!isset($this->_repository[$license]) || $this->_repository[$license] == self::STATUS_FAILED) {
                    if ($this->retrieve($license)) {
                        $this->_repository[$license] = self::STATUS_INSTALLED;
                    } else {
                        $this->_repository[$license] = self::STATUS_FAILED;
                    }
                }
            }
            aam_Core_API::updateBlogOption('aam_repository', $this->_repository);
        }
    }

    protected function retrieve($license) {
        global $wp_filesystem;

        $url = WPAAM_REST_API . '?method=extension&license=' . $license;
        $res = wp_remote_request($url, array('timeout' => 10));
        $response = false;

        if (!is_wp_error($res)) {
            //write zip archive to the filesystem first
            $zip = AAM_TEMP_DIR . '/' . uniqid();
            if ($wp_filesystem->put_contents($zip, base64_decode($res['body']))) {
                $response = $this->insert($zip);
                $wp_filesystem->delete($zip);
            }
        }

        return $response;
    }

    protected function insert($zip) {
        $response = true;
        if (is_wp_error(unzip_file($zip, $this->_basedir))) {
            aam_Core_Console::write('Failed to insert extension');
            $response = false;
        }

        return $response;
    }

    /**
     * Bootstrap the Extension
     *
     * In case of any errors, the output can be found in console
     *
     * @param string $extension
     *
     * @return void
     *
     * @access protected
     */
    protected function bootstrapExtension($extension) {
        $bootstrap = $this->_basedir . "/{$extension}/bootstrap.php";
        if (file_exists($bootstrap) && !$this->_cache[$extension]) {
            $this->_cache[$extension] = require_once($bootstrap);
        }
    }

    public function setParent(aam $parent) {
        $this->_parent = $parent;
    }

    /**
     *
     * @return aam
     */
    public function getParent() {
        return $this->_parent;
    }

}
