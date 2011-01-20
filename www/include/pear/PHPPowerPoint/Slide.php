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
 * @package    PHPPowerPoint_Slide
 * @copyright  Copyright (c) 2009 - 2010 PHPPowerPoint (http://www.codeplex.com/PHPPowerPoint)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    0.1.0, 2009-04-27
 */


/** PHPPowerPoint */
require_once 'PHPPowerPoint.php';

/** PHPPowerPoint_Slide_Layout */
require_once 'PHPPowerPoint/Slide/Layout.php';

/** PHPPowerPoint_Shape */
require_once 'PHPPowerPoint/Shape.php';

/** PHPPowerPoint_Shape_RichText */
require_once 'PHPPowerPoint/Shape/RichText.php';

/** PHPPowerPoint_Shape_BaseDrawing */
require_once 'PHPPowerPoint/Shape/BaseDrawing.php';

/** PHPPowerPoint_Shape_Drawing */
require_once 'PHPPowerPoint/Shape/Drawing.php';

/** PHPPowerPoint_Shape_MemoryDrawing */
require_once 'PHPPowerPoint/Shape/MemoryDrawing.php';

/** PHPPowerPoint_IComparable */
require_once 'PHPPowerPoint/IComparable.php';

/** PHPPowerPoint_Shared_Font */
require_once 'PHPPowerPoint/Shared/Font.php';

/** PHPPowerPoint_Shared_String */
require_once 'PHPPowerPoint/Shared/String.php';


/**
 * PHPPowerPoint_Slide
 *
 * @category   PHPPowerPoint
 * @package    PHPPowerPoint_Slide
 * @copyright  Copyright (c) 2009 - 2010 PHPPowerPoint (http://www.codeplex.com/PHPPowerPoint)
 */
class PHPPowerPoint_Slide implements PHPPowerPoint_IComparable
{
	/**
	 * Parent presentation
	 *
	 * @var PHPPowerPoint
	 */
	private $_parent;

	/**
	 * Collection of shapes
	 *
	 * @var PHPPowerPoint_Shape[]
	 */
	private $_shapeCollection = null;
	
	/**
	 * Slide identifier
	 * 
	 * @var string
	 */
	private $_identifier;
	
	/**
	 * Slide layout
	 * 
	 * @var string
	 */
	private $_slideLayout = PHPPowerPoint_Slide_Layout::BLANK;

	/**
	 * Create a new slide
	 *
	 * @param PHPPowerPoint 		$pParent
	 */
	public function __construct(PHPPowerPoint $pParent = null)
	{
		// Set parent
		$this->_parent = $pParent;

    	// Shape collection
    	$this->_shapeCollection = new ArrayObject();
    	
    	// Set identifier
    	$this->_identifier = md5(rand(0,9999) . time());
	}

	/**
	 * Get collection of shapes
	 *
	 * @return PHPPowerPoint_Shape[]
	 */
	public function getShapeCollection()
	{
		return $this->_shapeCollection;
	}
	
	/**
	 * Add shape to slide
	 * 
	 * @param PHPPowerPoint_Shape $shape
	 */
	public function addShape(PHPPowerPoint_Shape $shape)
	{
		$shape->setSlide($this);
	}
	
	/**
	 * Create rich text shape
	 * 
	 * @return PHPPowerPoint_Shape_RichText
	 */
	public function createRichTextShape()
	{
		$shape = new PHPPowerPoint_Shape_RichText();
		$this->addShape($shape);
		return $shape;
	}
	
	/**
	 * Create drawing shape
	 * 
	 * @return PHPPowerPoint_Shape_Drawing
	 */
	public function createDrawingShape()
	{
		$shape = new PHPPowerPoint_Shape_Drawing();
		$this->addShape($shape);
		return $shape;
	}

    /**
     * Get parent
     *
     * @return PHPPowerPoint
     */
    public function getParent() {
    	return $this->_parent;
    }

    /**
     * Re-bind parent
     *
     * @param PHPPowerPoint $parent
     */
    public function rebindParent(PHPPowerPoint $parent) {
		$this->_parent->removeSlideByIndex(
			$this->_parent->getIndex($this)
		);
		$this->_parent = $parent;
    }
    
    /**
     * Get slide layout
     * 
     * @return string
     */
    public function getSlideLayout() {
    	return $this->_slideLayout;
    }
    
    /**
     * Set slide layout
     * 
     * @param string $layout
     */
    public function setSlideLayout($layout = PHPPowerPoint_Slide_Layout::BLANK) {
    	$this->_slideLayout = $layout;
    }

	/**
	 * Get hash code
	 *
	 * @return string	Hash code
	 */
	public function getHashCode() {
    	return md5(
    		  $this->_identifier
    		. __CLASS__
    	);
    }
    
    /**
     * Hash index
     *
     * @var string
     */
    private $_hashIndex;
    
	/**
	 * Get hash index
	 * 
	 * Note that this index may vary during script execution! Only reliable moment is
	 * while doing a write of a workbook and when changes are not allowed.
	 *
	 * @return string	Hash index
	 */
	public function getHashIndex() {
		return $this->_hashIndex;
	}
	
	/**
	 * Set hash index
	 * 
	 * Note that this index may vary during script execution! Only reliable moment is
	 * while doing a write of a workbook and when changes are not allowed.
	 *
	 * @param string	$value	Hash index
	 */
	public function setHashIndex($value) {
		$this->_hashIndex = $value;
	}

	/**
	 * Copy slide (!= clone!)
	 *
	 * @return PHPPowerPoint_Slide
	 */
	public function copy() {
		$copied = clone $this;

		return $copied;
	}

	/**
	 * Implement PHP __clone to create a deep clone, not just a shallow copy.
	 */
	public function __clone() {
		foreach ($this as $key => $val) {
			if (is_object($val) || (is_array($val))) {
				$this->{$key} = unserialize(serialize($val));
			}
		}
	}
}
