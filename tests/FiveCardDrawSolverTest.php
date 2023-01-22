<?php

use PHPUnit\Framework\TestCase;
include_once("src/Solver.php");

class FiveCardDrawSolverTest extends TestCase {

    protected static $solver;

    public static function setUpBeforeClass(): void
    {
        static::$solver = new Solver();
    }

    public function test5cd4s5hTsQh9h() {
        $this->assertEquals("4s5hTsQh9h Qc8d7cTcJd 5s5d7s4dQd 7h6h7d2cJc 3cKs4cKdJs 2hAhKh4hKc As6d5cQsAc", static::$solver->process("five-card-draw 4s5hTsQh9h Qc8d7cTcJd 5s5d7s4dQd 3cKs4cKdJs 2hAhKh4hKc 7h6h7d2cJc As6d5cQsAc"));        
    }

    public function test5cd7h4s4h8c9h() {
        $this->assertEquals("4c8h2h6c9c Ah9d6s2cKh Kd9sAs3cQs 7h4s4h8c9h Tc5h6dAc5c", static::$solver->process("five-card-draw 7h4s4h8c9h Tc5h6dAc5c Kd9sAs3cQs Ah9d6s2cKh 4c8h2h6c9c"));
    }

    public function test5cd5s3s4c2h9d() {
        $this->assertEquals("5s3s4c2h9d 4h6s8hJd5d 5c3cQdTd9s 8dKsTc6c2c 8c3d7h7dTs KhJs9c5h9h AhQhKcQc2d", static::$solver->process("five-card-draw 5s3s4c2h9d 8dKsTc6c2c 4h6s8hJd5d 5c3cQdTd9s AhQhKcQc2d KhJs9c5h9h 8c3d7h7dTs"));
    }

}
