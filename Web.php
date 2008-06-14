<?php
/**
 * This is a HTML driver for PEAR_PackageUpdate.
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
 * @copyright 2006-2008 Laurent Laville
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/PEAR_PackageUpdate_Web
 * @since     File available since Release 0.1.0
 */

require_once 'HTML/QuickForm.php';

/**
 * This is a HTML driver for PEAR_PackageUpdate.
 *
 * A package to make adding self updating functionality to other
 * packages easy.
 *
 * The interface for this package must allow for the following
 * functionality:
 * - check to see if a new version is available for a given
 *   package on a given channel
 *   - check minimum state
 * - present information regarding the upgrade (version, size)
 *   - inform user about dependencies
 * - allow user to confirm or cancel upgrade
 * - download and install the package
 * - track preferences on a per package basis
 *   - don't ask again
 *   - don't ask until next release
 *   - only ask for state XXXX or higher
 *   - bug/minor/major updates only
 * - update channel automatically
 * - force application to exit when upgrade complete
 *   - PHP-GTK/CLI apps must exit to allow classes to reload
 *   - web front end could send headers to reload certain page
 *
 * This class is simply a wrapper for PEAR classes that actually
 * do the work.
 *
 * EXAMPLE:
 * <code>
 * <?php
 *  class Goo {
 *      function __construct()
 *      {
 *          // Check for updates...
 *          require_once 'PEAR/PackageUpdate.php';
 *          $ppu =& PEAR_PackageUpdate::factory('Web', 'XML_RPC', 'pear');
 *          if ($ppu !== false) {
 *              if ($ppu->checkUpdate()) {
 *                  // Use a dialog window to ask permission to update.
 *                  if ($ppu->presentUpdate()) {
 *                      if ($ppu->update()) {
 *                          // If the update succeeded, the application should
 *                          // be restarted.
 *                          $ppu->forceRestart();
 *                      }
 *                  }
 *              }
 *          }
 *          // ...
 *      }
 *      // ...
 *  }
 * ?>
 * </code>
 *
 * @category  PEAR
 * @package   PEAR_PackageUpdate_Web
 * @author    Laurent Laville <pear@laurent-laville.org>
 * @copyright 2006-2008 Laurent Laville
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PEAR_PackageUpdate_Web
 * @since     Class available since Release 0.1.0
 */

class PEAR_PackageUpdate_Web extends PEAR_PackageUpdate
{
    /**
     * The main Dialog widget.
     *
     * @access public
     * @var    object
     * @since  0.1.0
     */
    var $mainwidget;

    /**
     * The preference Dialog widget.
     *
     * @access public
     * @var    object
     * @since  0.1.0
     */
    var $prefwidget;

    /**
     * The error Dialog widget.
     *
     * @access public
     * @var    object
     * @since  0.1.0
     */
    var $errwidget;

    /**
     * Style sheet for the custom layout
     *
     * @var    string
     * @access public
     * @since  0.3.0
     */
    var $css;

    /**
     * Creates the dialog that will ask the user if it is ok to update.
     *
     * @access protected
     * @return void
     * @since  version 0.1.0 (2006-04-30)
     */
    function createMainDialog()
    {
        // Create the dialog
        $this->mainwidget = new HTML_QuickForm('infoPPU');
        $this->mainwidget->removeAttribute('name');        // XHTML compliance

        // Create a title string.
        $title = 'Update available for: ' . $this->packageName;
        $this->mainwidget->addElement('header', '', $title);

        // Create an image placeholder and the message for the dialog.
        $msg  = 'A new version of ' . $this->packageName . ' ';
        $msg .= " is available.\n\nWould you like to upgrade?";
        $this->mainwidget->addElement('static', 'message',
            '<div id="widget-icon-info"></div>', nl2br($msg));

        // The update details.
        $this->mainwidget->addElement('text', 'current_version',
            'Current Version:');
        $this->mainwidget->addElement('text', 'release_version',
            'Release Version:');
        $this->mainwidget->addElement('text', 'release_date', 'Release Date:');
        $this->mainwidget->addElement('text', 'release_state', 'Release State:');
        $this->mainwidget->addElement('static', 'release_notes', 'Release Notes:',
            '<div class="autoscroll">' .
            nl2br($this->info['releasenotes']) .
            '</div>');
        $this->mainwidget->addElement('text', 'release_by', 'Released By:');
        $current_version = ($this->instVersion === '0.0.0')
            ? '- None -' : $this->instVersion;

        $this->mainwidget->setDefaults(array(
            'current_version' => $current_version,
            'release_version' => $this->latestVersion,
            'release_date'    => $this->info['releasedate'],
            'release_state'   => $this->info['state'],
            'release_by'      => $this->info['doneby']
        ));

        $buttons = array();
        // Add the preferences button.
        $buttons[] = &HTML_QuickForm::createElement('submit',
                         'btnPrefs', 'Preferences');

        // Add the yes/no buttons.
        $buttons[] = &HTML_QuickForm::createElement('submit', 'mainBtnNo', 'No');
        $buttons[] = &HTML_QuickForm::createElement('submit', 'mainBtnYes', 'Yes');

        $this->mainwidget->addGroup($buttons, 'buttons', '', '&nbsp;', false);

        $this->mainwidget->freeze();
    }

    /**
     * Creates the dialog that will ask the user for his preferences.
     *
     * @param array $prefs User's preferences
     *
     * @access protected
     * @return void
     * @since  version 0.1.0 (2006-04-30)
     */
    function createPrefDialog($prefs)
    {
        // Create the dialog
        $this->prefwidget = new HTML_QuickForm('prefPPU');
        $this->prefwidget->removeAttribute('name');        // XHTML compliance

        // Create the preferences dialog title.
        $title = $this->packageName . ' Update Preferences';
        $this->prefwidget->addElement('header', '', $title);

        // It needs a check box for "Don't ask again"
        $this->prefwidget->addElement('checkbox', 'dontAsk',
            '', 'Don\'t ask me again');
        // Set the default.
        if (isset($prefs[PEAR_PACKAGEUPDATE_PREF_NOUPDATES])) {
            $this->prefwidget->setDefaults(array(
                'dontAsk' => $prefs[PEAR_PACKAGEUPDATE_PREF_NOUPDATES]));
        }

        // It needs a check box for the next release.
        $this->prefwidget->addElement('checkbox', 'nextRelease',
            '', 'Don\'t ask again until the next release.');
        // Set the default.
        if (isset($prefs[PEAR_PACKAGEUPDATE_PREF_NEXTRELEASE])) {
            $this->prefwidget->setDefaults(array(
                'nextRelease' => $prefs[PEAR_PACKAGEUPDATE_PREF_NEXTRELEASE]));
        }

        // It needs a radio group for the state.
        $allStates   = array();
        $allStates[] = &HTML_QuickForm::createElement('radio', null, null,
                          'All states', 'all');
        $allStates[] = &HTML_QuickForm::createElement('radio', null, null,
                          'devel', PEAR_PACKAGEUPDATE_STATE_DEVEL);
        $allStates[] = &HTML_QuickForm::createElement('radio', null, null,
                          'alpha', PEAR_PACKAGEUPDATE_STATE_ALPHA);
        $allStates[] = &HTML_QuickForm::createElement('radio', null, null,
                          'beta', PEAR_PACKAGEUPDATE_STATE_BETA);
        $allStates[] = &HTML_QuickForm::createElement('radio', null, null,
                          'stable', PEAR_PACKAGEUPDATE_STATE_STABLE);
        $this->prefwidget->addGroup($allStates, 'allStates',
            'Only ask when the state is at least:', '<br />');
        // Set the default.
        $stateDef = (isset($prefs[PEAR_PACKAGEUPDATE_PREF_STATE])) ?
            $prefs[PEAR_PACKAGEUPDATE_PREF_STATE] : 'all';
        $this->prefwidget->setDefaults(array('allStates' => $stateDef));


        // It needs a radio group for the type.
        $allTypes   = array();
        $allTypes[] = &HTML_QuickForm::createElement('radio', null, null,
                         'All Release Types', 'all');
        $allTypes[] = &HTML_QuickForm::createElement('radio', null, null,
                         'Bug fix', PEAR_PACKAGEUPDATE_TYPE_BUG);
        $allTypes[] = &HTML_QuickForm::createElement('radio', null, null,
                         'Minor', PEAR_PACKAGEUPDATE_TYPE_MINOR);
        $allTypes[] = &HTML_QuickForm::createElement('radio', null, null,
                         'Major', PEAR_PACKAGEUPDATE_TYPE_MAJOR);
        $this->prefwidget->addGroup($allTypes, 'allTypes',
            'Only ask when the type is at least:', '<br />');
        // Set the default.
        $typeDef = (isset($prefs[PEAR_PACKAGEUPDATE_PREF_TYPE])) ?
            $prefs[PEAR_PACKAGEUPDATE_PREF_TYPE] : 'all';
        $this->prefwidget->setDefaults(array('allTypes' => $typeDef));

        // Add the yes/no buttons.
        $buttons   = array();
        $buttons[] = &HTML_QuickForm::createElement('submit', 'prefBtnNo', 'No');
        $buttons[] = &HTML_QuickForm::createElement('submit', 'prefBtnYes', 'Yes');

        $this->prefwidget->addGroup($buttons, 'buttons', '', '&nbsp;', false);
    }

    /**
     * Creates the dialog that will show errors to the user.
     *
     * @param boolean $context (optional) Decides to show or not
     *                         the context (file, line) of error
     *
     * @access protected
     * @return void
     * @since  version 0.1.0 (2006-04-30)
     */
    function createErrorDialog($context = false)
    {
        // Don't do anything if the dialog already exists.
        if (isset($this->errwidget)) {
            return;
        }

        // Create the dialog
        $this->errwidget = new HTML_QuickForm('errorPPU');
        $this->errwidget->removeAttribute('name');        // XHTML compliance

        // Create a title string.
        $title = 'Error(s) occured while trying to Update for: ' .
                 $this->packageName;
        $this->errwidget->addElement('header', '', $title);

        // Create an image placeholder and the message for the dialog.
        $this->errwidget->addElement('static', 'icon',
            '<div id="widget-icon-error"></div>');
        $this->errwidget->addElement('static', 'message', 'Message:');

        if ($context) {
            // The error context details.
            $this->errwidget->addElement('text', 'context_file', 'File:');
            $this->errwidget->addElement('text', 'context_line', 'Line:');
            $this->errwidget->addElement('text', 'context_function', 'Function:');
            $this->errwidget->addElement('text', 'context_class', 'Class:');
        }

        $buttons = array();
        // Add the Ok button.
        $buttons[] = &HTML_QuickForm::createElement('submit', 'errorBtnOk', 'Ok');

        $this->errwidget->addGroup($buttons, 'buttons', '', '&nbsp;', false);

        $this->errwidget->freeze();
    }

    /**
     * Creates and runs a dialog for setting preferences.
     *
     * @access public
     * @return boolean true if the preferences were set and saved.
     * @since  version 0.1.0 (2006-04-30)
     */
    function prefDialog()
    {
        // The preferences dialog needs to have some inputs for the user.
        // Get the current preferences so that defaults can be set.
        $prefs = $this->getPackagePreferences();

        // Create the preference dialog widget.
        $this->createPrefDialog($prefs);

        $renderer = &$this->getHtmlRendererWithoutLabel($this->prefwidget);

        // Get Html code to display
        $html = $this->toHtml($renderer);

        // Run the dialog and return whether or not the user clicked "Yes".
        if ($this->prefwidget->validate()) {
            $safe = $this->prefwidget->exportValues();

            if (isset($safe['prefBtnYes'])) {
                // Get all of the preferences.
                $prefs = array();

                // Check for the don't ask preference.
                $prefs[PEAR_PACKAGEUPDATE_PREF_NOUPDATES] = isset($safe['dontAsk']);

                // Check for next version.
                $prefs[PEAR_PACKAGEUPDATE_PREF_NEXTRELEASE]
                    = isset($safe['nextRelease']);

                // Check for type.
                $prefs[PEAR_PACKAGEUPDATE_PREF_TYPE] = $safe['allTypes'];

                // Check for state.
                $prefs[PEAR_PACKAGEUPDATE_PREF_STATE] = $safe['allStates'];

                // Save the preferences.
                return $this->setPreferences($prefs);

            } elseif (isset($safe['prefBtnNo'])) {
                return false;
            }
        }
        echo $html;
        exit();
    }

    /**
     * Redirects or exits to force the user to restart the application.
     *
     * @access public
     * @return void
     * @since  version 0.1.0 (2006-04-30)
     */
    function forceRestart()
    {
        // Reload current page.
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    /**
     * Checks to see if an update is available,
     * and if package was already installed.
     *
     * Respects the user preferences when determining if an
     * update is available. Returns true if an update is available
     * and the user may want to update the package.
     *
     * @access public
     * @return boolean true if an update is available.
     * @since  version 0.4.0 (2007-07-01)
     */
    function checkUpdate()
    {
        $update_exists = parent::checkUpdate();
        if ($update_exists === true) {
            if ($this->instVersion == '0.0.0') {
                $this->pushError(PEAR_PACKAGEUPDATE_ERROR_NOTINSTALLED,
                    'warning', array('packagename' => $this->packageName));
            }
        }
        return $update_exists;
    }

    /**
     * Presents the user with the option to update.
     *
     * @access public
     * @return boolean true if the user would like to update the package.
     * @since  version 0.1.0 (2006-04-30)
     */
    function presentUpdate()
    {
        // Make sure the info has been grabbed.
        // This will just return if the info has already been grabbed.
        $this->getPackageInfo();

        // Create the main dialog widget.
        $this->createMainDialog();

        $renderer = &$this->getHtmlRendererWithLabel($this->mainwidget);

        // Get Html code to display
        $html = $this->toHtml($renderer);

        // Run the dialog and return whether or not the user clicked "Yes".
        if ($this->mainwidget->validate()) {
            $safe = $this->mainwidget->exportValues();

            if (isset($safe['mainBtnYes'])) {
                return true;
            } elseif (isset($safe['mainBtnNo'])) {
                return false;
            } else {
                $this->prefDialog();
            }
        }
        echo $html;
        exit();
    }

    /**
     * Presents an error in a dialog window.
     *
     * @param boolean $context (optional) true if you want to have
     *                         error context details
     *
     * @access public
     * @return boolean true if an error was displayed.
     * @since  version 0.1.0 (2006-04-30)
     */
    function errorDialog($context = false)
    {
        // Check to see if there are errors into stack.
        if ($this->hasErrors()) {
            $error = $this->popError();

            // Create the error dialog widget.
            $this->createErrorDialog($context);
            $this->errwidget->setConstants(array('message' => $error['message']));
            // Fill the context details
            if ($context) {
                $file = $line = $function = $class = '';

                if (isset($error['context']['file'])) {
                    $file = $error['context']['file'];
                }
                if (isset($error['context']['line'])) {
                    $line = $error['context']['line'];
                }
                if (isset($error['context']['function'])) {
                    $function = $error['context']['function'];
                }
                if (isset($error['context']['class'])) {
                    $class = $error['context']['class'];
                }

                $this->errwidget->setConstants(array(
                    'context_file' => $file,
                    'context_line' => $line,
                    'context_function' => $function,
                    'context_class' => $class
                    ));
            }

            $renderer = &$this->getHtmlRendererWithLabel($this->errwidget);

            // Get Html code to display
            $html = $this->toHtml($renderer);

            // Run the dialog.
            if ($this->errwidget->validate()) {
                return true;
            }
            echo $html;
            exit();
        }
        // Nothing to do.
        return false;
    }

    /**
     * Returns HTML renderer for a dialog with input labels and values
     *
     * @param object &$widget instance of HTML_QuickForm (main dialog)
     *
     * @access protected
     * @return object  instance of a QuickForm renderer
     * @since  version 0.1.0 (2006-04-30)
     */
    function &getHtmlRendererWithLabel(&$widget)
    {
        // Templates string
        $formTemplate = "\n<form{attributes}>"
            . "\n<table class=\"dialogbox\">"
            . "\n{content}"
            . "\n</table>"
            . "\n</form>";

        $headerTemplate = "\n<tr>"
            . "\n\t<td class=\"widget-header\" colspan=\"2\">"
            . "\n\t\t{header}"
            . "\n\t</td>"
            . "\n</tr>";

        $elementTemplate = "\n<tr>"
            . "\n\t<td class=\"widget-label\">"
            . "<!-- BEGIN label -->{label}<!-- END label --></td>"
            . "\n\t<td class=\"widget-input\">{element}</td>"
            . "\n</tr>";

        $elementNavig = "\n<tr class=\"widget-buttons\">"
            . "\n\t<td>&nbsp;</td>"
            . "\n\t<td>{element}</td>"
            . "\n</tr>";

        $renderer =& $widget->defaultRenderer();

        $renderer->setFormTemplate($formTemplate);
        $renderer->setHeaderTemplate($headerTemplate);
        $renderer->setElementTemplate($elementTemplate);
        $renderer->setElementTemplate($elementNavig, 'buttons');

        $widget->accept($renderer);

        return $renderer;
    }

    /**
     * Returns HTML renderer for a dialog with only input values (no labels)
     *
     * @param object &$widget instance of HTML_QuickForm (main dialog)
     *
     * @access protected
     * @return object  instance of a QuickForm renderer
     * @since  version 0.1.0 (2006-04-30)
     */
    function &getHtmlRendererWithoutLabel(&$widget)
    {
        // Templates string
        $formTemplate = "\n<form{attributes}>"
            . "\n<table class=\"dialogbox\">"
            . "\n{content}"
            . "\n</table>"
            . "\n</form>";

        $headerTemplate = "\n<tr>"
            . "\n\t<td class=\"widget-header\">"
            . "\n\t\t{header}"
            . "\n\t</td>"
            . "\n</tr>";

        $elementTemplate = "\n<tr>"
            . "\n\t<td class=\"widget-input\">{element}</td>"
            . "\n</tr>";

        $elementNavig = "\n<tr class=\"widget-buttons\">"
            . "\n\t<td>{element}</td>"
            . "\n</tr>";

        $elementRadio = "\n<tr>"
            . "\n\t<td class=\"widget-input\">"
            . "<!-- BEGIN label -->{label}<!-- END label --><br />{element}</td>"
            . "\n</tr>";

        $renderer =& $widget->defaultRenderer();

        $renderer->setFormTemplate($formTemplate);
        $renderer->setHeaderTemplate($headerTemplate);
        $renderer->setElementTemplate($elementTemplate);
        $renderer->setElementTemplate($elementNavig, 'buttons');
        $renderer->setElementTemplate($elementRadio, 'allStates');
        $renderer->setElementTemplate($elementRadio, 'allTypes');

        $widget->accept($renderer);

        return $renderer;
    }

    /**
     * Returns the custom style sheet to use for layout
     *
     * @param bool $content (optional) Either return css filename or string contents
     *
     * @return string
     * @access public
     * @since  version 0.3.0 (2006-07-17)
     */
    function getStyleSheet($content = true)
    {
        if ($content) {
            $styles = file_get_contents($this->css);
        } else {
            $styles = $this->css;
        }
        return $styles;
    }

    /**
     * Set the custom style sheet to use your own styles
     *
     * @param string $css (optional) File to read user-defined styles from
     *
     * @return bool    True if custom styles, false if default styles applied
     * @access public
     * @since  version 0.3.0 (2006-07-17)
     */
    function setStyleSheet($css = null)
    {
        // default stylesheet is into package data directory
        if (!isset($css)) {
            $this->css = '@data_dir@' . DIRECTORY_SEPARATOR
                 . '@package_name@' . DIRECTORY_SEPARATOR
                 . 'ppu.css';
        }

        $res = isset($css) && file_exists($css);
        if ($res) {
            $this->css = $css;
        }
        return $res;
    }

    /**
     * Returns HTML code of a dialog box.
     *
     * @param object $renderer instance of a QuickForm renderer
     *
     * @access public
     * @return string
     * @since  version 0.1.0 (2006-04-30)
     */
    function toHtml($renderer)
    {
        if (!isset($this->css)) {
            // when no user-styles defined, used the default values
            $this->setStyleSheet();
        }
        $styles = $this->getStyleSheet();

        $body = $renderer->toHtml();

        $html = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3c.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>PEAR_PackageUpdate Web Frontend</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<style type="text/css">
<!--
$styles
 -->
</style>
</head>
<body>
$body
</body>
</html>
HTML;
        return $html;
    }
}
?>