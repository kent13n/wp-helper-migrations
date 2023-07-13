<?php

namespace WpHelperMigrations\Generators;

class PostGenerator extends AbstractQueryGenerator
{
    public function Generate($post): array
    {
        $data = $this->FormatData(
            $post,
            'post_author',
            'post_date',
            'post_date_gmt',
            'post_content',
            'post_title',
            'post_excerpt',
            'post_status',
            'comment_status',
            'ping_status',
            'post_password',
            'post_name',
            'to_ping',
            'pinged',
            'post_modified',
            'post_modified_gmt',
            'post_content_filtered',
            'post_parent',
            'guid',
            'menu_order',
            'post_type',
            'post_mime_type',
            'comment_count'
        );

        // (select post_id from wp_postmeta WHERE meta_key = "_wp_helper_migrations_id" and meta_value = "7c25a13f-6d89-456f-a9b9-2ea26dfeafcd")
        $this->condition = "ID = (SELECT post_id FROM {$this->table_prefix}postmeta WHERE meta_key = '_wp_helper_migrations_id' and meta_value = '{$this->migration_id}')";
        return $this->GenerateQuery($data);
    }
}
