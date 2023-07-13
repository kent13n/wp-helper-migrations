# Wp Helper Migrations

Wp Helper Migrations is a WordPress plugin that simplifies the process of creating post migrations in WordPress. With this plugin, you can easily generate and manage migrations for your posts, making it convenient to move or update your content across different WordPress installations.

## Features

- **Easy Migration Creation**: Quickly generate migration files for your posts with a simple command.
- **Effortless Management**: Seamlessly manage your migrations using intuitive commands and features.
- **Post Content Updates**: Update post content across multiple WordPress installations with ease.
- **Flexible Configuration**: Customize the migration settings to suit your specific needs.
- **Version Control Friendly**: Works well with version control systems, allowing for better collaboration and tracking.

## Installation

1. Download the Wp Helper Migrations plugin as a ZIP file.
2. In your WordPress admin dashboard, navigate to "Plugins" → "Add New".
3. Click on the "Upload Plugin" button and choose the ZIP file you downloaded.
4. Activate the plugin after the installation is complete.

## Usage

### Creating a Migration

To create a migration, follow these steps:

1. In the WordPress admin dashboard, navigate to "Wp Migrations" in the menu.
2. Click on the desired post's "Migrate" link to automatically generate the migration.
   - This will create a migration file in the appropriate directory based on the post's content.
3. The migration file will contain the necessary instructions to update the post in the destination WordPress installation.

Please note the following:

- **Automatic Delete Migration**: When a post with one or more migrations is permanently deleted, an automatic delete migration is created.
- This delete migration is triggered only when the post is permanently deleted and not when it is moved to the trash.

### Running Migrations

To run migrations and apply the changes to your posts, use the following command in WP CLI:
```
wp migrate
```

This command will execute all pending migrations and update the corresponding posts.

Please note that the migrations are executed using WP CLI, so make sure you have WP CLI installed and configured on your system.

### Configuration

The plugin allows you to customize the directory where migration files are stored. By default, migrations are stored in the migrations directory.
To customize the migrations directory, you can use the wp_helper_migrations_path filter. Here's an example of how to modify the migrations directory:

```
function my_custom_migrations_path( $default_path ) {
    // Replace 'custom-migrations' with your desired directory name
    return WP_CONTENT_DIR . '/custom-migrations';
}
add_filter( 'wp_helper_migrations_path', 'my_custom_migrations_path' );
```

In the above example, the my_custom_migrations_path function specifies a custom directory called 'custom-migrations' within the wp-content directory.

Feel free to customize the filter and directory path according to your needs.

### Known Limitations

* __No Rollback Support__: The rollback functionality is not currently implemented in this version of Wp Helper Migrations. Once a migration is executed, it cannot be rolled back automatically. Please ensure you have a backup of your data before running migrations.
* __Only support export of posts and postmeta__: The plugin does currently support only the export of posts and postmeta. You may need to handle the migration of everything else separately.

Please note these limitations and consider them when planning your migrations or working with this plugin.

## Contributing

Contributions are welcome! If you have any ideas, improvements, or bug fixes, please submit them as issues or pull requests in the [GitHub repository](https://github.com/kent13n/wp-helper-migrations).

## License

This plugin is released under the [MIT License](LICENSE).

## Credits

Wp Helper Migrations is developed and maintained by [Quentin Jallet](https://your-website.com).
