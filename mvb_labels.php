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

/*
 * TODO - Kindly to say, not the best way. But enough for first time
 * 
 */

$capabilitiesDesc = array(
    'switch_themes' => '
        <b>Since 2.0</b><br/>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels">Administration Panel</a> options:<br/>
         - Appearance<br/>
         - Appearance &gt; Themes',
    'edit_themes' => '
        <b>Since 2.0</b><br/>
        Allows access to Appearance &gt; <a href="http://codex.wordpress.org/Appearance_Editor_SubPanel" target="_blank" title="Appearance Editor SubPanel">Theme Editor</a> to edit theme files.',
    'edit_theme_options' => '
        <b>Since 3.0</b><br/>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels">Administration Panel</a> options:<br/>
        - Appearance &gt; <a href="http://codex.wordpress.org/Appearance_Background_SubPanel" target="_blank" title="Appearance Background SubPanel" >Background</a><br/>
        - Appearance &gt; <a href="http://codex.wordpress.org/Appearance_Header_SubPanel" target="_blank" title="Appearance Header SubPanel" >Header</a><br/>
        - Appearance &gt; <a href="http://codex.wordpress.org/Appearance_Menus_SubPanel" target="_blank" title="Appearance Menus SubPanel" >Menus</a><br/>
        - Appearance &gt; <a href="http://codex.wordpress.org/Appearance_Widgets_SubPanel" target="_blank" title="Appearance Widgets SubPanel" >Widgets</a><br/>
        - Also allows access to Theme Options pages if they are included in the Theme',
    'edit_published_posts' => '
        <div style="font-size:10px;"><b>Since 2.0</b><br/>
        User can edit their published posts. <i>This capability is off by default.</i>
        The core checks the capability <b>edit_posts</b>, but on demand this check is changed to <b>edit_published_posts</b>.<br/>
        If you don\'t want a user to be able edit his published posts, remove this capability. <i>(see also <a href="http://www.im-web-gefunden.de/wordpress-plugins/role-manager/#comment-5602" target="_blank" >this comment</a> on the <a href="http://www.im-web-gefunden.de/wordpress-plugins/role-manager/" target="_blank" title="http://www.im-web-gefunden.de/wordpress-plugins/role-manager/">Role Manager Plugin Homepage</a>).</i></div>',
    'edit_others_posts' => '
        <div style="font-size:10px;"><b>Since 2.0</b><br/>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels" >Administration Panel</a> options:<br/>
         - Manage &gt; Comments (<i>Lets user delete and edit every comment, see edit_posts above</i>)<br/>
         - user can edit other users\' posts through function get_others_drafts()<br/>
         - user can see other users\' images in inline-uploading [no? see <a href="http://trac.wordpress.org/file/trunk/wp-admin/inline-uploading.php" target="_blank" >inline-uploading.php</a>]<br/>
        ',
    'manage_options' => '
        <b>Since 2.0</b><br/>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels">Administration Panel</a> options: 
        <table class="description-table">
            <tbody>
                <tr>
                    <td>Settings &gt; General</td>
                    <td>Settings &gt; Writing</td>
                </tr>
                <tr>
                    <td>Settings &gt; Writing</td>
                    <td>Settings &gt; Reading</td>
                </tr>
                <tr>
                    <td>Settings &gt; Discussion</td>
                    <td>Settings &gt; Permalinks</td>
                </tr>
                <tr>
                    <td>Settings &gt; Miscellaneous</td>
                    <td>&nbsp;</td>
                </tr>
            </tbody>
       </table>',
    'install_themes' => '
        <b>Since 2.0</b><br/>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels">Administration Panel</a> options:<br/>
        - Appearance &gt; Add New Themes',
    'activate_plugins' => '
        <b>Since 2.0</b><br/>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels" >Administration Panel</a> options:<br/>  
        - <a href="http://codex.wordpress.org/Administration_Panels#Plugins_-_Add_Functionality_to_your_Blog" target="_blank" title="Administration Panels">Plugins</a>',
    'edit_plugins' => '
        <b>Since 2.0</b><br/>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels">Administration Panel</a> options:<br/>  
        - <a href="http://codex.wordpress.org/Administration_Panels#Plugins_-_Add_Functionality_to_your_Blog" target="_blank" title="Administration Panels">Plugins</a> &gt; <a href="http://codex.wordpress.org/Administration_Panels#Plugin_Editor" target="_blank" title="Administration Panels">Plugin Editor</a>',
    'install_plugins' => '
        <b>Since 2.0</b>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels">Administration Panel</a> options:<br/>  
        - <a href="http://codex.wordpress.org/Administration_Panels#Plugins_-_Add_Functionality_to_your_Blog" target="_blank" title="Administration Panels">Plugins</a> &gt; Add New',
    'edit_users' => '
        <b>Since 2.0</b><br/>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels">Administration Panel</a> options:<br/>  
        - <a href="http://codex.wordpress.org/Administration_Panels#Users_-_Your_Blogging_Family" target="_blank" title="Administration Panels">Users</a>',
    'edit_files' => '
        <b>Since 2.0</b><br/>
        <b>Note:</b> No longer used.',
    'moderate_comments' => '
        <b>Since 2.0</b><br/>
        Allows users to moderate comments from the Comments SubPanel (although a user needs the <b>edit_posts</b> Capability in order to access this)',
    'manage_categories' => '
        <b>Since 2.0</b><br/>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels">Administration Panel</a> options:<br/>  
        - Posts &gt; Categories<br/>
        - Links &gt; Categories',
    'manage_links' => '
        <b>Since 2.0</b><br/>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels">Administration Panel</a> options:<br/>  
        - Links<br/>
        - Links &gt; Add New',
    'upload_files' => '
        <b>Since 2.0</b><br/>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels">Administration Panel</a> options:<br/>  
        - Media<br/>
        - Media &gt; Add New',
    'import' => '
        <b>Since 2.0</b><br/>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels">Administration Panel</a> options:<br/>  
        - Tools &gt; Import<br/>
        - Tools &gt; Export',
    'unfiltered_html' => '
        <b>Since 2.0</b><br/>
        Allows user to post HTML markup or even JavaScript code in pages, posts, and comments.<br/><br/>
        <b>Note:</b> Enabling this option for untrusted users may result in their posting malicious or poorly formatted code.',
    'edit_posts' => '
        <b>Since 2.0</b><br/>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels">Administration Panel</a> options:<br/>  
        - Posts<br/>
        - Posts &gt; Add New<br/>
        - Comments<br/>
        - Comments &gt; Awaiting Moderation',
    'publish_posts' => '
        <b>Since 2.0</b><br/>
        See and use the "publish" button when editing their post <i>(otherwise they can only save drafts)</i><br/>
        Can use XML-RPC to publish <i>(otherwise they get a "Sorry, you can not post on this weblog or category.")</i>',
    'edit_pages' => '
        <b>Since 2.0</b><br/>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels">Administration Panel</a> options:<br/>  
        - Pages<br/>
        - Pages &gt; Add New',
    'read' => '
        <b>Since 2.0</b><br/>
        Allows access to <a href="http://codex.wordpress.org/Administration_Panels" target="_blank" title="Administration Panels">Administration Panel</a> options:<br/>
        - <a href="http://codex.wordpress.org/Dashboard_Dashboard_SubPanel" target="_blank" title="Dashboard Dashboard SubPanel">Dashboard</a><br/>
        - Users &gt; Your Profile<br/>
        <i>Used nowhere in the core code except the menu.php</i>',
    'edit_others_pages' => '<b>Since 2.1</b>',
    'edit_published_pages' => '<b>Since 2.1</b>',
    'edit_published_pages_2' => '<b>Since 2.1</b>',
    'delete_pages' => '<b>Since 2.1</b>',
    'delete_others_pages' => '<b>Since 2.1</b>',
    'delete_published_pages' => '<b>Since 2.1</b>',
    'delete_posts' => '<b>Since 2.1</b>',
    'delete_others_posts' => '<b>Since 2.1</b>',
    'delete_published_posts' => '<b>Since 2.1</b>',
    'delete_private_posts' => '<b>Since 2.1</b>',
    'edit_private_posts' => '<b>Since 2.1</b>',
    'read_private_posts' => '<b>Since 2.1</b>',
    'delete_private_pages' => '<b>Since 2.1</b>',
    'edit_private_pages' => '<b>Since 2.1</b>',
    'read_private_pages' => '<b>Since 2.1</b>',
    'delete_users' => '<b>Since 2.1</b>',
    'create_users' => '<b>Since 2.1</b>',
    'unfiltered_upload' => '<b>Since 2.3</b>',
    'edit_dashboard' => '<b>Since 2.5</b>',
    'update_plugins' => '<b>Since 2.6</b>',
    'delete_plugins' => '<b>Since 2.6</b>',
    'update_core' => '<b>Since 3.0</b>',
    'list_users' => '<b>Since 3.0</b>',
    'remove_users' => '<b>Since 3.0</b>',
    'add_users' => '<b>Since 3.0</b>',
    'promote_users' => '<b>Since 3.0</b>',
    'delete_themes' => '<b>Since 3.0</b>',
    'export' => '<b>Since 3.0</b>',
    'edit_comment' => '<b>Since 3.1</b>',
    'manage_sites' => '
        <b>Since 3.0</b><br/>
        Multi-site only<br/>
        Allows access to <a href="http://codex.wordpress.org/Network_Admin#Sites" target="_blank" title="Network Admin">Network Sites</a> menu<br/>
        Allows user to add, edit, delete, archive, unarchive, activate, deactivate, spam and unspam new site/blog in the network',
    'manage_network_users' => '
        <b>Since 3.0</b><br/>
        Multi-site only<br/>
        Allows access to <a href="http://codex.wordpress.org/Network_Admin#Users" target="_blank" title="Network Admin">Network Users</a> menu',
    'manage_network_themes' => '
        <b>Since 3.0</b><br/>
        Multi-site only<br/>
        Allows access to <a href="http://codex.wordpress.org/Network_Admin#Themes" target="_blank" title="Network Admin">Network Themes</a> menu',
    'manage_network_options' => '
        <b>Since 3.0</b><br/>
        Multi-site only<br/>
        Allows access to <a href="http://codex.wordpress.org/Network_Admin#Settings" target="_blank" title="Network Admin">Network Options</a> menu',
    'level_0' => 'User Level 0 converts to <a href="javascript:void(0);" title="">Subscriber</a>',
    'level_1' => 'User Level 1 converts to <a href="javascript:void(0);" title="">Contributor</a>',
    'level_2' => 'User Level 2 converts to <a href="javascript:void(0);" title="">Author</a>',
    'level_3' => 'User Level 3 converts to <a href="javascript:void(0);" title="">Author</a>',
    'level_4' => 'User Level 4 converts to <a href="javascript:void(0);" title="">Author</a>',
    'level_5' => 'User Level 5 converts to <a href="javascript:void(0);" title="">Editor</a>',
    'level_6' => 'User Level 6 converts to <a href="javascript:void(0);" title="">Editor</a>',
    'level_7' => 'User Level 7 converts to <a href="javascript:void(0);" title="">Editor</a>',
    'level_8' => 'User Level 8 converts to <a href="javascript:void(0);" title="">Administrator</a>',
    'level_9' => 'User Level 9 converts to <a href="javascript:void(0);" title="">Administrator</a>',
    'level_10' => 'User Level 10 converts to <a href="javascript:void(0);" title="">Administrator</a>',
    'publish_pages' => '<b>Description does not exist</b>',
    'administrator' => '<b>Description does not exist</b>',
    'update_themes' => '<b>Description does not exist</b>',
);

$upgrade_restriction = 'Install <a href="http://whimba.com/plugins/advanced-access-manager/add-ons/" target="_blank">Extend Restriction</a> to be able to set more then ' . WPACCESS_RESTRICTION_LIMIT . ' restrictions for one Role';

$restrict_message = '<p>You do not have sufficient permissions to perform this action</p>';

$help_context = '
    <p><h3>General Information</h3></p>
    <p>If you want to filter Admin Menu for some User Roles or just delete unnecessary dashboard widgets or metaboxes in Edit Post Page, this plugin is for you.
       You can do following things with Advanced Access Manager:</p>
<ul>
<li>Filter Admin Menu for specific User Role</li>
<li>Filter Dashboard Widgets for specific User Role</li>
<li>Filter List of Metaboxes in Edit Post page for specific User Role</li>
<li>Add new User Capabilities</li>
<li>Delete created User Capabilities</li>
<li>Create new User Roles</li>
<li>Delete any User Role</li>
<li>Save current User Roles settings and restore later</li>
<li>View the list of Posts Pages and Categories in a nice hierarchical tree</li>
<li>Filter Posts and Post Categories</li>
<li>Filter Pages and Sub Pages</li>
<li>Set expiration Date for specific Posts, Pages or even Categories</li>
<li>Reorganize Order of Main Menu for specific User Role</li>
</ul>
    <p><h3>Main Menu</h3></p>
    <p>Under Main Menu Tab there is a list of admin menu and submenus. You can Restrict access to certain menus of submenus by checking proper checkbox.
    Also you can reorganize the menu order by draggin and dropping menus in order you want.</p>
    <p><h3>Metabox & Widgets</h3></p>
    <p>This section allows you to filter the list of metaboxes (sections to the Write Post, Write Page, and Write Link editing pages) and dashboard widgets.</p>
    <p><h3>Capabilities</h3></p>
    <p>This is more advanced Tab which allows to create different combinations of User Roles for current User Role. If you are not familiar with Capabilities please read <a href="http://codex.wordpress.org/Roles_and_Capabilities" target="_blank">Roles and Capabilities</a>.</p>
    <p><h3>Posts & Pages</h3></p>
    <p>Tree View of Posts (grouped into categories) and Pages (organized hierarchically according to Parent Page parameter) where you can restrict access to certain page or post or the whole category for current User Role. There is also possibility to set expiration date to posts or pages.</p>
';
?>