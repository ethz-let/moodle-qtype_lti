@qtype @qtype_lti @qtype_lti_preview
Feature: Preview lti questions
  As a teacher
  In order to check my lti questions will work for students
  I need to preview them

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
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype | name      | template         |
      | Test questions   | lti   | lti-001   | plain            |
      | Test questions   | lti   | lti-002   | plain            |
      | Test questions   | lti   | lti-003   | plain            |


  @javascript @_switch_window
  Scenario: Preview an lti question and submit a partially correct response.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And I choose "Preview" action for "lti-003" in the question bank
  #  And I switch to "questionpreview" window ###: Apparently, preview from qbank now opens in same window...
    And I expand all fieldsets
    And I set the field "How questions behave" to "Immediate feedback"
    And I press "Start again with these options"
    And I press "Check"
    Then I should see "I hope your code had a beginning, a middle and an end."
    And I press "Close preview"
    And I should see "lti-001"
