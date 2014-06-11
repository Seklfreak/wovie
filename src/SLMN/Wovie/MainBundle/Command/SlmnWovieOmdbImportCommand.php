<?php

namespace SLMN\Wovie\MainBundle\Command;

use SLMN\Wovie\MainBundle\Entity\Omdb;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SlmnWovieOmdbImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('slmn:wovie:omdb:import')
            ->setDescription('Imports the omdb.txt from http://www.omdbapi.com/')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'omdb.txt path'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null); // Disable SQL logger
        $repo = $em->getRepository('SLMNWovieMainBundle:Omdb');
        $path = $input->getArgument('path');

        if (file_exists($path) && is_readable($path))
        {
            $handle = fopen($path, 'r');
            $lines = 0;
            while (!feof($handle))
            {
                $lines += substr_count(fread($handle, 8192), "\n");
            }
            fclose($handle);
            $output->writeln('<info>Importing '.$lines.' lines</info>');

            $progress = $this->getHelperSet()->get('progress');
            $progress->start($output, $lines);
            $progress->setRedrawFrequency(1000);
            $handle = fopen($path, 'r');
            if ($handle)
            {
                $i = 1;
                $unflushed = 0;
                while (!feof($handle))
                {
                    $buffer = fgets($handle, 8192);
                    if ($i > 1)
                    {
                        $bufferArray = explode("\t", $buffer);
                        if (count($bufferArray) == 21)
                        {
                            $omdb = $repo->find($bufferArray[1]);
                            if (!$omdb)
                            {
                                $omdb = new Omdb();
                            }

                            $omdb->setImdbId($bufferArray[1]);
                            $omdb->setRating($bufferArray[12] != 'N/A' ? $bufferArray[12] : null);
                            $omdb->setPosterImage($bufferArray[14] != 'N/A' ? $bufferArray[14] : null);
                            $omdb->setPlot($bufferArray[15] != 'N/A' ? $bufferArray[15] : null);
                            $em->persist($omdb);
                        }
                        else
                        {
                            $progress->clear();
                            $output->writeln('<error>Malformed line '.$i.' ('.count($bufferArray).' elements)</error>');
                            $progress->display();
                        }
                    }
                    $progress->advance();
                    $i++;
                    $unflushed++;
                    if ($unflushed >= 1000)
                    {
                        $em->flush();
                        $em->clear();
                        gc_collect_cycles();
                        $unflushed = 0;
                    }
                }
                fclose($handle);
                $progress->finish();
            }
        }
        else
        {
            $output->writeln('<error>File not found or is not readable</error>');
        }
    }
}