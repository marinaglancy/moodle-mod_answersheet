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
class mod_answersheet_report {
    public static function display($cm, $answersheet, $userid = 0) {
        global $OUTPUT;
        $attempts = self::get_all_attempts($cm, $answersheet, $userid);
        if (!$attempts) {
            return '';
        }
        $table = new html_table();
        $table->head = array(
            'Student', // TODO strings
            'Started',
            'Grade',
            'Percentage',
            'Answers'
        );
        if ($userid) {
            array_shift($table->head);
        }
        foreach ($attempts as $attempt) {
            $url = new moodle_url('/mod/answersheet/view.php', array('id' => $cm->id, 'attempt' => $attempt->id));
            $data = array(fullname($attempt),
                html_writer::link($url, userdate($attempt->timestarted, get_string('strftimedatetime', 'core_langconfig'))),
                mod_answersheet_attempt::convert_grade($answersheet, $attempt->grade, true),
                round($attempt->grade * 100, 2).'%',
                $attempt->answers);
            if ($userid) {
                array_shift($data);
            }
            $table->data[] = $data;
        }
        return $OUTPUT->heading('Completed attempts', 3). // TODO string
            html_writer::table($table);
    }

    protected static function get_all_attempts($cm, $answersheet, $userid = 0, $completedonly = true) {
        global $DB;
        $namefields = get_all_user_name_fields(true, 'u');
        $records = $DB->get_records_sql(
                'SELECT a.id, a.answers, a.timestarted, a.timecompleted, '.
                'a.grade, a.userid, '.$namefields.' '.
                'FROM {answersheet_attempt} a LEFT JOIN {user} u ON u.id = a.userid '.
                'WHERE answersheetid = :aid '.
                ($userid ? 'AND a.userid = :userid ' : '').
                ($completedonly ? 'AND timecompleted IS NOT NULL ' : '').
                'ORDER BY timestarted, id',
                array('aid' => $answersheet->id, 'userid' => $userid));
        $rv = array();
        foreach ($records as $record) {
            $rv[] = $record;
        }
        return $rv;
    }
}
