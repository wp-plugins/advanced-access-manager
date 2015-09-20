<?php

/**
  Copyright (C) <2013-2014>  Vasyl Martyniuk <support@wpaam.com>

  This program is commercial software: you are not allowed to redistribute it
  and/or modify. Unauthorized copying of this file, via any medium is strictly
  prohibited.
  For any questions or concerns contact Vasyl Martyniuk <support@wpaam.com>
 */

/**
 * AAM Content Filter Extension
 *
 * @package AAM
 * @author Vasyl Martyniuk <support@wpaam.com>
 * @copyright Copyright C 2014 Vasyl Martyniuk
 */
class AAM_Extension_ContentFilter extends AAM_Core_Extension {

    /**
     * Page or Post Content
     * 
     * @var string
     * 
     * @access private 
     */
    private $_content;

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

        if (is_admin()) {
           //for now leave this section empty 
        } else {
            add_filter('the_content', array($this, 'filterContent'));
        }
    }

    /**
     * Filter content
     * 
     * @param string $content
     * 
     * @return string
     */
    public function filterContent($content) {
        $this->setContent($content);

        //check if there is any restricted area specified
        if ($areas = $this->retrieveSections()) {
            foreach ($areas[1] as $i => $template) {
                $this->filterSubpart($areas[0][$i], $template);
            }
        }

        return $this->getContent();
    }

    /**
     * 
     * @param type $html
     * @param type $template
     */
    public function filterSubpart($html, $template) {
        //check if template is specified in ConfigPress
        $config = aam_Core_ConfigPress::getParam("content-filter.{$template}");
        if ($config) {
            switch ($config->subject) {
                case aam_Control_Subject_Visitor::UID:
                    $this->filterVisitor($html, $config);
                    break;

                case aam_Control_Subject_Role::UID:
                    $this->filterRole($html, $config);
                    break;

                case aam_Control_Subject_User::UID:
                    $this->filterUser($html, $config);
                    break;

                default:
                    break;
            }
        }
    }

    /**
     * Filter content for visitor
     * 
     * @param string $html
     * @param Zend_Config $config
     * 
     * @return void
     * 
     * @access protected
     */
    protected function filterVisitor($html, Zend_Config $config) {
        if ($this->getUser()->getUID() == aam_Control_Subject_Visitor::UID) {
            $this->setContent(
                    str_replace($html, $config->replace, $this->getContent())
            );
        }
    }

    /**
     * Filter content for role
     * 
     * @param string $html
     * @param Zend_Config $config
     * 
     * @return void
     * 
     * @access protected
     */
    protected function filterRole($html, Zend_Config $config) {
        if ($this->getUser()->getUID() != aam_Control_Subject_Visitor::UID) {
            foreach ($this->getUser()->roles as $role) {
                if ($role == $config->id) {
                    $this->setContent(
                            str_replace($html, $config->replace, $this->getContent())
                    );
                    break;
                }
            }
        }
    }

    /**
     * Filter content for user
     * 
     * @param string $html
     * @param Zend_Config $config
     * 
     * @return void
     * 
     * @access protected
     */
    protected function filterUser($html, Zend_Config $config) {
        $user = $this->getUser();
        if (($user->getUID() == aam_Control_Subject_User::UID) 
                && ($user->getId() == $config->id)) {
            $this->setContent(
                    str_replace($html, $config->replace, $this->getContent())
            );
        }
    }

    /**
     * 
     * @param type $content
     * 
     * @return array
     */
    public function retrieveSections() {
        $regExp = '/<!\-\-[\s]?filter\|([\w]+)[\s]?begin\-\->';
        $regExp .= '.*<!\-\-[\s]?filter\|[\w]+[\s]?end\-\->/siU';

        if (preg_match_all($regExp, $this->getContent(), $matches)) {
            $response = $matches;
        } else {
            $response = null;
        }

        return $response;
    }

    /**
     * 
     * @param type $content
     */
    public function setContent($content) {
        $this->_content = $content;
    }

    /**
     * 
     * @return type
     */
    public function getContent() {
        return $this->_content;
    }

}
