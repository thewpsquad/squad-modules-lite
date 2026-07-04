<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Countdown Timer Module (Divi 4 shortcode).
 *
 * Renders a `.squad-countdown` shell carrying the countdown config via data-*
 * attributes. The live ticking is run client-side by `countdown-timer.ts`.
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

use DiviSquad\Builder\Shared\Modules\Creative\Countdown_Timer\Countdown_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use function absint;
use function esc_html__;
use function max;
use function min;
use function wp_enqueue_script;

/**
 * Countdown Timer Module class.
 *
 * @since 4.3.0
 */
class Countdown_Timer extends Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.3.0
	 *
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Countdown Timer', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Countdown Timers', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'countdown-timer.svg' );

		$this->slug             = 'disq_countdown_timer';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'countdown' => esc_html__( 'Countdown', 'squad-modules-for-divi' ),
					'expiry'    => esc_html__( 'On Expiry', 'squad-modules-for-divi' ),
					'units'     => esc_html__( 'Units', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'number_text' => esc_html__( 'Number Text', 'squad-modules-for-divi' ),
					'label_text'  => esc_html__( 'Label Text', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'fonts'          => array(
				'number_text' => divi_squad()->d4_module_helper->add_font_field(
					'',
					array(
						'font_size'   => array( 'default' => '40px' ),
						'css'         => array( 'main' => "$this->main_css_element .squad-countdown__number" ),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'number_text',
					)
				),
				'label_text'  => divi_squad()->d4_module_helper->add_font_field(
					'',
					array(
						'font_size'   => array( 'default' => '14px' ),
						'css'         => array( 'main' => "$this->main_css_element .squad-countdown__label" ),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'label_text',
					)
				),
			),
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array( 'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ) ),
			'box_shadow'     => array( 'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ) ),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
			'link_options'   => false,
		);
	}

	/**
	 * Get fields for the module.
	 *
	 * @since 4.3.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			// Countdown.
			'mode'                => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Countdown Mode', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Fixed counts down to a specific date; Evergreen counts down a fixed duration per visitor.', 'squad-modules-for-divi' ),
					'options'     => array(
						'fixed'     => esc_html__( 'Fixed Date', 'squad-modules-for-divi' ),
						'evergreen' => esc_html__( 'Evergreen', 'squad-modules-for-divi' ),
					),
					'default'     => 'fixed',
					'tab_slug'    => 'general',
					'toggle_slug' => 'countdown',
				)
			),
			'target_date'         => array(
				'label'       => esc_html__( 'Target Date', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The date and time to count down to (format: Y-m-d H:i:s).', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'show_if'     => array( 'mode' => 'fixed' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'countdown',
			),
			'evergreen_duration'  => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Evergreen Duration', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'How long the evergreen countdown runs, in seconds.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '2592000', 'step' => '1' ),
					'default'        => '3600',
					'unitless'       => true,
					'show_if'        => array( 'mode' => 'evergreen' ),
					'tab_slug'       => 'general',
					'toggle_slug'    => 'countdown',
				)
			),
			'timezone'            => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Timezone', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Compute the countdown against the site timezone or the visitor browser timezone.', 'squad-modules-for-divi' ),
					'options'     => array(
						'site'    => esc_html__( 'Site Timezone', 'squad-modules-for-divi' ),
						'visitor' => esc_html__( 'Visitor Timezone', 'squad-modules-for-divi' ),
					),
					'default'     => 'site',
					'tab_slug'    => 'general',
					'toggle_slug' => 'countdown',
				)
			),
			'separator'           => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Unit Separator', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Character shown between countdown units.', 'squad-modules-for-divi' ),
					'options'     => array(
						'colon' => esc_html__( 'Colon (:)', 'squad-modules-for-divi' ),
						'slash' => esc_html__( 'Slash (/)', 'squad-modules-for-divi' ),
						'none'  => esc_html__( 'None', 'squad-modules-for-divi' ),
					),
					'default'     => 'colon',
					'tab_slug'    => 'general',
					'toggle_slug' => 'countdown',
				)
			),

			// On Expiry.
			'on_expiry'           => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'When Expired', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'What happens when the countdown reaches zero.', 'squad-modules-for-divi' ),
					'options'     => array(
						'message'  => esc_html__( 'Show Message', 'squad-modules-for-divi' ),
						'hide'     => esc_html__( 'Hide Timer', 'squad-modules-for-divi' ),
						'redirect' => esc_html__( 'Redirect', 'squad-modules-for-divi' ),
					),
					'default'     => 'message',
					'tab_slug'    => 'general',
					'toggle_slug' => 'expiry',
				)
			),
			'expiry_message'      => array(
				'label'       => esc_html__( 'Expiry Message', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Message shown when the countdown ends.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'show_if'     => array( 'on_expiry' => 'message' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'expiry',
			),
			'redirect_url'        => array(
				'label'       => esc_html__( 'Redirect URL', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'URL the visitor is sent to when the countdown ends.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'show_if'     => array( 'on_expiry' => 'redirect' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'expiry',
			),

			// Units.
			'show_days'           => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Days', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Display the days unit.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'units',
				)
			),
			'show_hours'          => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Hours', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Display the hours unit.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'units',
				)
			),
			'show_minutes'        => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Minutes', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Display the minutes unit.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'units',
				)
			),
			'show_seconds'        => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Seconds', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Display the seconds unit.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'units',
				)
			),
			'label_days'          => array(
				'label'       => esc_html__( 'Days Label', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Caption shown under the days number.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Days', 'squad-modules-for-divi' ),
				'show_if'     => array( 'show_days' => 'on' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'units',
			),
			'label_hours'         => array(
				'label'       => esc_html__( 'Hours Label', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Caption shown under the hours number.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Hours', 'squad-modules-for-divi' ),
				'show_if'     => array( 'show_hours' => 'on' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'units',
			),
			'label_minutes'       => array(
				'label'       => esc_html__( 'Minutes Label', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Caption shown under the minutes number.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Minutes', 'squad-modules-for-divi' ),
				'show_if'     => array( 'show_minutes' => 'on' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'units',
			),
			'label_seconds'       => array(
				'label'       => esc_html__( 'Seconds Label', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Caption shown under the seconds number.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Seconds', 'squad-modules-for-divi' ),
				'show_if'     => array( 'show_seconds' => 'on' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'units',
			),

			// Design — number/label color.
			'number_color'        => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Number Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the countdown numbers.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'number_text',
				)
			),
			'label_color'         => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Label Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the unit labels.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'label_text',
				)
			),
		);
	}

	/**
	 * Get CSS transition fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array<string, array<string, string>>
	 */
	public function get_transition_fields_css_props(): array {
		$fields = parent::get_transition_fields_css_props();

		$fields['number_color'] = array( 'color' => "$this->main_css_element .squad-countdown__number" );
		$fields['label_color']  = array( 'color' => "$this->main_css_element .squad-countdown__label" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'number_text', "$this->main_css_element .squad-countdown__number" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'label_text', "$this->main_css_element .squad-countdown__label" );

		return $fields;
	}

	/**
	 * Render module output.
	 *
	 * @since 4.3.0
	 *
	 * @param array<array-key, mixed> $attrs       List of attributes.
	 * @param string                  $content     Content being processed.
	 * @param string                  $render_slug Slug of module being rendered.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		wp_enqueue_script( 'squad-module-countdown-timer' );

		$config = $this->build_countdown_config();
		$units  = $this->build_countdown_units();

		return Countdown_Helper::build_shell( $config, $units );
	}

	/**
	 * Build the resolved data-* config for the shell.
	 *
	 * @since 4.3.0
	 *
	 * @return array<string, mixed>
	 */
	protected function build_countdown_config(): array {
		$mode      = Countdown_Helper::is_valid_mode( (string) $this->prop( 'mode', 'fixed' ) ) ? (string) $this->prop( 'mode', 'fixed' ) : 'fixed';
		$on_expiry = Countdown_Helper::sanitize_on_expiry( (string) $this->prop( 'on_expiry', 'message' ) );

		return array(
			'mode'      => $mode,
			'target'    => (string) $this->prop( 'target_date', '' ),
			'duration'  => (string) max( 0, min( 2592000, absint( $this->prop( 'evergreen_duration', '3600' ) ) ) ),
			'timezone'  => Countdown_Helper::sanitize_timezone( (string) $this->prop( 'timezone', 'site' ) ),
			'on_expiry' => $on_expiry,
			'message'   => (string) $this->prop( 'expiry_message', '' ),
			'redirect'  => (string) $this->prop( 'redirect_url', '' ),
			'separator' => Countdown_Helper::is_valid_separator( (string) $this->prop( 'separator', 'colon' ) ) ? (string) $this->prop( 'separator', 'colon' ) : 'colon',
		);
	}

	/**
	 * Build the per-unit config (enabled + label) for the shell.
	 *
	 * @since 4.3.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function build_countdown_units(): array {
		return array(
			'days'    => array(
				'enabled' => 'on' === $this->prop( 'show_days', 'on' ),
				'label'   => (string) $this->prop( 'label_days', 'Days' ),
			),
			'hours'   => array(
				'enabled' => 'on' === $this->prop( 'show_hours', 'on' ),
				'label'   => (string) $this->prop( 'label_hours', 'Hours' ),
			),
			'minutes' => array(
				'enabled' => 'on' === $this->prop( 'show_minutes', 'on' ),
				'label'   => (string) $this->prop( 'label_minutes', 'Minutes' ),
			),
			'seconds' => array(
				'enabled' => 'on' === $this->prop( 'show_seconds', 'on' ),
				'label'   => (string) $this->prop( 'label_seconds', 'Seconds' ),
			),
		);
	}
}
