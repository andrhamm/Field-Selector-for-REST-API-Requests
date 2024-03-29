## Serialize
```php
$fields = array(
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

$serialized = FieldSelector::serialize($fields); // =>  "(general,email:(id,email),work:(title,company:(name)),education:(school:(name)))"
```


## Deserialize
```php
$deserialized = array();
FieldSelector::deserialize($serialized, $deserialized);
```

## Check if a field is selected
```php
FieldSelector::init(array('fields'=>$fields));
FieldSelector::is_selected('fields/work/title');   // => false
FieldSelector::is_selected('fields/general');      // => true
FieldSelector::is_selected('fields/email');        // => true
FieldSelector::is_selected('email/email',$fields); // => true
```
