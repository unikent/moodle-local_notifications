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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', \context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/notifications/index.php');
$PAGE->set_title("Course Notifications");

// Check form.
$form = new \local_notifications\form\notify_form();
if ($data = $form->get_data()) {
    $actionable = isset($data->actionable) ? true : false;
    $dismissable = isset($data->dismissable) ? true : false;

    \local_notifications\Notification::create($data->courseid, 0, uniqid(), $data->message, $data->type, $actionable, $dismissable);
    redirect(new moodle_url('/course/view.php', array(
        'id' => $data->courseid
    )));
}

// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading("Course Notifications");

// Show form.
$form->display();

// Output footer.
echo $OUTPUT->footer();
