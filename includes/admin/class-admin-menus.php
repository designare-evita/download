<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Direkten Zugriff auf die Datei verhindern
}

/**
 * Erstellt die Admin-Menüs und steuert die Anzeige der Plugin-Seiten.
 * 
 * Version 7.1 - Komplett überarbeitet mit korrekten Berechtigungen
 * 
 * @since 6.0
 */
class CSV_Import_Pro_Admin {

	/**
	 * Plugin-Slug für Menüs
	 * 
	 * @var string
	 */
	private $menu_slug = 'csv-import';

	/**
	 * Menü-Konfiguration
	 * 
	 * @var array
	 */
	private $menu_config = [];

	/**
	 * Admin-Seiten Registry
	 * 
	 * @var array
	 */
	private $admin_pages = [];

	/**
	 * Konstruktor - Initialisiert Admin-Komponenten
	 */
	public function __construct() {
		$this->setup_menu_config();
		$this->init_hooks();
	}

	/**
	 * Konfiguriert Menü-Struktur mit korrekten Capabilities
	 */
	private function setup_menu_config() {
		$this->menu_config = [
			'main' => [
				'page_title' => __( 'CSV Import Pro', 'csv-import' ),
				'menu_title' => __( 'CSV Import', 'csv-import' ),
				'capability' => 'manage_options',
				'menu_slug'  => $this->menu_slug,
				'callback'   => [ $this, 'display_main_page' ],
				'icon_url'   => 'dashicons-database-import',
				'position'   => 30
			],
			'submenus' => [
				'import' => [
					'page_title' => __( 'CSV Import Dashboard', 'csv-import' ),
					'menu_title' => __( 'Import Dashboard', 'csv-import' ),
					'menu_slug'  => $this->menu_slug,
					'callback'   => [ $this, 'display_main_page' ],
					'capability' => 'manage_options'
				],
				'settings' => [
					'page_title' => __( 'CSV Import Einstellungen', 'csv-import' ),
					'menu_title' => __( 'Einstellungen', 'csv-import' ),
					'menu_slug'  => 'csv-import-settings',
					'callback'   => [ $this, 'display_settings_page' ],
					'capability' => 'manage_options'
				],
				'backups' => [
					'page_title' => __( 'CSV Import Backups', 'csv-import' ),
					'menu_title' => __( 'Backups & Rollback', 'csv-import' ),
					'menu_slug'  => 'csv-import-backups', 
					'callback'   => [ $this, 'display_backup_page' ],
					'capability' => 'manage_options'
				],
				'profiles' => [
					'page_title' => __( 'CSV Import Profile', 'csv-import' ),
					'menu_title' => __( 'Import-Profile', 'csv-import' ),
					'menu_slug'  => 'csv-import-profiles',
					'callback'   => [ $this, 'display_profiles_page' ],
					'capability' => 'manage_options'
				],
				'scheduling' => [
					'page_title' => __( 'CSV Import Scheduling', 'csv-import' ),
					'menu_title' => __( 'Automatisierung', 'csv-import' ),
					'menu_slug'  => 'csv-import-scheduling',
					'callback'   => [ $this, 'display_scheduling_page' ],
					'capability' => 'manage_options'
				],
				'logs' => [
					'page_title' => __( 'CSV Import Logs', 'csv-import' ),
					'menu_title' => __( 'Logs & Monitoring', 'csv-import' ),
					'menu_slug'  => 'csv-import-logs',
					'callback'   => [ $this, 'display_logs_page' ],
					'capability' => 'manage_options'
				],
				'advanced' => [
					'page_title' => __( 'CSV Import Erweiterte Einstellungen', 'csv-import' ),
					'menu_title' => __( 'Erweitert', 'csv-import' ),
					'menu_slug'  => 'csv-import-advanced',
					'callback'   => [ $this, 'display_advanced_settings_page' ],
					'capability' => 'manage_options'
				],
				'tools' => [
					'page_title' => __( 'CSV Import Werkzeuge', 'csv-import' ),
					'menu_title' => __( 'Werkzeuge', 'csv-import' ),
					'menu_slug'  => 'csv-import-tools',
					'callback'   => [ $this, 'display_tools_page' ],
					'capability' => 'manage_options'
				]
			]
		];

		// Filter für Anpassungen durch andere Plugins/Themes
		$this->menu_config = apply_filters( 'csv_import_menu_config', $this->menu_config );
	}

	/**
	 * Initialisiert WordPress-Hooks
	 */
	private function init_hooks() {
		// Admin-Menü registrieren
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ], 10 );
		
		// Assets nur auf Plugin-Seiten laden
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		
		// Plugin-Einstellungen registrieren
		add_action( 'admin_init', [ $this, 'register_plugin_settings' ] );
		
		// Admin-Notices für Plugin-spezifische Meldungen
		add_action( 'admin_notices', [ $this, 'show_plugin_notices' ] );
		
		// AJAX-Handler für Admin-Funktionen
		add_action( 'wp_ajax_csv_import_admin_action', [ $this, 'handle_admin_ajax' ] );
		
		// Plugin-Links in der Plugin-Liste
		add_filter( 'plugin_action_links_' . CSV_IMPORT_PRO_BASENAME, [ $this, 'add_plugin_action_links' ] );
		
		// Plugin-Meta-Links
		add_filter( 'plugin_row_meta', [ $this, 'add_plugin_meta_links' ], 10, 2 );

		// Admin-Footer-Text anpassen auf Plugin-Seiten
		add_filter( 'admin_footer_text', [ $this, 'admin_footer_text' ] );
		
	}

	/**
	 * Registriert das Admin-Menü und Untermenüs - KORRIGIERT
	 */
/**
	 * Registriert das Admin-Menü und Untermenüs.
	 * KORRIGIERTE VERSION: Verwendet die 'capability' aus der Konfiguration,
	 * statt sie hart zu codieren.
	 */
	public function register_admin_menu() {
		// Der initiale Sicherheitscheck ist gut, da niemand ohne diese Basis-Berechtigung
		// überhaupt Menüs sehen sollte.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$main_config = $this->menu_config['main'];
		
		// Hauptmenüpunkt hinzufügen
		$main_page = add_menu_page(
			$main_config['page_title'],
			$main_config['menu_title'],
			$main_config['capability'], // KORREKTUR: Wert aus der Konfiguration verwenden
			$main_config['menu_slug'],
			$main_config['callback'],
			$main_config['icon_url'],
			$main_config['position']
		);

		if ( $main_page ) {
			add_action( "load-{$main_page}", [ $this, 'load_main_page' ] );
			$this->admin_pages['main'] = $main_page;
		}

		// Untermenüs hinzufügen
		foreach ( $this->menu_config['submenus'] as $submenu_key => $submenu_config ) {
			$submenu_page = add_submenu_page(
				$this->menu_slug,
				$submenu_config['page_title'],
				$submenu_config['menu_title'],
				$submenu_config['capability'], // KORREKTUR: Wert aus der Konfiguration verwenden
				$submenu_config['menu_slug'],
				$submenu_config['callback']
			);

			if ( $submenu_page ) {
				add_action( "load-{$submenu_page}", [ $this, 'load_submenu_page' ] );
				$this->admin_pages[$submenu_key] = $submenu_page;
			}
		}

		// Debug-Tools (nur bei aktiviertem Debug)
		if ( defined( 'CSV_IMPORT_DEBUG' ) && CSV_IMPORT_DEBUG ) {
			$debug_page = add_submenu_page(
				$this->menu_slug,
				__( 'CSV Import Debug', 'csv-import' ),
				__( '🔧 Debug', 'csv-import' ),
				'manage_options', // Debug-Seite sollte immer nur für Admins sein
				'csv-import-debug',
				[ $this, 'display_debug_page' ]
			);
			if ( $debug_page ) {
				$this->admin_pages['debug'] = $debug_page;
			}
		}

		$this->maybe_add_dashboard_widget();
	}

	/**
	 * Überprüft Seitenzugriff für alle Plugin-Seiten
	 */
	public function check_page_access() {
		$screen = get_current_screen();
		
		// Prüfen ob wir auf einer Plugin-Seite sind
		if ( isset( $screen->id ) && $this->is_plugin_page( $screen->id ) ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 
					__( 'Du bist nicht berechtigt, auf diese Seite zuzugreifen.', 'csv-import' ),
					__( 'Keine Berechtigung', 'csv-import' ),
					[ 'response' => 403 ]
				);
			}
		}
	}

	/**
	 * Lädt Assets nur auf Plugin-Seiten
	 * 
	 * @param string $hook_suffix
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Prüfen ob wir auf einer Plugin-Seite sind
		if ( ! $this->is_plugin_page( $hook_suffix ) ) {
			return;
		}

		$version = defined( 'CSV_IMPORT_PRO_VERSION' ) ? CSV_IMPORT_PRO_VERSION : '1.0.0';
		$is_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		$suffix = $is_debug ? '' : '.min';

		// CSS einbinden
		wp_enqueue_style(
			'csv-import-pro-admin-style',
			CSV_IMPORT_PRO_URL . "assets/css/admin-style{$suffix}.css",
			[],
			$version
		);

		// JavaScript einbinden
		wp_enqueue_script(
			'csv-import-pro-admin-script',
			CSV_IMPORT_PRO_URL . "assets/js/admin-script{$suffix}.js",
			[ 'jquery', 'wp-util', 'wp-api' ],
			$version,
			true
		);

		// Spezielle Scripts für bestimmte Seiten
		$this->enqueue_page_specific_assets( $hook_suffix );

		// JavaScript-Variablen
		wp_localize_script( 'csv-import-pro-admin-script', 'csvImportAjax', [
			'ajaxurl'            => admin_url( 'admin-ajax.php' ),
			'nonce'              => wp_create_nonce( 'csv_import_ajax' ),
			'admin_nonce'        => wp_create_nonce( 'csv_import_admin_action' ),
			'strings'            => $this->get_js_strings(),
			'config'             => $this->get_js_config(),
			'debug'              => defined( 'CSV_IMPORT_DEBUG' ) ? CSV_IMPORT_DEBUG : false,
			'current_page'       => $this->get_current_page_slug( $hook_suffix ),
			'user_can_import'    => current_user_can( 'manage_options' ),
			'import_running'     => function_exists( 'csv_import_is_import_running' ) ? csv_import_is_import_running() : false,
			'system_status'      => $this->get_system_status_for_js()
		] );
	}

	/**
	 * Lädt seitenspezifische Assets
	 * 
	 * @param string $hook_suffix
	 */
	private function enqueue_page_specific_assets( $hook_suffix ) {
		$page_slug = $this->get_current_page_slug( $hook_suffix );

		switch ( $page_slug ) {
			case 'csv-import-logs':
				// Chart.js für Log-Visualisierung
				wp_enqueue_script(
					'chart-js',
					'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.min.js',
					[],
					'4.4.1',
					true
				);
				break;

			case 'csv-import-scheduling':
				// Cron-Expression-Builder
				wp_enqueue_script(
					'cron-builder',
					CSV_IMPORT_PRO_URL . 'assets/js/cron-builder.js',
					[ 'jquery' ],
					CSV_IMPORT_PRO_VERSION,
					true
				);
				break;

			case 'csv-import-advanced':
			case 'csv-import-debug':
				// CodeMirror für erweiterte Einstellungen
				wp_enqueue_code_editor( [ 'type' => 'application/json' ] );
				break;
		}
	}

	/**
	 * Registriert Plugin-Einstellungen
	 */
	public function register_plugin_settings() {
		// Einstellungsgruppe registrieren
		register_setting( 'csv_import_settings', 'csv_import_settings', [
			'type'              => 'array',
			'sanitize_callback' => [ $this, 'sanitize_settings' ],
			'default'           => $this->get_default_settings()
		] );

		// Einzelne Einstellungen für bessere Verwaltung
		$individual_settings = [
			'template_id'      => [ 'type' => 'integer', 'sanitize' => 'absint' ],
			'post_type'        => [ 'type' => 'string',  'sanitize' => 'sanitize_key' ],
			'post_status'      => [ 'type' => 'string',  'sanitize' => 'sanitize_key' ],
			'page_builder'     => [ 'type' => 'string',  'sanitize' => 'sanitize_key' ],
			'dropbox_url'      => [ 'type' => 'string',  'sanitize' => 'esc_url_raw' ],
			'local_path'       => [ 'type' => 'string',  'sanitize' => 'sanitize_text_field' ],
			'image_source'     => [ 'type' => 'string',  'sanitize' => 'sanitize_key' ],
			'image_folder'     => [ 'type' => 'string',  'sanitize' => 'sanitize_text_field' ],
			'memory_limit'     => [ 'type' => 'string',  'sanitize' => 'sanitize_text_field' ],
			'time_limit'       => [ 'type' => 'integer', 'sanitize' => 'absint' ],
			'seo_plugin'       => [ 'type' => 'string',  'sanitize' => 'sanitize_key' ],
			'required_columns' => [ 'type' => 'string',  'sanitize' => 'sanitize_textarea_field' ],
			'skip_duplicates'  => [ 'type' => 'boolean', 'sanitize' => 'rest_sanitize_boolean' ]
		];

		foreach ( $individual_settings as $key => $config ) {
			register_setting( 'csv_import_settings', 'csv_import_' . $key, [
				'type'              => $config['type'],
				'sanitize_callback' => $config['sanitize'],
				'default'           => $this->get_default_value( $key ),
				'show_in_rest'      => true,
			] );
		}

		// Erweiterte Einstellungen
		register_setting( 'csv_import_advanced_settings', 'csv_import_advanced_settings', [
			'type'              => 'array',
			'sanitize_callback' => [ $this, 'sanitize_advanced_settings' ],
			'default'           => $this->get_default_advanced_settings()
		] );
	}

/**
	 * Verarbeitet AJAX-Anfragen für Admin-Funktionen
	 */
	public function handle_admin_ajax() {
		// Sicherheitsprüfung
		if ( ! check_ajax_referer( 'csv_import_admin_action', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Sicherheitsprüfung fehlgeschlagen', 'csv-import' ) ] );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Keine Berechtigung', 'csv-import' ) ] );
		}

		$action = sanitize_key( $_POST['admin_action'] ?? '' );

		switch ( $action ) {
			case 'test_connection':
				$this->ajax_test_connection();
				break;

			case 'reset_plugin':
				$this->ajax_reset_plugin();
				break;

			case 'export_settings':
				$this->ajax_export_settings();
				break;

			case 'import_settings':
				$this->ajax_import_settings();
				break;

			case 'system_info':
				$this->ajax_get_system_info();
				break;

			default:
				wp_send_json_error( [ 'message' => __( 'Unbekannte Aktion', 'csv-import' ) ] );
		}
	}

	/**
	 * Fügt Plugin-Action-Links hinzu
	 * 
	 * @param array $links
	 * @return array
	 */
	public function add_plugin_action_links( $links ) {
		$plugin_links = [
			'settings' => sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'admin.php?page=csv-import-settings' ),
				__( 'Einstellungen', 'csv-import' )
			),
			'import' => sprintf(
				'<a href="%s" style="color: #00a32a; font-weight: 600;">%s</a>',
				admin_url( 'admin.php?page=csv-import' ),
				__( 'Import starten', 'csv-import' )
			)
		];

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Fügt Plugin-Meta-Links hinzu
	 * 
	 * @param array $links
	 * @param string $file
	 * @return array
	 */
	public function add_plugin_meta_links( $links, $file ) {
		if ( $file !== CSV_IMPORT_PRO_BASENAME ) {
			return $links;
		}

		$meta_links = [
			'docs' => sprintf(
				'<a href="%s" target="_blank">%s</a>',
				'https://example.com/docs',
				__( 'Dokumentation', 'csv-import' )
			),
			'support' => sprintf(
				'<a href="%s" target="_blank">%s</a>',
				'https://example.com/support',
				__( 'Support', 'csv-import' )
			)
		];

		return array_merge( $links, $meta_links );
	}

	/**
	 * Zeigt Plugin-spezifische Admin-Notices
	 */
	public function show_plugin_notices() {
		if ( ! $this->is_plugin_page() ) {
			return;
		}

		// Import läuft gerade
		if ( function_exists( 'csv_import_is_import_running' ) && csv_import_is_import_running() ) {
			$progress = function_exists( 'csv_import_get_progress' ) ? csv_import_get_progress() : [];
			
			echo '<div class="notice notice-info csv-import-progress-notice">';
			echo '<p><strong>' . __( 'Import läuft:', 'csv-import' ) . '</strong> ';
			
			if ( ! empty( $progress['message'] ) ) {
				echo esc_html( $progress['message'] );
			} else {
				echo __( 'Ein CSV-Import wird gerade verarbeitet...', 'csv-import' );
			}
			
			if ( ! empty( $progress['percent'] ) ) {
				echo sprintf( ' (%s%%)', $progress['percent'] );
			}
			
			echo '</p>';
			
			// Progress-Bar
			if ( ! empty( $progress['percent'] ) ) {
				echo '<div class="csv-import-progress-bar" style="background: #f0f0f1; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0;">';
				echo '<div class="csv-import-progress-fill" style="background: #00a32a; height: 100%; width: ' . intval( $progress['percent'] ) . '%; transition: width 0.3s ease;"></div>';
				echo '</div>';
			}
			
			echo '</div>';
		}

		// Konfigurationsfehler
		if ( function_exists( 'csv_import_get_config' ) && function_exists( 'csv_import_validate_config' ) ) {
			$config = csv_import_get_config();
			$validation = csv_import_validate_config( $config );
			
			if ( ! $validation['valid'] && ! empty( $validation['errors'] ) ) {
				echo '<div class="notice notice-warning">';
				echo '<p><strong>' . __( 'CSV Import Konfigurationsprobleme:', 'csv-import' ) . '</strong></p>';
				echo '<ul style="margin-left: 20px;">';
				foreach ( $validation['errors'] as $error ) {
					echo '<li>' . esc_html( $error ) . '</li>';
				}
				echo '</ul>';
				echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=csv-import-settings' ) ) . '" class="button">' . __( 'Einstellungen korrigieren', 'csv-import' ) . '</a></p>';
				echo '</div>';
			}
		}

		// System-Warnungen
		$this->show_system_warnings();
	}

	/**
	 * Anpassung des Admin-Footer-Texts
	 * 
	 * @param string $text
	 * @return string
	 */
	public function admin_footer_text( $text ) {
		if ( ! $this->is_plugin_page() ) {
			return $text;
		}

		return sprintf(
			__( 'Vielen Dank für die Nutzung von %s. | Version %s', 'csv-import' ),
			'<strong>CSV Import Pro</strong>',
			defined( 'CSV_IMPORT_PRO_VERSION' ) ? CSV_IMPORT_PRO_VERSION : '1.0.0'
		);
	}

	// ===================================================================
	// SEITEN-CALLBACK-FUNKTIONEN MIT SICHERHEITSCHECKS
	// ===================================================================

	/**
	 * Zeigt die Hauptseite an
	 */
	public function display_main_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Du bist nicht berechtigt, auf diese Seite zuzugreifen.', 'csv-import' ) );
		}
		
		$this->load_page_data( 'main' );
		$this->render_page( 'page-main.php' );
	}

	/**
	 * Zeigt die Einstellungsseite an
	 */
	public function display_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Du bist nicht berechtigt, auf diese Seite zuzugreifen.', 'csv-import' ) );
		}
		
		$this->handle_settings_form_submission();
		$this->load_page_data( 'settings' );
		$this->render_page( 'page-settings.php' );
	}

	/**
	 * Zeigt die Backup-Seite an
	 */
	public function display_backup_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Du bist nicht berechtigt, auf diese Seite zuzugreifen.', 'csv-import' ) );
		}
		
		$this->handle_backup_actions();
		$this->load_page_data( 'backups' );
		$this->render_page( 'page-backups.php' );
	}

	/**
	 * Zeigt die Profile-Seite an
	 */
	public function display_profiles_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Du bist nicht berechtigt, auf diese Seite zuzugreifen.', 'csv-import' ) );
		}
		
		$this->handle_profile_actions();
		$this->load_page_data( 'profiles' );
		$this->render_page( 'page-profiles.php' );
	}

	/**
	 * Zeigt die Scheduling-Seite an
	 */
	public function display_scheduling_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Du bist nicht berechtigt, auf diese Seite zuzugreifen.', 'csv-import' ) );
		}
		
		$this->handle_scheduling_actions();
		$this->load_page_data( 'scheduling' );
		$this->render_page( 'page-scheduling.php' );
	}

	/**
	 * Zeigt die Logs-Seite an
	 */
	public function display_logs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Du bist nicht berechtigt, auf diese Seite zuzugreifen.', 'csv-import' ) );
		}
		
		$this->handle_logs_actions();
		$this->load_page_data( 'logs' );
		$this->render_page( 'page-logs.php' );
	}

	/**
	 * Zeigt die erweiterten Einstellungen an
	 */
	public function display_advanced_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Du bist nicht berechtigt, auf diese Seite zuzugreifen.', 'csv-import' ) );
		}
		
		$this->handle_advanced_settings_submission();
		$this->load_page_data( 'advanced' );
		$this->render_page( 'page-advanced-settings.php' );
	}

	/**
	 * Zeigt die Werkzeuge-Seite an
	 */
	public function display_tools_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Du bist nicht berechtigt, auf diese Seite zuzugreifen.', 'csv-import' ) );
		}
		
		$this->handle_tools_actions();
		$this->load_page_data( 'tools' );
		$this->render_page( 'page-tools.php' );
	}

	/**
	 * Zeigt die Debug-Seite an (nur bei aktiviertem Debug)
	 */
	public function display_debug_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Du bist nicht berechtigt, auf diese Seite zuzugreifen.', 'csv-import' ) );
		}
		
		if ( ! defined( 'CSV_IMPORT_DEBUG' ) || ! CSV_IMPORT_DEBUG ) {
			wp_die( __( 'Debug-Modus ist nicht aktiviert.', 'csv-import' ) );
		}

		$this->load_page_data( 'debug' );
		$this->render_page( 'page-debug.php' );
	}

	// ===================================================================
	// HILFSFUNKTIONEN
	// ===================================================================

	/**
	 * Prüft ob wir auf einer Plugin-Seite sind
	 * 
	 * @param string|null $hook_suffix
	 * @return bool
	 */

private function is_plugin_page( $hook_suffix = null ) {
		if ( $hook_suffix === null ) {
			$screen = get_current_screen();
			$hook_suffix = $screen ? $screen->id : '';
		}

		return in_array( $hook_suffix, $this->admin_pages ) || 
			   strpos( $hook_suffix, 'csv-import' ) !== false;
	}

	/**
	 * Ermittelt den aktuellen Seiten-Slug
	 * 
	 * @param string $hook_suffix
	 * @return string
	 */
	private function get_current_page_slug( $hook_suffix ) {
		$page_mapping = array_flip( $this->admin_pages );
		return $page_mapping[ $hook_suffix ] ?? $this->menu_slug;
	}

	/**
	 * Lädt seitenspezifische Daten
	 * 
	 * @param string $page_type
	 */
	private function load_page_data( $page_type ) {
		// Gemeinsame Daten für alle Seiten
		$GLOBALS['csv_import_admin_data'] = [
			'page_type' => $page_type,
			'nonce_action' => 'csv_import_' . $page_type . '_action',
			'current_user_can_import' => current_user_can( 'manage_options' ),
			'plugin_version' => defined( 'CSV_IMPORT_PRO_VERSION' ) ? CSV_IMPORT_PRO_VERSION : '1.0.0',
			'wp_version' => get_bloginfo( 'version' ),
			'php_version' => PHP_VERSION
		];

		// Seitenspezifische Daten laden
		switch ( $page_type ) {
			case 'main':
				$this->load_main_page_data();
				break;
			case 'settings':
				$this->load_settings_page_data();
				break;
			case 'backups':
				$this->load_backup_page_data();
				break;
			case 'profiles':
				$this->load_profiles_page_data();
				break;
			case 'scheduling':
				$this->load_scheduling_page_data();
				break;
			case 'logs':
				$this->load_logs_page_data();
				break;
			case 'advanced':
				$this->load_advanced_page_data();
				break;
			case 'tools':
				$this->load_tools_page_data();
				break;
			case 'debug':
				$this->load_debug_page_data();
				break;
		}
	}

	/**
	 * Rendert eine Seiten-Template
	 * 
	 * @param string $template_file
	 */
	private function render_page( $template_file ) {
		$template_path = CSV_IMPORT_PRO_PATH . 'includes/admin/views/' . $template_file;
		
		if ( file_exists( $template_path ) ) {
			// Template-Variablen aus globalen Admin-Daten extrahieren
			if ( isset( $GLOBALS['csv_import_admin_data'] ) ) {
				extract( $GLOBALS['csv_import_admin_data'], EXTR_SKIP );
			}
			
			include $template_path;
		} else {
			$this->render_fallback_page( $template_file );
		}
	}

	/**
	 * Rendert eine Fallback-Seite wenn Template nicht gefunden
	 * 
	 * @param string $template_file
	 */
	private function render_fallback_page( $template_file ) {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
		echo '<div class="notice notice-error">';
		echo '<p>' . sprintf( 
			__( 'Template-Datei nicht gefunden: %s', 'csv-import' ), 
			esc_html( $template_file ) 
		) . '</p>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Lädt Hauptseiten-Daten
	 */
	private function load_main_page_data() {
		// Import-Status und Fortschritt
		$progress = function_exists( 'csv_import_get_progress' ) ? csv_import_get_progress() : [];
		$config = function_exists( 'csv_import_get_config' ) ? csv_import_get_config() : [];
		$validation = function_exists( 'csv_import_validate_config' ) ? csv_import_validate_config( $config ) : [ 'valid' => false ];
		$stats = function_exists( 'csv_import_get_stats' ) ? csv_import_get_stats() : [];
		$health = function_exists( 'csv_import_system_health_check' ) ? csv_import_system_health_check() : [];

		$GLOBALS['csv_import_admin_data'] = array_merge( $GLOBALS['csv_import_admin_data'], [
			'progress' => $progress,
			'config' => $config,
			'config_valid' => $validation,
			'stats' => $stats,
			'health' => $health,
			'import_running' => function_exists( 'csv_import_is_import_running' ) ? csv_import_is_import_running() : false
		] );
	}

	/**
	 * Lädt Einstellungsseiten-Daten
	 */
	private function load_settings_page_data() {
		$config = function_exists( 'csv_import_get_config' ) ? csv_import_get_config() : [];
		
		$GLOBALS['csv_import_admin_data'] = array_merge( $GLOBALS['csv_import_admin_data'], [
			'settings' => $config,
			'post_types' => get_post_types( [ 'public' => true ], 'objects' ),
			'template_info' => function_exists( 'csv_import_get_template_info' ) ? csv_import_get_template_info() : '',
			'file_status' => $this->get_file_status_info()
		] );
	}

	/**
	 * Lädt Backup-Seiten-Daten
	 */
	private function load_backup_page_data() {
		$sessions = [];
		if ( class_exists( 'CSV_Import_Backup_Manager' ) && method_exists( 'CSV_Import_Backup_Manager', 'get_import_sessions' ) ) {
			$sessions = CSV_Import_Backup_Manager::get_import_sessions();
		}

		$GLOBALS['csv_import_admin_data'] = array_merge( $GLOBALS['csv_import_admin_data'], [
			'sessions' => $sessions,
			'backup_settings' => get_option( 'csv_import_advanced_settings', [] )
		] );
	}

	/**
	 * Lädt Profile-Seiten-Daten
	 */
	private function load_profiles_page_data() {
		$profiles = [];
		if ( class_exists( 'CSV_Import_Profile_Manager' ) && method_exists( 'CSV_Import_Profile_Manager', 'get_profiles' ) ) {
			$profiles = CSV_Import_Profile_Manager::get_profiles();
		}

		$GLOBALS['csv_import_admin_data'] = array_merge( $GLOBALS['csv_import_admin_data'], [
			'profiles' => $profiles,
			'current_config' => function_exists( 'csv_import_get_config' ) ? csv_import_get_config() : []
		] );
	}

	/**
	 * Lädt Scheduling-Seiten-Daten
	 */
	private function load_scheduling_page_data() {
		$scheduler_info = [];
		if ( class_exists( 'CSV_Import_Scheduler' ) && method_exists( 'CSV_Import_Scheduler', 'get_scheduler_info' ) ) {
			$scheduler_info = CSV_Import_Scheduler::get_scheduler_info();
		}

		$config = function_exists( 'csv_import_get_config' ) ? csv_import_get_config() : [];
		$validation = function_exists( 'csv_import_validate_config' ) ? csv_import_validate_config( $config ) : [ 'valid' => false ];

		$GLOBALS['csv_import_admin_data'] = array_merge( $GLOBALS['csv_import_admin_data'], [
			'scheduler_info' => $scheduler_info,
			'is_scheduled' => $scheduler_info['is_scheduled'] ?? false,
			'next_scheduled' => $scheduler_info['next_run'] ?? false,
			'current_source' => get_option( 'csv_import_scheduled_source', '' ),
			'current_frequency' => get_option( 'csv_import_scheduled_frequency', '' ),
			'validation' => $validation,
			'notification_settings' => get_option( 'csv_import_notification_settings', [
				'email_on_success' => false,
				'email_on_failure' => true,
				'recipients' => [ get_option( 'admin_email' ) ]
			] ),
			'scheduled_imports' => $this->get_scheduled_imports_history()
		] );
	}

	/**
	 * Lädt Logs-Seiten-Daten
	 */
	private function load_logs_page_data() {
		$filter_level = sanitize_key( $_GET['level'] ?? 'all' );
		$page = max( 1, intval( $_GET['paged'] ?? 1 ) );
		$per_page = 50;

		$logs = [];
		$total_logs = 0;
		$error_stats = [];
		$health = [];

		if ( class_exists( 'CSV_Import_Error_Handler' ) ) {
			$all_logs = CSV_Import_Error_Handler::get_persistent_errors();
			
			// Filter anwenden
			if ( $filter_level !== 'all' ) {
				$all_logs = array_filter( $all_logs, function( $log ) use ( $filter_level ) {
					return ( $log['level'] ?? '' ) === $filter_level;
				} );
			}

			$total_logs = count( $all_logs );
			$logs = array_slice( $all_logs, ( $page - 1 ) * $per_page, $per_page );
		}

		if ( function_exists( 'csv_import_get_error_stats' ) ) {
			$error_stats = csv_import_get_error_stats();
		}

		if ( function_exists( 'csv_import_system_health_check' ) ) {
			$health = csv_import_system_health_check();
		}

		$GLOBALS['csv_import_admin_data'] = array_merge( $GLOBALS['csv_import_admin_data'], [
			'logs' => $logs,
			'filter_level' => $filter_level,
			'page' => $page,
			'per_page' => $per_page,
			'total_logs' => $total_logs,
			'total_pages' => ceil( $total_logs / $per_page ),
			'error_stats' => $error_stats,
			'health' => $health
		] );
	}

	/**
	 * Lädt erweiterte Einstellungen-Daten
	 */
	private function load_advanced_page_data() {
		$advanced_settings = get_option( 'csv_import_advanced_settings', $this->get_default_advanced_settings() );

		$GLOBALS['csv_import_admin_data'] = array_merge( $GLOBALS['csv_import_admin_data'], [
			'advanced_settings' => $advanced_settings,
			'system_info' => $this->get_system_info(),
			'php_extensions' => get_loaded_extensions(),
			'wp_debug' => defined( 'WP_DEBUG' ) && WP_DEBUG
		] );
	}

	/**
	 * Lädt Werkzeuge-Seiten-Daten
	 */
	private function load_tools_page_data() {
		$GLOBALS['csv_import_admin_data'] = array_merge( $GLOBALS['csv_import_admin_data'], [
			'tools' => [
				'database_size' => $this->get_database_size(),
				'upload_dir_size' => $this->get_upload_dir_size(),
				'plugin_tables' => $this->get_plugin_tables(),
				'temp_files' => $this->get_temp_files_count(),
				'log_files' => $this->get_log_files_info()
			],
			'export_url' => wp_nonce_url(
				admin_url( 'admin.php?page=csv-import-tools&action=export_settings' ),
				'csv_import_export'
			)
		] );
	}

	/**
	 * Lädt Debug-Seiten-Daten
	 */
	private function load_debug_page_data() {
		$debug_info = [
			'plugin_status' => method_exists( 'CSV_Import_Pro', 'get_status' ) ? csv_import_pro()->get_status() : [],
			'wp_constants' => $this->get_wp_constants(),
			'server_info' => $_SERVER,
			'php_config' => [
				'version' => PHP_VERSION,
				'memory_limit' => ini_get( 'memory_limit' ),
				'max_execution_time' => ini_get( 'max_execution_time' ),
				'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
				'post_max_size' => ini_get( 'post_max_size' )
			],
			'hooks_info' => $this->get_hooks_debug_info()
		];

		$GLOBALS['csv_import_admin_data'] = array_merge( $GLOBALS['csv_import_admin_data'], [
			'debug_info' => $debug_info,
			'can_debug' => current_user_can( 'manage_options' ) && defined( 'CSV_IMPORT_DEBUG' ) && CSV_IMPORT_DEBUG
		] );
	}

	// ===================================================================
	// FORMULAR-VERARBEITUNG
	// ===================================================================

	/**
	 * Verarbeitet Einstellungsformular-Eingaben
	 */
	private function handle_settings_form_submission() {
		if ( ! isset( $_POST['submit'] ) || ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'csv_import_settings-options' ) ) {
			return;
		}

		// WordPress Settings API übernimmt die Verarbeitung
		// Zusätzliche Validierung hier möglich
		$this->validate_and_save_settings();
	}

	/**
	 * Verarbeitet Backup-Aktionen
	 */
	private function handle_backup_actions() {
		if ( ! isset( $_POST['action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_key( $_POST['action'] );

		switch ( $action ) {
			case 'rollback_import':
				if ( wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'csv_import_rollback' ) ) {
					$this->handle_rollback_action();
				}
				break;

			case 'cleanup_backups':
				if ( wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'csv_import_cleanup_backups' ) ) {
					$this->handle_cleanup_backups();
				}
				break;
		}
	}

	/**
	 * Verarbeitet Profil-Aktionen
	 */
	private function handle_profile_actions() {
		if ( ! isset( $_POST['action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_key( $_POST['action'] );

		switch ( $action ) {
			case 'save_profile':
				if ( wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'csv_import_save_profile' ) ) {
					$this->handle_save_profile();
				}
				break;

			case 'load_profile':
				if ( wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'csv_import_load_profile' ) ) {
					$this->handle_load_profile();
				}
				break;

			case 'delete_profile':
				if ( wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'csv_import_delete_profile' ) ) {
					$this->handle_delete_profile();
				}
				break;
		}
	}

	/**
	 * Verarbeitet Scheduling-Aktionen
	 */
	private function handle_scheduling_actions() {
		if ( ! isset( $_POST['action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_key( $_POST['action'] );

		switch ( $action ) {
			case 'schedule_import':
				if ( wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'csv_import_scheduling' ) ) {
					$this->handle_schedule_import();
				}
				break;

			case 'unschedule_import':
				if ( wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'csv_import_scheduling' ) ) {
					$this->handle_unschedule_import();
				}
				break;

			case 'update_notifications':
				if ( wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'csv_import_notification_settings' ) ) {
					$this->handle_update_notifications();
				}
				break;
		}
	}

	/**
	 * Verarbeitet Logs-Aktionen
	 */
	private function handle_logs_actions() {
		if ( ! isset( $_POST['action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_key( $_POST['action'] );

		if ( $action === 'clear_logs' && wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'csv_import_clear_logs' ) ) {
			$this->handle_clear_logs();
		}
	}

	/**
	 * Verarbeitet erweiterte Einstellungen
	 */
	private function handle_advanced_settings_submission() {
		if ( ! isset( $_POST['submit'] ) || ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'update-options' ) ) {
			return;
		}

		$this->save_advanced_settings();
	}

	/**
	 * Verarbeitet Werkzeuge-Aktionen
	 */
	private function handle_tools_actions() {
		if ( ! isset( $_GET['action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_key( $_GET['action'] );

		switch ( $action ) {
			case 'export_settings':
				if ( wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'csv_import_export' ) ) {
					$this->handle_export_settings();
				}
				break;

			case 'reset_plugin':
				if ( wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'csv_import_reset' ) ) {
					$this->handle_reset_plugin();
				}
				break;
		}
	}

	// ===================================================================
	// AJAX-HANDLER UND HILFSFUNKTIONEN (gekürzt für Platz)
	// ===================================================================

	/**
	 * Holt Standard-Einstellungen
	 */
	private function get_default_settings() {
		return function_exists( 'csv_import_get_config' ) ? csv_import_get_config() : [];
	}

	/**
	 * Holt Standard-Wert für eine Einstellung
	 */
	private function get_default_value( $key ) {
		return function_exists( 'csv_import_get_default_value' ) ? csv_import_get_default_value( $key ) : '';
	}

	/**
	 * Holt erweiterte Standard-Einstellungen
	 */
	private function get_default_advanced_settings() {
		return [
			'batch_size' => 25,
			'performance_logging' => true,
			'max_errors_per_level' => [
				'critical' => 1,
				'error' => 10,
				'warning' => 50
			],
			'csv_preprocessing' => [
				'remove_empty_rows' => true,
				'trim_values' => true,
				'convert_encoding' => true
			],
			'security_settings' => [
				'strict_ssl_verification' => true,
				'allowed_file_extensions' => [ 'csv', 'txt' ],
				'max_file_size_mb' => 50
			],
			'backup_retention_days' => 30
		];
	}

	/**
	 * Sanitiert Einstellungen
	 */
	public function sanitize_settings( $settings ) {
		return array_map( 'sanitize_text_field', $settings );
	}

	/**
	 * Sanitiert erweiterte Einstellungen
	 */
	public function sanitize_advanced_settings( $settings ) {
		$sanitized = [];
		$sanitized['batch_size'] = max( 1, min( 200, intval( $settings['batch_size'] ?? 25 ) ) );
		$sanitized['performance_logging'] = rest_sanitize_boolean( $settings['performance_logging'] ?? true );
		$sanitized['backup_retention_days'] = max( 1, min( 365, intval( $settings['backup_retention_days'] ?? 30 ) ) );
		return $sanitized;
	}

	/**
	 * JavaScript-Strings für Lokalisierung
	 */
	private function get_js_strings() {
		return [
			'confirm_import' => __( 'Import wirklich starten?', 'csv-import' ),
			'confirm_rollback' => __( 'Rollback wirklich durchführen?', 'csv-import' ),
			'import_running' => __( 'Import läuft bereits', 'csv-import' ),
			'import_success' => __( 'Import erfolgreich abgeschlossen', 'csv-import' ),
			'import_error' => __( 'Import fehlgeschlagen', 'csv-import' )
		];
	}

	/**
	 * JavaScript-Konfiguration
	 */
	private function get_js_config() {
		return [
			'refresh_interval' => 5000,
			'max_retries' => 3,
			'timeout' => 30000,
			'auto_refresh_progress' => true,
			'show_debug_info' => defined( 'CSV_IMPORT_DEBUG' ) ? CSV_IMPORT_DEBUG : false
		];
	}

	/**
	 * System-Status für JavaScript
	 */
	private function get_system_status_for_js() {
		$health = function_exists( 'csv_import_system_health_check' ) ? csv_import_system_health_check() : [];
		return [
			'overall_status' => empty( array_filter( $health, function( $status ) { return $status === false; } ) ),
			'memory_ok' => $health['memory_ok'] ?? true,
			'disk_space_ok' => $health['disk_space_ok'] ?? true,
			'permissions_ok' => $health['permissions_ok'] ?? true
		];
	}

	/**
	 * Fügt Dashboard-Widget hinzu falls gewünscht
	 */
	private function maybe_add_dashboard_widget() {
		if ( apply_filters( 'csv_import_show_dashboard_widget', true ) ) {
			add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_widget' ] );
		}
	}

	/**
	 * Fügt Dashboard-Widget hinzu
	 */
	public function add_dashboard_widget() {
		wp_add_dashboard_widget(
			'csv_import_dashboard_widget',
			__( 'CSV Import Pro Status', 'csv-import' ),
			[ $this, 'render_dashboard_widget' ]
		);
	}

	/**
	 * Rendert Dashboard-Widget
	 */
	public function render_dashboard_widget() {
		if ( function_exists( 'csv_import_dashboard_widget' ) ) {
			csv_import_dashboard_widget();
		} else {
			echo '<p>' . __( 'CSV Import Pro ist aktiv.', 'csv-import' ) . '</p>';
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=csv-import' ) ) . '" class="button">' . __( 'Import starten', 'csv-import' ) . '</a></p>';
		}
	}

	/**
	 * Lädt Hauptseiten-Hook
	 */
	public function load_main_page() {
		do_action( 'csv_import_load_main_page' );
	}

	/**
	 * Lädt Unterseiten-Hook
	 */
	public function load_submenu_page() {
		do_action( 'csv_import_load_submenu_page' );
	}

	/**
	 * Zeigt System-Warnungen
	 */
	private function show_system_warnings() {
		if ( ! function_exists( 'csv_import_system_health_check' ) ) {
			return;
		}

		$health = csv_import_system_health_check();
		$issues = array_filter( $health, function( $status ) { return $status === false; } );

		if ( empty( $issues ) ) {
			return;
		}

		$warning_messages = [
			'memory_ok' => __( 'Niedriges Memory Limit', 'csv-import' ),
			'disk_space_ok' => __( 'Wenig freier Speicherplatz verfügbar', 'csv-import' ),
			'permissions_ok' => __( 'Dateiberechtigungen-Probleme erkannt', 'csv-import' ),
			'php_version_ok' => __( 'PHP-Version ist veraltet', 'csv-import' ),
			'wp_version_ok' => __( 'WordPress-Version ist veraltet', 'csv-import' ),
			'curl_ok' => __( 'cURL-Erweiterung nicht verfügbar', 'csv-import' ),
			'import_locks' => __( 'Import-Sperren aktiv', 'csv-import' ),
			'stuck_processes' => __( 'Hängende Import-Prozesse erkannt', 'csv-import' )
		];

		echo '<div class="notice notice-warning">';
		echo '<p><strong>' . __( 'CSV Import Pro - System-Warnungen:', 'csv-import' ) . '</strong></p>';
		echo '<ul style="margin-left: 20px;">';
		
		foreach ( $issues as $issue => $status ) {
			if ( isset( $warning_messages[ $issue ] ) ) {
				echo '<li>' . esc_html( $warning_messages[ $issue ] ) . '</li>';
			}
		}
		
		echo '</ul>';
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=csv-import-advanced' ) ) . '" class="button">' . __( 'System-Details anzeigen', 'csv-import' ) . '</a></p>';
		echo '</div>';
	}

	// WEITERE HILFSFUNKTIONEN (vereinfacht)
	private function get_file_status_info() { return []; }
	private function get_scheduled_imports_history() { return []; }
	private function get_system_info() { return []; }
	private function get_wp_constants() { return []; }
	private function get_hooks_debug_info() { return []; }
	private function validate_and_save_settings() {}
	private function handle_rollback_action() {}
	private function handle_cleanup_backups() {}
	private function handle_save_profile() {}
	private function handle_load_profile() {}
	private function handle_delete_profile() {}
	private function handle_schedule_import() {}
	private function handle_unschedule_import() {}
	private function handle_update_notifications() {}
	private function handle_clear_logs() {}
	private function save_advanced_settings() {}
	private function handle_export_settings() {}
	private function handle_reset_plugin() {}
	private function ajax_test_connection() {}
	private function ajax_export_settings() {}
	private function ajax_import_settings() {}
	private function ajax_get_system_info() {}
	private function ajax_reset_plugin() {}
	private function get_database_size() { return 'Unbekannt'; }
	private function get_upload_dir_size() { return 'Unbekannt'; }
	private function get_plugin_tables() { return []; }
	private function get_temp_files_count() { return 0; }
	private function get_log_files_info() { return []; }
}
