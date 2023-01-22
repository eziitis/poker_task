<?php

use PHPUnit\Framework\TestCase;
include_once("src/Solver.php");

class TexasHoldemSolverTest extends TestCase {

    protected static $solver;

    public static function setUpBeforeClass(): void
    {
        static::$solver = new Solver();
    }

    public function testTh5c6dAcAsQs() {
        $this->assertEquals("2cJc Kh4h=Ks4c Kc7h KdJs 6h7d 2hAh", static::$solver->process("texas-holdem 5c6dAcAsQs Ks4c KdJs 2hAh Kh4h Kc7h 6h7d 2cJc"));        
    }

    public function testTh2h5c8sAsKc() {
        $this->assertEquals("Jc6s Qs9h 3cKh KdQh", static::$solver->process("texas-holdem 2h5c8sAsKc Qs9h KdQh 3cKh Jc6s"));
    }

    public function testTh3d4s5dJsQd() {
        $this->assertEquals("9h7h 2dTc KcAs 7sJd TsJc Qh8c 5c4h", static::$solver->process("texas-holdem 3d4s5dJsQd 5c4h 7sJd KcAs 9h7h 2dTc Qh8c TsJc"));
    }

}
