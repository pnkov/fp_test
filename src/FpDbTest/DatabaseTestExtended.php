<?php

namespace App\FpDbTest;

use Exception;

class DatabaseTestExtended
{
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function testBuildQuery(): void
    {
        $results = [];

        $results[] = $this->db->buildQuery(
            "SELECT * FROM table WHERE a = 'How are you?' AND b = ?",
            ['Jack']
        );

        $results[] = $this->db->buildQuery(
            "SELECT * FROM `tab``l\\`e?{}` WHERE a = ''' \\ \\' ?{}' AND b = ?",
            ['Jack']
        );

        $results[] = $this->db->buildQuery(
            'SELECT * FROM `tab``l\\`e?{}` WHERE a = """ \\ \\" ?{}" AND b = ?',
            ['Jack']
        );

        $results[] = $this->db->buildQuery(
            "UPDATE table SET a = ?, b = ? WHERE c = ?",
            ['foo', 'bar', 'baz']
        );

        $correct = [
            "SELECT * FROM table WHERE a = 'How are you?' AND b = 'Jack'",
            "SELECT * FROM `tab``l\\`e?{}` WHERE a = ''' \\ \\' ?{}' AND b = 'Jack'",
            'SELECT * FROM `tab``l\\`e?{}` WHERE a = """ \\ \\" ?{}" AND b = \'Jack\'',
            "UPDATE table SET a = 'foo', b = 'bar' WHERE c = 'baz'",
        ];

        if ($results !== $correct) {
            throw new Exception('Failure.');
        }
    }
}
