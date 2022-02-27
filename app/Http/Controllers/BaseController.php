<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BaseController extends Controller
{
    public function explodeSlug($slug){
        //will create an array using the slug string
        $slugged = explode('-',$slug);
        //pulls the last value inside the slugged array
        $id = $slugged[array_key_last($slugged)];

        return $id;
    }
}
