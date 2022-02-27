<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RecipesController;
use App\Http\Controllers\ReviewsController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\FavoritesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Public routes
Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'store']);
Route::post('/unique', [UserController::class, 'checkUnique']); //validation for unique username
Route::get('/recipe/all', [RecipesController::class, 'index']);
Route::get('/recipe/get-filters', [RecipesController::class, 'getFilterRecipes']);
Route::post('/recipe/filter', [RecipesController::class, 'getFilteredRecipes']);
Route::get('/recipe/category/{category}', [RecipesController::class, 'getCategory']);
Route::post('/reviews/all', [ReviewsController::class, 'index']);
Route::get('/recipe/show/{slug}', [RecipesController::class, 'show']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('/logout', [UserController::class, 'logout']);

    Route::prefix('/recipe')->group( function (){
        Route::post('/store', [RecipesController::class, 'store']);
        Route::post('/add-favorite', [RecipesController::class, 'addFavorites']);
        Route::post('/check-fav', [RecipesController::class, 'checkFav']);
        Route::get('/my-recipes', [RecipesController::class, 'getMyRecipes']);
        Route::put('/update', [RecipesController::class, 'update']);
        Route::delete('/destroy/{id}', [RecipesController::class, 'destroy']);
    });

    Route::prefix('/review')->group( function (){
        Route::post('/store', [ReviewsController::class, 'store']);
        Route::put('/update', [ReviewsController::class, 'update']);
        Route::put('/like', [ReviewsController::class, 'updateLikes']);
        Route::delete('/destroy/{id}', [ReviewsController::class, 'destroy']);
    });

    Route::prefix('/favorites')->group( function (){
        Route::get('/all', [FavoritesController::class, 'index']);
    });


});
