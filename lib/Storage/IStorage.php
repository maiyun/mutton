<?php
/**
 * User: JianSuoQiYue
 * Date: 2018/12/15 23::54
 * Last: 2018-12-15 23:59:40
 */

declare(strict_types = 1);

namespace lib\Storage;

interface IStorage {

    public function __construct(array $opt = []);

    public function putFile(string $path, $content, bool $gzip = false): array;

    public function uploadFile(string $fromPath, string $toPath): bool;

    public function deleteFile(string $path): void;

    public function doesExist(string $path): bool;

    public function getSignature(array $opt = []): array;

    /**
     * @return array|bool
     */
    public function callback();

}

