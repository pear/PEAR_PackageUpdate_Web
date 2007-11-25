<?php
/**
 * PEAR_PackageUpdate_Web Package Script Generator
 *
 * Generate a new fresh version of package xml 2.0
 * built with PEAR_PackageFileManager 1.6.0+
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  PEAR
 * @package   PEAR_PackageUpdate_Web
 * @author    Laurent Laville <pear@laurent-laville.org>
 * @copyright 2007 The PHP Group
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/PEAR_PackageUpdate
 * @since     File available since Release 0.4.0
 * @ignore
 */

require_once 'PEAR/PackageFileManager2.php';

PEAR::setErrorHandling(PEAR_ERROR_DIE);

$packagefile = 'c:/php/pear/PEAR_PackageUpdate_Web/package2.xml';

$options = array('filelistgenerator' => 'cvs',
    'packagefile' => 'package2.xml',
    'baseinstalldir' => 'PEAR/PackageUpdate',
    'addhiddenfiles' => true,
    'simpleoutput' => true,
    'clearcontents' => false,
    'changelogoldtonew' => false,
    'ignore' => array('package.php')
    );

$p2 = &PEAR_PackageFileManager2::importOptions($packagefile, $options);
$p2->setPackageType('php');
$p2->addRelease();
$p2->generateContents();
$p2->setReleaseVersion('0.4.0');
$p2->setAPIVersion('1.0.0');
$p2->setReleaseStability('beta');
$p2->setAPIStability('beta');
$p2->setNotes('* IMPORTANT
This beta version will be the last one before final stable release 1.0.0

* changes
- copyright notice bumped to 2007
- removed PPU resource from main class.
- prevent updating new copy of a package if it was not installed first
');

if (isset($_GET['make'])
    || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
    $p2->writePackageFile();
} else {
    $p2->debugPackageFile();
}
?>