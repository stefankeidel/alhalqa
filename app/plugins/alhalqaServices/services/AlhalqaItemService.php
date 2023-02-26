<?php
/** ---------------------------------------------------------------------
 * app/plugins/aboutDrawingServices/services/AlhalqaItemService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */
  
require_once(__CA_LIB_DIR__."/Service/ItemService.php");
require_once(__CA_LIB_DIR__."/Datamodel.php");

class AlhalqaItemService extends ItemService {
	# -------------------------------------------------------
	public function __construct($po_request,$ps_table="") {
		parent::__construct($po_request,$ps_table);
	}
	# -------------------------------------------------------
	protected function getAllItemInfo() {
		$va_info = parent::getAllItemInfo();

		if(($this->getTableName() == 'ca_objects') && is_array($va_info) && sizeof($va_info)>0) {
			$t_object = new ca_objects($va_info['object_id']['value']);
			if(!$t_object->getPrimaryKey()) { return $va_info; }

			// include number of 'likes' (comments)
			$va_info['likes'] = (int) $t_object->getNumComments(null);

			// include copyright holder
			$vs_copyright_holder = $t_object->get('ca_entities.preferred_labels', array('restrictToRelationshipTypes' => 'copyright'));
			if($vs_copyright_holder) { $va_info['copyright_holder'] = $vs_copyright_holder; }

            // include sets for objects
            $t_set = new ca_sets();
			$va_sets = $t_set->getSetsForItem($t_object->tableNum(), $t_object->getPrimaryKey(), array('checkAccess' => 1));
			$va_info['sets'] = $va_sets;

            // include multifiles for representations
            if (is_array($va_info['representations'])) {

                foreach ($va_info['representations'] as $vn_rep_id => &$va_rep) {
                    $t_rep = new ca_object_representations($vn_rep_id);
                    $multifiles = $t_rep->getFileList($vn_rep_id, $pn_start=null, $pn_num_files=null, ['original']);
                }

                foreach ($multifiles as $multifile) {
                    $urls_media_original[] = array(
                        'width'=> $multifile['original_width'],
                        'height'=> $multifile['original_height'],
                        'url'=> $multifile['original_url']
                    );
                }

                if (sizeof($urls_media_original) > 0) {
                    $va_rep['multifiles'] = $urls_media_original;
                }
            }


			// include urls for reference img
			$va_objects = $t_object->getRelatedItems('ca_objects', array('restrictToRelationshipTypes' => 'reference'));
			if(!is_array($va_objects) || sizeof($va_objects) != 1) { return $va_info;; }

			$va_object = array_shift($va_objects);
			$t_rel_object = new ca_objects($va_object['object_id']);
			$va_rep = $t_rel_object->getPrimaryRepresentation(array('preview170', 'medium', 'alhalqa1000', 'alhalqa2000'));
			if(!is_array($va_rep) || !is_array($va_rep['urls'])) { return $va_info;; }

			$va_info['reference_image_urls'] = $va_rep['urls'];
		}

		if(($this->getTableName() == 'ca_object_representations') && is_array($va_info) && sizeof($va_info)>0) {
            $vn_rep_id = $va_info['representation_id']['value'];
            $t_rep = new ca_object_representations($vn_rep_id);
            $multifiles = $t_rep->getFileList($vn_rep_id, $pn_start=null, $pn_num_files=null, ['original']);

			foreach ($multifiles as $multifile) {
                $urls_media_original[] = $multifile['original_url'];
            }

            $va_info['multifiles'] = $urls_media_original;
        }

		return $va_info;
	}
	# -------------------------------------------------------
	protected function getSpecificItemInfo() {
		return parent::getSpecificItemInfo();
	}
	# -------------------------------------------------------
}
