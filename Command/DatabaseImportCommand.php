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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $c = $this->getContainer();

        if ($c->getParameter('database_driver') !== 'pdo_mysql') {
            throw new \RuntimeException('This command only works for mysql databases');
        }

        exec(sprintf(
            'MYSQL_PWD=%s mysql -h%s -u%s %s < %s',
            escapeshellarg($c->getParameter('database_password')),
            escapeshellarg($c->getParameter('database_host')),
            escapeshellarg($c->getParameter('database_user')),
            escapeshellarg($c->getParameter('database_name')),
            escapeshellarg($input->getArgument('file'))
        ));

        $output->writeln(sprintf('database dump loaded: %s', $input->getArgument('file')));
    }
}