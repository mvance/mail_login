#!/bin/bash

# Mail Login Module - Quick Smoke Test
# Fast validation that core functionality works

set -e

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PHPUNIT_CMD="./vendor/bin/phpunit"
PHPUNIT_CONFIG="-c web/core/phpunit.xml.dist"
MODULE_PATH="web/modules/contrib/mail_login"
TEST_PATH="$MODULE_PATH/tests"

echo -e "${BLUE}=== Mail Login Module Smoke Test ===${NC}"
echo "Running quick validation of core functionality..."
echo

# Test core email lookup functionality
echo "Testing core email lookup..."
if $PHPUNIT_CMD $PHPUNIT_CONFIG $TEST_PATH/src/Unit/AuthDecoratorTest.php --filter testLookupAccountWithValidEmail > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Email lookup test passed${NC}"
else
    echo -e "${RED}✗ Email lookup test failed${NC}"
    exit 1
fi

# Test core authentication functionality
echo "Testing core authentication..."
if $PHPUNIT_CMD $PHPUNIT_CONFIG $TEST_PATH/src/Unit/AuthDecoratorTest.php --filter testAuthenticateWithValidCredentials > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Authentication test passed${NC}"
else
    echo -e "${RED}✗ Authentication test failed${NC}"
    exit 1
fi

# Test functional login flow
echo "Testing functional login flow..."
if $PHPUNIT_CMD $PHPUNIT_CONFIG $TEST_PATH/src/Functional/AuthenticationTest.php --filter testEmailLoginSuccess > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Functional login test passed${NC}"
else
    echo -e "${RED}✗ Functional login test failed${NC}"
    exit 1
fi

# Test admin form functionality
echo "Testing admin form..."
if $PHPUNIT_CMD $PHPUNIT_CONFIG $TEST_PATH/src/Unit/Form/MailLoginAdminSettingsFormTest.php --filter testBuildForm > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Admin form test passed${NC}"
else
    echo -e "${RED}✗ Admin form test failed${NC}"
    exit 1
fi

echo
echo -e "${GREEN}=== SMOKE TEST PASSED ===${NC}"
echo "✓ Core functionality is working"
echo "✓ Ready for full test suite execution"
