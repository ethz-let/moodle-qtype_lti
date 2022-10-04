@qtype @qtype_lti @qtype_lti_addtool
Feature: Add tools
  In order to provide activities for learners
  As a teacher
  I need to be able to add external tools to a course

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |


  @javascript
  Scenario: Add a tool via the activity picker
    # define a pre-configured tool
    When I log in as "admin"
    And I navigate to "Plugins > Question types > External question type (ETH)" in site administration
    And I follow "Add preconfigured tool"
    And I set the following fields to these values:
      | Tool name                | Teaching Tool 1 |
      | Tool configuration usage | Show in activity chooser and as a preconfigured tool |
    And I set the field "Tool URL" to local url "/question/type/lti/tests/fixtures/tool_provider.php"
    And I press "Save changes"
    And I log out
    # create a new question using the pre-configured tool
    And  I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    # For tool that does not support Content-Item message type, the Select content button must be disabled.
    And I press "Create a new question ..."
    And I set the field "item_qtype_lti" to "1"
    And I press "submitbutton"
    Then I should see "Adding an External question"
    And I set the following fields to these values:
      | id_name                  | lti-001                      |
      | id_generalfeedback       | This is general feedback     |
    And I set the field "Preconfigured tool" to "Teaching Tool 1"
    And the "Select content" "button" should be disabled
    And I press "id_submitbutton"
    Then I should see "lti-001"
    # check that all values were stored correctly
    And I choose "Edit question" action for "lti-001" in the question bank
    Then the field "Preconfigured tool" matches value "Teaching Tool 1"
    And the "Select content" "button" should be disabled
    And the "Tool URL" "field" should be disabled
