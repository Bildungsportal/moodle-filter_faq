/**
 * @copyright  2023 Austrian Federal Ministry of Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/copy_to_clipboard'], function() {
    return {
        init: function() {
            // Das Modul core/copy_to_clipboard initialisiert sich normalerweise selbst
            // oder stellt Funktionen bereit, die global/über das DOM verfügbar sind.
            // Durch das Laden hier stellen wir sicher, dass es im AMD-Pipeline-Kontext geladen wird.
        }
    };
});
