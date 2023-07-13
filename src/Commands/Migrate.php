<?php

namespace WpHelperMigrations\Commands;

use WpHelperMigrations\Migrator;

class Migrate extends \WP_CLI_Command
{
    public function __invoke($args, $assoc_args)
    {
        $total_migrations_executed = Migrator::Run();
        $message = $total_migrations_executed > 0 ? "{$total_migrations_executed} migrations executed." : "No migration to execute.";
        \WP_CLI::success($message);
    }
}
