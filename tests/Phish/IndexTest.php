<?php

class Phish_IndexTest extends PHPUnit_Framework_TestCase
{

    /**
     * Tests serialization and deserialization
     */
    public function testSerialize() {
        $index = Phish_Index::load();
        $str = serialize($index);
        $indexRestored = unserialize($str);
        $this->assertEquals($index, $indexRestored);
    }


    public function testRemove() {
        $index = Phish_Index::load();
        $index->store('TestA', 'testa.php');
        $index->store('TestB', 'testb.php');
        $index->remove('TestA');
        $this->assertNotEmpty($index->classes());
    }
}
