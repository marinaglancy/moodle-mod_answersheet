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
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace answersheet with the name of your module and remove this line.

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // ... answersheet instance ID - it should be named as the first character of the module.
$attempt = optional_param('attempt', null, PARAM_NOTAGS);

if ($id) {
    $cm         = get_coursemodule_from_id('answersheet', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $answersheet  = $DB->get_record('answersheet', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($a) {
    $answersheet  = $DB->get_record('answersheet', array('id' => $a), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $answersheet->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('answersheet', $answersheet->id, $course->id, false, MUST_EXIST);
    $id         = $cm->id;
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$event = \mod_answersheet\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $answersheet);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/answersheet/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($answersheet->name));
$PAGE->set_heading(format_string($course->fullname));

/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('answersheet-'.$somevar);
 */

if (($attempt === 'new') && mod_answersheet_attempt::can_start($PAGE->cm, $answersheet)) {
    $attemptobj = mod_answersheet_attempt::start($PAGE->cm, $answersheet);
    $url = new moodle_url('/mod/answersheet/view.php', array('id' => $id, 'attempt' => $attemptobj->id));
    redirect($url);
}

// Output starts here.
echo $OUTPUT->header();

// Conditions to show the intro can change to look for own settings or whatever.
if ($answersheet->intro) {
    echo $OUTPUT->box(format_module_intro('answersheet', $answersheet, $cm->id), 'generalbox mod_introbox', 'answersheetintro');
}

// Replace the following lines with you own code.
//echo $OUTPUT->heading('Yay! It works!');

if (((int)$attempt) > 0) {
    $attemptobj = mod_answersheet_attempt::get((int)$attempt, $PAGE->cm, $answersheet);
    $attemptobj->display();
} else {
    $attempts = mod_answersheet_attempt::get_user_attempts($PAGE->cm, $answersheet);
    foreach ($attempts as $attempt) {
        $url = new moodle_url('/mod/answersheet/view.php', array('id' => $id, 'attempt' => $attempt->id));
        if ($attempt->attempt->timecompleted) {
            echo html_writer::link($url, 'Review attempt '.$attempt->id.' - '.
                    round(100.0 * $attempt->attempt->grade, 2).'%').'<br/>'; // TODO string
        } else {
            echo html_writer::link($url, 'Continue attempt '.$attempt->id).'<br/>'; // TODO string
        }
    }
    if (mod_answersheet_attempt::can_start($PAGE->cm, $answersheet)) {
        $url = new moodle_url('/mod/answersheet/view.php', array('id' => $id, 'attempt' => 'new'));
        echo html_writer::link($url, 'Start new attempt'); // TODO string
    }
}

// Finish the page.
echo $OUTPUT->footer();
