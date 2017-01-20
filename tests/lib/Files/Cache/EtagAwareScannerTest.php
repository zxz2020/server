<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Files\Cache;

use OC\Files\Cache\CacheEntry;
use OC\Files\Cache\EtagAwareScanner;
use OC\Files\Cache\Scanner;
use OC\Files\Storage\Storage;
use OCP\Files\Cache\ICache;

/**
 * Class ScannerTest
 *
 * @group DB
 *
 * @package Test\Files\Cache
 */
class EtagAwareScannerTest extends \Test\TestCase {
	/** @var  ICache|\PHPUnit_Framework_MockObject_MockObject */
	private $cache;

	/** @var  Storage|Storage|\PHPUnit_Framework_MockObject_MockObject */
	private $storage;

	/** @var  EtagAwareScanner */
	private $scanner;

	public function setUp() {
		parent::setUp();

		$this->cache = $this->createMock(ICache::class);
		$this->storage = $this->createMock(Storage::class);

		$this->storage->expects($this->any())
			->method('getCache')
			->willReturn($this->cache);

		$this->scanner = new EtagAwareScanner($this->storage);
	}

	public function testScanWithDifferentEtag() {
		$this->storage->expects($this->any())
			->method('getMetaData')
			->willReturn([
				'mimetype' => 'text/plain',
				'mtime' => 100,
				'size' => 10,
				'etag' => 'new',
				'storage_mtime' => 100,
				'permissions' => 31
			]);

		$cacheData = new CacheEntry([
			'path' => '/foo',
			'fileid' => 2,
			'parent' => 1,
			'mimetype' => 'text/plain',
			'mtime' => 90,
			'size' => 5,
			'etag' => 'old',
			'storage_mtime' => 90,
			'permissions' => 31
		]);

		$this->cache->expects($this->once())
			->method('update')
			->with(2, [
				'mtime' => 100,
				'size' => 10,
				'etag' => 'new',
				'storage_mtime' => 100,
				'checksum' => ''
			]);

		$this->scanner->scanFile('/foo', Scanner::REUSE_ETAG | Scanner::REUSE_SIZE, 1, $cacheData, false);
	}

	public function testScanWithSameEtag() {
		$this->storage->expects($this->any())
			->method('getMetaData')
			->willReturn([
				'mimetype' => 'text/plain',
				'mtime' => 100,
				'size' => 10,
				'etag' => 'new',
				'storage_mtime' => 100,
				'permissions' => 31
			]);

		$cacheData = new CacheEntry([
			'path' => '/foo',
			'fileid' => 2,
			'parent' => 1,
			'mimetype' => 'text/plain',
			'mtime' => 90,
			'size' => 5,
			'etag' => 'new',
			'storage_mtime' => 90,
			'permissions' => 31
		]);

		$this->cache->expects($this->never())
			->method('update');

		$this->scanner->scanFile('/foo', Scanner::REUSE_ETAG | Scanner::REUSE_SIZE, 1, $cacheData, false);
	}
}
