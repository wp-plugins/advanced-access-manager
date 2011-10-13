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

class module_ajax {
    /*
     * Parent Object
     * 
     * Holds the main plugin object
     * 
     * @var object
     * @access protected
     */

    protected $pObj;

    /*
     * Requested action
     * 
     * @var string
     * @access protected
     */
    protected $action;


    /*
     * Main Constructor
     * 
     * @param object
     */

    public function __construct($pObj) {

        $this->pObj = $pObj;
        $this->action = $this->get_action();
    }

    /*
     * Process Ajax request
     * 
     */

    public function process() {

        switch ($this->action) {
            case 'add_capability':
                $this->add_capability();
                break;

            case 'check_addons':
                $this->check_addons();
                break;

            case 'create_role':
                $this->create_role();
                break;

            case 'delete_role':
                $this->delete_role();
                break;

            case 'restore_role':
                $this->restore_role($_POST['role']);
                break;

            case 'render_metabox_list':
                $this->render_metabox_list();
                break;

            case 'initiate_wm':
                $this->initiate_wm();
                break;

            case 'initiate_url':
                $this->initiate_url();
                break;

            case 'delete_capability':
                $this->delete_capability();
                break;

            case 'get_treeview':
                $this->get_treeview();
                break;

            case 'get_info':
                $this->get_info();
                break;

            case 'save_info':
                $this->save_info();
                break;

            case 'save_order':
                $this->save_order();
                break;

            case 'export':
                $this->export();
                break;

            default:
                die();
                break;
        }
    }

    /*
     * Get current action
     * 
     * @return bool Return true if ok
     */

    protected function get_action() {

        $a = (isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : FALSE);

        return $a;
    }

}

?>
