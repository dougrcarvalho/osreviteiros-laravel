<?php

namespace App\Http\Controllers;

use App\Services\HomeService;
use App\Support\HomeJsonLd;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(HomeService $home): View
    {
        $dados = $home->dados();

        $seo = [
            'title' => __('Famílias Revit, Texturas e Projetos Prontos | Os Reviteiros'),
            'description' => __(':total famílias Revit, texturas e projetos prontos para prefeitura. Insira no modelo em um clique com o plugin para Revit. Comece grátis.', ['total' => $dados['totalFmt']]),
            'canonical' => route('home'),
            'image' => config('reviteiros.home.og_image') ?: asset('images/og-home.png'),
        ];

        return view('home', $dados + [
            'seo' => $seo,
            'jsonLd' => HomeJsonLd::montar($dados, $seo['description']),
        ]);
    }
}
