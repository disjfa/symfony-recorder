<?php

namespace App\Command;

use App\Repository\TaskRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:task:list',
    description: 'List the last 10 tasks',
)]
class ListTasksCommand extends Command
{
    public function __construct(private TaskRepository $taskRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $tasks = $this->taskRepository->findBy([], ['createdAt' => 'DESC'], 10);

        if (empty($tasks)) {
            $io->warning('No tasks found');

            return Command::SUCCESS;
        }

        $io->title('Last 10 Tasks');

        $rows = [];
        foreach ($tasks as $task) {
            $rows[] = [
                $task->getId(),
                $task->getTitle(),
                $task->getStatus(),
                $task->getPriority(),
                $task->getDueDate()?->format('Y-m-d H:i'),
                $task->getCreatedAt()?->format('Y-m-d H:i') ?? 'N/A',
            ];
        }

        $io->table(
            ['ID', 'Title', 'Status', 'Priority', 'Due Date', 'Created At'],
            $rows
        );

        $io->success(sprintf('Found %d task(s)', count($tasks)));

        return Command::SUCCESS;
    }
}
