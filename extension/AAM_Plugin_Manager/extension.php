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
 * @copyright Copyright C  Vasyl Martyniuk
 * @license GNU General Public License {@link http://www.gnu.org/licenses/}
 */
class AAM_Plugin_Manager extends AAM_Core_Extension {

    /**
     * Unique Feature ID
     * 
     */
    const FEATURE_ID = 'plugin_manager';

    /**
     *
     * @var type 
     */
    private $_subject = null;

    /**
     * Constructor
     *
     * @param aam $parent Main AAM object
     *
     * @return void
     *
     * @access public
     * @see aam It is class in advanced-access-manager/aam.php file
     */
    public function __construct(aam $parent) {
        parent::__construct($parent);

        //include login counter object
        require_once(dirname(__FILE__) . '/plugin.php');

        if (is_admin()) {
            $this->registerFeature();
        }

        //define new AAM Object
        add_filter('aam_object', array($this, 'pluginObject'), 10, 4);

        //control list of plugin actions
        add_filter('all_plugins', array($this, 'all_plugins'));
        add_filter('plugin_action_links', array($this, 'actionLinks'), 10, 4);
        add_filter('network_admin_plugin_action_links', array($this, 'actionLinks'), 10, 4);

        add_filter('user_has_cap', array($this, 'user_has_cap'), 10, 3);
    }

    /**
     * Filter Plugin list
     * 
     * @param array $plugins
     * @return array 
     */
    public function all_plugins($plugins) {
        if (is_array($plugins)) {
            $object = $this->getUser()->getObject(aam_Control_Object_Plugin::UID);
            foreach ($plugins as $plugin => $data) {
                if ($object->has($plugin, 'hide')) {
                    unset($plugins[$plugin]);
                }
            }
        }

        return $plugins;
    }

    /**
     * 
     * @param type $actions
     * @param type $plugin_file
     * @param type $plugin_data
     * @param type $context
     */
    public function actionLinks($actions, $plugin_file, $plugin_data, $context) {
        $object = $this->getUser()->getObject(aam_Control_Object_Plugin::UID);
        if ($object->has($plugin_file, 'activate') && isset($actions['activate'])){
            unset($actions['activate']);
        }
        if ($object->has($plugin_file, 'deactivate') && isset($actions['deactivate'])){
            unset($actions['deactivate']);
        }
        if ($object->has($plugin_file, 'delete') && isset($actions['delete'])){
            unset($actions['delete']);
        }
        
        return $actions;
    }

    /**
     * Check if user has possibility to activate/deactivate a plugin
     * 
     * @param type $all_caps
     * @param type $caps
     * @param type $args
     * @return type 
     */
    public function user_has_cap($all_caps, $caps, $args) {
        switch ($args[0]) {
            case 'activate_plugins':
                $action = $this->getCurrentAction();
                $object = $this->getUser()->getObject(aam_Control_Object_Plugin::UID);
                switch ($action) {
                    case 'activate':
                    case 'deactivate':
                        $plugin = aam_Core_Request::request('plugin');
                        if ($object->has($plugin, $action)) {
                            unset($all_caps[$args[0]]);
                        }
                        break;

                    case 'activate-selected':
                    case 'network-activate-selected':
                        $plugins = aam_Core_Request::request('checked', array());
                        foreach ($plugins as $plugin) {
                            if ($object->has($plugin, 'activate')) {
                                unset($all_caps[$args[0]]);
                                break;
                            }
                        }
                        break;

                    case 'deactivate-selected':
                        $plugins = aam_Core_Request::post('checked', array());
                        foreach ($plugins as $plugin) {
                            if ($object->has($plugin, 'deactivate')) {
                                unset($all_caps[$args[0]]);
                                break;
                            }
                        }
                        break;

                    case 'delete-selected':
                        $plugins = aam_Core_Request::request('checked', array());
                        foreach ($plugins as $plugin) {
                            if ($object->has($plugin, 'delete')) {
                                unset($all_caps[$args[0]]);
                                break;
                            }
                        }
                        break;

                    default:
                        break;
                }
                break;

            default:
                break;
        }

        return $all_caps;
    }

    /**
     *
     * @return type 
     */
    protected function getCurrentAction() {
        $action = FALSE;

        if (aam_Core_Request::request('action')) {
            $action = aam_Core_Request::request('action');
        }
        if (aam_Core_Request::request('action2')) {
            $action = aam_Core_Request::request('action2');
        }

        return $action;
    }

    /**
     * Register feature
     * 
     * @return void
     *
     * @access protected
     */
    protected function registerFeature() {
        //add feature
        $capability = aam_Core_ConfigPress::getParam(
                        'aam.feature.' . self::FEATURE_ID . '.capability', 'administrator'
        );

        //make sure that current user has access to current Extension. This is 
        //mandatory check and should be obeyed by all developers
        if (current_user_can($capability)) {
            //register the Extension's javascript
            add_action('admin_print_scripts', array($this, 'printScripts'));
            //register the Extension's stylesheet
            add_action('admin_print_styles', array($this, 'printStyles'));
            //register the Feature
            aam_View_Collection::registerFeature((object) array(
                        //uid is mandatory and this should be the unique ID
                        'uid' => self::FEATURE_ID,
                        //Extension Position is the list of AAM features. This works
                        //the same way as WordPress Admin Menu
                        'position' => 40,
                        //Extension's Title
                        'title' => __('Plugin Manager', 'aam'),
                        //Define what subjects can see the Extenion's UI.
                        'subjects' => array(
                            aam_Control_Subject_Role::UID,
                            aam_Control_Subject_User::UID
                        ),
                        //Reference to Extension's Controller.
                        'controller' => $this
            ));
        }
    }

    /**
     * Render UI Content
     * 
     * If Extension shows UI, this function is mandatory and should return the HTML
     * string.
     * 
     * @param aam_Control_Subject $subject Current Subject
     * 
     * @return string HTML Template
     * 
     * @access public
     * @see aam_View_Manager::retrieveFeatures
     */
    public function content(aam_Control_Subject $subject) {
        $this->setSubject($subject);
        ob_start();
        require dirname(__FILE__) . '/ui.phtml';
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Register Extension's javascript
     * 
     * This function should check if user is on AAM Page. Otherwise there is no need
     * to load any javascript to the header of HTML page.
     *
     * @return void
     *
     * @access public
     * @see aam::isAAMScreen
     */
    public function printScripts() {
        if ($this->getParent()->isAAMScreen()) {
            wp_enqueue_script(
                    'aam-plugin-manager-admin', AAM_PLUGIN_MANAGER_BASE_URL . '/plugin_manager.js', array('aam-admin') //make sure that main aam javascript is loaded
            );
        }
    }

    /**
     * Register Extenion's stylesheet
     *
     * This function should check if user is on AAM Page. Otherwise there is no need
     * to load any stylesheet to the header of HTML page.
     *
     * @return void
     *
     * @access public
     * @see aam::isAAMScreen
     */
    public function printStyles() {
        if ($this->getParent()->isAAMScreen()) {
            wp_enqueue_style(
                    'aam-plugin-manager-admin', AAM_PLUGIN_MANAGER_BASE_URL . '/stylesheet.css', array('aam-style') //Extension can overwrite the main AAM style
            );
        }
    }

    /**
     * 
     * @param null                $object     Default Object
     * @param int                 $object_uid Current User ID
     * @param string              $object_id  Request Object ID
     * @param aam_Control_Subject $subject    Current Subject
     * 
     * @return aam_Control_Object_LoginCounter
     * 
     * @access public
     * @see aam_Control_Subject::getObject
     */
    public function pluginObject($object, $object_uid, $object_id, $subject) {
        if ($object_uid === aam_Control_Object_Plugin::UID) {
            $object = new aam_Control_Object_Plugin($subject, $object_id);
        }

        return $object;
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
