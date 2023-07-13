<?php

namespace WpHelperMigrations\Generators;

use WpHelperMigrations\Enums\Mode;

abstract class AbstractQueryGenerator
{
    public Mode $mode;
    public string $table_prefix;
    public string $table_name;
    public string $migration_id;
    public string $condition;

    public function __construct(string $table_name, Mode $mode, string $migration_id)
    {
        global $wpdb;
        $this->table_prefix = $wpdb->prefix;
        $this->table_name = $wpdb->prefix . $table_name;
        $this->mode = $mode;
        $this->migration_id = esc_sql($migration_id);
    }

    protected function FormatColumns(array $data): string
    {
        return join(',', array_keys($data));
    }

    protected function GenerateInsertQuery(array $data): string
    {
        $values = array_reduce($data, function ($carry, $value) {
            $result = is_int($value) ? $value : "'" . esc_sql($value) . "'";
            if ($carry === null || strlen($carry) === 0) return $result;
            return $carry . ',' . $result;
        });

        return "INSERT INTO {$this->table_name} ({$this->FormatColumns($data)}) VALUES ({$values});\n";
    }

    protected function GenerateUpdateQuery(array $data): string
    {
        $result = array_reduce(array_keys($data), function ($carry, $key) use ($data) {
            $value = is_int($data[$key]) ? $data[$key] : "'" . esc_sql($data[$key]) . "'";
            if ($carry === null || strlen($carry) === 0) return $key . '=' . $value;
            return $carry . ',' . $key . '=' . $value;
        });

        return "UPDATE {$this->table_name} SET {$result} WHERE {$this->condition};\n";
    }

    protected function GenerateDeleteQuery(): string
    {
        return "DELETE FROM {$this->table_name} WHERE {$this->condition};\n";
    }

    protected function GenerateQuery(array $data): array
    {
        if ($this->mode == Mode::Insert) {
            return [$this->GenerateInsertQuery($data)];
        } else if ($this->mode == Mode::Update) {
            return [$this->GenerateUpdateQuery($data)];
        } else if ($this->mode == Mode::Delete) {
            return [$this->GenerateDeleteQuery()];
        }
        return [];
    }

    protected function FormatData($origin, ...$columns): array
    {
        $data = [];
        foreach ($columns as $col) {
            $value = null;
            if (is_array($origin) && isset($origin[$col])) {
                $value = is_array($origin[$col]) ? $origin[$col][0] : $origin[$col];
                $data[$col] = $value;
            } else if (is_object($origin) && isset($origin->{$col})) {
                $value = is_array($origin->{$col}) ? $origin->{$col}[0] : $origin->{$col};
                $data[$col] = $value;
            }
        }
        return $data;
    }

    abstract public function Generate($data): array;
    /* abstract public function Update(array $values): string;
    abstract public function Delete(): bool; */
}
