<?php

declare(strict_types=1);

namespace RaveSoft\ErDiagram;

use RaveSoft\ErDiagram\Graph\Graph;
use RaveSoft\ErDiagram\Graph\Layout\GraphLayoutInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'draw-io:export', description: 'Export doctrine metadata to Draw IO format')]
class DrawIoExportCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly DoctrineSchema $doctrineSchema,
        private readonly DrawIoRenderer $drawIoRenderer,
        private readonly GraphLayoutInterface $layout,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(name: 'filename', mode: InputArgument::OPTIONAL, description: 'Filename to render DrawIO.')
            ->addOption(name: 'manager', mode: InputOption::VALUE_REQUIRED, description: 'Entity manager name.')
            ->addOption(name: 'tables', mode: InputOption::VALUE_REQUIRED, description: 'Filter for table names, separated by comma.')
            ->addOption(name: 'deep', mode: InputOption::VALUE_REQUIRED, description: 'Recursive deep')
            ->addOption(name: 'bidirectional', mode: InputOption::VALUE_REQUIRED, description: 'Bidirectional graph traversal');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $managerName = $input->getOption('manager');
        $manager = $this->registry->getManager($managerName ?: null);
        $tables = $input->getOption('tables');
        $tables = $tables ? array_map('trim', explode(',', $tables)) : [];

        $entityList = $this->doctrineSchema->getEntityList($manager);
        if ($tables) {
            $maxLevel = filter_var($input->getOption('deep'), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            $bidirectional = filter_var($input->getOption('bidirectional'), FILTER_VALIDATE_BOOL);
            $entityList->filterByTables($tables, $maxLevel ?? ($bidirectional ? 2 : 10), $bidirectional);
        }

        $graph = new Graph();
        foreach ($entityList->getEntities() as $entity) {
            $graph->addNode($entity->uniqId);
        }
        foreach ($entityList->getEntities() as $entity) {
            foreach ($entity->relations as $relation) {
                if (null !== $relation->target) {
                    $graph->addEdge($entity->uniqId, $relation->target->uniqId);
                }
            }
        }

        $size = $this->layout->doLayout($graph);

        $content = $this->drawIoRenderer->renderMxFile($entityList->getEntities(), $graph, $size);

        $filename = $input->getArgument('filename');
        if ($filename) {
            file_put_contents($filename, $content);
        } else {
            echo $content;
        }

        return self::SUCCESS;
    }
}
