<?php

namespace App\Http\Controllers;

use App\Models\Links;
use Illuminate\Http\Request;
use DB;
use PHPUnit\Framework\Constraint\IsEmpty;

class LinksController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // retornar todos
        $links = Links::all();
        //dd($links);
        if ($links->count() > 0) {
            return response()->json(['links' => $links]);
        } else {
            return response()->json(['links' => 0]);
        }
    }

    public function listar()
    {
        // retornar 10 mais visitadas
        $links = Links::orderBy("clicks", "DESC")->limit(10)->get();
        //dd($links);
        if ($links->count() > 0) {
            return response()->json(['links' => $links]);
        } else {
            return response()->json(['message' => 'No links found'], 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function encurtar(Request $request)
    {
        $inicio = microtime(true);
        try {
            // salvar na tabela links e retornar os dados
            if ($request->custom_alias) {
                $a = $request->custom_alias;
            } else {
                $a = "";
            }
            $b = $request->url;
            $links = Links::where('url_original', '=', $b)->get()->first();
            if ($links) {
                if ($links->alias == $a) {
                    return response()->json(['codigo' => 2, 'alias' => $links->alias, 'err_code' => '001', 'message' => 'CUSTOM ALIAS JÁ EXISTE']);
                } elseif ($links->url_original == $b) {
                    $this->addClick($links);
                    return response()->json(['codigo' => 3, 'message' => 'Link já existente, nova visita', 'link' => $links], 200);
                }
            } else {
                $link = new Links();
                $link->url_original = $b;
                $link->url_shortened = $this->gerarCodigo($b);
                $link->alias = $a == "" ? $this->gerarAlias($request->url) : $a;
                $link->clicks = 1;
                $link->save();
            }

        } catch (\Exception $e) {
            return response()->json(['codigo' => 4, 'error' => 'Erro ao criar link', 'message' => $e->getMessage()]);
        }
        $texec = $this->calcTempoExec($inicio);
        return response()->json(['codigo' => 1, 'message' => 'Link adicionado com sucesso', 'alias' => $link->alias, 'url' => $link->url_original, 'statistics' => ['time_taken' => $texec]]);
    }


    public function calcTempoExec($inicio)
    {
        sleep(1);
        $fim = microtime(true);
        $tempo = $this->formatPeriod($fim, $inicio);
        return $tempo;
    }

    function formatPeriod($endtime, $starttime)
    {

        $duration = $endtime - $starttime;

        $hours = (int) ($duration / 60 / 60);

        $minutes = (int) ($duration / 60) - $hours * 60;

        $seconds = (int) $duration - $hours * 60 * 60 - $minutes * 60;

        return $seconds < 10 ? "0" . $seconds : $seconds;
    }
    public function addClick(Links $link)
    {
        $link->clicks++;
        $link->save();
    }

    public function gerarCodigo($url)
    {
        $arr = explode("//", $url);
        $arr2 = explode("/", $arr[1]);
        $dominio = $arr2[0];
        $codigo = $dominio . "/" . hash('crc32', $url);
        return $codigo;
    }

    public function gerarAlias($url)
    {
        $arr = explode("//", $url);
        $arr2 = explode("/", $arr[1]);
        $dominio = $arr2[0];
        $alias = $dominio;
        return $alias;
    }


}
