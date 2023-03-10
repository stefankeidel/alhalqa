<?php
/* ----------------------------------------------------------------------
 * plugins/vimeo/controllers/AuthController.php :
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

 	require_once(__CA_LIB_DIR__.'/Configuration.php');
 	include_once(__CA_LIB_DIR__."/Vimeo/vimeo.php");

    class AuthController extends ActionController {
        # -------------------------------------------------------
        protected $opo_config;      // plugin configuration file
        # -------------------------------------------------------
        #
        # -------------------------------------------------------
        public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
            parent::__construct($po_request, $po_response, $pa_view_paths);
            $this->opo_config = Configuration::load(__CA_APP_DIR__.'/plugins/vimeo/conf/vimeo.conf');
        }
        # -------------------------------------------------------
        public function Index() {
            $vo_vimeo = new \Vimeo\Vimeo($this->opo_config->get('consumer_key'), $this->opo_config->get('consumer_secret'));
            $vb_had_stored_token = false;

            // get stored request or access token if we have one
            if(file_exists(__CA_APP_DIR__.'/tmp/vimeo.token')){
                $vs_token = file_get_contents(__CA_APP_DIR__.'/tmp/vimeo.token');
                $vb_had_stored_token = true;
            }

            $redirect_uri = 'https://www.alhalqa-virtual.com/admin/index.php/vimeo/Auth/verify';
            $scopes = ['private', 'edit', 'create', 'upload', 'delete', 'video_files'];
            $state = 'abc123';

            $this->view->setVar('authorize_link', $vs_authorize_link = $vo_vimeo->buildAuthorizationEndpoint($redirect_uri, $scopes, $state));
            $this->view->setVar('token', $va_token = []);
            $this->view->setVar('had_stored_token', $vb_had_stored_token);

            $this->render('auth_html.php');
        }
        # -------------------------------------------------------
        public function verify() {
            // incoming state and code from above
            $incoming_state = $this->request->getParameter('state', pString);
            $incoming_code = $this->request->getParameter('code', pString);
            $redirect_uri = 'https://www.alhalqa-virtual.com/admin/index.php/vimeo/Auth/verify';

            $vo_vimeo = new \Vimeo\Vimeo($this->opo_config->get('consumer_key'), $this->opo_config->get('consumer_secret'));

            if($incoming_state != 'abc123') { exit; }

            $tokens = $vo_vimeo->accessToken($incoming_code, $redirect_uri);

            if ($tokens['status'] == 200) {
                $access_token = $tokens['body']['access_token'];
                file_put_contents(__CA_APP_DIR__.'/tmp/vimeo.token', $access_token);
                $this->view->setVar('success', true);
            }

            $this->view->setVar('success', false);

            $this->render('verify.php');
        }
        # -------------------------------------------------------
    }
 ?>
