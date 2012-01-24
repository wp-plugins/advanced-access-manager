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

function mvbahm_object(){}

mvbahm_object.prototype.init = function(){
    
    jQuery('#tabs').tabs();
    
    var icons = {
        header: "ui-icon-circle-arrow-e",
        headerSelected: "ui-icon-circle-arrow-s"
    };
    
    jQuery('.plugin-list').accordion({
        collapsible: true,
        header: 'h4',
        autoHeight: false,
        icons: icons,
        active: -1
    });
   
}

jQuery(document).ready(function(){
   
    mvb_Obj = new mvbahm_object();
   
    mvb_Obj.init();
});