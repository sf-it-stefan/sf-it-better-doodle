<?php

namespace App;

class FormTranslations
{
    private static array $strings = [
        'en' => [
            // Public form
            'submit' => 'Submit',
            'update_response' => 'Update Response',
            'required_mark' => '*',
            'select_placeholder' => 'Select an option...',
            'select_all_that_apply' => 'Select all that apply',
            'select_one' => 'Select one option',
            'open_until' => 'Open until',
            'drop_image' => 'Drop an image or click to select',
            'image_formats' => 'JPEG, PNG, WebP',
            'use_this_crop' => 'Use this crop',
            'cancel' => 'Cancel',
            'change_image' => 'Change image',
            'image_too_large' => 'Image must be smaller than 10MB.',
            'invalid_image' => 'Please select a valid image file (JPEG, PNG, or WebP).',
            'file_max_size' => 'Max. 20MB',

            // Closed form
            'form_closed' => 'This form is closed',
            'form_closed_on' => 'This form closed on',
            'form_no_longer_accepting' => 'This form is no longer accepting responses.',
            'edit_link_note' => 'If you have an edit link, your existing response is still saved.',

            // Thank you page
            'thank_you_title' => "You're in!",
            'response_updated_title' => 'Response Updated!',
            'response_recorded' => 'Your response has been recorded.',
            'edit_prompt' => 'Want to change your answers later?',
            'copy' => 'Copy',
            'copied' => 'Copied!',
            'save_link_warning' => "Save this link — it won't be shown again and no account is needed.",

            // Duplicate warning
            'duplicate_title' => "We've already received a response from your network.",
            'duplicate_message' => 'Are you sure you want to submit another response?',
            'duplicate_continue' => 'Yes, fill out anyway',

            // Privacy / GDPR
            'privacy_notice' => 'By submitting this form, you agree that your data will be stored to process your response. All data will be automatically and permanently deleted 90 days after the form closes.',
            'privacy_notice_no_expiry' => 'By submitting this form, you agree that your data will be stored to process your response. The form administrator can delete all collected data at any time.',

            // Footer
            'powered_by' => 'Powered by SF-IT Better Doodle',
        ],

        'de' => [
            // Public form
            'submit' => 'Absenden',
            'update_response' => 'Antwort aktualisieren',
            'required_mark' => '*',
            'select_placeholder' => 'Option auswählen...',
            'select_all_that_apply' => 'Wähle alle zutreffenden aus',
            'select_one' => 'Wähle eine Option',
            'open_until' => 'Offen bis',
            'drop_image' => 'Bild hierher ziehen oder klicken',
            'image_formats' => 'JPEG, PNG, WebP',
            'use_this_crop' => 'Zuschnitt verwenden',
            'cancel' => 'Abbrechen',
            'change_image' => 'Bild ändern',
            'image_too_large' => 'Bild muss kleiner als 10MB sein.',
            'invalid_image' => 'Bitte eine gültige Bilddatei auswählen (JPEG, PNG oder WebP).',
            'file_max_size' => 'Max. 20MB',

            // Closed form
            'form_closed' => 'Dieses Formular ist geschlossen',
            'form_closed_on' => 'Dieses Formular wurde geschlossen am',
            'form_no_longer_accepting' => 'Dieses Formular nimmt keine Antworten mehr entgegen.',
            'edit_link_note' => 'Falls du einen Bearbeitungslink hast, ist deine Antwort weiterhin gespeichert.',

            // Thank you page
            'thank_you_title' => 'Du bist dabei!',
            'response_updated_title' => 'Antwort aktualisiert!',
            'response_recorded' => 'Deine Antwort wurde gespeichert.',
            'edit_prompt' => 'Möchtest du deine Antworten später ändern?',
            'copy' => 'Kopieren',
            'copied' => 'Kopiert!',
            'save_link_warning' => 'Speichere diesen Link — er wird nicht erneut angezeigt und es wird kein Konto benötigt.',

            // Duplicate warning
            'duplicate_title' => 'Wir haben bereits eine Antwort aus deinem Netzwerk erhalten.',
            'duplicate_message' => 'Möchtest du trotzdem eine weitere Antwort absenden?',
            'duplicate_continue' => 'Ja, trotzdem ausfüllen',

            // Privacy / GDPR
            'privacy_notice' => 'Mit dem Absenden dieses Formulars stimmst du zu, dass deine Daten zur Bearbeitung deiner Antwort gespeichert werden. Alle Daten werden automatisch und dauerhaft 90 Tage nach Schließung des Formulars gelöscht.',
            'privacy_notice_no_expiry' => 'Mit dem Absenden dieses Formulars stimmst du zu, dass deine Daten zur Bearbeitung deiner Antwort gespeichert werden. Der Formular-Administrator kann alle gesammelten Daten jederzeit löschen.',

            // Footer
            'powered_by' => 'Powered by SF-IT Better Doodle',
        ],
    ];

    public static function get(string $key, string $lang = 'en'): string
    {
        return static::$strings[$lang][$key] ?? static::$strings['en'][$key] ?? $key;
    }

    public static function all(string $lang = 'en'): array
    {
        return static::$strings[$lang] ?? static::$strings['en'];
    }

    public static function availableLanguages(): array
    {
        return [
            'en' => 'English',
            'de' => 'Deutsch',
        ];
    }
}
