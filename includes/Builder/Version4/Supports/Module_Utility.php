<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Module Utilities Provider Class
 *
 * @since   1.5.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Version4\Supports;

use DiviSquad\Builder\Version4\Abstracts\Module;
use DiviSquad\Builder\Version4\Supports\Module_Utilities\Breadcrumbs;
use DiviSquad\Builder\Version4\Supports\Module_Utilities\Divider;
use DiviSquad\Builder\Version4\Supports\Module_Utilities\Fields\CSS_Generations as Field_CSS_Generations;
use DiviSquad\Builder\Version4\Supports\Module_Utilities\Fields\Definitions as Field_Definitions;
use DiviSquad\Builder\Version4\Supports\Module_Utilities\Mask_Shape;

/**
 * Module Utilities Provider Class
 *
 * This class serves as a container for utility instances specific to a module.
 * It allows sharing the Module_Helper singleton while keeping module-specific utilities.
 *
 * @since   1.5.0
 * @package DiviSquad
 *
 * @property Divider               $divider               The divider utility
 * @property Breadcrumbs           $breadcrumbs           The breadcrumbs utility
 * @property Mask_Shape            $mask_shape            The mask shape utility
 * @property Field_Definitions     $field_definitions     The field definitions utility
 * @property Field_CSS_Generations $field_css_generations The field CSS generations utility
 */
class Module_Utility {
	/**
	 * The module instance
	 *
	 * @var Module
	 */
	private Module $module;

	/**
	 * Utility instances cache
	 *
	 * @var array<string, object>
	 */
	private array $utilities = array();

	/**
	 * Utility class mapping
	 *
	 * @var array<string, string>
	 */
	private array $utility_classes = array(
		'divider'               => Divider::class,
		'breadcrumbs'           => Breadcrumbs::class,
		'mask_shape'            => Mask_Shape::class,
		'field_definitions'     => Field_Definitions::class,
		'field_css_generations' => Field_CSS_Generations::class,
	);

	/**
	 * Constructor
	 *
	 * @param Module $module The module instance.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	/**
	 * Magic getter to access utility instances
	 *
	 * @param string $name The utility name.
	 *
	 * @return mixed The utility instance
	 */
	public function __get( string $name ) {
		if ( ! isset( $this->utilities[ $name ] ) && isset( $this->utility_classes[ $name ] ) ) {
			$class                    = $this->utility_classes[ $name ];
			$this->utilities[ $name ] = new $class( $this->module );
		}

		return $this->utilities[ $name ] ?? null;
	}

	/**
	 * Check if a utility exists
	 *
	 * @param string $name The utility name.
	 *
	 * @return bool Whether the utility exists
	 */
	public function __isset( string $name ): bool {
		return isset( $this->utilities[ $name ] ) || isset( $this->utility_classes[ $name ] );
	}
}
