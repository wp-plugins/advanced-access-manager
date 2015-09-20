<?php

/**
  Copyright (C)  Vasyl Martyniuk <support@wpaam.com>

  This program is commercial software: you are not allowed to redistribute it
  and/or modify. Unauthorized copying of this file, via any medium is strictly
  prohibited.
  For any questions or concerns contact Vasyl Martyniuk <support@wpaam.com>
 */

/**
 * AAM Media Manager Extension
 *
 * @package AAM
 * @author Vasyl Martyniuk <support@wpaam.com>
 * @copyright Copyright C 2014 Vasyl Martyniuk
 */
class AAM_Extension_MediaManager extends AAM_Core_Extension {

    /**
     * Skip the part of functionality
     * 
     * To avoid infinity loop, skip some part of functionality because some 
     * callbackes in filters loop each other
     * 
     * @var boolean
     * 
     * @access private
     * @todo Find better solution 
     */
    private $_skip = false;

    /**
     * Constructor
     *
     * @param aam $parent
     *
     * @return void
     *
     * @access public
     */
    public function __construct(aam $parent) {
        parent::__construct($parent);

        if (aam_Core_ConfigPress::getParam('aam.media_category', 'true') == 'true') {
            register_taxonomy('media_category', 'attachment', array(
                'hierarchical' => TRUE,
                'rewrite' => TRUE,
                'public' => TRUE,
                'show_ui' => TRUE,
                'show_in_nav_menus' => TRUE,
            ));
        }
        
        if (is_admin()){
            add_action('admin_print_scripts', array($this, 'printScripts'));
        }

        //control attachment link URL
        add_filter('wp_get_attachment_url', array($this, 'attachmentURL'), 999, 2);
        add_filter(
                'wp_get_attachment_thumb_url', array($this, 'attachmentURL'), 999, 2
        );
        //control custom image size URL
        add_filter('image_downsize', array($this, 'imageDownsize'), 10, 3);

        //modify the list of Restrictions
        add_filter('aam_term_access_list', array($this, 'termAccessList'), 0, 4);
        add_filter('aam_post_access_list', array($this, 'postAccessList'), 0, 4);

        //AAM load with "init" action and it also loads all active extensions. That
        //is why it should check media access int contructor
        $this->checkMedia();
    }
    
    /**
     * Print necessary scripts
     *
     * @return void
     *
     * @access public
     */
    public function printScripts() {
        if ($this->getParent()->isAAMScreen()) {
            wp_enqueue_script(
                    'aam-media-manager', 
                    AAM_MEDIA_BASE_URL . '/media.js', 
                    array('aam-admin')
            );
        }
    }

    /**
     * Filter list of Restrictions
     * 
     * @param array              $list
     * @param string             $area
     * @param aam_Control_Object $object
     * @param string             $post_type
     * 
     * @return array
     * 
     * @access public
     */
    public function termAccessList($list, $area, $object, $post_type) {
        if ($post_type == 'attachment') {
            //remove exclude
            for ($i = 0; $i < count($list); $i++) {
                if ($list[$i] == aam_Control_Object_Term::ACTION_EXCLUDE) {
                    unset($list[$i]);
                    break;
                }
            }
        }

        return $list;
    }

    /**
     * Filter Post Restrictions
     * 
     * @param array              $list
     * @param string             $area
     * @param aam_Control_Object $object
     * @param string             $post_type
     * 
     * @return array
     * 
     * @access public
     */
    public function postAccessList($list, $area, $object, $post_type) {
        if ($post_type == 'attachment') {
            //remove exclude
            for ($i = 0; $i < count($list); $i++) {
                if ($list[$i] == aam_Control_Object_Term::ACTION_EXCLUDE) {
                    unset($list[$i]);
                    break;
                }
            }
        }

        return $list;
    }

    /**
     * Control custom image size URL
     * 
     * Image Downsize generate the settings for all images. That is why this function
     * controls that process
     * 
     * @param string       $url
     * @param int          $pid
     * @param array|string $size
     * 
     * @return boolean|array
     * 
     * @access public
     */
    public function imageDownsize($url, $pid, $size) {
        if ($this->_skip === false) {
            $this->_skip = true;
            $attr = image_downsize($pid, $size);
            $size = (is_array($size) ? implode('_', $size) : (string) $size);
            $attr[0] = get_site_url() . "/index.php?aam_media={$pid}&size={$size}";
            $this->_skip = false;
        } else {
            $attr = false;
        }

        return $attr;
    }

    /**
     * Get Attachment URL
     * 
     * @param string $url
     * @param int    $pid
     * 
     * @return string
     * 
     * @access public
     */
    public function attachmentURL($url, $pid) {
        return ($this->_skip ? $url : get_site_url() . "/index.php?aam_media={$pid}");
    }

    /**
     * Check if current user has acces to media
     * 
     * If there is _GET parameter aam_media, it means that AAM should check if current
     * image is accessible.
     * 
     * @return void
     * 
     * @access public
     */
    public function checkMedia() {
        if ($attachment_id = intval(aam_Core_Request::request('aam_media'))) {
            $this->checkAccess($attachment_id);
            die(); //kill the load
        }
    }

    /**
     * Check Access
     * 
     * Make sure that media file is accessible
     * 
     * @param int $id
     * 
     * @return void
     * 
     * @access public
     */
    public function checkAccess($id) {
        $this->_skip = true;
        $media = get_post($id);
        if ($media && !is_wp_error($media) && ($media->post_type == 'attachment')) {
            $size = $this->getSize();
            if ($this->allowed($media)) {
                $this->printAttachment($media, $size);
            } else {
                if ($size){
                    $this->printAttachment(
                        aam_Core_ConfigPress::getParam('media.deny.image'), $size
                    );
                } else {
                    $this->getParent()->reject();
                }
            }
        } else {
            $this->printAttachment(
                    aam_Core_ConfigPress::getParam('media.default.image'), $size
            );
        }
    }

    /**
     * Get Image size
     * 
     * @return string|array
     * 
     * @access public
     */
    public function getSize() {
        $size = explode('_', aam_Core_Request::request('size'));
        if (count($size) == 1) {
            $size = $size[0];
        } elseif (count($size) != 2) {
            $size = 'thumbnail';
        }

        return $size;
    }

    /**
     * 
     * @param type $media
     * @param type $size
     */
    public function printAttachment($media, $size) {
        if (!($media instanceof WP_Post)){
            $media = get_post($media);
        }
        if ($media && !is_wp_error($media) && ($media->post_type == 'attachment')) {
            header('Content-Type: ' . $media->post_mime_type);
            //get media url
            $image = wp_get_attachment_image_src($media->ID, $size);
            if (empty($image)){ //in case of non-image
                $image = array($media->guid);
            }
            //replace the site url with base dir
            $filename = str_replace(WP_CONTENT_URL, WP_CONTENT_DIR, $image[0]);
            // if (file_exists($filename)) {
            echo file_get_contents($filename);
        } 
    }

    /**
     * 
     * @param type $attachment
     * @return type
     */
    public function allowed($attachment) {
        $object = $this->getParent()
                       ->getUser()
                       ->getObject(
                            aam_Control_Object_Post::UID, 
                            $attachment->ID
                  );
        $area = (is_admin() ? 'backend' : 'frontend');

        return ($object->has($area, aam_Control_Object_Post::ACTION_READ) ? false : true);
    }

}