# Mail Login Module - PHPUnit Testing Specification

## Overview

This specification defines a phased approach to implementing comprehensive PHPUnit tests for the Drupal mail_login module. The testing will be implemented in three phases: Basic Coverage, Critical Path Coverage, and Comprehensive Coverage.

## Phase 1: Basic Coverage - Authentication Flow Testing

### Scope and Objectives

Phase 1 focuses on testing the core authentication functionality of the mail_login module, specifically the `AuthDecorator` class and complete login flows. This phase establishes the foundation for all subsequent testing phases.

### Test Architecture

#### Test Organization Structure
- **Unit Tests**: `tests/src/Unit/AuthDecoratorTest.php`
- **Functional Tests**: `tests/src/Functional/AuthenticationTest.php`

#### Test Types
- **Unit Tests**: Isolated testing of `AuthDecorator` class methods with fully mocked dependencies
- **Integration Tests**: End-to-end browser-based testing of complete login flows

### Test Scenarios Coverage

#### Core Authentication Scenarios
1. **Happy Path Scenarios**
   - Valid email login with mail_login enabled
   - Valid username login (fallback behavior)
   - Successful authentication with correct credentials

2. **Email-Specific Scenarios**
   - Email login when mail_login is enabled/disabled
   - Case-sensitive vs case-insensitive email matching
   - Email-only mode (username login disabled)

3. **Error Handling Scenarios**
   - Invalid credentials (wrong password)
   - Blocked/inactive user accounts
   - Non-existent user accounts
   - Invalid email format handling

#### Configuration Testing Matrix
Test the following configuration combinations:
- `mail_login_enabled`: TRUE/FALSE
- `mail_login_case_sensitive`: TRUE/FALSE  
- `mail_login_email_only`: TRUE/FALSE

### Technical Implementation Details

#### Mocking Strategy (Unit Tests)
- **Full Mocking Approach**: Mock all dependencies for complete isolation
- **Dependencies to Mock**:
  - `ConfigFactoryInterface` and `Config` objects
  - `EntityTypeManagerInterface` and `EntityStorageInterface`
  - `Connection` (database)
  - `MessengerInterface`
  - `UserAuthInterface`
  - `UserInterface` objects

#### Test Data Strategy
- **Realistic Test Data**: Use common email formats and typical usernames
- **Mixed Approach**: 
  - Simple static users for basic test scenarios
  - Data providers for testing email format variations and edge cases
- **Example Test Data**:
  - Emails: `user@example.com`, `test.user@domain.org`, `admin@site.co.uk`
  - Usernames: `testuser`, `admin`, `user123`
  - Passwords: Standard complexity passwords for testing

#### Assertion Strategy
- **Behavioral Assertions**: Test return values, method calls, and side effects
- **Error Message Testing**: Partial text matching for robustness
- **Key Assertions**:
  - Verify correct user objects are returned
  - Confirm appropriate error messages are displayed
  - Validate that correct methods are called on dependencies
  - Check configuration-dependent behavior changes

#### Performance Considerations
- **Balanced Approach**:
  - Unit tests optimized for speed (fast execution, minimal setup)
  - Functional tests allowed to be more thorough (complete user experience)
- **Test Isolation**: Each test should be independent and not affect others

### Quality Standards and Completion Criteria

#### Quality Gates for Phase 1 Completion
1. **Tests Pass Consistently**: No flaky or intermittently failing tests
2. **Drupal Coding Standards**: Follow Drupal coding conventions and PHPUnit best practices
3. **Documentation Standards**: 
   - Clear docblocks for all test methods
   - Descriptive test method names
   - Inline comments explaining complex test logic
4. **Maintainability**: Code structure allows easy extension and modification

#### Documentation Requirements
- **Inline Documentation**: Comprehensive docblocks and comments within test files
- **Basic README**: Setup instructions, execution commands, and overview of test coverage

### File Structure

```
tests/
├── src/
│   ├── Unit/
│   │   └── AuthDecoratorTest.php
│   └── Functional/
│       └── AuthenticationTest.php
└── README.md
```

### Dependencies and Setup

#### Required Dependencies
- PHPUnit (via Drupal core)
- Drupal Test Traits
- Standard Drupal testing framework

#### Test Execution
- **Development Phase**: Manual execution via PHPUnit commands
- **Commands**:
  ```bash
  # Run unit tests only
  ./vendor/bin/phpunit tests/src/Unit/AuthDecoratorTest.php
  
  # Run functional tests only  
  ./vendor/bin/phpunit tests/src/Functional/AuthenticationTest.php
  
  # Run all Phase 1 tests
  ./vendor/bin/phpunit tests/src/Unit/ tests/src/Functional/
  ```

### Detailed Test Specifications

#### Unit Test Class: AuthDecoratorTest

**Test Methods Required**:
1. `testLookupAccountWithValidEmail()` - Test successful email lookup
2. `testLookupAccountWithValidUsername()` - Test username fallback
3. `testLookupAccountWithBlockedUser()` - Test blocked user handling
4. `testLookupAccountEmailOnlyModeWithUsername()` - Test email-only restriction
5. `testLookupAccountCaseInsensitive()` - Test case-insensitive email matching
6. `testLookupAccountCaseSensitive()` - Test case-sensitive email matching
7. `testLookupAccountMailLoginDisabled()` - Test behavior when mail login disabled
8. `testAuthenticateWithValidCredentials()` - Test successful authentication
9. `testAuthenticateWithInvalidCredentials()` - Test failed authentication
10. `testAuthenticateAccountMethod()` - Test the authenticateAccount wrapper

#### Functional Test Class: AuthenticationTest

**Test Methods Required**:
1. `testEmailLoginSuccess()` - Complete email login flow
2. `testUsernameLoginSuccess()` - Complete username login flow  
3. `testEmailOnlyModeRejectsUsername()` - Test email-only mode enforcement
4. `testCaseInsensitiveEmailLogin()` - Test case-insensitive email login
5. `testBlockedUserLoginFailure()` - Test blocked user cannot login
6. `testInvalidCredentialsShowError()` - Test error messages for bad credentials
7. `testMailLoginDisabledFallsBackToUsername()` - Test fallback behavior

### Error Handling Strategy

#### Error Message Testing
- **Approach**: Partial text matching for robustness
- **Key Error Messages to Test**:
  - "Login by username has been disabled"
  - "The user has not been activated yet or is blocked"
  - Authentication failure messages
- **Implementation**: Use `assertStringContainsString()` for partial matching

#### Exception Handling
- Test graceful handling of database errors
- Verify proper fallback behavior when services are unavailable
- Ensure no sensitive information is leaked in error messages

### Phase Transition Criteria

Phase 1 is considered complete when:
1. All specified test methods are implemented and passing
2. Code follows Drupal coding standards and PHPUnit best practices
3. Documentation meets the specified requirements (inline docs + basic README)
4. Tests demonstrate consistent, reliable execution
5. Quality gates are satisfied

### Deliverables

#### Complete Test Package Includes
1. **Test Files**: Both unit and functional test classes
2. **Documentation**: README with setup and execution instructions
3. **Setup Instructions**: Any required configuration or dependencies
4. **Code Quality**: Standards-compliant, well-documented code

#### README.md Contents
- Overview of test coverage
- Setup instructions
- How to run tests (individual and complete suites)
- Brief explanation of test organization
- Prerequisites and dependencies

### Future Phases Preview

- **Phase 2 (Critical Path Coverage)**: Security-focused testing, form validation, configuration edge cases
- **Phase 3 (Comprehensive Coverage)**: Performance testing, accessibility, internationalization, advanced edge cases

This specification provides a complete foundation for implementing robust authentication testing for the mail_login module, ensuring both developer productivity and code quality from the start.
