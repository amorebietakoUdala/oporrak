<?php

namespace App\Command;

use App\Entity\Status;
use App\Repository\EventRepository;
use App\Repository\StatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AutomaticApprovalCommand extends Command
{
    protected static $defaultName = 'app:automatic-approval';
    protected static $defaultDescription = 'This command automatically approves events asked more than the number of days specified.';

    private EventRepository $repo;
    private EntityManagerInterface $em;
    private StatusRepository $statusRepo;
    private int $days = 0;

    public function __construct(EventRepository $repo, StatusRepository $statusRepo, EntityManagerInterface $em)
    {
        $this->repo = $repo;
        $this->statusRepo = $statusRepo;
        $this->em = $em;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('days', InputArgument::OPTIONAL, 'How many days had to be passed to approve an asked event?', 15)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $daysArgument = $input->getArgument('days');
        if ($daysArgument && intval($daysArgument) !== null && $daysArgument > 0) {
            $this->days = $daysArgument;
        } 
        $events = $this->repo->findAllReservedAndAskedDaysAgo($this->days);
        $beforeDate = (new \DateTime())->sub(new \DateInterval('P'.$this->days.'D'));
        $io->info('Approving events before: '.$beforeDate->format('Y-m-d'));
        $io->info('Total events to update: '.count($events));
        $approvedStatus = $this->statusRepo->find(Status::APPROVED);
        try  {
            foreach ($events as $event) {
                $event->setStatus($approvedStatus);
                $this->em->persist($event);
            }
            if ($input->getOption('dry-run')) {
                $io->info('Rolled back because of the dry-run option');
                $this->em->clear();
            } else {
                $this->em->flush();
            }
        } catch( \Exception $e ) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
        $io->success('Successfully updated: '.count($events));
        return Command::SUCCESS;
    }
}
