<?php

/*
  Copyright (C) <2011>  Vasyl Martyniuk <martyniuk.vasyl@gmail.com>

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */

/**
 * Main Access Control Model
 * 
 * @package AAM
 * @subpackage Models
 * @author Vasyl Martyniuk <martyniuk.vasyl@gmail.com>
 * @copyrights Copyright © 2011 Vasyl Martyniuk
 * @license GNU General Public License {@link http://www.gnu.org/licenses/}
 */
class mvb_Model_AccessControl {

	/**
	 * User Config
	 * 
	 * @access protected
	 * @var mvb_Model_UserConfig 
	 */
	protected $user_config;

	/**
	 * Menu Filter
	 * 
	 * @access protected
	 * @var mvb_Model_FilterMenu 
	 */
	protected $menu_filter = NULL;
	
	/**
	 * Class that called current
	 * 
	 * @access protected
	 * @var object
	 */
	protected $caller;

	/**
	 * Init Object
	 * 
	 * @param int $user_id 
	 */
	public function __construct($caller, $user_id = FALSE) {

		$this->caller = $caller;
		$user_id = ($user_id ? $user_id : get_current_user_id());

		$this->user_config = mvb_Model_API::getUserAccessConfig($user_id);
		$this->user_config->initRestrictionTree();

		$this->menu_filter = new mvb_Model_FilterMenu($this);
	}

	/**
	 * Main function for checking if user has access to a page
	 * 
	 * Check if current user has access to requested page. If no, print an
	 * notification
	 * 
	 * @access public
	 * @global object $wp_query
	 * @global object $post
	 * @return bool
	 */
	public function checkAccess() {
		global $wp_query, $post;

		//skip Super Admin Role
		if (mvb_Model_API::isSuperAdmin()) {
			return TRUE;
		}

		if (is_admin()) {
			//check if user has access to requested Menu
			$uri = $_SERVER['REQUEST_URI'];
			if (!$this->getMenuFilter()->checkAccess($uri)) {
				mvb_Model_ConfigPress::doRedirect();
			}

			//check if current user has access to requested Post
			$post_id = mvb_Model_Helper::getCurrentPostID();
			if ($post_id) {
				$post = get_post($post_id);
				if (!$this->checkPostAccess($post)) {
					mvb_Model_ConfigPress::doRedirect();
				}
			} elseif (isset($_GET['taxonomy']) && isset($_GET['tag_ID'])) { // TODO - Find better way
				if (!$this->checkCategoryAccess($_GET['tag_ID'])) {
					mvb_Model_ConfigPress::doRedirect();
				}
			}
		} else {
			if (is_category()) {
				$cat_obj = $wp_query->get_queried_object();
				if (!$this->checkCategoryAccess($cat_obj->term_id)) {
					mvb_Model_ConfigPress::doRedirect();
				}
			} else {
				if (!$wp_query->is_home() && $post) {
					if (!$this->checkPostAccess($post)) {
						mvb_Model_ConfigPress::doRedirect();
					}
				}
			}
		}
	}

	/**
	 * Get User Config
	 * 
	 * @access public
	 * @return mvb_Model_UserConfig 
	 */
	public function getUserConfig() {

		return $this->user_config;
	}

	/**
	 * Get Menu Filter
	 * 
	 * @access public
	 * @return mvb_Model_FilterMenu
	 */
	public function getMenuFilter() {

		return $this->menu_filter;
	}

	/**
	 * Check Category Restriction
	 * 
	 * @param int $id
	 * @return boolean TRUE if restricted
	 */
	public function checkCategoryAccess($id) {

		$access = TRUE;
		if ($data = $this->getUserConfig()->getRestriction('taxonomy', $id)) {
			if (is_admin() && $data['restrict']) {
				$access = FALSE;
			} elseif (!is_admin() && $data['restrict_front']) {
				$access = FALSE;
			}
		}

		return $access;
	}

	/**
	 * Check if user has access to current post
	 * 
	 * @param object $post
	 * @return boolean
	 */
	public function checkPostAccess($post) {

		$access = TRUE;
		//get post's categories
		$taxonomies = get_object_taxonomies($post);
		$cat_list = wp_get_object_terms($post->ID, $taxonomies, array('fields' => 'ids'));

		if (is_array($cat_list)) {
			foreach ($cat_list as $cat_id) {
				if (!$this->checkCategoryAccess($cat_id)) {
					$access = FALSE;
					break;
				}
			}
		}

		if ($data = $this->getUserConfig()->getRestriction('post', $post->ID)) {
			if (is_admin() && $data['restrict']) {
				$access = FALSE;
			} elseif (!is_admin() && $data['restrict_front']) {
				$access = FALSE;
			}
		}

		return $access;
	}

	/**
	 * Check if page is excluded from the menu
	 * 
	 * @param type $page
	 * @return boolean 
	 * @todo Delete This is not necessary
	 */
	public function checkPageExcluded($page) {

		return ($this->getUserConfig()->hasExclude($page->ID) ? TRUE : FALSE);
	}

}

?>