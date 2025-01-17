<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function assert;
use function count;
use function sprintf;

/**
 * Show information about mapped documents
 *
 * @internal since version 4.7.0
 */
class InfoDoctrineODMCommand extends DoctrineODMCommand
{
    /** @return void */
    protected function configure()
    {
        $this
            ->setName('doctrine:mongodb:mapping:info')
            ->addOption('dm', null, InputOption::VALUE_OPTIONAL, 'The document manager to use for this command.')
            ->setDescription('Show basic information about all mapped documents.')
            ->setHelp(<<<'EOT'
The <info>doctrine:mongodb:mapping:info</info> shows basic information about which
documents exist and possibly if their mapping information contains errors or not.

  <info>./bin/console doctrine:mongodb:mapping:info</info>

If you are using multiple document managers you can pick your choice with the <info>--dm</info> option:

  <info>./bin/console doctrine:mongodb:mapping:info --dm=default</info>
EOT
        );
    }

    /** @return int */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $documentManagerName = $input->hasOption('dm') ? $input->getOption('dm') : $this->getManagerRegistry()->getDefaultManagerName();

        $documentManager = $this->getManagerRegistry()->getManager($documentManagerName);
        assert($documentManager instanceof DocumentManager);

        $documentClassNames = $documentManager->getConfiguration()
                                          ->getMetadataDriverImpl()
                                          ->getAllClassNames();

        if (! $documentClassNames) {
            throw new Exception(
                'You do not have any mapped Doctrine MongoDB ODM documents for any of your bundles. ' .
                'Create a class inside the Document namespace of any of your bundles and provide ' .
                'mapping information for it with Annotations directly in the classes doc blocks ' .
                'or with XML in your bundles Resources/config/doctrine/metadata/mongodb directory.'
            );
        }

        $output->write(sprintf(
            "Found <info>%d</info> documents mapped in document manager <info>%s</info>:\n",
            count($documentClassNames),
            $documentManagerName
        ), true);

        foreach ($documentClassNames as $documentClassName) {
            try {
                $cm = $documentManager->getClassMetadata($documentClassName);
                $output->write('<info>[OK]</info>   ' . $documentClassName, true);
            } catch (Throwable $e) {
                $output->write('<error>[FAIL]</error> ' . $documentClassName, true);
                $output->write('<comment>' . $e->getMessage() . '</comment>', true);
                $output->write('', true);
            }
        }

        return 0;
    }
}
