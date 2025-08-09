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
        require_once $plugin_path . 'includes/core/core-functions.php';
        require_once $plugin_path . 'includes/class-csv-import-error-handler.php';

        try {
            // Verzeichnisse erstellen
            $image_folder_path = get_option('csv_import_image_folder', 'wp-content/uploads/csv-import-images/');
            $directories = [
                ABSPATH . 'data/',
                ABSPATH . ltrim($image_folder_path, '/')
            ];

            foreach ($directories as $dir) {
                if (!file_exists($dir)) {
                    if (!wp_mkdir_p($dir) || !is_writable($dir)) {
                        throw new Exception("Konnte Verzeichnis nicht erstellen oder es ist nicht beschreibbar: $dir");
                    }

                    // .htaccess für Sicherheit hinzufügen
                    $htaccess_file = trailingslashit($dir) . '.htaccess';
                    if (!file_exists($htaccess_file)) {
                        @file_put_contents($htaccess_file, "Options -Indexes\nDeny from all");
                    }
                }
            }

            // Standard-Einstellungen setzen, falls nicht vorhanden
            $defaults_to_check = ['template_id', 'post_type', 'post_status', 'page_builder', 'required_columns'];
            foreach ($defaults_to_check as $key) {
                if (get_option('csv_import_' . $key) === false) {
                    update_option('csv_import_' . $key, csv_import_get_default_value($key));
                }
            }

            // Plugin-Version speichern
            update_option('csv_import_pro_version', '6.2');

            // Log-Eintrag
            CSV_Import_Error_Handler::handle(
                CSV_Import_Error_Handler::LEVEL_INFO,
                'CSV Import System V6.2 erfolgreich aktiviert'
            );

        } catch (Exception $e) {
            // Bei einem Fehler während der Aktivierung, das Plugin sofort wieder deaktivieren.
            deactivate_plugins(plugin_basename(CSV_IMPORT_PRO_PATH . 'csv-import-pro.php'));
            wp_die('Plugin-Aktivierung fehlgeschlagen: ' . esc_html($e->getMessage()));
        }
    }
}
