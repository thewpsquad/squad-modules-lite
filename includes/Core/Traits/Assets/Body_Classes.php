<?php
/**
 * Body Classes Trait
 *
 * Handles adding and managing CSS classes on the body tag.
 *
 * @since   3.3.0
 * @package DiviSquad
 */

namespace DiviSquad\Core\Traits\Assets;

/**
 * Body Classes Management Trait
 *
 * @since 3.3.0
 */
trait Body_Classes {

	/**
	 * List of body classes
	 *
	 * @var array<string>
	 */
	private array $body_classes = array();

	/**
	 * Add a body class
	 *
	 * @param string $class Class name to add.
	 *
	 * @return bool Whether the class was added
	 */
	public function add_body_class( string $class ): bool {
		$class = sanitize_html_class( $class );

		if ( empty( $class ) || in_array( $class, $this->body_classes, true ) ) {
			return false;
		}

		$this->body_classes[] = $class;

		return true;
	}

	/**
	 * Remove a body class
	 *
	 * @param string $class Class name to remove.
	 *
	 * @return bool Whether the class was removed
	 */
	public function remove_body_class( string $class ): bool {
		$index = array_search( $class, $this->body_classes, true );

		if ( false === $index ) {
			return false;
		}

		array_splice( $this->body_classes, $index, 1 );

		return true;
	}

	/**
	 * Filter body classes for frontend
	 *
	 * @param array<string> $classes Existing body classes.
	 *
	 * @return array<string> Modified body classes
	 */
	public function filter_body_classes( array $classes ): array {
		return array_merge( $classes, $this->get_filtered_body_classes() );
	}

	/**
	 * Filter body classes for admin
	 *
	 * @param string $classes Space-separated list of classes.
	 *
	 * @return string Modified space-separated list of classes
	 */
	public function filter_admin_body_classes( string $classes ): string {
		$all_classes = array_merge(
			empty( $classes ) ? array() : explode( ' ', trim( $classes ) ),
			$this->get_filtered_body_classes()
		);

		return implode( ' ', array_unique( array_filter( $all_classes ) ) );
	}

	/**
	 * Get filtered body classes
	 *
	 * @return array<string>
	 */
	protected function get_filtered_body_classes(): array {
		$prefix = $this->get_body_class_prefix();

		/**
		 * Filter body classes before output
		 *
		 * @param array<string> $classes List of body classes
		 */
		$classes = apply_filters( 'divi_squad_body_classes', $this->body_classes );

		// Add prefix to classes.
		$classes = array_map( fn( $class ): string => $prefix . $class, $classes );

		return (array) apply_filters( 'divi_squad_body_classes_filtered', $classes );
	}

	/**
	 * Get prefix for body classes
	 *
	 * @return string
	 */
	protected function get_body_class_prefix(): string {
		/**
		 * Filter prefix for body classes
		 *
		 * @param string $prefix Class prefix (default: 'divi-squad-')
		 */
		return apply_filters( 'divi_squad_body_class_prefix', 'divi-squad-' );
	}
}
