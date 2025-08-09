<?php
/**
 * Behandelt die Aktivierungs- und Deinstallations-Logik des Plugins.
 * KORRIGIERTE VERSION: Lädt Abhängigkeiten explizit, um Aktivierungsfehler zu verhindern.
 */
class Installer {

    /**
     * Wird bei der Plugin-Aktivierung ausgeführt.
     */
    public static function activate() {
        // KORREKTUR: Alle notwendigen Abhängigkeiten direkt hier laden.
        // Dies stellt sicher, dass die Aktivierung unabhängig von der Lade-Reihenfolge funktioniert.
        $plugin_path = plugin_dir_path( __DIR__ );
        if ( file_exists( $plugin_path . 'includes/core/core-functions.php' ) ) {
            require_once $plugin_path . 'includes/core/core-functions.php';
        }
        if ( file_exists( $plugin_path . 'includes/class-csv-import-error-handler.php' ) ) {
            require_once $plugin_path . 'includes/class-csv-import-error-handler.php';
        }

        try {
            // Verzeichnisse erstellen
            $image_folder_path = get_option('csv_import_image_folder', 'wp-content/uploads/csv-import-images/');
            $directories = [
                ABSPATH . 'data/',
                ABSPATH . ltrim($image_folder_path, '/')
            ];

            foreach ($directories as $dir) {
                if (!is_dir($dir)) {
                    wp_mkdir_p($dir);
                }
                if (!is_writable($dir)) {
                     throw new Exception("Verzeichnis ist nicht beschreibbar: $dir");
                }
            }

            // Standard-Einstellungen setzen, falls nicht vorhanden
            $defaults_to_check = ['template_id', 'post_type', 'post_status', 'page_builder', 'required_columns'];
            foreach ($defaults_to_check as $key) {
                if (get_option('csv_import_' . $key) === false && function_exists('csv_import_get_default_value')) {
                    update_option('csv_import_' . $key, csv_import_get_default_value($key));
                }
            }

            // Plugin-Version speichern
            update_option('csv_import_pro_version', '6.2');

            // Log-Eintrag
            if (class_exists('CSV_Import_Error_Handler')) {
                CSV_Import_Error_Handler::handle(
                    CSV_Import_Error_Handler::LEVEL_INFO,
                    'CSV Import System V6.2 erfolgreich aktiviert'
                );
            }

        } catch (Exception $e) {
            // Bei einem Fehler während der Aktivierung, das Plugin sofort wieder deaktivieren.
            if(function_exists('deactivate_plugins')) {
                deactivate_plugins(plugin_basename(CSV_IMPORT_PRO_PATH . 'csv-import-pro.php'));
            }
            wp_die('Plugin-Aktivierung fehlgeschlagen: ' . esc_html($e->getMessage()));
        }
    }
}
