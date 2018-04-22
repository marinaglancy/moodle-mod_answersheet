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
 * File contains mod_answersheet_attempt class
 *
 * @package    mod_answersheet
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * mod_answersheet_attempt class allows to deal with one attempt.
 *
 * @package    mod_answersheet
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_answersheet_attempt {

    /** @var stdClass */
    protected $attempt;
    /** @var cm_info */
    protected $cm;
    /** @var stdClass */
    protected $answersheet;

    /**
     * Constructor
     *
     * @param stdClass $record
     * @param cm_info $cm
     * @param stdClass $answersheet
     */
    protected function __construct($record, $cm, $answersheet) {
        $this->attempt = $record;
        $this->cm = $cm;
        $this->answersheet = $answersheet;
    }

    /**
     * Magic getter
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        if ($name === 'answersheet' || $name === 'cm' || $name === 'attempt') {
            return $this->$name;
        } else if ($name === 'id') {
            return $this->attempt->id;
        }
    }

    /**
     * Retrieves incomplete attempt for the current user
     *
     * @param cm_info $cm
     * @param stdClass $answersheet
     * @return mod_answersheet_attempt|null
     */
    public static function find_incomplete_attempt($cm, $answersheet) {
        global $DB, $USER;
        if ($record = $DB->get_record_select('answersheet_attempt',
                'answersheetid = :aid AND userid = :userid AND timecompleted IS NULL',
                array('aid' => $answersheet->id, 'userid' => $USER->id))) {
            return new self($record, $cm, $answersheet);
        }
        return null;
    }

    /**
     * Does this user have completed attempts
     *
     * @param int $cmid
     * @return bool
     */
    public static function has_completed_attempts($instanceid) {
        global $DB, $USER;
        if (!isloggedin() || isguestuser()) {
            return false;
        }
        return $DB->record_exists_select('answersheet_attempt',
            'answersheetid = :aid AND userid = :userid AND timecompleted IS NOT NULL',
            array('aid' => $instanceid, 'userid' => $USER->id));
    }

    /**
     * Checks if current user is able to start a new attempt
     *
     * @param cm_info $cm
     * @param stdClass $answersheet
     * @return bool
     */
    public static function can_start($cm, $answersheet) {
        if (!has_capability('mod/answersheet:submit',
                context_module::instance($cm->id), null/*, false*/)) {
            return false;
        }
        return self::find_incomplete_attempt($cm, $answersheet) ? false : true;
    }

    /**
     * Starts a new attempt
     *
     * @param cm_info $cm
     * @param stdClass $answersheet
     * @return mod_answersheet_attempt
     */
    public static function start($cm, $answersheet) {
        global $DB, $USER;
        $id = $DB->insert_record('answersheet_attempt', array(
            'userid' => $USER->id,
            'answersheetid' => $answersheet->id,
            'timestarted' => time()
        ));
        $attempt = self::get($id, $cm, $answersheet);
        \mod_answersheet\event\attempt_created::create_from_record($attempt->attempt, $cm)->trigger();
        return $attempt;
    }

    /**
     * Retrieves the attempt by id
     *
     * @param int $id
     * @param cm_info $cm
     * @param stdClass $answersheet
     * @return mod_answersheet_attempt|null
     */
    public static function get($id, $cm, $answersheet) {
        global $DB;
        if ($record = $DB->get_record('answersheet_attempt', array('id' => $id))) {
            return new self($record, $cm, $answersheet);
        }
        return null;
    }

    /**
     * Checks if the current user is able to view this attempt
     *
     * @return bool
     */
    public function can_view() {
        global $USER;
        return $this->attempt->userid === $USER->id ||
                has_capability('mod/answersheet:viewreports',
                        context_module::instance($this->cm->id));
    }

    /**
     * Converts the grade to the scale string
     *
     * @param int $scaleid
     * @param float $grade
     * @return string
     */
    public static function get_scale($scaleid, $grade){
        global $DB;
        static $scales = array();
        if (!array_key_exists($scaleid, $scales)) {
            if ($scale = $DB->get_record('scale', array('id' => $scaleid))) {
                $scales[$scaleid] = make_menu_from_list($scale->scale);
            } else {
                $scales[$scaleid] = null;
            }
        }
        if ($scales[$scaleid]) {
            return $scales[$scaleid][$grade];
        } else {
            return '-';
        }
    }

    /**
     * Get the primary grade item for this module instance.
     *
     * @return stdClass The grade_item record
     */
    public static function get_grade_item($course, $instanceid) {
        global $CFG;
        require_once($CFG->libdir.'/gradelib.php');
        static $items = array();
        if (!array_key_exists($instanceid, $items)) {
            $params = array('itemtype' => 'mod',
                            'itemmodule' => 'answersheet',
                            'iteminstance' => $instanceid,
                            'courseid' => $course,
                            'itemnumber' => 0);
            $items[$instanceid] = grade_item::fetch($params);
        }
        return $items[$instanceid];
    }

    /**
     * Converts the grade from the percentage to the current gradeitem's format
     *
     * @param stdClass $answersheet
     * @param float $floatgrade
     * @param bool $human
     * @return string|float|int
     */
    public static function convert_grade($answersheet, $floatgrade, $human = false) {
        if ($answersheet->grade > 0) {
            $rv = $floatgrade * $answersheet->grade;
            if ($human) {
                $rv = round($rv, 2) .' / '.$answersheet->grade;
            }
        } else {
            $gradeitem = self::get_grade_item($answersheet->course, $answersheet->id);
            $rv = round($floatgrade *
                    ($gradeitem->grademax - $gradeitem->grademin) + $gradeitem->grademin, 0);
            if ($human) {
                $rv = self::get_scale(-$answersheet->grade, $rv);
            }
        }
        return $rv;
    }

    /**
     * Helper method for updating grades
     *
     * @param stdClass $answersheet
     * @param int $userid
     * @return array
     */
    public static function get_last_completed_attempt_grade($answersheet, $userid) {
        global $DB;
        if (!$answersheet->grade) {
            // Not graded.
            return null;
        }
        $rs = $DB->get_recordset_sql('SELECT userid AS id, userid, grade AS rawgrade '.
                'FROM {answersheet_attempt} '.
                'WHERE answersheetid=:aid AND timecompleted IS NOT NULL '.
                ($userid ? ' AND userid=:userid ' : '').
                'ORDER BY userid, timestarted DESC, id DESC',
                array('userid' => $userid, 'aid' => $answersheet->id));
        $rv = array();
        foreach ($rs as $record) {
            // This will return the array with one (last) attempt grade per user.
            if (!isset($rv[$record->id])) {
                if ($answersheet->grade > 0) {
                    $record->rawgrade = $record->rawgrade * $answersheet->grade;
                } else {
                    $gradeitem = self::get_grade_item($answersheet->course, $answersheet->id);
                    $record->rawgrade = round($record->rawgrade *
                            ($gradeitem->grademax - $gradeitem->grademin) + $gradeitem->grademin, 0);
                }

                $rv[$record->id] = $record;
            }
        }
        $rs->close();
        if ($userid && empty($rv)) {
            return array($userid => null);
        }
        return $rv;
    }

    /**
     * Parses the options list form the module settings
     *
     * @param string $value
     * @return array
     */
    public static function parse_options($value) {
        return preg_split('/\s*,\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Parses the correct answers list form the module settings
     *
     * @param string $value
     * @return array
     */
    public static function parse_answerslist($value) {
        return preg_split('/\s*[,|\n]\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Saves the attempt results
     *
     * @param array $rawanswers
     * @param bool $finish
     */
    protected function save($rawanswers, $finish = true) {
        global $DB, $CFG;
        $answers = array();
        $missed = false;
        for ($i=0; $i<$this->answersheet->questionscount; $i++) {
            if (isset($rawanswers[$i])) {
                $answers[$i] = $rawanswers[$i];
            } else {
                $answers[$i] = '';
                $missed = true;
            }
        }
        $this->attempt->answers = join(',', $answers);
        $record = array('id' => $this->attempt->id,
                    'answers' => $this->attempt->answers);
        if ($finish) {
            $DB->execute('UPDATE {answersheet_attempt} SET islast = ? '.
                    'WHERE answersheetid = ? AND userid = ? AND islast = ?',
                    array(0, $this->answersheet->id, $this->attempt->userid, 1));
            $record['timecompleted'] = $this->attempt->timecompleted = time();
            $record['grade'] = $this->attempt->grade = self::get_grade($answers);
            $record['islast'] = $this->attempt->islast = 1;
        }
        $DB->update_record('answersheet_attempt', $record);

        if ($finish) {
            \mod_answersheet\event\attempt_submitted::create_from_record($this->attempt, $this->cm)->trigger();
        } else {
            \mod_answersheet\event\attempt_saved::create_from_record($this->attempt, $this->cm)->trigger();
        }

        if ($finish) {
            answersheet_update_grades($this->answersheet, $this->attempt->userid);

            // Update completion state
            require_once($CFG->libdir.'/completionlib.php');
            $completion = new completion_info($this->cm->get_course());
            $completion->update_state($this->cm, COMPLETION_COMPLETE);
        }
    }

    /**
     * Calculates the grade from the answers
     *
     * @param array $answers
     * @return float
     */
    protected function get_grade($answers) {
        $count = 0;
        $correctanswers = self::parse_answerslist($this->answersheet->answerslist);
        foreach ($correctanswers as $i => $answer) {
            $count += (isset($answers[$i]) && ($answers[$i] === $answer)) ? 1 : 0;
        }
        return 1.0 * $count / $this->answersheet->questionscount;
    }

    /**
     * Prepares attempt information for display
     *
     * @return type
     */
    protected function attempt_info() {
        global $USER, $DB;
        $contents = html_writer::start_tag('ul');
        if ($this->attempt->userid != $USER->id) {
            $namefields = get_all_user_name_fields(true);
            $user = $DB->get_record('user', array('id' => $this->attempt->userid),
                    $namefields);
            $contents .= html_writer::tag('li', get_string('user') . ': '.fullname($user));
        }
        $contents .= html_writer::tag('li', get_string('started', 'answersheet') . ': ' .
                userdate($this->attempt->timestarted, get_string('strftimedatetime', 'core_langconfig')));
        if ($this->attempt->timecompleted) {
            $contents .= html_writer::tag('li', get_string('completed', 'answersheet') . ': ' .
                    userdate($this->attempt->timecompleted, get_string('strftimedatetime', 'core_langconfig')));
            $contents .= html_writer::tag('li', get_string('grade') . ': ' .
                    self::convert_grade($this->answersheet, $this->attempt->grade, true));
            $contents .= html_writer::tag('li', get_string('percentage', 'grades') . ': ' .
                    round($this->attempt->grade * 100, 2).'%');
        }
        $contents .= html_writer::end_tag('ul');
        return $contents;
    }

    /**
     * Displays the attempt (as a form or review)
     */
    public function display() {
        global $OUTPUT;

        $form = new mod_answersheet_attempt_form(null, (object)array('attempt' => $this));
        $q = preg_split('/,/', $this->attempt->answers);
        $form->set_data(array('q' => $q));
        $finish = false;
        if ($data = $form->get_data()) {
            $finish = isset($data->submitbutton);
            self::save(!empty($data->q) ? $data->q : array(), $finish);
            $form = new mod_answersheet_attempt_form(null, (object)array('attempt' => $this));

            if (!$finish) {
                redirect($this->cm->url);
            }
        }
        $contents = '';

        // Display question text.
        $options = array('noclean' => true, 'para' => false, 'filter' => true, 'context' => $this->cm->context, 'overflowdiv' => true);
        $question = file_rewrite_pluginfile_urls($this->answersheet->question, 'pluginfile.php', $this->cm->context->id,
            'mod_answersheet', 'question', 0);
        $question = format_text($question, $this->answersheet->questionformat, $options, null);
        $contents .= $OUTPUT->box($question);

        if ($finish) {
            $a = (object)[
                'grade' => self::convert_grade($this->answersheet, $this->attempt->grade, true),
                'percentage' => round($this->attempt->grade * 100, 2)
            ];
            $notification = get_string('yourgrade', 'mod_answersheet', $a);
            core\notification::add($notification, \core\output\notification::NOTIFY_INFO);
        } else {
            $contents .= $this->attempt_info();
        }
        $contents .= $form->render();
        if ($finish) {
            if ($a->percentage < 100 && !empty($this->answersheet->explanations)) {
                $options = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'context' => $this->cm->context);
                $explanations = file_rewrite_pluginfile_urls($this->answersheet->explanations, 'pluginfile.php',
                    $this->cm->context->id, 'mod_answersheet', 'explanations', 0);
                $contents .= trim(format_text($explanations, $this->answersheet->explanationsformat, $options));
            }
            $contents .= $OUTPUT->continue_button($this->cm->url);
        }
        return $contents;
    }
}