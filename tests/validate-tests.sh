#!/bin/bash

# Mail Login Module - Test Validation Script
# This script provides comprehensive test validation and quality assurance

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PHPUNIT_CMD="./vendor/bin/phpunit"
PHPUNIT_CONFIG="-c web/core/phpunit.xml.dist"
MODULE_PATH="web/modules/contrib/mail_login"
TEST_PATH="$MODULE_PATH/tests"

echo -e "${BLUE}=== Mail Login Module Test Validation ===${NC}"
echo "Starting comprehensive test validation..."
echo

# Function to run a command and capture output
run_test() {
    local test_name="$1"
    local test_cmd="$2"
    local expected_result="$3"
    
    echo -e "${YELLOW}Running: $test_name${NC}"
    
    if eval "$test_cmd" > /tmp/test_output.log 2>&1; then
        if [ "$expected_result" = "pass" ]; then
            echo -e "${GREEN}✓ PASSED${NC}"
            return 0
        else
            echo -e "${RED}✗ FAILED (expected to fail but passed)${NC}"
            return 1
        fi
    else
        if [ "$expected_result" = "fail" ]; then
            echo -e "${GREEN}✓ FAILED AS EXPECTED${NC}"
            return 0
        else
            echo -e "${RED}✗ FAILED${NC}"
            cat /tmp/test_output.log
            return 1
        fi
    fi
}

# Function to check test isolation
check_test_isolation() {
    echo -e "${BLUE}=== Checking Test Isolation ===${NC}"
    
    # Run tests multiple times to ensure consistency
    for i in {1..3}; do
        echo "Test run $i/3..."
        if ! $PHPUNIT_CMD $PHPUNIT_CONFIG $TEST_PATH > /tmp/isolation_test_$i.log 2>&1; then
            echo -e "${RED}✗ Test isolation check failed on run $i${NC}"
            return 1
        fi
    done
    
    echo -e "${GREEN}✓ Test isolation verified - all runs consistent${NC}"
    return 0
}

# Function to validate test performance
check_test_performance() {
    echo -e "${BLUE}=== Checking Test Performance ===${NC}"
    
    # Unit tests should be fast (< 5 seconds total)
    echo "Measuring unit test performance..."
    start_time=$(date +%s.%N)
    if $PHPUNIT_CMD $PHPUNIT_CONFIG $TEST_PATH/src/Unit/ > /tmp/unit_perf.log 2>&1; then
        end_time=$(date +%s.%N)
        duration=$(echo "$end_time - $start_time" | bc -l)
        
        if (( $(echo "$duration < 5.0" | bc -l) )); then
            echo -e "${GREEN}✓ Unit tests completed in ${duration}s (< 5s target)${NC}"
        else
            echo -e "${YELLOW}⚠ Unit tests took ${duration}s (> 5s, consider optimization)${NC}"
        fi
    else
        echo -e "${RED}✗ Unit test performance check failed${NC}"
        return 1
    fi
    
    # Functional tests allowed to be slower (< 120 seconds)
    echo "Measuring functional test performance..."
    start_time=$(date +%s.%N)
    if $PHPUNIT_CMD $PHPUNIT_CONFIG $TEST_PATH/src/Functional/ > /tmp/functional_perf.log 2>&1; then
        end_time=$(date +%s.%N)
        duration=$(echo "$end_time - $start_time" | bc -l)
        
        if (( $(echo "$duration < 120.0" | bc -l) )); then
            echo -e "${GREEN}✓ Functional tests completed in ${duration}s (< 120s target)${NC}"
        else
            echo -e "${YELLOW}⚠ Functional tests took ${duration}s (> 120s, may need optimization)${NC}"
        fi
    else
        echo -e "${RED}✗ Functional test performance check failed${NC}"
        return 1
    fi
    
    return 0
}

# Function to check code quality
check_code_quality() {
    echo -e "${BLUE}=== Checking Code Quality ===${NC}"
    
    # Check for proper namespace declarations
    echo "Checking namespace declarations..."
    if grep -r "^namespace Drupal\\\\Tests\\\\mail_login" $TEST_PATH/src/ > /dev/null; then
        echo -e "${GREEN}✓ Proper namespace declarations found${NC}"
    else
        echo -e "${RED}✗ Missing or incorrect namespace declarations${NC}"
        return 1
    fi
    
    # Check for proper use statements
    echo "Checking use statements..."
    if grep -r "^use Drupal\\\\" $TEST_PATH/src/ > /dev/null; then
        echo -e "${GREEN}✓ Proper use statements found${NC}"
    else
        echo -e "${RED}✗ Missing or incorrect use statements${NC}"
        return 1
    fi
    
    # Check for proper docblocks
    echo "Checking docblocks..."
    if grep -r "\/\*\*" $TEST_PATH/src/ > /dev/null; then
        echo -e "${GREEN}✓ Docblocks found${NC}"
    else
        echo -e "${RED}✗ Missing docblocks${NC}"
        return 1
    fi
    
    # Check for @group annotations
    echo "Checking @group annotations..."
    if grep -r "@group mail_login" $TEST_PATH/src/ > /dev/null; then
        echo -e "${GREEN}✓ Proper @group annotations found${NC}"
    else
        echo -e "${RED}✗ Missing @group annotations${NC}"
        return 1
    fi
    
    return 0
}

# Function to validate configuration coverage
check_configuration_coverage() {
    echo -e "${BLUE}=== Checking Configuration Coverage ===${NC}"
    
    # Check that all configuration options are tested
    local config_options=(
        "mail_login_enabled"
        "mail_login_case_sensitive"
        "mail_login_email_only"
        "mail_login_override_login_labels"
    )
    
    for option in "${config_options[@]}"; do
        if grep -r "$option" $TEST_PATH/src/ > /dev/null; then
            echo -e "${GREEN}✓ Configuration option '$option' is tested${NC}"
        else
            echo -e "${RED}✗ Configuration option '$option' not found in tests${NC}"
            return 1
        fi
    done
    
    return 0
}

# Function to check error message testing
check_error_message_testing() {
    echo -e "${BLUE}=== Checking Error Message Testing ===${NC}"
    
    # Check for key error messages
    local error_messages=(
        "Login by username has been disabled"
        "The user has not been activated yet or is blocked"
    )
    
    for message in "${error_messages[@]}"; do
        if grep -r "$message" $TEST_PATH/src/ > /dev/null; then
            echo -e "${GREEN}✓ Error message '$message' is tested${NC}"
        else
            echo -e "${RED}✗ Error message '$message' not found in tests${NC}"
            return 1
        fi
    done
    
    return 0
}

# Main validation sequence
main() {
    local exit_code=0
    
    echo "Validating test environment..."
    
    # Check if PHPUnit is available
    if ! command -v $PHPUNIT_CMD &> /dev/null; then
        echo -e "${RED}✗ PHPUnit not found at $PHPUNIT_CMD${NC}"
        exit 1
    fi
    
    # Check if test files exist
    if [ ! -d "$TEST_PATH" ]; then
        echo -e "${RED}✗ Test directory not found: $TEST_PATH${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✓ Test environment validated${NC}"
    echo
    
    # Run individual test validations
    echo -e "${BLUE}=== Individual Test Validation ===${NC}"
    
    # Unit tests
    run_test "Unit Tests - AuthDecoratorTest" \
        "$PHPUNIT_CMD $PHPUNIT_CONFIG $TEST_PATH/src/Unit/AuthDecoratorTest.php" \
        "pass" || exit_code=1
    
    run_test "Unit Tests - MailLoginAdminSettingsFormTest" \
        "$PHPUNIT_CMD $PHPUNIT_CONFIG $TEST_PATH/src/Unit/Form/MailLoginAdminSettingsFormTest.php" \
        "pass" || exit_code=1
    
    # Functional tests
    run_test "Functional Tests - AuthenticationTest" \
        "$PHPUNIT_CMD $PHPUNIT_CONFIG $TEST_PATH/src/Functional/AuthenticationTest.php" \
        "pass" || exit_code=1
    
    echo
    
    # Run comprehensive validations
    check_test_isolation || exit_code=1
    echo
    
    check_test_performance || exit_code=1
    echo
    
    check_code_quality || exit_code=1
    echo
    
    check_configuration_coverage || exit_code=1
    echo
    
    check_error_message_testing || exit_code=1
    echo
    
    # Final validation - complete test suite
    echo -e "${BLUE}=== Complete Test Suite Validation ===${NC}"
    run_test "Complete Test Suite" \
        "$PHPUNIT_CMD $PHPUNIT_CONFIG $TEST_PATH/" \
        "pass" || exit_code=1
    
    echo
    
    # Summary
    if [ $exit_code -eq 0 ]; then
        echo -e "${GREEN}=== ALL VALIDATIONS PASSED ===${NC}"
        echo "✓ Test suite is production-ready"
        echo "✓ All quality gates satisfied"
        echo "✓ Phase 1 completion criteria met"
    else
        echo -e "${RED}=== VALIDATION FAILURES DETECTED ===${NC}"
        echo "✗ Some quality gates failed"
        echo "✗ Review and fix issues before proceeding"
    fi
    
    return $exit_code
}

# Run main function
main "$@"
