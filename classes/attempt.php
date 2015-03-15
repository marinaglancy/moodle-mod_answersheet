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
 *
 *
 * @package    mod_answersheet
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 *
 *
 * @package    mod_answersheet
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_answersheet_attempt {

    protected $id;
    protected $attempt;
    protected $cm;
    protected $answersheet;
    protected static $userattempts = array();

    protected function __construct($id, $cm, $answersheet) {
        global $DB;
        if (is_object($id)) {
            $this->attempt = $id;
            $this->id = $this->attempt->id;
        } else {
            $this->id = $id;
            $this->attempt = $DB->get_record('answersheet_attempt', array('id' => $id), '*', MUST_EXIST);
        }
        $this->cm = $cm;
        $this->answersheet = $answersheet;
    }

    public function __get($name) {
        if ($name === 'id' || $name === 'answersheet' || $name === 'cm' || $name === 'attempt') {
            return $this->$name;
        }
    }

    public static function find_incomplete_attempt($cm, $answersheet) {
        $attempts = self::get_user_attempts($cm, $answersheet);
        foreach ($attempts as $attempt) {
            if (!$attempt->attempt->timecompleted) {
                return $attempt;
            }
        }
        return null;
    }

    public static function can_start($cm, $answersheet) {
        if (!has_capability('mod/answersheet:submit',
                context_module::instance($cm->id), null/*, false*/)) {
            return false;
        }
        return self::find_incomplete_attempt($cm, $answersheet) ? false : true;
    }

    public static function start($cm, $answersheet) {
        global $DB, $USER;
        $id = $DB->insert_record('answersheet_attempt', array(
            'userid' => $USER->id,
            'answersheetid' => $answersheet->id,
            'timestarted' => time()
        ));
        return new self($id, $cm, $answersheet);
    }

    public static function get($id, $cm, $answersheet) {
        global $USER;
        try {
            $attempt = new self($id, $cm, $answersheet);
        } catch (Exception $e) {
            return null;
        }
        if ($attempt->attempt->userid === $USER->id) {
            return $attempt;
        }
        return null;
    }

    public static function get_user_attempts($cm, $answersheet) {
        global $DB, $USER;
        if (!array_key_exists($cm->id, self::$userattempts)) {
            $records = $DB->get_records('answersheet_attempt',
                    array('userid' => $USER->id,
                        'answersheetid' => $answersheet->id
                        ));
            self::$userattempts[$cm->id] = array();
            foreach ($records as $record) {
                self::$userattempts[$cm->id][] = new self($record, $cm, $answersheet);
            }
        }
        return self::$userattempts[$cm->id];
    }

    public static function parse_options($value) {
        return preg_split('/\s*,\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
    }

    public static function parse_answerslist($value) {
        return preg_split('/\s*[,|\n]\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
    }

    protected function save($rawanswers, $finish = true) {
        global $DB;
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
        $record = array('id' => $this->id,
                    'answers' => join(',', $answers));
        if ($finish) {
            $record['timecompleted'] = time();
            $record['grade'] = self::get_grade($answers);
        }
        $DB->update_record('answersheet_attempt', $record);
    }

    protected function get_grade($answers) {
        $count = 0;
        $correctanswers = self::parse_answerslist($this->answersheet->answerslist);
        foreach ($correctanswers as $i => $answer) {
            $count += (isset($answers[$i]) && ($answers[$i] === $answer)) ? 1 : 0;
        }
        return 1.0 * $count / $this->answersheet->questionscount;
    }

    public function display() {
        $form = new mod_answersheet_attempt_form(null, (object)array('attempt' => $this));
        $q = preg_split('/,/', $this->attempt->answers);
        //print_r($this->attempt);
        $form->set_data(array('q' => $q));
        if ($data = $form->get_data()) {
            self::save(!empty($data->q) ? $data->q : array(), isset($data->submitbutton));
            redirect(new moodle_url('/mod/answersheet/view.php', array('id' => $this->cm->id)));
        } else {
            $form->display();
        }
    }
}