<?php

namespace Ctrl\Common\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption(
                'gzip',
                null,
                InputOption::VALUE_NONE,
                'If set, the database dump will be gzipped'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $c = $this->getContainer();

        if ($c->getParameter('database_driver') !== 'pdo_mysql') {
            throw new \RuntimeException('This command only works for mysql databases');
        }

        $outputfile = $input->getArgument('output');
        $gzip = '';

        if ($input->getOption('gzip')) {
            $gzip = ' | gzip';

            if (substr($outputfile, -3) !== '.gz') {
                $outputfile = $outputfile . '.gz';
            }
        }

        exec(sprintf(
            'mysqldump -h%s -u%s -p%s --disable-keys --add-drop-table --no-tablespaces --create-options --no-create-db %s %s > %s',
            escapeshellarg($c->getParameter('database_host')),
            escapeshellarg($c->getParameter('database_user')),
            escapeshellarg($c->getParameter('database_password')),
            escapeshellarg($c->getParameter('database_name')),
            $gzip,
            escapeshellarg($outputfile)
        ));

        $output->writeln(sprintf('database dump written in %s', $outputfile));
    }
}
