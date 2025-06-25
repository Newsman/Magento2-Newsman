<?php
/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */
namespace Dazoot\Newsman\Console\Command;

use Dazoot\Newsman\Logger\Logger;
use Dazoot\Newsman\Model\Newsletter\Bulk\Export\Consumer;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to export newsletter subscribers
 * @see \Dazoot\Newsman\Cron\ExportSubscribers
 */
class ExportSubscriberCommand extends Command
{
    /**
     * Name of input option
     */
    public const INPUT_KEY_LIST_ID = 'list_id';

    /**
     * Name of input option
     */
    public const INPUT_KEY_STORE_ID = 'store_id';

    /**
     * Name of input option
     */
    public const INPUT_KEY_CHUNK_SIZE = 'chunk_size';

    /**
     * Name of input option
     */
    public const INPUT_KEY_STEP = 'step';

    /**
     * @var Consumer
     */
    protected $exportSubscriberConsumer;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Consumer $exportSubscriberConsumer
     * @param Logger $logger
     */
    public function __construct(
        Consumer $exportSubscriberConsumer,
        Logger $logger
    ) {
        $this->exportSubscriberConsumer = $exportSubscriberConsumer;
        $this->logger = $logger;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::INPUT_KEY_LIST_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'List ID'
            ),
            new InputOption(
                self::INPUT_KEY_STORE_ID,
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Store Id(s). Use --store_id=1 --store_id=2 ... to add multiple store IDs'
            ),
            new InputOption(
                self::INPUT_KEY_CHUNK_SIZE,
                null,
                InputOption::VALUE_REQUIRED,
                'Chunk size (page size in collection)'
            ),
            new InputOption(
                self::INPUT_KEY_STEP,
                null,
                InputOption::VALUE_REQUIRED,
                'Step (current page in collection)'
            )
        ];
        $this->setName('dazoot:newsman:export:subscribers')
            ->setDescription('Export newsletter subscribers to Newsman')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * Export subscribers to Newsman
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = [
            'list_id' => $input->getOption(self::INPUT_KEY_LIST_ID),
            'store_ids' => $input->getOption(self::INPUT_KEY_STORE_ID),
            'chunk_size' => $input->getOption(self::INPUT_KEY_CHUNK_SIZE),
            'step' => $input->getOption(self::INPUT_KEY_STEP)
        ];

        try {
            $count = $this->exportSubscriberConsumer->execute($data);
            $output->writeln('<info>' . 'Exported successfully ' . $count .  ' subscribers</info>');
        } catch (\Exception $e) {
            $this->logger->error($e);
            $output->writeln('<error>' . $e->getMessage() .  '</error>');

            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
