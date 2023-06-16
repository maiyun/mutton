<?php
/*
// --- MySQL ---
CREATE TABLE `m_test` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `token` CHAR(16) NOT NULL COLLATE 'ascii_bin',
  `point` POINT NOT NULL,
  `time_add` BIGINT NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `token` (`token`) USING BTREE,
	INDEX `time_add` (`time_add`) USING BTREE
) ENGINE=InnoDB COLLATE=utf8mb4_general_ci;
// --- SQLite ---
CREATE TABLE m_test (
  `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  `token` CHAR (16) UNIQUE NOT NULL,
  `point` VARCHAR (16) NOT NULL,
  `time_add` BIGINT NOT NULL
);
CREATE INDEX `time_add` ON `m_test` (`time_add`);
*/
declare(strict_types = 1);

namespace mod;

class Test extends Mod {

    protected static $_table = 'test';

    protected static $_primary = 'id';

    protected static $_key = 'token';

    public $id, $token, $point, $time_add;

    protected static $_astext = ['point'];

    protected function _keyGenerator(): string {
        return 'test_' . rand(0, 5);
    }

}

