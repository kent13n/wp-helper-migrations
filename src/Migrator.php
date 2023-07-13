<?php

namespace WpHelperMigrations;

use WpHelperMigrations\Generators\PostGenerator;
use WpHelperMigrations\Generators\PostMetaGenerator;

class Migrator
{
    public static string $wp_helper_migrations_id = '';
    public static Enums\Mode $mode = Enums\Mode::Unknown;

    private static function ExtractMode(array $post_meta, Enums\Mode $mode): Enums\Mode
    {
        $result = Enums\Mode::Unknown;
        if (array_key_exists('_wp_helper_migrations_id', $post_meta)) {
            self::$wp_helper_migrations_id = $post_meta['_wp_helper_migrations_id'][0];
            $existing_migrations = count(MigrationHandler::GetMigrationFiles(self::$wp_helper_migrations_id)) > 0;

            if ($mode === Enums\Mode::Delete) {
                $result = $existing_migrations ? Enums\Mode::Delete : Enums\Mode::Unknown;
            } else {
                $result = $existing_migrations ? Enums\Mode::Update : Enums\Mode::Insert;
            }
        } else if ($mode === Enums\Mode::Unknown) $result = Enums\Mode::Insert;
        return $result;
    }

    public static function Generate(int $post_id, Enums\Mode $mode = Enums\Mode::Unknown): bool
    {
        global $errors;
        global $wpdb;

        $post = get_post($post_id);
        if (!$post) {
            $errors = new \WP_Error();
            $errors->add('post_not_found', __("Couldn't find the post id: {$post_id}", 'wp-helper-migrations'));
            return false;
        }
        $post_meta = get_post_meta($post_id);

        self::$mode = self::ExtractMode($post_meta, $mode);
        if (self::$mode === Enums\Mode::Unknown) return false;

        if (empty(self::$wp_helper_migrations_id)) {
            self::$wp_helper_migrations_id = wp_generate_uuid4();
            add_post_meta($post_id, '_wp_helper_migrations_id', self::$wp_helper_migrations_id);
            $post_meta = get_post_meta($post_id);
        }

        $post_sql = self::PostSQL($post);
        $meta_sql = self::PostMetaSQL($wpdb, $post_meta, $post_id);

        $sql = array_merge($post_sql, $meta_sql);
        return MigrationHandler::CreateMigrationFile(self::$wp_helper_migrations_id, $sql);
    }

    public static function PostSQL(object $post): array
    {
        $postGenerator = new PostGenerator('posts', self::$mode, self::$wp_helper_migrations_id);
        return $postGenerator->Generate($post);
    }

    public static function PostMetaSQL(object $wpdb, array $meta_data, int $post_id): array
    {
        $postMetaGenerator = new PostMetaGenerator('postmeta', self::$mode, self::$wp_helper_migrations_id);
        return $postMetaGenerator->Generate($meta_data);
    }

    public static function Run(): int
    {
        global $wpdb;
        $total_migrations_executed = 0;
        $migrations = MigrationHandler::GetMigrationsToExecute();

        foreach ($migrations as $m) {
            require_once($m);
            $className = MigrationHandler::GenerateClassName(basename($m));
            $migration = new $className;
            $migration->Run();
            $wpdb->insert('wp_helper_migrations', array('name' => $className, 'created_at' => date("Y-m-d H:i:s")));
            $total_migrations_executed++;
        }

        return $total_migrations_executed;
    }

    private static function GetClassWithNamespace($className)
    {
        $all_classes = get_declared_classes();
        foreach ($all_classes as $class) {
            if (substr($class, -strlen($className)) === $className) {
                return $class;
            }
        }

        return false;
    }
}
