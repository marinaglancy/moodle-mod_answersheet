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
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_answersheet
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_answersheet_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {

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
        $mform->addHelpButton('name', 'answersheetname', 'answersheet');

        // Adding the standard "intro" and "introformat" fields.
        $this->add_intro_editor();

        $mform->addElement('header', 'answersheetfieldset', get_string('answersheetfieldset', 'answersheet'));

        $mform->addElement('text', 'questionscount', 'Number of questions');
        $mform->setType('questionscount', PARAM_INT);

        $mform->addElement('text', 'questionsoptions', 'Questions options');
        $mform->setType('questionsoptions', PARAM_NOTAGS);
        $mform->setDefault('questionsoptions', 'A,B,C,D');

        $mform->addElement('textarea', 'answerslist', 'List of answers');
        $mform->setType('answerslist', PARAM_NOTAGS);
        //C,C,B,A,B,B,C,D,A,C,C,B,C,C,A,B,C,C,C,C,D,D,A,B,A,D,C,B,A,C,C,A,A,C,B

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
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
}
