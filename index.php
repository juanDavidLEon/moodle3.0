<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * @package     local_greetings
 * @copyright   2022 Dintev <juan.garces.leon@correounivalle.edu.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot. '/local/greetings/lib.php');
require_once($CFG->dirroot. '/local/greetings/message_form.php');

require_login();

if (isguestuser()) {
    throw new moodle_exception('noguest');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/greetings/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_greetings'));
$PAGE->set_heading(get_string('pluginname', 'local_greetings'));

$allowpost = has_capability('local/greetings:postmessages', $context);
$deleteanypost = has_capability('local/greetings:deleteanymessage', $context);
$allowviewmessages = has_capability('local/greetings:viewmessages', $context);
$editpost = has_capability('local/greetings:editanypost', $context);


$action = optional_param('action', '', PARAM_TEXT);

if ($action == 'edit') {

    $id = required_param('id', PARAM_TEXT);

    if ($editpost) {

        $params = array('id' => $id);
        // $sql = "UPDATE {local_greetings_messages}
        // SET message='hello'
        // WHERE id= $id ";

        // $DB->execute($sql);
        $messageform = new local_greetings_message_form();

        if (!empty($message)) {
            $record = new stdclass;
            $record->id = $id;
            $record->message = "";
            $DB->update_record('local_greetings_messages', $record);
        }
    }
}

if ($action == 'del') {
    require_sesskey();

    $id = required_param('id', PARAM_TEXT);

    if ($deleteanypost) {
        $params = array('id' => $id);
        $DB->delete_records('local_greetings_messages', $params);
    }
}

$messageform = new local_greetings_message_form();

if ($data = $messageform->get_data()) {
    require_capability('local/greetings:postmessages', $context);

    $message = required_param('message', PARAM_TEXT);

    if (!empty($message)) {
        $record = new stdClass;
        $record->message = $message;
        $record->timecreated = time();
        $record->userid = $USER->id;
        $DB->insert_record('local_greetings_messages', $record);
    }
}

echo $OUTPUT->header();
// echo var_dump($PAGE->url);

if (isloggedin()) {
    echo local_greetings_get_greeting($USER);
} else {
    echo get_string('greetinguser', 'local_greetings');
}

if ($allowpost) {
    $messageform->display();
}


$messages = $DB->get_records('local_greetings_messages');

if ($allowviewmessages) {
    echo $OUTPUT->box_start('card-columns');

    foreach ($messages as $m) {
        $sql = "SELECT u.firstname
            FROM {user} u
            WHERE {$m->userid} = u.id";

        $sqlrs = $DB->get_record_sql($sql);

        echo html_writer::start_tag('div', array('class' => 'card'));
        echo html_writer::start_tag('div', array('class' => 'card-body'));
        echo html_writer::tag('p', format_text($m->message, FORMAT_PLAIN), array('class' => 'card-text'));
        echo html_writer::tag('p', get_string('postedby', 'local_greetings', $sqlrs->firstname), array('class' => 'card-text'));
        echo html_writer::start_tag('p', array('class' => 'card-text'));
        echo html_writer::tag('small', userdate($m->timecreated), array('class' => 'text-muted'));
        echo html_writer::end_tag('p');

        if ($editpost || $deleteanypost) {

            echo html_writer::start_tag('p', array('class' => 'card-footer text-center'));

            if ($editpost) {

                    echo html_writer::link(
                        new moodle_url(
                            '/local/greetings/index.php',
                            array('action' => 'edit', 'id' => $m->id)
                        ),
                        $OUTPUT->pix_icon('t/editinline', ''),
                        array('role' => 'button', 'aria-label' => get_string('edit', 'local_greetings'), 'title' => get_string('edit','local_greetings')));
            }


            if ($deleteanypost) {
                echo html_writer::link(
                        new moodle_url(
                            '/local/greetings/index.php',
                            array('action' => 'del', 'id' => $m->id, 'sesskey' => sesskey())
                        ),
                        $OUTPUT->pix_icon('t/delete', ''),
                        array('role' => 'button', 'aria-label' => get_string('delete'), 'title' => get_string('delete')));
            }

            echo html_writer::end_tag('p');
        }
        /*if ($editpost) {
            $messageform->display();
        }*/

        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    }
    echo $OUTPUT->box_end();
}


echo $OUTPUT->footer();
