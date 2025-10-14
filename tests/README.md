# Mail Login Module - PHPUnit Test Suite

## Overview

This test suite provides comprehensive coverage for the Drupal mail_login module, ensuring that email-based authentication functionality works correctly across different configurations and scenarios. The tests are organized into unit tests and functional tests, covering both isolated component testing and end-to-end user workflows.

## Test Coverage and Organization

### Test Categories

#### Unit Tests (`tests/src/Unit/`)
- **AuthDecoratorTest.php** - Tests the core `AuthDecorator` class in isolation with mocked dependencies
- **Form/MailLoginAdminSettingsFormTest.php** - Tests the admin configuration form functionality

#### Functional Tests (`tests/src/Functional/`)
- **AuthenticationTest.php** - End-to-end browser-based testing of complete login flows

### Coverage Areas

The test suite covers:

- **Core Authentication Logic**: Email lookup, username fallback, password validation
- **Configuration Scenarios**: All mail_login configuration combinations
- **Case Sensitivity**: Both case-sensitive and case-insensitive email matching
- **Email-Only Mode**: Restricting login to email addresses only
- **Error Handling**: Invalid credentials, blocked users, malformed input
- **Security**: XSS prevention, SQL injection protection, input validation
- **Edge Cases**: Empty inputs, special characters, Unicode, very long inputs
- **Performance**: Reasonable execution times for all test scenarios
- **Admin Interface**: Configuration form building and submission

## Setup Instructions and Prerequisites

### Prerequisites

1. **Drupal Environment**: Working Drupal installation with mail_login module
2. **PHPUnit**: Installed via Drupal core (typically in `vendor/bin/phpunit`)
3. **Test Database**: Separate test database (automatically handled by Drupal testing framework)
4. **Permissions**: Write access to temporary directories for test execution

### Environment Setup

1. **Ensure mail_login module is installed**:
   ```bash
   # If using Composer
   composer require drupal/mail_login
   
   # Enable the module
   drush en mail_login
   ```

2. **Verify PHPUnit configuration**:
   ```bash
   # Check that PHPUnit is available
   ./vendor/bin/phpunit --version
   
   # Verify Drupal's PHPUnit configuration exists
   ls web/core/phpunit.xml.dist
   ```

3. **Configure test environment** (if needed):
   - Ensure `SIMPLETEST_DB` environment variable is set for functional tests
   - Verify web server is running for functional tests

## Running Tests

### Quick Test Execution Scripts

The test suite includes convenient scripts for common testing scenarios:

```bash
# Quick smoke test - validates core functionality
bash web/modules/contrib/mail_login/tests/smoke-test.sh

# Comprehensive test runner with options
bash web/modules/contrib/mail_login/tests/run-tests.sh [OPTIONS] [TEST_TYPE]

# Full test validation and quality assurance
bash web/modules/contrib/mail_login/tests/validate-tests.sh
```

### Test Runner Script Usage

The `run-tests.sh` script provides flexible test execution:

```bash
# Run all tests
bash web/modules/contrib/mail_login/tests/run-tests.sh

# Run specific test types
bash web/modules/contrib/mail_login/tests/run-tests.sh unit
bash web/modules/contrib/mail_login/tests/run-tests.sh functional
bash web/modules/contrib/mail_login/tests/run-tests.sh smoke

# Run with options
bash web/modules/contrib/mail_login/tests/run-tests.sh -v unit          # Verbose unit tests
bash web/modules/contrib/mail_login/tests/run-tests.sh -c all           # All tests with coverage
bash web/modules/contrib/mail_login/tests/run-tests.sh -s functional    # Stop on first failure
bash web/modules/contrib/mail_login/tests/run-tests.sh -f testEmail     # Filter by test name
```

### Individual Test Files

Run specific test files to focus on particular functionality:

```bash
# Unit tests for core authentication logic
./vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/mail_login/tests/src/Unit/AuthDecoratorTest.php

# Functional tests for complete login flows
./vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/mail_login/tests/src/Functional/AuthenticationTest.php

# Unit tests for admin settings form
./vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/mail_login/tests/src/Unit/Form/MailLoginAdminSettingsFormTest.php
```

### Specific Test Methods

Run individual test methods for targeted debugging:

```bash
# Test only email login functionality
./vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/mail_login/tests/src/Unit/AuthDecoratorTest.php --filter testLookupAccountWithValidEmail

# Test only case-insensitive scenarios
./vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/mail_login/tests/src/Functional/AuthenticationTest.php --filter testCaseInsensitiveEmailLogin

# Test only form building
./vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/mail_login/tests/src/Unit/Form/MailLoginAdminSettingsFormTest.php --filter testBuildForm
```

### Test Suites

Run groups of related tests:

```bash
# Run all unit tests
./vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/mail_login/tests/src/Unit/

# Run all functional tests
./vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/mail_login/tests/src/Functional/

# Run complete test suite
./vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/mail_login/tests/
```

### Verbose Output and Debugging

Add flags for more detailed output during development:

```bash
# Verbose output with test details
./vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/mail_login/tests/ --verbose

# Stop on first failure for debugging
./vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/mail_login/tests/ --stop-on-failure

# Show test coverage (if Xdebug is available)
./vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/mail_login/tests/ --coverage-text
```

## Test Categories Explained

### Unit Tests vs Functional Tests

#### Unit Tests
- **Purpose**: Test individual components in isolation
- **Speed**: Fast execution (typically < 1 second per test)
- **Dependencies**: All external dependencies are mocked
- **Scope**: Focus on specific methods and their return values
- **Use Cases**: Testing business logic, configuration handling, error conditions

#### Functional Tests
- **Purpose**: Test complete user workflows end-to-end
- **Speed**: Slower execution (several seconds per test)
- **Dependencies**: Real database, web server, full Drupal bootstrap
- **Scope**: Complete user interactions from browser perspective
- **Use Cases**: Testing login flows, form submissions, user experience

### Test Data and Configuration

#### Test Users
The test suite creates various test users with different characteristics:

- **Standard Users**: `testuser` with `test@example.com`
- **Case Variations**: Users with different email case patterns
- **Blocked Users**: Users with `status = 0` for testing access restrictions
- **Complex Passwords**: Users with various password complexity levels

#### Configuration Scenarios
Tests cover all combinations of mail_login settings:

- `mail_login_enabled`: TRUE/FALSE
- `mail_login_case_sensitive`: TRUE/FALSE  
- `mail_login_email_only`: TRUE/FALSE
- `mail_login_override_login_labels`: TRUE/FALSE

#### Email Format Testing
The test suite validates various email formats:

- Standard formats: `user@example.com`
- Subdomains: `admin@mail.example.com`
- Special characters: `user+tag@example.com`, `first.last@example.com`
- International domains: `contact@example.co.uk`
- Edge cases: Very long emails, Unicode characters

## Test Validation and Quality Assurance

### Automated Test Validation

The test suite includes comprehensive validation tools:

```bash
# Run complete test validation
bash web/modules/contrib/mail_login/tests/validate-tests.sh
```

This validation script checks:
- **Test Isolation**: Ensures tests don't affect each other
- **Performance Requirements**: Verifies tests complete within time limits
- **Code Quality**: Checks namespace declarations, use statements, docblocks
- **Configuration Coverage**: Validates all config options are tested
- **Error Message Testing**: Ensures proper error handling

### Quality Gates

The test suite enforces these quality standards:

- **Unit Test Performance**: < 5 seconds total execution time
- **Functional Test Performance**: < 120 seconds total execution time
- **Test Isolation**: Consistent results across multiple runs
- **Code Standards**: Proper Drupal coding conventions
- **Configuration Coverage**: All mail_login settings tested
- **Error Handling**: Graceful handling of edge cases and failures

### Continuous Integration

For CI/CD integration, use these commands:

```bash
# Quick validation for pull requests
bash web/modules/contrib/mail_login/tests/smoke-test.sh

# Full validation for releases
bash web/modules/contrib/mail_login/tests/validate-tests.sh

# Generate coverage reports (requires Xdebug)
bash web/modules/contrib/mail_login/tests/run-tests.sh -c all
```

## Troubleshooting Common Issues

### Test Failures

#### "Class not found" errors
```bash
# Ensure autoloader is up to date
composer dump-autoload

# Verify module is properly installed
drush pm:list | grep mail_login
```

#### Database connection issues
```bash
# Check SIMPLETEST_DB environment variable
echo $SIMPLETEST_DB

# Verify database permissions
# Ensure test database user has CREATE/DROP privileges
```

#### Functional test timeouts
```bash
# Increase timeout in phpunit.xml if needed
# Check web server is running and accessible
# Verify no conflicting modules are interfering
```

#### Test validation failures
```bash
# Run individual validation components
bash web/modules/contrib/mail_login/tests/validate-tests.sh

# Check specific test isolation
./vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/mail_login/tests/ --filter testTestIsolation

# Verify performance requirements
./vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/mail_login/tests/ --filter testPerformance
```

### Performance Issues

#### Slow unit tests
- Check for unmocked dependencies
- Verify database queries aren't being executed
- Review test setup complexity

#### Slow functional tests
- Normal behavior - functional tests are inherently slower
- Consider running unit tests first for rapid feedback
- Use `--stop-on-failure` to avoid running all tests when debugging

### Common Configuration Problems

#### Mail login not working in tests
```bash
# Verify configuration is properly set in test methods
# Check that configureMailLoginSettings() helper is called
# Ensure configuration values are being saved correctly
```

#### User creation failures
```bash
# Check for duplicate usernames/emails in test data
# Verify test isolation - each test should clean up after itself
# Review createTestUser() helper method usage
```

### Debugging Test Logic

#### Adding debug output
```php
// Temporary debug output in tests (remove before committing)
$this->debug('Current user: ' . $user->getAccountName());
$this->debug('Page content: ' . $this->getSession()->getPage()->getContent());
```

#### Inspecting test state
```php
// Check current URL in functional tests
$current_url = $this->getSession()->getCurrentUrl();
$this->debug('Current URL: ' . $current_url);

// Examine page text for debugging
$page_text = $this->getSession()->getPage()->getText();
$this->debug('Page text: ' . substr($page_text, 0, 500));
```

## Development Workflow

### Adding New Tests

1. **Identify the scenario** to test (new feature, bug fix, edge case)
2. **Choose test type** (unit vs functional)
3. **Create test method** with descriptive name
4. **Add proper docblock** explaining the test purpose
5. **Follow existing patterns** for setup and assertions
6. **Run test** to ensure it passes
7. **Add to appropriate data provider** if testing variations

### Test Naming Conventions

- **Unit tests**: `testMethodNameWithScenario()`
- **Functional tests**: `testUserWorkflowDescription()`
- **Data provider tests**: `testScenarioWithDataProvider()` + `@dataProvider providerName`

### Code Quality Standards

- Follow Drupal coding standards
- Include comprehensive docblocks
- Use descriptive variable names
- Add inline comments for complex logic
- Ensure test isolation (no dependencies between tests)
- Mock all external dependencies in unit tests

## Integration with CI/CD

### Automated Testing

The test suite is designed to work with continuous integration systems:

```bash
# Example CI command for complete test run
./vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/mail_login/tests/ --log-junit results.xml
```

### Quality Gates

Tests should be run as part of:
- Pre-commit hooks
- Pull request validation
- Release preparation
- Regular regression testing

## Contributing

When contributing to the test suite:

1. **Maintain test coverage** for new functionality
2. **Follow existing patterns** and conventions
3. **Update documentation** when adding new test categories
4. **Ensure all tests pass** before submitting changes
5. **Add appropriate edge cases** for new features

## Support and Resources

- **Drupal Testing Documentation**: https://www.drupal.org/docs/automated-testing
- **PHPUnit Documentation**: https://phpunit.de/documentation.html
- **Mail Login Module**: https://www.drupal.org/project/mail_login

For issues specific to this test suite, review the test code and inline documentation for implementation details.
