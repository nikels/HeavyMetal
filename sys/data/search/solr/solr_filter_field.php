<?

uses('system.data.field');
uses('system.data.filter');
uses('system.data.filter_field');
uses('system.data.search.solr.solr_filter');

/**
 * A filtered field
 */
class SOLRFilterField extends FilterField
{
	public $q_param = false;
	public $field_ext = null;
	
	public function field_name()
	{
		if ($this->filter->facet->{$this->field->name}) 
			return $this->field->name . $this->filter->facet->{$this->field->name}->field_ext;
		else
			return $this->field->name;	
	}
	
	/**
	 * Equals
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function equals($value, $include_null=false, $op='=', $caseconv=null)
	{
		if (is_array($value))
			return $this->is_in($value);

		$val = $this->escape_value($value);
		
		if (empty($val))
			$val = '[* TO *]';
		
		if ($this->and)
			$this->value = '+' . $this->field_name() . ":" . $val . ' ';
		else
			$this->value = $this->field_name() . ":" . $val . ' ';
			
		$this->value = $this->check_add_facet_exclusion();
		
		return $this->filter;
	}

	/**
	 * Not Equal
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function not_equal($value, $include_null=false)
	{
		if (!is_array($value))
			$value = array($value);
		
		foreach($value as $val)
		{
			$val = $this->escape_value($val);
			$this->value .= '-' . $this->field_name() . ":" . $val . ' ';
		}

		$this->value = $this->check_add_facet_exclusion();
		
		return $this->filter;
	}


	/**
	 * Equals any value in the supplied list.
	 *
	 * @param array $values The values to use for comparison
	 */
	function equals_any($values)
	{
		return $this->equals($values);
	}

	/**
	 * Determines if a value is in a set
	 *
	 * @param mixed $values The values to use for comparison (array or another filter)
	 */
	function is_in($values, $include_null=false, $caseconv=null)
	{
		if (is_array($values) && count($values) > 0)
		{
			$this->value = '+' . $this->field_name() . ':(';
				
			foreach($values as $val)
			{
				$val = $this->escape_value($val);
				$this->value .=  $val . ' ';
			}
	
			$this->value .= ') ';
			$this->value = $this->check_add_facet_exclusion();
		}
				
		return $this->filter;
	}

	/**
	 * Determines if a value is NOT in a set
	 *
	 * @param mixed $values The values to use for comparison
	 */
	function is_not_in($values, $include_null=false)
	{
		if (is_array($values) && count($values) > 0)
		{
			$this->value = '-' . $this->field_name() . ':(';
				
			foreach($values as $val)
			{
				$val = $this->escape_value($val);
				$this->value .=  $val . ' ';
			}
	
			$this->value .= ') ';
	
			$this->value = $this->check_add_facet_exclusion();
		}
				
		return $this->filter;
	}

	/**
	 * Starts with
	 *
	 * @param mixed $value The value to use for comparison
     * @param mixed $op Used to swap ILIKE for LIKE or ~ if indexes require it.
	 */
	function starts_with($value, $op='ILIKE', $caseconv=null)
	{
		$this->value = $this->field_name() . ':' . $value . '* ';

		if ($this->and)
			$this->value = '+'.$this->value;
		
		$this->value = $this->check_add_facet_exclusion();
			
		return $this->filter;
	}

	/**
	 * Contains
	 *
	 * @param mixed $value The value to use for comparison
     * @param mixed $op Used to swap ILIKE for LIKE or ~ if indexes require it.
	 */
	function contains($value, $op='ILIKE', $caseconv=null)
	{
		return $this->equals($value);
		
		// 'Use equals to take advantage of SOLR full-text features.  ' .
		// 'The only wildcard searches allowed are A*B (use equals) or A* ' .
		//	'(use equals or starts_with).'
	}

	/**
	 * Contains
	 *
	 * @param mixed $value The value to use for comparison
     * @param mixed $op Used to swap ILIKE for LIKE or ~ if indexes require it.
	 */
	function contains_any($values, $op='ILIKE', $caseconv=null)
	{
		return $this->equals($value);
	}
	
	/**
	 * Contains
	 *
	 * @param mixed $value The value to use for comparison
     * @param mixed $op Used to swap ILIKE for LIKE or ~ if indexes require it.
	 */
	function contains_all($values, $op='ILIKE', $caseconv=null)
	{	
		$qry = array();
		
		if (!is_array($values))
			$values = array($values);

					
		foreach($values as $val)
		{
			$val = $this->escape_value($val);
			$qry[] = $val;
		}

		$this->value = $this->field_name . ':(' . implode($qry, ' AND ') . ')';
		
		$this->value = $this->check_add_facet_exclusion();
		
		return $this->filter;
	}
	
	
	/**
	 * Ends with
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function ends_with($value, $op='ILIKE', $caseconv=null)
	{
		throw new Exception('SOLR Wildcard queries cannot start with *');
	}

	/**
	 * Greater than or equal to
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function greater_equal($value, $include_null=false)
	{
		$value = $this->escape_value($value);

	    $this->value = $this->field_name() . ':[' . $value . ' TO *]';

  		if ($this->and)
			$this->value = '+'.$this->value;
			
		$this->value = $this->check_add_facet_exclusion();
	    
		return $this->filter;
	}

	/**
	 * Less than or equal to
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function less_equal($value, $include_null=false)
	{
		$value = $this->escape_value($value);
				
		$this->value = $this->field_name() . ':[* TO ' . $value . ']';

		if ($this->and)
			$this->value = '+'.$this->value;

		$this->value = $this->check_add_facet_exclusion();
		
		return $this->filter;
	}

	/**
	 * Greater than
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function greater($value, $include_null=false)
	{
		throw new Exception('SOLR does not support greater, use greater_equal');
	}

	/**
	 * Within a specified range
	 *
	 * @param mixed $min The minumum value
	 * @param mixed $max The maximum value
	 */
	function within($min,$max)
	{
		if ($this->field->type == Field::TIMESTAMP)
		{
			$min = $this->escape_value($min);
			$max = $this->escape_value($max);
		}
		
		$this->value = $this->field_name() . ':[' . $min . ' TO ' . $max . ']'; 
		
		if ($this->and)
			$this->value = '+'.$this->value;

		$this->value = $this->check_add_facet_exclusion();
			
		return $this->filter;
	}


	/**
	 * Less 
	 *
	 * @param mixed $value The value to use for comparison
	 */
	function less($value, $include_null=false)
	{
		throw new Exception('SOLR does not support less, use less_equal');
	}

	/**
	 * Not null
	 */
	function not_null()
	{
		$this->value = $this->field_name() . ':[* TO *]';

		if ($this->and)
			$this->value = '+'.$this->value;
		
		$this->value = $this->check_add_facet_exclusion();
			
		return $this->filter;
	}

	/**
	 * Is Null
	 */
	function is_null()
	{
		$this->value = '-' . $this->field_name() . ':[* TO *]';

		$this->value = $this->check_add_facet_exclusion();

		return $this->filter;
	}
	
	private function escape_value($value=null)
	{
		$value = trim($value);

		switch($this->field->type)
		{	
			case Field::STRING:
			case Field::TEXT:
				return (empty($value)) ? "[* TO *]" : '"'.$value.'"';
			case Field::BOOLEAN:
				return ($value) ? "true" : "false";
			case Field::TIMESTAMP:
				return reformat_to_iso($value);
			default:
				if (is_numeric($value))
					return $value;
				else
					return '"'.$value.'"';
				
		}
	}


	private function check_add_facet_exclusion()
	{
		// Doh!  hasn't been set yet
		if ($this->filter->facet->{$this->field->name})
		{
			$facet = $this->filter->facet->{$this->field->name};
			
			if ($facet->freeze==true)
				return '{!tag='.$this->field->name.'}'.$this->value;
		}

		return $this->value;
	}
}

	
