<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Extension Repository
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Repository {

    /**
     * Relative path to extension directory
     */
    const RELPATH = '/aam/extension';

    /**
     * Single instance of itself
     * 
     * @var AAM_Core_Repository
     * 
     * @access private
     * @static 
     */
    private static $_instance = null;

    /**
     * Consturctor
     *
     * @return void
     *
     * @access protected
     */
    protected function __construct() {}

    /**
     * Get single instance of itself
     * 
     * @param AAM $parent
     * 
     * @return AAM_Core_Repository
     * 
     * @access public
     * @static
     */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * Load active extensions
     *
     * @return void
     *
     * @access public
     */
    public function load() {
        $basedir = WP_CONTENT_DIR . self::RELPATH;

        if (file_exists($basedir)) {
            //iterate through each active extension and load it
            foreach (scandir($basedir) as $module) {
                if (!in_array($module, array('.', '..'))) {
                    $this->bootstrapExtension($basedir . '/' . $module);
                }
            }
        }
    }

    /**
     * Bootstrap the Extension
     *
     * In case of any errors, the output can be found in console
     *
     * @param string $path
     *
     * @return void
     * @access protected
     */
    protected function bootstrapExtension($path) {
        $bootstrap = "{$path}/bootstrap.php";

        if (file_exists($bootstrap)) { //bootstrap the extension
            require($bootstrap);
        }
    }

    /**
     * Add new extension
     * 
     * @param blob $content
     * 
     * @return boolean|WP_Error
     * @access public
     * @global type $wp_filesystem
     */
    public function addExtension($content) {
        $filepath  = WP_CONTENT_DIR . self::RELPATH . '/' . uniqid('aam_');
        
        $response = file_put_contents($filepath, $content);
        if (!is_wp_error($response)) { //unzip the archive
            WP_Filesystem(false, false, true); //init filesystem
            $response = unzip_file($filepath, WP_CONTENT_DIR . self::RELPATH);
            if (!is_wp_error($response)) {
                $response = true;
            }
            @unlink($filepath); //remove the working archive
        }

        return $response;
    }

    /**
     * Check extension directory
     * 
     * @return boolean|sstring
     * 
     * @access public
     * 
     * @global type $wp_filesystem
     */
    public function checkDirectory() {
        $error = false;

        //create a directory if does not exist
        $basedir = WP_CONTENT_DIR . self::RELPATH;
        if (!file_exists($basedir)) {
            if (!@mkdir($basedir, fileperms(ABSPATH) & 0777 | 0755, true)) {
                $error = sprintf(__('Failed to create %s', AAM_KEY), $basedir);
            }
        } elseif (!is_writable($basedir)) {
            $error = sprintf(
                    __('Directory %s is not writable', AAM_KEY), $basedir
            );
        }

        return $error;
    }

}