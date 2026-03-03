<?php

declare(strict_types=1);

/*
 * This file is part of the package mfd/fal-duplicate-checker.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Mfd\Fal\DuplicateChecker\Tests\Functional;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for the duplicate-detection query used in
 * ElementInformationController::mainAction().
 *
 * The controller runs:
 *   SELECT * FROM sys_file WHERE sha1 = :sha1 AND uid != :uid
 *
 * These tests exercise that exact query against a real SQLite database
 * to verify the duplicate-detection logic is correct.
 */
final class DuplicateDetectionTest extends FunctionalTestCase
{
    /**
     * Runs the same query the controller uses.
     *
     * @return list<array<string,mixed>>
     */
    private function findDuplicates(string $sha1, int $excludeUid): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file');

        return $queryBuilder
            ->select('*')
            ->from('sys_file')
            ->where(
                $queryBuilder->expr()->eq(
                    'sha1',
                    $queryBuilder->createNamedParameter($sha1)
                )
            )
            ->andWhere(
                $queryBuilder->expr()->neq(
                    'uid',
                    $queryBuilder->createNamedParameter($excludeUid)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * uid 1 and uid 2 share the same sha1; querying for uid 1's hash
     * while excluding uid 1 must return exactly uid 2.
     */
    public function testFindsDuplicateFilesWithMatchingSha1(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_file.csv');

        $duplicates = $this->findDuplicates(
            'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            1
        );

        self::assertCount(1, $duplicates);
        self::assertSame(2, (int)$duplicates[0]['uid']);
    }

    /**
     * uid 3 has a unique sha1; querying for that hash while excluding
     * uid 3 must return an empty result set.
     */
    public function testReturnsEmptyArrayWhenNoDuplicatesExist(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_file.csv');

        $duplicates = $this->findDuplicates(
            'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
            3
        );

        self::assertCount(0, $duplicates);
    }
}
