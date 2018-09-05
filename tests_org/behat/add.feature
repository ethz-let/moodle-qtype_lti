@qtype @qtype_lti
Feature: Test creating a Multiple choice question
  As a teacher
  In order to test my students
  I need to be able to create a Multiple choice question

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" node in "Course administration"

  Scenario: Create an LTI question
    When I add a "LTI (ETH)" question filling the form with:
      | Question name            | Qtype-LTI-001                   |
      | Question text            | Find the capital cities in Europe. |
      | General feedback         | Paris and London                   |
      | One or multiple answers? | Multiple answers allowed           |
      | Choice 1                 | Tokyo                              |
      | Choice 2                 | Spain                              |
      | Choice 3                 | London                             |
      | Choice 4                 | Barcelona                          |
      | Choice 5                 | Paris                              |
      | id_fraction_0            | None                               |
      | id_fraction_1            | None                               |
      | id_fraction_2            | 50%                                |
      | id_fraction_3            | None                               |
      | id_fraction_4            | 50%                                |
      | Hint 1                   | First hint                         |
      | Hint 2                   | Second hint                        |
    Then I should see "Qtype-LTI-001"