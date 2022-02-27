<?php

namespace App\Http\Controllers;

use App\Models\Steps;
use App\Models\Recipes;
use App\Models\Reviews;
use App\Models\Favorites;
use App\Models\Ingredients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class RecipesController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Recipes::orderBy('id', 'DESC')->paginate(12);
    }

    public function addFavorites(Request $request){

        $fav_checker = Favorites::where('recipe_id', $request->recipe_id)
        ->where('user_id', $request->user_id)
        ->get();
        $type = '';
        if (count($fav_checker) <= 0){ //if recipe has not been favorited
            $favorite = new Favorites;
            $type = 'stored-favorites';
            $favorite->recipe_id = $request->recipe_id;
            $favorite->user_id = $request->user_id;
            $result = $favorite->save();
        }
        else {
            $result = Favorites::where('recipe_id', $request->recipe_id)
            ->where('user_id', $request->user_id)
            ->delete();
            $type = 'removed-favorites';
        }


        if ($result == 1){
            return $type;
        }
        else {
            return 'error';
        }
    }

    public function getCategory($category){
        $clean_category = str_replace('-', ' ', $category);
        return Recipes::where('category', $clean_category)->orderBy('id', 'DESC')->paginate(12);
    }

    public function getMyRecipes(){
        $myrecipescheck = Recipes::where('user_id', Auth::id())->first();

        if ($myrecipescheck){
            return DB::table('recipes')
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->select('recipes.id', 'recipes.user_id', 'recipes.name','recipes.description','recipes.category',
            'recipes.rating', 'recipes.updated_at')
            ->where('users.id', Auth::id()) //can only use auth when route is placed inside auth middleware in api file
            ->orderBy('recipes.id', 'DESC')
            ->paginate(12);
        }
        else {
            return 'none';
        }
    }

    public function getFilteredRecipes(Request $request){
        if ($request->category != ''){ // if a category has been selected
            if ($request->searchText != null && $request->searchText != '' ){ // filter by search text only
                if ($request->rating == 'No Filter'){
                    return Recipes::where('category', $request->category)
                    ->where('name', 'LIKE', '%'.$request->searchText.'%')
                    ->orWhere('category', 'LIKE', '%'.$request->searchText.'%')
                    ->orderBy('id', 'DESC')
                    ->paginate(12);
                }
                else {
                    $rating = substr($request->rating, 0, 1); // filter using text and rating
                    return Recipes::where('rating', $rating)
                    ->where('category', $request->category)
                    ->where('name', 'LIKE', '%'.$request->searchText.'%')
                    ->orWhere('category', 'LIKE', '%'.$request->searchText.'%')
                    ->orderBy('id', 'DESC')
                    ->paginate(12);
                }
            }
            else {
                if ($request->rating == 'No Filter'){ // retrieve all data
                    return Recipes::where('category', $request->category)->orderBy('id', 'DESC')->paginate(12);
                }
                else {// filter by rating only
                    $rating = substr($request->rating, 0, 1);
                    return Recipes::where('rating', $rating)
                    ->where('category', $request->category)
                    ->orderBy('id', 'DESC')
                    ->paginate(12);
                }
            }
        }
        else {
            if ($request->searchText != null && $request->searchText != '' ){ // filter by search text only
                if ($request->rating == 'No Filter'){
                    return Recipes::where('name', 'LIKE', '%'.$request->searchText.'%')
                    ->orWhere('category', 'LIKE', '%'.$request->searchText.'%')
                    ->orderBy('id', 'DESC')
                    ->paginate(12);
                }
                else {
                    $rating = substr($request->rating, 0, 1); // filter using text and rating
                    return Recipes::where('rating', $rating)
                    ->where('name', 'LIKE', '%'.$request->searchText.'%')
                    ->orWhere('category', 'LIKE', '%'.$request->searchText.'%')
                    ->orderBy('id', 'DESC')
                    ->paginate(12);
                }
            }
            else {
                if ($request->rating == 'No Filter'){ // retrieve all data
                    return Recipes::orderBy('id', 'DESC')->paginate(12);
                }
                else {// filter by rating only
                    $rating = substr($request->rating, 0, 1);
                    return Recipes::where('rating', $rating)
                    ->orderBy('id', 'DESC')
                    ->paginate(12);
                }
            }
        }
    }

    public function getFilterRecipes(){
        return Recipes::orderBy('id', 'DESC')->get();
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
        $validation_result = $request->validate([
            'name' => 'required|max:300',
            'category' => 'required',
            'description' => 'required|max:500'
        ]);

        $recipe = new Recipes;
        $recipe->name = $request->name;
        $recipe->user_id = Auth::id();
        $recipe->description = $request->description;
        $recipe->category = $request->category;
        $recipe->servings = $request->servings;
        $recipe->rating = 0;
        if ($request->hasfile('image')){
             //returns an error message rejected
            $validation_result2 = $request->validate([
                'image' => 'file|image|max:2000',
            ]);
            $file = $request->file('image');
            $filename = $file->hashName(); // Generate a unique, random name...
            // $extension = $file->extension(); // Determine the file's extension based on the file's MIME type...
            $file->storeAs('recipe_images', $filename, 'public'); //i specified public to make sure it save on the storage link when php artisan storage:link is used
            $recipe->image = $filename; //saving the filename to database
            $recipe->image_url = URL::to('/').'/storage/recipe_images/'.$filename; //saving image url to database
        }
        else {
            $recipe->image = 'recipe_default.png';
            $recipe->image_url = URL::to('/').'/storage/recipe_images/recipe_default.png'; //setting the default image url to database
        }
        $result1 = $recipe->save();
        $recipe_id = $recipe->id;

        $result2 = '';
        foreach (json_decode($request->steps, true) as $step){ //use json_decode to convert json string back to object
            $steps = new Steps;
            $steps->steps_count = $step['number'];
            $steps->steps_description = $step['content'];
            $steps->recipe_id = $recipe_id;
            $result2 = $steps->save();
        }

        $result3 = '';
        foreach (json_decode($request->ingredients, true) as $ingredient){
            $ingredients = new Ingredients;
            $ingredients->ingredient_name = $ingredient['name'];
            $ingredients->ingredient_amount = $ingredient['amount'];
            $ingredients->recipe_id = $recipe_id;
            $result3 = $ingredients->save();
        }

        if ($result1 == 1 && $result2 == 1 && $result3 == 1){

            $response = [
                'recipe_id' => $recipe_id,
                'message' => 'stored-recipe'
            ];
            return $response;
        }
        else {
            return 'error-recipe';
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Recipes  $recipes
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $id = $this->explodeSlug($slug); //this is a method inside BaseController

        $recipe =  DB::table('users')
        ->join('recipes', 'recipes.user_id', '=', 'users.id')
        ->select('recipes.id', 'recipes.user_id', 'recipes.name','recipes.description','recipes.category' , 'recipes.servings',
        'recipes.image_url', 'recipes.updated_at', 'users.firstname', 'users.lastname')
        ->where('recipes.id', $id)
        ->get();

        $steps = Steps::where('recipe_id', $id)->get();
        $ingredients = Ingredients::where('recipe_id', $id)->get();
        $reviews = Reviews::select('rating')->where('recipe_id', $id)->get();

        $total_items = count($reviews);
        $avg_rating = 0;
        if ($total_items > 0){
            $rating_sum = 0;
            foreach ($reviews as $review){
                $rating_sum += $review->rating;
            }
            $avg_rating = round($rating_sum / $total_items);
        }

        $recipe_update = Recipes::find($id);
        $recipe_update->rating = $avg_rating;
        $result = $recipe_update->save();

         if ($result == 1){
            $response = [
                'recipe' => $recipe,
                'steps' => $steps,
                'ingredients' => $ingredients,
                'rating' => $avg_rating
            ];
            return $response;
         }
         else {
            return 'error';
         }
    }

    public function checkFav(Request $request){
        $fav_status = 0;
        $favorites = Favorites::where('recipe_id', $request->recipe_id)
        ->where('user_id', $request->user_id)
        ->get();
        if (count($favorites) > 0 ){
            $fav_status = 1; //set this to an integer value because typing true or false returns empty string to vue js
        }
        else {
            $fav_status = 0;
        }
        return $fav_status;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Recipes  $recipes
     * @return \Illuminate\Http\Response
     */
    public function edit(Recipes $recipes)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Recipes  $recipes
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request) // continue working on delete image...
    {

        //returns an error message rejected
        $result = $request->validate([
            'name' => 'required|max:300',
            'category' => 'required',
            'description' => 'required|max:500'
        ]);
        $recipe = Recipes::find($request->recipe_id);
        $recipe->name = $request->name;
        $recipe->description = $request->description;
        $recipe->category = $request->category;
        $recipe->servings = $request->servings;

        if ($request->hasfile('image')){
            //returns an error message rejected
           $validation_result2 = $request->validate([
               'image' => 'file|image|max:2000',
           ]);
           if ($recipe->image != 'recipe_default.png'){ //makes sure that our default image file won't get deleted...
            File::delete(public_path('/storage/recipe_images/'.$recipe->image)); //use this to delete files from storage
           }

           $file = $request->file('image');
           $filename = $file->hashName(); // Generate a unique, random name...
           // $extension = $file->extension(); // Determine the file's extension based on the file's MIME type...
           $file->storeAs('recipe_images', $filename, 'public');
           $recipe->image = $filename;
           $recipe->image_url = URL::to('/').'/storage/recipe_images/'.$filename;
       }

        $result1 = $recipe->save();
        $recipe_id = $recipe->id;

        $result2 = '';
        foreach (json_decode($request->steps, true) as $step){
            if (array_key_exists('id', $step)){ //if steps already exists, just update it.
                $steps = Steps::find($step['id']);
            }
            else { //else, insert a new record
                $steps = new Steps;
                $steps->recipe_id = $request->recipe_id;
            }
            $steps->steps_count = $step['steps_count'];
            $steps->steps_description = $step['steps_description'];
            $result2 = $steps->save();
        }

        $result3 = '';
        foreach (json_decode($request->ingredients, true) as $ingredient){
            if (array_key_exists('id', $ingredient)){ //if ingredient already exists, just update it.
                $ingredients = Ingredients::find($ingredient['id']);
            }
            else {
                $ingredients = new Ingredients;
                $ingredients->recipe_id = $request->recipe_id;
            }
            $ingredients->ingredient_name = $ingredient['ingredient_name'];
            $ingredients->ingredient_amount = $ingredient['ingredient_amount'];
            $result3 = $ingredients->save();
        }

        if ($result1 == 1 && $result2 == 1 && $result3 == 1){

            $response = [
              'recipe_id' => $recipe_id,
              'message' => 'updated-recipe'
            ];
            return $response;
        }
        else {
            return 'error';
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Recipes  $recipes
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $recipe = Recipes::find($id);
        if ($recipe->image != 'recipe_default.png'){ //checks if the url in database is not equal to the default image
            File::delete(public_path('/storage/recipe_images/'.$recipe->image)); //use this to delete files from storage
        }
        $result = $recipe->delete();
        if ($result == 1){
           return 'deleted-recipe';
        }
        else {
            return 'error-recipe';
        }
    }
}
