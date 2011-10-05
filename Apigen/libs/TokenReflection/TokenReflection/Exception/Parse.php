<?php
/**
 * PHP Token Reflection
 *
 * Version 1.0.0 RC 1
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this library in the file LICENSE.
 *
 * @author Ondřej Nešpor
 * @author Jaroslav Hanslík
 */

namespace TokenReflection\Exception;

use TokenReflection;

/**
 * Parse exception.
 *
 * Thrown while parsing source codes.
 */
class Parse extends TokenReflection\Exception
{
	/**#@+
	 * The requested file does not exist.
	 *
	 * @var integer
	 */
	const FILE_DOES_NOT_EXIST = 10;

	/**
	 * The requested file is not readable.
	 */
	const FILE_NOT_READABLE = 11;

	/**
	 * The requested directory does not exist.
	 */
	const DIR_DOES_NOT_EXIST = 12;

	/**
	 * An invalid parent reflection object was provided.
	 */
	const INVALID_PARENT = 13;

	/**
	 * Unable to parse the reflection object.
	 */
	const PARSE_ELEMENT_ERROR = 14;

	/**
	 * Unable to parse element children.
	 */
	const PARSE_CHILDREN_ERROR = 15;
}
