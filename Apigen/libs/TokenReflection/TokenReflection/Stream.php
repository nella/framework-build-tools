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

namespace TokenReflection;

use TokenReflection\Exception;
use SeekableIterator, Countable, ArrayAccess, Serializable;

// Ensure that we check if we have a native support of traits
if (!defined('NATIVE_TRAITS')) {
	require_once __DIR__ . '/Broker.php';
}

/**
 * Token stream iterator.
 */
class Stream implements SeekableIterator, Countable, ArrayAccess, Serializable
{
	/**
	 * Token source file name.
	 *
	 * @var string
	 */
	private $fileName = 'unknown';

	/**
	 * Tokens storage.
	 *
	 * @var array
	 */
	private $tokens = array();

	/**
	 * Internal pointer.
	 *
	 * @var integer
	 */
	private $position = 0;

	/**
	 * Token stream size.
	 *
	 * @var integer
	 */
	private $count = 0;

	/**
	 * Constructor.
	 *
	 * Creates a token substream.
	 *
	 * @param string $fileName File name
	 * @throws \TokenReflection\Exception\Parse If tokenizer PHP extension is missing
	 * @throws \TokenReflection\Exception\Parse If file does not exist or is not readable
	 */
	public function __construct($fileName)
	{
		if (!extension_loaded('tokenizer')) {
			throw new Exception\Parse('The tokenizer PHP extension is not loaded.', Exception\Parse::UNSUPPORTED);
		}

		$this->fileName = Broker::getRealPath($fileName);

		if (false === $this->fileName) {
			throw new Exception\Parse('File does not exist.', Exception\Parse::FILE_DOES_NOT_EXIST);
		}

		$contents = file_get_contents($this->fileName);
		if (false === $contents) {
			throw new Exception\Parse('File is not readable.', Exception\Parse::FILE_NOT_READABLE);
		}

		$stream = @token_get_all(str_replace(array("\r\n", "\r"), "\n", $contents));

		static $checkLines = array(T_COMMENT => true, T_WHITESPACE => true, T_DOC_COMMENT => true, T_INLINE_HTML => true, T_ENCAPSED_AND_WHITESPACE => true, T_CONSTANT_ENCAPSED_STRING => true);

		foreach ($stream as $position => $token) {
			if (is_array($token)) {
				if (!NATIVE_TRAITS && T_STRING === $token[0]) {
					$lValue = strtolower($token[1]);
					if ('trait' === $lValue) {
						$token[0] = T_TRAIT;
					} elseif ('insteadof' === $lValue) {
						$token[0] = T_INSTEADOF;
					}
				}

				$this->tokens[] = $token;
			} else {
				$previous = $this->tokens[$position - 1];
				$line = $previous[2];
				if (isset($checkLines[$previous[0]])) {
					$line += substr_count($previous[1], "\n");
				}

				$this->tokens[] = array($token, $token, $line);
			}
		}

		$this->count = count($this->tokens);
	}

	/**
	 * Returns the file name this is a part of.
	 *
	 * @return string
	 */
	public function getFileName()
	{
		return $this->fileName;
	}

	/**
	 * Returns the original source code.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return $this->getSourcePart();
	}

	/**
	 * Returns a part of the source code.
	 *
	 * @param mixed $start Start offset
	 * @param mixed $end End offset
	 * @return string
	 */
	public function getSourcePart($start = null, $end = null)
	{
		$start = (int) $start;
		$end = null === $end ? ($this->count - 1) : (int) $end;

		$source = '';
		for ($i = $start; $i <= $end; $i++) {
			$source .= $this->tokens[$i][1];
		}
		return $source;
	}

	/**
	 * Finds the position of the token of the given type.
	 *
	 * @param integer|string $type Token type
	 * @return \TokenReflection\Stream|boolean
	 */
	public function find($type)
	{
		$actual = $this->position;
		while (isset($this->tokens[$this->position])) {
			if ($type === $this->tokens[$this->position][0]) {
				return $this;
			}

			$this->position++;
		}

		$this->position = $actual;
		return false;
	}

	/**
	 * Returns the position of the token with the matching bracket.
	 *
	 * @return \TokenReflection\Stream
	 * @throws \TokenReflection\Exception\Runtime If out of the array
	 * @throws \TokenReflection\Exception\Runtime If there is no brancket at the current position
	 * @throws \TokenReflection\Exception\Runtime If the matching bracket could not be found
	 */
	public function findMatchingBracket()
	{
		static $brackets = array(
			'(' => ')',
			'{' => '}',
			'[' => ']',
			T_CURLY_OPEN => '}',
			T_DOLLAR_OPEN_CURLY_BRACES => '}'
		);

		if (!$this->valid()) {
			throw new Exception\Runtime('Out of array.', Exception\Runtime::DOES_NOT_EXIST);
		}

		$position = $this->position;

		$bracket = $this->tokens[$this->position][0];

		if (!isset($brackets[$bracket])) {
			throw new Exception\Runtime(sprintf('There is no usable bracket at position "%d" in file "%s".', $position, $this->fileName), Exception\Runtime::DOES_NOT_EXIST);
		}

		$searching = $brackets[$bracket];

		$level = 0;
		while (isset($this->tokens[$this->position])) {
			$type = $this->tokens[$this->position][0];
			if ($searching === $type) {
				$level--;
			} elseif ($bracket === $type || ($searching === '}' && ('{' === $type || T_CURLY_OPEN === $type || T_DOLLAR_OPEN_CURLY_BRACES === $type))) {
				$level++;
			}

			if (0 === $level) {
				return $this;
			}

			$this->position++;
		}

		throw new Exception\Runtime(sprintf('Could not find the end bracket "%s" of the bracket at position "%d" in file "%s".', $searching, $position, $this->fileName), Exception\Runtime::DOES_NOT_EXIST);
	}

	/**
	 * Skips whitespaces and comments next to the current position.
	 *
	 * @return \TokenReflection\Stream
	 */
	public function skipWhitespaces()
	{
		static $skipped = array(T_WHITESPACE => true, T_COMMENT => true);

		do {
			$this->position++;
		} while (isset($this->tokens[$this->position]) && isset($skipped[$this->tokens[$this->position][0]]));

		return $this;
	}

	/**
	 * Checks if there is a token of the given type at the given position.
	 *
	 * @param integer|string $type Token type
	 * @param integer $position Position; if none given, consider the current iteration position
	 * @return boolean
	 */
	public function is($type, $position = -1)
	{
		return $type === $this->getType($position);
	}

	/**
	 * Returns the type of a token.
	 *
	 * @param integer $position Token position; if none given, consider the current iteration position
	 * @return string|integer|null
	 */
	public function getType($position = -1)
	{
		if (-1 === $position) {
			$position = $this->position;
		}

		return isset($this->tokens[$position]) ? $this->tokens[$position][0] : null;
	}

	/**
	 * Returns the current token value.
	 *
	 * @param integer $position Token position; if none given, consider the current iteration position
	 * @return stirng
	 */
	public function getTokenValue($position = -1)
	{
		if (-1 === $position) {
			$position = $this->position;
		}

		return isset($this->tokens[$position]) ? $this->tokens[$position][1] : null;
	}

	/**
	 * Returns the token type name.
	 *
	 * @param integer $position Token position; if none given, consider the current iteration position
	 * @return string|null
	 */
	public function getTokenName($position = -1)
	{
		$type = $this->getType($position);
		if (is_string($type)) {
			return $type;
		} elseif (T_TRAIT === $type) {
			return 'T_TRAIT';
		} elseif (T_INSTEADOF === $type) {
			return 'T_INSTEADOF';
		}

		return token_name($type);
	}

	/**
	 * Stream serialization.
	 *
	 * @return string
	 */
	public function serialize()
	{
		return serialize(array($this->fileName, $this->tokens));
	}

	/**
	 * Restores the stream from the serialized state.
	 *
	 * @param string $serialized Serialized form
	 * @throws \TokenReflection\Exception\Runtime On unserialization error
	 */
	public function unserialize($serialized)
	{
		$data = @unserialize($serialized);
		if (false === $data) {
			throw new Exception\Runtime('Could not deserialize the serialized data.', Exception\Runtime::SERIALIZATION_ERROR);
		}
		if (2 !== count($data) || !is_string($data[0]) || !is_array($data[1])) {
			throw new Exception\Runtime('Invalid serialization data.', Exception\Runtime::SERIALIZATION_ERROR);
		}

		$this->fileName = $data[0];
		$this->tokens = $data[1];
		$this->count = count($this->tokens);
		$this->position = 0;
	}

	/**
	 * Checks of there is a token with the given index.
	 *
	 * @param integer $offset Token index
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return isset($this->tokens[$offset]);
	}

	/**
	 * Removes a token.
	 *
	 * Unsupported.
	 *
	 * @param integer $offset Position
	 * @throws \TokenReflection\Exception\Runtime Unsupported
	 */
	public function offsetUnset($offset)
	{
		throw new Exception\Runtime('Removing of tokens from the stream is not supported.', Exception\Runtime::UNSUPPORTED);
	}

	/**
	 * Returns a token at the given index.
	 *
	 * @param integer $offset Token index
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return isset($this->tokens[$offset]) ? $this->tokens[$offset] : null;
	}

	/**
	 * Sets a value of a particular token.
	 *
	 * Unsupported
	 *
	 * @param integer $offset Position
	 * @param mixed $value Value
	 * @throws \TokenReflection\Exception\Runtime Unsupported
	 */
	public function offsetSet($offset, $value)
	{
		throw new Exception\Runtime('Setting token values is not supported.', Exception\Runtime::UNSUPPORTED);
	}

	/**
	 * Returns the current internal pointer value.
	 *
	 * @return integer
	 */
	public function key()
	{
		return $this->position;
	}

	/**
	 * Advances the internal pointer.
	 *
	 * @return \TokenReflection\Stream
	 */
	public function next()
	{
		$this->position++;
		return $this;
	}

	/**
	 * Sets the internal pointer to zero.
	 *
	 * @return \TokenReflection\Stream
	 */
	public function rewind()
	{
		$this->position = 0;
		return $this;
	}

	/**
	 * Returns the current token.
	 *
	 * @return array|null
	 */
	public function current()
	{
		return isset($this->tokens[$this->position]) ? $this->tokens[$this->position] : null;
	}

	/**
	 * Checks if there is a token on the current position.
	 *
	 * @return boolean
	 */
	public function valid()
	{
		return isset($this->tokens[$this->position]);
	}

	/**
	 * Returns the number of tokens in the stream.
	 *
	 * @return integer
	 */
	public function count()
	{
		return $this->count;
	}

	/**
	 * Sets the internal pointer to the given value.
	 *
	 * @param integer $position New position
	 * @return \TokenReflection\Stream
	 */
	public function seek($position)
	{
		$this->position = (int) $position;
		return $this;
	}

	/**
	 * Returns the stream source code.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getSource();
	}
}
