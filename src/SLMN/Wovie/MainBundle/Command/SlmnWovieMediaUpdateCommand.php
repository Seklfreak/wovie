<?php

namespace SLMN\Wovie\MainBundle\Command;

use SLMN\Wovie\MainBundle\Entity\Omdb;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SlmnWovieMediaUpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('slmn:wovie:media:update')
            ->setDescription('Updates all media entries and add new data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $mediaApi = $this->getContainer()->get('media_api');
        $mediaApi->setLang('de');
        $userOptionsRepo = $em->getRepository('SLMNWovieMainBundle:UserOption');
        $em->getConnection()->getConfiguration()->setSQLLogger(null); // Disable SQL logger

        $progress = $this->getHelperSet()->get('progress');
        $batchSize = 20;
        $i = 0;
        $q = $em->createQuery('select m from SLMNWovieMainBundle:Media m');
        $progress->start($output, count($q->getResult()));
        $iterableResult = $q->iterate();
        foreach($iterableResult AS $row) {
            $progress->clear();
            $media = $row[0];
            $lang = $userOptionsRepo->findOneBy(
                array(
                    'key' => 'language',
                    'createdBy' => $media->getCreatedBy()
                )
            );
            if ($lang && $lang->getValue())
            {
                $mediaApi->setLang($lang->getValue());
            }
            else
            {
                $mediaApi->setLang('en');
            }
            if ($media->getAllowUpdates() && $media->getFreebaseId())
            {
                if (($episodes=$mediaApi->fetchEpisodes($media->getFreebaseId(), true)) && is_array($episodes))
                {
                    if ($media->getNumberOfEpisodes() < count($episodes))
                    {
                        $output->writeln('(user '.$media->getCreatedBy()->getEmail().', lang '.$lang->getValue().') Replace episodes of #'.$media->getId().' ('.$media->getTitle().') with "'.$episodes[1]['name'].'"… ('.count($episodes).' episodes)');
                        $media->setEpisodes($episodes);
                        $media->setNumberOfEpisodes(count($episodes));
                    }
                }
            }
            $progress->display();
            $progress->advance();
            if (($i % $batchSize) == 0) {
                $em->flush();
                $em->clear();
            }
            ++$i;
        }
        $em->flush();
        $em->clear();
        $progress->finish();
    }
}