<?php // phpcs:ignore WordPress.Files.FileName

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
	 * @param string $body_class Class name to add.
	 *
	 * @return bool Whether the class was added
	 */
	public function add_body_class( string $body_class ): bool {
		/**
		 * Filters the class before it's sanitized and added.
		 *
		 * @since 3.4.0
		 *
		 * @param string $body_class The class name to be added.
		 */
		$body_class = (string) apply_filters( 'divi_squad_pre_add_body_class', $body_class );

		$body_class = sanitize_html_class( $body_class );

		/**
		 * Filters the class after it's sanitized but before it's added.
		 *
		 * @since 3.4.0
		 *
		 * @param string $body_class The sanitized class name.
		 */
		$body_class = (string) apply_filters( 'divi_squad_sanitized_body_class', $body_class );

		if ( '' === $body_class || in_array( $body_class, $this->body_classes, true ) ) {
			/**
			 * Fires when a body class is rejected (empty or duplicate).
			 *
			 * @since 3.4.0
			 *
			 * @param string        $body_class   The class that was rejected.
			 * @param array<string> $body_classes Current body classes.
			 */
			do_action( 'divi_squad_body_class_rejected', $body_class, $this->body_classes );

			return false;
		}

		/**
		 * Filters whether the class should be added.
		 *
		 * @since 3.4.0
		 *
		 * @param bool          $should_add   Whether the class should be added.
		 * @param string        $body_class   The class name.
		 * @param array<string> $body_classes Current body classes.
		 */
		$should_add = apply_filters( 'divi_squad_should_add_body_class', true, $body_class, $this->body_classes );

		if ( ! $should_add ) {
			return false;
		}

		/**
		 * Fires before a body class is added.
		 *
		 * @since 3.4.0
		 *
		 * @param string        $body_class   The class being added.
		 * @param array<string> $body_classes Current body classes before addition.
		 */
		do_action( 'divi_squad_before_body_class_added', $body_class, $this->body_classes );

		$this->body_classes[] = $body_class;

		/**
		 * Fires after a body class is added.
		 *
		 * @since 3.4.0
		 *
		 * @param string        $body_class   The class that was added.
		 * @param array<string> $body_classes Current body classes after addition.
		 */
		do_action( 'divi_squad_body_class_added', $body_class, $this->body_classes );

		return true;
	}

	/**
	 * Remove a body class
	 *
	 * @param string $body_class Class name to remove.
	 *
	 * @return bool Whether the class was removed
	 */
	public function remove_body_class( string $body_class ): bool {
		/**
		 * Filters the class before attempting to remove it.
		 *
		 * @since 3.4.0
		 *
		 * @param string $body_class The class name to be removed.
		 */
		$body_class = (string) apply_filters( 'divi_squad_pre_remove_body_class', $body_class );

		$index = array_search( $body_class, $this->body_classes, true );

		if ( false === $index ) {
			/**
			 * Fires when attempting to remove a body class that doesn't exist.
			 *
			 * @since 3.4.0
			 *
			 * @param string        $body_class   The class that was not found.
			 * @param array<string> $body_classes Current body classes.
			 */
			do_action( 'divi_squad_body_class_not_found', $body_class, $this->body_classes );

			return false;
		}

		/**
		 * Filters whether the class should be removed.
		 *
		 * @since 3.4.0
		 *
		 * @param bool          $should_remove Whether the class should be removed.
		 * @param string        $body_class    The class name.
		 * @param int           $index         The index of the class in the array.
		 * @param array<string> $body_classes  Current body classes.
		 */
		$should_remove = apply_filters( 'divi_squad_should_remove_body_class', true, $body_class, $index, $this->body_classes );

		if ( ! $should_remove ) {
			return false;
		}

		/**
		 * Fires before a body class is removed.
		 *
		 * @since 3.4.0
		 *
		 * @param string        $body_class   The class being removed.
		 * @param int           $index        The index of the class in the array.
		 * @param array<string> $body_classes Current body classes before removal.
		 */
		do_action( 'divi_squad_before_body_class_removed', $body_class, $index, $this->body_classes );

		array_splice( $this->body_classes, (int) $index, 1 );

		/**
		 * Fires after a body class is removed.
		 *
		 * @since 3.4.0
		 *
		 * @param string $body_class   The class that was removed.
		 * @param array  $body_classes Current body classes after removal.
		 */
		do_action( 'divi_squad_body_class_removed', $body_class, $this->body_classes );

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
		/**
		 * Filters the existing body classes before merging with custom classes.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $classes The existing body classes.
		 */
		$classes = (array) apply_filters( 'divi_squad_existing_body_classes', $classes );

		$filtered_classes = $this->get_filtered_body_classes();

		/**
		 * Fires before merging the custom classes with existing classes.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $filtered_classes Custom body classes.
		 * @param array<string> $classes          Existing body classes.
		 */
		do_action( 'divi_squad_before_merge_body_classes', $filtered_classes, $classes );

		$merged_classes = array_merge( $classes, $filtered_classes );

		/**
		 * Filters the merged body classes.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $merged_classes   The merged classes.
		 * @param array<string> $classes          Original existing classes.
		 * @param array<string> $filtered_classes Custom body classes.
		 */
		return apply_filters( 'divi_squad_merged_body_classes', $merged_classes, $classes, $filtered_classes );
	}

	/**
	 * Filter body classes for admin
	 *
	 * @param string $classes Space-separated list of classes.
	 *
	 * @return string Modified space-separated list of classes
	 */
	public function filter_admin_body_classes( string $classes ): string {
		/**
		 * Filters the existing admin body classes string before processing.
		 *
		 * @since 3.4.0
		 *
		 * @param string $classes Space-separated list of admin body classes.
		 */
		$classes = (string) apply_filters( 'divi_squad_existing_admin_body_classes', $classes );

		$existing_classes = '' === $classes ? array() : explode( ' ', trim( $classes ) );

		/**
		 * Filters the existing admin body classes array after splitting.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $existing_classes The existing admin body classes as array.
		 * @param string        $classes          Original space-separated list.
		 */
		$existing_classes = apply_filters( 'divi_squad_existing_admin_body_classes_array', $existing_classes, $classes );

		$filtered_classes = $this->get_filtered_body_classes();

		/**
		 * Fires before merging the custom classes with existing admin classes.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $filtered_classes Custom body classes.
		 * @param array<string> $existing_classes Existing admin body classes.
		 */
		do_action( 'divi_squad_before_merge_admin_body_classes', $filtered_classes, $existing_classes );

		$all_classes    = array_merge( $existing_classes, $filtered_classes );
		$unique_classes = array_unique( array_filter( $all_classes, static fn( $body_class ) => (bool) $body_class ) );

		/**
		 * Filters the merged and unique admin body classes before imploding.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $unique_classes   The unique, filtered classes.
		 * @param array<string> $all_classes      All merged classes before filtering.
		 * @param array<string> $existing_classes Original existing classes.
		 * @param array<string> $filtered_classes Custom body classes.
		 */
		$unique_classes = apply_filters( 'divi_squad_unique_admin_body_classes', $unique_classes, $all_classes, $existing_classes, $filtered_classes );

		$result = implode( ' ', $unique_classes );

		/**
		 * Filters the final admin body classes string.
		 *
		 * @since 3.4.0
		 *
		 * @param string $result         The space-separated list of classes.
		 * @param array  $unique_classes The array of unique classes.
		 */
		return apply_filters( 'divi_squad_admin_body_classes_result', $result, $unique_classes );
	}

	/**
	 * Get filtered body classes
	 *
	 * @return array<string>
	 */
	protected function get_filtered_body_classes(): array {
		/**
		 * Filters the raw body classes before prefixing.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $body_classes The raw body classes before processing.
		 */
		$classes = apply_filters( 'divi_squad_raw_body_classes', $this->body_classes );

		/**
		 * Filter body classes before output
		 *
		 * @param array<string> $classes List of body classes
		 */
		$classes = apply_filters( 'divi_squad_body_classes', $classes );

		$prefix = $this->get_body_class_prefix();

		/**
		 * Fires before prefixing body classes.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $classes The body classes to be prefixed.
		 * @param string        $prefix  The prefix to be applied.
		 */
		do_action( 'divi_squad_before_prefix_body_classes', $classes, $prefix );

		// Add prefix to classes.
		$prefixed_classes = array_map(
		/**
		 * Filters each individual class with its prefix.
		 *
		 * @since 3.4.0
		 *
		 * @param string $prefixed_class The prefixed class.
		 * @param string $class          The original class.
		 * @param string $prefix         The prefix used.
		 */
			static function ( string $class ) use ( $prefix ): string {
				$prefixed_class = $prefix . $class;

				/**
				 * Filters the prefixed class.
				 *
				 * @since 3.4.0
				 *
				 * @param string $prefixed_class The prefixed class.
				 * @param string $class          The original class.
				 * @param string $prefix         The prefix used.
				 */
				return apply_filters( 'divi_squad_prefixed_body_class', $prefixed_class, $class, $prefix );
			},
			$classes
		);

		/**
		 * Fires after prefixing body classes.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $prefixed_classes The prefixed body classes.
		 * @param array<string> $classes          The original body classes.
		 * @param string        $prefix           The prefix applied.
		 */
		do_action( 'divi_squad_after_prefix_body_classes', $prefixed_classes, $classes, $prefix );

		/**
		 * Filters the prefixed body classes.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $prefixed_classes The prefixed body classes.
		 * @param array<string> $classes          The original body classes.
		 * @param string        $prefix           The prefix applied.
		 */
		$prefixed_classes = apply_filters( 'divi_squad_prefixed_body_classes', $prefixed_classes, $classes, $prefix );

		return (array) apply_filters( 'divi_squad_body_classes_filtered', $prefixed_classes );
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
		$default_prefix = 'divi-squad-';

		/**
		 * Filters the default body class prefix before it's filtered by the main hook.
		 *
		 * @since 3.4.0
		 *
		 * @param string $default_prefix The default class prefix.
		 */
		$default_prefix = apply_filters( 'divi_squad_default_body_class_prefix', $default_prefix );

		return apply_filters( 'divi_squad_body_class_prefix', $default_prefix );
	}

	/**
	 * Set multiple body classes at once
	 *
	 * @param array<string> $classes Array of class names to add.
	 *
	 * @return int Number of classes successfully added
	 */
	public function set_body_classes( array $classes ): int {
		/**
		 * Filters the array of classes before setting them.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $classes The classes to be set.
		 */
		$classes = (array) apply_filters( 'divi_squad_pre_set_body_classes', $classes );

		/**
		 * Fires before setting multiple body classes.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $classes      The classes to be set.
		 * @param array<string> $body_classes Current body classes.
		 */
		do_action( 'divi_squad_before_set_body_classes', $classes, $this->body_classes );

		$added = 0;
		foreach ( $classes as $class ) {
			if ( $this->add_body_class( $class ) ) {
				$added ++;
			}
		}

		/**
		 * Fires after setting multiple body classes.
		 *
		 * @since 3.4.0
		 *
		 * @param int           $added        Number of classes added.
		 * @param array<string> $classes      The classes that were set.
		 * @param array<string> $body_classes Current body classes after addition.
		 */
		do_action( 'divi_squad_after_set_body_classes', $added, $classes, $this->body_classes );

		return $added;
	}

	/**
	 * Check if a body class exists
	 *
	 * @param string $class Class name to check.
	 *
	 * @return bool Whether the class exists
	 */
	public function has_body_class( string $class ): bool {
		/**
		 * Filters the class before checking if it exists.
		 *
		 * @since 3.4.0
		 *
		 * @param string $class The class name to check.
		 */
		$class = (string) apply_filters( 'divi_squad_pre_check_body_class', $class );

		$exists = in_array( $class, $this->body_classes, true );

		/**
		 * Filters whether a body class exists.
		 *
		 * @since 3.4.0
		 *
		 * @param bool   $exists       Whether the class exists.
		 * @param string $class        The class being checked.
		 * @param array  $body_classes Current body classes.
		 */
		return apply_filters( 'divi_squad_has_body_class', $exists, $class, $this->body_classes );
	}

	/**
	 * Get all body classes
	 *
	 * @return array<string> List of body classes
	 */
	public function get_body_classes(): array {
		/**
		 * Filters the raw body classes when retrieved directly.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $body_classes The current body classes.
		 */
		return apply_filters( 'divi_squad_get_body_classes', $this->body_classes );
	}

	/**
	 * Clear all body classes
	 *
	 * @return int Number of classes that were cleared
	 */
	public function clear_body_classes(): int {
		$count = count( $this->body_classes );

		/**
		 * Filters whether body classes should be cleared.
		 *
		 * @since 3.4.0
		 *
		 * @param bool          $should_clear Whether to clear the classes.
		 * @param array<string> $body_classes Current body classes.
		 * @param int           $count        Number of classes to be cleared.
		 */
		$should_clear = apply_filters( 'divi_squad_should_clear_body_classes', true, $this->body_classes, $count );

		if ( ! $should_clear ) {
			return 0;
		}

		/**
		 * Fires before clearing all body classes.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $body_classes Classes to be cleared.
		 * @param int           $count        Number of classes to be cleared.
		 */
		do_action( 'divi_squad_before_clear_body_classes', $this->body_classes, $count );

		$this->body_classes = array();

		/**
		 * Fires after clearing all body classes.
		 *
		 * @since 3.4.0
		 *
		 * @param int $count Number of classes that were cleared.
		 */
		do_action( 'divi_squad_after_clear_body_classes', $count );

		return $count;
	}
}
