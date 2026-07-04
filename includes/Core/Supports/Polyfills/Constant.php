<?php

/**
 * Polyfill for PHP constants.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.1
 */

namespace DiviSquad\Core\Supports\Polyfills;

/**
 * Constant class.
 *
 * @package DiviSquad
 * @since   3.1.1
 */
class Constant {
	/**
	 * PHP_INT_MAX constants.
	 *
	 * @var integer
	 */
	const PHP_INT_MAX = 9223372036854775807;

	/**
	 * PHP_INT_MIN constants.
	 *
	 * @var integer
	 */
	const PHP_INT_MIN = -9223372036854775808; // @phpstan-ignore-line
}
