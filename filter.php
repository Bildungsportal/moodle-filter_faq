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

defined('MOODLE_INTERNAL') || die;

/**
 * This is the filter itself.
 *
 * @package    filter_faq
 * @copyright  2023 Austrian Federal Ministry of Education
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_faq extends moodle_text_filter {
        private int $maxdepth = 10;
    public function filter($text, array $options = array()) {
        if (strpos($text, "{faq:") === false) return $text;
        return $this->format($text, $options, 0, false);
    }
    private function format(string $text, array $options, int $depth, bool $morehelpshown): string {
        global $OUTPUT;
        $depth++;
        if ($depth > $this->maxdepth) return $text;

        preg_match_all(
                '~(?<={faq:).+?(?=})~',
                $text,
                $matches
        );

        $curlang = current_language();
        $secondarylang = \filter_faq\lib::default_lang();
        $langs = [$curlang];
        if ($curlang != $secondarylang) {
                $langs[] = $secondarylang;
        }

        foreach ($matches[0] as $match) {
                $search = "{faq:$match}";
                $match = array_map('trim', explode(":", $match));
                $path = $match[0];
                $type = count($match) == 2 ? $match[1] : 'linkshort';
                $pathid = \filter_faq\lib::get_pathid($path);
                if (empty($pathid)) {
                        $params = (object)[
                                'path' => $path,
                                'content' => $type,
                        ];
                        $text = str_replace($search, $OUTPUT->render_from_template('filter_faq/no_faq_found', $params), $text);
                } else {
                        $params = (object)[
                                'morehelpshown' => $morehelpshown ? 1 : 0,
                                'langarray' => "['" . implode("','", $langs) . "']",
                                'longdescription' => '',
                                'longtitle' => '',
                                'p' => $pathid,
                                'shortdescription' => '',
                                'shorttitle' => '',
                                'type_' . $type => 1,
                                'urlprimary' => (new \moodle_url('/filter/faq/page.php', ['p' => $pathid]))->out(),
                                'urlsecondary' => (new \moodle_url('/filter/faq/page.php', ['l' => $secondarylang, 'p' => $pathid]))->out(),
                        ];
                        switch ($type) {
                                case 'collapsiblelonglong':
                                        $params->longtitle = \filter_faq\lib::get_content($pathid, 'longtitle', $langs);
                                        $params->longdescription = \filter_faq\lib::get_content($pathid, 'longdescription', $langs);
                                        break;
                                case 'collapsiblelongshort':
                                        $params->longtitle = \filter_faq\lib::get_content($pathid, 'longtitle', $langs);
                                        $params->shortdescription = \filter_faq\lib::get_content($pathid, 'shortdescription', $langs);
                                        break;
                                case 'collapsibleshortlong':
                                        $params->shorttitle = \filter_faq\lib::get_content($pathid, 'shorttitle', $langs);
                                        $params->longdescription = \filter_faq\lib::get_content($pathid, 'longdescription', $langs);
                                        break;
                                case 'collapsibleshortshort':
                                        $params->shorttitle = \filter_faq\lib::get_content($pathid, 'shorttitle', $langs);
                                        $params->shortdescription = \filter_faq\lib::get_content($pathid, 'shortdescription', $langs);
                                        break;
                                case 'linklong':
                                case 'modallonglong':
                                case 'modallongshort':
                                case 'titlelong':
                                        $params->longtitle = \filter_faq\lib::get_content($pathid, 'longtitle', $langs);
                                        break;
                                case 'linkshort':
                                case 'modalshortlong':
                                case 'modalshortshort':
                                case 'titleshort':
                                        $params->shorttitle = \filter_faq\lib::get_content($pathid, 'shorttitle', $langs);
                                        break;
                                case 'textlong':
                                        $params->longdescription = \filter_faq\lib::get_content($pathid, 'longdescription', $langs);
                                        break;
                                case 'textshort':
                                case 'textshortonly':
                                        $params->shortdescription = \filter_faq\lib::get_content($pathid, 'shortdescription', $langs);
                                        break;
                        }
                        $elementtext = $OUTPUT->render_from_template('filter_faq/element', $params);
                        if (strpos($text, "{faq:") !== false) {
                                $morehelpshownsub = in_array($type, [ 'collapsiblelongshort', 'collapsibleshortshort', 'textshort' ]);
                                $elementtext = $this->format($elementtext, $options, $depth, $morehelpshownsub);
                        }
                        $text = str_replace($search, $elementtext, $text);
                }
        }
        return $text;
    }
}
