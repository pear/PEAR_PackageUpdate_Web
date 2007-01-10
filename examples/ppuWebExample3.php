<?php
/**
 * An example script that update PEAR::DB_DataObject
 * using PEAR_PackageUpdate with a Web front end, and a custom html layout.
 *
 * @author    Laurent Laville
 * @package   PEAR_PackageUpdate_Web
 * @version   $Id$
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @copyright 2006-2007 Laurent Laville
 * @ignore
 */

require_once 'PEAR/PackageUpdate/Web.php';

class PEAR_PackageUpdate_Web3 extends PEAR_PackageUpdate_Web
{
    function toHtml($renderer)
    {
        $styles = basename($this->getStyleSheet(false));

        $body = $renderer->toHtml();

        $html = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=UTF-8" />
<meta name="author" content="Laurent Laville" />
<title>My PPU </title>
<link rel="stylesheet" type="text/css" href="ppugreyskin.css" />
<link rel="stylesheet" type="text/css" href="$styles" />
</head>
<body>

<div id="header">
<h1>Laurent-Laville.org</h1>
</div>

<div id="footer">
</div>

<div id="contents">
$body
</div>

</body>
</html>
HTML;
        return $html;
    }
}

// Create a Web package updater for the DB_DataObject package on pear channel.
$ppu = PEAR_PackageUpdate::factory('Web3', 'DB_DataObject', 'pear');

// Make sure the updater was created properly.
if ($ppu === false) {
    echo "Could not create updater.\n";
    echo "You might want to check for and install updates manually.\n";
    die();
}

// set your own styles, rather than use the default stylesheet
$ppu->setStyleSheet(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ppuweb3.css');

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