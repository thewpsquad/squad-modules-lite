<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Author Box Module Class which extend the Divi Builder Module Class.
 *
 * This class provides author box functionalities for displaying post author
 * information in the visual builder.
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Version4\Abstracts\Module;
use WP_User;
use function absint;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function get_author_posts_url;
use function get_avatar;
use function get_post_field;
use function get_the_ID;
use function get_user_by;
use function get_userdata;
use function in_array;
use function wp_kses_post;

/**
 * Author Box Module Class.
 *
 * @since 4.0.0
 */
class Author_Box extends Module {

	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Author Box', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Author Boxes', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'author-box.svg' );

		$this->slug             = 'disq_author_box';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'admin_label';
		$this->child_title_fallback_var = 'admin_label';

		// Connect with utils.
		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'main_content'         => esc_html__( 'Content', 'squad-modules-for-divi' ),
					'avatar_element'       => esc_html__( 'Avatar', 'squad-modules-for-divi' ),
					'name_element'         => esc_html__( 'Author Name', 'squad-modules-for-divi' ),
					'bio_element'          => esc_html__( 'Author Bio', 'squad-modules-for-divi' ),
					'posts_link_element'   => esc_html__( 'Posts Link', 'squad-modules-for-divi' ),
					'website_link_element' => esc_html__( 'Website Link', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'avatar'       => esc_html__( 'Avatar', 'squad-modules-for-divi' ),
					'author_name'  => esc_html__( 'Author Name', 'squad-modules-for-divi' ),
					'author_bio'   => esc_html__( 'Author Bio', 'squad-modules-for-divi' ),
					'posts_link'   => esc_html__( 'Posts Link', 'squad-modules-for-divi' ),
					'website_link' => esc_html__( 'Website Link', 'squad-modules-for-divi' ),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'fonts'          => array(
				'author_name_text'  => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Name', 'squad-modules-for-divi' ),
					array(
						'css'         => array(
							'main'  => "$this->main_css_element .squad-author-box__name",
							'hover' => "$this->main_css_element .squad-author-box__name:hover",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'author_name',
					)
				),
				'author_bio_text'   => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Bio', 'squad-modules-for-divi' ),
					array(
						'css'         => array(
							'main'  => "$this->main_css_element .squad-author-box__bio",
							'hover' => "$this->main_css_element .squad-author-box__bio:hover",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'author_bio',
					)
				),
				'posts_link_text'   => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Posts Link', 'squad-modules-for-divi' ),
					array(
						'css'         => array(
							'main'  => "$this->main_css_element .squad-author-box__posts-link",
							'hover' => "$this->main_css_element .squad-author-box__posts-link:hover",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'posts_link',
					)
				),
				'website_link_text' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Website Link', 'squad-modules-for-divi' ),
					array(
						'css'         => array(
							'main'  => "$this->main_css_element .squad-author-box__website-link",
							'hover' => "$this->main_css_element .squad-author-box__website-link:hover",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'website_link',
					)
				),
			),
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default'      => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'avatar'       => array(
					'label_prefix' => esc_html__( 'Avatar', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element .squad-author-box__avatar img",
							'border_radii_hover'  => "$this->main_css_element .squad-author-box__avatar img:hover",
							'border_styles'       => "$this->main_css_element .squad-author-box__avatar img",
							'border_styles_hover' => "$this->main_css_element .squad-author-box__avatar img:hover",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'avatar',
				),
				'posts_link'   => array(
					'label_prefix' => esc_html__( 'Posts Link', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element .squad-author-box__posts-link",
							'border_radii_hover'  => "$this->main_css_element .squad-author-box__posts-link:hover",
							'border_styles'       => "$this->main_css_element .squad-author-box__posts-link",
							'border_styles_hover' => "$this->main_css_element .squad-author-box__posts-link:hover",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'posts_link',
				),
				'website_link' => array(
					'label_prefix' => esc_html__( 'Website Link', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element .squad-author-box__website-link",
							'border_radii_hover'  => "$this->main_css_element .squad-author-box__website-link:hover",
							'border_styles'       => "$this->main_css_element .squad-author-box__website-link",
							'border_styles_hover' => "$this->main_css_element .squad-author-box__website-link:hover",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'website_link',
				),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'avatar'  => array(
					'label'           => esc_html__( 'Avatar Box Shadow', 'squad-modules-for-divi' ),
					'option_category' => 'layout',
					'css'             => array(
						'main'  => "$this->main_css_element .squad-author-box__avatar img",
						'hover' => "$this->main_css_element .squad-author-box__avatar img:hover",
					),
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'avatar',
				),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);

		// Declare custom CSS fields.
		$this->custom_css_fields = array(
			'author_box'   => array(
				'label'    => esc_html__( 'Author Box', 'squad-modules-for-divi' ),
				'selector' => '.squad-author-box',
			),
			'avatar'       => array(
				'label'    => esc_html__( 'Avatar', 'squad-modules-for-divi' ),
				'selector' => '.squad-author-box__avatar img',
			),
			'author_name'  => array(
				'label'    => esc_html__( 'Author Name', 'squad-modules-for-divi' ),
				'selector' => '.squad-author-box__name',
			),
			'author_bio'   => array(
				'label'    => esc_html__( 'Author Bio', 'squad-modules-for-divi' ),
				'selector' => '.squad-author-box__bio',
			),
			'posts_link'   => array(
				'label'    => esc_html__( 'Posts Link', 'squad-modules-for-divi' ),
				'selector' => '.squad-author-box__posts-link',
			),
			'website_link' => array(
				'label'    => esc_html__( 'Website Link', 'squad-modules-for-divi' ),
				'selector' => '.squad-author-box__website-link',
			),
		);
	}

	/**
	 * Declare general fields for the module.
	 *
	 * @since 4.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		$content_fields = array(
			'layout'         => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Layout', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose the author box layout.', 'squad-modules-for-divi' ),
					'options'          => array(
						'horizontal' => array( 'value' => 'horizontal', 'label' => esc_html__( 'Horizontal', 'squad-modules-for-divi' ) ),
						'vertical'   => array( 'value' => 'vertical', 'label' => esc_html__( 'Vertical', 'squad-modules-for-divi' ) ),
					),
					'default_on_front' => 'horizontal',
					'default'          => 'horizontal',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
				)
			),
			'author_user_id' => array(
				'label'           => esc_html__( 'Author User ID', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Enter a user ID to pin a specific author. Leave empty to use the current post author.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'dynamic_content' => 'text',
			),
		);

		$avatar_fields = array(
			'show_avatar' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Avatar', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Toggle the author avatar display.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'affects'          => array( 'avatar_size' ),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'avatar_element',
				)
			),
			'avatar_size' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Avatar Size', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Set the avatar image size in pixels.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min_limit' => '20',
						'min'       => '20',
						'max_limit' => '300',
						'max'       => '300',
						'step'      => '1',
					),
					'default'         => '96',
					'default_unit'    => 'px',
					'unitless'        => false,
					'hover'           => false,
					'mobile_options'  => false,
					'responsive'      => false,
					'depends_show_if' => 'on',
					'depends_on'      => array( 'show_avatar' ),
					'tab_slug'        => 'general',
					'toggle_slug'     => 'avatar_element',
				)
			),
		);

		$name_fields = array(
			'show_name' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Name', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Toggle the author name display.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'affects'          => array( 'name_tag' ),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'name_element',
				)
			),
			'name_tag'  => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Name HTML Tag', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose the HTML tag for the author name.', 'squad-modules-for-divi' ),
					'options'          => divi_squad()->d4_module_helper->get_html_tag_elements(),
					'default_on_front' => 'h4',
					'default'          => 'h4',
					'depends_show_if'  => 'on',
					'depends_on'       => array( 'show_name' ),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'name_element',
				)
			),
		);

		$bio_fields = array(
			'show_bio' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Bio', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Toggle the author bio display.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'bio_element',
				)
			),
		);

		$posts_link_fields = array(
			'show_posts_link'   => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Posts Link', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Toggle the "View all posts" link.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'affects'          => array( 'posts_link_text', 'posts_link_target' ),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'posts_link_element',
				)
			),
			'posts_link_text'   => array(
				'label'           => esc_html__( 'Posts Link Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Text for the author posts link.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => esc_html__( 'View all posts', 'squad-modules-for-divi' ),
				'depends_show_if' => 'on',
				'depends_on'      => array( 'show_posts_link' ),
				'tab_slug'        => 'general',
				'toggle_slug'     => 'posts_link_element',
				'dynamic_content' => 'text',
			),
			'posts_link_target' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Posts Link Target', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Choose whether the link opens in the same or a new tab.', 'squad-modules-for-divi' ),
					'options'         => array(
						'_blank' => array( 'value' => '_blank', 'label' => esc_html__( 'New Tab', 'squad-modules-for-divi' ) ),
						'_self'  => array( 'value' => '_self', 'label' => esc_html__( 'Same Tab', 'squad-modules-for-divi' ) ),
					),
					'default'         => '_blank',
					'depends_show_if' => 'on',
					'depends_on'      => array( 'show_posts_link' ),
					'tab_slug'        => 'general',
					'toggle_slug'     => 'posts_link_element',
				)
			),
		);

		$website_link_fields = array(
			'show_website_link'   => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Website Link', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Toggle the author website link.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'affects'          => array( 'website_link_text', 'website_link_target' ),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'website_link_element',
				)
			),
			'website_link_text'   => array(
				'label'           => esc_html__( 'Website Link Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Text for the author website link.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => esc_html__( 'Visit website', 'squad-modules-for-divi' ),
				'depends_show_if' => 'on',
				'depends_on'      => array( 'show_website_link' ),
				'tab_slug'        => 'general',
				'toggle_slug'     => 'website_link_element',
				'dynamic_content' => 'text',
			),
			'website_link_target' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Website Link Target', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Choose whether the link opens in the same or a new tab.', 'squad-modules-for-divi' ),
					'options'         => array(
						'_blank' => array( 'value' => '_blank', 'label' => esc_html__( 'New Tab', 'squad-modules-for-divi' ) ),
						'_self'  => array( 'value' => '_self', 'label' => esc_html__( 'Same Tab', 'squad-modules-for-divi' ) ),
					),
					'default'         => '_blank',
					'depends_show_if' => 'on',
					'depends_on'      => array( 'show_website_link' ),
					'tab_slug'        => 'general',
					'toggle_slug'     => 'website_link_element',
				)
			),
		);

		return array_merge(
			$content_fields,
			$avatar_fields,
			$name_fields,
			$bio_fields,
			$posts_link_fields,
			$website_link_fields
		);
	}

	/**
	 * Renders the module output.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs       List of attributes.
	 * @param string               $content     Content being processed.
	 * @param string               $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		$user = $this->squad_resolve_author();
		if ( ! $user instanceof WP_User ) {
			return '';
		}

		$layout       = $this->prop( 'layout', 'horizontal' );
		$avatar_html  = $this->squad_render_avatar( $user );
		$content_html = $this->squad_render_content( $user );

		return sprintf(
			'<div class="squad-author-box squad-author-box--%1$s et_pb_with_background">%2$s%3$s</div>',
			esc_attr( $layout ),
			$avatar_html,
			$content_html
		);
	}

	/**
	 * Resolve the author WP_User from module props or current post context.
	 *
	 * @since 4.0.0
	 *
	 * @return WP_User|false
	 */
	protected function squad_resolve_author() {
		$user_id_override = trim( (string) $this->prop( 'author_user_id', '' ) );
		if ( '' !== $user_id_override ) {
			return get_user_by( 'id', absint( $user_id_override ) );
		}

		$post_id = get_the_ID();
		if ( false === $post_id ) {
			return false;
		}

		return get_userdata( (int) get_post_field( 'post_author', $post_id ) );
	}

	/**
	 * Render the avatar block.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_User $user The author user object.
	 *
	 * @return string
	 */
	protected function squad_render_avatar( WP_User $user ): string {
		if ( 'on' !== $this->prop( 'show_avatar', 'on' ) ) {
			return '';
		}

		$size   = absint( $this->prop( 'avatar_size', '96' ) );
		$avatar = get_avatar( $user->ID, $size, '', esc_attr( $user->display_name ) );
		if ( false === $avatar ) {
			return '';
		}

		return sprintf(
			'<div class="squad-author-box__avatar">%s</div>',
			$avatar
		);
	}

	/**
	 * Render the content block (name + bio + links).
	 *
	 * @since 4.0.0
	 *
	 * @param WP_User $user The author user object.
	 *
	 * @return string
	 */
	protected function squad_render_content( WP_User $user ): string {
		$name_html  = $this->squad_render_name( $user );
		$bio_html   = $this->squad_render_bio( $user );
		$links_html = $this->squad_render_links( $user );

		return sprintf(
			'<div class="squad-author-box__content">%s%s%s</div>',
			$name_html,
			$bio_html,
			$links_html
		);
	}

	/**
	 * Render the author name element.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_User $user The author user object.
	 *
	 * @return string
	 */
	protected function squad_render_name( WP_User $user ): string {
		if ( 'on' !== $this->prop( 'show_name', 'on' ) ) {
			return '';
		}

		$allowed_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div' );
		$tag          = $this->prop( 'name_tag', 'h4' );
		$tag          = in_array( $tag, $allowed_tags, true ) ? $tag : 'h4';

		return sprintf(
			'<%1$s class="squad-author-box__name">%2$s</%1$s>',
			$tag,
			esc_html( $user->display_name )
		);
	}

	/**
	 * Render the author bio element.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_User $user The author user object.
	 *
	 * @return string
	 */
	protected function squad_render_bio( WP_User $user ): string {
		if ( 'on' !== $this->prop( 'show_bio', 'on' ) ) {
			return '';
		}
		if ( '' === $user->description ) {
			return '';
		}

		return sprintf(
			'<div class="squad-author-box__bio">%s</div>',
			wp_kses_post( $user->description )
		);
	}

	/**
	 * Render the links block (posts link + website link).
	 *
	 * @since 4.0.0
	 *
	 * @param WP_User $user The author user object.
	 *
	 * @return string
	 */
	protected function squad_render_links( WP_User $user ): string {
		$posts_link_html   = $this->squad_render_posts_link( $user );
		$website_link_html = $this->squad_render_website_link( $user );

		if ( '' === $posts_link_html && '' === $website_link_html ) {
			return '';
		}

		return sprintf(
			'<div class="squad-author-box__links">%s%s</div>',
			$posts_link_html,
			$website_link_html
		);
	}

	/**
	 * Render the "View all posts" link.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_User $user The author user object.
	 *
	 * @return string
	 */
	protected function squad_render_posts_link( WP_User $user ): string {
		if ( 'on' !== $this->prop( 'show_posts_link', 'on' ) ) {
			return '';
		}

		$text   = $this->prop( 'posts_link_text', __( 'View all posts', 'squad-modules-for-divi' ) );
		$target = $this->prop( 'posts_link_target', '_blank' );

		return sprintf(
			'<a class="squad-author-box__posts-link" href="%s" target="%s">%s</a>',
			esc_url( get_author_posts_url( $user->ID ) ),
			esc_attr( $target ),
			esc_html( $text )
		);
	}

	/**
	 * Render the author website link.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_User $user The author user object.
	 *
	 * @return string
	 */
	protected function squad_render_website_link( WP_User $user ): string {
		if ( 'on' !== $this->prop( 'show_website_link', 'on' ) ) {
			return '';
		}
		if ( '' === $user->user_url ) {
			return '';
		}

		$text   = $this->prop( 'website_link_text', __( 'Visit website', 'squad-modules-for-divi' ) );
		$target = $this->prop( 'website_link_target', '_blank' );

		return sprintf(
			'<a class="squad-author-box__website-link" href="%s" target="%s" rel="noopener noreferrer">%s</a>',
			esc_url( $user->user_url ),
			esc_attr( $target ),
			esc_html( $text )
		);
	}
}
