<?php
/**
 * Parent WP-CLI namespace for `wp wcus26`.
 *
 * Must be a class without __invoke so WP-CLI treats it as a command group
 * that can have subcommands.
 *
 * @package Maintenance_Tools_WCUS26
 * @since 1.0.0
 */

namespace Maintenance_Tools_WCUS26\CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Empty command group registered as `wp wcus26`.
 *
 * @since 1.0.0
 */
class Root_Command {
}
