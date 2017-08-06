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
 * The main answersheet configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_answersheet
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_answersheet
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_answersheet_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('answersheetname', 'answersheet'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        $mform->addElement('header', 'answersheetfieldset', get_string('answersheetfieldset', 'answersheet'));

        $mform->addElement('text', 'questionscount', get_string('questionscount', 'answersheet'));
        $mform->setType('questionscount', PARAM_INT);
        $mform->addHelpButton('questionscount', 'questionscount', 'answersheet');

        $mform->addElement('text', 'questionsoptions', get_string('questionsoptions', 'answersheet'));
        $mform->setType('questionsoptions', PARAM_NOTAGS);
        $mform->setDefault('questionsoptions', 'A,B,C,D');
        $mform->addHelpButton('questionsoptions', 'questionsoptions', 'answersheet');

        $mform->addElement('textarea', 'answerslist', get_string('answerslist', 'answersheet'));
        $mform->setType('answerslist', PARAM_NOTAGS);
        $mform->addHelpButton('answerslist', 'answerslist', 'answersheet');

        $mform->addElement('editor', 'explanations_editor', get_string('explanations', 'answersheet'),
            array('rows' => 10), array('maxfiles' => EDITOR_UNLIMITED_FILES, 'context' => $this->context));
        $mform->addHelpButton('explanations_editor', 'explanations', 'answersheet');

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    public function data_preprocessing(&$default_values) {

        $context = $this->context;
        if ($this->context && $this->context->contextlevel != CONTEXT_MODULE) {
            $context = null;
        }
        $data = file_prepare_standard_editor((object)$default_values, 'explanations',
            array('maxfiles' => EDITOR_UNLIMITED_FILES, 'context' => $context),
            $context,
            'mod_answersheet', 'explanations', 0);
        $default_values = (array)$data;

    }

    /**
     * Display module-specific activity completion rules.
     * Part of the API defined by moodleform_mod
     * @return array Array of string IDs of added items, empty array if none
     */
    public function add_completion_rules() {
        $mform = $this->_form;

        $mform->addElement('advcheckbox', 'completionsubmit', null,
                get_string('completionsubmit', 'answersheet'));

        $mform->addElement('advcheckbox', 'completionpass', null, get_string('completionpass', 'answersheet'));
        $mform->addHelpButton('completionpass', 'completionpass', 'answersheet');

        return array('completionsubmit', 'completionpass');
    }

    /**
     * Called during validation. Indicates whether a module-specific completion rule is selected.
     *
     * @param array $data Input data (not yet validated)
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionpass']) || !empty($data['completionsubmit']);
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['questionscount']) || $data['questionscount'] <= 0) {
            $errors['questionscount'] = get_string('error_questionscount', 'answersheet');
        }

        $options = mod_answersheet_attempt::parse_options($data['questionsoptions']);
        if (empty($options) || count($options) != count(array_unique($options)) ||
                count($options) < 2 || count($options) > 20) {
            $errors['questionsoptions'] = get_string('error_questionsoptions', 'answersheet', 20);
        }

        if (empty($errors['questionscount']) && empty($errors['questionsoptions'])) {
            $answers = mod_answersheet_attempt::parse_answerslist($data['answerslist']);
            if (count($answers) != $data['questionscount']) {
                $errors['answerslist'] = get_string('error_answerslist_mismatch', 'answersheet');
            } else {
                $extraanswers = array_diff(array_unique($answers), $options);
                if (!empty($extraanswers)) {
                    $errors['answerslist'] = get_string('error_answerslist_invalid', 'answersheet',
                            join(',', $extraanswers));
                }
            }
        }
        return $errors;
    }
}
