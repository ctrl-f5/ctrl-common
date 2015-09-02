<?php

namespace Ctrl\Common\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ctrl:db:import')
            ->setDescription('import a dump into the configured mysql database')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'Location of the dump to load'
            )
            ->addOption(
                'gzip',
                null,
                InputOption::VALUE_NONE,
                'Set this if the database dump is gzipped'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $c = $this->getContainer();

        if ($c->getParameter('database_driver') !== 'pdo_mysql') {
            throw new \RuntimeException('This command only works for mysql databases');
        }

        $file = $input->getArgument('file');

        if ($input->getOption('gzip')) {
            $file = "`gzip -d -c $file`";
        }

        exec(sprintf(
            'MYSQL_PWD=%s mysql -h%s -u%s %s < `gzip -d -c %s`',
            escapeshellarg($c->getParameter('database_password')),
            escapeshellarg($c->getParameter('database_host')),
            escapeshellarg($c->getParameter('database_user')),
            escapeshellarg($c->getParameter('database_name')),
            escapeshellarg($file)
        ));

        $output->writeln(sprintf('database dump loaded: %s', $input->getArgument('file')));
    }
}
