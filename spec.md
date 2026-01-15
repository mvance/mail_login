# Mail Login Module - Test Specification

## Overview

This specification documents the comprehensive PHPUnit test suite for the Drupal mail_login module. The test suite provides thorough coverage of email-based authentication functionality through unit tests (isolated component testing) and functional tests (end-to-end browser-based testing).

## Test Architecture

### File Structure

```
tests/
├── src/
│   ├── Unit/
│   │   ├── AuthDecoratorTest.php
│   │   └── Form/
│   │       └── MailLoginAdminSettingsFormTest.php
│   └── Functional/
│       └── AuthenticationTest.php
├── README.md
├── run-tests.sh
├── smoke-test.sh
└── validate-tests.sh
```

### Test Types

| Type | Purpose | Speed | Dependencies |
|------|---------|-------|--------------|
| **Unit Tests** | Isolated component testing | Fast (<1s per test) | All dependencies mocked |
| **Functional Tests** | End-to-end user workflows | Slower (several seconds) | Full Drupal bootstrap, real database |

## Unit Tests

### AuthDecoratorTest.php

Tests the core `AuthDecorator` class which handles email-based authentication logic.

| Test Method | Description |
|-------------|-------------|
| `testAuthDecoratorInstantiation()` | Verifies decorator implements UserAuthInterface and UserAuthenticationInterface |
| `testLookupAccountWithValidEmail()` | Tests successful email lookup with matching user |
| `testLookupAccountWithValidUsername()` | Tests username fallback when email lookup fails |
| `testLookupAccountEmailOnlyModeWithUsername()` | Verifies email-only mode rejects username login |
| `testLookupAccountMailLoginDisabled()` | Tests behavior when mail_login_enabled is FALSE |
| `testLookupAccountCaseSensitive()` | Tests exact-case email matching |
| `testCaseInsensitiveConflicts()` | Tests case-insensitive email matching with LIKE query |
| `testLookupAccountWithBlockedUser()` | Verifies blocked users cannot authenticate |
| `testAuthenticateWithValidCredentials()` | Tests successful authentication returns user ID |
| `testAuthenticateWithInvalidCredentials()` | Tests failed authentication with wrong password |
| `testAuthenticateAccountMethod()` | Tests authenticateAccount() with UserAuthenticationInterface |
| `testAuthenticateAccountMethodLegacy()` | Tests authenticateAccount() with legacy UserAuthInterface |
| `testVariousEmailFormats()` | Data provider test for different email formats |
| `testEdgeCaseIdentifiers()` | Data provider test for edge cases (empty, Unicode, long strings) |
| `testLookupAccountWithMultipleUsersSameEmail()` | Tests handling when multiple users share an email |
| `testAuthenticateWithValidCredentialsAndUserAuthenticationInterface()` | Tests authentication with modern interface |
| `testSuccessfulFallbackAuthentication()` | Tests fallback to original auth service |
| `testCaseInsensitiveWithMultipleMatches()` | Verifies FALSE returned when multiple case-insensitive matches |

### Form/MailLoginAdminSettingsFormTest.php

Tests the admin configuration form for mail_login settings.

| Test Method | Description |
|-------------|-------------|
| `testGetFormId()` | Verifies form returns correct ID |
| `testGetEditableConfigNames()` | Verifies correct config names are editable |
| `testBuildForm()` | Tests form structure with default configuration |
| `testFormWithDifferentConfigs()` | Tests form reflects non-default configuration values |
| `testSubmitForm()` | Tests configuration saving on form submission |
| `testSubmitMinimalValues()` | Tests submission with empty/minimal values |
| `testConfigurationScenarios()` | Data provider test for all config combinations |
| `testSubmitFormWithEdgeCaseTextValues()` | Data provider test for edge case text inputs |
| `testFormValidation()` | Tests form validation with long and malicious input |
| `testFormValidationWithInvalidData()` | Data provider test for invalid form data scenarios |
| `testFormSecurityAndValidation()` | Tests XSS and SQL injection handling in form |
| `testFormConfigurationIntegrity()` | Tests form handles missing configuration gracefully |
| `testFormBuildingStability()` | Tests form can be built repeatedly without state issues |

## Functional Tests

### AuthenticationTest.php

End-to-end browser-based tests for complete login workflows.

| Test Method | Description |
|-------------|-------------|
| `testEmailLoginSuccess()` | Complete successful login flow using email |
| `testUsernameLoginSuccess()` | Complete successful login flow using username |
| `testMailLoginDisabledFallsBackToUsername()` | Tests username works and email fails when disabled |
| `testEmailOnlyMode()` | Tests username rejected and email accepted in email-only mode |
| `testBlockedUserHandling()` | Tests blocked users cannot login via email or username |
| `testInvalidCredentials()` | Tests error messages for wrong password |
| `testCaseInsensitiveEmailLogin()` | Tests login with various email case variations |
| `testCaseSensitiveEmailLogin()` | Tests exact-case email matching with multiple users |
| `testLoginWithVariousEmailFormats()` | Data provider test for different email formats |
| `testLoginWithInvalidScenarios()` | Data provider test for invalid login attempts |
| `testPasswordComplexity()` | Data provider test for various password formats |
| `testSqlInjectionPrevention()` | Tests SQL injection attempts are handled safely |
| `testXssPrevention()` | Tests XSS attempts are handled safely |
| `testFunctionalLoginWithUnicodeEmails()` | Data provider test for Unicode email addresses |
| `testUnicodePasswordHandling()` | Tests authentication with Unicode passwords |
| `testCaseInsensitiveUnicodeMatching()` | Tests case-insensitive matching with accented characters |
| `testUnicodeEmailHandling()` | Tests various Unicode email formats handled gracefully |
| `testBrowserUnicodeRendering()` | Tests Unicode characters render correctly in browser |
| `testMultipleEmailConflicts()` | Tests handling of 3+ users with case-variant emails |
| `testConflictResolution()` | Tests system handles ambiguous email matches gracefully |
| `testConflictErrorMessages()` | Tests appropriate errors shown for conflicting users |
| `testConflictingScenariosWithProvider()` | Data provider test for various conflict scenarios |
| `testConflictEdgeCases()` | Tests edge cases like trailing spaces in emails |
| `testCaseInsensitiveConflicts()` | Tests case-insensitive mode with conflicting users |

## Configuration Coverage

The test suite covers all combinations of mail_login configuration options:

| Configuration Key | Values Tested | Coverage |
|-------------------|---------------|----------|
| `mail_login_enabled` | TRUE, FALSE | Unit + Functional |
| `mail_login_case_sensitive` | TRUE, FALSE | Unit + Functional |
| `mail_login_email_only` | TRUE, FALSE | Unit + Functional |
| `mail_login_override_login_labels` | TRUE, FALSE | Form tests |
| Text configuration fields | Empty, normal, edge cases | Form tests |

## Security Testing

The test suite includes explicit security validation:

| Security Concern | Test Coverage |
|------------------|---------------|
| SQL Injection | `testSqlInjectionPrevention()`, form validation tests |
| XSS (Cross-Site Scripting) | `testXssPrevention()`, form validation tests |
| Sensitive Data Leakage | Assertions verify passwords not displayed in errors |
| Blocked User Access | `testBlockedUserHandling()`, `testLookupAccountWithBlockedUser()` |

## Technical Implementation

### Mocking Strategy (Unit Tests)

All external dependencies are mocked for complete isolation:

- `ConfigFactoryInterface` and `Config` objects
- `EntityTypeManagerInterface` and `EntityStorageInterface`
- `Connection` (database)
- `MessengerInterface`
- `UserAuthInterface` / `UserAuthenticationInterface`
- `UserInterface` objects

### Test Data Strategy

#### Email Formats Tested
- Standard: `user@example.com`
- Subdomains: `admin@mail.example.com`
- Plus addressing: `user+tag@example.com`
- Dots: `first.last@example.com`
- International TLDs: `contact@example.co.uk`
- Unicode: `café@example.com`, `用户@example.com`
- Edge cases: Very long emails, special characters

#### Password Formats Tested
- Simple: `simple123`
- Complex: `C0mpl3x!P@ssw0rd`
- Spaces: `password with spaces`
- Unicode: `pässwörd123`, `パスワード`

### Assertion Strategy

- **Behavioral Assertions**: Test return values, method calls, and side effects
- **Error Message Testing**: Partial text matching for cross-version robustness
- **Key Assertions**:
  - Verify correct user objects are returned
  - Confirm appropriate error messages are displayed
  - Validate configuration-dependent behavior changes

## Test Execution

For detailed instructions on running tests, including helper scripts, CI/CD integration, and troubleshooting, see **[tests/README.md](tests/README.md)**.

### Quick Reference

```bash
# Smoke test (quick validation)
bash tests/smoke-test.sh

# Full test suite
bash tests/run-tests.sh

# Unit tests only
./vendor/bin/phpunit -c web/core/phpunit.xml.dist tests/src/Unit/

# Functional tests only
./vendor/bin/phpunit -c web/core/phpunit.xml.dist tests/src/Functional/
```

## Quality Standards

- **Test Isolation**: Each test is independent with no cross-test dependencies
- **Drupal Coding Standards**: All code follows Drupal conventions
- **Documentation**: Comprehensive docblocks on all test methods
- **Consistent Results**: Tests pass reliably across multiple runs
- **Maintainability**: Code structure allows easy extension and modification
