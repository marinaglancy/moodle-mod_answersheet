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
 * File contains mod_answersheet_attempt_form class
 *
 * @package    mod_answersheet
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * mod_answersheet_attempt_form class
 *
 * @package    mod_answersheet
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_answersheet_attempt_form extends moodleform {
    /**
     * Form definition
     */
    protected function definition() {
        global $OUTPUT;
        $mform = $this->_form;
        $attempt = $this->_customdata->attempt;
        $answersheet = $attempt->answersheet;
        $questionscount = $answersheet->questionscount;
        $freeze = $attempt->attempt->timecompleted ? true : false;
        $questionsoptions = mod_answersheet_attempt::parse_options($answersheet->questionsoptions);
        if ($freeze) {
            $correctanswers = mod_answersheet_attempt::parse_answerslist($answersheet->answerslist);
            $answers = preg_split('/,/', $attempt->attempt->answers);
        }

        $mform->addElement('hidden', 'id', $attempt->cm->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'attempt', $attempt->id);
        $mform->setType('attempt', PARAM_NOTAGS);

        for ($i=0; $i<$questionscount; $i++) {
            $group = array();
            $answer = isset($answers[$i]) ? $answers[$i] : '';
            if ($freeze) {
                if ($answer === $correctanswers[$i]) {
                    $icon = 'i/grade_correct';
                    $alt = get_string('correct', 'answersheet');
                } else {
                    $icon = 'i/grade_incorrect';
                    $alt = get_string('wrong', 'answersheet');
                }
                $group[] = &$mform->createElement('static', $i.'-x', '',
                        $OUTPUT->pix_icon($icon, $alt));
            }
            for ($j=0; $j<count($questionsoptions); $j++) {
                if ($freeze) {
                    $sel = (isset($answers[$i]) && $answers[$i] == $questionsoptions[$j]) ? true : false;
                    $group[] = &$mform->createElement('static', $i.'-'.$j, '', $sel ? $questionsoptions[$j] : '');
                } else {
                    $group[] = &$mform->createElement('radio', $i, '', $questionsoptions[$j], $questionsoptions[$j]);
                }
            }
            $mform->addElement('group', 'q', $i + 1, $group, '&nbsp;&nbsp;&nbsp;&nbsp;');
        }
        if (!$freeze) {
            $this->add_abuttons();
        }
    }

    /**
     * Adds buttons to the form
     */
    function add_abuttons(){
        $mform =& $this->_form;
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('finish', 'answersheet'));
        $buttonarray[] = &$mform->createElement('submit', 'saveonly', get_string('saveonly', 'answersheet'));
        //$buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}
