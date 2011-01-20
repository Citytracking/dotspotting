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
 * @package    PHPPowerPoint_Writer_PowerPoint2007
 * @copyright  Copyright (c) 2009 - 2010 PHPPowerPoint (http://www.codeplex.com/PHPPowerPoint)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    0.1.0, 2009-04-27
 */


/**
 * PHPPowerPoint_Writer_PowerPoint2007_LayoutPack
 *
 * @category   PHPPowerPoint
 * @package    PHPPowerPoint_Writer_PowerPoint2007
 * @copyright  Copyright (c) 2009 - 2010 PHPPowerPoint (http://www.codeplex.com/PHPPowerPoint)
 */
abstract class PHPPowerPoint_Writer_PowerPoint2007_LayoutPack
{
	/**
	 * Master slide.
	 * 
	 * @var array
	 */
	protected $_masterSlide = array();
	
	/**
	 * Array of slide layouts.
	 * 
	 * These are all an array consisting of:
	 * - name (string)
	 * - body (string)
	 * 
	 * @var array
	 */
	protected $_layouts = array();
	
	/**
	 * Get master slide.
	 * 
	 * @return array
	 */
	public function getMasterSlide()
	{
		return $this->_masterSlide;
	}
	
	/**
	 * Get array of slide layouts.
	 * 
	 * These are all an array consisting of:
	 * - name (string)
	 * - body (string)
	 * 
	 * @return array
	 */
	public function getLayouts()
	{
		return $this->_layouts;
	}
	
	/**
	 * Find specific slide layout.
	 * 
	 * This is an array consisting of:
	 * - name (string)
	 * - body (string)
	 * 
	 * @return array
	 * @throws Exception
	 */
	public function findLayout($name = '')
	{
		foreach ($this->_layouts as $layout)
		{
			if ($layout['name'] == $name)
			{
				return $layout;
			}
		}
		
		throw new Exception("Could not find slide layout $name in current layout pack.");
	}
	
	/**
	 * Find specific slide layout index.
	 * 
	 * @return int
	 * @throws Exception
	 */
	public function findLayoutIndex($name = '')
	{
		$i = 0;
		foreach ($this->_layouts as $layout)
		{
			if ($layout['name'] == $name)
			{
				return $i;
			}
			
			++$i;
		}
		
		throw new Exception("Could not find slide layout $name in current layout pack.");
	}
}
