 <?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/MediaReplication/WLPlugMediaReplicationVimeo.php : replicates media to Vimeo
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage MediaReplication
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

  /**
    *
    */

include_once(__CA_LIB_DIR__."/core/Parsers/getid3/getid3.php");
include_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugMediaReplication.php");
include_once(__CA_LIB_DIR__."/core/Plugins/MediaReplication/BaseMediaReplicationPlugin.php");
include_once(__CA_LIB_DIR__."/core/Vimeo/Vimeo.php");
require_once(__CA_LIB_DIR__.'/core/Logging/Eventlog.php');

class WLPlugMediaReplicationVimeo Extends BaseMediaReplicationPlugin {
    # ------------------------------------------------
    /**
     * Keys given out by Vimeo for our app https://developer.vimeo.com/apps/30027
     */
    private $ops_client_id = 'a104c3290a2469cdcef92a4c9ce2cd33e6d488c6';
    private $ops_client_secret = 'bcQARZuSuBcJ8C8+AKBs+M8jzVoX+2HSGM/ziC4InKzKrKQ//nU+6Ew8+ZgVaqvjMqpB2Kud2BcfXM0FZMIDSWPUuVOEzzYS2fXQyfhf2DVT9UPi3XsyqxaBHyA6L4nw';

    /**
     * Target info from config file
     */
    private $opa_target_info;

    /**
     * Vimeo client
     */
    private $opo_client = null;

    /**
     * Error registry
     */
    private $opa_errors = array();
    # ------------------------------------------------
    /**
     *
     */
    public function __construct($pa_target_info=null) {
        parent::__construct();
        $this->info['NAME'] = 'Vimeo';

        $this->description = _t('Replicates media to Vimeo using the Advanced API');

        if ($pa_target_info) {
            $this->setTargetInfo($pa_target_info);
        }
    }
    # ------------------------------------------------
    /**
     *
     */
    public function setTargetInfo($pa_target_info) {
        $this->opa_target_info = $pa_target_info;
        return $this->getClient(array('reset'));
    }
    # ------------------------------------------------
    /**
     * @return string Unique request token. The token can be used on subsequent calls to fetch information about the replication request
     */
    public function initiateReplication($ps_filepath, $pa_data, $pa_options=null) {
        $o_client = $this->getClient();
        $pa_target_options = $this->opa_target_info['options'];

        try {
            if(!$o_client) { throw new VimeoAPIException(_t("Initial connection to Vimeo failed. Did you authorize CollectiveAccess to use your Vimeo account? Enable the 'vimeo' application plugin and navigate to Manage > Vimeo integration to do so.")); }

            $va_options = [
                'name' => $pa_data['title'] ?: 'Test',
                'description' => $pa_data['description'] ?: '',
                'license' => caGetOption('license', $pa_target_options, 0),
            ];

            // upload video to vimeo, set properties afterwards
            // returns something like /videos/365365506, which we may want to stash
            if($vs_video_uri = $o_client->upload($ps_filepath, $va_options)) {
                $vs_video_id = explode('/', $vs_video_uri)[2];

                $o_client->request($vs_video_uri, array(
                    'privacy' => array(
                        'view' => caGetOption('privacy', $pa_target_options, 'nobody'),
                        'download' => (bool) caGetOption('downloadPrivacy', $pa_target_options, 0),
                    ),
                ), 'PATCH');

                if (isset($pa_data['tags'])) {
                    foreach(explode(',', $pa_data['tags']) as $tag) {
                        $tag_to_add = urlencode($tag);
                        $o_client->request("{$vs_video_uri}/tags/{$tag_to_add}", [], 'PUT');
                    }
                }

                if($vs_preset = caGetOption('embed_preset', $pa_target_options, false)) {
                    $o_client->request("{$vs_video_uri}/presets/{$vs_preset}", [], 'PUT');
                }

                // @todo this kinda doesn't work right now
                if($vs_channel = caGetOption('channel', $pa_target_options, false)) {
                    $o_client->request("/channels/{$vs_channel}/videos/{$vs_video_id}", [], 'PUT');
                }
            } else {
                // upload() and all other phpVimeo methods throw their
                // own exceptions if something goes wrong, except in this case
                throw new VimeoAPIException(_t("File for replication doesn't exist"));
            }
        } catch (VimeoAPIException $e){
            // Let's put the error in the event log so you have some chance of knowing what's going on
            $o_log = new Eventlog();
            $o_log->log(array(
                'SOURCE' => 'Vimeo replication plugin',
                'MESSAGE' => _t('Upload to Vimeo failed. Code: %1, Message: %2', $e->getCode(), $e->getMessage()),
                'CODE' => 'ERR')
            );

            return false;
        }

        // this, I think, is what we store alongside the media
        return $vs_video_uri;
    }
    # ------------------------------------------------
    /**
     *
     */
    public function getReplicationStatus($ps_request_token, $pa_options=null) {
        $o_client = $this->getClient();

        // todo: deal with old ones
        $video_data = $o_client->request($ps_request_token . '?fields=status');

        if(isset($video_data['body']['status'])) {
            switch($video_data['body']['status']) {
                case 'available':
                    return __CA_MEDIA_REPLICATION_STATUS_COMPLETE__;
                case 'transcoding':
                case 'transcode_starting':
                    return __CA_MEDIA_REPLICATION_STATUS_PROCESSING__;
                default:
                    return __CA_MEDIA_REPLICATION_STATUS_ERROR__;
            }
        }

        return __CA_MEDIA_REPLICATION_STATUS_UNKNOWN__;
    }
    # ------------------------------------------------
    /**
     *
     */
    public function getReplicationErrors($ps_request_token) {
        return array();
    }
    # ------------------------------------------------
    /**
     *
     */
    public function getReplicationInfo($ps_request_token, $pa_options=null) {
        return array();
        // $o_client = $this->getClient();

        // $vs_video_id = preg_replace("!^".$this->info['NAME']."://!", "", $ps_request_token); // remove plugin identifier to obtain raw video ID
        // $this->opa_errors[$vs_video_id] = array();

        // //$vo_info = $o_client->call('vimeo.videos.getInfo', array('video_id' => $vs_video_id));

        // return array(
        //     'id' => $vo_info->video[0]->id,
        //     'title' => $vo_info->video[0]->title,
        //     'description' => $vo_info->video[0]->description,
        //     'viewCount' => $vo_info->video[0]->number_of_plays,
        //     'pageUrl' => $vo_info->video[0]->urls->url[0]->_content,
        //     'playUrl' => $vo_info->video[0]->getFlashPlayerUrl()
        // );
    }
    # ------------------------------------------------
    /**
     *
     */
    public function removeReplication($ps_request_token, $pa_options=null) {
        $o_client = $this->getClient();
        $o_client->request($ps_request_token, [], 'DELETE');
    }
    # ------------------------------------------------
    /**
     *
     */
    private function getClient($pa_options=null) {
        if ($vb_reset = (bool)caGetOption('reset', $pa_options, false)) {
            $this->opo_client = null;
        }
        if ($this->opo_client) { return $this->opo_client; }

        if(file_exists(__CA_APP_DIR__.'/tmp/vimeo.token')){
            $vs_token = file_get_contents(__CA_APP_DIR__.'/tmp/vimeo.token');
            $this->opo_client = new \Vimeo\Vimeo($this->ops_client_id, $this->ops_client_secret, $vs_token);

            return $this->opo_client;
        }

        return false;
    }
    # ------------------------------------------------
    /**
     *
     */
    public function getUrl($ps_key, $pa_options=null) {
        $va_tmp = explode("/", $ps_key);
        if((sizeof($va_tmp) == 2) && (strtolower($va_tmp[0]) == 'videos')) {
            return "https://www.vimeo.com/".$va_tmp[1];
        }
        return null;
    }
    # ------------------------------------------------
}
?>
