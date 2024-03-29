<?php

class FieldSelector
{
	private static $state = array();
	
	private static $init = FALSE;
	
	/**
	 * Set the public static vars with the deserialized values of fields & sources
	 *
	 * @param $input - associative array of serialized OR unserialized keys
	 * @author Andrew Hammond
	 */
	public static function init ( $input )
	{
		self::add_keys( $input );
		
		self::$init = TRUE;
	}
	
	/**
	 * Add multiple keys to the current state
	 *
	 * @param array $keyArr - array of FieldSelector-style arrays to add to subject
	 * @param array $subject - the FieldSelector-style array (if other than $state) to add the key to
	 * @author Andrew Hammond
	 */
	public static function add_keys ( $keyArr, &$subject = NULL )
	{
		foreach ( $keyArr as $key => $val )
		{
			self::add_key( $key, $val, $subject );
		}
	}
	
	/**
	 * Add one key to the current state
	 *
	 * @param string $key - The name of the key
	 * @param array $val - A FieldSelector-style array
	 * @param array $subject - the FieldSelector-style array (if other than $state) to add the key to
	 * @author Andrew Hammond
	 */
	public static function add_key ( $key, $val, &$subject = NULL )
	{
		$state = ( isset($subject) && is_array($subject) ) ? $subject : self::$state;
		
		$state[$key] = ( is_string($val) ) ? self::deserialize( $val ) : ( ( is_array($val) ) ? $val : array() );
	}
	
	/**
	 * Remove a key from the subject
	 *
	 * @param string $key - the key to unset
	 * @param array $subject - the FieldSelector-style array (if other than $state) to remove the key from
	 * @author Andrew Hammond
	 */
	public static function remove_key ( $key, &$subject = NULL )
	{
		$state = ( isset($subject) && self::is_assoc($subject) ) ? $subject : self::$state;
		
		unset( $state['$key'] );
	}
	
	/**
	 * Get a subset of keys
	 *
	 * @param string $keyXPath - the URL-like identifier for the key ( fields/general/email ) will check 3 levels deep in self::$state
	 * @param array $subject - the FieldSelector-style array (if other than $state)
	 * @return reference to 
	 * @author Andrew Hammond
	 */
	public function get_key ( $keyXPath, &$subject = NULL )
	{
		$path = explode( '/', rtrim($keyXPath,'/') );
		
		$rabbitHole = ( isset($subject) && is_array($subject) ) ? $subject : self::$state;
		
		while ( !empty( $path ) )
		{
			$key = array_shift( $path );
			
			if ( !array_key_exists( $key, $rabbitHole ) )
			{
				// don't go any deeper, dead end
				return FALSE;
			}
			else
			{
				// is this the last element?
				if ( empty($path) )
				{
					// found it
					return $rabbitHole[$key];
				}
				else
				{
					// *jedi mind trick* these are not the keys you're looking for
					$rabbitHole = $rabbitHole[$key];
				}
			}
		}
	}
	
	/**
	 * Check if a key is selected.
	 * 
	 * @param string $keyXPath - the URL-like identifier for the key ( fields/general/email ) will check 3 levels deep in self::$state
	 * @param array $subject - the FieldSelector-style array (if other than $state)
	 * @author Andrew Hammond
	 */
	public static function is_selected ( $keyXPath, &$subject = NULL )
	{
		$path = explode( '/', rtrim($keyXPath,'/') );
		
		return (boolean) self::get_key( $keyXPath, $subject );
	}
	
	/**
	 * Seralize a FieldSelector-style multi-dimensional array into a FieldSelector-style string
	 *
	 * @param string $arr - array to serialize (reference)
	 * @return string - the FieldSelector-style serialized array
	 * @author Andrew Hammond
	 */
	public static function serialize( &$arr = array() )
	{
		$serialized = '(';
		
		$last = count( $arr );
		$i = 0;
		foreach ( $arr as $key => $val )
		{
			$added_key = TRUE;
			
			if ( is_array( $val ) )
			{
				$serialized .= $key . ':' . self::serialize( $val );
			}
			else if ( $val === 1 ) // enforce standard of using 1 (helps with tests)
			{
				$serialized .= $key;
			}
			else
			{
				$added_key = FALSE;
			}
			
			if ( $added_key && $i+1 !== $last )
				$serialized .= ',';
			
			$i++;
		}
		
		$serialized .= ')';
		
		return $serialized;
	}
	
	/**
	 * Deserialize a FieldSelector-style string
	 *
	 * @param string $field_str - the string to deserialize (reference). will be empty after deserialization!
	 * @param string $field_arr - the output array (reference)
	 * @param string $x - leave this alone please <3
	 * @return array - returns a multidimensional (or not) array contianing the keys selected. Same syntax as serialize() accepts
	 * @author Andrew Hammond
	 */
	public static function deserialize( &$field_str, &$field_arr, &$x = 0 )
	{
		if ( !strlen( $field_str ) )
			return;
		
		// enforce strict syntax
		if ( $x === 0 )
		{
			if ( substr($field_str, 0, 1) !== '(' || substr($field_str, -1) !== ')' )
				throw new Exception('Malformed field selector string. [0001] ');
			if ( !preg_match('#^[a-z0-9(),:_-]*$#i', $field_str) )
				throw new Exception('Request contains disallowed characters.');
			
			// remove consecutive commas, might cause trouble
			$field_str = preg_replace('/,{2,}/', ',', $field_str);
		}
		
		// remove the outer parens
		if ( (substr( $field_str, 0, 1 ) === '(') && (substr( $field_str, -1 ) === ')') )
			$field_str = substr( substr( $field_str, 0, -1 ), 1 );
		
		// enforce closing of all open parens
		$num_lparen = substr_count( $field_str, '(' );
		$num_rparen = substr_count( $field_str, ')' );
		if ( $num_lparen !== $num_rparen )
			throw new Exception('Malformed field selector string. [0002]');
		
		// split on top level commas
		$top_level = array();
		
		while ( strlen($field_str) )
		{
			$parens_opened	= 0;
			$parens_closed	= 0;
			
			$field_str_arr = str_split( $field_str ); // make an array of all the chars
			
			for ( $i=0; !empty($field_str_arr); $i++ )
			{
				$char = array_shift($field_str_arr);
				if ( $char === '(' )
				{
					$parens_opened += 1;
				}
				else if ( $char === ')' )
				{
					$parens_closed += 1;
				}
				else if ( $char === ',' && $parens_opened === $parens_closed )
				{
					list ( $this_field, $field_str ) = self::splitpos( $field_str, $i );
					$top_level[] = $this_field;
					break;
				}
			}
			
			// if it gets to here, we've reached the end, so the remaining string is the last root field
			if (empty($field_str_arr))
			{	
				$top_level[] = $field_str;
				$field_str = '';
			}
		}
		
		// loop through the top level selectors and do recursive parsing if needed (for mult-dimensional selector)
		foreach ( $top_level as $field_name )
		{
			$first_colon = strpos( $field_name, ':');
			
			if ( $first_colon !== FALSE )
			{
				// this is a field with a sub select
				list ( $field_name, $selector ) = self::splitpos( $field_name, $first_colon );
				
				$field_arr[$field_name] = array();
				
				self::deserialize( $selector, $field_arr[$field_name], ++$x );
			}
			else
			{
				// this is a single field
				$field_arr[$field_name] = 1;
			}
		}
	}
	
	/**
	 * Check if an array is associative
	 *
	 * @param string $array - the array to check
	 * @return bool
	 * @see http://php.net/manual/en/function.is-array.php
	 */
	private static function is_assoc($array)
	{
	    return (is_array($array) && (count($array)==0 || 0 !== count(array_diff_key($array, array_keys(array_keys($array))) )));
	}
	
	/**
	 * Split a string at the given position, Optionally exclude the delimeter from either output strings.
	 *
	 * @param string $str - string to split
	 * @param string $pos - the offset of the delimeter (length from start of string)
	 * @param string $remove_delim - exclude the delimeter from output strings
	 * @return array - [0] = first half, [1] = second half
	 * @author Andrew Hammond
	 */
	private static function splitpos( $str, $pos, $remove_delim = TRUE )
	{
		$str_arr[] = ( $half = substr( $str, 0, $pos ) ) ? $half : '';
		if ( $remove_delim ) $pos += 1;
		$str_arr[] = ( $half = substr( $str, $pos ) ) ? $half : '';
		
		return $str_arr;
	}
}
