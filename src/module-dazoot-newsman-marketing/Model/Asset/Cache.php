<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsmanmarketing\Model\Asset;

use Dazoot\Newsmanmarketing\Model\Config;
use Magento\Framework\App\Cache\Type\Block as BlockCache;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\Serializer\Json;

class Cache
{
    /**
     * Relative work path in var directory
     */
    public const RELATIVE_WORK_PATH = 'dazoot/newsmanmarketing';

    /**
     * Tag for API key in required file patterns
     */
    public const API_KEY_TAG = '{{api_key}}';

    /**
     * File expire time.
     * It should be synchronized with Newsman server max-age in request header.
     */
    public const FILE_LIFETIME = 86400;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var array
     */
    protected $allowedFileExtensions;

    /**
     * @param Filesystem $filesystem
     * @param Json $json
     * @param Config $config
     * @param TypeListInterface $cacheTypeList
     * @param array $allowedFileExtensions
     */
    public function __construct(
        Filesystem $filesystem,
        Json $json,
        Config $config,
        TypeListInterface $cacheTypeList,
        array $allowedFileExtensions
    ) {
        $this->filesystem = $filesystem;
        $this->json = $json;
        $this->config = $config;
        $this->cacheTypeList = $cacheTypeList;
        $this->allowedFileExtensions = $allowedFileExtensions;
    }

    /**
     * @param string $path
     * @return false|DataObject
     * @throws FileSystemException
     */
    public function load($path)
    {
        $filename = $this->getFilename($path);
        $headersFilename = '__headers_' . $filename;

        $read = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $relativePath = $this->getRelativePath($path);
        if (!$read->isFile($relativePath . '/' . $filename)) {
            return false;
        }
        if (!$read->isFile($relativePath . '/' . $headersFilename)) {
            return false;
        }

        $stat = $read->stat($relativePath . '/' . $filename);
        if ($this->isFileExpired($stat)) {
            return false;
        }

        $content = $read->readFile($relativePath . '/' . $filename);
        $headersStr = $read->readFile($relativePath . '/' . $headersFilename);
        if (empty($headersStr)) {
            return false;
        }
        $headers = $this->json->unserialize($headersStr);

        return new DataObject([
            'content' => $content,
            'headers' => $headers
        ]);
    }

    /**
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    public function isCached($path)
    {
        return $this->load($path) !== false;
    }

    /**
     * @param array $stat
     * @return bool
     */
    public function isFileExpired($stat)
    {
        return $stat['mtime'] + $this->getFileLifetime() < time();
    }

    /**
     * @param string $path
     * @param string $content
     * @param array $cacheHeaders
     * @return void
     * @throws FileSystemException
     */
    public function save($path, $content, $cacheHeaders)
    {
        if (empty($content)) {
            return;
        }

        $filename = $this->getFilename($path);
        if ($filename === false) {
            return;
        }
        $headersFilename = '__headers_' . $filename;

        $write = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $relativePath = $this->getRelativePath($path);
        if (!$write->isDirectory($relativePath)) {
            $write->create($relativePath);
        }

        $write->writeFile($relativePath . '/' . $filename, $content);

        $cacheHeaders = $this->modifyExpireHeaders($cacheHeaders);
        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone('GMT'));
        $cacheHeaders['date'] = $now->format(\DateTime::RFC7231);
        $write->writeFile($relativePath . '/' . $headersFilename, $this->json->serialize($cacheHeaders));

        $filenameScriptUrl = $this->getFilename($this->config->getScriptUrl());
        // Invalidate block for main JS file (t.js)
        if ($filenameScriptUrl === $filename) {
            $this->cleanScriptBlockCache();
        }
    }

    /**
     * @return bool
     * @throws FileSystemException
     */
    public function isAllRequiredFilesCached()
    {
        try {
            $apiKey = $this->config->getUaId();
            $patterns = $this->config->getRequiredFilePatterns();
            foreach ($patterns as $pattern) {
                $relativePath = str_replace(self::API_KEY_TAG, $apiKey, $pattern);
                if (!$this->isCached($relativePath)) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @return void
     * @throws FileSystemException
     */
    public function clean()
    {
        $write = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $relativePath = $this->getRelativeWorkPath();
        if ($write->isDirectory($relativePath)) {
            $list = $write->read($relativePath);
            if (!empty($list)) {
                $write->delete($relativePath);
                $this->cleanScriptBlockCache();
            }
        }
    }

    /**
     * @return void
     */
    public function cleanScriptBlockCache()
    {
        $this->cacheTypeList->cleanType(BlockCache::TYPE_IDENTIFIER);
        $this->cacheTypeList->cleanType('full_page');
    }

    /**
     * @param array $headers
     * @return array
     */
    public function modifyExpireHeaders($headers)
    {
        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone('GMT'));

        if (isset($headers['date'])) {
            unset($headers['date']);
        }

        if (isset($headers['last-modified'])) {
            $headers['last-modified'] = $now->format(\DateTime::RFC7231);
        }

        if (isset($headers['expires'])) {
            $expires = clone $now;
            $expires->modify('+' . $this->getFileLifetime() . ' seconds');
            $headers['expires'] = $expires->format(\DateTime::RFC7231);
        }

        if (isset($headers['cache-control'])) {
            $headers['cache-control'] = 'max-age=' . $this->getFileLifetime();
        }

        return $headers;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getRelativePath($path)
    {
        $fileRelativePath = trim($this->getFileRelativePath($path), '/');
        $relativePath = $this->getRelativeWorkPath();
        if (!empty($fileRelativePath)) {
            $relativePath .= '/' . $fileRelativePath;
        }
        return $relativePath;
    }

    /**
     * @param string $path
     * @return string|false
     */
    public function getFilename($path)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $pathInfo = pathinfo($path);
        return $pathInfo['filename'] . '.' . $pathInfo['extension'];
    }

    /**
     * @param string $path
     * @return string
     */
    public function getFileRelativePath($path)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $info = pathinfo($path);
        if (isset($info['dirname'])) {
            return $info['dirname'];
        }
        return '';
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isValidPath($path)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $pathInfo = pathinfo($path);
        foreach ($this->allowedFileExtensions as $fileExtension) {
            if ($pathInfo['extension'] === $fileExtension) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getRelativeWorkPath()
    {
        return self::RELATIVE_WORK_PATH;
    }

    /**
     * @return int
     */
    public function getFileLifetime()
    {
        return self::FILE_LIFETIME;
    }
}
