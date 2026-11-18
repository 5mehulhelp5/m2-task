<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Task\Task;

use Exception;
use Throwable;
use UnexpectedValueException;

/**
 * Base of tasks that import the files of a path one by one, optionally only those that match the regular expression of
 * the setting file_pattern, and archive them afterwards.
 */
abstract class File extends Files
{
    /** @var string[]|null */
    private ?array $importFiles = null;

    /**
     * @var array
     * @phpstan-var array<string, bool>
     */
    private array $importedFiles = [];

    /** @var bool */
    private bool $emptyRun = true;

    /**
     * Checks the paths.
     *
     * @return void
     * @throws Exception
     */
    protected function prepare(): void
    {
        $this->validatePaths();
    }

    /**
     * Imports the files.
     *
     * @return bool
     * @throws Exception
     */
    protected function runTask(): bool
    {
        $importFiles = $this->determineImportFiles(false);
        $fileCount = count($importFiles);
        $success = true;

        if ($fileCount === 0) {
            $this->setProhibitSummarySending(self::SUMMARY_TYPE_ALL, $this->isSuppressEmptyMails());
            $this->logging->info(__('Nothing to import')->render());

            return true;
        }

        $this->setEmptyRun(false);

        foreach ($importFiles as $index => $importFile) {
            $this->logging->debug(sprintf('Importing file %d/%d: %s', $index + 1, $fileCount, $importFile));

            try {
                $this->importedFiles[ $importFile ] = $this->importFile($importFile);

                $this->logging->debug(sprintf('Successfully finished import of file: %s', $importFile));
            } catch (Throwable $exception) {
                $this->logging->debug(
                    sprintf('Could not finish import of file: %s because: %s', $importFile, $exception->getMessage())
                );
                $this->logging->error($exception);

                $this->importedFiles[ $importFile ] = false;
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Archives the imported files.
     *
     * @param bool $success
     *
     * @return void
     * @throws Exception
     */
    protected function dismantle(bool $success): void
    {
        $this->logging->info(sprintf('Archiving %d imported file(s)', count($this->importedFiles)));

        foreach ($this->importedFiles as $importedFile => $result) {
            $this->archiveImportFile($importedFile, $result);
        }
    }

    /**
     * Returns the files to import.
     *
     * @param bool $quiet
     *
     * @return string[]
     * @throws Exception
     */
    protected function determineImportFiles(bool $quiet = true): array
    {
        if ($this->importFiles === null) {
            $path = $this->getImportPath();

            if (! $quiet) {
                $this->logging->info(sprintf('Checking for files to import in path: %s', $path));
            }

            $importFiles = $this->coreFilesHelper->determineFilesFromFilePath($path);
            $filePattern = $this->getFilePattern();

            if ($filePattern !== '') {
                if (! $quiet) {
                    $this->logging->info(
                        sprintf('Checking files to import in path: %s for pattern: %s', $path, $filePattern)
                    );
                }

                $expression = sprintf('/%s/', str_replace('/', '\/', $filePattern));

                if (preg_match($expression, '') === false) {
                    throw new UnexpectedValueException(sprintf('Invalid file pattern: %s', $filePattern));
                }

                $importFiles = array_values(
                    array_filter(
                        $importFiles,
                        fn(string $importFile): bool => preg_match($expression, $importFile) === 1
                    )
                );
            }

            $this->importFiles = $importFiles;
        }

        if (! $quiet) {
            $this->logging->info(sprintf('Found %d file(s) to import', count($this->importFiles)));
        }

        return $this->importFiles;
    }

    /**
     * Returns whether there are files to import.
     *
     * @return bool
     * @throws Exception
     */
    protected function hasImportFiles(): bool
    {
        return count($this->determineImportFiles()) > 0;
    }

    /**
     * Imports a file and returns whether it was successful.
     *
     * @param string $importFile
     *
     * @return bool
     * @throws Exception
     */
    abstract protected function importFile(string $importFile): bool;

    /**
     * Returns the regular expression for the names of the files to import.
     *
     * @return string
     */
    protected function getFilePattern(): string
    {
        return $this->values->string($this->getTaskSetting('file_pattern'));
    }

    /**
     * Returns whether there was nothing to import.
     *
     * @return bool
     */
    public function isEmptyRun(): bool
    {
        return $this->emptyRun;
    }

    /**
     * Sets whether there was nothing to import.
     *
     * @param bool $emptyRun
     *
     * @return void
     */
    public function setEmptyRun(bool $emptyRun): void
    {
        $this->emptyRun = $emptyRun;
    }
}
