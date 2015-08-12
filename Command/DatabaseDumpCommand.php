<?php

namespace Ctrl\Common\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseDumpCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ctrl:db:dump')
            ->setDescription('dump the configured mysql database')
            ->addArgument(
                'output',
                InputArgument::REQUIRED,
                'Location of the generated dump file'
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
            'MYSQL_PWD=%s mysqldump -h%s -u%s %s --disable-keys --add-drop-table --add-drop-trigger --no-tablespaces --create-options --no-create-db > %s',
            escapeshellarg($c->getParameter('database_password')),
            escapeshellarg($c->getParameter('database_host')),
            escapeshellarg($c->getParameter('database_user')),
            escapeshellarg($c->getParameter('database_name')),
            escapeshellarg($input->getArgument('output'))
        ));

        $output->writeln(sprintf('database dump written in %s', $input->getArgument('output')));
    }
}