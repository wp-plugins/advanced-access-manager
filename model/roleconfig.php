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
 * Role Config Model Class
 * 
 * Role Config Object
 * 
 * @package AAM
 * @subpackage Models
 * @author Vasyl Martyniuk <martyniuk.vasyl@gmail.com>
 * @copyrights Copyright © 2011 Vasyl Martyniuk
 * @license GNU General Public License {@link http://www.gnu.org/licenses/}
 */
class mvb_Model_RoleConfig extends mvb_Model_Abstract_Config {
    
     /**
     * {@inheritdoc}
     */
    protected $type = 'role';

    /**
     * {@inheritdoc }
     */
    public function saveConfig() {

        $roles = mvb_Model_API::getRoleList(FALSE);
        if (isset($roles[$this->getID()])) {
            $roles[$this->getID()]['capabilities'] = $this->getCapabilities();
            mvb_Model_API::updateBlogOption('user_roles', $roles);
        }

        $options = (object) array(
                    'menu' => $this->getMenu(),
                    'metaboxes' => $this->getMetaboxes(),
                    'menu_order' => $this->getMenuOrder(),
                    'restrictions' => $this->getRestrictions(),
                    'excludes' => $this->getExcludes()
        );
        mvb_Model_API::updateBlogOption(WPACCESS_PREFIX . 'config_' . $this->getID(), $options);

        mvb_Model_Cache::clearCache();
        
        do_action(WPACCESS_PREFIX . 'do_save');
    }

    /**
     * {@inheritdoc }
     */
    protected function getConfig() {

        $config = mvb_Model_API::getBlogOption(WPACCESS_PREFIX . 'config_' . $this->getID());
        if (!$config) { //TODO - Delete is deprecated
            $options = (object) $this->getOldData(WPACCESS_PREFIX . 'options');
            $m_order = $this->getOldData(WPACCESS_PREFIX . 'menu_order');
            $restric = $this->getOldData(WPACCESS_PREFIX . 'restrictions');
            $exclude = $this->getExcludeList($restric);
            $config = (object) array();
            $config->menu = (isset($options->menu) ? $options->menu : array());
            $config->metaboxes = (isset($options->metaboxes) ? $options->metaboxes : array());
            $config->menu_order = (is_array($m_order) ? $m_order : array());
            $config->restrictions = (is_array($restric) ? $restric : array());
            $config->excludes = (is_array($exclude) ? $exclude : array());
        }
        $roles = mvb_Model_API::getRoleList(FALSE); //TODO - Potensially hole

        $this->setMenu($config->menu);
        $this->setMenuOrder($config->menu_order);
        $this->setMetaboxes($config->metaboxes);
        if (isset($roles[$this->getID()]['capabilities'])) {
            $this->setCapabilities($roles[$this->getID()]['capabilities']);
        }
        $this->setRestrictions($config->restrictions);
        $this->setExcludes($config->excludes);
    }

    /**
     * Get Data from Database
     * 
     * @param string $option
     * @return array
     * @todo Delete in next releases
     */
    protected function getOldData($option) {

        $id = $this->getID();
        $data = mvb_Model_API::getBlogOption($option);
        $data = ( isset($data[$id]) ? $data[$id] : array());

        return $data;
    }

    /**
     * Get Exclude list from current configurations
     * 
     * @access protected
     * @param array $restric
     * @return array
     * @todo Should be deleted in next releases
     */
    protected function getExcludeList($restric) {

        $exclude = array();
        if (isset($restric['posts']) && is_array($restric['posts'])) {
            foreach ($restric['posts'] as $post_id => $data) {
                if (isset($data['exclude']) && ($data['exclude'] == 1)) {
                    $exclude[$post_id] = 1;
                }
            }
        }

        return $exclude;
    }

}

?>