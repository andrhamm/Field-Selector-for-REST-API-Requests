<?php

require_once ( '../FieldSelector.php' );
require_once('simpletest/autorun.php');

class FieldSelectorTest extends UnitTestCase
{
	function testSerialize()
	{
		$fields_given = array(
		  'general'	=> 1,
			'email'		=> array(
				'id' 		=> 1,
				'email' 	=> 1
			),
			'work'		=> array(
				'title' 	=> 0,
				'company' 	=> array(
					'name' => 1
				)
			),
			'education'	=> array(
				'school' => array(
					'name' => 1
				)
			)
		);
		
		$serialized_expected = '(general,email:(id,email),work:(company:(name)),education:(school:(name)))';
		
		$serialized = FieldSelector::serialize( $fields_given );
		$serializedCopy = $serialized;
		
		$this->assertEqual( $serialized_expected, $serialized );
		
		$deserialized = array();
		FieldSelector::deserialize( $serialized, $deserialized );
		
		// Deserialized array might not be the same as the given array (because of explicitly unwanted keys where val is 0)
		// But if we reserialize it, it should be the same as before
		$reserialized = FieldSelector::serialize( $deserialized );
		$this->assertEqual( $reserialized, $serializedCopy );
	}
}