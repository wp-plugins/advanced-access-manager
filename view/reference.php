<?php

require_once('../../../../wp-admin/admin.php');

wp_enqueue_style('wpaccess-treeview', WPACCESS_CSS_URL . 'treeview/jquery.treeview.css');
wp_enqueue_style('wpaccess-reference', WPACCESS_CSS_URL . 'reference.css');
wp_enqueue_script('jquery-treeview', WPACCESS_JS_URL . 'treeview/jquery.treeview.js', array('jquery'));
wp_enqueue_script('jquery-treeedit', WPACCESS_JS_URL . 'treeview/jquery.treeview.edit.js');
wp_enqueue_script('admin-reference', WPACCESS_JS_URL . 'admin-reference.js');

iframe_header('ConfigPress Reference');

echo mvb_Model_Template::readTemplate(WPACCESS_TEMPLATE_DIR . 'reference.html');

iframe_footer();
?>