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
 * Internal library of functions for module ipal
 * All the ipal specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package   mod_ipal
 * @copyright 2011 Eckerd College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->libdir/formslib.php");
defined('MOODLE_INTERNAL') || die();
$PAGE->requires->yui2_lib('yahoo');
$PAGE->requires->yui2_lib('event');
//require_js(array('yui_yahoo', 'yui_event'));

/**
 * Get answers for a particular question id
 * TODO: the 2.1 question engine may make this function uneeded
 *
 * @param int $questionid
 * @return string $line
*/
function ipal_get_answers($questionid){
	global $ipal;
	global $DB;
	global $CFG;
	$line="";
	$answers=$DB->get_records('question_answers',array('question'=>$questionid));
	foreach($answers as $answers){
		$line .= $answers->answer;
		$line .= "&nbsp;";
	}
	return($line);
}


/**
 * Setup the grid view
 * Deprecated: use gridview now
 * TODO: Lots to add here
 */
function ipal_grid_view(){
	global $DB;
	global $ipal;

	$questions=explode(",",$ipal->questions);

	echo "<table border=\"1\" width=\"100%\">\n";
	echo "<tr><td>Name</td>\n";
	foreach($questions as $question){
		$question_data=$DB->get_record('question',array('id'=>$question));
		echo "<td><div style=\"word-wrap: break-word;\">".substr(trim(strip_tags($question_data->name)),0,80)."</div></td>\n";
	}
	echo "</tr>\n";

	foreach(ipal_who_sofar($ipal->id) as $user){
		echo "<tr><td>".ipal_find_student($user)."</td>\n";
		foreach($questions as $question){
			if($question != ""){
				$answer=$DB->get_record('ipal_answered',array('ipal_id'=>$ipal->id,'user_id'=>$user, 'question_id'=>$question));
				if(!$answer){
					echo "<td>&nbsp;</td>\n";
				}
				else{
					$answer_data=$DB->get_record('question_answers',array('id'=>$answer->answer_id));
					echo "<td><div style=\"word-wrap: break-word;\">".substr(trim(strip_tags($answer_data->answer)),0,40)."</div></td>\n";
				}
			}
		}
		echo "</tr>\n";
	}
	echo "</table>\n";
}

/**
 * Find out who has answered questions so far
 *
 * @param int $ipalid
 * @return array of students
 */
function ipal_who_sofar($ipalid){
	global $DB;

	$records=$DB->get_records('ipal_answered',array('ipal_id'=>$ipalid));

	foreach($records as $records){
		$answer[]=$records->user_id;
	}
	return(array_unique($answer));
}

/**
 * Find student name associated with $userid
 *
 * @param int $userid
 * @return string $name
 */
function ipal_find_student($userid){
	global $DB;
	$user=$DB->get_record('user',array('id'=>$userid));
	$name=$user->lastname.", ".$user->firstname;
	return($name);
}

/**
 * Find responses by a particular student in an IPAL instance
 *
 * @param int $userid
 * @param int $ipalid
 * @return array of String
 */
function ipal_find_student_responses($userid,$ipalid){
	global $DB;
	$responses=$DB->get_records('ipal_answered',array('ipal_id'=>$ipalid, 'user_id'=>$userid));
	foreach($responses as $records){
		$temp[]="Q".$records->question_id." = ".$records->answer_id;
	}
	return(implode(",",$temp));
}

/**
 * Gets answers formated for the student display
 * TODO: Replace with the rendering engine from question lib
 *
 * @param int $questionid
 * @return an array of answer
 */
function ipal_get_answers_student($questionid){
	global $ipal;
	global $DB;
	global $CFG;

	$answerarray=array();
	$line="";
	$answers=$DB->get_records('question_answers',array('question'=>$questionid));
	foreach($answers as $answers){
		$answerarray[$answers->id]=$answers->answer;
	}

	return($answerarray);
}

/**
 * Get the questions if in the student context
 *
 * @param int $qid
 * @return array of questions
 */
function ipal_get_questions_student($questionid){

	global $ipal;
	global $DB;
	global $CFG;
	$q='';
	$pagearray2=array();

	$aquestions=$DB->get_record('question',array('id'=>$questionid));
	if($aquestions->questiontext != ""){

		$pagearray2[]=array('id'=>$questionid, 'question'=>$aquestions->questiontext, 'answers'=>ipal_get_answers_student($questionid));
	}
	return($pagearray2);
}

/**
 * Get the questions in any context (like the instructor)
 *
 * @return array of questions
 */
function ipal_get_questions(){

	global $ipal;
	global $DB;
	global $CFG;
	$q='';
	$pagearray2=array();
	// is there an quiz associated with an ipal?
	//	$ipal_quiz=$DB->get_record('ipal_quiz',array('ipal_id'=>$ipal->id));

	// get quiz and put it into an array
	$quiz=$DB->get_record('ipal',array('id'=>$ipal->id));

	//Get the question ids
	$questions=explode(",",$quiz->questions);

	//get the questions and stuff them into an array
	foreach($questions as $q){
			
		$aquestions=$DB->get_record('question',array('id'=>$q));
		if(isset($aquestions->questiontext)){
			$pagearray2[]=array('id'=>$q, 'question'=>strip_tags($aquestions->questiontext), 'answers'=>ipal_get_answers($q));

		}

	}

	return($pagearray2);
}

/**
 * This function counts questions based on ipal id
 * TODO: Make this function check on a count by query of answer id
 *
 * @param int $questionid
 * @return string
 */
function ipal_count_questions($questionid){
	global $DB;
	global $ipal;
	$answers=$DB->get_records('question_answers',array('question'=>$questionid));
	foreach($answers as $answers)
	{
		$labels[]=htmlentities(substr($answers->answer,10));
		$data[]=$DB->count_records('ipal_answered', array('ipal_id'=>$ipal->id,'answer_id'=>$answers->id));

	}

	return( "?data=".implode(",",$data)."&labels=".implode(",",$labels)."&total=10");

	//print $DB->count_records('ipal_answered', array('ipal_id'=>$ipal->id,'answer_id'=>$answer_id));
}

/**
 * Create the form for the instructors (or anyone higher than a student) to view
 *
 * @return string
 */
function ipal_make_instructor_form(){
	global $ipal;
	$myform="<form action=\"?".$_SERVER['QUERY_STRING']."\" method=\"post\">\n";
	$myform .= "\n";
	foreach(ipal_get_questions() as $items){
		$myform .= "<input type=\"radio\" name=\"question\" value=\"".$items['id']."\" />";

		$myform .="<a href=\"show_question.php?qid=".$items['id']."&id=".$ipal->id."\" target=\"_blank\">[question]</a>";
		$myform .= "<a href=\"standalone_graph.php?id=".$items['id']."&ipalid=".$ipal->id."\" target=\"_blank\">[graph]</a>".$items['question']."<br /><br />\n";
	}
	if(ipal_check_active_question()){
		$myform .= "<input type=\"submit\" value=\"Send Question\" />\n</form>\n";
	}else{
		$myform .= "<input type=\"submit\" value=\"Start Polling\" />\n</form>\n";
	}

	return($myform);
}

/**
 * Set the question in the databse so the client functions can find what question is active.s
 */
function ipal_send_question(){
	global $ipal;
	global $DB;
	global $CFG;

	$ipal_course=$DB->get_record('ipal',array('id'=>$ipal->id));
	$record = new stdClass();
	$record->id = '';
	$record->course = $ipal_course->course;
	$record->ipal_id = $ipal->id;
	$record->quiz_id = $ipal->id;
	$record->question_id = $_POST['question'];
	$record->timemodified = time();
	if($DB->record_exists('ipal_active_questions', array('ipal_id'=>$ipal->id))){
		$mybool=$DB->delete_records('ipal_active_questions', array('ipal_id'=>$ipal->id));
	}
	$lastinsertid = $DB->insert_record('ipal_active_questions', $record);
	ipal_send_message_to_device();

}

/**
 * Clear the current question
 */
function ipal_clear_question(){
	global $ipal;
	global $DB;

	if($DB->record_exists('ipal_active_questions', array('ipal_id'=>$ipal->id))){
		$mybool=$DB->delete_records('ipal_active_questions', array('ipal_id'=>$ipal->id));
	}
}

/**
 * provide Javascript to check if the chart needs updating
 */
function ipal_java_graphupdate() {
	global $ipal;
	global $DB;
	echo "\n\n<script type=\"text/javascript\">\nvar http = false;\nvar x=\"\";\n\nif(navigator.appName == \"Microsoft Internet Explorer\") {\nhttp = new ActiveXObject(\"Microsoft.XMLHTTP\");\n} else {\nhttp = new XMLHttpRequest();
}";

	echo "\n\nfunction replace() {\nvar t=setTimeout(\"replace()\",3000);\nhttp.open(\"GET\", \"graphicshash.php?ipalid=".$ipal->id."\", true);";
	echo "\nhttp.onreadystatechange=function() {\nif(http.readyState == 4) {\nif(http.responseText != x){";
	echo "\nx=http.responseText;\n";
	$state=$DB->get_record('ipal',array('id'=>$ipal->id));
	if($state->preferredbehaviour =="Graph"){
		echo "document.getElementById('graphIframe').src=\"graphics.php?ipalid=".$ipal->id."\"";
	}
	else
	{
		echo "document.getElementById('graphIframe').src=\"gridview.php?id=".$ipal->id."\"";
	}

	echo "}\n}\n}\nhttp.send(null);\n}\nreplace();\n</script>";

}

/**
 * Javascript to check if the question has changed
 */
function ipal_java_questionUpdate() {
	global $ipal;
	echo "\n\n<script type=\"text/javascript\">\nvar http = false;\nvar x=\"\";\nvar myCount=0;\n\nif(navigator.appName == \"Microsoft Internet Explorer\") {\nhttp = new ActiveXObject(\"Microsoft.XMLHTTP\");\n} else {\nhttp = new XMLHttpRequest();
}";

	echo "\n\nfunction replace() {\nvar t=setTimeout(\"replace()\",3000);\nhttp.open(\"GET\", \"currentQ.php?ipalid=".$ipal->id."\", true);";
	echo "\nhttp.onreadystatechange=function() {\nif(http.readyState == 4) {\n\nif(http.responseText != x && myCount > 1){\n";
	echo "window.location = window.location.href+'&x';\n";
	echo "}\nx=http.responseText;}\n}\nhttp.send(null);\nmyCount++;}\n\nreplace();\n</script>";

}

/**
 * Make the button controls in the instructor interface
 *
 * @return string
 */
function instructor_buttons(){
	$disabled="";
	$myform="<form action=\"?".$_SERVER['QUERY_STRING']."\" method=\"post\">\n";
	$myform .= "\n";
	if(!ipal_check_active_question()){
		$disabled= "disabled=\"disabled\"";
	}

	$myform .= "<input type=\"submit\" value=\"Stop Polling\" name=\"clearQuestion\" ".$disabled."/>\n</form>\n";

	return($myform);

}

/**
 * Toggle view
 *
 * @param  string $newstate
 * @return string
 */
function ipal_toggle_view($newstate){
	$myform="<form action=\"?".$_SERVER['QUERY_STRING']."\" method=\"post\">\n";
	$myform .= "\n";
	$myform .= "<INPUT TYPE=hidden NAME=ipal_view VALUE=\"changeState\">";
	$myform .= "Change View to <input type=\"submit\" value=\"$newstate\" name=\"gridView\"/>\n</form>\n";

	return($myform);

}



//
//
//Compadre button
//
//
/**
 * Compadre button
 * @param int $cmid
 * @return string
 */
function ipal_show_compadre($cmid){
	$myform="<form action=\"ipal_quiz_edit.php?cmid=".$cmid."\" method=\"post\">\n";
	$myform .= "\n";
	$myform .= "<input type=\"submit\" value=\"Add/Change Questions\" />\n</form>\n";
	return($myform);
}


//
//This function puts all the elements together for
//the instructors interface.  This is the last stop
//before its displayed
//
/**
 * This function puts all the elements together for the instructor interface.
 * This is the last stop before it is displayed
 * @param int $cmid
 */
function ipal_display_instructor_interface($cmid){
	global $DB;
	global $ipal;

	if(isset($_POST['clearQuestion'])){
		ipal_clear_question();
	}
	if(isset($_POST['question'])){
		ipal_send_question();
	}

	$state=$DB->get_record('ipal',array('id'=>$ipal->id));
	if((isset($_POST['ipal_view'])) and ($_POST['ipal_view']=="changeState")){
		if($state->preferredbehaviour == "Graph"){
			$result=$DB->set_field('ipal', 'preferredbehaviour', 'Grid',array('id'=>$ipal->id));
			$newstate = 'Histogram';
		}else{
			$result=$DB->set_field('ipal', 'preferredbehaviour','Graph',array('id'=>$ipal->id));
			$newstate = 'Spreadsheet';
		}
	}
	else
	{
		if($state->preferredbehaviour == "Graph"){
			$newstate = 'Spreadsheet';
		}
		else{$newstate = 'Histogram';
		}
	}
	if(($newstate == 'Histogram') and (ipal_get_qtype(ipal_show_current_question_id()) == 'essay')){
		$newstate = 'Responses';
	}

	ipal_java_graphupdate();
	echo "<br>Mobile passcode: ". ipal_get_passcode();
	echo "<table><tr><td>".instructor_buttons()."</td><td>".ipal_show_compadre($cmid)."</td><td>".ipal_toggle_view($newstate)."<td></tr></table>";
	echo  ipal_make_instructor_form();
	echo "<br><br>";
	$state=$DB->get_record('ipal',array('id'=>$ipal->id));
	if($state->preferredbehaviour =="Graph"){
		if(ipal_show_current_question()==1){
			echo "<br>";
			echo "<br>";
			echo "<iframe id= \"graphIframe\" src=\"graphics.php?ipalid=".$ipal->id."\" height=\"535\" width=\"723\"></iframe>";
			echo "<br><br><a onclick=\"window.open('popupgraph.php?ipalid=".$ipal->id."', '',
					'width=620,height=450,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes');
					return false;\"
					href=\"popupgraph.php?ipalid=".$ipal->id."\" target=\"_blank\">Open a new window for the graph.</a>";
		}
	}else{
		echo "<br>";
		echo "<br>";
		echo "<iframe id= \"graphIframe\" src=\"gridview.php?id=".$ipal->id."\" height=\"535\" width=\"723\"></iframe>";
		//echo "<br><br><a href=\"gridview.php?refresh=true&id=".$ipal->id."\" target=\"_blank\">Open a new window for the graph.</a>";
		echo "<br><br><a onclick=\"window.open('popupgraph.php?ipalid=".$ipal->id."', '',
				'width=620,height=450,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes');
				return false;\"
				href=\"popupgraph.php?ipalid=".$ipal->id."\" target=\"_blank\">Open a new window for the graph.</a>";

		//ipal_grid_view();
	}
}




/**
 * Disable the question if the student has already answered it.
 * Which also, confirms the question is in the database for that user.
 *
 * @param int $userid
 * @param int $questionid
 * @param int $quizid
 * @param int $classid
 * @param int $ipalid
 * @return string
 */
function ipal_check_if_answered($userid, $questionid, $quizid, $classid, $ipalid){
	global $DB;
	if($DB->record_exists('ipal_answered', array('user_id'=>$userid, 'question_id'=>$questionid, 'quiz_id'=>$quizid, 'class_id'=>$classid, 'ipal_id'=>$ipalid ))){
		return("disabled=\"disabled\"");
	}else{
		return("");
	}

}

/**
 * This function finds the current question that is active for the ipal
 * that it was requested from.
 * TODO: make a prettier interface
 * @return number
 */
function ipal_show_current_question(){
	global $DB;
	global $ipal;
	if($DB->record_exists('ipal_active_questions', array('ipal_id'=>$ipal->id))){
		$question=$DB->get_record('ipal_active_questions', array('ipal_id'=>$ipal->id));
		$questiontext=$DB->get_record('question',array('id'=>$question->question_id));
		echo "The current question is -> ".strip_tags($questiontext->questiontext);
		return(1);
	}
	else
	{
		return(0);
	}
}

/**
 * Check if there is a question active
 * @return number
 */
function ipal_check_active_question(){
	global $DB;
	global $ipal;
	if($DB->record_exists('ipal_active_questions', array('ipal_id'=>$ipal->id))){
		return(1);
	}
	else
	{
		return(0);
	}
}

/**
 * Find the currrent question id that is active for the ipal that
 * it was requiested from.
 *
 * @return int $questionid if found the question, 0 if not
 */
function ipal_show_current_question_id(){
	global $DB;
	global $ipal;
	if($DB->record_exists('ipal_active_questions', array('ipal_id'=>$ipal->id))){
		$question=$DB->get_record('ipal_active_questions', array('ipal_id'=>$ipal->id));
		return($question->question_id);
	}
	else{
		return(0);
	}
}

/**
 * Modification by Junkin
 * A function to obtain the question type of a given question
 * redundant with function ipal_get_question_type in /mod/ipal/graphics.php
 *
 * @param unknown_type $questionid
 * @return string
 */
function ipal_get_qtype($questionid){
	global $DB;
	if($questiontype=$DB->get_record('question',array('id'=>$questionid))){
		return($questiontype->qtype);
	}
	else
	{
		return 'multichoice';
	}
}

/**
 * Make the form for student to answer form
 */
function ipal_make_student_form(){
	global $ipal;
	global $DB;
	global $CFG;
	global $USER;
	global $course;
	$disabled='';

	//	$ipal_quiz=$DB->get_record('ipal_quiz',array('ipal_id'=>$ipal->id));

	if($DB->record_exists('ipal_active_questions', array('ipal_id'=>$ipal->id))){
		$question=$DB->get_record('ipal_active_questions', array('ipal_id'=>$ipal->id));
		$questionid=$question->question_id;
		$myFormArray= ipal_get_questions_student($questionid);
		//		$disabled=ipal_check_if_answered($USER->id,$myFormArray[0]['id'],$ipal_quiz->quiz_id,$course->id,$ipal->id);
		echo "<br><br><br><br>";
		echo "<form class=\"ipalquestion\" action=\"?id=".$_GET['id']."\" method=\"post\">\n";

		//Display question text
		echo "<fieldset>\n<legend>";
		echo $myFormArray[0]['question'];
		echo "</legend>\n";

		if(ipal_get_qtype($questionid) == 'essay'){ // Display text field if essay question
			echo  "<INPUT TYPE=\"text\" NAME=\"a_text\" >\n<br>";
			echo  "<INPUT TYPE=hidden NAME=\"answer_id\" value=\"-1\">";
		}
		else { //Display choices if multiple-choice question
			$countid = 0;
			foreach($myFormArray[0]['answers'] as $key=>$value){
				echo "<span>";
				echo "<input type=\"radio\" name=\"answer_id\" id=\"choice".$countid."\" value=\"$key\" ".$disabled."/>";
				echo "<label class=\"choice\" for=\"choice".$countid."\">".strip_tags($value)."</label>";
				echo "</span>\n";
				echo "<br>";
				$countid++;
			}
			echo "<br>";
			echo "<INPUT TYPE=hidden NAME=a_text VALUE=\" \">";
		}

		//Hidden inputs
		echo "<INPUT TYPE=hidden NAME=question_id VALUE=\"".$myFormArray[0]['id']."\">";
		echo "<INPUT TYPE=hidden NAME=active_question_id VALUE=\"$question->id\">";
		echo "<INPUT TYPE=hidden NAME=course_id VALUE=\"$course->id\">";
		echo "<INPUT TYPE=hidden NAME=user_id VALUE=\"$USER->id\">";
		echo "<INPUT TYPE=submit NAME=submit VALUE=\"Submit\" ".$disabled.">";
		echo "<INPUT TYPE=hidden NAME=ipal_id VALUE=\"$ipal->id\">";
		echo "<INPUT TYPE=hidden NAME=instructor VALUE=\"".findInstructor($course->id)."\">";
		echo "\n</fieldset>";
		echo "</form>\n";
	}
	else{
		echo "<br><br>No Current Question.";
	}
}

/*//Old and bad version
 function findInstructor($c_num){
global $DB;
$query="SELECT u.id FROM mdl_user u, mdl_role_assignments r, mdl_context cx, mdl_course c WHERE u.id = r.userid AND r.contextid = cx.id AND cx.instanceid = c.id AND r.roleid =3 AND
c.id = ".$c_num." AND cx.contextlevel =50";

$result=$DB->get_record_sql($query);
return md5($result->id.$result->ipal);
}
*/

/**
 * @param unknown_type $c_num
* @return string
*/
function findInstructor($c_num){
	global $DB;
	global $CFG;

	$query="SELECT u.id FROM ".$CFG->prefix."user u, ".$CFG->prefix."role_assignments r, ".$CFG->prefix."context cx, ".$CFG->prefix."course c, ".$CFG->prefix."role ro
			WHERE u.id = r.userid AND r.contextid = cx.id AND cx.instanceid = c.id AND ro.shortname='editingteacher' AND r.roleid =ro.id AND
			c.id = ".$c_num." AND cx.contextlevel =50";

	$result=$DB->get_record_sql($query);
	if(!$result){
		return('none');
	}else{
		$instructorwww = $result->id.$CFG->wwwroot;
		return md5($instructorwww);
	}
}

/**
 * insert the student responses into the database
 *
 * @param int $questionid
 * @param int $answerid
 * @param int $activequestionid
 * @param int $atext
 * @param int $instructor
 */
function ipal_save_student_response($questionid, $answerid, $activequestionid,$atext,$instructor){
	global $ipal;
	global $DB;
	global $CFG;
	global $USER;
	global $course;

	// Create insert for archive
	$recordarc = new stdClass();
	$recordarc->id = '';
	$recordarc->user_id = $USER->id;
	$recordarc->question_id = $questionid;
	$recordarc->quiz_id = $ipal->id;
	$recordarc->answer_id = $answerid;
	$recordarc->a_text = $atext;
	$recordarc->class_id = $course->id;
	$recordarc->ipal_id = $ipal->id;
	$recordarc->ipal_code = $activequestionid;
	$recordarc->shortname = $course->shortname;
	$recordarc->instructor = $instructor;
	$recordarc->time_created = time();
	$recordarc->sent = '1';
	$lastinsertid = $DB->insert_record('ipal_answered_archive', $recordarc);

	//Create insert for current question
	$record = new stdClass();
	$record->id = '';
	$record->user_id = $USER->id;
	$record->question_id = $questionid;
	$record->quiz_id = $ipal->id;
	$record->answer_id = $answerid;
	$record->a_text = $atext;
	$record->class_id = $course->id;
	$record->ipal_id = $ipal->id;
	$record->ipal_code = $activequestionid;
	$record->time_created = time();

	if($DB->record_exists('ipal_answered', array('user_id'=>$USER->id, 'question_id'=>$questionid, 'ipal_id'=>$ipal->id ))){
		$mybool=$DB->delete_records('ipal_answered', array('user_id'=>$USER->id, 'question_id'=>$questionid, 'ipal_id'=>$ipal->id ));
	}
	$lastinsertid = $DB->insert_record('ipal_answered', $record);

}

/**
 * puts the student interface together, last stop before it is displayed
 */
function ipal_display_student_interface(){
	global $DB;
	global $ipal;

	ipal_java_questionUpdate();
	$priorresponse = '';
	if(isset($_POST['answer_id'])){
		if($_POST['answer_id'] == '-1'){
			$priorresponse = "\n<br />Last answer you submitted:".strip_tags($_POST['a_text']);
		}
		else
		{
			$answer_id = $_POST['answer_id'];
			$answer = $DB->get_record('question_answers', array('id'=>$answer_id));
			//$answer = $DB
			$priorresponse = "\n<br />Last answer you submitted: ".strip_tags($answer->answer);
		}
		ipal_save_student_response($_POST['question_id'],$_POST['answer_id'],$_POST['active_question_id'],$_POST['a_text'],$_POST['instructor']);
	}

	//Print the anonymous message and prior response.
	echo $priorresponse;

	//Print the question
	ipal_make_student_form();
}

/**
 * print a message to tell users whether the form is anonymous or non-anonymous
 */
function ipal_print_anonymous_message() {
	global $ipal;
	if ($ipal->anonymous) {
		echo get_string('anonymousmess', 'ipal');
	}
	else {
		echo get_string('nonanonymousmess', 'ipal');
	}
}

/**
 * Return true there is any records (of answers) in every questions of the IPAL instance.
 * Return false otherwise.
 *
 * @param int $ipalid
 * @return boolean
 */
function ipal_check_answered($ipalid) {
	global $DB;
	if (sizeof($DB->get_records('ipal_answered',array('ipal_id'=>$ipalid)))) {
		return true;
	}
	else {
		return false;
	}
}

/**
 * Return the passcode for the ipal
 * @return int passcode
 */
function ipal_get_passcode() {
	global $ipal;
	global $DB;
	return $ipal->id*100+fmod($ipal->timecreated, 100);
}

/**
 * Display question in tempview.php, modified suitable for android apps.
 *
 * @param id $userid
 * @param string $passcode
 * @param string $username
 */
function ipal_tempview_display_question($userid, $passcode, $username) {
	global $DB;
	global $ipal;
	global $CFG;
	global $USER;
	global $course;

	ipal_java_questionUpdate();
	//echo "displaying question";
	$priorresponse = '';
	if(isset($_POST['answer_id'])){

		if($_POST['answer_id'] == '-1'){
			$priorresponse = "\n<br />Last answer you submitted:".strip_tags($_POST['a_text']);
		}
		else
		{
			$answer_id = $_POST['answer_id'];
			$answer = $DB->get_record('question_answers', array('id'=>$answer_id));
			//$answer = $DB
			$priorresponse = "\n<br />Last answer you submitted: ".strip_tags($answer->answer);
		}
		//save student response
		ipal_tempview_save_response($_POST['question_id'],$_POST['answer_id'],$_POST['active_question_id'],$_POST['a_text'],$_POST['instructor'], $userid);

	}
	//echo "displaying question";
	//Print the anonymous message and prior response.
	echo $priorresponse;

	$disabled='';

	//	$ipal_quiz=$DB->get_record('ipal_quiz',array('ipal_id'=>$ipal->id));

	if($DB->record_exists('ipal_active_questions', array('ipal_id'=>$ipal->id))){
		$question=$DB->get_record('ipal_active_questions', array('ipal_id'=>$ipal->id));
		$questionid=$question->question_id;
		$myFormArray= ipal_get_questions_student($questionid);
		//		$disabled=ipal_check_if_answered($USER->id,$myFormArray[0]['id'],$ipal_quiz->quiz_id,$course->id,$ipal->id);
		echo "<br><br><br><br>";
		echo "<p id=\"questiontype\">".ipal_get_qtype($questionid)."<p>";
		echo "<form class=\"ipalquestion\" action=\"?p=".$passcode."&user=".$username."\" method=\"post\">\n";

		//Display question text
		echo "<fieldset>\n<legend>";
		echo $myFormArray[0]['question'];
		echo "</legend>\n";

		if(ipal_get_qtype($questionid) == 'essay'){ // Display text field if essay question
			echo  "<INPUT TYPE=\"text\" NAME=\"a_text\" >\n<br>";
			echo  "<INPUT TYPE=hidden NAME=\"answer_id\" value=\"-1\">";
		}
		else { //Display choices if multiple-choice question
			$countid = 0;
			foreach($myFormArray[0]['answers'] as $key=>$value){
				echo "<span>";
				echo "<input type=\"radio\" name=\"answer_id\" id=\"choice".$countid."\" value=\"$key\" ".$disabled."/>";
				echo "<label class=\"choice\" for=\"choice".$countid."\">".strip_tags($value)."</label>";
				echo "</span>\n";
				echo "<br>";
				$countid++;
			}
			echo "<br>";
			echo "<INPUT TYPE=hidden NAME=a_text VALUE=\" \">";
		}

		//Hidden inputs
		echo "<INPUT TYPE=hidden NAME=question_id VALUE=\"".$myFormArray[0]['id']."\">";
		echo "<INPUT TYPE=hidden NAME=active_question_id VALUE=\"$question->id\">";
		echo "<INPUT TYPE=hidden NAME=course_id VALUE=\"$course->id\">";
		echo "<INPUT TYPE=hidden NAME=user_id VALUE=\"$userid\">";
		echo "<INPUT TYPE=submit NAME=submit VALUE=\"Submit\" ".$disabled.">";
		echo "<INPUT TYPE=hidden NAME=ipal_id VALUE=\"$ipal->id\">";
		echo "<INPUT TYPE=hidden NAME=instructor VALUE=\"".findInstructor($course->id)."\">";
		echo "\n</fieldset>";
		echo "</form>\n";
	}
	else{
		echo "<p id=\"questiontype\">nocurrentquestion<p>";
		echo "<br><br>No Current Question.";
	}
}


/**
 * These function save response to database from the tempview
 *
 * @param int $questionid
 * @param int $answerid
 * @param int $activequestionid
 * @param String $atext
 * @param String $instructor
 * @param int $userid
 */
function ipal_tempview_save_response($questionid, $answerid, $activequestionid,$atext,$instructor, $userid){
	global $ipal;
	global $DB;
	global $CFG;
	global $USER;
	global $course;

	// Create insert for archive
	$recordarc = new stdClass();
	$recordarc->id = '';
	$recordarc->user_id = $userid;
	$recordarc->question_id = $questionid;
	$recordarc->quiz_id = $ipal->id;
	$recordarc->answer_id = $answerid;
	$recordarc->a_text = $atext;
	$recordarc->class_id = $course->id;
	$recordarc->ipal_id = $ipal->id;
	$recordarc->ipal_code = $activequestionid;
	$recordarc->shortname = $course->shortname;
	$recordarc->instructor = $instructor;
	$recordarc->time_created = time();
	$recordarc->sent = '1';
	$lastinsertid = $DB->insert_record('ipal_answered_archive', $recordarc);

	//Create insert for current question
	$record = new stdClass();
	$record->id = '';
	$record->user_id = $userid;
	$record->question_id = $questionid;
	$record->quiz_id = $ipal->id;
	$record->answer_id = $answerid;
	$record->a_text = $atext;
	$record->class_id = $course->id;
	$record->ipal_id = $ipal->id;
	$record->ipal_code = $activequestionid;
	$record->time_created = time();

	if($DB->record_exists('ipal_answered', array('user_id'=>$userid, 'question_id'=>$questionid, 'ipal_id'=>$ipal->id ))){
		$mybool=$DB->delete_records('ipal_answered', array('user_id'=>$userid, 'question_id'=>$questionid, 'ipal_id'=>$ipal->id ));
	}
	$lastinsertid = $DB->insert_record('ipal_answered', $record);
}
/**
 * Send a message contain the string "refresh" to the devices registered with IPAL 
 * to Google GCM servers. Google Servers will be responsible to send this message to 
 * the phone.
 *
 * @param string $regid
 * @param int $userid
 */
function ipal_send_message_to_device() {
	global $ipal;
	global $DB;
	global $course;

	// The BROWSER API key from Google APIs
	$apiKey = "AIzaSyARBhzl2L5MCV4-_rZNH6nz4xGHvhXpW2E";

	// Replace with real client registration IDs
	//$registrationIDs = array( "APA91bEjokTRyBRt9GUzihAVhiHVsgxtEFunJi8J_A8zbeLC4Sr5lgLYWnIIa-RyrIN6GvlGPrbpRVjz4_tJc0v5o2QTSk8BljCl5o05euqbc2bQd57CRliaJMxwpHMqJDPvHomUGYRO");

	//Get users in the course, and then find the regIDs in the ipal_mobile table
	$context = get_context_instance(CONTEXT_COURSE, $course->id);
	$students = get_role_users(5, $context);
	$regIds = array();
	foreach ($students as $s) {
		//echo "ipal id:". $ipal->id;
		//echo "student id:" . $s->id;
		$r = $DB->get_record('ipal_mobile', array('user_id'=>$s->id));
		echo "regid" . $r->reg_id;
		array_push($regIds, $r->reg_id);
	}
	// Message to be sent
	$message = "refresh";

	// Set POST variables
	$url = 'https://android.googleapis.com/gcm/send';

	$fields = array(
			'registration_ids'  => $regIds,
			'data'              => array( "message" => $message ),
	);

	$headers = array(
			'Authorization: key=' . $apiKey,
			'Content-Type: application/json'
	);

	// Open connection
	$ch = curl_init();

	// Set the url, number of POST vars, POST data
	curl_setopt( $ch, CURLOPT_URL, $url);
	curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields ) );

	// Execute post
	$result = curl_exec($ch);

	// Close connection
	curl_close($ch);

	echo "This is the result:" . $result;
}

/**
 * Add or Update (if existed) the regID that is associated with a userid
 *
 * @param string $regid
 * @param int $userid
 */
function add_regid($regid, $username) {
	global $DB;
	if ($user = $DB->get_record('user', array('username' => $username))) {
		if ($record = $DB->get_record('ipal_mobile', array('user_id'=>$user->id))) {
			$record->reg_id = $regid;
			$record->time_created = time();
			$DB->update_record('ipal_mobile', $record);
			return true;
		}
		else {
			$recordnew = new stdClass();
			$recordnew->id = '';
			$recordnew->user_id = $user->id;
			$recordnew->reg_id = $regid;
			$recordnew->time_created = time();
			$DB->insert_record('ipal_mobile', $recordnew);
			return true;
		}
	}
}
?>