<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Task;

use Exception;
use Infrangible\Core\Helper\Registry;
use Infrangible\SimpleMail\Model\MailFactory;
use Infrangible\Task\Helper\Data;
use Infrangible\Task\Helper\Values;
use Infrangible\Task\Model\ResourceModel\RunFactory as RunResourceFactory;
use Infrangible\Task\Model\RunFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * Base of tasks that process files: the settings path, archive_path and error_path of the task name the directories
 * of the files, and of the processed files, which are moved there (copied in test mode).
 */
abstract class Files extends Base
{
    /**
     * @param File $file
     * @param Registry $registryHelper
     * @param Data $helper
     * @param LoggerInterface $logging
     * @param DirectoryList $directoryList
     * @param RunFactory $runFactory
     * @param RunResourceFactory $runResourceFactory
     * @param MailFactory $mailFactory
     * @param \Infrangible\Core\Helper\Files $coreFilesHelper
     * @param Values $values
     */
    public function __construct(
        File $file,
        Registry $registryHelper,
        Data $helper,
        LoggerInterface $logging,
        DirectoryList $directoryList,
        RunFactory $runFactory,
        RunResourceFactory $runResourceFactory,
        MailFactory $mailFactory,
        protected \Infrangible\Core\Helper\Files $coreFilesHelper,
        protected Values $values
    ) {
        parent::__construct(
            $file,
            $registryHelper,
            $helper,
            $logging,
            $directoryList,
            $runFactory,
            $runResourceFactory,
            $mailFactory
        );
    }

    /**
     * Checks that the paths are configured.
     *
     * @return void
     * @throws Exception
     */
    protected function validatePaths(): void
    {
        if ($this->getImportPath() === '') {
            throw new Exception('No path to import specified');
        }

        if ($this->getArchivePath() === '') {
            throw new Exception('No archive path specified');
        }

        if ($this->getErrorPath() === '') {
            throw new Exception('No error path specified');
        }
    }

    /**
     * Returns the path of the files.
     *
     * @return string
     */
    protected function getImportPath(): string
    {
        return $this->values->string($this->getTaskSetting('path'));
    }

    /**
     * Returns the path of the processed files.
     *
     * @return string
     */
    protected function getArchivePath(): string
    {
        return $this->values->string($this->getTaskSetting('archive_path'));
    }

    /**
     * Returns the path of the files that could not be processed.
     *
     * @return string
     */
    protected function getErrorPath(): string
    {
        return $this->values->string($this->getTaskSetting('error_path'));
    }

    /**
     * Returns whether a run without files sends no summary.
     *
     * @return bool
     */
    protected function isSuppressEmptyMails(): bool
    {
        return (bool)$this->getTaskSetting('suppress_empty_mails', false, true);
    }

    /**
     * Moves a processed file into the archive path, or the error path if it failed.
     *
     * In test mode, or if the file is kept, it is copied.
     *
     * @param string $importedFile
     * @param bool $result
     * @param bool $keepFile
     *
     * @return void
     * @throws Exception
     * @throws FileSystemException
     */
    protected function archiveImportFile(string $importedFile, bool $result, bool $keepFile = false): void
    {
        $archivePath = $this->coreFilesHelper->determineFilePath(
            $result ? $this->getArchivePath() : $this->getErrorPath(),
            null,
            true
        );
        $archiveFileName = $this->coreFilesHelper->determineFilePath(
            // phpcs:ignore Magento2.Functions.DiscouragedFunction -- the name of the file, not a path in Magento
            $this->getArchiveFileName(basename($importedFile)),
            $archivePath
        );

        $this->logging->debug(sprintf('Moving import file: %s to archive file: %s', $importedFile, $archiveFileName));

        if ($this->isTest() || $keepFile) {
            $this->file->copy($importedFile, $archiveFileName);
        } else {
            $this->file->rename($importedFile, $archiveFileName);
        }
    }

    /**
     * Returns the name of a processed file in the archive.
     *
     * @param string $importFileName
     *
     * @return string
     */
    abstract protected function getArchiveFileName(string $importFileName): string;
}
