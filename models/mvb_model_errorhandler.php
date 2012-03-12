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
 * Error Handler Model Class
 * 
 * Error Handler
 * 
 * @package AAM
 * @subpackage Models
 * @author Vasyl Martyniuk <martyniuk.vasyl@gmail.com>
 * @copyrights Copyright © 2011 Vasyl Martyniuk
 * @license GNU General Public License {@link http://www.gnu.org/licenses/}
 */
class mvb_Model_errorHandler {

    public static $error_codes = array(
        '1' => 'E_ERROR',
        '2' => 'E_WARNING',
        '4' => 'E_PARSE',
        '8' => 'E_NOTICE',
        '16' => 'E_CORE_ERROR',
        '32' => 'E_CORE_WARNING',
        '64' => 'E_COMPILE_ERROR',
        '128' => 'E_COMPILE_WARNING',
        '256' => 'E_USER_ERROR',
        '512' => 'E_USER_WARNING',
        '1024' => 'E_USER_NOTICE',
        '2048' => 'E_STRICT',
        '4096' => 'E_RECOVERABLE_ERROR',
        '8192' => 'E_DEPRECATED',
        '16384' => 'E_USER_DEPRECATED',
        '32767' => 'E_ALL'
    );

    public static function handle($level, $message, $file, $line) {

        if ( (error_reporting() & $level) && strpos($file, 'advanced-access-manager') ) {
            
            $error_line  = '[' . date('Y-m-d H:i:s') . '] ' . $level . ': ';
            $error_line .= $message . " in {$file} ({$line})\n";
            
            file_put_contents(WPACCESS_LOG_DIR . '/error.log', $error_line, FILE_APPEND);
        }
    }

    public static function fatalHandler() {

        $error = error_get_last();

        if ($error !== NULL) {
            if (in_array($error['type'], array(E_ERROR, E_USER_ERROR))){
                self::handle(
                        $error['type'], 
                        $error['message'], 
                        $error['file'], 
                        $error['line']
                );
            }
        }
    }

}

?>