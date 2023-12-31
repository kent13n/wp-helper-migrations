<?php

namespace WpHelperMigrations;

class ContentListTable extends \WP_List_Table {
	/**
	 * Const to declare number of posts to show per page in the table.
	 */
	const POSTS_PER_PAGE = 10;

	/**
	 * Property to store post types
	 */
	private $allowed_post_types;

	public function __construct() {
		parent::__construct( [
			'singular' => '',
			'plural'   => '',
			'ajax'     => false
		] );

		$this->allowed_post_types = $this->allowed_post_types();
	}

	/**
	 * Retrieve post types to be shown in the table.
	 *
	 * @return array Allowed post types in an array.
	 */
	private function allowed_post_types() {
		$post_types = get_post_types( [ 'public' => true ] );

		// unset($post_types['attachment']);

		return $post_types;
	}

	/**
	 * Convert slug string to human readable.
	 *
	 * @param string $title String to transform human readable.
	 *
	 * @return string Human readable of the input string.
	 */
	private function human_readable( $title ) {
		return ucwords( str_replace( '_', ' ', $title ) );
	}

	/**
	 * A map method return all allowed post types to human readable format.
	 *
	 * @return array Array of allowed post types in human readable format.
	 */
	private function allowed_post_types_readable() {
		$formatted = array_map(
			[ $this, 'human_readable' ],
			$this->allowed_post_types
		);

		return $formatted;
	}

	/**
	 * Return instances post object.
	 *
	 * @return WP_Query Custom query object with passed arguments.
	 */
	protected function get_posts_object() {
		$post_types = $this->allowed_post_types;

		$post_args = [
			'post_type'      => $post_types,
			'post_status'    => [ 'publish', 'inherit' ],
			'posts_per_page' => self::POSTS_PER_PAGE,
		];

		$paged = filter_input( INPUT_GET, 'paged', FILTER_VALIDATE_INT );

		if ( $paged ) {
			$post_args['paged'] = $paged;
		}

		$post_type = filter_input( INPUT_GET, 'type' );

		if ( $post_type ) {
			$post_args['post_type'] = htmlspecialchars( $post_type );
		}

		$orderby = filter_input( INPUT_GET, 'orderby' );
		if ( ! empty( $orderby ) ) {
			$orderby = sanitize_sql_orderby( $orderby );
		}

		$order = esc_sql( filter_input( INPUT_GET, 'order' ) );

		if ( empty( $orderby ) ) {
			$orderby = 'date';
		}

		if ( empty( $order ) ) {
			$order = 'DESC';
		}

		$post_args['orderby'] = $orderby;
		$post_args['order']   = $order;

		$search = esc_sql( filter_input( INPUT_GET, 's' ) );
		if ( ! empty( $search ) ) {
			$post_args['s'] = $search;
		}

		return new \WP_Query( $post_args );
	}

	/**
	 * Display text for when there are no items.
	 */
	public function no_items() {
		esc_html_e( 'No posts found.', 'wp-helper-migrations' );
	}

	/**
	 * The Default columns
	 *
	 * @param array $item The Item being displayed.
	 * @param string $column_name The column we're currently in.
	 *
	 * @return string              The Content to display
	 */
	public function column_default( $item, $column_name ) {
		$result = '';
		switch ( $column_name ) {
			case 'date':
				$t_time    = get_the_time( 'Y/m/d g:i:s a', $item['id'] );
				$time      = get_post_timestamp( $item['id'], 'modified' );
				$time_diff = time() - $time;

				if ( $time && $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
					/* translators: %s: Human-readable time difference. */
					$h_time = sprintf( __( '%s ago', 'wp-helper-migrations' ), human_time_diff( $time ) );
				} else {
					$h_time = get_the_time( 'Y/m/d', $item['id'] );
				}

				$result = '<span title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $item['id'], 'date', 'list' ) . '</span>';
				break;

			case 'type':
				$result = $item['type'];
				break;
			case 'migrate':
				if ( $item['migrate'] === 0 ) {
					$result = 'No migration';
				} else {
					if ( time() - $item['migrate'] < DAY_IN_SECONDS ) {
						$h_time = sprintf( __( '%s ago', 'wp-helper-migrations' ), human_time_diff( $item['migrate'] ) );
					} else {
						$h_time = date( 'Y/m/d', $item['migrate'] );
					}

					$result = '<span title="' . date( 'Y/m/d g:i:s a', $item['migrate'] ) . '">' . $h_time . '</span>';
				}
				break;
		}

		return $result;
	}

	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return [
			'cb'      => '<input type="checkbox"/>',
			'title'   => __( 'Title', 'wp-helper-migrations' ),
			'type'    => __( 'Type', 'wp-helper-migrations' ),
			'date'    => __( 'Date', 'wp-helper-migrations' ),
			'migrate' => __( 'Migration', 'wp-helper-migrations' )
		];
	}

	/**
	 * Return title column.
	 *
	 * @param array $item Item data.
	 *
	 * @return string
	 */
	public function column_title( $item ) {
		$edit_url     = get_edit_post_link( $item['id'] );
		$post_link    = get_permalink( $item['id'] );
		$migrate_link = admin_url( "admin.php?page=wp-helper-migrations&migrate={$item['id']}" );
		$delete_link  = get_delete_post_link( $item['id'] );

		$output = '<strong>';

		/* translators: %s: Post Title */
		$output .= '<a class="row-title" href="' . esc_url( $edit_url ) . '" aria-label="' . sprintf( __( '%s (Edit)', 'wp-helper-migrations' ), $item['title'] ) . '">' . esc_html( $item['title'] ) . '</a>';
		$output .= _post_states( get_post( $item['id'] ), false );
		$output .= '</strong>';

		// Get actions.
		$actions = [
			'migrate' => '<a href="' . esc_url( $migrate_link ) . '" class="migrate">' . __( 'Migrate', 'wp-helper-migrations' ) . '</a>',
			'view'    => '<a href="' . esc_url( $post_link ) . '">' . __( 'View', 'wp-helper-migrations' ) . '</a>',
		];

		$row_actions = [];

		foreach ( $actions as $action => $link ) {
			$row_actions[] = '<span class="' . esc_attr( $action ) . '">' . $link . '</span>';
		}

		$output .= '<div class="row-actions">' . implode( ' | ', $row_actions ) . '</div>';

		return $output;
	}

	/**
	 * Column cb.
	 *
	 * @param array $item Item data.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s_id[]" value="%2$s" />',
			esc_attr( $this->_args['singular'] ),
			esc_attr( $item['id'] )
		);
	}

	/**
	 * Prepare the data for the WP List Table
	 *
	 * @return void
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$sortable              = $this->get_sortable_columns();
		$hidden                = [];
		$primary               = 'title';
		$this->_column_headers = [ $columns, $hidden, $sortable, $primary ];
		$data                  = [];

		$this->process_migration();
		$this->process_bulk_action();

		$get_posts_obj = $this->get_posts_object();

		if ( $get_posts_obj->have_posts() ) {

			while ( $get_posts_obj->have_posts() ) {

				$get_posts_obj->the_post();
				$wp_helper_migration = get_post_meta( get_the_ID(), '_wp_helper_migrations_id', true );
				$migration_time      = $wp_helper_migration ? MigrationHandler::GetLastMigrationDate( $wp_helper_migration ) : 0;

				$data[ get_the_ID() ] = [
					'id'      => get_the_ID(),
					'title'   => get_the_title(),
					'type'    => ucwords( get_post_type_object( get_post_type() )->labels->singular_name ),
					'date'    => get_post_datetime(),
					'migrate' => $migration_time === 0 ? 0 : $migration_time->getTimestamp(),
				];
			}
			wp_reset_postdata();
		}

		$this->items = $data;

		$this->set_pagination_args( [
			'total_items' => $get_posts_obj->found_posts,
			'per_page'    => $get_posts_obj->post_count,
			'total_pages' => $get_posts_obj->max_num_pages,
		] );
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return [
			'migrate' => __( 'Migrate', 'wp-helper-migrations' )
		];
	}

	/**
	 * Get bulk actions.
	 *
	 * @return void
	 */
	public function process_bulk_action() {
		if ( 'Migrate selection' === $this->current_action() ) {
			$post_ids = filter_input( INPUT_GET, '_id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

			if ( is_array( $post_ids ) ) {
				$post_ids = array_map( 'intval', $post_ids );
				foreach ( $post_ids as $id ) {
					$sql = Migrator::Generate( $id );
				}
				$this->display_messages();
			}
		}
	}

	public function process_migration() {
		$post_id = filter_input( INPUT_GET, 'migrate', FILTER_VALIDATE_INT );

		if ( ! empty( $post_id ) && $this->current_action() !== 'migrate' ) {
			$sql = Migrator::Generate( $post_id );
			$this->display_messages( false );
		}
	}

	protected function display_messages( $multiple = false ) {
		global $errors;
		if ( is_wp_error( $errors ) ) {
			// some errors
			if ( count( $errors->errors ) > 0 ) {
				echo '<div id="message" class="error">';
				foreach ( $errors->errors as $e ) {
					echo '<p>' . $e[0] . '</p>';
				}
				echo '</div>';
			}
		} else {
			// success
			$text = $multiple ? 'The migrations have been successfully completed.' : 'the migration has been successfully completed.';
			echo '<div class="updated notice">';
			echo '<p>' . __( $text, 'wp-helper-migrations' ) . '</p>';
			echo '</div>';
		}
	}

	/**
	 * Generates the table navigation above or below the table
	 *
	 * @param string $which Position of the navigation, either top or bottom.
	 *
	 * @return void
	 */
	protected function display_tablenav( $which ) {
		?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

            <br class="clear"/>
        </div>
		<?php
	}

	/**
	 * Overriden method to add dropdown filters column type.
	 *
	 * @param string $which Position of the navigation, either top or bottom.
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ) {

		if ( 'top' === $which ) {
			$drafts_dropdown_arg = [
				'options'   => [ '' => 'All' ] + $this->allowed_post_types_readable(),
				'container' => [
					'class' => 'alignleft actions',
				],
				'label'     => [
					'class'      => 'screen-reader-text',
					'inner_text' => __( 'Filter by Post Type', 'wp-helper-migrations' ),
				],
				'select'    => [
					'name'     => 'type',
					'id'       => 'filter-by-type',
					'selected' => filter_input( INPUT_GET, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
				],
			];

			$this->html_dropdown( $drafts_dropdown_arg );

			submit_button( __( 'Filter', 'wp-helper-migrations' ), 'secondary', 'action', false );
			submit_button( __( 'Migrate selection' ), 'primary', 'action', false, [ 'style' => 'margin-left: 15px;' ] );
		}
	}

	/**
	 * Navigation dropdown HTML generator
	 *
	 * @param array $args Argument array to generate dropdown.
	 *
	 * @return void
	 */
	private function html_dropdown( $args ) {
		?>

        <div class="<?php echo( esc_attr( $args['container']['class'] ) ); ?>">
            <label for="<?php echo( esc_attr( $args['select']['id'] ) ); ?>"
                   class="<?php echo( esc_attr( $args['label']['class'] ) ); ?>">
            </label>
            <select name="<?php echo( esc_attr( $args['select']['name'] ) ); ?>"
                    id="<?php echo( esc_attr( $args['select']['id'] ) ); ?>">
				<?php
				foreach ( $args['options'] as $id => $title ) {
					?>
                    <option <?php if ( $args['select']['selected'] === $id ) { ?> selected="selected" <?php } ?>
                            value="<?php echo( esc_attr( $id ) ); ?>">
						<?php echo esc_html( \ucwords( $title ) ); ?>
                    </option>
					<?php
				}
				?>
            </select>
        </div>

		<?php
	}

	/**
	 * Include the columns which can be sortable.
	 *
	 * @return Array $sortable_columns Return array of sortable columns.
	 */
	public function get_sortable_columns() {

		return [
			'title' => [ 'title', false ],
			'type'  => [ 'type', false ],
			'date'  => [ 'date', false ],
		];
	}
}
