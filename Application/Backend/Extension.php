<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend extension manager
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Extension {
    
    /**
     * Extension status: installed
     * 
     * Extension has been installed and is up to date
     */
    const STATUS_INSTALLED = 'installed';
    
    /**
     * Extension status: download
     * 
     * Extension is not installed and either needs to be purchased or 
     * downloaded for free.
     */
    const STATUS_DOWNLOAD = 'download';
    
    /**
     * Extension status: update
     * 
     * New version of the extension has been detected.
     */
    const STATUS_UPDATE = 'update';
    
    /**
     * Extension list option
     */
    const EXTENSION_LIST = 'aam-extension-list';
    
    /**
     * Get HTML content
     * 
     * @return string
     * 
     * @access public
     */
    public function getContent() {
        ob_start();
        require_once(dirname(__FILE__) . '/view/extension.phtml');
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
    
    /**
     * Install an extension
     * 
     * @param string $storedLicense
     * 
     * @return string
     * 
     * @access public
     */
    public function install($storedLicense = null) {
        $repo = AAM_Core_Repository::getInstance();
        $license = AAM_Core_Request::post('license', $storedLicense);
        
        //download the extension from the server first
        $package = AAM_Core_Server::download($license);
        
        if (is_wp_error($package)) {
            $response = array(
                'status' => 'failure', 'error'  => $package->get_error_message()
            );
        }elseif ($error = $repo->checkDirectory()) {
            $response = $this->installFailureResponse($error, $package);
            $this->storeLicense($package->title, $license);
        } else { //otherwise install the extension
            $result = $repo->addExtension(base64_decode($package->content));
            if (is_wp_error($result)) {
                $response = $this->installFailureResponse(
                        $result->get_error_message(), $package
                );
            } else {
                $response = array('status' => 'success');
            }
            $this->storeLicense($package->title, $license);
        }
        
        return json_encode($response);
    }
    
    /**
     * Update the extension
     * 
     * @return string
     * 
     * @access public
     */
    public function update() {
        $extension = AAM_Core_Request::post('extension');
        
        $list = AAM_Core_API::getOption('aam-extension-license', array());
        if (isset($list[$extension])) {
            $response = $this->install($list[$extension]);
        } else {
            $response = json_encode(array(
                'status' => 'failure', 
                'error' => __('License key is missing.', AAM_KEY)
            ));
        }
        
        return $response;
    }
    
    /**
     * Install extension failure response
     * 
     * In case the filesystem fails, AAM allows to download the extension for
     * manuall installation
     * 
     * @param string   $error
     * @param stdClass $package
     * 
     * @return array
     * 
     * @access protected
     */
    protected function installFailureResponse($error, $package) {
        return array(
            'status'  => 'failure',
            'error'   => $error,
            'title'   => $package->title,
            'content' => $package->content
        );
    }
    
    /**
     * Store the license key
     * 
     * This is important to have just for the update extension purposes
     * 
     * @param string $title
     * @param string $license
     * 
     * @return void
     * 
     * @access protected
     */
    protected function storeLicense($title, $license) {
        //retrieve the installed list of extensions
        $list = AAM_Core_API::getOption('aam-extension-license', array());
        $list[$title] = $license;
        
        //update the extension list
        AAM_Core_API::updateOption('aam-extension-license', $list);
    }
    
    /**
     * Check extension status
     * 
     * The list of extensions is comming from the external server. This list is
     * updated daily by the registered cron-job.
     * Each extension is following by next naming convension and stardard - the 
     * title of an extension contains only latin letters and spaces and name is 
     * no longer than 50 characters. As a standard, each extension defines the 
     * global contant that indicates an extension version. The name of the 
     * contants derives from the extension title by transforming all letters to 
     * upper case and replacing the white spaces with underscore "_" 
     * (e.g AAM Plus Package defines the contant AAM_PLUS_PACKAGE). 
     * 
     * @param string $title
     * 
     * @return string
     * 
     * @access protected
     */
    protected function extensionStatus($title) {
        static $cache = null;
        
        $status = self::STATUS_INSTALLED;
        $const = str_replace(' ', '_', strtoupper($title));
        
        if (is_null($cache)) {
            $list = AAM_Core_API::getOption('aam-extension-list', array());
            $cache = array();
            foreach($list as $row) {
                $cache[$row->title] = $row;
            }
        }
        
        if (!defined($const)) { //extension does not exist
            $status = self::STATUS_DOWNLOAD;
        } elseif (!empty($cache[$title])) {
            $version = constant($const);
            if ($version != $cache[$title]->version) { //version mismatch?
                $status = self::STATUS_UPDATE;
            }
        }
        
        return $status;
    }

    /**
     * Register Extension feature
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid' => 'extension',
            'position' => 999,
            'title' => __('Extensions', AAM_KEY),
            'subjects' => array(
                'AAM_Core_Subject_Role', 
                'AAM_Core_Subject_User', 
                'AAM_Core_Subject_Visitor'
            ),
            'view' => __CLASS__
        ));
    }

}