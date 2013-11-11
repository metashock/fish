<?php
/**
 * @copyright 2013 Thorsten Heymann
 */
/**
 * Tests all operations on the index. (The index contains 
 * information about classes and function and theirs locations
 * in the file sytem)
 */
class Phish_IndexTest extends PHPUnit_Framework_TestCase
{

    /**
     * Tests serialization and deserialization of the index.
     */
    public function testSerialize() {
        $index = Phish_Index::load();
        $str = serialize($index);
        $indexRestored = unserialize($str);
        $this->assertEquals($index, $indexRestored);
    }


    /**
     * Tests storage and removal of elements from
     * the index.
     */
    public function testRemove() {
        $index = Phish_Index::load();
        $index->store('TestA', 'testa.php');
        $index->store('TestB', 'testb.php');
        $index->remove('TestA');
        $this->assertNotEmpty($index->classes());
    }
}

