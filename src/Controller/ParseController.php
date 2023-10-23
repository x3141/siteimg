<?php

namespace App\Controller;

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
        $parserEntity->setUrl('https://mayak.travel/');
        $form = $this->createForm(ParserType::class, $parserEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->parser->setUrl($parserEntity->getUrl());
            $parsedData = $this->parser->parseSite();

            return $this->render('crawl/parse.html.twig', [
                'parsedData' => $parsedData,
                'crawl' => $parserEntity,
                'form' => $form->createView(),
            ]);
        }

        return $this->render('crawl/parse.html.twig', [
            'parsedData' => '',
            'crawl' => $parserEntity,
            'form' => $form->createView(),
        ]);
    }
}
