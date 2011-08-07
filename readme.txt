=== Advanced Access Manager ===
Contributors: vasyl_m
Tags: user role, access manager, capability, metabox, admin menu, role manager, submenu, dashboard widget
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 0.9.7

Graphic interface to manage User Roles and Capabilities

== Description ==

You can afford to do next things with Advanced Access Manager:

* Filter Admin Menu for specific User Role
* Filter Dashboard Widgets for specific User Role
* Filter List of Metaboxes in Edit Post page for specific User Role
* Add or delete capabilities for User Role
* Create or Delete User Role

If you have any problems with current plugin, please send me and email or leave a
message on Forums Posts.

== Installation ==

1. Upload `advanced-access-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Find Access Manager under Users Admin Menu

== Frequently Asked Questions ==

= What is "Initiate URL" Button for, under Metaboxes tab? =

Sometimes list of additional metaboxes are conditional on edit post page. Like show
custom metabox "Photos" only if Post Status is Published. Access Manager initiates
the list of metaboxes for each post in status auto-draft. So that is why you have
manually put the URL to the edit post page where list of additional metaboxes could
be picked by the plugin.

= Why I can't delete a Role on Role Manager metabox? =

If there is at least one user with current role, you will be not able to delete it

= I deselected some Menu Items on Main Menu Tab, but they still not appear, why? =

The reason is that Main Menu Tab is not directly related to list of Capabilities. 
It means, if you selected/deselected some Menus or Submenus it will not add/delete
capabilities to current User Role. In such way if you want to give somebody access
to backend I'm recommending to use predefined set of options - Editor and then
just filter Main Menu. 

== Screenshots ==

1. General view of Access Manager
2. List of Metaboxes to Manage
3. List of Capabilities

== Changelog ==

= 0.9.7 =
* Added Dashboard Widget Filtering functionality

= 0.9.6 =
* Fixed bug with Metabox initialization if installed plugin executes wp_remove_metabox function

= 0.9.5 =
* Added pre-defined set of capabilities - Administrator, Editor, Author, Contributor, Subscriber and Clear All
* Fixed bug with submenu rendered as custom WP page, for example themes.php?page=theme_options
* Fixed bug with Add New Post submenu. If it was selected then no edit.php page was accessible.

= 0.9.0 =
* Added Restore Default Settings functionality
* Fixed bug with Whole Branch checkbox
* Put tooltip on the center right position instead of center top
* Added activation and deactivation hooks
* Changed Tab Order on Role Manager Section
* Implemented on unsaved page leaving notification

= 0.8.1 =
* Fixed issue with edit.php
* Added to support box my twitter account

= 0.8 =
* First version