=== Advanced Access Manager ===
Contributors: vasyl_m
Donate link: http://whimba.com/advanced-access-manager-donation/
Tags: user role, access manager, filter posts, user control, capability, metabox, user access, filter menu, role manager, filter pages, dashboard widget, access control, expire link, expire
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 1.0

Graphic interface to manage User Roles and Capabilities

== Description ==

If you want to filter Admin Menu for some User Roles or just delete unnecessary 
dashboard widgets or metaboxes in Edit Post Page, this plugin is for you.
You can do following things with Advanced Access Manager:

* Filter Admin Menu for specific User Role
* Filter Dashboard Widgets for specific User Role
* Filter List of Metaboxes in Edit Post page for specific User Role
* Add new User Capabilities
* Delete created User Capabilities
* Create new User Roles
* Delete any User Role
* Save current User Roles settings and restore later

About additional features, I'm working on now, you can find on my <a href="http://whimba.com/new-features-in-advanced-access-manager/" target="_blank">website</a>.

[youtube http://www.youtube.com/watch?v=zkyxply_JHs]

If you have any problems with current plugin, please send me an email or leave a
message on Forums Posts.

== Installation ==

1. Upload `advanced-access-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Find Access Manager under Users Admin Menu

== Frequently Asked Questions ==

= It is not working! Why? =

Actually it works. To make Advanced Access Manager good looking and easy to navigate,
I'm using the latest JavaScript libraries - jQuery and jQuery UI. And this is a standard
for today. If you have installed plugins out of date, you, probably will have some problems.
In such way, please <a href="mailto:admin@whimba.com">contact me</a> or leave a message on a Forums Posts.   
 
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

= What is "Restore Default" link near Save button? =

Advanced Access Manager has implemented activation hook which will store all User
Roles defined in the system with the list of capabilities. So if you did something wrong,
you'll be able to restore original settings.
After Advanced Access Manager deactivation, all setting will be restored automatically.

== Screenshots ==

1. General view of Access Manager
2. List of Metaboxes to Manage
3. List of Capabilities

== Changelog ==

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