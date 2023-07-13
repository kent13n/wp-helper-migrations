<?php

namespace WpHelperMigrations;

class MigrationHandler
{
    private static function EnsureMigrationPathExist()
    {
        $path = self::GetMigrationPath();
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
    }

    public static function GetMigrationPath(): string
    {
        return apply_filters('wp_helper_migrations_path', WP_CONTENT_DIR . '/migrations');
    }

    public static function GetMigrationFiles($uuid = null): array
    {
        self::EnsureMigrationPathExist();

        $results = [];
        $files = glob(trailingslashit(self::GetMigrationPath()) . '/*.php');

        if ($uuid === null) return $files;

        foreach ($files as $file) {
            if (str_contains($file, $uuid)) {
                $results[] = $file;
            }
        }
        return $results;
    }

    public static function GetMigrationsAlreadyExecuted()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'helper_migrations';
        return $wpdb->get_col("SELECT name FROM $table");
    }

    public static function GetMigrationsToExecute()
    {
        $results = [];

        $all_migrations = self::GetMigrationFiles();
        $migrations_arleady_executed = self::GetMigrationsAlreadyExecuted();

        foreach ($all_migrations as $m) {
            if (!in_array(self::GenerateClassName(basename($m)), $migrations_arleady_executed)) {
                $results[] = $m;
            }
        }

        return $results;
    }

    public static function CreateMigrationFile(string $uuid, array $sql): bool
    {
        self::EnsureMigrationPathExist();

        $path = self::GetMigrationPath();
        $filename = time() . '_' . $uuid . '.php';

        $content = file_get_contents(dirname(__FILE__) . '/Stubs/migration.stub');
        $content = str_replace('{{ class }}', self::GenerateClassName($filename), $content);
        $content = str_replace('{{ run }}', self::GenerateRunMethod($sql), $content);
        $content = str_replace('{{ rollback }}', '', $content);
        $success = file_put_contents($path . '/' . $filename, $content) ? true : false;

        self::SetMigrationAsExecuted(self::GenerateClassName($filename));
        return $success;
    }

    public static function GenerateClassName(string $filename): string
    {
        $uuid = explode('.', $filename)[0];
        return 'Migration_' . str_replace('-', '_', $uuid);
    }

    public static function SetMigrationAsExecuted($migration)
    {
        global $wpdb;
        $wpdb->insert('wp_helper_migrations', array('name' => $migration, 'created_at' => date("Y-m-d H:i:s")));
    }

    private static function GenerateRunMethod(array $sql): string
    {
        global $wpdb;

        $request = '';
        foreach ($sql as $k => $q) {
            $q = trim($q, "\n");
            $indentation = $k === 0 ? '' : "\t\t\t\t";
            if (preg_match('/INSERT INTO ' . $wpdb->prefix . 'posts/i', $q)) {
                $request .= "{$indentation}\$wpdb->query(\"{$q}\");\n\t\t\t\t\$lastid = \$wpdb->insert_id;\n";
            } else {
                $request .= "{$indentation}\$wpdb->query(\"" . str_replace("'{{ post_id }}'", '$lastid', $q) . "\");\n";
            }
        }

        $content = "
            global \$wpdb;
            \$wpdb->query('START TRANSACTION');
            try {
                {$request}
                \$wpdb->query('COMMIT');
            } catch (\\Throwable \$e) {
                \$wpdb->query('ROLLBACK');
            }
        ";

        return $content;
    }
}
