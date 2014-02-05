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
class AAM_Extension_ActivityLog {

    /**
     *
     * @var type
     */
    private $_parent = null;

    /**
     *
     * @var type
     */
    private $_subject = null;

    /**
     *
     * @param aam|aam_View_Connector $parent
     */
    public function __construct(aam $parent) {
        $this->setParent($parent);

        //include activity object
        require_once(dirname(__FILE__) . '/activity.php');

        if (is_admin()) {
            add_action('admin_print_scripts', array($this, 'printScripts'));
            add_action('admin_print_styles', array($this, 'printStyles'));
            add_filter('aam_ui_features', array($this, 'feature'), 10);
            add_filter('aam_ajax_call', array($this, 'ajax'), 10, 2);
            add_action('aam_localization_labels', array($this, 'localizationLabels'));
        }

        //define new Activity Object
        add_filter('aam_object', array($this, 'activityObject'), 10, 3);

        //login & logout hooks
        add_action('wp_login', array($this, 'login'), 10, 2);
        add_action('wp_logout', array($this, 'logout'));
    }

    /**
     *
     * @param type $username
     * @param type $user
     */
    public function login($username, $user) {
        $subject = new aam_Control_Subject_User($user->ID);
        $subject->getObject(aam_Control_Object_Activity::UID)->add(
                time(),
                array(
                    'action' => aam_Control_Object_Activity::ACTIVITY_LOGIN
                )
        );
    }

    /**
     *
     */
    public function logout() {
        $user = $this->getParent()->getUser();
        $user->getObject(aam_Control_Object_Activity::UID)->add(
                time(),
                array(
                    'action' => aam_Control_Object_Activity::ACTIVITY_LOGOUT
                )
        );
    }

    /**
     *
     * @param aam_Control_Object_Activity $object
     * @param type $object_uid
     * @param type $object_id
     * @return \aam_Control_Object_Activity
     */
    public function activityObject($object, $object_uid, $object_id) {
        if ($object_uid === aam_Control_Object_Activity::UID) {
            $object = new aam_Control_Object_Activity(
                    $this->getParent()->getUser(), $object_id
            );
        }

        return $object;
    }

    /**
     *
     * @param type $features
     * @return string
     */
    public function feature($features) {
        //add feature
        $features['activity_log'] = array(
            'id' => 'activity_log',
            'position' => 35,
            'title' => __('Activity Log', 'aam'),
            'anonimus' => false,
            'content' => array($this, 'content'),
            'help' => __(
                    'Tracks User Activities like user login/logout or post changes. '
                    . 'Check <b>AAM Activities</b> Extension to get advanced list of possible '
                    . 'activities.', 'aam'
            )
        );

        return $features;
    }

    /**
     *
     * @return type
     */
    public function content() {
        ob_start();
        require dirname(__FILE__) . '/ui.phtml';
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
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
                    'aam-activity-log-admin',
                    AAM_ACTIVITY_LOG_BASE_URL . '/activity.js',
                    array('aam-admin')
            );
        }
    }

    /**
     *
     */
    public function printStyles() {
        if ($this->getParent()->isAAMScreen()) {
            wp_enqueue_style(
                    'aam-activity-log-admin',
                    AAM_ACTIVITY_LOG_BASE_URL . '/activity.css'
            );
        }
    }

    /**
     * Add extra UI labels
     *
     * @param array $labels
     *
     * @return array
     *
     * @access public
     */
    public function localizationLabels($labels) {
        $labels['Clear Logs'] = __('Clear Logs', 'aam');
        $labels['Get More'] = __('Get More', 'aam');

        return $labels;
    }

    /**
     * Hanlde Ajax call
     *
     * @param mixed $default
     * @param aam_Control_Subject $subject
     *
     * @return mixed
     *
     * @access public
     */
    public function ajax($default, aam_Control_Subject $subject = null) {
        $this->setSubject($subject);

        switch (aam_Core_Request::request('sub_action')) {
            case 'activity_list':
                $response = $this->getActivityList();
                break;

            case 'clear_activities':
                $response = $this->clearActivities();
                break;

            default:
                $response = $default;
                break;
        }

        return $response;
    }

    /**
     *
     * @return type
     */
    protected function getActivityList() {
        $response = array(
            'iTotalRecords' => 0,
            'iTotalDisplayRecords' => 0,
            'sEcho' => aam_Core_Request::request('sEcho'),
            'aaData' => array(),
        );

        $activity = $this->getSubject()->getObject(aam_Control_Object_Activity::UID);
        $activities = $activity->getOption();

        foreach ($activities as $user_id => $list) {
            $user = new WP_User($user_id);
            if ($user->ID && is_array($list)) {
                foreach ($list as $time => $data) {
                    $response['aaData'][] = array(
                        $user->ID,
                        ($user->display_name ? $user->display_name : $user->user_nicename),
                        $activity->decorate($data),
                        date('Y-m-d H:i:s', $time)
                    );
                }
            }
        }

        return json_encode($response);
    }

    /**
     * Clear the activities
     *
     * @global wpdb $wpdb
     *
     * @return string
     *
     * @access public
     */
    protected function clearActivities() {
        $activity = $this->getSubject()->getObject(aam_Control_Object_Activity::UID);
        foreach ($activity->getOption() as $user_id => $list) {
            delete_user_option($user_id, 'aam_activity');
        }

        return json_encode(array('status' => 'success'));
    }

    /**
     *
     * @param aam $parent
     */
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

    /**
     *
     * @param type $subject
     */
    public function setSubject($subject) {
        $this->_subject = $subject;
    }

    /**
     *
     * @return type
     */
    public function getSubject() {
        return $this->_subject;
    }

}