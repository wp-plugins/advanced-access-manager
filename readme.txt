=== Advanced Access Manager ===
Contributors: vasyl_m
Donate link: http://whimba.org/donation
Tags: access manager, access control, capability, dashboard widget, expire, expire link, filter menu, page, post, metabox, role manager, user access, user control, user role, access config
Requires at least: 3.2
Tested up to: 3.3.1
Stable tag: 1.5.8.2

Graphic interface to manage User Roles, Capabilities and Post/Page Access

== Description ==

Advanced Access Manager is very powerful and flexible Access Control tool for 
your WordPress website. It supports Single WordPress installation and Multisite 
setup.
This is the basic list of features you can perform this AAM:

* Filter Admin Menu
* Filter Admin Panel
* Filter Dashboard Widgets
* Filter Metaboxes
* Manage Capabilities (Create, Delete)
* Manage User Roles (Create, Edit, Delete)
* Manage Access to your Posts, Pages or even Custom Post Types
* Give possibility to promote Users
* Manage Admin Menu Order
* Manage other Administrators
* Exclude Front-end Pages from Navigation

And many-many other features.

[youtube http://www.youtube.com/watch?v=zkyxply_JHs]

If you have any problems with current plugin, please send me an email or leave a
message on Forums Posts.


== Installation ==

1. Upload `advanced-access-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Find Access Manager under AWM Group Menu

== Frequently Asked Questions ==

= How to give access for Administrator's Role to Advanced Access Manager? =

If you are Super Admin, you can manage the Administrator's Role as other User
Roles. To give an access to Access Manager's Option Page, just create a new
capability <b>AAM Manage</b> and check it for Administrator's User Role.

= Why do I have a red message says JavaScript Error =

The reason you see this message is incompatibility with plugins which are not 
following the simply WordPress rules. Many plugins just print additional JavaScript 
libraries without any reason and this is the most frequent reason of conflicts.
 
= What is "Initiate URL" button for, under "Metaboxes & Widgets" Tab? =

Sometimes list of additional metaboxes is conditional on edit post page. Like e.g.
display custom metabox "Photos" only if Post Status is Published. Access Manager 
initiates the list of metaboxes for each post in status auto-draft. So that is why
you have to put manually the URL to the edit post page where list of additional 
metaboxes can be picked by the plugin.

= I can't edit comments. What should I do? =

To be able to edit comments, just go to "Capabilities" Tab and add new Capability - 
"Edit Comment". For administrator it'll automatically be added and this will let
to configure comment editing for other roles.

= I unchecked some Menus on "Main Menu" Tab but they are still not shown. Why? =

The reason is that "Main Menu" Tab is not directly related to list of Capabilities. 
It means, if you selected/deselected some Menu or Submenu it will not add or delete
correct capabilities to current User Role. In such way if you want to give somebody 
access to backend I recommend to use predefined set of options "Editor" and then
just filter Main Menu.


== Screenshots ==

1. General view of Access Manager
2. List of Metaboxes to Manage
3. List of Capabilities
4. Post/Page Tree View

== Changelog ==

= 1.6 =
* Fixed bug for post__not_in
* Fixed bug with Admin Panel filtering
* Added Restore Default button
* Added Social and Support links
* Modified Error Handling feature
* Modified Config Press Handling

= 1.5.8 =
* Fixed bug with categories
* Addedd delete_capabilities parameter to Config Press

= 1.5.7 =
* Bug fixing
* Introduced error handling
* Added internal .htaccess

= 1.5.6 =
* Introduced _Visitor User Role
* Fixed few core bugs
* Implemented caching system
* Improved API

= 1.5.5 =
* Performed code refactoring
* Added Access Config
* Added User Managing feature
* Fixed bugs related to WP 3.3.x releases

= 1.4.3 =
* Emergency bug fixing

= 1.4.2 =
* Fixed cURL bug

= 1.4.1 =
* Fixed some bugs with checking algorithm
* Maintained the code

= 1.4 =
* Added Multi-Site Support
* Added Multi-Language Support
* Improved checking algorithm
* Improved Super Admin functionality

= 1.3.1 =
* Improved Super Admin functionality
* Optimized main class
* Improved Checking algorithm
* Added ability to change User Role's Label
* Added ability to Exclude Pages from Navigation
* Added ability to spread Post/Category Restriction Options to all User Roles
* Sorted List of Capabilities Alphabetically

= 1.3 =
* Change some interface button to WordPress default
* Deleted General Info metabox
* Improved check Access algorithm for compatibility with non standard links
* Split restriction on Front-end and Back-end
* Added Page Menu Filtering
* Added Admin Top Menu Filtering
* Added Import/Export Configuration functionality 

= 1.2.1 =
* Fixed issue with propAttr jQuery IU incompatibility
* Added filters for checkAccess and compareMenu results

= 1.2 =
* Fixed some notice messages reported by llucax
* Added ability to sort Admin Menu
* Added ability to filter Posts, Categories and Pages

= 1.0 =
* Fixed issue with comment editing
* Implemented JavaScript error catching

= 0.9.8 =
* Added ability to add or remove Capabilities
* Fixed bug with network admin dashboard
* Fixed bug with Metabox initialization
* Fixed bug with whole branch checkbox if menu name has incompatible symbols for element's attribute ID
* Changed metabox list view
* Auto hide/show "Restore Default" link according to current User Role
* Optimized JavaScript and CSS
* Deleted Empty submenu holder. For example - Comments
* Changed bothering tooltip behavior
* Fixed bug with General metabox on Access Manager Option page
* Changed some labels
* Added auto-hide for message Options Updated after 10 sec

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