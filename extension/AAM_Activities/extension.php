<?php

/**
  Copyright (C) <2013-2014>  Vasyl Martyniuk <support@wpaam.com>

  This program is commercial software: you are not allowed to redistribute it
  and/or modify. Unauthorized copying of this file, via any medium is strictly
  prohibited.
  For any questions or concerns contact Vasyl Martyniuk <support@wpaam.com>
 */

/**
 *
 * @package AAM
 * @author Vasyl Martyniuk <support@wpaam.com>
 * @copyright Copyright C 2013 Vasyl Martyniuk
 * @license GNU General Public License {@link http://www.gnu.org/licenses/}
 */
class AAM_Extension_Activities extends AAM_Core_Extension{

    /**
     * Activity for Post/Page update
     */
    const ACTIVITY_POST_UPDATE = 'post_update';

    /**
     * Activity for Post/Page trash
     */
    const ACTIVITY_POST_TRASH = 'trash_post';

    /**
     * Activity for Post/Page untrash
     */
    const ACTIVITY_POST_UNTRASH = 'untrash_post';

    /**
     * Post/Page Delete activity
     */
    const ACTIVITY_POST_DELETE = 'delete_post';


    /**
     *
     * @param aam|aam_View_Connector $parent
     */
    public function __construct(aam $parent) {
        parent::__construct($parent);

        if (is_admin()) {
            add_action('admin_print_scripts', array($this, 'printScripts'));
            add_filter('aam_activity_decorator', array($this, 'decorator'), 10, 2);
        }

        add_action('edit_post', array($this, 'editPost'), 10, 2);
        add_action('trashed_post', array($this, 'trashedPost'), 10);
        add_action('untrashed_post', array($this, 'untrashedPost'), 10);
        add_action('delete_post', array($this, 'deletePost'), 10);
    }

    /**
     *
     * @param type $post_id
     * @param type $post
     */
    public function editPost($post_id, $post) {
        $this->addActivity(array(
            'action' => self::ACTIVITY_POST_UPDATE,
            'post_id' => $post_id
        ));
    }

    /**
     *
     * @param type $post_id
     */
    public function trashedPost($post_id) {
        $this->addActivity(array(
            'action' => self::ACTIVITY_POST_TRASH,
            'post_id' => $post_id
        ));
    }

    /**
     *
     * @param type $post_id
     */
    public function untrashedPost($post_id) {
        $this->addActivity(array(
            'action' => self::ACTIVITY_POST_UNTRASH,
            'post_id' => $post_id
        ));
    }

    /**
     *
     * @param type $post_id
     */
    public function deletePost($post_id) {
        $post = get_post($post_id);

        $this->addActivity(array(
            'action' => self::ACTIVITY_POST_DELETE,
            'post_id' => $post_id,
            'post_type' => $post->post_type,
            'post_title' => $post->post_title
        ));
    }

    /**
     *
     * @param type $activity
     */
    public function addActivity($activity) {
        $object = $this->getParent()->getUser()->getObject(
                aam_Control_Object_Activity::UID
        );
        if ($object instanceof aam_Control_Object_Activity) {
            $object->add(time(), $activity);
        }
    }

    /**
     *
     * @param type $default
     * @param type $activity
     * @return type
     */
    public function decorator($default, $activity) {
        switch ($activity['action']) {
            case self::ACTIVITY_POST_UPDATE:
                $post_details = $this->getPostDetails($activity['post_id']);
                if (is_null($post_details)) {
                    $response = __('Post does not exist.', 'aam');
                } else {
                    $response = sprintf(
                            __('Updated %s: %s', 'aam'),
                            $post_details->type,
                            $post_details->title
                    );
                }
                break;

            case self::ACTIVITY_POST_TRASH:
                $post_details = $this->getPostDetails($activity['post_id']);
                if (is_null($post_details)) {
                    $response = __('Post does not exist.', 'aam');
                } else {
                    $response = sprintf(
                            __('Trashed %s: %s', 'aam'),
                            $post_details->type,
                            $post_details->title
                    );
                }
                break;


            case self::ACTIVITY_POST_UNTRASH:
                $post_details = $this->getPostDetails($activity['post_id']);
                if (is_null($post_details)) {
                    $response = __('Post does not exist.', 'aam');
                } else {
                    $response = sprintf(
                            __('Untrashed %s: %s', 'aam'),
                            $post_details->type,
                            $post_details->title
                    );
                }
                break;

            case self::ACTIVITY_POST_DELETE:
                $response = sprintf(
                        __('Deleted %s %s', 'aam'),
                        $this->getPostTypeLabel($activity['post_type']),
                        $activity['post_title']
                );
                break;



            default:
                $response = $default;
                break;
        }

        return $response;
    }

    /**
     * Get Post Details based on post id
     *
     * @param type $post_id
     * @return type
     */
    public function getPostDetails($post_id) {

        $response = null;

        $post = get_post($post_id);
        if ($post instanceof WP_Post) {
            $type = $this->getPostTypeLabel($post->post_type);
            $title = '<a href="' . get_edit_post_link($post->ID) . '" ';
            $title .= 'target="_blank">' . esc_js($post->post_title) . '</a>';

            $response = (object) array(
                        'type' => $type, 'title' => $title, 'post' => $post
            );
        }

        return $response;
    }

    /**
     *
     * @global type $wp_post_types
     * @param type $post_type
     * @return type
     */
    public function getPostTypeLabel($post_type) {
        global $wp_post_types;

        if (isset($wp_post_types[$post_type])) {
            $labels = $wp_post_types[$post_type]->labels;
            if (empty($labels->singular_name)) {
                $type = ucfirst($post_type);
            } else {
                $type = $labels->singular_name;
            }
        } else {
            $type = __('Undefined Type', 'aam');
        }

        return $type;
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
                    'aam-activities-admin',
                    AAM_ACTIVITIES_BASE_URL . '/activity.js',
                    array('aam-admin')
            );
        }
    }

}