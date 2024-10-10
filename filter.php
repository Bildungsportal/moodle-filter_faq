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
    private static int $currentdepth = 0;
    private static int $maxdepth = 10;
    private static bool $morehelpshown = false;

    public function filter($text, array $options = array()) {
        if (strpos($text, "{faq:") === false)
            return $text;
        return $this->format($text, $options);
    }

    private function format(string $text, array $options): string {
        global $CFG, $OUTPUT;
        static::$currentdepth++;
        if (static::$currentdepth > static::$maxdepth)
            return $text;

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
            if ($path == 'stringlib') {
                $strinfo = explode('~', implode(':', array_slice($match, 1)));
                if (count($strinfo) < 2) {
                    $elementtext = get_string('stringlib:invalid_path', 'filter_faq', [ 'match' => implode('~', $strinfo)]);
                } else {
                    $textid = $strinfo[0];
                    $component = $strinfo[1];
                    $default = count($strinfo) > 2 ? $strinfo[2] : '';
                    $elementtext = \filter_faq\stringlib::get_string($textid, $component, null, $default);
                }
            } else {
                $type = count($match) == 2 ? $match[1] : 'linkshort';

                try {
                    $pathid = \filter_faq\lib::get_pathid($path);
                } catch (\moodle_exception $e) {
                    if ($CFG->developermode) {
                        $text = str_replace($search, '[FAQ-DEV,tag:'.$search.',error:'.$e->getMessage().']', $text);
                        return $text;
                    } else {
                        throw $e;
                    }
                }
                if (empty($pathid)) {
                    $params = (object)[
                        'path' => $path,
                        'content' => $type,
                    ];
                    $text = str_replace($search, $OUTPUT->render_from_template('filter_faq/no_faq_found', $params), $text);
                } else {
                    $params = (object)[
                        'morehelpshown' => static::$morehelpshown ? 1 : 0,
                        'langarray' => "['".implode("','", $langs)."']",
                        'longdescription' => '',
                        'longtitle' => '',
                        'p' => $pathid,
                        'shortdescription' => '',
                        'shorttitle' => '',
                        'type_'.$type => 1,
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
                    $enablemorehelp = in_array($type, ['collapsiblelongshort', 'collapsibleshortshort', 'textshort']);
                    if (!static::$morehelpshown && $enablemorehelp) {
                        static::$morehelpshown = true;
                    }
                    $elementtext = $OUTPUT->render_from_template('filter_faq/element', $params);
                    $options = [
                        'newlines' => false,
                        'noclean' => true,
                        'trusted' => true,
                    ];
                    if ($type != 'linkurl') {
                        $elementtext = format_text($elementtext, FORMAT_HTML, $options);
                    }
                    static::$currentdepth--;
                    if ($enablemorehelp) {
                        static::$morehelpshown = false;
                    }
                }
            }
            $text = str_replace($search, $elementtext, $text);
        }
        return $text;
    }
}
