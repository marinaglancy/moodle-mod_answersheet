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
 * Prints a particular instance of answersheet
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_answersheet
 * @copyright  2015 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace answersheet with the name of your module and remove this line.

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir.'/completionlib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
if ($id) {
    $cm          = get_coursemodule_from_id('answersheet', $id, 0, false, MUST_EXIST);
    $course      = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $answersheet = $DB->get_record('answersheet', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $a           = required_param('a', PARAM_INT); // Answersheet ID.
    $answersheet = $DB->get_record('answersheet', array('id' => $a), '*', MUST_EXIST);
    $course      = $DB->get_record('course', array('id' => $answersheet->course), '*', MUST_EXIST);
    $cm          = get_coursemodule_from_instance('answersheet', $answersheet->id, $course->id, false, MUST_EXIST);
    $id          = $cm->id;
}

require_login($course, true, $cm);
$PAGE->set_activity_record($answersheet);

$attempt = optional_param('attempt', null, PARAM_NOTAGS);

$event = \mod_answersheet\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $answersheet);
$event->trigger();

$PAGE->set_url('/mod/answersheet/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($answersheet->name));
$PAGE->set_heading(format_string($course->fullname));

if (($attempt === 'new') && mod_answersheet_attempt::can_start($PAGE->cm, $answersheet)) {
    $attemptobj = mod_answersheet_attempt::start($PAGE->cm, $answersheet);
    $url = new moodle_url('/mod/answersheet/view.php', array('id' => $id, 'attempt' => $attemptobj->id));
    redirect($url);
}

// Update 'viewed' state if required by completion system.
$completion = new completion_info($course);
$completion->set_module_viewed($PAGE->cm);

$contents = '';

if (((int)$attempt) > 0) {
    // Display individual attempt.
    $attemptobj = mod_answersheet_attempt::get((int)$attempt, $PAGE->cm, $answersheet);
    if ($attemptobj && $attemptobj->can_view()) {
        $contents .= $attemptobj->display();
    }
} else {
    // Display current incompleted attampt.
    if ($attempt = mod_answersheet_attempt::find_incomplete_attempt($PAGE->cm, $answersheet)) {
        $url = new moodle_url('/mod/answersheet/view.php', array('id' => $id, 'attempt' => $attempt->id));
        $contents .= html_writer::tag('div', html_writer::link($url,
                get_string('continueattempt', 'answersheet')),
                array('class' => 'continueattempt'));
    }

    // Display link to start a new attempt.
    if (mod_answersheet_attempt::can_start($PAGE->cm, $answersheet)) {
        $url = new moodle_url('/mod/answersheet/view.php', array('id' => $id, 'attempt' => 'new'));
        $contents .= html_writer::tag('div', html_writer::link($url,
                get_string('startnerattempt', 'answersheet')),
                array('class' => 'startattempt'));
    }

    if (has_capability('mod/answersheet:viewreports', $PAGE->context)) {
        // View all attempts.
        $userid = optional_param('userid', 0, PARAM_INT);
        $contents .= mod_answersheet_report::display($cm, $answersheet, $userid);
    } else {
        // View own past attempts.
        $contents .= mod_answersheet_report::display($cm, $answersheet, $USER->id);
    }
}

// Output starts here.
echo $OUTPUT->header();

// Conditions to show the intro can change to look for own settings or whatever.
if ($answersheet->intro) {
    echo $OUTPUT->box(format_module_intro('answersheet', $answersheet, $cm->id),
            'generalbox mod_introbox', 'answersheetintro');
}

echo $contents;

// Finish the page.
echo $OUTPUT->footer();
