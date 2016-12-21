<?php

namespace App\Action;

use Slim\Http\Request;
use Slim\Http\Response;
use FileSystemCache;
use Thapp\XmlBuilder\XMLBuilder;
use Thapp\XmlBuilder\Normalizer;

final class PhrasesAndThoughts
{
    private $fileXML;

    public function __invoke(Request $request, Response $response, $args)
    {
        $this->setFileXML(__DIR__ . '/../../../data/frases_pensamentos.xml');

        if(file_exists($this->getFileXML()))
        {
            $amount_thinker = isset($args['amount_thinker']) ? $args['amount_thinker'] : 5;
            $amount_phrases = isset($args['amount_phrases']) ? $args['amount_phrases'] : 5;
            $forceFileCached = isset($request->getQueryParams()['forceFileCached']) ? $request->getQueryParams()['forceFileCached'] : false;

            FileSystemCache::$cacheDir = __DIR__ . '/../../../cache/tmp';
            $key = FileSystemCache::generateCacheKey('cache', null);
            $newXML = FileSystemCache::retrieve($key);

            if($newXML === false || $forceFileCached == true)
            {
                $reader = json_decode(json_encode(simplexml_load_file($this->getFileXML())), true);
                $reader = $reader['thinker'];
                $newXML = array();

                if(count($reader) < $amount_thinker)
                {
                    $amount_thinker = count($reader);
                }


                for ($i = 0; $i < $amount_thinker; $i++)
                {
                    $indice_reader = rand(0, count($reader)-1);
                    $newXML[$i] = array(
                        'name' => $reader[$indice_reader]['name'],
                        'biography' => $reader[$indice_reader]['biography'],
                        'image' =>$this->getPathImages() . $reader[$indice_reader]['image'],
                        'phrases'=> array()
                    );

                    if(count($reader[$indice_reader]['phrases']['phrase']) < $amount_phrases)
                    {
                        $amount_phrases = count($reader[$indice_reader]['phrases']['phrase']);
                    }

                    for($p = 0; $p < $amount_phrases; $p++)
                    {
                        $indice_phrase = rand(0, count($reader[$indice_reader]['phrases']['phrase'])-1);
                        $newXML[$i]['phrases'][] = $reader[$indice_reader]['phrases']['phrase'][$indice_phrase];

                        unset($reader[$indice_reader]['phrases']['phrase'][$indice_phrase]);
                        shuffle($reader[$indice_reader]['phrases']['phrase']);
                    }

                    unset($reader[$indice_reader]);
                    shuffle($reader);
                }

                FileSystemCache::store($key, $newXML, 432000);
            }
        }
        else
        {
            $newXML = array(
                'status' => 'ERROR',
                'message' => 'Arquivo não encontrado'
            );
        }

        $xmlMaker = new XMLBuilder('root');
        $xmlMaker->setSingularizer(function ($name) {
            if ('phrases' === $name) {
                return 'item';
            }
            return $name;
        });
        $xmlMaker->load($newXML);
        $xml_output = $xmlMaker->createXML(true);
        $response->write($xml_output);
        $response = $response->withHeader('content-type', 'text-html');

        if(isset($newXML['status']))
        {
            if ($newXML['status'] == 'ERROR') {
                $response = $response->withStatus(404);
            }
        }

        return $response;

    }

    public function getFileXML()
    {
        return $this->fileXML;
    }

    public function setFileXML($fileXML)
    {
        $this->fileXML = $fileXML;
    }
    public function  getPathImages()
    {
        return 'http://' . $_SERVER['HTTP_HOST'] . '/data/uploads/images/';
    }

}