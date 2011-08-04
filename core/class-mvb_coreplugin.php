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

class mvb_corePlugin {

    function __construct() {

        if (is_admin()) {
            $this->scripts();
            $this->styles();
        }
    }

    function styles() {
        wp_enqueue_style('dashboard');
        wp_enqueue_style('global');
        wp_enqueue_style('wp-admin');
    }

    function scripts() {
        wp_enqueue_script('postbox');
        wp_enqueue_script('dashboard');
        wp_enqueue_script('thickbox');
        wp_enqueue_script('media-upload');
    }

    /**
     * Create a potbox widget
     */
    function postbox($id, $title, $content) {
        $content = '
        <div id="' . $id . '" class="postbox">
            <div class="handlediv" title="Click to toggle"><br /></div>
            <h3 class="hndle"><span>' . $title . '</span></h3>
            <div class="inside">
                ' . $content . '
            </div>
        </div>';

        return $content;
    }

}

?>