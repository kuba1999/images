<?php
/**
 * Validator.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Images!
 * @subpackage	Validators
 * @since		5.0
 *
 * @date		11.02.15
 */

namespace IPub\Images\Validators;

use Nette;

/**
 * Copyright (c) dotBlue (http://dotblue.net)
 */
class Validator extends Nette\Object implements IValidator
{
	/**
	 * @var array[]
	 */
	private $rules = [];

	/**
	 * Adds rule
	 *
	 * @param  int $width
	 * @param  int $height
	 * @param  int|string $algorithm
	 */
	public function addRule($width, $height, $algorithm)
	{
		$this->rules[] = [
			'width'     => (int) $width,
			'height'    => (int) $height,
			'algorithm' => (string) $algorithm,
		];
	}

	/**
	 * Validates whether provided arguments match at least one rule
	 *
	 * @param int $width
	 * @param int $height
	 * @param int $algorithm
	 *
	 * @return bool
	 */
	public function validate($width, $height, $algorithm)
	{
		foreach ($this->rules as $rule) {
			if (
				(int) $width === $rule['width']
				&& (int) $height === $rule['height']
				&& (
					!isset($algorithm)
					|| (int) $algorithm === $rule['algorithm']
				)
			) {
				return TRUE;
			}
		}

		return count($this->rules) > 0 ? FALSE : TRUE;
	}

	/**
	 * Returns all added rules
	 *
	 * @return array[]
	 */
	public function getRules()
	{
		return $this->rules;
	}
}