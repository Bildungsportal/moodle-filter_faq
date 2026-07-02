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

namespace filter_faq;

defined('MOODLE_INTERNAL') || die;

/**
 * This is the filter itself.
 *
 * @package    filter_faq
 * @copyright  2023 Austrian Federal Ministry of Education
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends \core_filters\text_filter {
    protected static array $force_languages = [];
    protected static array $feature_enabled = [];
    private static int $currentdepth = 0;
    private static int $maxdepth = 10;
    private static bool $morehelpshown = false;

    /**
     * Filter a text for {faq:*}-occurences.
     * @param $text
     * @param array $localconfig
     * @return string
     * @throws \moodle_exception
     */
    public function filter($text, array $localconfig = []) {
        if (strpos($text, "{faq:") === false)
            return $text;
        return $this->format($text);
    }

    private function format(string $text, array $options = []): string {
        global $CFG, $OUTPUT;
        static::$currentdepth++;
        if (static::$currentdepth > static::$maxdepth)
            return $text;

        preg_match_all(
            '~(?<={faq:).+?(?=})~',
            $text,
            $matches
        );
        // Set the languages for this filter-tree
        if (count(static::$force_languages) > 0) {
            // Languages are forced.
            $languages = static::$force_languages;
        } else {
            $current_language = current_language();
            $secondary_language = \filter_faq\lib::default_lang();
            $languages = [$current_language];
            if ($current_language != $secondary_language) {
                $languages[] = $secondary_language;
            }
        }

        foreach ($matches[0] as $match) {
            $search = "{faq:$match}";
            $match = array_map('trim', explode(":", $match));
            $path = $match[0];
            switch ($path) {
                case 'call':
                    $paramarray = explode('~', $match[1]);
                    $class = array_shift($paramarray);
                    if (!class_exists($class)) {
                        $elementtext = \get_string_manager()->get_string("callable:class_missing", 'filter_faq', ['class' => $class], $languages[0]);
                    } elseif (!is_subclass_of($class, \filter_faq\callable_class::class)) {
                        // implementiert interface callable_class nicht
                        $elementtext = \get_string_manager()->get_string("callable:class_missing", 'filter_faq', ['class' => $class], $languages[0]);
                    } else {
                        $elementtext = $class::filter_faq_call($paramarray);
                    }
                    break;
                case 'feature':
                    $elementtext = "";
                    $paramarray = explode('~', $match[1]);
                    $feature = array_shift($paramarray);
                    switch ($feature) {
                        case "parallax":
                            if (empty(static::$feature_enabled[$feature])) {
                                static::$feature_enabled[$feature] = true;
                                // Increment sourceversion if sources have changed. This forces any cache to re-validate.
                                $sourceversion = 2025070900;
                                $elementtext .=  "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$CFG->wwwroot}/filter/faq/style/parallax/parallax.css?{$sourceversion}\">\n";
                                $elementtext .=  "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$CFG->wwwroot}/filter/faq/thirdparty/aos/aos.css?{$sourceversion}\">\n";
                                $elementtext .=  "<script src=\"{$CFG->wwwroot}/filter/faq/thirdparty/aos/aos.js?{$sourceversion}\"></script>\n";
                                $elementtext .=  "<script src=\"{$CFG->wwwroot}/filter/faq/script/parallax/parallax.js?{$sourceversion}\"></script>\n";
                            }
                            break;
                    }
                    break;
                case 'stringlib':
                    $strinfo = explode('~', implode(':', array_slice($match, 1)));
                    if (count($strinfo) < 2) {
                        $elementtext = \get_string_manager()->get_string('stringlib:invalid_path', 'filter_faq', ['match' => implode('~', $strinfo)], $languages[0]);
                    } else {
                        $textid = $strinfo[0];
                        $component = $strinfo[1];
                        $default = count($strinfo) > 2 ? $strinfo[2] : '';
                        $elementtext = \filter_faq\stringlib::get_string($textid, $component, null, $default, $languages);
                    }
                    break;
                default:
                    $type = count($match) == 2 ? $match[1] : 'linkshort';

                    try {
                        $pathid = \filter_faq\lib::get_pathid($path);
                    } catch (\moodle_exception $e) {
                        if ($CFG->developermode) {
                            $text = str_replace($search, '[FAQ-DEV,tag:' . $search . ',error:' . $e->getMessage() . ']', $text);
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
                            'langarray' => "['" . implode("','", $languages) . "']",
                            'longdescription' => '',
                            'longtitle' => '',
                            'p' => $pathid,
                            'shortdescription' => '',
                            'shorttitle' => '',
                            'type_' . $type => 1,
                            'urlprimary' => (new \moodle_url('/filter/faq/page.php', ['p' => $pathid]))->out(),
                            'urlsecondary' => (new \moodle_url('/filter/faq/page.php', ['l' => $secondary_language, 'p' => $pathid]))->out(),
                        ];
                        switch ($type) {
                            case 'collapsiblelonglong':
                                $params->longtitle = \filter_faq\lib::get_content($pathid, 'longtitle', $languages);
                                $params->longdescription = \filter_faq\lib::get_content($pathid, 'longdescription', $languages);
                                break;
                            case 'collapsiblelongshort':
                                $params->longtitle = \filter_faq\lib::get_content($pathid, 'longtitle', $languages);
                                $params->shortdescription = \filter_faq\lib::get_content($pathid, 'shortdescription', $languages);
                                break;
                            case 'collapsibleshortlong':
                                $params->shorttitle = \filter_faq\lib::get_content($pathid, 'shorttitle', $languages);
                                $params->longdescription = \filter_faq\lib::get_content($pathid, 'longdescription', $languages);
                                break;
                            case 'collapsibleshortshort':
                                $params->shorttitle = \filter_faq\lib::get_content($pathid, 'shorttitle', $languages);
                                $params->shortdescription = \filter_faq\lib::get_content($pathid, 'shortdescription', $languages);
                                break;
                            case 'linklong':
                            case 'modallonglong':
                            case 'modallongshort':
                            case 'titlelong':
                                $params->longtitle = \filter_faq\lib::get_content($pathid, 'longtitle', $languages);
                                break;
                            case 'linkshort':
                            case 'modalshortlong':
                            case 'modalshortshort':
                            case 'titleshort':
                                $params->shorttitle = \filter_faq\lib::get_content($pathid, 'shorttitle', $languages);
                                break;
                            case 'textlong':
                                $params->longdescription = \filter_faq\lib::get_content($pathid, 'longdescription', $languages);
                                break;
                            case 'textshort':
                            case 'textshortonly':
                                $params->shortdescription = \filter_faq\lib::get_content($pathid, 'shortdescription', $languages);
                                break;
                        }
                        $enablemorehelp = in_array($type, ['collapsiblelongshort', 'collapsibleshortshort', 'textshort']);
                        if (!static::$morehelpshown && $enablemorehelp) {
                            static::$morehelpshown = true;
                        }
                        $elementtext = $OUTPUT->render_from_template('filter_faq/element', $params);
                        $formatoptions = [
                            'newlines' => false,
                            'noclean' => true,
                            'trusted' => true,
                        ];
                        if ($type != 'linkurl') {
                            $elementtext = format_text($elementtext, FORMAT_HTML, $formatoptions);
                        }
                        static::$currentdepth--;
                        if ($enablemorehelp) {
                            static::$morehelpshown = false;
                        }
                    }
            }
            $text = str_replace($search, $elementtext ?? '', $text);
        }
        return $text;
    }
    public static function force_languages(array $languages): void {
        global $SESSION;
        static::$force_languages = $languages;
        if (count($languages) > 0) {
            $SESSION->lang = $languages[count($languages) - 1];
        } else {
            unset($SESSION->lang);
        }
    }
}
