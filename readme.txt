=== Category Quiz ===
Contributors: Spiralli
Tags: Quiz, Enneagram
Requires at least: 3.0
Tested up to: 3.4.1
Stable tag: 1.0

Find your dominant category by answering yes/no questions divided into hidden categories

== Description ==

This plugin implements a yes/no quiz where each question is associated with a category and each "yes" response increases the count for that category. Each category must have the same number of questions. The questions are stored in the questions.csv file in the format question id,question category,question text. All questions must be answered for a valid submission, at which time the category results will be displayed as number of yes responses/percentages, in decreasing order. The supplied questions.csv contains an enneagram quiz. Insert the quiz to any page using the shortcode [sbsquiz]

== Installation ==

Upload the zip file or ftp the Category Quiz folder to the plugins directory

== Frequently Asked Questions ==

= How do I use my own questions? =

Edit the questions.csv file in the plugin directory. Format is question id,question category,question text. Questions can be input in any order, but you must have the same number of questions in each category.

= I edited the file, but my questions don't show up =

Disable and re-enable the plugin. This will purge the old questions and load the new ones.

== Changelog ==

= Version 1.0 =

* Removed debug logic *
* One quiz allowed - will add multiple quizzes/file-import in 1.1 *