<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Model\Http;

use Magento\Framework\App\Cache\Type\Collection as CacheTypeCollection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\Dir\Reader as ModuleReader;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Get MIME by file extension service
 *
 * @see https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
 * @see https://www.php.net/manual/en/function.mime-content-type.php#107798
 */
class GetMimeByFileExtension
{
    /**
     * Filename containing mime types
     */
    public const MIME_TYPE_FILENAME = 'mime-types.txt';

    /**
     * Cache tag used to save mime types
     */
    public const CACHE_TAG = 'DZ_NM_MT';

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ModuleReader
     */
    protected $moduleReader;

    /**
     * @var CacheTypeCollection
     */
    protected $collectionCache;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var array
     */
    protected $mimeTypes;

    /**
     * @param Filesystem $filesystem
     * @param ModuleReader $moduleReader
     * @param CacheTypeCollection $collectionCache
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Filesystem $filesystem,
        ModuleReader $moduleReader,
        CacheTypeCollection $collectionCache,
        SerializerInterface $serializer
    ) {
        $this->filesystem = $filesystem;
        $this->moduleReader = $moduleReader;
        $this->collectionCache = $collectionCache;
        $this->serializer = $serializer;
    }

    /**
     * Retrieve the MIME type for a given file extension.
     *
     * @param string $extension
     * @return string|false
     * @throws FileSystemException
     */
    public function execute($extension)
    {
        if (empty($extension)) {
            return '';
        }

        if ($this->mimeTypes !== null) {
            if (isset($this->mimeTypes[$extension])) {
                return (string) $this->mimeTypes[$extension];
            }
            return '';
        }

        $cacheId = $this->getCacheId();
        $data = $this->collectionCache->load($cacheId);
        if (!empty($data)) {
            $this->mimeTypes = $this->serializer->unserialize($data);
            if (empty($this->mimeTypes)) {
                $this->mimeTypes = [];
                return '';
            }

            if (isset($this->mimeTypes[$extension])) {
                return (string) $this->mimeTypes[$extension];
            }
            return '';
        }

        $path = $this->moduleReader->getModuleDir('', 'Dazoot_Newsmanmarketing') . '/data/' .
            $this->getMimeTypeFilename();
        $readFilesystem = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);
        if (!$readFilesystem->isFile($path)) {
            return '';
        }

        $contents = $readFilesystem->readFile($path);
        if (empty($contents)) {
            $this->mimeTypes = [];
            $this->saveCache($this->mimeTypes);
            return '';
        }
        $this->mimeTypes = $this->generateMimeTypes($contents);
        if (empty($this->mimeTypes)) {
            $this->mimeTypes = [];
            $this->saveCache($this->mimeTypes);
            return '';
        }

        $this->saveCache($this->mimeTypes);

        if (isset($this->mimeTypes[$extension])) {
            return (string) $this->mimeTypes[$extension];
        }

        return '';
    }

    /**
     * Get the mime type filename.
     *
     * @return string
     */
    public function getMimeTypeFilename()
    {
        return self::MIME_TYPE_FILENAME;
    }

    /**
     * Generate mime types from file contents.
     *
     * @see https://www.php.net/manual/en/function.mime-content-type.php#107798
     *
     * @param string $contents
     * @return array
     */
    public function generateMimeTypes($contents)
    {
        $return = [];

        $arr = explode("\n", $contents);
        if (empty($arr)) {
            return [];
        }

        foreach ($arr as $line) {
            if (isset($line[0]) && $line[0] !== '#' && preg_match_all('#([^\s]+)#', $line, $matches)) {
                if (isset($matches[1]) && ($count = count($matches[1])) > 1) {
                    for ($i = 1; $i < $count; $i++) {
                        $return[$matches[1][$i]] = $matches[1][0];
                    }
                }
            }
        }
        return $return;
    }

    /**
     * Save mime types into collection cache
     *
     * @param array $mimeTypes
     * @return void
     */
    public function saveCache($mimeTypes)
    {
        $this->collectionCache->save(
            $this->serializer->serialize($mimeTypes),
            $this->getCacheId(),
            $this->getCacheTags(),
            $this->getCacheLifetime()
        );
    }

    /**
     * Get the cache identifier for mime types.
     *
     * @return string
     */
    public function getCacheId()
    {
        return 'dazoot_nzm_mark_mime_types';
    }

    /**
     * Get the cache lifetime for mime types.
     *
     * @return int
     */
    public function getCacheLifetime()
    {
        return 999999;
    }

    /**
     * Get the cache tags for mime types.
     *
     * @return array
     */
    public function getCacheTags()
    {
        return [
            self::CACHE_TAG,
            CacheTypeCollection::CACHE_TAG
        ];
    }
}
