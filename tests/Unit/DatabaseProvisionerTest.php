<?php

namespace Tests\Unit;

use App\Services\DatabaseProvisioner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseProvisionerTest extends TestCase
{
    private function quote(): callable
    {
        return fn (string $v) => "'".addslashes($v)."'";
    }

    public function test_create_statements_build_database_user_and_grant(): void
    {
        $sql = DatabaseProvisioner::createStatements($this->quote(), 's1a2b3c4d_game_db', 'u1a2b3c4d', '%', 'pw123');

        $this->assertSame('CREATE DATABASE IF NOT EXISTS `s1a2b3c4d_game_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $sql[0]);
        $this->assertSame("CREATE USER IF NOT EXISTS 'u1a2b3c4d'@'%' IDENTIFIED BY 'pw123'", $sql[1]);
        $this->assertSame("ALTER USER 'u1a2b3c4d'@'%' IDENTIFIED BY 'pw123'", $sql[2]);
        // "_" di GRANT harus di-escape supaya bukan wildcard
        $this->assertSame("GRANT ALL PRIVILEGES ON `s1a2b3c4d\\_game\\_db`.* TO 'u1a2b3c4d'@'%'", $sql[3]);
    }

    public function test_drop_removes_user_only_when_unused(): void
    {
        $last = DatabaseProvisioner::dropStatements($this->quote(), 's1_db', 'u1', '10.0.0.5', false);
        $this->assertSame(['DROP DATABASE IF EXISTS `s1_db`', "DROP USER IF EXISTS 'u1'@'10.0.0.5'"], $last);

        $shared = DatabaseProvisioner::dropStatements($this->quote(), 's1_db', 'u1', '%', true);
        $this->assertSame("REVOKE ALL PRIVILEGES ON `s1\\_db`.* FROM 'u1'@'%'", $shared[1]);
    }

    public function test_rotate_statement_changes_only_the_password(): void
    {
        $sql = DatabaseProvisioner::rotateStatement($this->quote(), 'u1_abc', '%', 'baruPw');

        $this->assertSame("ALTER USER 'u1_abc'@'%' IDENTIFIED BY 'baruPw'", $sql);
    }

    public function test_unsafe_identifiers_are_rejected(): void
    {
        foreach ([
            ['x`; DROP DATABASE mysql; --', 'u1', '%'],
            ['db', "u1' OR '1'='1", '%'],
            ['db', 'u1', "%' OR 1=1 --"],
            ['', 'u1', '%'],
        ] as [$db, $user, $remote]) {
            try {
                DatabaseProvisioner::createStatements($this->quote(), $db, $user, $remote, 'pw');
                $this->fail("Harusnya ditolak: {$db} {$user} {$remote}");
            } catch (RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
