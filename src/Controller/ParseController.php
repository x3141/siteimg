<?php

namespace App\Controller;

use App\Exception\ParserException;
use App\Parser\Parser;
use App\Entity\Parser as ParserEntity;
use App\Form\ParserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParseController extends AbstractController
{
    public function __construct(private Parser $parser)
    {
    }

    #[Route('/', name: 'app_parse', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {

        $parserEntity = new ParserEntity();
        $form = $this->createForm(ParserType::class, $parserEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->parser->setUrl($parserEntity->getUrl());
            try {
                $parsedData = $this->parser->parseSite();
            } catch (ParserException $e) {
                return $this->render('crawl/parse.html.twig', [
                    'parsedData' => '',
                    'error' => $e->getMessage(),
                    'crawl' => $parserEntity,
                    'form' => $form->createView(),
                ]);
            }


            return $this->render('crawl/parse.html.twig', [
                'parsedData' => $parsedData,
                'error' => '',
                'crawl' => $parserEntity,
                'form' => $form->createView(),
            ]);
        }

        return $this->render('crawl/parse.html.twig', [
            'parsedData' => '',
            'error' => '',
            'crawl' => $parserEntity,
            'form' => $form->createView(),
        ]);
    }
}
