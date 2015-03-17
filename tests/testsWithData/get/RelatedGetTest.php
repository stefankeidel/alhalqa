<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/RelatedGetTest.php
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
 * @subpackage tests
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_BASE_DIR__.'/tests/testsWithData/BaseTestWithData.php');

/**
 * Class RelatedGetTest
 * Note: Requires testing profile!
 */
class RelatedGetTest extends BaseTestWithData {
	# -------------------------------------------------------
	/**
	 * @var BundlableLabelableBaseModelWithAttributes
	 */
	private $opt_object = null;
	# -------------------------------------------------------
	public function setUp() {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		/**
		 * @see http://docs.collectiveaccess.org/wiki/Web_Service_API#Creating_new_records
		 * @see https://gist.githubusercontent.com/skeidel/3871797/raw/item_request.json
		 */
		$vn_object_id = $this->addTestRecord('ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'image',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name" => "My test image",
				),
			),
		));
		$this->assertGreaterThan(0, $vn_object_id);

		$vn_entity_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'ind',
				'idno' => 'hjs',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Homer",
					"middlename" => "J.",
					"surname" => "Simpson",
				),
			),
			'nonpreferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Max",
					"middlename" => "",
					"surname" => "Power",
					"type_id" => "alt",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'creator',
						'effective_date' => '2015',
						'source_info' => 'Me'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_entity_id);

		$vn_entity_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'ind',
				'idno' => 'hjs',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "Bart",
					"middlename" => "",
					"surname" => "Simpson",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'publisher',
						'effective_date' => '2014-2015',
						'source_info' => 'Homer'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_entity_id);

		$vn_entity_id = $this->addTestRecord('ca_entities', array(
			'intrinsic_fields' => array(
				'type_id' => 'org',
				'idno' => 'hjs',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"forename" => "",
					"middlename" => "",
					"surname" => "ACME Inc.",
				),
			),
			'related' => array(
				'ca_objects' => array(
					array(
						'object_id' => $vn_object_id,
						'type_id' => 'source',
						'effective_date' => '2013',
						'source_info' => 'Bart'
					)
				),
			),
		));

		$this->assertGreaterThan(0, $vn_entity_id);

		$this->opt_object = new ca_objects($vn_object_id);
	}
	# -------------------------------------------------------
	public function testGets() {
		$vm_ret = $this->opt_object->get('ca_entities', array('delimiter' => '; '));
		$this->assertEquals('Homer J. Simpson; Bart Simpson; ACME Inc.', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities.preferred_labels', array('delimiter' => '; '));
		$this->assertEquals('Homer J. Simpson; Bart Simpson; ACME Inc.', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities.nonpreferred_labels');
		$this->assertEquals('Max Power', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities', array('returnAsArray' => true));
		$vm_ret2 = $this->opt_object->getRelatedItems('ca_entities');
		$this->assertSame($vm_ret, $vm_ret2);

		$vm_ret = $this->opt_object->get('ca_entities', array('restrictToRelationshipTypes' => array('creator')));
		$this->assertEquals('Homer J. Simpson', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities', array('restrictToRelationshipTypes' => array('publisher')));
		$this->assertEquals('Bart Simpson', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities', array('delimiter' => '; ', 'restrictToTypes' => array('ind')));
		$this->assertEquals('Homer J. Simpson; Bart Simpson', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities', array('delimiter' => '; ', 'restrictToTypes' => array('org')));
		$this->assertEquals('ACME Inc.', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities', array('delimiter' => '; ', 'restrictToTypes' => array('ind', 'org')));
		$this->assertEquals('Homer J. Simpson; Bart Simpson; ACME Inc.', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities', array('delimiter' => '; ', 'excludeRelationshipTypes' => array('creator', 'publisher')));
		$this->assertEquals('ACME Inc.', $vm_ret);

		$vm_ret = $this->opt_object->get('ca_entities', array('delimiter' => '; ', 'excludeTypes' => array('ind')));
		$this->assertEquals('ACME Inc.', $vm_ret);
	}
	# -------------------------------------------------------
}
