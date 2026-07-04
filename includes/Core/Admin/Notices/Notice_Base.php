<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Base Notice Class
 *
 * This abstract class implements common functionality for all notice classes.
 *
 * @since   3.3.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin\Notices;

use Throwable;

/**
 * Base Notice Class
 *
 * @since   3.3.3
 * @package DiviSquad
 */
abstract class Notice_Base implements Notice_Interface {

	/**
	 * The notice ID.
	 *
	 * @since 3.3.3
	 * @var string
	 */
	protected string $notice_id = '';

	/**
	 * Notice scopes.
	 *
	 * @since 3.3.3
	 * @var array<string>
	 */
	protected array $scopes = array( 'global' );

	/**
	 * Get the notice ID.
	 *
	 * @since 3.3.3
	 *
	 * @return string The notice ID.
	 */
	public function get_notice_id(): string {
		/**
		 * Filter the notice ID.
		 *
		 * @since 3.3.3
		 *
		 * @param string      $notice_id The notice ID.
		 * @param Notice_Base $notice    The notice instance.
		 */
		return apply_filters( 'divi_squad_notice_id', $this->notice_id, $this );
	}

	/**
	 * Get the notice body classes.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string> The notice body classes.
	 */
	public function get_body_classes(): array {
		try {
			$classes = array();

			/**
			 * Filter the notice body classes.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string> $classes The body classes.
			 * @param Notice_Base   $notice  The notice instance.
			 */
			return apply_filters( 'divi_squad_notice_body_classes', $classes, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get notice body classes' );

			return array();
		}
	}

	/**
	 * Get the scopes where this notice should be displayed.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string> The notice scopes.
	 */
	public function get_scopes(): array {
		try {
			/**
			 * Filter the notice scopes.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string> $scopes The notice scopes.
			 * @param Notice_Base   $notice The notice instance.
			 */
			return apply_filters( 'divi_squad_notice_scopes', $this->scopes, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get notice scopes' );

			return array( 'global' );
		}
	}

	/**
	 * Set the scopes for this notice.
	 *
	 * @since 3.3.3
	 *
	 * @param array<string> $scopes The notice scopes.
	 *
	 * @return void
	 */
	public function set_scopes( array $scopes ): void {
		$this->scopes = $scopes;

		/**
		 * Action fired after notice scopes are set.
		 *
		 * @since 3.3.3
		 *
		 * @param array<string> $scopes The notice scopes.
		 * @param Notice_Base   $notice The notice instance.
		 */
		do_action( 'divi_squad_notice_scopes_set', $scopes, $this );
	}

	/**
	 * Add a scope to this notice.
	 *
	 * @since 3.3.3
	 *
	 * @param string $scope The scope to add.
	 *
	 * @return void
	 */
	public function add_scope( string $scope ): void {
		if ( ! in_array( $scope, $this->scopes, true ) ) {
			$this->scopes[] = $scope;

			/**
			 * Action fired after a notice scope is added.
			 *
			 * @since 3.3.3
			 *
			 * @param string        $scope  The added scope.
			 * @param array<string> $scopes All notice scopes after addition.
			 * @param Notice_Base   $notice The notice instance.
			 */
			do_action( 'divi_squad_notice_scope_added', $scope, $this->scopes, $this );
		}
	}

	/**
	 * Remove a scope from this notice.
	 *
	 * @since 3.3.3
	 *
	 * @param string $scope The scope to remove.
	 *
	 * @return void
	 */
	public function remove_scope( string $scope ): void {
		$key = array_search( $scope, $this->scopes, true );
		if ( false !== $key ) {
			unset( $this->scopes[ $key ] );
			$this->scopes = array_values( $this->scopes ); // Re-index array

			/**
			 * Action fired after a notice scope is removed.
			 *
			 * @since 3.3.3
			 *
			 * @param string        $scope  The removed scope.
			 * @param array<string> $scopes All notice scopes after removal.
			 * @param Notice_Base   $notice The notice instance.
			 */
			do_action( 'divi_squad_notice_scope_removed', $scope, $this->scopes, $this );
		}
	}

	/**
	 * Get default template arguments common to all notices.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string, mixed> Default template arguments.
	 */
	protected function get_default_template_args(): array {
		try {
			$args = array(
				'wrapper_classes' => '',
				'logo'            => 'logos/divi-squad-d-default.svg',
				'title'           => '',
				'content'         => '',
				'action_buttons'  => array(),
				'is_dismissible'  => true,
				'notice_id'       => $this->get_notice_id(),
				'scopes'          => $this->get_scopes(),
			);

			/**
			 * Filter the default notice template arguments.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, mixed> $args   The default template arguments.
			 * @param Notice_Base          $notice The notice instance.
			 */
			return apply_filters( 'divi_squad_notice_default_template_args', $args, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get default notice template args' );

			return array(
				'wrapper_classes' => '',
				'is_dismissible'  => true,
				'notice_id'       => $this->get_notice_id(),
			);
		}
	}

	/**
	 * Check if the notice can be rendered.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Whether the notice can be rendered.
	 */
	abstract public function can_render_it(): bool;

	/**
	 * Get the notice template arguments.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string, mixed> The notice template arguments.
	 */
	abstract public function get_template_args(): array;
}
