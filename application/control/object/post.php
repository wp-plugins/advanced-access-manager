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
class aam_Control_Object_Post extends aam_Control_Object {

    const UID = 'post';
    const ACTION_COMMENT = 'comment';
    const ACTION_READ = 'read';
    const ACTION_TRASH = 'trash';
    const ACTION_DELETE = 'delete';
    const ACTION_EDIT = 'edit';

    private $_post;
    private $_option = array();

    public function save($params = null) {
        if (is_array($params)) {
            update_post_meta($this->getPost()->ID, $this->getOptionName(), $params);
        }
    }

    public function getAccessList($area) {
        if ($area == 'frontend') {
            $response = array(self::ACTION_READ, self::ACTION_COMMENT);
        } elseif ($area == 'backend') {
            $response = array(
                self::ACTION_TRASH, self::ACTION_DELETE, self::ACTION_EDIT
            );
        } else {
            $response = array();
        }

        return apply_filters('aam_post_access_list', $response, $area);
    }

    public function getUID() {
        return self::UID;
    }

    protected function getOptionName() {
        $subject = $this->getSubject();
        //prepare option name
        $meta_key = 'aam_' . self::UID . '_access_' . $subject::UID;
        $meta_key .= ($subject->getId() ? $subject->getId() : '');

        return $meta_key;
    }

    public function init($object_id) {
        if ($object_id && empty($this->_option)) {
            $this->setPost(get_post($object_id));
            $this->read();
        }
    }

    public function read() {
        $option = get_post_meta($this->getPost()->ID, $this->getOptionName(), true);
        //try to inherit it from parent category
        if (empty($option)) {
            $terms = $this->retrievePostTerms();
            //use only first term for inheritance
            $term_id = array_shift($terms);
            //try to get any parent access
            $option = $this->inheritAccess($term_id);
        }

        $this->setOption(
                apply_filters('aam_post_access_option', $option, $this->getSubject())
        );
    }

    public function delete() {
        return delete_post_meta($this->getPost()->ID, $this->getOptionName());
    }

    private function retrievePostTerms() {
        $taxonomies = get_object_taxonomies($this->getPost());
        if (is_array($taxonomies) && count($taxonomies)) {
            //filter taxonomies to hierarchical only
            $filtered = array();
            foreach ($taxonomies as $taxonomy) {
                if (is_taxonomy_hierarchical($taxonomy)) {
                    $filtered[] = $taxonomy;
                }
            }
            $terms = wp_get_object_terms(
                    $this->getPost()->ID, $filtered, array('fields' => 'ids')
            );
        } else {
            $terms = array();
        }

        return $terms;
    }

    private function inheritAccess($term_id) {
        $term = new aam_Control_Object_Term($this->getSubject(), $term_id);
        $access = $term->getOption();
        if (isset($access['post']) && $access['post']) {
            $result = array('post' => $access['post']);
        } elseif ($term->getTerm()->parent) {
            $result = $this->inheritAccess($term->getTerm()->parent);
        } else {
            $result = array();
        }

        return $result;
    }

    public function setPost(WP_Post $post) {
        $this->_post = $post;
    }

    public function getPost() {
        return $this->_post;
    }

    public function setOption($option) {
        $this->_option = (is_array($option) ? $option : array());
    }

    public function getOption() {
        return $this->_option;
    }

    public function has($area, $action) {
        $response = false;
        if (isset($this->_option['post'][$area][$action])) {
            $response = (intval($this->_option['post'][$area][$action]) ? true : false);
        }

        return $response;
    }

}
