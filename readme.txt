=== Advanced Access Manager ===
Contributors: vasyl_m
Donate link: http://whimba.com/advanced-access-manager-donation/
Tags: access manager, access-control, capability, dashboard widget, expire, expire link, filter menu, filter pages, filter posts, metabox, role manager, user access, user control, user role
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 1.5

Graphic interface to manage User Roles, Capabilities and Post/Page Access

== Description ==

If you want to filter Admin Menu for some User Roles or just delete unnecessary 
dashboard widgets or metaboxes in Edit Post Page or filter the list of posts, pages and categories, 
this plugin is for you. You can do following things with Advanced Access Manager:

* Filter Admin Menu for specific User Role
* Filter Dashboard Widgets for specific User Role
* Filter List of Metaboxes in Edit Post page for specific User Role
* Add new User Capabilities
* Delete created User Capabilities
* Create new User Roles
* Delete any User Role
* Save current User Roles settings and restore later
* NEW! View the list of Posts Pages and Categories in a nice hierarchical tree 
* NEW! Filter Posts and Post Categories
* NEW! Filter Pages and Sub Pages
* NEW! Set expiration Date for specific Posts, Pages or even Categories
* NEW! Reorganize Order of Main Menu for specific User Role

PLEASE NOTICE, that it filters not only Front-End Posts, Pages and Categories, but
also and Back-End.

[youtube http://www.youtube.com/watch?v=zkyxply_JHs]

If you have any problems with current plugin, please send me an email or leave a
message on Forums Posts.


== Installation ==

1. Upload `advanced-access-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Find Access Manager under Users Admin Menu

== Frequently Asked Questions ==

= It is not working! Why? =

Actually it works. This plugin was tested by hundreds of people and it is also
successfully work on more the 10 projects I did. The reason it can behaviors strange
is incompatibility with plugins which are not following the simply WordPress rules.
Many plugins just print additional JavaScript libraries without any reason and 
this is the most frequent reason of conflicts.
 
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
4. Post/Page Tree View

== Changelog ==

= 1.5 =
* Change some interface button to WordPress default
* Deleted General Info metabox
* Improved check Access algorithm for compatibility with non standard links
* Split restriction on Front-end and Back-end
* Added Page Menu Filtering
* Added Admin Top Menu Filtering 

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