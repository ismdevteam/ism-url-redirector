<?php
/**
 * Plugin Name:       ISM URL Redirector
 * Plugin URI:        https://github.com/ismdevteam/ism-url-redirector
 * Description:       Override any site URL and force-redirect it before WordPress/WooCommerce routing. Bootstrap-powered admin UI, configuration stored as CSV inside the plugin folder.
 * Version:           1.0.0
 * Author:            @ismdevteam
 * Author URI:        https://github.com/ismdevteam
 * License:           GPLv3
 * Text Domain:       ism-url-redirector
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ISM_URL_Redirector {

	const VERSION = '1.0.0';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_post_ism_urlr_save', [ $this, 'handle_save' ] );
		add_action( 'admin_post_ism_urlr_delete', [ $this, 'handle_delete' ] );
		add_action( 'admin_post_ism_urlr_toggle', [ $this, 'handle_toggle' ] );
		add_action( 'init', [ $this, 'maybe_redirect' ], 1 );
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
	}

	/* ------------------------------------------------------------------ *
	 *  Storage (CSV inside plugin folder, one item per line):
	 *  enabled,path,target
	 *  Example: 1,/buy/3840,https://widget.afisha.yandex.ru/w/sessions/...
	 * ------------------------------------------------------------------ */

	public static function csv_path() {
		return plugin_dir_path( __FILE__ ) . 'data' . DIRECTORY_SEPARATOR . 'redirects.csv';
	}

	public static function read_items() {
		$items = [];
		$file  = self::csv_path();
		if ( ! file_exists( $file ) ) {
			return $items;
		}
		foreach ( file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {
			$row = str_getcsv( $line );
			if ( count( $row ) < 3 ) {
				continue;
			}
			$items[] = [
				'enabled' => '1' === $row[0] ? 1 : 0,
				'path'    => $row[1],
				'target'  => $row[2],
			];
		}
		return $items;
	}

	public static function write_items( $items ) {
		$file = self::csv_path();
		$fh   = fopen( $file, 'w' );
		if ( ! $fh ) {
			return false;
		}
		flock( $fh, LOCK_EX );
		foreach ( $items as $item ) {
			fputcsv( $fh, [ $item['enabled'] ? '1' : '0', $item['path'], $item['target'] ] );
		}
		flock( $fh, LOCK_UN );
		fclose( $fh );
		return true;
	}

	/* ------------------------------------------------------------------ *
	 *  Front-end: force redirect before WP/Woo routing
	 * ------------------------------------------------------------------ */

	public function maybe_redirect() {
		if ( is_admin() || defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) || defined( 'XMLRPC_REQUEST' ) ) {
			return;
		}
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$request_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
		$request_path = '/' . ltrim( (string) $request_path, '/' );
		$normalized   = rtrim( $request_path, '/' );
		if ( '' === $normalized ) {
			$normalized = '/';
		}

		foreach ( self::read_items() as $item ) {
			if ( ! $item['enabled'] ) {
				continue;
			}
			$item_path = parse_url( $item['path'], PHP_URL_PATH ); // accept full URLs too
			$item_path = '/' . ltrim( (string) $item_path, '/' );
			$item_path = rtrim( $item_path, '/' );
			if ( '' === $item_path ) {
				$item_path = '/';
			}
			if ( 0 === strcasecmp( $normalized, $item_path ) ) {
				wp_redirect( $item['target'], 302 );
				exit;
			}
		}
	}

	/* ------------------------------------------------------------------ *
	 *  Admin UI
	 * ------------------------------------------------------------------ */

	public function activate() {
		$data_dir = plugin_dir_path( __FILE__ ) . 'data';
		if ( ! is_dir( $data_dir ) ) {
			wp_mkdir_p( $data_dir );
		}
		if ( ! file_exists( self::csv_path() ) ) {
			self::write_items( [] );
		}
	}

	public function register_admin_menu() {
		add_menu_page(
			__( 'URL Redirector', 'ism-url-redirector' ),
			__( 'URL Redirector', 'ism-url-redirector' ),
			'manage_options',
			'ism-url-redirector',
			[ $this, 'render_admin_page' ],
			'dashicons-randomize',
			80
		);
	}

	public function enqueue_assets( $hook ) {
		if ( false === strpos( (string) $hook, 'ism-url-redirector' ) ) {
			return;
		}
		wp_enqueue_style(
			'ism-urlr-bootstrap',
			plugin_dir_url( __FILE__ ) . 'assets/css/bootstrap.min.css',
			[],
			self::VERSION
		);
		wp_enqueue_script(
			'ism-urlr-bootstrap',
			plugin_dir_url( __FILE__ ) . 'assets/js/bootstrap.bundle.min.js',
			[],
			self::VERSION,
			true
		);
	}

	private function back( $args = [] ) {
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php?page=ism-url-redirector' ) ) );
		exit;
	}

	private function normalize_path( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return '';
		}
		if ( preg_match( '#^https?://#i', $raw ) ) {
			$parts = parse_url( $raw );
			$path  = isset( $parts['path'] ) ? $parts['path'] : '/';
			if ( ! empty( $parts['query'] ) ) {
				$path .= '?' . $parts['query'];
			}
			return $path;
		}
		return '/' . ltrim( $raw, '/' );
	}

	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'ism_urlr_save' );

		$index  = isset( $_POST['item_index'] ) && '' !== $_POST['item_index'] ? (int) $_POST['item_index'] : null;
		$raw    = isset( $_POST['override_url'] ) ? sanitize_text_field( wp_unslash( $_POST['override_url'] ) ) : '';
		$target = isset( $_POST['target_url'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['target_url'] ) ) ) : '';
		if ( '' !== $target && 0 !== strpos( $target, '/' ) ) {
			$target = esc_url_raw( $target ); // full URL required unless it is a site path
		}

		$path = $this->normalize_path( $raw );
		if ( '' === $path || '' === $target ) {
			$this->back( [ 'ism_urlr_notice' => 'error' ] );
		}

		$items   = self::read_items();
		$warning = 0;

		// Warn if the override URL carries a foreign domain.
		if ( preg_match( '#^https?://#i', $raw ) ) {
			$host = parse_url( $raw, PHP_URL_HOST );
			if ( $host && strcasecmp( $host, wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
				$warning = 1;
			}
		}

		foreach ( $items as $i => $existing ) {
			if ( $i !== $index && 0 === strcasecmp( rtrim( $existing['path'], '/' ), rtrim( $path, '/' ) ) ) {
				$this->back( [ 'ism_urlr_notice' => 'duplicate' ] );
			}
		}

		$item = [ 'enabled' => 1, 'path' => $path, 'target' => $target ];
		if ( null !== $index && isset( $items[ $index ] ) ) {
			$item['enabled'] = $items[ $index ]['enabled']; // keep switch state on edit
			$items[ $index ] = $item;
		} else {
			$items[] = $item;
		}

		self::write_items( $items );
		$this->back( [ 'ism_urlr_notice' => 'saved', 'ism_urlr_warn' => $warning ] );
	}

	public function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'ism_urlr_delete' );
		$index = (int) ( $_POST['index'] ?? $_GET['index'] ?? -1 );
		$items = self::read_items();
		if ( isset( $items[ $index ] ) ) {
			unset( $items[ $index ] );
			self::write_items( array_values( $items ) );
		}
		$this->back( [ 'ism_urlr_notice' => 'deleted' ] );
	}

	public function handle_toggle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'ism_urlr_toggle' );
		$index = (int) ( $_POST['index'] ?? $_GET['index'] ?? -1 );
		$items = self::read_items();
		if ( isset( $items[ $index ] ) ) {
			$items[ $index ]['enabled'] = $items[ $index ]['enabled'] ? 0 : 1;
			self::write_items( $items );
		}
		$this->back( [] );
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Forbidden' );
		}
		require_once plugin_dir_path( __FILE__ ) . 'includes/admin-page.php';
	}
}

ISM_URL_Redirector::instance();
