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

admin_externalpage_setup('notificationsmanager');

$params = array(
    'page'    => optional_param('page', 0, PARAM_INT),
    'perpage' => optional_param('perpage', 25, PARAM_INT),
    'extref' => optional_param('extref', '', PARAM_RAW),
    'courseid' => optional_param('courseid', '', PARAM_INT)
);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new \moodle_url('/local/notifications/index.php', $params));
$PAGE->set_title("Notifications Center");

$action = optional_param('action', '', PARAM_RAW);
if ($action == 'delete') {
    require_sesskey();
    $id = required_param('id', PARAM_INT);
    $notification = \local_notifications\Notification::instance($id);
    $notification->delete();
    redirect($PAGE->url, 'Notification deleted', 2);
}

$PAGE->requires->js_call_amd('local_notifications/filtering', 'init', array());

$table = new \html_table();
$table->head = array(
    'Course', 'Reference', 'Message', 'Action'
);
$table->data = array();

$notificationparams = array();
if (!empty($params['extref'])) {
    $notificationparams['extref'] = $params['extref'];
}
if (!empty($params['courseid'])) {
    $notificationparams['courseid'] = $params['courseid'];
}

$courses = $DB->get_records('course', null, 'shortname');
$notifications = $DB->get_recordset('course_notifications', $notificationparams, '', '*', $params['page'] * $params['perpage'], $params['perpage']);
foreach ($notifications as $row) {
    $course = new \html_table_cell(\html_writer::tag('a', $courses[$row->courseid]->shortname, array(
        'href' => new \moodle_url('/course/view.php', array(
            'id' => $row->courseid
        )),
        'target' => '_blank'
    )));

    $actionurl = new \moodle_url('/local/notifications/index.php', array_merge($params, array(
        'sesskey' => sesskey(),
        'action' => 'delete',
        'id' => $row->id
    )));
    $action = new \html_table_cell(\html_writer::tag('a', 'Delete', array(
        'href' => $actionurl
    )));

    $table->data[] = array(
        $course,
        $row->extref,
        $row->message,
        $action
    );
}

$notifications->close();


// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading("Notifications Center");

// Display filtering options.
$extrefs = $DB->get_fieldset_sql('SELECT DISTINCT extref FROM {course_notifications}');
$prettyextrefs = array();
foreach ($extrefs as $extref) {
    $prettyextrefs[$extref] = $extref;
}
echo \html_writer::select($prettyextrefs, 'extref', $params['extref'], array('' => 'Filter by reference'));

$prettycourses = array();
foreach ($courses as $course) {
    $prettycourses[$course->id] = $course->shortname . ': ' . $course->fullname;
}
echo \html_writer::select($prettycourses, 'course', $params['courseid'], array('' => 'Filter by course'));

echo '<br />';

// Display notifications table.
echo \html_writer::table($table);

$total = $DB->count_records('course_notifications', $notificationparams);
echo $OUTPUT->paging_bar($total, $params['page'], $params['perpage'], $PAGE->url);

// Output footer.
echo $OUTPUT->footer();
