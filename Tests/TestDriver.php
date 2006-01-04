<?php

if (defined('E_STRICT')) {
    // PHP 5
    $_Net_OpenID_allowed_deprecation =
        array('var',
              'is_a()'
              );

    function ignoreDeprecation($errno, $errstr, $errfile, $errline) {
        // Handle http://bugs.php.net/bug.php?id=32428
        // Augment this
        // regular expression if the bug exists in another version.
        if (preg_match('/^5\.1\.1$/', phpversion()) && $errno == 2) {
            $allowed_files = array(array('/Net/OpenID/CryptUtil.php',
                                         'xxx'),
                                   array('/Net/OpenID/OIDUtil.php',
                                         'parse_url'));

            foreach ($allowed_files as $entry) {
                list($afile, $msg) = $entry;
                $slen = strlen($afile);
                $slice = substr($errfile, strlen($errfile) - $slen, $slen);
                if ($slice == $afile && strpos($errstr, $msg) == 0) {
                    // Ignore this error
                    return;
                }
            }
        }

        global $_Net_OpenID_allowed_deprecation;

        switch ($errno) {
        case E_STRICT:
            // XXX: limit this to files we know about
            foreach ($_Net_OpenID_allowed_deprecation as $depr) {
                if (strpos($errstr, "$depr: Deprecated.") !== false) {
                    return;
                }
            }
        default:
            error_log("$errfile:$errline - $errno: $errstr");
        }
    }

    set_error_handler('ignoreDeprecation');
    error_reporting(E_STRICT | E_ALL);
} else {
    error_reporting(E_ALL);
}

require_once('PHPUnit.php');
require_once('PHPUnit/GUI/HTML.php');

/**
 * Load the tests that are defined in the named modules.
 *
 * If you have Tests/Foo.php which defines a test class called
 * Tests_Foo, the call would look like:
 *
 * loadTests('Tests/', array('Foo'))
 *
 * @param string $test_dir The root of the test hierarchy. Must end
 * with a /
 *
 * @param array $test_names The names of the modules in which the
 * tests are defined. This should not include the root of the test
 * hierarchy.
 */
function loadTests($test_dir, $test_names) {
    $suites = array();

    foreach ($test_names as $filename) {
        $filename = $test_dir . $filename . '.php';
        $class_name = str_replace(DIRECTORY_SEPARATOR, '_', $filename);
        $class_name = basename($class_name, '.php');
        include_once($filename);
        $test = new $class_name($class_name);
        if (is_a($test, 'PHPUnit_TestCase')) {
            $test = new PHPUnit_TestSuite($class_name);
        }
        $suites[] = $test;
    }

    return $suites;
}

$_test_dir = 'Tests/Net/OpenID/';
$_test_names = array(
    'KVForm',
    'CryptUtil',
    'OIDUtil',
    'DiffieHellman',
    'HMACSHA1',
    'Association'
    );

// Only run store tests if -s or --test-stores is specified on the
// command line because store backends will probably not be installed.
if (in_array('--test-stores', $argv) ||
    in_array('-s', $argv)) {
    $_test_names[] = 'StoreTest';
}

// Load OpenID library tests
function loadSuite() {
    global $_test_names;
    global $_test_dir;
    return loadTests($_test_dir, $_test_names);
}
?>
