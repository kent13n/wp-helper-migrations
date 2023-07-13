<?php

/**
 * Plugin Name: WP Helper Migrations
 * Plugin URI:
 * Description: WP Helper Migrations allows you to effortlessly create migrations for existings content.
 * Author: Quentin Jallet
 * Author URI:
 * Version: 1.0.0
 * Text Domain: wp-helper-migrations
 *
 * @package WpHelperMigrations
 */

defined('ABSPATH') || exit;

require_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

$plugin = new WpHelperMigrations\Main(__FILE__);

/**
 * Adding WP List Table class if it's not available.
 */
/* if (!class_exists(\WP_List_Table::class))
	require_once ABSPATH . "wp-admin{DIRECTORY_SEPARATOR}includes/class-wp-list-table.php"; */



/* add_action('shutdown', 'mySqlLogger');
function mySqlLogger()
{
	global $wpdb;

	$file = fopen(trailingslashit(WP_CONTENT_DIR) . 'uploads/sqlLogs.txt', 'a');

	// fwrite($file, "\n\n------ NEW REQUEST [" . date("F j, Y, g:i:s a") . "] ------\n\n");

	foreach ($wpdb->queries as $q) {
		if (preg_match('/^(update|insert|delete)/i', $q[0])) {
			if (!preg_match('/option_name[^=]*= .rewrite_rules|_postmeta.*_edit_lock|_postmeta.*_edit_last|option_name[^=]*= .cron|_options.*transient/i', $q[0])) {
				fwrite($file, $q[0] . " - ($q[1] s)" . "\n\n");
			}
		}
	}

	fclose($file);
} */

/* register_deactivation_hook(__FILE__, function () {
    // dd("deactivation");
});

register_uninstall_hook(__FILE__, function () {
    // dd("uninstall");
}); */

/* add_action('admin_menu', 'menu');
function menu()
{
	add_menu_page('WP Helper Migrations', 'WP Migrations', 'manage_options', 'wp-helper-migrations', 'pageSettings');
}

function pageSettings()
{
?>
	<h1>WP Helper Migrations</h1>
	<?php if (have_posts()) : ?>
		<?php while (have_posts()) : the_post(); ?>
			<h3><?= the_title() ?></h3>
		<?php endwhile; ?>

	<?php endif; ?>
<?php
} */
