<?php

/**
  Copyright (C) <2013-2014>  Vasyl Martyniuk <support@wpaam.com>

  This program is commercial software: you are not allowed to redistribute it
  and/or modify. Unauthorized copying of this file, via any medium is strictly
  prohibited.
  For any questions or concerns contact Vasyl Martyniuk <support@wpaam.com>
 */

/**
 * AAM Core Extension
 *
 * @package AAM
 * @author Vasyl Martyniuk <support@wpaam.com>
 * @copyright Copyright C 2014 Vasyl Martyniuk
 */
class AAM_Core_Extension {

    /**
     * Parent AAM object
     * 
     * @var aam
     * 
     * @access public 
     */
    private $_parent = null;
   
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
        $this->setParent($parent);
    }

    /**
     * Set Parent Object
     * 
     * This is reference to main AAM class
     * 
     * @param aam $parent
     * 
     * @return void
     * 
     * @access public
     */
    public function setParent(aam $parent) {
        $this->_parent = $parent;
    }

    /**
     * Get Parent Object
     * 
     * @return aam
     * 
     * @access public
     */
    public function getParent() {
        return $this->_parent;
    }

    /**
     * Get current User
     * 
     * @return aam_Control_Subject_User
     * 
     * @access public
     */
    public function getUser() {
        return $this->getParent()->getUser();
    }

}