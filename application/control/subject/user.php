<?php/** * ====================================================================== * LICENSE: This file is subject to the terms and conditions defined in * * file 'license.txt', which is part of this source code package.       * * ====================================================================== *//** * * @package AAM * @author Vasyl Martyniuk <support@wpaam.com> * @copyright Copyright C 2013 Vasyl Martyniuk * @license GNU General Public License {@link http://www.gnu.org/licenses/} */class aam_Control_Subject_User extends aam_Control_Subject {    /**     * Subject UID: USER     */    const UID = 'user';    /**     *     * @param type $id     * @param type $blog_id     */    public function __construct($id, $blog_id) {        parent::__construct($id, $blog_id);        //initialize list of capabilities        $this->getObject(aam_Control_Object_Capability::UID);    }    /**     *     * @return type     */    public function delete() {        $response = false;        if (current_user_can('delete_users') && ($this->getId() !== get_current_user_id())) {            $response = wp_delete_user($this->getId());        }        return $response;    }    /**     *     * @global type $wpdb     * @return boolean     */    public function block() {        global $wpdb;        $response = false;        if (current_user_can('edit_users') && ($this->getId() != get_current_user_id())) {            $status = ($this->getSubject()->user_status == 0 ? 1 : 0);            if ($wpdb->update(                            $wpdb->users, array('user_status' => $status), array('ID' => $this->getId())                    )) {                $this->getSubject()->user_status = $status;                clean_user_cache($this->getSubject());                $response = true;            }        }        return $response;    }    /**     * Retrieve User based on ID     *     * @return WP_Role|null     *     * @access protected     */    protected function retrieveSubject() {        global $current_user;        if ($current_user instanceof WP_User && $current_user->ID == $this->getId()) {            $subject = $current_user;        } else {            $subject = new WP_User($this->getId());        }        return $subject;    }    /**     *     * @global type $current_user     * @return type     */    public function getCapabilities() {        global $current_user;        $meta_caps = $this->getSubject()->caps;        if (is_array($meta_caps) && !empty($meta_caps)) {            foreach ($this->getSubject()->allcaps as $cap => $grant) {                if (isset($meta_caps[$cap])) {                    $this->getSubject()->allcaps[$cap] = $meta_caps[$cap];                }            }        }        return $this->getSubject()->allcaps;    }    /**     * Remove Capability     *     * @param string  $capability     *     * @return boolean     *     * @access public     */    public function removeCapability($capability) {        return $this->getSubject()->add_cap($capability, false);    }    /**     *     * @param type $value     * @param type $object     * @param type $object_id     * @return type     */    public function updateOption($value, $object, $object_id = '') {        return update_user_option(                $this->getId(), $this->getOptionName($object, $object_id), $value        );    }    /**     *     * @param type $object     * @param type $object_id     * @return type     */    public function readOption($object, $object_id = '') {        $option = get_user_option(                $this->getOptionName($object, $object_id), $this->getId()        );        if (empty($option)) {            //try to get this option from the User's Role            $roles = $this->getSubject()->roles;            if (count($roles) > 1) {                aam_Core_Console::write(                        "User " . $this->getId() . " has more than one roles"                );            }            $role = new aam_Control_Subject_Role(                    array_shift($roles), $this->getBlogId()            );            $option = $role->getObject($object, $object_id)->getOption();        }        return $option;    }    /**     *     * @param type $object     * @param type $object_id     * @return type     */    public function deleteOption($object, $object_id = '') {        return delete_user_option(                $this->getId(), $this->getOptionName($object, $object_id), $this->getBlogId()        );    }    /**     *     * @param type $object     * @param type $object_id     * @return type     */    protected function getOptionName($object, $object_id) {        return "aam_{$object}" . ($object_id ? "_{$object_id}" : '');    }    /**     *     * @return type     */    public function getUID() {        return self::UID;    }}