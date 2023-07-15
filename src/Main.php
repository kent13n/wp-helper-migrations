<?php

namespace WpHelperMigrations;

use WpHelperMigrations\Commands\Migrate;

class Main
{
    public function __construct($plugin_main_file)
    {
        $this->Setup();
        add_action('admin_menu', [$this, 'CreateAdminMenu']);
        add_action('admin_head', [$this, 'SetHead']);
        add_action('before_delete_post', [$this, 'CreateDeletePostMigration']);
        add_action('delete_attachment', [$this, 'CreateDeleteAttachmentMigation']);

        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('migrate', Migrate::class);
        }
    }

    public function Setup(): bool
    {
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        global $wpdb;
        $collation = $wpdb->has_cap('collation') ? $wpdb->get_charset_collate('collation') : '';
        $table = $wpdb->prefix . 'helper_migrations';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}';") === $table) {
            return false;
        }

        $sql = "
            CREATE TABLE IF NOT EXISTS {$table} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY (id)
            ) {$collation};
        ";

        dbDelta($sql);
        return true;
    }

    public function CreateAdminMenu()
    {
        add_menu_page('WP Helper Migrations', 'WP Migrations', 'manage_options', 'wp-helper-migrations', [$this, 'Render']);
    }

    public function CreateDeletePostMigration($post_id)
    {
        Migrator::Generate($post_id, Enums\Mode::Delete);
    }

    public function CreateDeleteAttachmentMigation($post_id)
    {
        Migrator::Generate($post_id, Enums\Mode::Delete);
    }

    public function SetHead()
    {
        $url = admin_url("admin.php?page=wp-helper-migrations");
        $page = esc_attr(filter_input(INPUT_GET, 'page'));
        if ($page !== 'wp-helper-migrations')
            return;

        echo '<style type="text/css">';
        echo '.wp-list-table .column-type {width: 10%;}';
        echo '.wp-list-table a.migrate {color: #00a32a;}';
        echo '</style>';
        echo '<script>jQuery(document).ready(function() { window.history.replaceState({additionalInformation: "URL Updated"}, "Wp Helper Migrations", "' . $url . '") });</script>';
    }

    public function Render()
    {
        $data_table = new ContentListTable();
?>
        <div class="wrap">
            <h2><?php esc_html_e('WP Helper Migrations', 'wp-helper-migrations') ?></h2>
            <form id="wp-helper-migrations" method="get">
                <input type="hidden" name="page" value="wp-helper-migrations" />
                <?php
                $data_table->prepare_items();
                $data_table->search_box('Search', 'search');
                $data_table->display();
                ?>
            </form>
        </div>
<?php
    }
}
