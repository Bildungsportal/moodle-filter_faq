<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    filter_faq
 * @copyright  2024 Austrian Federal Ministry of Education
 * @author     GTN solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_faq;

class hook_callbacks {
    public static function before_standard_head_html_generation($hook): void {
    global $PAGE, $DB;
    $p = optional_param('p', 0, PARAM_INT);
    if ($p) {
        $pathrecord = $DB->get_record('filter_faq', ['id' => $p], '*', MUST_EXIST);
        $language = current_language();

        $langs = [$language];
        $secondarylang = \filter_faq\lib::default_lang();
        if ($language != $secondarylang) {
            $langs[] = $secondarylang;
        }

        $params = (object)[
            'permalink' => \filter_faq\lib::permalink($pathrecord->path),
        ];
    
        $metaTagsJs = "
            var linkCanonical = document.createElement('link');
            linkCanonical.rel = 'canonical';
            linkCanonical.href = '{$params->permalink}';
            document.head.appendChild(linkCanonical);
        ";
    
        foreach (['de', 'en'] as $lang) {
            $alternateUrl = new \moodle_url('/filter/faq/page.php', [
                'p' => $p,
                'lang' => $lang
            ]);
            $metaTagsJs .= "
                var linkAlternateLang = document.createElement('link');
                linkAlternateLang.rel = 'alternate';
                linkAlternateLang.hreflang = '{$lang}';
                linkAlternateLang.href = '{$alternateUrl->out(false)}';
                document.head.appendChild(linkAlternateLang);
    
                var linkAlternateLangWithT = document.createElement('link');
                linkAlternateLangWithT.rel = 'alternate';
                linkAlternateLangWithT.hreflang = '{$lang}';
                linkAlternateLangWithT.href = '{$alternateUrl->out(false)}&t';
                document.head.appendChild(linkAlternateLangWithT);
            ";
        }
    
        // Inject the JavaScript into the page using js_init_code.
        $PAGE->requires->js_init_code($metaTagsJs, true);

        $question = \filter_faq\lib::get_content($p, 'longtitle', $langs);

        // FAQPage guidelines require questions to actually be a question, some FAQ pages have statements as titles
        if (substr($question, -1) === '?') {
            $options = [
                'newlines' => false,
                'noclean' => true,
                'trusted' => true,
            ];
            $shortdescription = format_text(\filter_faq\lib::get_content($p, 'shortdescription', $langs), FORMAT_HTML, $options);

            // Prefer shortdescription, fallback to longdescription
            if (substr($shortdescription, -11) === ':textshort}') {
                $answer = format_text(\filter_faq\lib::get_content($p, 'longdescription', $langs), FORMAT_HTML, $options);

                // Skip if no answer found
                if (substr($answer, -10) === ':textlong}') {
                    return;
                }
            } else {
                $answer = $shortdescription;
            }

            $structured_data = [
                "@context" => "https://schema.org",
                "@type" => "FAQPage",
                "mainEntity" => [
                    [
                        "@type" => "Question",
                        "name" => $question,
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => $answer
                        ]
                    ]
                ]
            ];

            $json_ld = json_encode($structured_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            $PAGE->requires->js_init_code("
                var script = document.createElement('script');
                script.type = 'application/ld+json';
                script.text = " . json_encode($json_ld) . ";
                document.head.appendChild(script);
            ", true);
        }
    }

        $PAGE->requires->css('/filter/faq/style/faq.css?2025041700');
    }
}
