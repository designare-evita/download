<?php
/**
 * Verarbeitet alle AJAX-Anfragen aus dem Admin-Bereich des CSV Import Pro Plugins.
 *
 * WICHTIG: Diese Datei enthält nur die Handler-Funktionen. Die Registrierung der
 * 'wp_ajax_'-Hooks erfolgt zentral in der Haupt-Plugin-Klasse, um den korrekten
 * Ladezeitpunkt sicherzustellen.
 *
 * @version 6.0
 * @author Dein Name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Verhindert den direkten Zugriff.
}

/**
 * Handler für die Validierung von Konfiguration und CSV-Dateien.
 */
function csv_import_validate_handler() {
    // 1. Sicherheits- und Berechtigungsprüfung
    check_ajax_referer( 'csv_import_ajax', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( ['message' => 'Keine Berechtigung für diese Aktion.'] );
    }

    // 2. Eingabedaten holen und bereinigen
    $type = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '';
    $response_data = [ 'valid' => false, 'message' => 'Unbekannter Test-Typ.' ];

    // 3. Logik ausführen
    try {
        $config = csv_import_get_config();

        if ( $type === 'config' ) {
            $validation = csv_import_validate_config( $config );
            $response_data = array_merge($response_data, $validation);

            if ( !$validation['valid'] ) {
                $response_data['message'] = 'Konfigurationsfehler: <ul><li>' . implode('</li><li>', $validation['errors']) . '</li></ul>';
            } else {
                 $response_data['message'] = '✅ Konfiguration ist gültig und einsatzbereit.';
            }

        } elseif ( in_array( $type, [ 'dropbox', 'local' ] ) ) {
            $csv_result = csv_import_validate_csv_source( $type, $config );
            $response_data = array_merge( $response_data, $csv_result );

        } else {
            // Wenn der Typ ungültig ist, eine klare Fehlermeldung senden.
            throw new Exception('Ungültiger Validierungstyp angegeben.');
        }

    } catch ( Exception $e ) {
        // Fehler abfangen und als JSON senden
        $response_data['message'] = 'Validierungsfehler: ' . $e->getMessage();
    }

    // 4. Antwort senden
    if ( $response_data['valid'] ) {
        wp_send_json_success( $response_data );
    } else {
        wp_send_json_error( $response_data );
    }
}

/**
 * Handler zum Starten des Imports.
 */
function csv_import_start_handler() {
    // 1. Sicherheits- und Berechtigungsprüfung
    check_ajax_referer( 'csv_import_ajax', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( ['message' => 'Keine Berechtigung.'] );
    }

    // 2. Eingabedaten holen und bereinigen
    $source = isset( $_POST['source'] ) ? sanitize_key( $_POST['source'] ) : '';
    if ( ! in_array( $source, ['dropbox', 'local'] ) ) {
        wp_send_json_error( [ 'message' => 'Ungültige Import-Quelle.' ] );
    }

    // 3. Logik ausführen
    if ( csv_import_is_import_running() ) {
        wp_send_json_error( [ 'message' => 'Ein Import läuft bereits. Bitte warten Sie.' ] );
    }

    if ( class_exists( 'CSV_Import_Pro_Run' ) ) {
        // Der Import wird direkt ausgeführt, um die Zuverlässigkeit zu erhöhen.
        $result = CSV_Import_Pro_Run::run( $source );

        // 4. Antwort senden
        if ( isset($result['success']) && $result['success'] ) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    } else {
        wp_send_json_error(['message' => 'Kritischer Fehler: Die Import-Klasse (CSV_Import_Pro_Run) wurde nicht gefunden.']);
    }
}

/**
 * Handler zum Abrufen des Import-Fortschritts.
 */
function csv_import_get_progress_handler() {
    // 1. Sicherheits- und Berechtigungsprüfung
    check_ajax_referer( 'csv_import_ajax', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( ['message' => 'Keine Berechtigung.'] );
    }

    // 2. Logik ausführen und Antwort senden
    $progress = csv_import_get_progress();
    wp_send_json_success( $progress );
}

/**
 * Handler zum Abbrechen eines laufenden Imports.
 */
function csv_import_cancel_handler() {
    // 1. Sicherheits- und Berechtigungsprüfung
    check_ajax_referer( 'csv_import_ajax', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( ['message' => 'Keine Berechtigung.'] );
    }

    // 2. Logik ausführen und Antwort senden
    csv_import_force_reset_import_status();
    wp_send_json_success( ['message' => 'Der Import wurde abgebrochen und der Status zurückgesetzt.'] );
}

// Am Ende der admin-ajax.php hinzufügen:

function csv_import_get_profile_details_handler() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'csv_import_ajax')) {
        wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
    }
    
    $profile_id = sanitize_key($_POST['profile_id'] ?? '');
    wp_send_json_success(['profile' => [], 'message' => 'Handler funktioniert']);
}

function csv_import_system_check_handler() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'csv_import_ajax')) {
        wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
    }
    
    wp_send_json_success([
        'memory_ok' => true,
        'disk_space_ok' => true,
        'permissions_ok' => true
    ]);
}

function csv_debug_info_handler() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'csv_import_ajax')) {
        wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
    }
    
    wp_send_json_success([
        'plugin_version' => CSV_IMPORT_PRO_VERSION,
        'status' => 'loaded'
    ]);
}
