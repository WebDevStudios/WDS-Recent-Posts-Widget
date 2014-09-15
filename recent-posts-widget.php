<?php
/*
Plugin Name: WDS - Recent Posts Widget
Plugin URI: http://webdevstudios.com
Description: Display recent posts in a widget area.
Version: 1.0.0
Author: WebDevStudios
Author URI: http://webdevstudios.com
License: GPLv2
Text Domain: wds
*/

// Prevent direct file access
if ( ! defined ( 'ABSPATH' ) ) {
	exit;
}


class WDS_Recent_Posts_Widget extends WP_Widget {


	/**
	 * Unique identifier for this widget.
	 */
	protected $widget_slug = 'wds-recent-posts';


	/**
	 * Contruct widget.
	 */
	public function __construct() {

		parent::__construct(
			$this->get_widget_slug(),
			__( 'WDS - Recent Posts Widget', $this->get_widget_slug() ), // Widget name
			array(
				'classname'   => $this->get_widget_slug() . '-class',
				'description' => __( 'Display recent posts in a widget area.', $this->get_widget_slug() ) // Widget description
			)
		);

		add_action( 'save_post',    array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );
	}


	/**
	 * Return the widget slug.
	 */
	public function get_widget_slug() {
		return $this->widget_slug;
	}


	/**
	 * Delete all widget cache.
	 */
	public function flush_widget_cache() {
		wp_cache_delete( $this->get_widget_slug(), 'widget' );
		delete_transient( $this->id );
	}


	/**
	 * Front-end display of widget.
	 */
	public function widget( $args, $instance ) {

		// Widget args
		extract( $args );

		echo $before_widget;

		// Get values from database
		$title  = $instance['title'];
		$count  = $instance['count'];
		$cat    = $instance['cat'];
		$recent = $this->get_recent_posts( $count, $cat );
		?>

			<?php echo ( $title ) ? $before_title . esc_html( $title ) . $after_title : ''; ?>

			<?php if ( $recent->have_posts() ) : ?>

				<article>
				<?php while ( $recent->have_posts() ) : $recent->the_post(); ?>
					<header><h4><?php the_title(); ?></h4></header>
					<main><p><?php the_excerpt(); ?></p></main>
					<footer><a href="<?php the_permalink(); ?>" title="<?php esc_attr( the_title() ); ?>"><?php _e( 'Read More', 'wds' ); ?> ... </a></footer>
				<?php endwhile; ?>
				</article>

				<?php else: ?>
					<?php _e( 'No posts found.', 'wds' ); ?>
			<?php endif; ?>

			<?php wp_reset_postdata(); ?>

		<?php echo $after_widget; ?>

	<?php }


	/**
	 * Update form values as they are saved.
	 */
	public function update( $new_instance, $old_instance ) {

		// Previously saved values
		$instance = $old_instance;

		// Sanitize data before saving to database
		foreach ( array( 'title', 'cat' ) as $key => $value ) {
			$instance[$value] = sanitize_text_field( $new_instance[$value] );
		}

		$instance['count'] = absint( $new_instance['count'] );

		// Flush cache
		$this->flush_widget_cache();

		// Return updated options
		return $instance;
	}


	/**
	 * Back-end widget form with defaults.
	 */
	public function form( $instance ) {

		// Set default values
		$instance = wp_parse_args( (array) $instance, array(
			'title' => '',
			'count' => 3,
			'cat'   => '',
		) );

		// Get values from database
		$title = $instance['title'];
		$count = $instance['count'];
		$cat   = $instance['cat'];
		?>

		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wds' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_html( $title ); ?>" placeholder="optional" /></p>

		<p><label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Post Count:', 'wds' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="text" value="<?php echo absint( $count ); ?>" placeholder="3" /></p>

		<p><label for="<?php echo $this->get_field_id( 'cat' ); ?>"><?php _e( 'Category:', 'wds' ); ?></label></p>
		<p><select class="widefat" id="<?php echo $this->get_field_id( 'cat' ); ?>" name="<?php echo $this->get_field_name( 'cat' ); ?>">
			<?php $categories = get_categories(); ?>
			<?php foreach ( $categories as $cat ) : ?>
				<option value="<?php echo esc_html( $cat->slug ); ?>" <?php selected( $instance['cat'], esc_html( $cat->slug ) ); ?>><?php echo esc_html( $cat->cat_name ); ?></option>
			<?php endforeach; ?>
			</select>
		</p>

		<?php
	}


	/**
	 * Run a query and fetch some recent posts.
	 */
	public function get_recent_posts( $count, $cat ) {

		// Check for transient
		if ( false === ( $recent = get_transient( $this->id ) ) ) {

			// If none, run WP_Query
			$recent = new WP_Query( array(
				'category_name'  => esc_html( $cat ),
				'posts_per_page' => absint( $count ),
			) );

			// Put the results in a transient and expire after 12 hours
			set_transient( $this->id, $recent, 12 * HOUR_IN_SECONDS );

		}

		return $recent;
	}

}


/**
 * Register this widget with WordPress.
 */
function wds_register_recent_posts_widget() {
	register_widget( 'WDS_Recent_Posts_Widget' );
}
add_action( 'widgets_init', 'wds_register_recent_posts_widget' );
