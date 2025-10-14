# Mail Login Module PHPUnit Testing - Implementation Plan

## Project Blueprint Overview

This plan implements comprehensive PHPUnit tests for the Drupal mail_login module in Phase 1 (Basic Coverage), focusing on authentication flow testing. The implementation follows a test-driven approach with incremental complexity building.

### Architecture Summary
- **Unit Tests**: Test AuthDecorator class in isolation with mocked dependencies
- **Functional Tests**: End-to-end browser testing of complete login flows
- **Configuration Testing**: Multiple mail_login configuration scenarios
- **Error Handling**: Comprehensive error message and exception testing

### Implementation Strategy
1. Start with basic test infrastructure and simple passing tests
2. Build up mocking infrastructure incrementally
3. Add configuration scenarios one by one
4. Implement error handling and edge cases
5. Create comprehensive functional tests
6. Add documentation and finalize

---

## Step-by-Step Implementation Prompts

### Prompt 1: Create Basic Test Infrastructure

```
Create the basic test directory structure and a minimal unit test file for the AuthDecorator class. This should include:

1. Create the directory structure: tests/src/Unit/ and tests/src/Functional/
2. Create a basic AuthDecoratorTest.php file in tests/src/Unit/ that:
   - Extends UnitTestCase
   - Has proper namespace and use statements
   - Includes a simple setUp() method
   - Has one basic test method that just asserts true (placeholder)
   - Follows Drupal coding standards with proper docblocks

The goal is to establish the foundation and ensure the test can run successfully before adding complexity.
```

### Prompt 2: Add Basic Mocking Infrastructure

```
Expand the AuthDecoratorTest.php to include the basic mocking infrastructure for AuthDecorator dependencies. This should:

1. Add protected properties for all the main mocks (userAuth, entityTypeManager, configFactory, etc.)
2. Expand the setUp() method to create mocks for all AuthDecorator dependencies
3. Create a basic AuthDecorator instance in setUp() with the mocked dependencies
4. Replace the placeholder test with a simple test that verifies the AuthDecorator can be instantiated
5. Add proper docblocks and follow Drupal testing conventions

Focus on getting the mocking structure in place without complex test logic yet.
```

### Prompt 3: Implement Basic Email Lookup Test

```
Add the first real test method for email lookup functionality. This should:

1. Create testLookupAccountWithValidEmail() method that:
   - Configures the config mock to return mail_login_enabled = TRUE
   - Mocks the user storage to return a valid, unblocked user for an email lookup
   - Calls lookupAccount() with a valid email address
   - Asserts that the correct user object is returned

2. Add helper methods if needed for creating mock user objects
3. Ensure the test follows the behavioral assertion strategy (testing return values and method calls)

This establishes the core testing pattern for the AuthDecorator class.
```

### Prompt 4: Add Username Fallback and Email-Only Mode Tests

```
Expand the unit tests to cover username fallback and email-only mode scenarios:

1. Add testLookupAccountWithValidUsername() that:
   - Tests the username fallback when email lookup fails
   - Verifies proper user storage method calls

2. Add testLookupAccountEmailOnlyModeWithUsername() that:
   - Configures mail_login_email_only = TRUE
   - Tests that username login is rejected
   - Verifies the correct error message is displayed via messenger mock

3. Add testLookupAccountMailLoginDisabled() that:
   - Tests behavior when mail_login_enabled = FALSE
   - Verifies fallback to original userAuth service

Focus on configuration-dependent behavior and error message testing.
```

### Prompt 5: Implement Case Sensitivity and Blocked User Tests

```
Add tests for case sensitivity handling and blocked user scenarios:

1. Add testLookupAccountCaseSensitive() that:
   - Tests case-sensitive email matching when mail_login_case_sensitive = TRUE
   - Verifies exact email match behavior

2. Add testLookupAccountCaseInsensitive() that:
   - Tests case-insensitive email matching when mail_login_case_sensitive = FALSE
   - Mocks the database query for case-insensitive lookup
   - Handles the single-match scenario

3. Add testLookupAccountWithBlockedUser() that:
   - Tests blocked user detection
   - Verifies error message display
   - Ensures FALSE is returned for blocked users

These tests cover important edge cases and security considerations.
```

### Prompt 6: Add Authentication Method Tests

```
Implement tests for the authenticate() and authenticateAccount() methods:

1. Add testAuthenticateWithValidCredentials() that:
   - Tests the complete authenticate() flow
   - Mocks successful lookupAccount() and userAuth.authenticate()
   - Verifies correct user ID is returned

2. Add testAuthenticateWithInvalidCredentials() that:
   - Tests failed authentication scenarios
   - Verifies FALSE is returned for invalid credentials

3. Add testAuthenticateAccountMethod() that:
   - Tests the authenticateAccount() wrapper method
   - Handles both UserAuthenticationInterface and legacy UserAuthInterface
   - Tests the interface detection logic

This completes the core AuthDecorator functionality testing.
```

### Prompt 7: Create Basic Functional Test Infrastructure

```
Create the functional test infrastructure for end-to-end testing:

1. Create AuthenticationTest.php in tests/src/Functional/ that:
   - Extends BrowserTestBase
   - Sets up required modules ['mail_login', 'user']
   - Includes proper namespace and use statements
   - Has a setUp() method for test preparation
   - Includes one basic test that creates a user and verifies the test environment

2. Add helper methods for:
   - Creating test users with email and username
   - Configuring mail_login settings
   - Common assertions for login success/failure

Focus on establishing the functional testing foundation.
```

### Prompt 8: Implement Core Functional Login Tests

```
Add the main functional tests for email and username login flows:

1. Add testEmailLoginSuccess() that:
   - Creates a test user with email and password
   - Enables mail_login in configuration
   - Performs login via email address
   - Verifies successful login (checks for "Member for" text or similar)

2. Add testUsernameLoginSuccess() that:
   - Creates a test user
   - Tests login via username (fallback behavior)
   - Verifies successful login

3. Add testMailLoginDisabledFallsBackToUsername() that:
   - Tests behavior when mail_login is disabled
   - Verifies username login still works

These tests verify the core user-facing functionality works end-to-end.
```

### Prompt 9: Add Functional Tests for Email-Only Mode and Error Scenarios

```
Implement functional tests for email-only mode and error handling:

1. Add testEmailOnlyModeRejectsUsername() that:
   - Enables mail_login_email_only mode
   - Attempts login with username
   - Verifies error message appears and login fails
   - Tests successful login with email address

2. Add testBlockedUserLoginFailure() that:
   - Creates a blocked user account
   - Attempts login
   - Verifies appropriate error message and login failure

3. Add testInvalidCredentialsShowError() that:
   - Tests login with wrong password
   - Verifies error message display
   - Ensures no sensitive information is leaked

These tests cover important security and user experience scenarios.
```

### Prompt 10: Add Case Sensitivity Functional Tests

```
Implement functional tests for case sensitivity behavior:

1. Add testCaseInsensitiveEmailLogin() that:
   - Creates user with lowercase email
   - Configures mail_login_case_sensitive = FALSE
   - Tests login with mixed-case email
   - Verifies successful login

2. Add testCaseSensitiveEmailLogin() that:
   - Configures mail_login_case_sensitive = TRUE
   - Tests that case-sensitive matching works correctly
   - Verifies case mismatches fail appropriately

3. Add data providers if needed for testing multiple email case variations

This ensures the case sensitivity configuration works correctly in real usage.
```

### Prompt 11: Create Admin Settings Form Unit Tests

```
Create unit tests for the MailLoginAdminSettingsForm:

1. Create MailLoginAdminSettingsFormTest.php in tests/src/Unit/Form/ that:
   - Tests getFormId() returns correct form ID
   - Tests getEditableConfigNames() returns correct config names
   - Tests buildForm() creates expected form structure
   - Tests submitForm() saves configuration correctly

2. Mock the ConfigFactoryInterface and Config objects appropriately
3. Test form building with various configuration states
4. Verify form submission saves all configuration values

This ensures the admin interface works correctly and maintains configuration integrity.
```

### Prompt 12: Add Data Providers and Edge Case Tests

```
Enhance the test suite with data providers and edge case coverage:

1. Add data providers for testing multiple email formats:
   - Common formats (user@domain.com, test.user@domain.org)
   - Edge cases (user+tag@domain.co.uk, etc.)

2. Add edge case tests for:
   - Empty/null identifiers
   - Invalid email formats
   - Multiple users with similar emails (case-insensitive scenarios)
   - Database connection failures (if applicable)

3. Enhance existing tests to use data providers where appropriate
4. Add performance considerations for test execution

This provides comprehensive coverage of realistic usage scenarios.
```

### Prompt 13: Create Test Documentation and README

```
Create comprehensive documentation for the test suite:

1. Create tests/README.md that includes:
   - Overview of test coverage and organization
   - Setup instructions and prerequisites
   - How to run individual tests and test suites
   - Explanation of test categories (unit vs functional)
   - Troubleshooting common issues

2. Add inline documentation improvements:
   - Enhance docblocks for complex test methods
   - Add comments explaining test setup and assertions
   - Document any test data or configuration requirements

3. Include examples of running tests:
   - Individual test files
   - Specific test methods
   - Full test suite execution

This ensures the test suite is maintainable and accessible to other developers.
```

### Prompt 14: Add Test Quality Assurance and Validation

```
Implement quality assurance measures and final validation:

1. Add test validation methods:
   - Verify all tests pass consistently
   - Check for proper error handling in tests themselves
   - Validate test isolation (tests don't affect each other)

2. Add code quality improvements:
   - Ensure Drupal coding standards compliance
   - Optimize test performance where possible
   - Add any missing assertions or edge cases

3. Create test execution scripts or commands:
   - Quick smoke test execution
   - Full test suite with coverage
   - Individual component testing

4. Final integration verification:
   - Ensure all tests integrate properly with Drupal testing framework
   - Verify no orphaned or unused test code
   - Confirm all configuration scenarios are covered

This completes Phase 1 with a production-ready test suite.
```

---

## Implementation Notes

### Key Principles
- **Incremental Complexity**: Each step builds on the previous without big jumps
- **Test Isolation**: Each test is independent and doesn't affect others
- **Realistic Data**: Use common email formats and typical usernames
- **Behavioral Assertions**: Test return values, method calls, and side effects
- **Error Message Testing**: Use partial text matching for robustness

### Quality Gates
- All tests must pass consistently
- Follow Drupal coding standards and PHPUnit best practices
- Include comprehensive documentation
- Maintain test performance (fast unit tests, thorough functional tests)

### Success Criteria
Phase 1 is complete when:
1. All 14 prompts have been implemented successfully
2. Test suite demonstrates consistent, reliable execution
3. Documentation meets specified requirements
4. Code quality standards are satisfied
5. All core authentication scenarios are covered

This plan provides a solid foundation for implementing comprehensive authentication testing for the mail_login module while maintaining code quality and developer productivity.
