<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Team Member Item (child) Module Class.
 *
 * A single team-member card (photo, name, role, bio and social links) rendered
 * with schema.org Person markup for SEO.
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
use function wp_kses_post;
use function wpautop;

/**
 * Team Member Item (child) Module Class.
 *
 * @since 4.2.0
 */
class Team_Member_Item extends Child_Module {

	/**
	 * Supported social networks: field key => human label.
	 *
	 * @since 4.2.0
	 *
	 * @var array<string, string>
	 */
	private const SOCIAL_NETWORKS = array(
		'facebook'  => 'Facebook',
		'x_twitter' => 'X (Twitter)',
		'linkedin'  => 'LinkedIn',
		'instagram' => 'Instagram',
		'youtube'   => 'YouTube',
		'website'   => 'Website',
	);

	public function init(): void {
		$this->name   = esc_html__( 'Team Member', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Team Members', 'squad-modules-for-divi' );

		$this->slug             = 'disq_team_member_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'name';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'content' => esc_html__( 'Content', 'squad-modules-for-divi' ),
					'social'  => esc_html__( 'Social Links', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'layout'   => esc_html__( 'Layout', 'squad-modules-for-divi' ),
					'name'     => esc_html__( 'Name Text', 'squad-modules-for-divi' ),
					'position' => esc_html__( 'Position Text', 'squad-modules-for-divi' ),
					'bio'      => esc_html__( 'Bio Text', 'squad-modules-for-divi' ),
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
				'name'     => array(
					'label'        => esc_html__( 'Name', 'squad-modules-for-divi' ),
					'css'          => array( 'main' => "{$this->main_css_element} .squad-team-member__name" ),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'name',
					'header_level' => array( 'default' => 'h3' ),
					'font_size'    => array( 'default' => '20px' ),
					'line_height'  => array( 'default' => '1.3em' ),
				),
				'position' => array(
					'label'       => esc_html__( 'Position', 'squad-modules-for-divi' ),
					'css'         => array( 'main' => "{$this->main_css_element} .squad-team-member__position" ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'position',
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.4em' ),
				),
				'bio'      => array(
					'label'       => esc_html__( 'Bio', 'squad-modules-for-divi' ),
					'css'         => array( 'main' => "{$this->main_css_element} .squad-team-member__bio" ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'bio',
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.6em' ),
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
		$fields = array(
			'image'      => array(
				'label'              => esc_html__( 'Photo', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Upload a photo for this team member.', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'upload_button_text' => esc_attr__( 'Upload a photo', 'squad-modules-for-divi' ),
				'choose_text'        => esc_attr__( 'Choose a photo', 'squad-modules-for-divi' ),
				'update_text'        => esc_attr__( 'Set as photo', 'squad-modules-for-divi' ),
				'default'            => '',
				'tab_slug'           => 'general',
				'toggle_slug'        => 'content',
			),
			'name'       => array(
				'label'       => esc_html__( 'Name', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The team member name.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'John Doe', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'content',
			),
			'position'   => array(
				'label'       => esc_html__( 'Position', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The role or job title.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Designer', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'content',
			),
			'bio'        => array(
				'label'       => esc_html__( 'Bio', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'A short biography.', 'squad-modules-for-divi' ),
				'type'        => 'textarea',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'content',
			),
			'alignment'  => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Content Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Align the card content.', 'squad-modules-for-divi' ),
					'default'          => 'center',
					'default_on_front' => 'center',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'layout',
				)
			),
			'use_social' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Social Links', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Display the social link icons.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'social',
				)
			),
		);

		foreach ( self::SOCIAL_NETWORKS as $key => $label ) {
			$fields[ "social_{$key}" ] = array(
				'label'       => $label,
				/* translators: %s: social network name. */
				'description' => sprintf( esc_html__( '%s profile URL.', 'squad-modules-for-divi' ), $label ),
				'type'        => 'text',
				'default'     => '',
				'show_if'     => array( 'use_social' => 'on' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'social',
			);
		}

		return $fields;
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
		$this->apply_alignment_css( $render_slug );

		$name     = (string) $this->prop( 'name', '' );
		$position = (string) $this->prop( 'position', '' );
		$bio      = (string) $this->prop( 'bio', '' );
		$image    = (string) $this->prop( 'image', '' );

		$image_html = '';
		if ( '' !== $image ) {
			$image_html = sprintf(
				'<div class="squad-team-member__image"><img src="%1$s" alt="%2$s" itemprop="image" loading="lazy" /></div>',
				esc_url( $image ),
				esc_attr( $name )
			);
		}

		$name_html     = '' !== $name ? sprintf( '<h3 class="squad-team-member__name" itemprop="name">%s</h3>', esc_html( $name ) ) : '';
		$position_html = '' !== $position ? sprintf( '<div class="squad-team-member__position" itemprop="jobTitle">%s</div>', esc_html( $position ) ) : '';
		$bio_html      = '' !== $bio ? sprintf( '<div class="squad-team-member__bio" itemprop="description">%s</div>', wpautop( wp_kses_post( $bio ) ) ) : '';

		return sprintf(
			'<div class="squad-team-member" itemscope itemtype="https://schema.org/Person">%1$s<div class="squad-team-member__body">%2$s%3$s%4$s%5$s</div></div>',
			$image_html,
			$name_html,
			$position_html,
			$bio_html,
			$this->render_social_links()
		);
	}

	/**
	 * Build the social-links list from the configured URLs.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	private function render_social_links(): string {
		if ( 'on' !== $this->prop( 'use_social', 'on' ) ) {
			return '';
		}

		$links = '';
		foreach ( self::SOCIAL_NETWORKS as $key => $label ) {
			$url = (string) $this->prop( "social_{$key}", '' );
			if ( '' === $url ) {
				continue;
			}

			$links .= sprintf(
				'<li class="squad-team-member__social-item"><a class="squad-team-member__social-link squad-team-member__social-link--%1$s" href="%2$s" target="_blank" rel="noopener noreferrer nofollow" aria-label="%3$s" itemprop="sameAs"><span class="screen-reader-text">%3$s</span></a></li>',
				esc_attr( $key ),
				esc_url( $url ),
				esc_attr( $label )
			);
		}

		if ( '' === $links ) {
			return '';
		}

		return sprintf( '<ul class="squad-team-member__social">%s</ul>', $links );
	}

	/**
	 * Emit alignment CSS for the card content.
	 *
	 * @since 4.2.0
	 *
	 * @param string $render_slug Slug of module that is used for rendering output.
	 *
	 * @return void
	 */
	public function apply_alignment_css( string $render_slug ): void {
		$alignment = (string) $this->prop( 'alignment', 'center' );
		$alignment = in_array( $alignment, array( 'left', 'center', 'right' ), true ) ? $alignment : 'center';

		$flex_map = array(
			'left'   => 'flex-start',
			'center' => 'center',
			'right'  => 'flex-end',
		);

		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%% .squad-team-member',
				'declaration' => sprintf( 'text-align: %s; align-items: %s;', $alignment, $flex_map[ $alignment ] ),
			)
		);
	}
}
