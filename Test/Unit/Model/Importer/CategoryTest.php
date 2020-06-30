<?php

namespace Dadolun\CategoryImporter\Test\Unit\Model\Importer;

use Magento\Catalog\Model\Category;
use Dadolun\CategoryImport\Model\Importer\Category as CategoryImporter;

/**
 * Class CategoryTest
 * Main goal: check original recursive function exiting control
 * @package Dadolun\CategoryImport\Test\Unit\Model\Importer
 */
class CategoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryTreeLevelOneFirstCategory; // A Category
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryTreeLevelOneSecondCategory; // B Category

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryTreeLevelTwoFirstCategory; // C Category

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryTreeLevelThreeFirstCategory; // D Category
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryTreeLevelThreeSecondCategory; // E Category

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryTreeLevelFourFirstCategory; // F Category

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryImporter;

    protected function setUp() {

        $this->categoryImporter = $this->getMockBuilder(CategoryImporter::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(array('getLastParentId'))
            ->getMock();

        /**

         --  A Category (4)
            --  C Category (5)
                -- D Category (6)
                -- E Category (7)
                    -- F Category (8)
         -- B Category (9)

         */

        // FIRST LEVEL - A Category
        $this->categoryTreeLevelOneFirstCategory = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','getName','getChildrenCategories'])
            ->getMock();
        $this->categoryTreeLevelOneFirstCategory->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(4));
        $this->categoryTreeLevelOneFirstCategory->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('A Category'));

        // SECOND LEVEL - C Category
        $this->categoryTreeLevelTwoFirstCategory = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','getName','getChildrenCategories'])
            ->getMock();
        $this->categoryTreeLevelTwoFirstCategory->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(5));
        $this->categoryTreeLevelTwoFirstCategory->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('C Category'));

        // THIRD LEVEL - D Category
        $this->categoryTreeLevelThreeFirstCategory = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','getName','getChildrenCategories'])
            ->getMock();
        $this->categoryTreeLevelThreeFirstCategory->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(6));
        $this->categoryTreeLevelThreeFirstCategory->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('D Category'));

        // THIRD LEVEL - E Category
        $this->categoryTreeLevelThreeSecondCategory = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','getName','getChildrenCategories'])
            ->getMock();
        $this->categoryTreeLevelThreeSecondCategory->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(7));
        $this->categoryTreeLevelThreeSecondCategory->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('E Category'));

        // FOURTH LEVEL - F Category
        $this->categoryTreeLevelFourFirstCategory = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','getName','getChildrenCategories'])
            ->getMock();
        $this->categoryTreeLevelFourFirstCategory->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(8));
        $this->categoryTreeLevelFourFirstCategory->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('F Category'));

        $this->categoryTreeLevelOneFirstCategory['categories'] = [
            $this->categoryTreeLevelTwoFirstCategory
        ];

        $this->categoryTreeLevelOneFirstCategory->expects($this->any())
            ->method('getChildrenCategories')
            ->will($this->returnValue($this->categoryTreeLevelOneFirstCategory['categories']));

        $this->categoryTreeLevelTwoFirstCategory['categories'] = [
            $this->categoryTreeLevelThreeFirstCategory,
            $this->categoryTreeLevelThreeSecondCategory
        ];

        $this->categoryTreeLevelThreeFirstCategory['categories'] = [];

        $this->categoryTreeLevelThreeSecondCategory['categories'] = [
            $this->categoryTreeLevelFourFirstCategory
        ];

        $this->categoryTreeLevelTwoFirstCategory->expects($this->any())
            ->method('getChildrenCategories')
            ->will($this->returnValue($this->categoryTreeLevelTwoFirstCategory['categories']));

        // FIRST LEVEL - B Category
        $this->categoryTreeLevelOneSecondCategory = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','getName','getChildrenCategories'])
            ->getMock();
        $this->categoryTreeLevelOneSecondCategory->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(9));
        $this->categoryTreeLevelOneSecondCategory->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('B Category'));

        $this->categoryTreeLevelOneSecondCategory['categories'] = [];

        $this->categoryTreeLevelOneSecondCategory->expects($this->any())
            ->method('getChildrenCategories')
            ->will($this->returnValue($this->categoryTreeLevelOneSecondCategory['categories']));

        $this->categoryTreeLevelThreeSecondCategory->expects($this->any())
            ->method('getChildrenCategories')
            ->will($this->returnValue($this->categoryTreeLevelThreeSecondCategory['categories']));

    }

    /**
     * Check category insert on first Level after roots
     */
    public function testGetLastParentIdFirstLevelInsert()
    {

        /**

        --  A Category (4)
            --  C Category (5)
                -- D Category (6)
                -- E Category (7)
                    -- F Category (8)
                -- <<<< INSERT
        -- B Category (9)

         */

        $lastParentId = $this->categoryImporter->getLastParentId($this->categoryTreeLevelOneFirstCategory, array('C Category','INSERT'), 'C Category');
        $this->assertEquals(
            $this->categoryTreeLevelTwoFirstCategory->getId(),
            $lastParentId
        );

    }

    /**
     * Check category insert on first Level (one for each root) on missing parent. Should return no match ( = false )
     */
    public function testGetLastParentIdFirstLevelNoResults()
    {

        /**

        --  A Category (4)
            --  C Category (5)
                -- D Category (6)
                -- E Category (7)
                    -- F Category (8)
            -- {No Results}
                -- <<<< INSERT (first try)
        -- B Category (9)
            -- {No Results}
                -- <<<< INSERT (second try)

         */

        $lastParentId = $this->categoryImporter->getLastParentId($this->categoryTreeLevelOneFirstCategory, array('No Results','INSERT'), 'No Results');
        $this->assertEquals(
            false,
            $lastParentId
        );
        $lastParentId = $this->categoryImporter->getLastParentId($this->categoryTreeLevelOneSecondCategory, array('No Results','INSERT'), 'No Results');
        $this->assertEquals(
            false,
            $lastParentId
        );

    }

    /**
     * Check category update on first Level
     */
    public function testGetLastParentIdFirstLevelUpdate()
    {
        /**

        --  A Category (4)
            --  C Category (5)
                -- D Category (6)
                -- E Category (7) <<<< UPDATE
                    -- F Category (8)
        -- B Category (9)

         */

        $lastParentId = $this->categoryImporter->getLastParentId($this->categoryTreeLevelOneFirstCategory, array('C Category','E Category'), 'C Category');
        $this->assertEquals(
            $this->categoryTreeLevelTwoFirstCategory->getId(),
            $lastParentId
        );
    }

    /**
     * Check category insert on second Level
     */
    public function testGetLastParentIdSecondLevelInsert()
    {
        /**

        --  A Category (4)
            --  C Category (5)
            -- D Category (6)
                -- <<<< INSERT
                -- E Category (7)
                    -- F Category (8)
        -- B Category (9)

         */

        $lastParentId = $this->categoryImporter->getLastParentId($this->categoryTreeLevelOneFirstCategory, array('C Category','D Category','INSERT'), 'D Category');
        $this->assertEquals(
            $this->categoryTreeLevelThreeFirstCategory->getId(),
            $lastParentId
        );
    }

    /**
     * Check category insert on third Level on second level not existing category. Should return no match ( = false )
     */
    public function testGetLastParentIdSecondLevelNoResults()
    {
        /**

        --  A Category (4)
            --  C Category (5)
                -- D Category (6)
                -- E Category (7)
                    -- F Category (8)
                    -- {No Results}
                        -- <<<< INSERT
        -- B Category (9)

         */

        $lastParentId = $this->categoryImporter->getLastParentId($this->categoryTreeLevelOneFirstCategory, array('C Category','No Results','INSERT'), 'No Results');
        $this->assertEquals(
            false,
            $lastParentId
        );

    }

    /**
     * Check category update for third Level category.
     */
    public function testGetLastParentIdSecondLevelUpdate()
    {
        /**

        --  A Category (4)
            --  C Category (5)
            -- D Category (6)
                -- E Category (7)
                    -- F Category (8) <<<< UPDATE
        -- B Category (9)

         */

        $lastParentId = $this->categoryImporter->getLastParentId($this->categoryTreeLevelOneFirstCategory, array('C Category','E Category','F Category'), 'E Category');
        $this->assertEquals(
            $this->categoryTreeLevelThreeSecondCategory->getId(),
            $lastParentId
        );
    }

    /**
     * Check category insert for fourth Level category.
     */
    public function testGetLastParentIdThirdLevelInsert()
    {
        /**

        --  A Category (4)
            --  C Category (5)
                -- D Category (6)
                -- E Category (7)
                    -- F Category (8)
                        -- <<<< INSERT
        -- B Category (9)

         */

        $lastParentId = $this->categoryImporter->getLastParentId($this->categoryTreeLevelOneFirstCategory, array('C Category','E Category','F Category','INSERT'), 'F Category');
        $this->assertEquals(
            $this->categoryTreeLevelFourFirstCategory->getId(),
            $lastParentId
        );
    }

    /**
     * Check category insert on not existing fifth Level category. Should return no match ( = false )
     */
    public function testGetLastParentIdThirdLevelNoResults()
    {
        /**

        --  A Category (4)
            --  C Category (5)
            -- D Category (6)
                -- E Category (7)
                    -- F Category (8)
                        -- {No Results}
                            -- <<<< INSERT
        -- B Category (9)

         */

        $lastParentId = $this->categoryImporter->getLastParentId($this->categoryTreeLevelOneFirstCategory, array('C Category','F Category','No Results','INSERT'), 'No Results');
        $this->assertEquals(
            false,
            $lastParentId
        );

    }
}
