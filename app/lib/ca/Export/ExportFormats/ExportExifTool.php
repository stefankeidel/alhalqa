<?php
/** ---------------------------------------------------------------------
 * ExportExifTool.php : defines ExifTool export format
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
 * @subpackage Export
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__.'/ca/Export/BaseExportFormat.php');
require_once(__CA_APP_DIR__.'/helpers/mediaPluginHelpers.php');

class ExportExifTool extends BaseExportFormat {
	# ------------------------------------------------------
	/**
	 * @var DOMDocument
	 */
	private $opo_dom = null;
	# ------------------------------------------------------
	public function __construct() {
		$this->ops_name = 'ExifTool';
		$this->ops_element_description = _t('Values are ExifTool XML element names. See http://www.sno.phy.queensu.ca/~phil/exiftool/metafiles.html#xml');

		$this->opo_dom = new DOMDocument('1.0','utf-8');
		$this->opo_dom->formatOutput = true;
		$this->opo_dom->preserveWhiteSpace = false;

		parent::__construct();
	}
	# ------------------------------------------------------
	/**
	 * Get DOM document
	 * @return DOMDocument
	 */
	private function getDom() {
		return $this->opo_dom;
	}
	# ------------------------------------------------------
	public function getFileExtension($pa_settings) {
		return 'xml';
	}
	# ------------------------------------------------------
	public function getContentType($pa_settings) {
		return 'text/xml';
	}
	# ------------------------------------------------------
	public function processExport($pa_data,$pa_options=array()) {
		if(!caGetOption('singleRecord', $pa_options, true)) {
			throw new Exception("The ExifTool exporter does not support exporting multiple records");
		}

		$o_rdf = $this->getDom()->createElementNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'rdf:RDF');
		$this->getDom()->appendChild($o_rdf);

		$o_desc = $this->getDom()->createElement('rdf:Description');
		$o_rdf->appendChild($o_desc);

		// add full range of exiftool namespaces
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:et', 'http://ns.exiftool.ca/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ExifTool', 'http://ns.exiftool.ca/ExifTool/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:System', 'http://ns.exiftool.ca/File/System/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:File', 'http://ns.exiftool.ca/File/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:JFIF', 'http://ns.exiftool.ca/JFIF/JFIF/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:IFD0', 'http://ns.exiftool.ca/EXIF/IFD0/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ExifIFD', 'http://ns.exiftool.ca/EXIF/ExifIFD/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Apple', 'http://ns.exiftool.ca/MakerNotes/Apple/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:XMP-x', 'http://ns.exiftool.ca/XMP/XMP-x/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:XMP-xmp', 'http://ns.exiftool.ca/XMP/XMP-xmp/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:XMP-photoshop', 'http://ns.exiftool.ca/XMP/XMP-photoshop/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Photoshop', 'http://ns.exiftool.ca/Photoshop/Photoshop/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ICC-header', 'http://ns.exiftool.ca/ICC_Profile/ICC-header/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ICC_Profile', 'http://ns.exiftool.ca/ICC_Profile/ICC_Profile/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ICC-view', 'http://ns.exiftool.ca/ICC_Profile/ICC-view/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ICC-meas', 'http://ns.exiftool.ca/ICC_Profile/ICC-meas/1.0/');
		$o_desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Composite', 'http://ns.exiftool.ca/Composite/1.0/');

		$o_desc->setAttribute('et:toolkit', 'CollectiveAccess ExifTool Exporter');


		$this->log("ExifTool export formatter: Now processing export tree ...");

		foreach($pa_data as $va_element) {
			if(!isset($va_element['element']) || !$va_element['element'] || !isset($va_element['text']) || !$va_element['text']) {
				$this->log("ExifTool export formatter: Skipped row because either element or text was empty. Element in tree was:");
				$this->log(print_r($va_element, true));
				continue;
			}

			$o_element = $this->getDom()->createElement($va_element['element'], $va_element['text']);
			$o_desc->appendChild($o_element);
		}

		$this->log("ExifTool export formatter: Done processing export tree ...");

		return $this->getDom()->saveXML();
	}

	# ------------------------------------------------------
	public function getMappingErrors($t_mapping) {
		if(!caExifToolInstalled()) {
			$va_errors[] = _t('ExifTool must be installed and available!');
		}

		$va_errors = array();
		$va_top = $t_mapping->getTopLevelItems();

		foreach($va_top as $va_item) {
			//@todo check element prefixes? e.g. /^ExifIFD/ etc.
		}

		return $va_errors;
	}
	# ------------------------------------------------------
}

BaseExportFormat::$s_format_settings['ExifTool'] = array();
