<?php
if (!defined('WP_UNINSTALL_PLUGIN'))
    exit();
delete_option('sbs_quiz_options');
global $wpdb;
$tablename=$wpdb->prefix.'sbsquiz';
$sql="DROP TABLE IF EXISTS " . $tablename;
$wpdb->query($sql);
?>
