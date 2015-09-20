<?php
/**
    Copyright (C) <2014>  Vasyl Martyniuk <support@wpaam.com>

    This program is commercial software: you are not allowed to redistribute it 
    and/or modify. Unauthorized copying of this file, via any medium is strictly 
    prohibited.
    For any questions or concerns contact Vasyl Martyniuk <support@wpaam.com>
 */
 
 //define extension constant
 define('AAM_MEDIA_MANAGER_EXTENSION', true);
 
$dirname = basename(dirname(__FILE__));
define('AAM_MEDIA_BASE_URL', AAM_BASE_URL . 'extension/' . $dirname);

require_once dirname(__FILE__) . '/extension.php';

return new AAM_Extension_MediaManager($this->getParent());