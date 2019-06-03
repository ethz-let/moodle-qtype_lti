*PHPUNIT tests:*

	vendor/bin/phpunit qtype_lti_question_test question/type/lti/tests/question_test.php
	vendor/bin/phpunit qtype_lti_test question/type/lti/tests/questiontype_test.php
	vendor/bin/phpunit qtype_lti_service_exception_handler_testcase question/type/lti/tests/qtype_lti_service_exception_handler_testcase.php
	vendor/bin/phpunit qtype_lti_servicelib_testcase question/type/lti/tests/servicelib_test.php
	vendor/bin/phpunit qtype_lti_walkthrough_testcase question/type/lti/tests/walkthrough_test.php

*Behat Features:*

	Add Question
	Add Tool
	Backup and Restore
	Backup and Restore Pre-configured Tools
	Edit
	Export
	Emport
	Preview