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
 * Metabox & Widget Manager
 * 
 * @package AAM
 * @subpackage Model
 */
class mvb_Model_Manager_Restriction {

    /**
     *
     * @global array $submenu
     * @param string $tmpl
     * @param mvb_Model_Manager $parent
     * @return string 
     */
    public static function render($tmpl, $parent) {

        return mvb_Model_Template::replaceSub('POST_INFORMATION', '', $tmpl);
    }

    public static function renderInfo($id, $type, $parent, $tmpl) {
        global $wp_post_statuses, $wp_post_types;

        switch ($type) {
            case 'post':
                //get information about page or post
                $post = get_post($id);
                if ($post->ID) {
                    $tmpl = mvb_Model_Template::retrieveSub('POST', $tmpl);
                    if ($parent->getConfig()->hasRestriction('post', $id)) {
                        $restiction = $parent->getConfig()
                                ->getRestriction('post', $id);
                        $checked = ($restiction['restrict'] ? 'checked' : '');
                        $checked_front = ($restiction['restrict_front'] ? 'checked' : '');
                        $exclude = ($parent->getConfig()->hasExclude($id) ? 'checked' : '');
                        $expire = esc_js($restiction['expire']);
                    }
                    $markers = array(
                        '###post_title###' => mvb_Model_Helper::editPostLink($post),
                        '###disabled_apply_all###' => ($parent->getCurrentUser() ? 'disabled="disabled"' : ''),
                        '###restrict_checked###' => (isset($checked) ? $checked : ''),
                        '###restrict_front_checked###' => (isset($checked_front) ? $checked_front : ''),
                        '###restrict_expire###' => (isset($expire) ? $expire : ''),
                        '###exclude_page_checked###' => (isset($exclude) ? $exclude : ''),
                        '###post_type###' => ucfirst($post->post_type),
                        '###post_status###' => $wp_post_statuses[$post->post_status]->label,
                        '###post_visibility###' => mvb_Model_Helper::checkVisibility($post),
                        '###ID###' => $post->ID,
                        '###info_image###' => WPACCESS_CSS_URL . 'images/Info-tooltip.png',
                    );
                    //check what type of post is it and render exclude if page
                    $render_exclude = FALSE;
                    if (isset($wp_post_types[$post->post_type])) {
                        switch ($wp_post_types[$post->post_type]->capability_type) {
                            case 'page':
                                $render_exclude = TRUE;
                                break;

                            default:
                                break;
                        }
                    }

                    if ($render_exclude) {
                        $excld_tmpl = mvb_Model_Template::retrieveSub(
                                        'EXCLUDE_PAGE', $tmpl
                        );
                    } else {
                        $excld_tmpl = '';
                    }
                    $tmpl = mvb_Model_Template::replaceSub(
                                    'EXCLUDE_PAGE', $excld_tmpl, $tmpl
                    );
                    $tmpl = mvb_Model_Template::updateMarkers($markers, $tmpl);
                }
                break;

            case 'taxonomy':
                //get information about category
                $taxonomy = mvb_Model_Helper::getTaxonomyByTerm($id);
                $term = get_term($id, $taxonomy);
                if ($term->term_id) {
                    $tmpl = mvb_Model_Template::retrieveSub('CATEGORY', $tmpl);
                    if ($parent->getConfig()->hasRestriction('taxonomy', $id)) {
                        $tax = $parent->getConfig()->getRestriction('taxonomy', $id);
                        $checked = ($tax['restrict'] ? 'checked' : '');
                        $checked_front = ($tax['restrict_front'] ? 'checked' : '');
                        $expire = ($tax['expire'] ? date('m/d/Y', $tax['expire']) : '');
                    }
                    $markers = array(
                        '###name###' => mvb_Model_Helper::editTermLink($term),
                        '###disabled_apply_all###' => ($parent->getCurrentUser() ? 'disabled="disabled"' : ''),
                        '###restrict_checked###' => (isset($checked) ? $checked : ''),
                        '###restrict_front_checked###' => (isset($checked_front) ? $checked_front : ''),
                        '###restrict_expire###' => (isset($expire) ? $expire : ''),
                        '###post_number###' => $term->count,
                        '###ID###' => $term->term_id,
                        '###info_image###' => WPACCESS_CSS_URL . 'images/Info-tooltip.png',
                    );
                    $tmpl = mvb_Model_Template::updateMarkers($markers, $tmpl);
                }
                break;

            default:
                $tmpl = '';
                break;
        }
        
        $result = array(
            'status' => 'success',
            'html' => mvb_Model_Template::clearTemplate($tmpl)
        );
        
        return $result;
    }

}

?>