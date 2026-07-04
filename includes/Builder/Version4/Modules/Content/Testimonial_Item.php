<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Testimonial Item (child) Module Class.
 *
 * A single testimonial card (quote, author, role/company, avatar and star
 * rating) rendered with schema.org Review markup for SEO.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function in_array;
use function max;
use function min;
use function wp_kses_post;
use function wpautop;

/**
 * Testimonial Item (child) Module Class.
 *
 * @since 4.2.0
 */
class Testimonial_Item extends Child_Module {

	public function init(): void {
		$this->name   = esc_html__( 'Testimonial', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Testimonials', 'squad-modules-for-divi' );

		$this->slug             = 'disq_testimonial_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'author';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'content' => esc_html__( 'Content', 'squad-modules-for-divi' ),
					'rating'  => esc_html__( 'Rating', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'layout'  => esc_html__( 'Layout', 'squad-modules-for-divi' ),
					'quote'   => esc_html__( 'Quote Text', 'squad-modules-for-divi' ),
					'name'    => esc_html__( 'Author Text', 'squad-modules-for-divi' ),
					'role'    => esc_html__( 'Role Text', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'fonts'          => array(
				'quote' => array(
					'label'       => esc_html__( 'Quote', 'squad-modules-for-divi' ),
					'css'         => array( 'main' => "{$this->main_css_element} .squad-testimonial__content" ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'quote',
					'font_size'   => array( 'default' => '16px' ),
					'line_height' => array( 'default' => '1.7em' ),
				),
				'name'  => array(
					'label'        => esc_html__( 'Author', 'squad-modules-for-divi' ),
					'css'          => array( 'main' => "{$this->main_css_element} .squad-testimonial__name" ),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'name',
					'font_size'    => array( 'default' => '16px' ),
					'line_height'  => array( 'default' => '1.3em' ),
				),
				'role'  => array(
					'label'       => esc_html__( 'Role', 'squad-modules-for-divi' ),
					'css'         => array( 'main' => "{$this->main_css_element} .squad-testimonial__role" ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'role',
					'font_size'   => array( 'default' => '13px' ),
					'line_height' => array( 'default' => '1.4em' ),
				),
			),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);
	}

	/**
	 * Declare general fields for the module.
	 *
	 * @since 4.2.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'content'     => array(
				'label'       => esc_html__( 'Quote', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The testimonial text.', 'squad-modules-for-divi' ),
				'type'        => 'textarea',
				'default'     => esc_html__( 'This product changed the way our team works. Highly recommended!', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'content',
			),
			'author'      => array(
				'label'       => esc_html__( 'Author Name', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The person giving the testimonial.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Jane Doe', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'content',
			),
			'role'        => array(
				'label'       => esc_html__( 'Role / Title', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The author role or job title.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Marketing Lead', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'content',
			),
			'company'     => array(
				'label'       => esc_html__( 'Company', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Optional company name.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'content',
			),
			'avatar'      => array(
				'label'              => esc_html__( 'Avatar', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Upload an avatar for the author.', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'upload_button_text' => esc_attr__( 'Upload an avatar', 'squad-modules-for-divi' ),
				'choose_text'        => esc_attr__( 'Choose an avatar', 'squad-modules-for-divi' ),
				'update_text'        => esc_attr__( 'Set as avatar', 'squad-modules-for-divi' ),
				'default'            => '',
				'tab_slug'           => 'general',
				'toggle_slug'        => 'content',
			),
			'use_rating'  => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Rating', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Display the star rating.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'rating',
				)
			),
			'rating'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Rating', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Number of stars (0–5).', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '5', 'step' => '1' ),
					'default'        => '5',
					'unitless'       => true,
					'show_if'        => array( 'use_rating' => 'on' ),
					'tab_slug'       => 'general',
					'toggle_slug'    => 'rating',
				)
			),
			'star_color'  => array(
				'label'       => esc_html__( 'Star Color', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Color of the filled stars.', 'squad-modules-for-divi' ),
				'type'        => 'color-alpha',
				'default'     => '#FFB400',
				'show_if'     => array( 'use_rating' => 'on' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'rating',
			),
			'alignment'   => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Content Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Align the card content.', 'squad-modules-for-divi' ),
					'default'          => 'left',
					'default_on_front' => 'left',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'layout',
				)
			),
		);
	}

	/**
	 * Render module output.
	 *
	 * @since 4.2.0
	 *
	 * @param array<string, mixed> $attrs       List of unprocessed attributes.
	 * @param string               $content     Content being processed.
	 * @param string               $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$this->apply_card_css( $render_slug );

		$quote   = (string) $this->prop( 'content', '' );
		$author  = (string) $this->prop( 'author', '' );
		$role    = (string) $this->prop( 'role', '' );
		$company = (string) $this->prop( 'company', '' );
		$avatar  = (string) $this->prop( 'avatar', '' );

		$rating_html = '';
		if ( 'on' === $this->prop( 'use_rating', 'on' ) ) {
			$rating_html = $this->render_rating( max( 0, min( 5, (int) $this->prop( 'rating', '5' ) ) ) );
		}

		$quote_html = '' !== $quote ? sprintf( '<div class="squad-testimonial__content" itemprop="reviewBody">%s</div>', wpautop( wp_kses_post( $quote ) ) ) : '';

		$avatar_html = '' !== $avatar ? sprintf(
			'<div class="squad-testimonial__avatar"><img src="%1$s" alt="%2$s" itemprop="image" loading="lazy" /></div>',
			esc_url( $avatar ),
			esc_attr( $author )
		) : '';

		$role_text = $role;
		if ( '' !== $company ) {
			$role_text = '' !== $role
				? sprintf( '%1$s, <span itemprop="worksFor">%2$s</span>', esc_html( $role ), esc_html( $company ) )
				: sprintf( '<span itemprop="worksFor">%s</span>', esc_html( $company ) );
		} else {
			$role_text = esc_html( $role );
		}

		$name_html = '' !== $author ? sprintf( '<span class="squad-testimonial__name" itemprop="name">%s</span>', esc_html( $author ) ) : '';
		$role_html = '' !== $role_text ? sprintf( '<span class="squad-testimonial__role">%s</span>', $role_text ) : '';

		$author_block = ( '' !== $avatar_html || '' !== $name_html || '' !== $role_html )
			? sprintf(
				'<div class="squad-testimonial__author">%1$s<div class="squad-testimonial__meta" itemprop="author" itemscope itemtype="https://schema.org/Person">%2$s%3$s</div></div>',
				$avatar_html,
				$name_html,
				$role_html
			)
			: '';

		return sprintf(
			'<div class="squad-testimonial" itemscope itemtype="https://schema.org/Review">%1$s%2$s%3$s</div>',
			$rating_html,
			$quote_html,
			$author_block
		);
	}

	/**
	 * Build the star-rating markup with schema.org Rating.
	 *
	 * @since 4.2.0
	 *
	 * @param int $rating Star count (0–5).
	 *
	 * @return string
	 */
	private function render_rating( int $rating ): string {
		$stars = '';
		for ( $i = 1; $i <= 5; $i++ ) {
			$stars .= sprintf(
				'<span class="squad-testimonial__star%s" aria-hidden="true">%s</span>',
				$i <= $rating ? ' is-filled' : '',
				$i <= $rating ? '★' : '☆'
			);
		}

		return sprintf(
			'<div class="squad-testimonial__rating" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating"><meta itemprop="ratingValue" content="%1$d" /><meta itemprop="bestRating" content="5" />%2$s</div>',
			$rating,
			$stars
		);
	}

	/**
	 * Emit alignment + star-color CSS for the card.
	 *
	 * @since 4.2.0
	 *
	 * @param string $render_slug Slug of module that is used for rendering output.
	 *
	 * @return void
	 */
	public function apply_card_css( string $render_slug ): void {
		$alignment = (string) $this->prop( 'alignment', 'left' );
		$alignment = in_array( $alignment, array( 'left', 'center', 'right' ), true ) ? $alignment : 'left';

		$flex_map = array(
			'left'   => 'flex-start',
			'center' => 'center',
			'right'  => 'flex-end',
		);

		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%% .squad-testimonial',
				'declaration' => sprintf( 'text-align: %s; align-items: %s;', $alignment, $flex_map[ $alignment ] ),
			)
		);

		$star_color = self::sanitize_css_background( (string) $this->prop( 'star_color', '#FFB400' ) );
		if ( '' !== $star_color ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-testimonial__star.is-filled',
					'declaration' => sprintf( 'color: %s;', esc_attr( $star_color ) ),
				)
			);
		}
	}
}
