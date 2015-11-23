<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Abstract subject
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
abstract class AAM_Core_Subject {

    /**
     * Subject ID
     *
     * Whether it is User ID or Role ID
     *
     * @var string|int
     *
     * @access private
     */
    private $_id;

    /**
     * WordPres Subject
     *
     * It can be WP_User or WP_Role, based on what class has been used
     *
     * @var WP_Role|WP_User
     *
     * @access private
     */
    private $_subject;

    /**
     * List of Objects to be access controled for current subject
     *
     * All access control objects like Admin Menu, Metaboxes, Posts etc
     *
     * @var array
     *
     * @access private
     */
    private $_objects = array();

    /**
     * Constructor
     *
     * @param string|int $id
     *
     * @return void
     *
     * @access public
     */
    public function __construct($id = '') {
        //set subject
        $this->setId($id);
        //retrieve and set subject itself
        $this->setSubject($this->retrieveSubject());
    }

    /**
     * Trigger Subject native methods
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     *
     * @access public
     */
    public function __call($name, $args) {
        $subject = $this->getSubject();
        
        //make sure that method is callable
        if (method_exists($subject, $name)) {
            $response = call_user_func_array(array($subject, $name), $args);
        } else {
            $response = null;
        }

        return $response;
    }

    /**
     * Get Subject's native properties
     *
     * @param string $name
     *
     * @return mixed
     *
     * @access public
     */
    public function __get($name) {
        $subject = $this->getSubject();
        
        return (!empty($subject->$name) ? $subject->$name : null);
    }

    /**
     * Set Subject's native properties
     *
     * @param string $name
     *
     * @return mixed
     *
     * @access public
     */
    public function __set($name, $value) {
        $subject = $this->getSubject();
        
        if ($subject) {
            $subject->$name = $value;
        }
    }

    /**
     * Set Subject ID
     *
     * @param string|int
     *
     * @return void
     *
     * @access public
     */
    public function setId($id) {
        $this->_id = $id;
    }

    /**
     * Get Subject ID
     *
     * @return string|int
     *
     * @access public
     */
    public function getId() {
        return $this->_id;
    }

    /**
     * Get Subject
     *
     * @return WP_Role|WP_User
     *
     * @access public
     */
    public function getSubject() {
        return $this->_subject;
    }

    /**
     * Set Subject
     *
     * @param WP_Role|WP_User $subject
     *
     * @return void
     *
     * @access public
     */
    public function setSubject($subject) {
        $this->_subject = $subject;
    }

    /**
     * Get Individual Object
     *
     * @param string $objectType
     * @param mixed  $id
     *
     * @return AAM_Core_Object
     *
     * @access public
     */
    public function getObject($objectType, $id = 'none') {
        $object = null;
        
        //make sure that object group is defined
        if (!isset($this->_objects[$objectType])){
            $this->_objects[$objectType] = array();
        }
        
        //check if there is an object with specified ID
        if (!isset($this->_objects[$objectType][$id])) {
            $classname = 'AAM_Core_Object_' . ucfirst($objectType);
            if (class_exists($classname)) {
                $object = new $classname($this, $id);
            } else {
                $object = apply_filters(
                        'aam-object-filter', null, $objectType, $id, $this
                );
            }
            
            if ($object instanceof AAM_Core_Object) {
                $this->_objects[$objectType][$id] = $object;
            }
        } else {
            $object = $this->_objects[$objectType][$id];
        }

        return $object;
    }

    /**
     *
     * @param type $capability
     * @return type
     */
    public function hasCapability($capability) {
        return $this->getSubject()->has_cap($capability);
    }
    
    /**
     * 
     * @param type $param
     * @param type $value
     * @param type $object
     * @param type $objectId
     * @return type
     */
    public function save($param, $value, $object, $objectId = 0) {
        return $this->getObject($object, $objectId)->save($param, $value);
    }

    /**
     * Retrieve list of subject's capabilities
     *
     * @return array
     *
     * @access public
     */
    abstract public function getCapabilities();

    /**
     * Retrieve subject based on used class
     *
     * @return void
     *
     * @access protected
     */
    abstract protected function retrieveSubject();
    
    /**
     * Read object from parent subject
     * 
     * @param string $object
     * @param mixed  $id
     * 
     * @return mixed
     * 
     * @access public
     */
    public function inheritFromParent($object, $id = ''){
        if ($subject = $this->getParentSubject()){
            $option = $subject->getObject($object, $id)->getOption();
        } else {
            $option = null;
        }
        
        return $option;
    }
    
    /**
     * Retrive parent subject
     * 
     * If there is no parent subject, return null
     * 
     * @return AAM_Core_Subject|null
     * 
     * @access public
     */
    abstract public function getParentSubject();
    
}