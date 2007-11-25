<?php
/**
 * An example script that update PEAR::DB_DataObject
 * using PEAR_PackageUpdate with a Web front end.
 *
 * PHP versions 4 and 5
 *
 * @category  PEAR
 * @package   PEAR_PackageUpdate_Web
 * @author    Laurent Laville <pear@laurent-laville.org>
 * @copyright 2006-2007 Laurent Laville
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/PEAR_PackageUpdate_Web
 * @ignore
 */

require_once 'PEAR/PackageUpdate.php';

// Create a Web package updater for the DB_DataObject package on pear channel.
$ppu = PEAR_PackageUpdate::factory('Web', 'DB_DataObject', 'pear');

// Make sure the updater was created properly.
if ($ppu === false) {
    echo "Could not create updater.\n";
    echo "You might want to check for and install updates manually.\n";
    die();
}

// set your own styles, rather than use the default stylesheet
$ppu->setStyleSheet(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ppugreyskin.css');

// Check to see if any updates are availble.
if ($ppu->checkUpdate()) {
    // If updates are available, present the user with the option to update.
    if ($ppu->presentUpdate()) {
        // Update the package.
        $ppu->update();
        $ppu->forceRestart();
    }
}

// Check for errors.
if ($ppu->hasErrors()) {
    $ppu->errorDialog(); // without context details
}

print 'still alive';
?>