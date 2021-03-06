<?php

namespace SLMN\Wovie\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrameController extends Controller
{
    public function chooseEpisodeAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $mediaRepo = $em->getRepository('SLMNWovieMainBundle:Media');
        $media = $mediaRepo->findOneById($id);
        if ($media != null && $media->getCreatedBy() == $this->getUser()) {
            if ($media->getMediaType() == 2) {

                return $this->render(
                    'SLMNWovieMainBundle:html/frame:chooseEpisode.html.twig',
                    array(
                        'media' => $media
                    )
                );
            }
            throw new \Exception('You are not allowed to edit this media.');
        }
        throw $this->createNotFoundException('Media not found!');
    }
}