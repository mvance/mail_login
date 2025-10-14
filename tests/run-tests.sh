#!/bin/bash

# Mail Login Module - Test Execution Script
# Comprehensive test runner with multiple execution modes

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

# Default options
VERBOSE=false
COVERAGE=false
STOP_ON_FAILURE=false
FILTER=""
TEST_TYPE="all"

# Usage function
usage() {
    echo "Usage: $0 [OPTIONS] [TEST_TYPE]"
    echo
    echo "Test Types:"
    echo "  all          Run all tests (default)"
    echo "  unit         Run unit tests only"
    echo "  functional   Run functional tests only"
    echo "  smoke        Run smoke tests only"
    echo "  auth         Run AuthDecorator tests only"
    echo "  form         Run admin form tests only"
    echo
    echo "Options:"
    echo "  -v, --verbose       Enable verbose output"
    echo "  -c, --coverage      Generate coverage report (requires Xdebug)"
    echo "  -s, --stop-on-fail  Stop on first failure"
    echo "  -f, --filter FILTER Filter tests by name/pattern"
    echo "  -h, --help          Show this help message"
    echo
    echo "Examples:"
    echo "  $0                           # Run all tests"
    echo "  $0 unit                      # Run unit tests only"
    echo "  $0 -v functional             # Run functional tests with verbose output"
    echo "  $0 -s -f testEmailLogin      # Run tests matching 'testEmailLogin', stop on failure"
    echo "  $0 -c all                    # Run all tests with coverage report"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        -c|--coverage)
            COVERAGE=true
            shift
            ;;
        -s|--stop-on-fail)
            STOP_ON_FAILURE=true
            shift
            ;;
        -f|--filter)
            FILTER="$2"
            shift 2
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        all|unit|functional|smoke|auth|form)
            TEST_TYPE="$1"
            shift
            ;;
        *)
            echo "Unknown option: $1"
            usage
            exit 1
            ;;
    esac
done

# Build PHPUnit command
build_phpunit_cmd() {
    local cmd="$PHPUNIT_CMD $PHPUNIT_CONFIG"
    
    if [ "$VERBOSE" = true ]; then
        cmd="$cmd --verbose"
    fi
    
    if [ "$COVERAGE" = true ]; then
        cmd="$cmd --coverage-text"
    fi
    
    if [ "$STOP_ON_FAILURE" = true ]; then
        cmd="$cmd --stop-on-failure"
    fi
    
    if [ -n "$FILTER" ]; then
        cmd="$cmd --filter $FILTER"
    fi
    
    echo "$cmd"
}

# Execute test command
execute_test() {
    local test_name="$1"
    local test_path="$2"
    local cmd=$(build_phpunit_cmd)
    
    echo -e "${BLUE}=== Running $test_name ===${NC}"
    echo "Command: $cmd $test_path"
    echo
    
    if eval "$cmd $test_path"; then
        echo -e "${GREEN}✓ $test_name completed successfully${NC}"
        return 0
    else
        echo -e "${RED}✗ $test_name failed${NC}"
        return 1
    fi
}

# Main execution function
main() {
    echo -e "${BLUE}=== Mail Login Module Test Runner ===${NC}"
    echo "Test type: $TEST_TYPE"
    echo "Verbose: $VERBOSE"
    echo "Coverage: $COVERAGE"
    echo "Stop on failure: $STOP_ON_FAILURE"
    if [ -n "$FILTER" ]; then
        echo "Filter: $FILTER"
    fi
    echo
    
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
    
    local exit_code=0
    
    case $TEST_TYPE in
        "smoke")
            echo "Running smoke tests..."
            if [ -f "$TEST_PATH/smoke-test.sh" ]; then
                bash "$TEST_PATH/smoke-test.sh" || exit_code=1
            else
                # Fallback smoke tests
                execute_test "Smoke Test - Email Lookup" "$TEST_PATH/src/Unit/AuthDecoratorTest.php --filter testLookupAccountWithValidEmail" || exit_code=1
                execute_test "Smoke Test - Authentication" "$TEST_PATH/src/Unit/AuthDecoratorTest.php --filter testAuthenticateWithValidCredentials" || exit_code=1
                execute_test "Smoke Test - Functional Login" "$TEST_PATH/src/Functional/AuthenticationTest.php --filter testEmailLoginSuccess" || exit_code=1
            fi
            ;;
        "unit")
            execute_test "Unit Tests" "$TEST_PATH/src/Unit/" || exit_code=1
            ;;
        "functional")
            execute_test "Functional Tests" "$TEST_PATH/src/Functional/" || exit_code=1
            ;;
        "auth")
            execute_test "AuthDecorator Tests" "$TEST_PATH/src/Unit/AuthDecoratorTest.php" || exit_code=1
            ;;
        "form")
            execute_test "Admin Form Tests" "$TEST_PATH/src/Unit/Form/MailLoginAdminSettingsFormTest.php" || exit_code=1
            ;;
        "all")
            execute_test "Complete Test Suite" "$TEST_PATH/" || exit_code=1
            ;;
        *)
            echo -e "${RED}✗ Unknown test type: $TEST_TYPE${NC}"
            usage
            exit 1
            ;;
    esac
    
    echo
    
    if [ $exit_code -eq 0 ]; then
        echo -e "${GREEN}=== ALL TESTS COMPLETED SUCCESSFULLY ===${NC}"
        echo "✓ Test execution finished without errors"
    else
        echo -e "${RED}=== TEST FAILURES DETECTED ===${NC}"
        echo "✗ Some tests failed - review output above"
    fi
    
    return $exit_code
}

# Run main function
main "$@"
