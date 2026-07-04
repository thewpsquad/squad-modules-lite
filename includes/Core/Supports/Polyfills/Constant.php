<?php

/**
 * Polyfill for PHP constants.
 *
 * @since   3.1.1
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Core\Supports\Polyfills;

/**
 * Constant class.
 *
 * @since   3.1.1
 * @package DiviSquad
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
	const PHP_INT_MIN = - 9223372036854775808; // @phpstan-ignore-line
}
