<?php

namespace App\Http\Controllers;
use App\Models\likes;
use App\Models\Reviews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReviewsController extends LikesController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        $reviews = DB::table('users')
            ->join('reviews', 'reviews.user_id', '=', 'users.id')
            ->select('reviews.id', 'reviews.rating', 'reviews.description', 'reviews.likes', 'reviews.created_at',
            'users.firstname', 'users.lastname', 'users.id AS user_id')
            ->where('reviews.recipe_id', $request->recipe_id)
            ->orderBy('id', 'DESC')
            ->paginate(2);

            //Cannot use Auth::id() for user id because route is not inside auth middleware in api
        $review_likes = likes::where('user_id', $request->user_id)->where('recipe_id', $request->recipe_id)->get();
        $response = [
            'reviews' => $reviews,
            'review_likes' => $review_likes
        ];
        if (count($response['reviews']) > 0){
            return $response;
        }
        else {
            return 'none';
        }
        // return Recipes::orderBy('id', 'DESC')->paginate(12);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //returns an error message rejected
        $result = $request->validate([
            'content' => 'required|max:1500'
        ]);
        $reviews = new Reviews;
        $reviews->description = $request->content;
        $reviews->rating = $request->rating;
        $reviews->likes = 0;
        $reviews->recipe_id = $request->recipe_id;
        $reviews->user_id = Auth::id();
        $result = $reviews->save();
        if ($result == 1) {
            return 'stored';
        }

        return 'error';
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Reviews  $reviews
     * @return \Illuminate\Http\Response
     */
    public function show(Reviews $reviews)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Reviews  $reviews
     * @return \Illuminate\Http\Response
     */
    public function edit(Reviews $reviews)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Reviews  $reviews
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
         //returns an error message rejected
         $result = $request->validate([
            'content' => 'required|max:1500'
        ]);
        $review = Reviews::find($request->review_id);
        $review->description = $request->content;
        $review->rating = $request->rating;
        $result = $review->save();
         if ($result == 1){
            return 'updated';
         }
         else {
            return 'error';
         }
    }

    public function updateLikes(Request $request)
    {
        $msg = '';
        $review = Reviews::find($request->review_id);
        $user_likes = likes::where('user_id', Auth::id())->where('review_id', $request->review_id)->get();
        if (count($user_likes) <= 0){
            $review->likes += 1;
            $likes = new likes;
            $likes->user_id = Auth::id();
            $likes->review_id = $request->review_id;
            $likes->recipe_id = $request->recipe_id;
            $likes->isliked = true;
            $likes->save();
            $msg = 'review_liked';
        }
        else {
            if ($review->likes !=0){
                $review->likes -= 1;
                likes::where('user_id', Auth::id())->where('review_id', $request->review_id)->delete(); // delete the retrieved data
                $msg = 'review_unliked';
            }
        }
        $result = $review->save();
        if ($result == 1){
            return $msg;
         }
         else {
            return 'error';
         }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Reviews  $reviews
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $review = Reviews::find($id);
        $recipe_id = $review->recipe_id;
        $result = $review->delete();
        if ($result == 1){

           return 'deleted';
        }
        else {
            return 'error';
        }
    }
}
