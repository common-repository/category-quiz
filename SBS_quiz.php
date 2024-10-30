<?php
/*
Plugin Name: Category Quiz
Plugin URI: http://www.spiralli.ie
Description: This plugin implements a yes/no quiz where each question is associated with a category and each "yes" response increases the count for that category. Each category must have the same number of questions. The questions are stored in the questions.csv file in the format <question id>,<auestion category>,<question text>. All questions must be answered for a valid submission, at which time the category results will be displayed as percentages, in decreasing order. The supplied questions.csv contains an enneagram quiz.
Version: 1.0
Author: Ivan O'Donoghue
Author URI: http://www.spiralli.ie/category-quiz
License: GPLv2
*/

/*  Copyright 2012  Ivan O'Donoghue  (email : info@spiralli.ie)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Helper functions */
function SBS_quiz_isint( $mixed )
{
    return ( preg_match( '/^\d*$/'  , $mixed) == 1 );
}

function br_trigger_error($message, $errno) {
    if(isset($_GET['action']) && $_GET['action'] == 'error_scrape') {
        echo '<strong>' . $message . '</strong>';
        exit;
    } else {
        trigger_error($message, $errno);
    }
}

/* Initialise database table and insert questions from array */
function SBS_quiz_insert_questions($questionArray) {
    global $wpdb;
    $tablename=$wpdb->prefix.'sbsquiz';
    $sql="DELETE FROM $tablename WHERE 1=1"; // on activation, delete and repopulate questions - they may have changed.
    $wpdb->query($sql);
    foreach ($questionArray as $question) {
        $rec= array(
            'quizid'=>      $question[0],
            'quizcat'=>     $question[1],
            'quizquestion'=>esc_sql($question[2])
        );
        $wpdb->insert($tablename,$rec);
    }
}

/* read questions from text file and validate content */
function SBS_quiz_read_questions_from_file($questionpath,$questionArray) {
    $row = 0; // file row
    $cat = -1;    
    $categorycount = array(); // used to check all categories have equal number of categories    
    if (($handle = fopen($questionpath, "r")) !== FALSE) { // Format is id,category,question
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $questionArray[] = $data;
            if ($questionArray[$row][1]>0 && SBS_QUIZ_isint($questionArray[$row][1])) { // ensure category in range
                $cat = (int)$questionArray[$row][1];
                $questionArray[$row][1]=strip_tags($questionArray[$row][1]);
                $categorycount[$cat]++; //increment category counter
            } else {
                //echo $questionArray[$row][1];
                return 1;
            }
            $row++;
        }
        // ensure we have no missing categories
        $cat=sizeof($categorycount);
        for ($i=1;$i<=$cat;$i++) {
            if (!(isset($categorycount[$i]))) {
                return 2;
            }
        }
        // ensure all categories have same number of questions
        $countconcensus = 0;
        foreach ($categorycount as $category) { 
            if ($category != 0) {
                if ($countconcensus == 0) {
                    $countconcensus = $category;
                } else if ($countconcensus != $category && $countconcensus != -1) {
                    $countconcensus = -1;
                }
            } else {
                $countconcensus = 0;
            }
        }
        if ($countconcensus==-1) {
            return 3;
        }
        shuffle($questionArray); // Randomise the questions
        fclose($handle);
        $options['question_count']=$row;
        $options['category_count']=sizeof($categorycount);
        $options['show_percentages']="yes";
        $options['show_count']="yes";        
        update_option('SBS_quiz_options', $options);
        return $questionArray;
    } else {
        _sbslog("Couldn't open file");
    }
}

/* Install plugin - check version compatibility, add questions to database*/
register_activation_hook( __FILE__, 'SBS_quiz_install' );
function SBS_quiz_install() {
    if ( version_compare( get_bloginfo( 'version' ), '3.1', '<' ) ) {
        deactivate_plugins( basename( __FILE__ ) ); // Deactivate our plugin
    }
    else {
        global $wpdb;
        $questionArray=array();
        $tablename=$wpdb->prefix.'sbsquiz';
        $sql="CREATE TABLE `$tablename` ( 
        `quizid` INT(10) NOT NULL,  
        `quizcat` INT(4) NOT NULL,              
        `quizquestion` VARCHAR(255) NOT NULL
        )"; 
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        $questionpath=plugin_dir_path(__FILE__).'questions.csv';
        $questionArray=SBS_quiz_read_questions_from_file($questionpath,$questionArray);
        // $questionArray returns an integer as an error code if there is an issue
        if (!is_array($questionArray) && SBS_quiz_isint($questionArray)) { 
            switch ($questionArray) {
                case 1:
                    br_trigger_error(__('Bad category number - positive integers only for categories in questions file', 'CategoryQuiz'),E_USER_ERROR);
                    break;
                case 2:
                    br_trigger_error(__('There is a missing category number in the category file', 'CategoryQuiz'),E_USER_ERROR);
                    break;
                case 3:
                    br_trigger_error(__('All categories must have an equal number of questions', 'CategoryQuiz'),E_USER_ERROR);
                    break;
            }
        }
        SBS_quiz_insert_questions($questionArray);
    }
}

/* Internationalisation */
add_action('init', 'SBS_quiz_i18n');
function SBS_quiz_i18n() {
    load_plugin_textdomain('CategoryQuiz', false, 'CategoryQuiz/languages');
}

/* Admin Menu */
add_action('admin_menu', 'SBS_quiz_create_menu');
function SBS_quiz_create_menu() {
    add_options_page(   
        'Category Quiz',
        __( "Category Quiz", 'CategoryQuiz'),
        'manage_options',
        'SBS_quiz',
        'SBS_quiz_show_settings'
    );
}

/* Admin settings page */
function SBS_quiz_show_settings() {
    ?>
    <div class='wrap'><div id='icon-options-general' class='icon32'><br></div><h2>

    </h3></div>
    <form method="post" action="options.php">
    <?php 
    settings_fields('SBS_quiz_admin_settings');
    do_settings_sections('SBS_quiz');
    ?>
    <input name="Submit" type="submit" value="Save Changes" />        
    </form>
    <?php
}

/* Admin Settings */
add_action('admin_init','SBS_quiz_admin_init');
function SBS_quiz_admin_init() {
    register_setting(
        'SBS_quiz_admin_settings',
        'SBS_quiz_options'
    );
    add_settings_section(
        'SBS_quiz_main_settings',
        __( "Quiz Settings", 'CategoryQuiz'),
        'SBS_quiz_section_header',
        'SBS_quiz'
    );
    add_settings_field(
        'SBS_quiz_percentage',
        __( "Display Percentage", 'CategoryQuiz'),
        'SBS_quiz_percentage_input',
        'SBS_quiz',
        'SBS_quiz_main_settings'
    );
    add_settings_field(
        'SBS_quiz_count',
        __( "Display Count", 'CategoryQuiz'),
        'SBS_quiz_count_input',
        'SBS_quiz',
        'SBS_quiz_main_settings'
    );    
}

function SBS_quiz_section_header(){
    echo "<p>".__("Update Settings", 'CategoryQuiz')."</p>";
}

function SBS_quiz_percentage_input() {
    $options=get_option('SBS_quiz_options');
    $percentage=$options['show_percentages'];
    if ($percentage=="yes") {
        $percentage_yes='checked="checked"';
        $percentage_no='';
    }
    else {
        $percentage_no='checked="checked"';
        $percentage_yes='';        
    }
    echo '<input type="radio" id="SBS_quiz_percentage" name="SBS_quiz_options[show_percentages]" value="yes" '.$percentage_yes.'/>'.__("Yes",CategoryQuiz).' <input type="radio" id="SBS_quiz_percentage" name="SBS_quiz_options[show_percentages]" value="no" '.$percentage_no.'/> '.__("No",CategoryQuiz);    
}

function SBS_quiz_count_input() {
    $options=get_option('SBS_quiz_options');
    $count=$options['show_count'];
    if ($count=="yes") {
        $count_yes='checked="checked"';
        $count_no='';
    }
    else {
        $count_no='checked="checked"';
        $count_yes='';        
    }
    echo '<input type="radio" name="SBS_quiz_options[show_count]" value="yes" '.$count_yes.'/>'.__("Yes",CategoryQuiz).' <input type="radio" name="SBS_quiz_options[show_count]" value="no" '.$count_no.'/> '.__("No",CategoryQuiz);
}

/* Process shortcode - display quiz and parse results */
add_shortcode('sbsquiz','do_sbs_quiz');
function do_sbs_quiz() { 
    global $wpdb;    
    $tablename=$wpdb->prefix.'sbsquiz';
    $questionArray=array();
    $sql="SELECT quizid,quizcat,quizquestion from `$tablename` LIMIT 0,1000";
    $questionArray=$wpdb->get_results($sql);    
    // Form not submitted - display form
    if (!(isset($_POST['Submit']) && $_POST['Submit'] == 'Submit')) {       
        $html .= '<form name="enneagram" action="'.$_SERVER["REQUEST_URI"].'" method="post">';
        $i=0;
        foreach ($questionArray as $key=>$value)  {
            $html.='<p>'.__('Question','CategoryQuiz').' ';
            $html.= $i + 1;
            $html.=': ' . stripslashes($value->quizquestion) . '<br />';
            $html.= '<input type="radio" name="q' . $i . '" value="1"';
            if (isset($val[$i]) && $val[$i] == 1) {
                $html.= ' checked="checked" ';
            }
            $html.='/> Yes <input type="radio" name="q' . $i . '" value="0"';
            if ((isset($val[$i]) && $val[$i] == 0) || !(isset($_POST['Submit']) && $_POST['Submit'] == 'Submit')) { // Default to no checked on first load
                $html.= ' checked="checked" ';
            }
            $html.='/> No</p>';
            $i++;
        }
        $html.='<input name="qcount" type="hidden" value="' . sizeof($questionArray) . '" />';
        $html.='<input name=questionArray type=hidden value="'.  base64_encode(serialize($questionArray)).'" />';
        $html.='<input type="submit" name="Submit" value="Submit"></form>';

        return $html;
    }
    // Form submitted
    else {
        //initialise results array
        $catcount=get_option('SBS_quiz_category_count');
        $answers=NULL;
        for ($i=1;$i<=$catcount;$i++){
            $cat="Category ".$i;
            $answers[$cat]=0;
        }
        //$answers = array("Category 1" => 0, "Category 2" => 0, "Category 3" => 0, "Category 4" => 0, "Category 5" => 0, "Category 6" => 0, "Category 7" => 0, "Category 8" => 0, "Category 9" => 0);
        for ($i = 0; $i < sizeof($questionArray); $i++) {
            $question = 'q' . $i;
            if (($_POST[$question]) == 1) { // for the current question, increment the category is the response is "yes"
                $cat = $questionArray[$i]->quizcat;
                $cat = 'Category ' . $cat;
                $answers[$cat]++;
            }
        }
        arsort($answers); // Serve results in descending order.
        $html ="<h2>Results</h2>";
        $options=get_option('sbs_quiz_options');
        $showcount=$options['show_count'];
        $showpercentages=$options['show_percentages'];
        $catcount=$options['category_count'];
        foreach ($answers as $key=>$val) {
            $html.= '<br />' . $key . ": ";
            if ($showcount=="yes")    {
                $html .= $val;                
            }
            if ($showpercentages=="yes")    {            
                $divisor = (sizeof($questionArray)) / $catcount;
                if ($val != 0) {
                    $percent = ($val * 100) / $divisor;
                    $html .= sprintf(" %01.2f&#37;", $percent);
                } else {
                    $html .= " 0&#37;";
                }
            }
        }          
        return $html;
    }
}        

?>