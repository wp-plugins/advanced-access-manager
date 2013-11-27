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
final class aam_Core_API {

    /**
     * Get current blog's option
     *
     * @param string $option
     * @param mixed $default
     *
     * @return mixed
     *
     * @access public
     * @static
     * @global object $wpdb
     */
    public static function getBlogOption($option, $default = FALSE) {
        return get_option($option, $default);
    }

    /**
     * Update Blog Option
     *
     * @param string $option
     * @param mixed $data
     *
     * @return bool
     *
     * @access public
     * @static
     * @global object $wpdb
     */
    public static function updateBlogOption($option, $data) {
        return update_option($option, $data);
    }

    /**
     * Delete Blog Option
     *
     * @param string $option
     *
     * @return bool
     *
     * @access public
     * @static
     * @global object $wpdb
     */
    public static function deleteBlogOption($option) {
        return delete_option($option);
    }

    /**
     * Initiate HTTP request
     *
     * @param string $url Requested URL
     * @param bool $send_cookies Wheather send cookies or not
     * @param bool $return_content Return content or not
     * @return bool Always return TRUE
     */
    public static function cURL($url, $send_cookies = TRUE, $return_content = FALSE) {
        $header = array(
            'User-Agent' => aam_Core_Request::server('HTTP_USER_AGENT')
        );

        $cookies = array();
        if (is_array($_COOKIE) && $send_cookies) {
            foreach ($_COOKIE as $key => $value) {
                //SKIP PHPSESSID - some servers does not like it for security reason
                if ($key !== 'PHPSESSID') {
                    $cookies[] = new WP_Http_Cookie(array(
                                'name' => $key,
                                'value' => $value
                            ));
                }
            }
        }

        $res = wp_remote_request($url, array(
            'headers' => $header,
            'cookies' => $cookies,
            'timeout' => 5)
        );

        if (is_wp_error($res)) {
            $result = array(
                'status' => 'error',
                'url' => $url
            );
        } else {
            $result = array('status' => 'success');
            if ($return_content) {
                $result['content'] = $res['body'];
            }
        }

        return $result;
    }

}