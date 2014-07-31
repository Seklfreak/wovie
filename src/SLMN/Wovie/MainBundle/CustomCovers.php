<?php

namespace SLMN\Wovie\MainBundle;

use Aws\S3\S3Client;

class CustomCovers
{
    protected $s3client;
    protected $bucket;
    protected $em;
    protected $router;

    function __construct($em, $router, $awsKey, $awsSecret, $awsRegion, $awsBucketName)
    {
        $this->em = $em;
        $this->router = $router;
        $this->s3client = S3Client::factory(array(
            'key'    => $awsKey,
            'secret' => $awsSecret
        ));

        if (!$this->s3client->doesBucketExist($awsBucketName))
        {
            $this->s3client->createBucket(array(
                'Bucket'             => $awsBucketName,
                'LocationConstraint' => $awsRegion
            ));
            $this->s3client->waitUntil('BucketExists', array('Bucket' => $awsBucketName));
        }
        $this->bucket = $awsBucketName;
    }

    function get($media)
    {
        if ($media->getCustomCoverKey())
        {
            $result = $this->s3client->getObject(array(
                'Bucket' => $this->bucket,
                'Key'    => $media->getCustomCoverKey()
            ));

            return $result['Body'];
        }
        else
        {
            return null;
        }
    }

    function delete($media)
    {
        if ($media->getCustomCoverKey())
        {
            $this->s3client->deleteObject(array(
                'Bucket' => $this->bucket,
                'Key' => $media->getCustomCoverKey()
            ));
            $media->setCustomCoverKey(null);
            $media->setPosterImage(null);
            if ($media->getFreebaseId())
            {
                $media->setPosterImage(
                    $this->router->generate('slmn_wovie_image_coverImage', array('freebaseId' => $media->getFreebaseId()), true)
                );
            }
            $this->em->persist($media);
            $this->em->flush();
        }
    }

    function save($media, $tmpFile)
    {
        $this->delete($media); // Delete old cover (if exists)
        // TODO: resize image as dropzine.js and minify it
        $pathInfo = pathinfo($tmpFile);
        $fileKey = 'customCovers/'.md5(microtime()).'_'.intval($media->getId()).'.'.$pathInfo['extension'];
        $this->s3client->putObject(array(
            'Bucket' => $this->bucket,
            'Key' => $fileKey,
            'SourceFile' => $tmpFile,
            'Metadata'   => array(
                'mediaId' => $media->getId(),
                'userId' => $media->getCreatedBy()->getId()
            )
        ));

        $this->s3client->waitUntil('ObjectExists', array(
            'Bucket' => $this->bucket,
            'Key' => $fileKey,
        ));

        $media->setPosterImage($this->router->generate(
            'slmn_wovie_image_customCoverImage',
            array('mediaId' => $media->getId(), 'hash' => md5(microtime()), '_format' => $pathInfo['extension']),
            true
        ));
        $media->setCustomCoverKey($fileKey);
        $this->em->persist($media);
        $this->em->flush();

        @unlink($tmpFile);
    }
}
