<?php

namespace App\Http\Controllers\HTML;

class TextController extends HTMLController
{
    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Meta::meta('title', __("Texts and translations"));

        return new Response(view('texts.apps', []));
    }

    /**
     * @param string $textId
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($textId, Request $request)
    {
        $description = trans_choice("texts.apples", 5);
        $response = new Response(view($view, compact('origin', 'app', 'text')));

        return $response;
    }
}
