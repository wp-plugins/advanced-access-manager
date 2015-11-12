=== Advanced Access Manager ===
Contributors: vasyltech
Tags: access control, access manager, role, user, capability, admin, page, post
Requires at least: 3.8
Tested up to: 4.3.1
Stable tag: 3.0 Beta

The powerful and easy-to-use tool to improve security and manage access to your 
posts, pages and backend area.

== Description ==

Advanced Access Manager main and the only objective is to control access to your 
posts, pages, custom post types, categories, comments, metaboxes, dashboard widgets, 
frontend widgets and admin menu. In order to perform this task successfully, 
AAM has a powerful interface to manage your roles, users, visitors and capabilities.

Check our [website page](http://vasyltech.com/advanced-access-manager) to find out more 
about the Advanced Access Manager.

== Installation ==

1. Upload `advanced-access-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Backend Menu interface
2. Metaboxes & Widgets interface
3. Capabilities interface
4. Posts & Pages interface

== Changelog ==

= 3.0 Beta =
* Localized Javascript labels
* Added visual header for current editable subject (user/role)
* Extended capability update check
* Tested against WP 3.8 version
* Fixed bug with protected method for user and role subjects
* Added notification when extension update is available
* Updated main localization file
* Hide the backend post & pages access options for visitor

= 3.0 Alpha 2 =
* Fixed bug with Posts & Pages access manager
* Fixed bug with Posts & Pages pagination
* Fixed bug with Menu & Submenus filters

= 3.0 Alpha =
* Brand new and much more intuitive user interface
* Fully responsive design
* Better, more reliable and faster core functionality
* Completely new extension handler
* Added "Manage Access" action to the list of user

= 2.9.4 =
* Added missing files from the previous commit.

= 2.9.3 =
* Introduced AAM version 3 alpha

= 2.9.2 =
* Small fix in core
* Moved ConfigPress as stand-alone plugin. It is no longer a part of AAM
* Styled the AAM notification message

= 2.8.8 =
* AAM is changing the primary owner to VasylTech
* Removed contextual help menu
* Added notification about AAM v3

= 2.8.7 =
* Tested and verified functionality on the latest WordPress release
* Removed AAM Plus Package. Happy hours are over.

= 2.8.5 =
* Fixed bugs reported by (@TheThree)
* Improved CSS

= 2.8.4 =
* Updated the extension list pricing
* Updated AAM Plugin Manager

= 2.8.3 =
* Improved ConfigPress security (thanks to Tom Adams from security.dxw.com)
* Added ConfigPress new setting control_permalink

= 2.8.2 =
* Fixed issue with Default acces to posts/pages for AAM Plus Package
* Fixed issue with AAM Plugin Manager for lower PHP version

= 2.8.1 =
* Simplified the Repository internal handling
* Added Development License Support

= 2.8 =
* Fixed issue with AAM Control Manage HTML
* Fixed issue with __PHP_Incomplete_Class
* Added AAM Plugin Manager Extension
* Removed Deprecated ConfigPress Object from the core

= 2.7.3 =
* Added ConfigPress Reference Page

= 2.7.2 =
* Maintenance release

= 2.7.1 =
* Improved SSL handling
* Added ConfigPress property aam.native_role_id
* Fixed bug with countryCode in AAM Security Extension

= 2.7 =
* Fixed bug with subject managing check 
* Fixed bug with update hook
* Fixed issue with extension activation hook
* Added AAM Security Feature. First iteration
* Improved CSS

= 2.6 =
* Fixed bug with user inheritance
* Fixed bug with user restore default settings
* Fixed bug with installed extension detection
* Improved core extension handling
* Improved subject inheritance mechanism
* Removed deprecated ConfigPress Tutorial
* Optimized CSS
* Regenerated translation pot file

= 2.5.2 =
* Fixed issue with AAM Media Manager

= 2.5.1 =
* Extended AAM Media Manager Extension
* Adjusted control_area to AAM Media Manager
* Fixed issue with mb_* functions
* Added Contextual Help Menu
* Updated My Feature extension

= 2.5 =
* Fixed issue with AAM Plus Package and Multisite
* Introduced Development License
* Minor internal adjustment for AAM Development Community

= 2.5 Beta =
* Refactored Post & Pages Access List
* Extended ConfigPress with Post & Pages Access List Options
* Refactored internal UI hander
* Fixed issue with Restore Default flag and AAM Plus Package
* Added LIST Restriction for AAM Plus Package
* Added ADD Restriction for AAM Plus Package
* Filter list of editable roles based on current user level
* Gives ability for non-admin users manage AAM if admin granted access
* Removed Backup object. Replaces with Restore Default
* Merged ajax handler with UI manager
* Implemented Clear All Settings feature (one step closer to Import/Export)
* Added Error notification for Extension page
* Fixed bug with Multisite and AAM Plus Package ajax call
* Regenerated language file
* Fixed bug with non-existing term

= 2.4 =
* Added Norwegian language Norwegian (by Christer Berg Johannesen)
* Localize the default Roles
* Regenerated .pod file
* Added AAM Media Manager Extension
* Added AAM Content Manager Extension
* Standardized Extension Modules
* Fixed issue with Media list

= 2.3 =
* Added Persian translation by Ghaem Omidi
* Added Inherit Capabilities From Role drop-down on Add New Role Dialog
* Small Cosmetic CSS changes

= 2.2.3 =
* Improved Admin Menu access control
* Extended ConfigPress with aam.menu.undefined setting
* Fixed issue with Frontend Widget
* Updated Polish Language File

= 2.2.2 =
* Fixed very significant issue with Role deletion
* Added Unfiltered Capability checkbox
* Regenerated language file
* Fixed issue with language encoding
* Fixed issue with Metaboxes tooltips

= 2.2.1 =
* Fixed the issue with Activity include

= 2.2 =
* Fixed issue with jQuery UI Tooltip Widget
* Added AAM Warning Panel
* Added Event Log Feature
* Moved ConfigPress to separate Page (refactored internal handling)
* Reverted back the SSL handling
* Added Post Delete feature
* Added Post's Restore Default Restrictions feature
* Added ConfigPress Extension turn on/off setting
* Russian translation by (Maxim Kernozhitskiy http://aeromultimedia.com)
* Removed Migration possibility
* Refactored AAM Core Console model
* Increased the number of saved restriction for basic version
* Simplified Undo feature

= 2.1.1 =
* Fixed fatal error in caching mechanism
* Extended ConfigPress tutorial
* Fixed error for AAM Plus Package for PHP earlier versions
* Improved Admin over SSL check
* Improved Taxonomy Query handling mechanism

= 2.1 =
* Fixed issue with Admin Menu restrictions (thanks to MikeB2B)
* Added Polish Translation
* Fixed issue with Widgets restriction
* Improved internal User & Role handling
* Implemented caching mechanism
* Extended Update mechanism (remove the AAM cache after update)
* Added New ConfigPress setting aam.caching (by default is FALSE)
* Improved Metabox & Widgets filtering mechanism
* Added French Translation (by Moskito7)
* Added "My Feature" Tab
* Regenerated .pot file

= 2.0 =
* New UI
* Robust and completely new core functionality
* Over 3 dozen of bug fixed and improvement during 3 alpha & beta versions
* Improved Update mechanism

= 1.9.1 =
* Fixed bug with empty event list
* Fixed bug with direct attachment access
* Reverted back the default UI design
* Last release of 1.x AAM Branch

= 1.9 =
* AAM 2.0 alpha 1 Announcement

= 1.8.5 =
* Added Event Manager
* Added ConfigPress parameter "aam.encoding"

= 1.8 =
* Fixed user caching issue
* Fixed issue with encoding
* Clear output buffer to avoid from third party plugins issues
* Notification about new release 2.0

= 1.7.5 =
* Accordion Fix

= 1.7.3 =
* Fixed reported issue #8894 to PHPSnapshot
* Added Media File access control
* Extended ConfigPress Tutorial

= 1.7.2 =
* Fixed CSS issues

= 1.7.1 =
* Fixed issue with cache removal query
* Silenced Upgrade for release 1.7 and higher
* Removed Capabilities description
* Added .POT file for multi-language support
* Silenced issue in updateRestriction function
* Silenced the issue with phpQuery and taxonomy rendering

= 1.7 =
* Removed Zend Caching mechanism
* Silenced the issue with array_merge in API model
* Removed the ConfigPress reference
* Created ConfigPress PDF Tutorial
* Moved SOAP wsdl to local directory


= 1.6.9.1 =
* Changed the way AHM displays

= 1.6.9 =
* Encoding issue fixed
* Removed AWM Group page
* Removed .htaccess file
* Fixed bug with Super Admin losing capabilities

= 1.6.8.3 =
* Implemented native WordPress jQuery UI include to avoid version issues

= 1.6.8.2 =
* Fixed JS issue with dialog destroy

= 1.6.8.1 =
* Fixed Javascript issue
* Fixed issue with comment feature

= 1.6.8 =
* Extended ConfigPress
* New view
* Updated ConfigPress Reference Guide

= 1.6.7.5 =
* Implemented alternative way of Premium Upgrade
* Extended ConfigPress

= 1.6.7 =
* New design

= 1.6.6 =
* Bug fixing
* Maintenance work
* Added Multisite importing feature

= 1.6.5.2 =
* Updated jQuery UI lib to 1.8.20
* Minimized JavaScript
* Implemented Web Service for AWM Group page
* Implemented Web Service for Premium Version
* Fixed bug with User Restrictions
* Fixed bug with Edit Permalink
* Fixed bug with Upgrade Hook
* Reorganized Label Module (Preparing for Russian and Polish transactions)

= 1.6.5.1 (Beta) =
* Bug fixing
* Removed custom error handler

= 1.6.5 =
* Turn off error reporting by default
* More advanced Post/Taxonomy access control
* Added Refresh feature for Post/Taxonomy Tree
* Added Custom Capability Edit Permalink
* Filtering Post's Quick Menu
* Refactored JavaScript

= 1.6.3 =
* Added more advanced possibility to manage comments
* Change Capabilities view
* Added additional checking for plugin's reliability

= 1.6.2 =
* Few GUI changes
* Added ConfigPress reference guide
* Introduced Extended version
* Fixed bug with UI menu ordering
* Fixed bug with ConfigPress caching
* Fixed bugs in filtermetabox class
* Fixed bug with confirmation message in Multisite Setup

= 1.6.1.3 =
* Fixed issue with menu

= 1.6.1.2 =
* Resolved issue with chmod
* Fixed issue with clearing config.ini during upgrade

= 1.6.1.1 =
* Fixed 2 bugs reported by jimaek

= 1.6.1 =
* Silenced few warnings in Access Control Class
* Extended description to Manually Metabox Init feature
* Added possibility to filter Frontend Widgets
* Refactored the Option Page manager
* Added About page

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