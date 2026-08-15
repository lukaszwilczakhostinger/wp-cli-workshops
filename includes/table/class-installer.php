<?php
/**
 * Creates plugin custom tables.
 *
 * @package Maintenance_Tools_WCUS26
 * @since 1.0.0
 */

namespace Maintenance_Tools_WCUS26\Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installs and upgrades the reports and logs tables.
 *
 * @since 1.0.0
 */
class Installer {

	/**
	 * Database schema version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const DB_VERSION = '2';

	/**
	 * Option that stores the installed schema version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const OPTION = 'mt_wcus26_db_version';

	/**
	 * Create tables if the schema is missing or outdated.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function maybe_install(): void {
		if ( self::DB_VERSION === (string) get_option( self::OPTION, '' ) ) {
			return;
		}

		self::install();
	}

	/**
	 * Create both custom tables and store the schema version.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function install(): void {
		Reports_Table::create_table();
		Logs_Table::create_table();
		update_option( self::OPTION, self::DB_VERSION, true );
	}
}
