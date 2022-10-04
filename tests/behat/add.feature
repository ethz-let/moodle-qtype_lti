@qtype @qtype_lti @qtype_lti_add
Feature: Test creating an lti question
  As a teacher
  In order to test my students
  I need to be able to create an lti question

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


  Scenario: Create an lti question
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And I press "Create a new question ..."
    And I set the field "item_qtype_lti" to "1"
    And I press "submitbutton"
    And I should see "Adding an External question"
    Then I set the following fields to these values:
      | id_name                  | lti-001                      |
      | id_generalfeedback       | This is general feedback     |
    And I press "id_updatebutton"
    Then I should see "lti-001"

