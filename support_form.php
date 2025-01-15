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
 * Version of filter_eduportal.
 *
 * @package    filter_faq
 * @copyright  2023 Austrian Federal Ministry of Education
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/filelib.php');

$PAGE->set_url('/filter/faq/support_form.php');
$PAGE->set_context(context_system::instance());

class filter_faq_support_form extends \moodleform {
    const FILE_EXTENSIONS = [
        '.jpg',
        '.jpeg',
        '.pdf',
    ];

    function __construct(private ?object $user, private object $data, private array $orgids) {
        parent::__construct();
    }

    function definition() {
        global $DB;

        $mform = $this->_form;

        $addStatic = function($title, $value) use ($mform) {
            $mform->addElement('static', md5($title), $title, '<div style="padding-top: calc(0.375rem + 1px)">' . $value . '</div>');
        };

        if (!empty($this->data->name)) {
            $addStatic(get_string('name'), $this->data->name);
        } else {
            $mform->addElement('text', 'name', get_string('name'));
            $mform->addRule('name', get_string('required'), 'required');
            $mform->setType('name', PARAM_TEXT);
        }

        if (!empty($this->data->email)) {
            $addStatic(get_string('email'), $this->data->email);
        } else {
            $mform->addElement('text', 'email', get_string('email'));
            $mform->addRule('email', get_string('required'), 'required');
            $mform->setType('email', PARAM_EMAIL);
        }

        if (empty($this->data->personalnummer)) {
            $mform->addElement('text', 'personalnummer', 'Personalnummer (Bedienstete)');
            $mform->setType('personalnummer', PARAM_TEXT);
        }


        if ($this->orgids) {
            $inputs = [];

            foreach ($this->orgids as $value => $title) {
                $input = $mform->createElement('checkbox', $value, $title);
                $input->updateAttributes(['value' => $value]);
                $inputs[] = $input;
            }

            $input = $mform->createElement('checkbox', 'other', 'andere');
            $input->updateAttributes(['value' => 'other']);
            $inputs[] = $input;

            $mform->addGroup($inputs, 'orgids', 'Schule(n)',
                // abstand zwischen den checkboxen und jede checkbox in einer eigenen Zeile (weil container display=flex)
                '<div style="height: 5px; width: 100%; overflow: hidden"></div>');

            $orgid_title = '';
            $mform->hideIf('orgid', 'orgids[other]', 'notchecked');
        } else {
            $orgid_title = 'Schule';
        }

        $areanames = [0 => ''];
        $orgs = $DB->get_records_select('local_eduportal_org', "", [], 'orgid ASC',);
        foreach ($orgs as $org) {
            $fields = [
                $org->orgid,
                !empty($org->name) ? $org->name : $org->officialname,
                $org->email,
                $org->street,
                $org->zip,
                $org->city,
            ];
            $areanames[$org->orgid] = implode(', ', array_filter($fields));
        }
        $options = array(
            'multiple' => false,
            'noselectionstring' => get_string('user:datarequest:selectorg', 'local_eduportal'),
        );
        $mform->addElement('autocomplete', 'orgid', $orgid_title, $areanames, $options);
        $mform->setType('orgid', PARAM_TEXT);

        /*
        $bundeslaender = [
            'keine Angabe' => 'keine Angabe',
            'Burgenland' => 'Burgenland',
            'Kärnten' => 'Kärnten',
            'Niederösterreich' => 'Niederösterreich',
            'Oberösterreich' => 'Oberösterreich',
            'Salzburg' => 'Salzburg',
            'Steiermark' => 'Steiermark',
            'Tirol' => 'Tirol',
            'Vorarlberg' => 'Vorarlberg',
            'Wien' => 'Wien',
        ];
        $mform->addElement('select', 'bundesland', 'Bundesland', $bundeslaender);
        $mform->setType('bundesland', PARAM_TEXT);
        */

        $mform->addElement('text', 'subject', get_string('subject'));
        $mform->addRule('subject', get_string('required'), 'required');
        $mform->setType('subject', PARAM_TEXT);

        $mform->addElement('textarea', 'description', get_string('description'));
        $mform->addRule('description', get_string('required'), 'required');
        $mform->setType('description', PARAM_TEXT);

        // $mform->addElement('filemanager', 'files', 'Anhänge');

        $mform->addElement('static', 'files_static', 'Anhänge', '<input type="file" name="files[]" accept="' . join(',', static::FILE_EXTENSIONS) . '" multiple>');
        // so fileuploads work:
        $mform->updateAttributes(['enctype' => 'multipart/form-data']);

        if (!$this->user) {
            $element = new \local_captcha\captcha_form_element('', '', ['set_captcha_used' => false]);
            $mform->addElement($element);
        }

        $this->add_action_buttons(false, 'Anfrage absenden');

    }

    function display() {
        parent::display();

        ?>
        <style>
            .mform .felement input[type="text"].form-control {
                /*width: 100%;*/
            }

            input[type="text"][name="subject"] {
                width: 100%;
            }

            #fgroup_id_orgids + #fitem_id_orgid {
                margin-top: -10px !important;
            }
        </style>
        <?php
    }

    function _validate_files(&$files) {
        // do nothing to disable moodle file validation
        return true;
    }
}

if (isloggedin() && !isguestuser()) {
    $user = $USER;
} else {
    $user = null;
}

$data = (object)[
    'name' => $user ? fullname($user) : null,
    'email' => $user ? $user->email : null,
    // 'personalnummer' => 'dfdfss',
];

$orgids = [];
$personalnummern = [];
if ($user) {
    $orgs = local_eduportal\api\org::get_userorgs($user->id);
    foreach ($orgs as $org) {
        $orgids[$org->orgid] = $org->name;
    }

    \local_eduportal\api\user::attach_additional_data($user);
    $sapids = array_filter(array_map(
        fn($externalid) => $externalid->fieldname == 'sapid' ? $externalid->externalid : null
        , $user->eduportalexternalids
    ));
    if ($sapids) {
        $data->personalnummer = join(', ', $sapids);
    }
}

// local_eduportal\api\user::is_staff()

$form = new filter_faq_support_form($user, $data, $orgids);

$error = '';
$show_success = false;

if ($fromform = $form->get_data()) {
    $data = (object)array_merge((array)$data, (array)$fromform);
    // does not work:
    // $files = file_get_all_files_in_draftarea($data->files);

    // only for logged in
    // $fs = get_file_storage();
    // $usercontext = context_user::instance($USER->id);
    // $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->files, 'id', false);

    $seed = '';

    if (!empty($_FILES['files'])) {
        $post_data = [];

        $c = new curl();

        // $file = curl_file_create($tempFilePath, 'text/plain', 'example.txt');

        $file_cnt = 0;
        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['files']['error'][$key] == 4) {
                // kein upload
                continue;
            }

            if ($_FILES['files']['error'][$key] != 0) {
                $error = file_get_upload_error($_FILES['files']['error'][$key]);
                break;
            }

            $regexp = '!(' . str_replace('.', '\.', join('|', filter_faq_support_form::FILE_EXTENSIONS)) . ')$!';
            if (!preg_match($regexp, strtolower($_FILES['files']['name'][$key]))) {
                $error = 'Dateiendung nicht erlaubt: ' . $_FILES['files']['name'][$key];
                break;
            }

            $post_data["files[{$file_cnt}]"] = curl_file_create($tmp_name, $_FILES['files']['type'][$key], $_FILES['files']['name'][$key]);
            $file_cnt++;
        }

        // foreach (array_values($files) as $i => $file) {
        //     $post_data['files[' . $i . ']'] = $file;
        // }

        // $path = $this->get_local_path_from_storedfile($file, true);
        // $curlrequest->_tmp_file_post_params[$key] = curl_file_create($path, null, $file->get_filename());
        // $options = [];
        // $options['CURLOPT_POST'] = 1;
        // $options['CURLOPT_POSTFIELDS'] = $this->_tmp_file_post_params;
        // return $this->request($url, $options);

        if ($post_data && !$error) {
            $seed = microtime(true);

            $post_data = array_merge([
                'secret' => '1234', // TODO
                'seed' => $seed,
            ], $post_data);

            // $result = $c->post('http://localhost/moodle/filter/faq/redmine/post-file.php', $post_data);
            $result = $c->post('https://support.bildung.gv.at/support-form/post-file.php', $post_data);
            if ($result) {
                $result = json_decode($result);
            }

            if ($result?->type == 'success') {
                // ok
            } else {
                $error = $result?->error ?: 'Ein Fehler ist aufgetreten';
            }
        }
    }

    if (!$error) {
        // cf_4: Schulkennzahl(en) (Text) (gilt für alle Projekte, somit universell nutzbar)
        // cf_5: Kommentarstatus-Test (Text); hatte ich als $seed verwendet
        // cf_6: Personalnummer
        // cf_7: SKZ (Text) - stammt noch aus der Zeit, als wir eine SKZ verwendet haben
        // cf_8: Bundesland (Liste) - nur der Vollständigkeit halber, wird ja nicht mehr verwendet Liste mit den Werten

        if ($orgids) {
            // can be selected
            $selected_orgids = $data->orgids ?? [];
            if (in_array('other', $selected_orgids)) {
                $selected_orgids[] = $data->orgid;
            }
            // remove 'other' from selected_orgids
            $selected_orgids = array_filter($selected_orgids, fn($orgid) => $orgid != 'other');
        } else {
            // use input field
            $selected_orgids = [];
            if ($data->orgid) {
                $selected_orgids[] = $data->orgid;
            }
        }

        $post_data = [
            'issue[name]' => $data->name,
            'issue[email]' => $data->email,
            'issue[subject]' => $data->subject,
            'issue[description]' => $data->description,
            'issue[cf_4]' => '-', // unbekanntes Feld, das darf nicht leer sein?!?
            'issue[custom_field_values][4]' => join(', ', $selected_orgids),
            'issue[custom_field_values][5]' => $seed,
            'issue[custom_field_values][6]' => $data->personalnummer,
        ];

        if ($CFG->developermode ?? false) {
            echo '<pre>';
            var_dump($post_data);
            exit;
        }

        $c = new curl();
        $result = $c->post('https://support.bildung.gv.at/projects/1/helpdesk_forms', $post_data);

        if ($result) {
            $result = json_decode($result);
        }

        if ($result?->type == 'success') {
            $show_success = true;
        } else {
            $error = $result?->error ?: 'Ein Fehler ist aufgetreten';
        }
    }
}

echo $OUTPUT->header();

if ($show_success) {
    echo '<div class="alert alert-success" style="margin: 30px 0; font-weight: bold;">' .
        'Die Anfrage wurde übermittelt und Sie erhalten in Kürze eine Bestätigung per Email' .
        '</div>';
} else {
    if ($error) {
        echo '<div class="alert alert-warning" style="margin: 30px 0; font-weight: bold;">' .
            $error .
            '</div>';
    }

    $form->display();
}

echo $OUTPUT->footer();
