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
 * @package    PHPPowerPoint_Style
 * @copyright  Copyright (c) 2009 - 2010 PHPPowerPoint (http://www.codeplex.com/PHPPowerPoint)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    0.1.0, 2009-04-27
 */


/** PHPPowerPoint_Style_Color */
require_once 'PHPPowerPoint/Style/Color.php';

/** PHPPowerPoint_IComparable */
require_once 'PHPPowerPoint/IComparable.php';


/**
 * PHPPowerPoint_Style_Border
 *
 * @category   PHPPowerPoint
 * @package    PHPPowerPoint_Style
 * @copyright  Copyright (c) 2009 - 2010 PHPPowerPoint (http://www.codeplex.com/PHPPowerPoint)
 */
class PHPPowerPoint_Style_Border implements PHPPowerPoint_IComparable
{
	/* Border style */
	const BORDER_NONE				= 'none';
	const BORDER_DASHDOT			= 'dashDot';
	const BORDER_DASHDOTDOT			= 'dashDotDot';
	const BORDER_DASHED				= 'dashed';
	const BORDER_DOTTED				= 'dotted';
	const BORDER_DOUBLE				= 'double';
	const BORDER_HAIR				= 'hair';
	const BORDER_MEDIUM				= 'medium';
	const BORDER_MEDIUMDASHDOT		= 'mediumDashDot';
	const BORDER_MEDIUMDASHDOTDOT	= 'mediumDashDotDot';
	const BORDER_MEDIUMDASHED		= 'mediumDashed';
	const BORDER_SLANTDASHDOT		= 'slantDashDot';
	const BORDER_THICK				= 'thick';
	const BORDER_THIN				= 'thin';
	
	/**
	 * Border style
	 *
	 * @var string
	 */
	private $_borderStyle;
	
	/**
	 * Border color
	 * 
	 * @var PHPPowerPoint_Style_Color
	 */
	private $_borderColor;
	
	/**
	 * Parent
	 *
	 * @var PHPPowerPoint_Style_Borders
	 */
	private $_parent;
	
	/**
	 * Parent Property Name
	 *
	 * @var string
	 */
	private $_parentPropertyName;
		
    /**
     * Create a new PHPPowerPoint_Style_Border
     */
    public function __construct()
    {
    	// Initialise values
		$this->_borderStyle			= PHPPowerPoint_Style_Border::BORDER_NONE;
		$this->_borderColor			= new PHPPowerPoint_Style_Color(PHPPowerPoint_Style_Color::COLOR_BLACK);
    }

	/**
	 * Property Prepare bind
	 *
	 * Configures this object for late binding as a property of a parent object
	 *	 
	 * @param $parent
	 * @param $parentPropertyName
	 */
	public function propertyPrepareBind($parent, $parentPropertyName)
	{
		// Initialize parent PHPPowerPoint_Style for late binding. This relationship purposely ends immediately when this object
		// is bound to the PHPPowerPoint_Style object pointed to so as to prevent circular references.
		$this->_parent		 		= $parent;
		$this->_parentPropertyName	= $parentPropertyName;
	}
    
    /**
     * Property Get Bound
     *
     * Returns the PHPPowerPoint_Style_Border that is actual bound to PHPPowerPoint_Style_Borders
	 *
	 * @return PHPPowerPoint_Style_Border
     */
	private function propertyGetBound() {
		if(!isset($this->_parent))
			return $this;																// I am bound

		if($this->_parent->propertyIsBound($this->_parentPropertyName))
		{
			switch($this->_parentPropertyName)											// Another one is bound
			{
				case "_left":
					return $this->_parent->getLeft();		

				case "_right":
					return $this->_parent->getRight();		

				case "_top":
					return $this->_parent->getTop();	
					
				case "_bottom":
					return $this->_parent->getBottom();

				case "_diagonal":
					return $this->_parent->getDiagonal();	

				case "_vertical":
					return $this->_parent->getVertical();

				case "_horizontal":
					return $this->_parent->getHorizontal();
			}
		}

		return $this;																	// No one is bound yet
	}
	
	/**
     * Property Begin Bind
     *
     * If no PHPPowerPoint_Style_Border has been bound to PHPPowerPoint_Style_Borders then bind this one. Return the actual bound one.
	 *
	 * @return PHPPowerPoint_Style_Border
     */
	private function propertyBeginBind() {
	
		if(!isset($this->_parent))
			return $this;																// I am already bound

		if($this->_parent->propertyIsBound($this->_parentPropertyName))
		{
			switch($this->_parentPropertyName)											// Another one is already bound
			{
				case "_left":
					return $this->_parent->getLeft();		

				case "_right":
					return $this->_parent->getRight();		

				case "_top":
					return $this->_parent->getTop();	
					
				case "_bottom":
					return $this->_parent->getBottom();

				case "_diagonal":
					return $this->_parent->getDiagonal();	

				case "_vertical":
					return $this->_parent->getVertical();

				case "_horizontal":
					return $this->_parent->getHorizontal();
			}
		}
			
		$this->_parent->propertyCompleteBind($this, $this->_parentPropertyName);		// Bind myself
		$this->_parent = null;
		return $this;
	}
        
    /**
     * Apply styles from array
     * 
     * <code>
     * $objPHPPowerPoint->getActiveSheet()->getStyle('B2')->getBorders()->getTop()->applyFromArray(
     * 		array(
     * 			'style' => PHPPowerPoint_Style_Border::BORDER_DASHDOT,
     * 			'color' => array(
     * 				'rgb' => '808080'
     * 			)
     * 		)
     * );
     * </code>
     * 
     * @param	array	$pStyles	Array containing style information
     * @throws	Exception
     */
    public function applyFromArray($pStyles = null) {
    	if (is_array($pStyles)) {
    		if (array_key_exists('style', $pStyles)) {
    			$this->setBorderStyle($pStyles['style']);
    		}
    	    if (array_key_exists('color', $pStyles)) {
    			$this->getColor()->applyFromArray($pStyles['color']);
    		}
    	} else {
    		throw new Exception("Invalid style array passed.");
    	}
    }
    
    /**
     * Get Border style
     *
     * @return string
     */
    public function getBorderStyle() {
    	return $this->propertyGetBound()->_borderStyle;
    }
    
    /**
     * Set Border style
     *
     * @param string $pValue
     */
    public function setBorderStyle($pValue = PHPPowerPoint_Style_Border::BORDER_NONE) {
    
        if ($pValue == '') {
    		$pValue = PHPPowerPoint_Style_Border::BORDER_NONE;
    	}
    	$this->propertyBeginBind()->_borderStyle = $pValue;
    }
    
    /**
     * Get Border Color
     *
     * @return PHPPowerPoint_Style_Color
     */
    public function getColor() {
    	// It's a get but it may lead to a modified color which we won't detect but in which case we must bind.
    	// So bind as an assurance.
    	return $this->propertyBeginBind()->_borderColor;
    }
    
    /**
     * Set Border Color
     *
     * @param 	PHPPowerPoint_Style_Color $pValue
     * @throws 	Exception
     */
    public function setColor(PHPPowerPoint_Style_Color $pValue = null) {
   		$this->propertyBeginBind()->_borderColor = $pValue;
    }
    
	/**
	 * Get hash code
	 *
	 * @return string	Hash code
	 */	
	public function getHashCode() {
		$property = $this->propertyGetBound();
    	return md5(
    		  $property->_borderStyle
    		. $property->_borderColor->getHashCode()
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
