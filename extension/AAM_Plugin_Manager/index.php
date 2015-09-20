<?php

/**
  Copyright (C) <2013-2014>  Vasyl Martyniuk <support@wpaam.com>

  This program is commercial software: you are not allowed to redistribute it
  and/or modify. Unauthorized copying of this file, via any medium is strictly
  prohibited.
  For any questions or concerns contact Vasyl Martyniuk <support@wpaam.com>
 */

//global constants
$dirname = basename(dirname(__FILE__));
define('AAM_PLUGIN_MANAGER_BASE_URL', AAM_BASE_URL . 'extension/' . $dirname);

//load the Extension Controller
require_once dirname(__FILE__) . '/extension.php';

//instantiate and return the Extension controller.
return new AAM_Plugin_Manager($this->getParent());