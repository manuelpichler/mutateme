<?php
/**
 * Mutateme
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://github.com/padraic/mutateme/blob/rewrite/LICENSE
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to padraic@php.net so we can send you a copy immediately.
 *
 * @category   Mutateme
 * @package    Mutateme
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2010 Pádraic Brady (http://blog.astrumfutura.com)
 * @license    http://github.com/padraic/mutateme/blob/rewrite/LICENSE New BSD License
 */

require_once 'Mutateme/Mutation/BooleanAnd.php';

class Mutateme_Mutation_BooleanAndTest extends PHPUnit_Framework_TestCase
{

    public function testReturnsTokenEquivalentToBooleanOr()
    {
        $mutation = new \Mutateme\Mutation\BooleanAnd;
        $this->assertEquals(
            array(
                10 => array(T_BOOLEAN_OR, '||')
            ),
            $mutation->getMutation(array(), 10)
        );
    }

}
