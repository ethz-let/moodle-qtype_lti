@qtype @qtype_lti
Feature: Test editing an LTI question
  As a teacher
  In order to be able to update my LTI question
  I need to edit them

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | T1        | Teacher1 | teacher1@example.com |
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
      | questioncategory | qtype       | name                        | template    |
      | Test questions   | lti | LTI for editing | two_of_four |
      | Test questions   | lti | LTI for editing   | one_of_four |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" node in "Course administration"

  Scenario: Edit a LTI question with multiple response (checkboxes)
    When I click on "Edit" "link" in the "LTI for editing" "table_row"
    And I set the following fields to these values:
      | Question name | |
    And I press "id_submitbutton"
    Then I should see "You must supply a value here."
    When I set the following fields to these values:
      | Question name | Edited LTI name |
    And I press "id_submitbutton"
    Then I should see "Edited LTI name"

  Scenario: Edit a LTI question with single response (radio buttons)
    When I click on "Edit" "link" in the "LTI for editing" "table_row"
    And I set the following fields to these values:
      | Question name | Edited LTI name |
    And I press "id_submitbutton"
    Then I should see "Edited LTI name"
