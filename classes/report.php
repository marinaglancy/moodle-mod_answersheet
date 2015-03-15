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
 * File contains mod_answersheet_report class
 *
 * @package    mod_answersheet
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * mod_answersheet_report class
 *
 * @package    mod_answersheet
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_answersheet_report {

    /**
     *
     * @param cm_info $cm
     * @param stdClass $answersheet
     * @param int $userid
     * @return string
     */
    public static function display($cm, $answersheet, $userid = 0, $displaytype = 'completedonly') {
        global $OUTPUT, $USER;
        $attempts = self::get_all_attempts($cm, $answersheet, $userid, $displaytype);
        if (!$attempts && $displaytype === 'all') {
            return '';
        }
        $baseurl = new moodle_url('/mod/answersheet/view.php', array('id' => $cm->id, 'displaytype' => $displaytype));
        $canviewallusers = has_capability('mod/answersheet:viewreports', context_module::instance($cm->id));
        $table = new html_table();
        $alluserslink = '';
        if ($userid && $canviewallusers) {
            $alluserslink = '<br>'.html_writer::link($baseurl, get_string('showallusers', 'answersheet'));
        }
        $table->head = array(
            get_string('user') . $alluserslink,
            get_string('started', 'answersheet'),
            get_string('grade'),
            get_string('percentage', 'grades')
        );
        if ($userid && !$canviewallusers) {
            array_shift($table->head);
        }
        foreach ($attempts as $attempt) {
            $attempturl = new moodle_url($baseurl, array('attempt' => $attempt->id));
            $userurl = new moodle_url($baseurl, array('userid' => $attempt->userid, 'displaytype' => 'allattempts'));
            $data = array(html_writer::link($userurl, fullname($attempt)),
                html_writer::link($attempturl, userdate($attempt->timestarted, get_string('strftimedatetime', 'core_langconfig'))),
                $attempt->timecompleted ? mod_answersheet_attempt::convert_grade($answersheet, $attempt->grade, true) : '',
                $attempt->timecompleted ? (round($attempt->grade * 100, 2).'%') : '');
            if ($userid && !$canviewallusers) {
                array_shift($data);
            }
            if ($attempt->islast) {
                foreach ($data as $i => $v) {
                    $data[$i] = html_writer::tag('strong', $v);
                }
            }
            $table->data[] = $data;
        }
        return self::get_tabs($cm, $userid, $displaytype).
            html_writer::table($table);
    }

    protected static function get_tabs($cm, $userid, $displaytype) {
        global $OUTPUT;

        if ($displaytype === 'all') {
            return $OUTPUT->heading(get_string('attemptsummary', 'answersheet'), 3);
        }

        $baseurl = new moodle_url('/mod/answersheet/view.php', array('id' => $cm->id));
        if ($userid) {
            $baseurl->param('userid', $userid);
        }
        $tabs = array();

        $completedurl = new moodle_url($baseurl);
        $completedlabel = get_string('completedattempts', 'answersheet');
        $tabs[] = new tabobject('completedattempts', $completedurl, $completedlabel, $completedlabel, false);

        $lasturl = new moodle_url($baseurl, array('displaytype' => 'lastattempts'));
        $lastlabel = get_string('lastattempts', 'answersheet');
        $tabs[] = new tabobject('lastattempts', $lasturl, $lastlabel, $lastlabel, false);

        $allurl = new moodle_url($baseurl, array('displaytype' => 'allattempts'));
        $alllabel = get_string('allattempts', 'answersheet');
        $tabs[] = new tabobject('allattempts', $allurl, $alllabel, $alllabel, false);

        $currenturl = new moodle_url($baseurl, array('displaytype' => 'currentattempts'));
        $currentlabel = get_string('currentattempts', 'answersheet');
        $tabs[] = new tabobject('currentattempts', $currenturl, $currentlabel, $currentlabel, false);

        return print_tabs(array($tabs), $displaytype, null, null, true);

    }

    /**
     * Retrieves all attempts for the activity or for the user
     *
     * @param cm_info $cm
     * @param stdClass $answersheet
     * @param int $userid
     * @param bool $completedonly
     * @return array
     */
    protected static function get_all_attempts($cm, $answersheet, $userid = 0, $displaytype = 'completedattempts') {
        global $DB;
        $namefields = get_all_user_name_fields(true, 'u');
        $records = $DB->get_records_sql(
                'SELECT a.id, a.answers, a.timestarted, a.timecompleted, '.
                'a.grade, a.userid, a.islast, '.$namefields.' '.
                'FROM {answersheet_attempt} a LEFT JOIN {user} u ON u.id = a.userid '.
                'WHERE answersheetid = :aid '.
                ($userid ? 'AND a.userid = :userid ' : '').
                (($displaytype == 'completedattempts') ? 'AND timecompleted IS NOT NULL ' : '').
                (($displaytype == 'lastattempts') ? 'AND islast = 1 ' : '').
                (($displaytype == 'currentattempts') ? 'AND timecompleted IS NULL ' : '').
                'ORDER BY timestarted, id',
                array('aid' => $answersheet->id, 'userid' => $userid));
        $rv = array();
        foreach ($records as $record) {
            $rv[] = $record;
        }
        return $rv;
    }
}
