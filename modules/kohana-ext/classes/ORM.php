<?php

class ORM extends Kohana_ORM implements JsonSerializable {

	const DATA_TYPE_INT = 'int';

	const DATA_TYPE_BOOLEAN = 'bool';

	const DATA_TYPE_DOUBLE = 'double';

	const DATA_TYPE_FLOAT = 'float';

	const DATA_TYPE_STRING = 'string';
	/**
	 * @var array
	 */
	protected static $_object_names_map = array();

	protected static $default_namespace = 'Common\Model';

	/**
	 * Creates and returns a new model.
	 * Model name must be passed with its' original casing, e.g.
	 *
	 *    $model = ORM::factory('User_Token');
	 *
	 * @chainable
	 * @param   string  $model  Model name
	 * @param   mixed   $id     Parameter for find()
	 * @return  ORM
	 */
	public static function factory($class, $id = NULL)
	{
		// Set class name
		if(isset(self::$_object_names_map[strtolower($class)])){
			$class = self::$_object_names_map[strtolower($class)];
		}
		else if (class_exists($class))
		{
			$class = $class;
		}
		else if (strpos($class, '\\') === FALSE)
		{
			$class = self::$default_namespace . '\\' . str_replace('_', '\\', $class);
		}

		if (!class_exists($class))
		{
			throw new Kohana_Exception(strtr('Unknown model class :class', array(
				':class' => $class
			)));
		}


		return new $class($id);
	}

	public function __construct($id = NULL)
	{
		parent::__construct($id);
		$this->init();
	}

	public function init()
	{
	}

	public function object_name($name = NULL)
	{
		if(!is_null($name)){
			$class = $name;
			if(($pos = strrpos($name, '\\')) !== FALSE){
				$name = substr($name, $pos + 1);
			}
			$this->_object_name = strtolower($name);
			self::$_object_names_map[$this->_object_name] = $class;
			return $this;
		} else {
			return $this->_object_name;
		}
	}

	// ============================================
	// Composite pk support
	/**
	 * Reloads the current object from the database.
	 *
	 * @chainable
	 * @return ORM
	 */
	public function reload()
	{
		$primary_key = $this->pk();

		// Replace the object and reset the object status
		$this->_object = $this->_changed = $this->_related = $this->_original_values = array();

		// Only reload the object if we have one to reload
		if ($this->_loaded){
			return $this->clear()
				->where_primary_key($primary_key)
				->find();
		}
		else
		{
			return $this->clear();
		}
	}

		/**
	 * Allows serialization of only the object data and state, to prevent
	 * "stale" objects being unserialized, which also requires less memory.
	 *
	 * @return string
	 */
	public function serialize()
	{
		$data = array();

		// Store only information about the object
		foreach (array('_object', '_changed', '_loaded', '_saved', '_sorting', '_original_values') as $var)
		{
			$data[$var] = $this->{$var};
		}

		$primary_key = $this->primary_key();
		if (is_array($primary_key))
		{
			foreach ($primary_key as $var)
			{
				$data[$var] = $this->{$var};
			}
		}
		else
		{
			$data[$primary_key] = $this->{$primary_key};
		}

		return serialize($data);
	}

	// we dont support composite pk in relations.. (get() method)

	/**
	 * Set values from an array with support for one-one relationships.  This method should be used
	 * for loading in post data, etc.
	 *
	 * @param  array $values   Array of column => val
	 * @param  array $expected Array of keys to take from $values
	 * @return ORM
	 */
	public function values(array $values, array $expected = NULL)
	{
		// Default to expecting everything except the primary key
		if ($expected === NULL)
		{
			$expected = array_keys($this->_table_columns);

			$primary_key = $this->primary_key();
			if (is_array($primary_key))
			{
				foreach ($primary_key as $key)
				{
					unset($values[$key]);
				}
			}
			else
			{
				// Don't set the primary key by default
				unset($values[$this->_primary_key]);
			}
		}

		foreach ($expected as $key => $column)
		{
			if (is_string($key))
			{
				// isset() fails when the value is NULL (we want it to pass)
				if ( ! array_key_exists($key, $values))
					continue;

				// Try to set values to a related model
				$this->{$key}->values($values[$key], $column);
			}
			else
			{
				// isset() fails when the value is NULL (we want it to pass)
				if ( ! array_key_exists($column, $values))
					continue;

				// Update the column, respects __set()
				$this->$column = $values[$column];
			}
		}

		return $this;
	}

	/**
	 * Loads an array of values into into the current object.
	 *
	 * @chainable
	 *
	 * @param array $values
	 * Values to load
	 * @return ORM
	 */
	protected function _load_values(array $values)
	{
		$primary_key = $this->primary_key();

		if (is_array($primary_key))
		{
			$pk_values = $this->_get_primary_key_value($values);
//var_dump($values, $pk_values, $this->primary_key());

			if (count($pk_values))
			{
				// Flag as loaded and valid
				$this->_loaded = $this->_valid = TRUE;

				// Store primary key
				$this->_primary_key_value = $pk_values;

			} else {
				// Not loaded or valid
				$this->_loaded = $this->_valid = FALSE;
			}
		}
		else if (array_key_exists($this->_primary_key, $values))
		{
			if ($values[$this->_primary_key] !== NULL)
			{
				// Flag as loaded and valid
				$this->_loaded = $this->_valid = TRUE;

				// Store primary key
				$this->_primary_key_value = $values[$this->_primary_key];
			}
			else
			{
				// Not loaded or valid
				$this->_loaded = $this->_valid = FALSE;
			}
		}


		// Related objects
		$related = array();

		foreach ($values as $column => $value)
		{
			if (strpos($column, ':') === FALSE)
			{
				// Load the value to this model
				$this->_object[$column] = $value;
			}
			else
			{
				// Column belongs to a related model
				list ($prefix, $column) = explode(':', $column, 2);

				$related[$prefix][$column] = $value;
			}
		}

		if ( ! empty($related))
		{
			foreach ($related as $object => $values)
			{
				// Load the related objects with the values in the result
				$this->_related($object)->_load_values($values);
			}
		}

		if ($this->_loaded)
		{
			// Store the object in its original state
			$this->_original_values = $this->_object;
		}

		return $this;
	}

	public function create(Validation $validation = NULL)
	{
		if ($this->_loaded)
			throw new Kohana_Exception('Cannot create :model model because it is already loaded.', array(':model' => $this->_object_name));

		// Require model validation before saving
		if ( ! $this->_valid OR $validation)
		{
			$this->check($validation);
		}

		$data = array();
		foreach ($this->_changed as $column)
		{
			// Generate list of column => values
			$data[$column] = $this->_object[$column];
		}

		if (is_array($this->_created_column))
		{
			// Fill the created column
			$column = $this->_created_column['column'];
			$format = $this->_created_column['format'];
			$data[$column] = $this->_object[$column] = ($format === TRUE) ? time() : date($format);
		}


		$result = DB::insert($this->_table_name)
			->columns(array_keys($data))
			->values(array_values($data))
			->execute($this->_db);

		$primary_key = $this->primary_key();
		if (is_array($primary_key))
		{
			$pk_values = $this->_get_primary_key_value($data);
			$this->_primary_key_value = $pk_values;
		}
		else
		{
			if (!array_key_exists($primary_key, $data))
			{
				// Load the insert id as the primary key if it was left out
				$this->_object[$primary_key] = $this->_primary_key_value = $result[0];
			}
			else
			{
				$this->_primary_key_value = $this->_object[$primary_key];
			}
		}

		// Object is now loaded and saved
		$this->_loaded = $this->_saved = TRUE;

		// All changes have been saved
		$this->_changed = array();
		$this->_original_values = $this->_object;

		return $this;
	}

	public function update(Validation $validation = NULL)
	{
		if ( ! $this->_loaded)
			throw new Kohana_Exception('Cannot update :model model because it is not loaded.', array(':model' => $this->_object_name));

		// Run validation if the model isn't valid or we have additional validation rules.
		if ( ! $this->_valid OR $validation)
		{
			$this->check($validation);
		}

		if (empty($this->_changed))
		{
			// Nothing to update
			return $this;
		}

		$data = array();
		foreach ($this->_changed as $column)
		{
			// Compile changed data
			$data[$column] = $this->_object[$column];
		}

		if (is_array($this->_updated_column))
		{
			// Fill the updated column
			$column = $this->_updated_column['column'];
			$format = $this->_updated_column['format'];

			$data[$column] = $this->_object[$column] = ($format === TRUE) ? time() : date($format);
		}

		// Use primary key value
		$id = $this->pk();

		$primary_key = $this->primary_key();

		if (is_array($primary_key))
		{
			$builder = DB::update($this->_table_name)->set($data);

			$c = 0;
			foreach($primary_key as $key){
				$builder->where($key, '=', $id[$c++]);
			}

			$builder->execute($this->_db);
		}
		else
		{
			// Update a single record
			DB::update($this->_table_name)->set($data)->where($primary_key, '=', $id)->execute($this->_db);
		}
		if (is_array($primary_key))
		{
			$pk_values = $this->_get_primary_key_value($data);

			if (count($pk_values))
			{
				$this->_primary_key_value = $pk_values;
			}
		}
		else
		{
			if (isset($data[$this->_primary_key]))
			{
				// Primary key was changed, reflect it
				$this->_primary_key_value = $data[$this->_primary_key];
			}
		}

		// Object has been saved
		$this->_saved = TRUE;

		// All changes have been saved
		$this->_changed = array();
		$this->_original_values = $this->_object;

		return $this;
	}

	public function delete()
	{
		if ( ! $this->_loaded)
			throw new Kohana_Exception('Cannot delete :model model because it is not loaded.', array(':model' => $this->_object_name));

		// Use primary key value
		$id = $this->pk();
		$primary_key = $this->primary_key();

		if (is_array($primary_key))
		{
			$builder = DB::delete($this->_table_name);

			$c = 0;

			foreach($primary_key as $key){
				$builder->where($key, '=', $id[ $c++ ]);
			}

			$builder->execute($this->_db);
		}
		else
		{
			// Delete the object
			DB::delete($this->_table_name)->where($this->_primary_key, '=', $id)->execute($this->_db);
		}
		return $this->clear();
	}

	public function count_all()
	{
		$selects = array();

		foreach ($this->_db_pending as $key => $method)
		{
			if ($method['name'] == 'select')
			{
				// Ignore any selected columns for now
				$selects[$key] = $method;
				unset($this->_db_pending[$key]);
			}
		}

		if ( ! empty($this->_load_with))
		{
			foreach ($this->_load_with as $alias)
			{
				// Bind relationship
				$this->with($alias);
			}
		}

		$this->_build(Database::SELECT);

		$records = $this->_db_builder->from(array($this->_table_name, $this->_object_name))
		// simple fix for composite pks, hehe))
			->select(array(DB::expr('COUNT(1)'), 'records_found'))
			->execute($this->_db)
			->get('records_found');

		// Add back in selected columns
		$this->_db_pending += $selects;

		$this->reset();

		// Return the total number of records in a table
		return (int) $records;
	}

	// ============================================

	/**
	 * Prepares the model database connection, determines the table name,
	 * and loads column information.
	 *
	 * @return void
	 */
	protected function _initialize()
	{
		// Set the object name if none predefined
		if (empty($this->_object_name))
		{
			$this->object_name(get_class($this));
		}

		// Check if this model has already been initialized
		if ( ! $init = Arr::get(ORM::$_init_cache, $this->_object_name, FALSE))
		{
			$init = array(
				'_belongs_to' => array(),
				'_has_one'    => array(),
				'_has_many'   => array(),
			);

			// Set the object plural name if none predefined
			if ( ! isset($this->_object_plural))
			{
				$init['_object_plural'] = Inflector::plural($this->_object_name);
			}

			if ( ! $this->_errors_filename)
			{
				$init['_errors_filename'] = $this->_object_name;
			}

			if ( ! is_object($this->_db))
			{
				// Get database instance
				$init['_db'] = Database::instance($this->_db_group);
			}

			if (empty($this->_table_name))
			{
				// Table name is the same as the object name
				$init['_table_name'] = $this->_object_name;

				if ($this->_table_names_plural === TRUE)
				{
					// Make the table name plural
					$init['_table_name'] = Arr::get($init, '_object_plural', $this->_object_plural);
				}
			}

			$defaults = array();

			foreach ($this->_belongs_to as $alias => $details)
			{
				if ( ! isset($details['model']))
				{
					$defaults['model'] = str_replace(' ', '_', ucwords(str_replace('_', ' ', $alias)));
				}

				$defaults['foreign_key'] = $alias.$this->_foreign_key_suffix;

				$init['_belongs_to'][$alias] = array_merge($defaults, $details);
			}

			foreach ($this->_has_one as $alias => $details)
			{
				if ( ! isset($details['model']))
				{
					$defaults['model'] = str_replace(' ', '_', ucwords(str_replace('_', ' ', $alias)));
				}

				$defaults['foreign_key'] = $this->_object_name.$this->_foreign_key_suffix;

				$init['_has_one'][$alias] = array_merge($defaults, $details);
			}

			foreach ($this->_has_many as $alias => $details)
			{
				if ( ! isset($details['model']))
				{
					$defaults['model'] = str_replace(' ', '_', ucwords(str_replace('_', ' ', Inflector::singular($alias))));
				}

				$defaults['foreign_key'] = $this->_object_name.$this->_foreign_key_suffix;
				$defaults['through'] = NULL;

				if ( ! isset($details['far_key']))
				{
					$defaults['far_key'] = Inflector::singular($alias).$this->_foreign_key_suffix;
				}

				$init['_has_many'][$alias] = array_merge($defaults, $details);
			}

			ORM::$_init_cache[$this->_object_name] = $init;
		}

		// Assign initialized properties to the current object
		foreach ($init as $property => $value)
		{
			$this->{$property} = $value;
		}

		// Load column information
		$this->reload_columns();

		// Clear initial model state
		$this->clear();
	}

	public function label($name)
	{
		$labels = $this->labels();
		return isset($labels[$name])? $labels[$name] : $name;
	}

	/**
	 * Validation that field exists in db
	 *
	 * @param unknown $id
	 * @return boolean
	 */
	public function exists($field, $value)
	{
		$model = ORM::factory($this->object_name())
			->where($field, '=', $value)
			->find();

		if ($this->loaded())
		{
			return ( ! ($model->loaded() AND $model->pk() != $this->pk()));
		}

		return ($model->loaded());
	}

	/**
	 * Handles setting of columns
	 * Override this method to add custom set behavior
	 *
	 * @param  string $column Column name
	 * @param  mixed  $value  Column value
	 * @throws Kohana_Exception
	 * @return ORM
	 */
	public function set($column, $value)
	{
		if (!isset($this->_object_name))
		{
			// Object not yet constructed, so we're loading data from a database call cast
			$this->_cast_data[$column] = $value;

			return $this;
		}

		if (in_array($column, $this->_serialize_columns))
		{
			$value = $this->_serialize_value($value);
		}

		if (array_key_exists($column, $this->_object))
		{
			// Filter the data
			$value = $this->run_filter($column, $value);

			// See if the data really changed
			if ($value !== $this->_object[$column])
			{
				$this->_object[$column] = $value;

				// Data has changed
				$this->_changed[$column] = $column;

				// Object is no longer saved or valid
				$this->_saved = $this->_valid = FALSE;
			}
		}
		elseif (isset($this->_belongs_to[$column]))
		{
			// Update related object itself
			$this->_related[$column] = $value;

			// Update the foreign key of this model
			$this->_object[$this->_belongs_to[$column]['foreign_key']] = ($value instanceof ORM) ? $value->pk() : NULL;

			$this->_changed[$column] = $this->_belongs_to[$column]['foreign_key'];
		}
		elseif(property_exists(get_class($this), $column))
		{
			$this->$column = $value;
		}
		else
		{
			throw new Kohana_Exception('The :property: property does not exist in the :class: class', array(
				':property:' => $column,
				':class:' => get_class($this)
			));
		}

		return $this;
	}

	/**
	 * @see Kohana_ORM::save()
	 */
	public function save(Validation $validation = NULL)
	{
		$this->_before_save();
		$retval = parent::save($validation);
		$this->_after_save();
		return $retval;
	}

	/**
	 * Binds another one-to-one object to this model.  One-to-one objects
	 * can be nested using 'object1:object2' syntax
	 *
	 * @param  string $target_path Target model to bind to
	 * @return ORM
	 */
	public function with($target_path, $type = 'LEFT')
	{
		if (isset($this->_with_applied[$target_path]))
		{
			// Don't join anything already joined
			return $this;
		}

		// Split object parts
		$aliases = explode(':', $target_path);
		$target = $this;
		foreach ($aliases as $alias)
		{
			// Go down the line of objects to find the given target
			$parent = $target;
			$target = $parent->_related($alias);

			if ( ! $target)
			{
				// Can't find related object
				return $this;
			}
		}

		// Target alias is at the end
		$target_alias = $alias;

		// Pop-off top alias to get the parent path (user:photo:tag becomes user:photo - the parent table prefix)
		array_pop($aliases);
		$parent_path = implode(':', $aliases);

		if (empty($parent_path))
		{
			// Use this table name itself for the parent path
			$parent_path = $this->_object_name;
		}
		else
		{
			if ( ! isset($this->_with_applied[$parent_path]))
			{
				// If the parent path hasn't been joined yet, do it first (otherwise LEFT JOINs fail)
				$this->with($parent_path);
			}
		}

		// Add to with_applied to prevent duplicate joins
		$this->_with_applied[$target_path] = TRUE;

		// Use the keys of the empty object to determine the columns
		foreach (array_keys($target->_object) as $column)
		{
			$name = $target_path.'.'.$column;
			$alias = $target_path.':'.$column;

			// Add the prefix so that load_result can determine the relationship
			$this->select(array($name, $alias));
		}

		if (isset($parent->_belongs_to[$target_alias]))
		{
			// Parent belongs_to target, use target's primary key and parent's foreign key
			$join_col1 = $target_path.'.'.$target->_primary_key;
			$join_col2 = $parent_path.'.'.$parent->_belongs_to[$target_alias]['foreign_key'];
		}
		else
		{
			// Parent has_one target, use parent's primary key as target's foreign key
			$join_col1 = $parent_path.'.'.$parent->_primary_key;
			$join_col2 = $target_path.'.'.$parent->_has_one[$target_alias]['foreign_key'];
		}

		// Join the related object into the result
		$this->join(array($target->_table_name, $target_path), $type)->on($join_col1, '=', $join_col2);

		return $this;
	}


	/**
	 * Loads a database result, either as a new record for this model, or as
	 * an iterator for multiple rows.
	 *
	 * @chainable
	 * @param  bool $multiple Return an iterator or load a single row
	 * @return ORM|Database_Result
	 */
	protected function _load_result($multiple = FALSE)
	{
		$this->_db_builder->from(array($this->_table_name, $this->_object_name));

		if ($multiple === FALSE)
		{
			// Only fetch 1 record
			$this->_db_builder->limit(1);
		}

		// Select all columns by default
		$this->_db_builder->select_array($this->_build_select());

		if ( ! isset($this->_db_applied['order_by']) AND ! empty($this->_sorting))
		{
			foreach ($this->_sorting as $column => $direction)
			{
				if (strpos($column, '.') === FALSE)
				{
					// Sorting column for use in JOINs
					$column = $this->_object_name.'.'.$column;
				}

				$this->_db_builder->order_by($column, $direction);
			}
		}

		$this->_before_find();

		if ($multiple === TRUE)
		{
			// Return database iterator casting to this object type
			$result = $this->_db_builder->as_object(get_class($this))->execute($this->_db);
			if(get_class($this) == 'Common\Model\User'){

			}
			$this->reset();

			foreach($result as $object) {
				$object->_after_find_base();
				$object->_after_find();
			}

			return $result;
		}
		else
		{
			// Load the result as an associative array
			$result = $this->_db_builder->as_assoc()->execute($this->_db);

			$this->reset();

			if ($result->count() === 1)
			{
				// Load object values
				$this->_load_values($result->current());
				$this->_after_find_base();
				$this->_after_find();
			}
			else
			{
				// Clear the object, nothing was found
				$this->clear();
			}

			return $this;
		}
	}

	public function skip_validation($skip = NULL)
	{
		if (!is_null($skip))
		{
			$this->_valid = $skip;
			return $this;
		}
		else
		{
			return $this->_valid == TRUE;
		}
	}

	public function extract_column_values(Database_Result $models, $column)
	{
		$values = array();
		foreach($models as $model){
			$values []= $model->get($column);
		}

		return $values;
	}

	/**
	 * Support for composite pk's
	 * @param scalar|array $value
	 */
	public function where_primary_key($value)
	{
		$primary_key = $this->primary_key();

		if (is_array($primary_key))
		{
			$c = 0;
			foreach ($primary_key as $key)
			{
				$this->where($this->_object_name . '.' . $key, '=', $value[$c++]);
			}
		}
		else
		{
			$this->where($this->_object_name . '.' . $primary_key, '=', $value);
		}
	}

	public function jsonSerialize()
	{
		return $this->as_array();
	}

	protected function _get_primary_key_value($data)
	{
		$primary_key = $this->primary_key();

		if (is_array($primary_key))
		{
			$values = array();
			foreach ($primary_key as $key)
			{
				if (array_key_exists($key, $data))
				{
					$values[] = $data[$key];
				}
				else
				{
					$values = array();
					break;
				}
			}
			return $values;
		}
		else
		{
			if (array_key_exists($primary_key, $data))
			{
				return $data[$primary_key];
			}
			else
			{
				return NULL;
			}
		}
	}


	protected function _process_datatypes()
	{
		foreach ($this->_table_columns as $name => $conf)
		{
			if (empty($conf['data_type']))
			{
				continue;
			}
			else
			{
				switch (strtolower($conf['data_type']))
				{
					default:
						continue;
					break;
					case (self::DATA_TYPE_INT):
						$this->$name = (int) $this->$name;
					break;
					case (self::DATA_TYPE_FLOAT):
					case (self::DATA_TYPE_DOUBLE):
						$this->$name = (float) $this->$name;
					break;
					case (self::DATA_TYPE_BOOLEAN):
						$this->$name = (bool) (int)$this->$name;
					break;
				}
			}
		}
	}
	/**
	 * Initializes validation rules, and labels
	 *
	 * @return void
	 */
	protected function _validation()
	{
		// Build the validation object with its rules
		$this->_validation = Validation::factory($this->_object)
			->bind(':model', $this)
			->bind(':original_values', $this->_original_values)
			->bind(':changed', $this->_changed);

		foreach ($this->rules() as $field => $rules)
		{
			$new_rules = array();
			foreach($rules as $rule){
				if(is_scalar($rule[0]) && $rule[0] == 'empty_rules'){
					$new_rules = array();
					continue;
				} else {
					$new_rules []= $rule;
				}
			}
			$this->_validation->rules($field, $new_rules);
		}

		// Use column names by default for labels
		$columns = array_keys($this->_table_columns);

		// Merge user-defined labels
		$labels = array_merge(array_combine($columns, $columns), $this->labels());

		foreach ($labels as $field => $label)
		{
			$this->_validation->label($field, $label);
		}
	}


	// Hooks
	protected function _before_save(){}
	protected function _after_save(){}

	protected function _before_find(){}
	protected function _after_find(){}
	protected function _after_find_base()
	{
		$this->_process_datatypes();
	}

	protected function _get_orm_errors_flatten(\ORM_Validation_Exception $e)
	{
		return '<ul><li>' . implode('</li><li>', $e->errors('validation', TRUE)) . '</li></ul>';
	}

}