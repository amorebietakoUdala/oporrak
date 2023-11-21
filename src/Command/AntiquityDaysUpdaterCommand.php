<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AntiquityDaysUpdaterCommand extends Command
{
    protected static $defaultName = 'app:antiquity-days-updater';
    protected static $defaultDescription = 'This command updates the antiquity days for all activated users. Is intended to be run on 1st of January every year through a cron job.';

    private UserRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(UserRepository $repo, EntityManagerInterface $em)
    {
        $this->repo = $repo;
        $this->em = $em;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $users = $this->repo->findBy([
            'activated' => true,
        ]);
        $io->info('Total users to update: '.count($users));
        try {
            foreach ($users as $user) {
                if ( null !== $user->getYearsWorked()) {
                    $user->setYearsWorked($user->getYearsWorked()+1);
                } else {
                    $user->setYearsWorked(1);
                }
                $this->em->persist($user);
            }
            if ($input->getOption('dry-run')) {
                $this->em->clear();
            } else {
                $this->em->flush();
            }
        } catch( \Exception $e ) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
        $io->success('Successfully updated: '.count($users));
        return Command::SUCCESS;
    }
}
