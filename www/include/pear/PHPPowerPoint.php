<?php
/**
 * PHPPowerPoint
 *
 * Copyright (c) 2009 - 2010 PHPPowerPoint
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPPowerPoint
 * @package    PHPPowerPoint
 * @copyright  Copyright (c) 2009 - 2010 PHPPowerPoint (http://www.codeplex.com/PHPPowerPoint)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    0.1.0, 2009-04-27
 */


/** PHPPowerPoint_Slide */
require_once 'PHPPowerPoint/Slide.php';

/** PHPPowerPoint_DocumentProperties */
require_once 'PHPPowerPoint/DocumentProperties.php';

/** PHPPowerPoint_Shared_ZipStreamWrapper */
require_once 'PHPPowerPoint/Shared/ZipStreamWrapper.php';

/** PHPPowerPoint_SlideIterator */
require_once 'PHPPowerPoint/SlideIterator.php';


/**
 * PHPPowerPoint
 *
 * @category   PHPPowerPoint
 * @package    PHPPowerPoint
 * @copyright  Copyright (c) 2009 - 2010 PHPPowerPoint (http://www.codeplex.com/PHPPowerPoint)
 */
class PHPPowerPoint
{
	/**
	 * Document properties
	 *
	 * @var PHPPowerPoint_DocumentProperties
	 */
	private $_properties;

	/**
	 * Collection of Slide objects
	 *
	 * @var PHPPowerPoint_Slide[]
	 */
	private $_slideCollection = array();

	/**
	 * Active slide index
	 *
	 * @var int
	 */
	private $_activeSlideIndex = 0;

	/**
	 * Create a new PHPPowerPoint with one Slide
	 */
	public function __construct()
	{
		// Initialise slide collection and add one slide
		$this->_slideCollection = array();
		$this->_slideCollection[] = new PHPPowerPoint_Slide($this);
		$this->_activeSlideIndex = 0;

		// Create document properties
		$this->_properties = new PHPPowerPoint_DocumentProperties();
	}

	/**
	 * Get properties
	 *
	 * @return PHPPowerPoint_DocumentProperties
	 */
	public function getProperties()
	{
		return $this->_properties;
	}

	/**
	 * Set properties
	 *
	 * @param PHPPowerPoint_DocumentProperties	$value
	 */
	public function setProperties(PHPPowerPoint_DocumentProperties $value)
	{
		$this->_properties = $value;
	}

	/**
	 * Get active slide
	 *
	 * @return PHPPowerPoint_Slide
	 */
	public function getActiveSlide()
	{
		return $this->_slideCollection[$this->_activeSlideIndex];
	}

	/**
	 * Create slide and add it to this presentation
	 *
	 * @return PHPPowerPoint_Slide
	 */
	public function createSlide()
	{
		$newSlide = new PHPPowerPoint_Slide($this);

		$this->addSlide($newSlide);

		return $newSlide;
	}

	/**
	 * Add slide
	 *
	 * @param PHPPowerPoint_Slide $slide
	 * @throws Exception
	 */
	public function addSlide(PHPPowerPoint_Slide $slide = null)
	{
		$this->_slideCollection[] = $slide;
	}

	/**
	 * Remove slide by index
	 *
	 * @param int $index Slide index
	 * @throws Exception
	 */
	public function removeSlideByIndex($index = 0)
	{
		if ($index > count($this->_slideCollection) - 1) {
			throw new Exception("Slide index is out of bounds.");
		} else {
			array_splice($this->_slideCollection, $index, 1);
		}
	}

	/**
	 * Get slide by index
	 *
	 * @param int $index Slide index
	 * @return PHPPowerPoint_Slide
	 * @throws Exception
	 */
	public function getSlide($index = 0)
	{
		if ($index > count($this->_slideCollection) - 1) {
			throw new Exception("Slide index is out of bounds.");
		} else {
			return $this->_slideCollection[$index];
		}
	}

	/**
	 * Get all slides
	 *
	 * @return PHPPowerPoint_Slide[]
	 */
	public function getAllSlides()
	{
		return $this->_slideCollection;
	}

	/**
	 * Get index for slide
	 *
	 * @param PHPPowerPoint_Slide $slide
	 * @return Slide index
	 * @throws Exception
	 */
	public function getIndex(PHPPowerPoint_Slide $slide)
	{
		foreach ($this->_slideCollection as $key => $value) {
			if ($value->getHashCode() == $slide->getHashCode()) {
				return $key;
			}
		}
	}

	/**
	 * Get slide count
	 *
	 * @return int
	 */
	public function getSlideCount()
	{
		return count($this->_slideCollection);
	}

	/**
	 * Get active slide index
	 *
	 * @return int Active slide index
	 */
	public function getActiveSlideIndex()
	{
		return $this->_activeSlideIndex;
	}

	/**
	 * Set active slide index
	 *
	 * @param int $index Active slide index
	 * @throws Exception
	 */
	public function setActiveSlideIndex($index = 0)
	{
		if ($index > count($this->_slideCollection) - 1) {
			throw new Exception("Active slide index is out of bounds.");
		} else {
			$this->_activeSlideIndex = $index;
		}
	}

	/**
	 * Add external slide
	 *
	 * @param PHPPowerPoint_Slide $slide External slide to add
	 * @throws Exception
	 */
	public function addExternalSheet(PHPPowerPoint_Slide $slide) {
		$slide->rebindParent($this);
		$this->addSheet($slide);
	}

	/**
	 * Get slide iterator
	 *
	 * @return PHPPowerPoint_SlideIterator
	 */
	public function getSlideIterator() {
		return new PHPPowerPoint_SlideIterator($this);
	}

	/**
	 * Copy presentation (!= clone!)
	 *
	 * @return PHPPowerPoint
	 */
	public function copy() {
		$copied = clone $this;

		$slideCount = count($this->_slideCollection);
		for ($i = 0; $i < $slideCount; ++$i) {
			$this->_slideCollection[$i] = $this->_slideCollection[$i]->copy();
			$this->_slideCollection[$i]->rebindParent($this);
		}

		return $copied;
	}

	/**
	 * Implement PHP __clone to create a deep clone, not just a shallow copy.
	 */
	public function __clone() {
		$vars = get_object_vars($this);
		foreach ($vars as $key => $value) {
			if (is_object($value)) {
				$this->$key = clone $value;
			} else {
				$this->$key = $value;
			}
		}
	}
}
