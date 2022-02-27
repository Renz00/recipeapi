<?php

namespace App\Http\Controllers;

use App\Models\likes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikesController extends BaseController
{
    public function getUserLikes(){
         // $review_likes = likes::where('user_id', Auth::id())->where('recipe_id', $recipe_id)->get();
    }
}
