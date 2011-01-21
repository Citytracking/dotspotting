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
 * @package	PHPPowerPoint_Writer_PowerPoint2007
 * @copyright  Copyright (c) 2009 - 2010 PHPPowerPoint (http://www.codeplex.com/PHPPowerPoint)
 * @license	http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version	0.1.0, 2009-04-27
 */


/** PHPPowerPoint_Writer_PowerPoint2007 */
require_once 'PHPPowerPoint/Writer/PowerPoint2007.php';

/** PHPPowerPoint_Writer_PowerPoint2007_WriterPart */
require_once 'PHPPowerPoint/Writer/PowerPoint2007/WriterPart.php';

/** PHPPowerPoint_Slide */
require_once 'PHPPowerPoint/Slide.php';

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

/** PHPPowerPoint_Shared_Font */
require_once 'PHPPowerPoint/Shared/Font.php';

/** PHPPowerPoint_Shared_String */
require_once 'PHPPowerPoint/Shared/String.php';

/** PHPPowerPoint_Shared_XMLWriter */
require_once 'PHPPowerPoint/Shared/XMLWriter.php';


/**
 * PHPPowerPoint_Writer_PowerPoint2007_Slide
 *
 * @category   PHPPowerPoint
 * @package	PHPPowerPoint_Writer_PowerPoint2007
 * @copyright  Copyright (c) 2006 - 2009 PHPPowerPoint (http://www.codeplex.com/PHPPowerPoint)
 */
class PHPPowerPoint_Writer_PowerPoint2007_Slide extends PHPPowerPoint_Writer_PowerPoint2007_WriterPart
{
	/**
	 * Write slide to XML format
	 *
	 * @param	PHPPowerPoint_Slide		$pSlide
	 * @return	string					XML Output
	 * @throws	Exception
	 */
	public function writeSlide(PHPPowerPoint_Slide $pSlide = null)
	{
		// Check slide
		if (is_null($pSlide))
			throw new Exception("Invalid PHPPowerPoint_Slide object passed.");
			
		// Create XML writer
		$objWriter = null;
		if ($this->getParentWriter()->getUseDiskCaching()) {
			$objWriter = new PHPPowerPoint_Shared_XMLWriter(PHPPowerPoint_Shared_XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
		} else {
			$objWriter = new PHPPowerPoint_Shared_XMLWriter(PHPPowerPoint_Shared_XMLWriter::STORAGE_MEMORY);
		}

		// XML header
		$objWriter->startDocument('1.0','UTF-8','yes');
		
		// p:sld
		$objWriter->startElement('p:sld');
		$objWriter->writeAttribute('xmlns:a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
		$objWriter->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
		$objWriter->writeAttribute('xmlns:p', 'http://schemas.openxmlformats.org/presentationml/2006/main');
		
    		// p:cSld
    		$objWriter->startElement('p:cSld');

    			// p:spTree
    			$objWriter->startElement('p:spTree');
    			
    				// p:nvGrpSpPr
    				$objWriter->startElement('p:nvGrpSpPr');
    				
        				// p:cNvPr
        				$objWriter->startElement('p:cNvPr');
        				$objWriter->writeAttribute('id', '1');
        				$objWriter->writeAttribute('name', '');
        				$objWriter->endElement();
        				
        				// p:cNvGrpSpPr
        				$objWriter->writeElement('p:cNvGrpSpPr', null);
        				
        				// p:nvPr
        				$objWriter->writeElement('p:nvPr', null);
    				
    				$objWriter->endElement();
    				
    				// p:grpSpPr
    				$objWriter->startElement('p:grpSpPr');
    				
        				// a:xfrm
        				$objWriter->startElement('a:xfrm');

            				// a:off
            				$objWriter->startElement('a:off');
            				$objWriter->writeAttribute('x', '0');
            				$objWriter->writeAttribute('y', '0');
            				$objWriter->endElement();
            				
            				// a:ext
            				$objWriter->startElement('a:ext');
            				$objWriter->writeAttribute('cx', '0');
            				$objWriter->writeAttribute('cy', '0');
            				$objWriter->endElement();
            				
            				// a:chOff
            				$objWriter->startElement('a:chOff');
            				$objWriter->writeAttribute('x', '0');
            				$objWriter->writeAttribute('y', '0');
            				$objWriter->endElement();
            				
            				// a:chExt
            				$objWriter->startElement('a:chExt');
            				$objWriter->writeAttribute('cx', '0');
            				$objWriter->writeAttribute('cy', '0');
            				$objWriter->endElement();
        				
        				$objWriter->endElement();
        				
        			$objWriter->endElement();
        				
        			// Loop shapes
        			$shapeId 	= 0;
        			$relationId = 1;
        			$shapes 	= $pSlide->getShapeCollection();
        			foreach ($shapes as $shape)
        			{
        				// Increment $shapeId
        				++$shapeId;
      					
        				// Check type
        				if ($shape instanceof PHPPowerPoint_Shape_BaseDrawing)
        				{
        					// Picture --> $relationId
        					++$relationId;
        					
        					$this->_writePic($objWriter, $shape, $shapeId, $relationId);
        				}
        				else if ($shape instanceof PHPPowerPoint_Shape_RichText)
        				{
        					$this->_writeTxt($objWriter, $shape, $shapeId);
        				}
        			}

    			$objWriter->endElement();
    			
    		$objWriter->endElement();
		
    		// p:clrMapOvr
    		$objWriter->startElement('p:clrMapOvr');
    			
    			// a:masterClrMapping
    			$objWriter->writeElement('a:masterClrMapping', '');

    		$objWriter->endElement();
	
		$objWriter->endElement();
		
		// Return
		return $objWriter->getData();
	}

	/**
	 * Write pic
	 *
	 * @param	PHPPowerPoint_Shared_XMLWriter		$objWriter		XML Writer
	 * @param	PHPPowerPoint_Shape_BaseDrawing		$shape
	 * @param	int									$shapeId
	 * @param	int									$relationId
	 * @throws	Exception
	 */
	private function _writePic(PHPPowerPoint_Shared_XMLWriter $objWriter = null, PHPPowerPoint_Shape_BaseDrawing $shape = null, $shapeId, $relationId)
	{
		// p:pic
		$objWriter->startElement('p:pic');
		
			// p:nvPicPr
			$objWriter->startElement('p:nvPicPr');
			
				// p:cNvPr
				$objWriter->startElement('p:cNvPr');
                $objWriter->writeAttribute('id', $shapeId);
                $objWriter->writeAttribute('name', $shape->getName());
				$objWriter->writeAttribute('descr', $shape->getDescription());
                $objWriter->endElement();
                        				
                // p:cNvPicPr
                $objWriter->startElement('p:cNvPicPr');
                
                	// a:picLocks
                	$objWriter->startElement('a:picLocks');
                	$objWriter->writeAttribute('noChangeAspect', '1');
                	$objWriter->endElement();
                
                $objWriter->endElement();
                    				
                // p:nvPr
        		$objWriter->writeElement('p:nvPr', null);
        				
			$objWriter->endElement();
                				
			// p:blipFill
			$objWriter->startElement('p:blipFill');
			
				// a:blip
				$objWriter->startElement('a:blip');
				$objWriter->writeAttribute('r:embed', 'rId' . $relationId);
				$objWriter->endElement();
				
				// a:stretch
				$objWriter->startElement('a:stretch');
					$objWriter->writeElement('a:fillRect', null);
                $objWriter->endElement();
                
			$objWriter->endElement();
			
			// p:spPr
			$objWriter->startElement('p:spPr');
			
				// a:xfrm
				$objWriter->startElement('a:xfrm');
				$objWriter->writeAttribute('rot', PHPPowerPoint_Shared_Drawing::degreesToAngle($shape->getRotation()));
				
					// a:off
					$objWriter->startElement('a:off');
					$objWriter->writeAttribute('x', PHPPowerPoint_Shared_Drawing::pixelsToEMU($shape->getOffsetX()));
                    $objWriter->writeAttribute('y', PHPPowerPoint_Shared_Drawing::pixelsToEMU($shape->getOffsetY()));
                    $objWriter->endElement();
                            				
                    // a:ext
                    $objWriter->startElement('a:ext');
                    $objWriter->writeAttribute('cx', PHPPowerPoint_Shared_Drawing::pixelsToEMU($shape->getWidth()));
                    $objWriter->writeAttribute('cy', PHPPowerPoint_Shared_Drawing::pixelsToEMU($shape->getHeight()));
                    $objWriter->endElement();

				$objWriter->endElement();
				
				// a:prstGeom
				$objWriter->startElement('a:prstGeom');
				$objWriter->writeAttribute('prst', 'rect');
				
					// a:avLst
					$objWriter->writeElement('a:avLst', null);
					
				$objWriter->endElement();
				
				if ($shape->getShadow()->getVisible()) {
					// a:effectLst
					$objWriter->startElement('a:effectLst');
					
						// a:outerShdw
						$objWriter->startElement('a:outerShdw');
						$objWriter->writeAttribute('blurRad', 		PHPPowerPoint_Shared_Drawing::pixelsToEMU($shape->getShadow()->getBlurRadius()));
						$objWriter->writeAttribute('dist',			PHPPowerPoint_Shared_Drawing::pixelsToEMU($shape->getShadow()->getDistance()));
						$objWriter->writeAttribute('dir',			PHPPowerPoint_Shared_Drawing::degreesToAngle($shape->getShadow()->getDirection()));
						$objWriter->writeAttribute('algn',			$shape->getShadow()->getAlignment());
						$objWriter->writeAttribute('rotWithShape', 	'0');

							// a:srgbClr
							$objWriter->startElement('a:srgbClr');
							$objWriter->writeAttribute('val',		$shape->getShadow()->getColor()->getRGB());

								// a:alpha
								$objWriter->startElement('a:alpha');
								$objWriter->writeAttribute('val', 	$shape->getShadow()->getAlpha() * 1000);
								$objWriter->endElement();

							$objWriter->endElement();

						$objWriter->endElement();

					$objWriter->endElement();
				}
                						
            $objWriter->endElement();
                
        $objWriter->endElement();
	}
	
	/**
	 * Write txt
	 *
	 * @param	PHPPowerPoint_Shared_XMLWriter		$objWriter		XML Writer
	 * @param	PHPPowerPoint_Shape_RichText		$shape
	 * @param	int									$shapeId
	 * @throws	Exception
	 */
	private function _writeTxt(PHPPowerPoint_Shared_XMLWriter $objWriter = null, PHPPowerPoint_Shape_RichText $shape = null, $shapeId)
	{
		// p:sp
		$objWriter->startElement('p:sp');
		
			// p:nvSpPr
			$objWriter->startElement('p:nvSpPr');
			
				// p:cNvPr
				$objWriter->startElement('p:cNvPr');
                $objWriter->writeAttribute('id', $shapeId);
                $objWriter->writeAttribute('name', '');
                $objWriter->endElement();
                        				
                // p:cNvSpPr
                $objWriter->startElement('p:cNvSpPr');
                $objWriter->writeAttribute('txBox', '1');
                $objWriter->endElement();
                    				
                // p:nvPr
        		$objWriter->writeElement('p:nvPr', null);
        				
			$objWriter->endElement();
			
			// p:spPr
			$objWriter->startElement('p:spPr');
			
				// a:xfrm
				$objWriter->startElement('a:xfrm');
				$objWriter->writeAttribute('rot', PHPPowerPoint_Shared_Drawing::degreesToAngle($shape->getRotation()));
				
					// a:off
					$objWriter->startElement('a:off');
					$objWriter->writeAttribute('x', PHPPowerPoint_Shared_Drawing::pixelsToEMU($shape->getOffsetX()));
                    $objWriter->writeAttribute('y', PHPPowerPoint_Shared_Drawing::pixelsToEMU($shape->getOffsetY()));
                    $objWriter->endElement();
                            				
                    // a:ext
                    $objWriter->startElement('a:ext');
                    $objWriter->writeAttribute('cx', PHPPowerPoint_Shared_Drawing::pixelsToEMU($shape->getWidth()));
                    $objWriter->writeAttribute('cy', PHPPowerPoint_Shared_Drawing::pixelsToEMU($shape->getHeight()));
                    $objWriter->endElement();

				$objWriter->endElement();
				
				// a:prstGeom
				$objWriter->startElement('a:prstGeom');
				$objWriter->writeAttribute('prst', 'rect');
				$objWriter->endElement();
				
				// a:noFill
				$objWriter->writeElement('a:noFill', null);
				
				if ($shape->getShadow()->getVisible()) {
					// a:effectLst
					$objWriter->startElement('a:effectLst');
					
						// a:outerShdw
						$objWriter->startElement('a:outerShdw');
						$objWriter->writeAttribute('blurRad', 		PHPPowerPoint_Shared_Drawing::pixelsToEMU($shape->getShadow()->getBlurRadius()));
						$objWriter->writeAttribute('dist',			PHPPowerPoint_Shared_Drawing::pixelsToEMU($shape->getShadow()->getDistance()));
						$objWriter->writeAttribute('dir',			PHPPowerPoint_Shared_Drawing::degreesToAngle($shape->getShadow()->getDirection()));
						$objWriter->writeAttribute('algn',			$shape->getShadow()->getAlignment());
						$objWriter->writeAttribute('rotWithShape', 	'0');

							// a:srgbClr
							$objWriter->startElement('a:srgbClr');
							$objWriter->writeAttribute('val',		$shape->getShadow()->getColor()->getRGB());

								// a:alpha
								$objWriter->startElement('a:alpha');
								$objWriter->writeAttribute('val', 	$shape->getShadow()->getAlpha() * 1000);
								$objWriter->endElement();

							$objWriter->endElement();

						$objWriter->endElement();

					$objWriter->endElement();
				}
                						
            $objWriter->endElement();
            
			// p:txBody
			$objWriter->startElement('p:txBody');
			
				// a:bodyPr
				$objWriter->startElement('a:bodyPr');
                $objWriter->writeAttribute('wrap', 'square');
                $objWriter->writeAttribute('rtlCol', '0');
                
                    // a:spAutoFit
            		$objWriter->writeElement('a:spAutoFit', null);
                
                $objWriter->endElement();
                
                // a:lstStyle
            	$objWriter->writeElement('a:lstStyle', null);
            		
				// a:p
				$objWriter->startElement('a:p');
				
	        		// a:pPr
        			$objWriter->startElement('a:pPr');
        			$objWriter->writeAttribute('algn', 		$shape->getAlignment()->getHorizontal());
        			$objWriter->writeAttribute('fontAlgn', 	$shape->getAlignment()->getVertical());
        			$objWriter->writeAttribute('indent', 	$shape->getAlignment()->getIndent());
        			$objWriter->writeAttribute('lvl', 		$shape->getAlignment()->getLevel());
        			$objWriter->endElement();

        		// Loop trough rich text elements
        		$elements = $shape->getRichTextElements();
        		foreach ($elements as $element) {
        			if ($element instanceof PHPPowerPoint_Shape_RichText_Break) {
            			// a:br
            			$objWriter->writeElement('a:br', null);
        			}
            		elseif ($element instanceof PHPPowerPoint_Shape_RichText_Run
            				|| $element instanceof PHPPowerPoint_Shape_RichText_TextElement)
            		{
            			// a:r
            			$objWriter->startElement('a:r');
            				
            				// a:rPr
            				if ($element instanceof PHPPowerPoint_Shape_RichText_Run) {
            					// a:rPr
            					$objWriter->startElement('a:rPr');
            					
                					// Bold
                					$objWriter->writeAttribute('b', ($element->getFont()->getBold() ? 'true' : 'false'));
                
                					// Italic
                					$objWriter->writeAttribute('i', ($element->getFont()->getItalic() ? 'true' : 'false'));
                					
                					// Strikethrough
                					$objWriter->writeAttribute('strike', ($element->getFont()->getStrikethrough() ? 'sngStrike' : 'noStrike'));
                						
                					// Size
                					$objWriter->writeAttribute('sz', ($element->getFont()->getSize() * 100));
                							
                					// Underline
                					$objWriter->writeAttribute('u', $element->getFont()->getUnderline());
                						
                					// Superscript / subscript
                					if ($element->getFont()->getSuperScript() || $element->getFont()->getSubScript()) {
                						if ($element->getFont()->getSuperScript()) {
                							$objWriter->writeAttribute('baseline', '30000');
                						} else if ($element->getFont()->getSubScript()) {
                							$objWriter->writeAttribute('baseline', '-25000');
                						}
                					}
            							
            						// Color - a:solidFill
            						$objWriter->startElement('a:solidFill');
            						
            							// a:srgbClr
                						$objWriter->startElement('a:srgbClr');
                						$objWriter->writeAttribute('val', $element->getFont()->getColor()->getRGB());
                						$objWriter->endElement();	
            						
            						$objWriter->endElement();	
            						
            						// Font - a:latin
            						$objWriter->startElement('a:latin');
            						$objWriter->writeAttribute('typeface', $element->getFont()->getName());
            						$objWriter->endElement();
    
            					$objWriter->endElement();
            				}
            					
            				// t
            				$objWriter->startElement('a:t');
            				$objWriter->writeRaw(PHPPowerPoint_Shared_String::ControlCharacterPHP2OOXML( htmlspecialchars($element->getText()) ));
            				$objWriter->endElement();
            					
            			$objWriter->endElement();
            		}
        		}

        				
				$objWriter->endElement();
			
			$objWriter->endElement();
			
            
	/*

						<a:r>
							<a:rPr lang="en-US" dirty="0" err="1" smtClean="0" />
						</a:r>

	*/
                
        $objWriter->endElement();
	}
}
