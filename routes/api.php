<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RegisterController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::middleware('auth:sanctum')->group(function () use($router) {
    $router->get('/logout', [UserController::class, "logout"]);
    
    // PROJEKTY
    $router->get('/projects', [ProjectController::class, "list"]);
    $router->put('/project', [ProjectController::class, "create"]);
    $router->get('/project/{id}', [ProjectController::class, "get"])->where("id", "[0-9]+");
    $router->put('/project/{id}', [ProjectController::class, "update"])->where("id", "[0-9]+");
    $router->delete('/project/{id}', [ProjectController::class, "delete"])->where("id", "[0-9]+");
    
    // PRACOWNICY
    $router->get('/users', [UserController::class, "list"]);
    $router->put('/user', [UserController::class, "create"]);
    $router->post('/invite', [UserController::class, "invite"]);
    $router->get('/user/{id}', [UserController::class, "get"])->where("id", "[0-9]+");
    $router->put('/user/{id}', [UserController::class, "update"])->where("id", "[0-9]+");
    $router->delete('/user/{id}', [UserController::class, "delete"])->where("id", "[0-9]+");
    
    // ZADANIA
});

// REJESTRACJA
Route::post("/register", [RegisterController::class, "register"]);
Route::get("/register/confirm/{token}", [RegisterController::class, "get"]);
Route::post("/register/confirm/{token}", [RegisterController::class, "confirm"]);

// REJESTRACJA Z ZAPROSZENIA
Route::get('/invite/{token}', [UserController::class, "inviteGet"]);
Route::put('/invite/{token}', [UserController::class, "inviteConfirm"]);

// LOGOWANIE / PRZYPOMNIENIE HASŁA
Route::post("/login", [UserController::class, "login"]);
Route::post("/forgot-password", [UserController::class, "forgotPassword"]);
Route::post("/reset-password", [UserController::class, "resetPassword"]);
