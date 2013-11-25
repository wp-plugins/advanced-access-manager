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
abstract class aam_View_Abstract {

    /**
     *
     * @var type 
     */
    static private $_subject = null;

    /**
     *
     */
    public function __construct() {
        if (is_null(self::$_subject)) {
            $blog_id = intval(aam_Core_Request::request('blog', 1));
            $role_id = trim(aam_Core_Request::request('role'), '');
            $user_id = intval(aam_Core_Request::request('user'), 0);
            $visitor = intval(aam_Core_Request::request('visitor'), 0);

            //initialize access config
            if ($user_id) {
                $this->setSubject(new aam_Control_Subject_User($user_id, $blog_id));
            } elseif ($role_id) {
                $this->setSubject(new aam_Control_Subject_Role($role_id, $blog_id));
            } elseif ($visitor) {
                $this->setSubject(new aam_Control_Subject_Visitor('', $blog_id));
            }
        }
    }

    /**
     *
     * @return type
     */
    public function getSubject() {
        return $this->_subject;
    }

    /**
     *
     * @param aam_Control_Subject $subject
     */
    public function setSubject(aam_Control_Subject $subject) {
        $this->_subject = $subject;
    }

    /**
     *
     * @param type $tmpl_path
     * @return type
     */
    public function loadTemplate($tmpl_path) {
        ob_start();
        require_once($tmpl_path);
        $content = ob_get_contents();
        ob_clean();

        return $content;
    }

}