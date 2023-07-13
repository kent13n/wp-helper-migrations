<?php

namespace WpHelperMigrations\Generators;

use WpHelperMigrations\Enums\Mode;

class PostMetaGenerator extends AbstractQueryGenerator
{
    public function Generate($post_meta): array
    {
        $sql = [];
        $index = 0;
        $meta_data = [];
        $this->condition = "(SELECT post_id FROM (SELECT post_id, meta_key, meta_value FROM {$this->table_prefix}postmeta) as m WHERE m.meta_key = '_wp_helper_migrations_id' and m.meta_value = '{$this->migration_id}')";

        $regex = $this->mode === Mode::Insert ? '/_edit_lock|_edit_last/i' : '/_edit_lock|_edit_last|_wp_helper_migrations_id/i';
        foreach ($post_meta as $meta_key => $meta_values) {
            if (!preg_match($regex, $meta_key)) {
                $meta_data[$index]['post_id'] = $this->mode === Mode::Insert ? '{{ post_id }}' : $this->condition;
                $meta_data[$index]['meta_key'] = $meta_key;
                $meta_data[$index]['meta_value'] = $meta_values[0];
                $index++;
            }
        }

        if (count($meta_data) > 0) {
            $t = $this->resetMetadata();
            if (strlen($t) > 0) $sql[] = $t;

            if ($this->mode === Mode::Update || $this->mode === Mode::Insert) {
                foreach ($meta_data as $data) {
                    $sql[] = $this->GeneratePostMetaQuery($data);
                }
            }
        }

        return $sql;
    }

    private function resetMetadata(): string
    {
        $query = '';

        if ($this->mode === Mode::Update || $this->mode === Mode::Delete) {
            $previousCondition = $this->condition;
            $this->condition = "post_id = (SELECT post_id FROM (SELECT post_id, meta_key, meta_value FROM {$this->table_prefix}postmeta) as m WHERE m.meta_key = '_wp_helper_migrations_id' and m.meta_value = '{$this->migration_id}')";
            if ($this->mode === Mode::Update) $this->condition .= " and meta_key != '_wp_helper_migrations_id' and meta_key != '_edit_lock' and meta_key != '_edit_last'";
            $query = $this->GenerateDeleteQuery();
            $this->condition = $previousCondition;
        }

        return $query;
    }

    private function GeneratePostMetaQuery(array $data): string
    {
        $values = array_reduce($data, function ($carry, $value) {
            if ($value === $this->condition) $result = $value;
            else $result = is_int($value) ? $value : "'" . esc_sql($value) . "'";
            if ($carry === null || strlen($carry) === 0) return $result;
            return $carry . ',' . $result;
        });

        return "INSERT INTO {$this->table_name} ({$this->FormatColumns($data)}) VALUES ({$values});\n";
    }
}
