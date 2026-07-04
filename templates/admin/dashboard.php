<?php
/**
 * Template file to manage layouts.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}
?>

<main id="squad-modules-app" class="squad-components">
	<style id='squad-modules-app-loader-css'>
		/* Resting admin styles*/
		body #wpwrap #wpcontent {
			padding-left: 0;
		}

		body #wpwrap #wpbody-content {
			padding-bottom: 0;
		}

		#squad-modules-app.squad-components {
			position: relative;
			display: block;
			background-color: #fff;
			width: initial;
			max-width: 2560px;
			min-height: 85vmin;
			font-size: 16px;
			color: #000;
		}

		/**===== Squad Modules App (Preloader) =====*/
		.squad-modules-app-loader {
			position: absolute;
			top: calc(50% - 120px);
			left: calc(50% - 40px);
			z-index: 200000;
			width: 40px;
			height: 40px;
			margin: 0 auto;
			border-top: 6px solid rgba(94, 46, 255, 1);
			border-right: 6px solid rgba(94, 46, 255, 0.48);
			border-bottom: 6px solid rgba(94, 46, 255, 1);
			border-left: 6px solid rgba(94, 46, 255, 0.48);
			border-radius: 100%;
			-webkit-animation: ext-copy-spinner-rotation .9s infinite linear;
			-moz-animation: ext-copy-spinner-rotation .9s infinite linear;
			-o-animation: ext-copy-spinner-rotation .9s infinite linear;
			animation: ext-copy-spinner-rotation .9s infinite linear;
		}

		@-webkit-keyframes ext-copy-spinner-rotation {

			from {
				-webkit-transform: rotate(0deg);
			}

			to {
				-webkit-transform: rotate(359deg);
			}
		}

		@-moz-keyframes ext-copy-spinner-rotation {

			from {
				-moz-transform: rotate(0deg);
			}

			to {
				-moz-transform: rotate(359deg);
			}
		}

		@-o-keyframes ext-copy-spinner-rotation {

			from {
				-o-transform: rotate(0deg);
			}

			to {
				-o-transform: rotate(359deg);
			}
		}

		@keyframes ext-copy-spinner-rotation {

			from {
				transform: rotate(0deg);
			}

			to {
				transform: rotate(359deg);
			}
		}
	</style>
	<div class='squad-modules-app-loader'></div>
</main>
