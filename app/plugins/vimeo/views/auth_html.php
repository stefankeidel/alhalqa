<?php
/* ----------------------------------------------------------------------
 * app/plugins/vimeo/views/auth_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */
    $va_token = $this->getVar('token');

    if($this->getVar('had_stored_token')) {
?>
                <h2><?php print _t('Vimeo integration is set up and ready to go!'); ?></h2>
<?php
    } else {
?>
        <div><?php print _t('You need to go to the Vimeo page and authorize our app to use your account.'); ?></div>
        <div><?php print _t('Please click here: %1','<a href="'.$this->getVar('authorize_link').'" target="_blank">Vimeo</a>'); ?></div>
<?php
    }

?>
