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


/** PHPPowerPoint */
require_once 'PHPPowerPoint.php';

/** PHPPowerPoint_Slide */
require_once 'PHPPowerPoint/Slide.php';

/** PHPPowerPoint_Writer_PowerPoint2007 */
require_once 'PHPPowerPoint/Writer/PowerPoint2007.php';

/** PHPPowerPoint_Writer_PowerPoint2007_WriterPart */
require_once 'PHPPowerPoint/Writer/PowerPoint2007/WriterPart.php';

/** PHPPowerPoint_Shared_XMLWriter */
require_once 'PHPPowerPoint/Shared/XMLWriter.php';


/**
 * PHPPowerPoint_Writer_PowerPoint2007_Rels
 *
 * @category   PHPPowerPoint
 * @package    PHPPowerPoint_Writer_PowerPoint2007
 * @copyright  Copyright (c) 2009 - 2010 PHPPowerPoint (http://www.codeplex.com/PHPPowerPoint)
 */
class PHPPowerPoint_Writer_PowerPoint2007_Rels extends PHPPowerPoint_Writer_PowerPoint2007_WriterPart
{
	/**
	 * Write relationships to XML format
	 *
	 * @param 	PHPPowerPoint	$pPHPPowerPoint
	 * @return 	string 		XML Output
	 * @throws 	Exception
	 */
	public function writeRelationships(PHPPowerPoint $pPHPPowerPoint = null)
	{
		// Create XML writer
		$objWriter = null;
		if ($this->getParentWriter()->getUseDiskCaching()) {
			$objWriter = new PHPPowerPoint_Shared_XMLWriter(PHPPowerPoint_Shared_XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
		} else {
			$objWriter = new PHPPowerPoint_Shared_XMLWriter(PHPPowerPoint_Shared_XMLWriter::STORAGE_MEMORY);
		}

		// XML header
		$objWriter->startDocument('1.0','UTF-8','yes');

		// Relationships
		$objWriter->startElement('Relationships');
		$objWriter->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/relationships');

			// Relationship docProps/app.xml
			$this->_writeRelationship(
				$objWriter,
				3,
				'http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties',
				'docProps/app.xml'
			);

			// Relationship docProps/core.xml
			$this->_writeRelationship(
				$objWriter,
				2,
				'http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties',
				'docProps/core.xml'
			);

			// Relationship ppt/presentation.xml
			$this->_writeRelationship(
				$objWriter,
				1,
				'http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument',
				'ppt/presentation.xml'
			);

		$objWriter->endElement();

		// Return
		return $objWriter->getData();
	}

	/**
	 * Write presentation relationships to XML format
	 *
	 * @param 	PHPPowerPoint	$pPHPPowerPoint
	 * @return 	string 		XML Output
	 * @throws 	Exception
	 */
	public function writePresentationRelationships(PHPPowerPoint $pPHPPowerPoint = null)
	{
		// Create XML writer
		$objWriter = null;
		if ($this->getParentWriter()->getUseDiskCaching()) {
			$objWriter = new PHPPowerPoint_Shared_XMLWriter(PHPPowerPoint_Shared_XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
		} else {
			$objWriter = new PHPPowerPoint_Shared_XMLWriter(PHPPowerPoint_Shared_XMLWriter::STORAGE_MEMORY);
		}

		// XML header
		$objWriter->startDocument('1.0','UTF-8','yes');

		// Relationships
		$objWriter->startElement('Relationships');
		$objWriter->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/relationships');

			// Relationship slideMasters/slideMaster1.xml
			$this->_writeRelationship(
				$objWriter,
				1,
				'http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster',
				'slideMasters/slideMaster1.xml'
			);
			
			// Relationship theme/theme1.xml
			$this->_writeRelationship(
				$objWriter,
				2,
				'http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme',
				'theme/theme1.xml'
			);

			// Relationships with slides
			$slideCount = $pPHPPowerPoint->getSlideCount();
			for ($i = 0; $i < $slideCount; ++$i) {
				$this->_writeRelationship(
					$objWriter,
					($i + 1 + 2),
					'http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide',
					'slides/slide' . ($i + 1) . '.xml'
				);
			}

		$objWriter->endElement();

		// Return
		return $objWriter->getData();
	}
	
	/**
	 * Write slide master relationships to XML format
	 *
	 * @return 	string 			XML Output
	 * @throws 	Exception
	 */
	public function writeSlideMasterRelationships()
	{
		// Create XML writer
		$objWriter = null;
		if ($this->getParentWriter()->getUseDiskCaching()) {
			$objWriter = new PHPPowerPoint_Shared_XMLWriter(PHPPowerPoint_Shared_XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
		} else {
			$objWriter = new PHPPowerPoint_Shared_XMLWriter(PHPPowerPoint_Shared_XMLWriter::STORAGE_MEMORY);
		}

		// XML header
		$objWriter->startDocument('1.0','UTF-8','yes');

		// Relationships
		$objWriter->startElement('Relationships');
		$objWriter->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/relationships');

			// Write slideLayout relationships
			$layoutPack		= $this->getParentWriter()->getLayoutPack();
			for ($i = 0; $i < count($layoutPack->getLayouts()); ++$i) {
				$this->_writeRelationship(
					$objWriter,
					$i + 1,
					'http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout',
					'../slideLayouts/slideLayout' . ($i + 1) . '.xml'
				);
			}

			// Relationship theme/theme1.xml
			$this->_writeRelationship(
				$objWriter,
				count($layoutPack->getLayouts()) + 1,
				'http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme',
				'../theme/theme1.xml'
			);

		$objWriter->endElement();

		// Return
		return $objWriter->getData();
	}
	
	/**
	 * Write slide layout relationships to XML format
	 *
	 * @return 	string 			XML Output
	 * @throws 	Exception
	 */
	public function writeSlideLayoutRelationships()
	{
		// Create XML writer
		$objWriter = null;
		if ($this->getParentWriter()->getUseDiskCaching()) {
			$objWriter = new PHPPowerPoint_Shared_XMLWriter(PHPPowerPoint_Shared_XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
		} else {
			$objWriter = new PHPPowerPoint_Shared_XMLWriter(PHPPowerPoint_Shared_XMLWriter::STORAGE_MEMORY);
		}

		// XML header
		$objWriter->startDocument('1.0','UTF-8','yes');

		// Relationships
		$objWriter->startElement('Relationships');
		$objWriter->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/relationships');

			// Write slideMaster relationship
			$this->_writeRelationship(
				$objWriter,
				1,
				'http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster',
				'../slideMasters/slideMaster1.xml'
			);

		$objWriter->endElement();

		// Return
		return $objWriter->getData();
	}

	/**
	 * Write slide relationships to XML format
	 *
	 * Numbering is as follows:
	 * 	rId1 				- Drawings
	 *
	 * @param 	PHPPowerPoint_Slide		$pSlide
	 * @param 	int						$pSlideId
	 * @return 	string 					XML Output
	 * @throws 	Exception
	 */
	public function writeSlideRelationships(PHPPowerPoint_Slide $pSlide = null, $pSlideId = 1)
	{
		// Create XML writer
		$objWriter = null;
		if ($this->getParentWriter()->getUseDiskCaching()) {
			$objWriter = new PHPPowerPoint_Shared_XMLWriter(PHPPowerPoint_Shared_XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
		} else {
			$objWriter = new PHPPowerPoint_Shared_XMLWriter(PHPPowerPoint_Shared_XMLWriter::STORAGE_MEMORY);
		}

		// XML header
		$objWriter->startDocument('1.0','UTF-8','yes');

		// Relationships
		$objWriter->startElement('Relationships');
		$objWriter->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/relationships');
			
			// Starting relation id
			$relId = 1;
			
			// Write slideLayout relationship
			$layoutPack		= $this->getParentWriter()->getLayoutPack();
			$layoutIndex	= $layoutPack->findlayoutIndex( $pSlide->getSlideLayout() );

			$this->_writeRelationship(
				$objWriter,
				$relId++,
				'http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout',
				'../slideLayouts/slideLayout' . ($layoutIndex + 1) . '.xml'
			);
						
			// Write drawing relationships?
			if ($pSlide->getShapeCollection()->count() > 0) {
				// Loop trough images and write relationships
				$iterator = $pSlide->getShapeCollection()->getIterator();
				while ($iterator->valid()) {
					if ($iterator->current() instanceof PHPPowerPoint_Shape_Drawing
						|| $iterator->current() instanceof PHPPowerPoint_Shape_MemoryDrawing) {
						// Write relationship for image drawing
						$this->_writeRelationship(
							$objWriter,
							$relId,
							'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image',
							'../media/' . str_replace(' ', '', $iterator->current()->getIndexedFilename())
						);
					}
	
					$iterator->next();
					++$relId;
				}
			}

		$objWriter->endElement();

		// Return
		return $objWriter->getData();
	}

	/**
	 * Write Override content type
	 *
	 * @param 	PHPPowerPoint_Shared_XMLWriter 	$objWriter 		XML Writer
	 * @param 	int							$pId			Relationship ID. rId will be prepended!
	 * @param 	string						$pType			Relationship type
	 * @param 	string 						$pTarget		Relationship target
	 * @param 	string 						$pTargetMode	Relationship target mode
	 * @throws 	Exception
	 */
	private function _writeRelationship(PHPPowerPoint_Shared_XMLWriter $objWriter = null, $pId = 1, $pType = '', $pTarget = '', $pTargetMode = '')
	{
		if ($pType != '' && $pTarget != '') {
			// Write relationship
			$objWriter->startElement('Relationship');
			$objWriter->writeAttribute('Id', 		'rId' . $pId);
			$objWriter->writeAttribute('Type', 		$pType);
			$objWriter->writeAttribute('Target',	$pTarget);

			if ($pTargetMode != '') {
				$objWriter->writeAttribute('TargetMode',	$pTargetMode);
			}

			$objWriter->endElement();
		} else {
			throw new Exception("Invalid parameters passed.");
		}
	}
}
