# Mail Login Module PHPUnit Testing - TODO Checklist

## Phase 1: Basic Coverage - Authentication Flow Testing

### Infrastructure Setup
- [x] **Prompt 1: Create Basic Test Infrastructure**
  - [x] Create directory structure: `tests/src/Unit/`
  - [x] Create directory structure: `tests/src/Functional/`
  - [x] Create basic `tests/src/Unit/AuthDecoratorTest.php`
  - [x] Add proper namespace and use statements
  - [x] Extend UnitTestCase
  - [x] Add basic setUp() method
  - [x] Add placeholder test method
  - [x] Follow Drupal coding standards with docblocks
  - [x] Verify test can run successfully

### Unit Test Foundation
- [x] **Prompt 2: Add Basic Mocking Infrastructure**
  - [x] Add protected properties for all main mocks
  - [x] Mock UserAuthInterface/UserAuthenticationInterface
  - [x] Mock EntityTypeManagerInterface
  - [x] Mock ConfigFactoryInterface and Config
  - [x] Mock Connection (database)
  - [x] Mock MessengerInterface
  - [x] Create AuthDecorator instance with mocked dependencies
  - [x] Replace placeholder test with instantiation test
  - [x] Add proper docblocks

### Core Unit Tests - Email Lookup
- [x] **Prompt 3: Implement Basic Email Lookup Test**
  - [x] Create `testLookupAccountWithValidEmail()` method
  - [x] Configure config mock for mail_login_enabled = TRUE
  - [x] Mock user storage for valid email lookup
  - [x] Mock unblocked user object
  - [x] Test lookupAccount() with valid email
  - [x] Assert correct user object returned
  - [x] Add helper methods for mock user creation
  - [x] Follow behavioral assertion strategy

### Configuration-Dependent Tests
- [x] **Prompt 4: Add Username Fallback and Email-Only Mode Tests**
  - [x] Create `testLookupAccountWithValidUsername()` method
  - [x] Test username fallback when email lookup fails
  - [x] Verify proper user storage method calls
  - [x] Create `testLookupAccountEmailOnlyModeWithUsername()` method
  - [x] Configure mail_login_email_only = TRUE
  - [x] Test username login rejection
  - [x] Verify error message via messenger mock
  - [x] Create `testLookupAccountMailLoginDisabled()` method
  - [x] Test behavior when mail_login_enabled = FALSE
  - [x] Verify fallback to original userAuth service

### Edge Cases and Security
- [x] **Prompt 5: Implement Case Sensitivity and Blocked User Tests**
  - [x] Create `testLookupAccountCaseSensitive()` method
  - [x] Test case-sensitive email matching
  - [x] Configure mail_login_case_sensitive = TRUE
  - [x] Verify exact email match behavior
  - [x] Create `testLookupAccountCaseInsensitive()` method
  - [x] Test case-insensitive email matching
  - [x] Configure mail_login_case_sensitive = FALSE
  - [x] Mock database query for case-insensitive lookup
  - [x] Handle single-match scenario
  - [x] Create `testLookupAccountWithBlockedUser()` method
  - [x] Test blocked user detection
  - [x] Verify error message display
  - [x] Ensure FALSE returned for blocked users

### Authentication Methods
- [x] **Prompt 6: Add Authentication Method Tests**
  - [x] Create `testAuthenticateWithValidCredentials()` method
  - [x] Test complete authenticate() flow
  - [x] Mock successful lookupAccount() and userAuth.authenticate()
  - [x] Verify correct user ID returned
  - [x] Create `testAuthenticateWithInvalidCredentials()` method
  - [x] Test failed authentication scenarios
  - [x] Verify FALSE returned for invalid credentials
  - [x] Create `testAuthenticateAccountMethod()` method
  - [x] Test authenticateAccount() wrapper method
  - [x] Handle UserAuthenticationInterface and legacy UserAuthInterface
  - [x] Test interface detection logic

### Functional Test Foundation
- [x] **Prompt 7: Create Basic Functional Test Infrastructure**
  - [x] Create `tests/src/Functional/AuthenticationTest.php`
  - [x] Extend BrowserTestBase
  - [x] Set up required modules ['mail_login', 'user']
  - [x] Add proper namespace and use statements
  - [x] Add setUp() method for test preparation
  - [x] Add basic test for environment verification
  - [x] Add helper method for creating test users
  - [x] Add helper method for configuring mail_login settings
  - [x] Add helper methods for login success/failure assertions

### Core Functional Tests
- [ ] **Prompt 8: Implement Core Functional Login Tests**
  - [ ] Create `testEmailLoginSuccess()` method
  - [ ] Create test user with email and password
  - [ ] Enable mail_login in configuration
  - [ ] Perform login via email address
  - [ ] Verify successful login (check for "Member for" text)
  - [ ] Create `testUsernameLoginSuccess()` method
  - [ ] Test login via username (fallback behavior)
  - [ ] Verify successful login
  - [ ] Create `testMailLoginDisabledFallsBackToUsername()` method
  - [ ] Test behavior when mail_login disabled
  - [ ] Verify username login still works

### Functional Error Scenarios
- [ ] **Prompt 9: Add Functional Tests for Email-Only Mode and Error Scenarios**
  - [ ] Create `testEmailOnlyModeRejectsUsername()` method
  - [ ] Enable mail_login_email_only mode
  - [ ] Attempt login with username
  - [ ] Verify error message appears and login fails
  - [ ] Test successful login with email address
  - [ ] Create `testBlockedUserLoginFailure()` method
  - [ ] Create blocked user account
  - [ ] Attempt login
  - [ ] Verify appropriate error message and login failure
  - [ ] Create `testInvalidCredentialsShowError()` method
  - [ ] Test login with wrong password
  - [ ] Verify error message display
  - [ ] Ensure no sensitive information leaked

### Case Sensitivity Functional Tests
- [ ] **Prompt 10: Add Case Sensitivity Functional Tests**
  - [ ] Create `testCaseInsensitiveEmailLogin()` method
  - [ ] Create user with lowercase email
  - [ ] Configure mail_login_case_sensitive = FALSE
  - [ ] Test login with mixed-case email
  - [ ] Verify successful login
  - [ ] Create `testCaseSensitiveEmailLogin()` method
  - [ ] Configure mail_login_case_sensitive = TRUE
  - [ ] Test case-sensitive matching works correctly
  - [ ] Verify case mismatches fail appropriately
  - [ ] Add data providers for multiple email case variations

### Admin Form Testing
- [ ] **Prompt 11: Create Admin Settings Form Unit Tests**
  - [ ] Create `tests/src/Unit/Form/MailLoginAdminSettingsFormTest.php`
  - [ ] Test `getFormId()` returns correct form ID
  - [ ] Test `getEditableConfigNames()` returns correct config names
  - [ ] Test `buildForm()` creates expected form structure
  - [ ] Test `submitForm()` saves configuration correctly
  - [ ] Mock ConfigFactoryInterface and Config objects
  - [ ] Test form building with various configuration states
  - [ ] Verify form submission saves all configuration values

### Enhanced Coverage
- [ ] **Prompt 12: Add Data Providers and Edge Case Tests**
  - [ ] Add data providers for multiple email formats
  - [ ] Test common formats (user@domain.com, test.user@domain.org)
  - [ ] Test edge cases (user+tag@domain.co.uk, etc.)
  - [ ] Add edge case tests for empty/null identifiers
  - [ ] Test invalid email formats
  - [ ] Test multiple users with similar emails (case-insensitive)
  - [ ] Test database connection failures (if applicable)
  - [ ] Enhance existing tests with data providers
  - [ ] Add performance considerations for test execution

### Documentation
- [ ] **Prompt 13: Create Test Documentation and README**
  - [ ] Create `tests/README.md`
  - [ ] Add overview of test coverage and organization
  - [ ] Add setup instructions and prerequisites
  - [ ] Document how to run individual tests and test suites
  - [ ] Explain test categories (unit vs functional)
  - [ ] Add troubleshooting common issues section
  - [ ] Enhance inline documentation with improved docblocks
  - [ ] Add comments explaining test setup and assertions
  - [ ] Document test data and configuration requirements
  - [ ] Include examples of running tests (individual, methods, full suite)

### Quality Assurance
- [ ] **Prompt 14: Add Test Quality Assurance and Validation**
  - [ ] Add test validation methods
  - [ ] Verify all tests pass consistently
  - [ ] Check proper error handling in tests themselves
  - [ ] Validate test isolation (tests don't affect each other)
  - [ ] Ensure Drupal coding standards compliance
  - [ ] Optimize test performance where possible
  - [ ] Add any missing assertions or edge cases
  - [ ] Create test execution scripts or commands
  - [ ] Add quick smoke test execution
  - [ ] Add full test suite with coverage
  - [ ] Add individual component testing
  - [ ] Final integration verification
  - [ ] Ensure proper Drupal testing framework integration
  - [ ] Verify no orphaned or unused test code
  - [ ] Confirm all configuration scenarios covered

## Testing and Validation

### Test Execution Verification
- [ ] Run individual unit test file: `./vendor/bin/phpunit tests/src/Unit/AuthDecoratorTest.php`
- [ ] Run individual functional test file: `./vendor/bin/phpunit tests/src/Functional/AuthenticationTest.php`
- [ ] Run admin form unit tests: `./vendor/bin/phpunit tests/src/Unit/Form/MailLoginAdminSettingsFormTest.php`
- [ ] Run all unit tests: `./vendor/bin/phpunit tests/src/Unit/`
- [ ] Run all functional tests: `./vendor/bin/phpunit tests/src/Functional/`
- [ ] Run complete test suite: `./vendor/bin/phpunit tests/`

### Code Quality Checks
- [ ] Verify Drupal coding standards compliance
- [ ] Check all docblocks are complete and accurate
- [ ] Ensure proper namespace declarations
- [ ] Verify all use statements are necessary and correct
- [ ] Check method visibility declarations
- [ ] Ensure proper error handling throughout

### Configuration Coverage Verification
- [ ] Test with mail_login_enabled = TRUE/FALSE
- [ ] Test with mail_login_case_sensitive = TRUE/FALSE
- [ ] Test with mail_login_email_only = TRUE/FALSE
- [ ] Test with mail_login_override_login_labels = TRUE/FALSE
- [ ] Verify all configuration combinations work correctly

### Error Message Testing
- [ ] Verify "Login by username has been disabled" message
- [ ] Verify "The user has not been activated yet or is blocked" message
- [ ] Test authentication failure messages
- [ ] Ensure partial text matching works correctly
- [ ] Verify no sensitive information is leaked in error messages

### Performance and Reliability
- [ ] Verify unit tests run quickly (< 1 second each)
- [ ] Ensure functional tests complete in reasonable time
- [ ] Check for test isolation (no test affects another)
- [ ] Verify tests are deterministic (same result every time)
- [ ] Test with different PHP versions if applicable

## Phase 1 Completion Criteria

### Quality Gates Checklist
- [ ] **Tests Pass Consistently**: All tests pass without flaky behavior
- [ ] **Drupal Coding Standards**: Code follows Drupal conventions and PHPUnit best practices
- [ ] **Documentation Standards**: Clear docblocks, descriptive names, inline comments
- [ ] **Maintainability**: Code structure allows easy extension and modification

### Deliverable Verification
- [ ] **Test Files**: Both unit and functional test classes complete
- [ ] **Documentation**: README with setup and execution instructions
- [ ] **Setup Instructions**: All required configuration and dependencies documented
- [ ] **Code Quality**: Standards-compliant, well-documented code

### Final Validation
- [ ] All 14 prompts implemented successfully
- [ ] Test suite demonstrates consistent, reliable execution
- [ ] Documentation meets specified requirements
- [ ] Code quality standards satisfied
- [ ] All core authentication scenarios covered
- [ ] Ready for Phase 2 (Critical Path Coverage)

## Notes and Reminders

### Key Testing Principles
- **Incremental Complexity**: Each step builds on previous without big jumps
- **Test Isolation**: Each test is independent and doesn't affect others
- **Realistic Data**: Use common email formats and typical usernames
- **Behavioral Assertions**: Test return values, method calls, and side effects
- **Error Message Testing**: Use partial text matching for robustness

### Common Pitfalls to Avoid
- Don't create tests that depend on other tests
- Don't use hardcoded paths or environment-specific values
- Don't skip error message testing
- Don't forget to test configuration edge cases
- Don't neglect performance considerations for unit tests

### Success Indicators
- All tests pass on first run
- Tests provide clear failure messages when they fail
- Code coverage includes all critical authentication paths
- Documentation enables other developers to understand and extend tests
- Test execution is fast enough for regular development use
