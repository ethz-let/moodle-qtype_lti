@qtype @qtype_lti
Feature: Test duplicating a quiz containing an LTI question
  As a teacher
  In order re-use my courses containing lti questions
  I need to be able to backup and restore them

  Background:
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name      | template         |
      | Test questions   | lti     | lti-001 | editor           |
      | Test questions   | lti     | lti-002 | editorfilepicker |
      | Test questions   | lti     | lti-003 | plain            |
    And the following "activities" exist:
      | activity   | name      | course | idnumber |
      | quiz       | Test quiz | C1     | quiz1    |
    And quiz "Test quiz" contains the following questions:
      | lti-001 | 1 |
      | lti-002 | 1 |
      | lti-003 | 1 |
    And I log in as "admin"
    And I am on "Course 1" course homepage

  @javascript
  Scenario: Backup and restore a course containing 3 lti questions
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 |
    And I navigate to "Question bank" node in "Course administration"
    And I should see "lti-001"
    And I should see "lti-002"
    And I should see "lti-003"
    And I click on "Edit" "link" in the "lti-001" "table_row"
    Then the following fields match these values:
      | Question name              | lti-001                                               |
      | General feedback           | I hope your code had a beginning, a middle and an end. |
    And I press "Cancel"
    And I click on "Edit" "link" in the "lti-002" "table_row"
    Then the following fields match these values:
      | Question name              | lti-002                                               |
      | General feedback           | I hope your code had a beginning, a middle and an end. |
    And I press "Cancel"
    And I click on "Edit" "link" in the "lti-003" "table_row"
    Then the following fields match these values:
      | Question name              | lti-003                                               |
      | General feedback           | I hope your code had a beginning, a middle and an end. |
