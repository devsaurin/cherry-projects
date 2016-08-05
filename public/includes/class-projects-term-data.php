<?php
/**
 * Cherry Projects Term
 *
 * @package   Cherry_Project
 * @author    Cherry Team
 * @license   GPL-2.0+
 * @link      http://www.cherryframework.com/
 * @copyright 2014 Cherry Team
 */

/**
 * Class for Portfolio data.
 *
 * @since 1.0.0
 */
class Cherry_Project_Term_Data extends Cherry_Project_Data {

	/**
	 * Default options array
	 *
	 * @var array
	 */
	public $default_options = array();

	/**
	 * Current options array
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Current template name
	 *
	 * @var string
	 */
	public $template = '';

	/**
	 * Sets up our actions/filters.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->set_default_options();
	}

	/**
	 * Get defaults data options
	 *
	 * @return void
	 */
	public function set_default_options() {
		$this->default_options = array(
			'term_type'        => 'category',
			'listing-layout'   => 'grid-layout',
			'column-number'    => 3,
			'post-per-page'    => 9,
			'item-margin'      => 10,
			'grid-template'    => 'terms-grid-default.tmpl',
			'masonry-template' => 'terms-masonry-default.tmpl',
			'list-template'    => 'terms-list-default.tmpl',
			'echo'             => true,
		);

		/**
		 * Filter the array of default options.
		 *
		 * @since 1.0.0
		 * @param array options.
		 * @param array The 'the_portfolio_items' function argument.
		 */
		$this->default_options = apply_filters( 'cherry_projects_term_default_options', $this->default_options );
	}

	/**
	 * Render project term
	 *
	 * @return string html string
	 */
	public function render_projects_term( $options = array() ) {
		//$this->enqueue_styles();
		//$this->enqueue_scripts();

		$this->options = wp_parse_args( $options, $this->default_options );

		switch ( $this->options['listing-layout'] ) {
			case 'masonry-layout':
				$this->template = $this->options['masonry-template'];
				break;
			case 'grid-layout':
				$this->template = $this->options['grid-template'];
				break;
			case 'list-layout':
				$this->template = $this->options['list-template'];
				break;
		}

		$settings = array(
			'list-layout'   => $this->options['listing-layout'],
			'post-per-page' => $this->options['post-per-page'],
			'column-number' => $this->options['column-number'],
			'item-margin'   => $this->options['item-margin'],
		);

		$settings = json_encode( $settings );

		$terms = $this->get_projects_terms(
			array(
				'taxonomy' => CHERRY_PROJECTS_NAME . '_' . $this->options['term_type']
			)
		);

		$html = '<div class="cherry-projects-terms-wrapper">';

			$container_class = 'projects-terms-container ' . $this->options['listing-layout'];

			$html .= sprintf( '<div class="%1$s" data-settings=\'%2$s\'>', $container_class, $settings );
				$html .= '<div class="projects-terms-list">';
					$html .= $this->render_projects_term_items( $terms );
				$html .= '</div>';
			$html .= '</div>';

		// Close wrapper.
		$html .= '</div>';

		if ( ! filter_var( $this->options['echo'], FILTER_VALIDATE_BOOLEAN ) ) {
			return $html;
		}

		echo $html;

	}

	/**
	 * Get term set object
	 *
	 * @param  array  $args Args
	 * @return object
	 */
	public function get_projects_terms( $args = array() ) {

		$defaults_args = apply_filters( 'cherry_projects_default_terms_args',
			array(
				'taxonomy'   => null,
				'order'      => 'ASC',
				'number'     => '',
				'offset'     => '',
				'hide_empty' => false,
			)
		);

		$args = wp_parse_args( $args, $defaults_args );

		$terms = get_terms( $args );

		if ( isset( $terms ) && $terms ) {
			return $terms;
		} else {
			return false;
		}
	}

	/**
	 * Render terms item
	 *
	 * @param  object $terms Terms objects
	 * @return string
	 */
	public function render_projects_term_items( $terms ) {
		$count = 1;
		$html = '';

		if ( $terms ) {

			// Item template.
			$template = $this->get_template_by_name( $this->template, 'projects-terms' );

			$macros = '/%%.+?%%/';

			$callbacks = $this->setup_template_data( $this->options );

			foreach ( $terms as $term_key => $term ) {
				$callbacks->set_term_data( $term );
				$template_content = preg_replace_callback( $macros, array( $this, 'replace_callback' ), $template );

				$html .= sprintf( '<div %1$s class="%2$s %3$s %4$s">',
					'id="projects-term-' . $term_key .'"',
					'projects-terms-item',
					'item-' . $count,
					( ( $count++ % 2 ) ? 'odd' : 'even' )
				);
					$html .= '<div class="inner-wrapper">';
						$html .= $template_content;
					$html .= '</div>';
				$html .= '</div>';

				$callbacks->clear_term_data();
			}

		} else {
			echo '<h4>' . esc_html__( 'Terms not found', 'cherry-projects' ) . '</h4>';
		}

/*		if ( $posts_query->have_posts() ) {

			// Item template.
			$template = $this->get_template_by_name( $settings['template'], 'projects' );

			$macros    = '/%%.+?%%/';
			$callbacks = $this->setup_template_data( $settings );

			while ( $posts_query->have_posts() ) : $posts_query->the_post();
				$post_id  = $posts_query->post->ID;
				$thumb_id = get_post_thumbnail_id();

				$template_content = preg_replace_callback( $macros, array( $this, 'replace_callback' ), $template );

				$size_array	= cherry_projects()->projects_data->cherry_utility->satellite->get_thumbnail_size_array( 'large' );
				$data_attrs = '';
				if ( 'justified-layout' === $settings['list_layout'] || 'cascading-grid-layout' === $settings['list_layout']  ) {
					if ( has_post_thumbnail( $post_id ) ) {
						$attachment_image_src = wp_get_attachment_image_src( $thumb_id, 'large' );

					}
					$data_attrs = sprintf(
						'data-image-width="%1$s" data-image-height="%2$s"',
						isset( $attachment_image_src[1] ) ? $attachment_image_src[1] : $size_array['width'],
						isset( $attachment_image_src[2] ) ? $attachment_image_src[2] : $size_array['height']
					);
				}

				$html .= sprintf( '<div %1$s class="%2$s %3$s %4$s %5$s %6$s %7$s" %8$s>',
					'id="quote-' . $post_id .'"',
					'projects-item',
					'item-' . $count,
					( ( $count++ % 2 ) ? 'odd' : 'even' ),
					'animate-cycle-show',
					$this->default_options['listing-layout'] . '-item',
					$this->default_options['hover-animation'] . '-hover',
					$data_attrs
				);
					$html .= '<div class="inner-wrapper">';

						$html .= $template_content;

					$html .= '</div>';
				$html .= '</div>';

				$callbacks->clear_data();
			endwhile;
		} else {
			echo '<h4>' . esc_html__( 'Posts not found', 'cherry-projects' ) . '</h4>';
		}*/

		// Reset the query.
		//wp_reset_postdata();

		return $html;
	}

}