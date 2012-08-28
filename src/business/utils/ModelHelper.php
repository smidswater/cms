<?php
namespace Blocks;

/**
 *
 */
class ModelHelper
{
	/**
	 * Returns the rules array used by CActiveRecord
	 * @param array $attributes
	 * @param array $indexes
	 * @return array
	 */
	public static function createRules($attributes, $indexes = array())
	{
		$rules = array();

		$uniques = array();
		$required = array();
		$emails = array();
		$urls = array();
		$strictLengths = array();
		$minLengths = array();
		$maxLengths = array();

		$numberTypes = array(PropertyType::TinyInt, PropertyType::SmallInt, PropertyType::MediumInt, PropertyType::Int, PropertyType::BigInt, PropertyType::Float, PropertyType::Decimal);
		$integerTypes = array(PropertyType::TinyInt, PropertyType::SmallInt, PropertyType::MediumInt, PropertyType::Int, PropertyType::BigInt);

		foreach ($attributes as $name => $settings)
		{
			$type = is_string($settings) ? $settings : (isset($settings['type']) ? $settings['type'] : (isset($settings[0]) ? $settings[0] : null));

			// Catch handles, email addresses, languages and URLs before running normalizePropertyConfig, since 'type' will get changed to VARCHAR
			if ($type == PropertyType::Handle)
			{
				$reservedWords = isset($settings['reservedWords']) ? ArrayHelper::stringToArray($settings['reservedWords']) : array();
				$rules[] = array($name, 'Blocks\HandleValidator', 'reservedWords' => $reservedWords);
			}

			if ($type == PropertyType::Language)
				$rules[] = array($name, 'Blocks\LanguageValidator');

			if ($type == PropertyType::Email)
				$emails[] = $name;

			if ($type == PropertyType::Url)
				$urls[] = $name;

			// Remember if it's a license key
			$isLicenseKey = ($type == PropertyType::LicenseKey);

			$settings = DatabaseHelper::normalizePropertyConfig($settings);

			// Uniques
			if (isset($settings['unique']) && $settings['unique'] === true)
				$uniques[] = $name;

			// Only enforce 'required' validation if there's no default value
			if (isset($settings['required']) && $settings['required'] === true && !isset($settings['default']))
				$required[] = $name;

			// Numbers
			if (in_array($settings['type'], $numberTypes))
			{
				$rule = array($name, 'numerical');

				if (isset($settings['min']) && is_numeric($settings['min']))
					$rule['min'] = $settings['min'];

				if (isset($settings['max']) && is_numeric($settings['max']))
					$rule['max'] = $settings['max'];

				if (in_array($settings['type'], $integerTypes))
					$rule['integerOnly'] = true;

				$rules[] = $rule;
			}

			// Enum attribute values
			if ($settings['type'] == PropertyType::Enum)
			{
				$values = ArrayHelper::stringToArray($settings['values']);
				$rules[] = array($name, 'in', 'range' => $values);
			}

			// License keys' length=36 is redundant in the context of validation, since matchPattern already enforces 36 chars
			if ($isLicenseKey)
				unset($settings['length']);

			// Strict, min, and max lengths
			if (isset($settings['length']) && is_numeric($settings['length']))
				$strictLengths[(string)$settings['length']][] = $name;
			else
			{
				// Only worry about min- and max-lengths if a strict length isn't set
				if (isset($settings['minLength']) && is_numeric($settings['minLength']))
					$minLengths[(string)$settings['minLength']][] = $name;

				if (isset($settings['maxLength']) && is_numeric($settings['maxLength']))
					$maxLengths[(string)$settings['maxLength']][] = $name;
			}

			// Regex pattern matching
			if (!empty($settings['matchPattern']))
				$rules[] = array($name, 'match', 'pattern' => $settings['matchPattern']);
		}

		// Catch any composite unique indexes
		foreach ($indexes as $index)
		{
			if (isset($index['unique']) && $index['unique'] === true)
			{
				if (count($index['columns']) > 1)
				{
					$columns = ArrayHelper::stringToArray($index['columns']);
					$initialColumn = array_shift($columns);
					$rules[] = array($initialColumn, 'Blocks\CompositeUniqueValidator', 'with' => implode(',', $columns));
				}
			}
		}

		if ($uniques)
			$rules[] = array(implode(',', $uniques), 'unique');

		if ($required)
			$rules[] = array(implode(',', $required), 'required');

		if ($emails)
			$rules[] = array(implode(',', $emails), 'email');

		if ($urls)
			$rules[] = array(implode(',', $urls), 'url', 'defaultScheme' => 'http');

		if ($strictLengths)
		{
			foreach ($strictLengths as $strictLength => $attributeNames)
			{
				$rules[] = array(implode(',', $attributeNames), 'length', 'is' => (int)$strictLength);
			}
		}

		if ($minLengths)
		{
			foreach ($minLengths as $minLength => $attributeNames)
			{
				$rules[] = array(implode(',', $attributeNames), 'length', 'min' => (int)$minLength);
			}
		}

		if ($maxLengths)
		{
			foreach ($maxLengths as $maxLength => $attributeNames)
			{
				$rules[] = array(implode(',', $attributeNames), 'length', 'max' => (int)$maxLength);
			}
		}

		$rules[] = array(implode(',', array_keys($attributes)), 'safe', 'on' => 'search');

		return $rules;
	}
}
