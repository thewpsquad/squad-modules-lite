<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Error Report Email Handler
 *
 * Provides a robust system for sending error reports via email with proper
 * WordPress integration, rate limiting, validation, and comprehensive error handling.
 *
 * @since      3.1.7
 * @deprecated 3.3.0
 * @package    DiviSquad\Emails
 */

namespace DiviSquad\Managers\Emails;

use DiviSquad\Emails\ErrorReport as BaseErrorReport;

/**
 * Error Report Email Handler
 *
 * Provides a robust system for sending error reports via email with proper
 * WordPress integration, rate limiting, validation, and comprehensive error handling.
 *
 * Features:
 * - Configurable rate limiting to prevent email flooding
 * - Data validation and sanitization
 * - HTML email templates with fallback
 * - WordPress filter and action hooks for extensibility
 * - Error handling and logging
 *
 * @since      3.1.7
 * @deprecated 3.3.0
 * @author     The WP Squad <support@squadmodules.com>
 * @package    DiviSquad\Emails
 */
class ErrorReport extends BaseErrorReport { }
