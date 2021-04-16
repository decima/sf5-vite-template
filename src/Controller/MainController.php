<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{

    #[Route("/", "Homepage")]
    public function index(){
        return $this->render("home/index.html.twig");
    }

}