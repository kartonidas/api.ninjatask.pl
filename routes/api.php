<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskTimeController;
use App\Http\Controllers\UserController;

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
    
    $router->get('/get-firm-id', [UserController::class, "getFirmId"]);
    $router->get('/get-id', [UserController::class, "getId"]);
    
    // UPRAWNIENIA
    $router->get('/permissions', [PermissionController::class, "list"]);
    $router->put('/permission', [PermissionController::class, "create"]);
    $router->get('/permission/{id}', [PermissionController::class, "get"])->where("id", "[0-9]+");
    $router->put('/permission/{id}', [PermissionController::class, "update"])->where("id", "[0-9]+");
    $router->delete('/permission/{id}', [PermissionController::class, "delete"])->where("id", "[0-9]+");
    $router->put('/permission/{id}/add', [PermissionController::class, "addPermission"])->where("id", "[0-9]+");
    $router->delete('/permission/{id}/del', [PermissionController::class, "removePermission"])->where("id", "[0-9]+");
    
    // ZADANIA
    $router->get('/tasks/{id}', [TaskController::class, "list"]);
    $router->put('/task', [TaskController::class, "create"]);
    $router->get('/task/{id}', [TaskController::class, "get"])->where("id", "[0-9]+");
    $router->put('/task/{id}', [TaskController::class, "update"])->where("id", "[0-9]+");
    $router->delete('/task/{id}', [TaskController::class, "delete"])->where("id", "[0-9]+");
    $router->post('/task/{id}/assign', [TaskController::class, "assignUser"])->where("id", "[0-9]+");
    $router->post('/task/{id}/deassign', [TaskController::class, "deAssignUser"])->where("id", "[0-9]+");
    $router->get('/task/{id}/attachment/{aid}', [TaskController::class, "getAttachment"])->where("id", "[0-9]+")->where("aid", "[0-9]+");
    $router->put('/task/{id}/attachment', [TaskController::class, "addAttachment"])->where("id", "[0-9]+");
    $router->delete('/task/{id}/attachment/{aid}', [TaskController::class, "removeAttachment"])->where("id", "[0-9]+")->where("aid", "[0-9]+");
    
    // ZADANIA - CZAS PRACY
    $router->post('/task/{id}/time/start', [TaskTimeController::class, "start"])->where("id", "[0-9]+");
    $router->post('/task/{id}/time/stop', [TaskTimeController::class, "stop"])->where("id", "[0-9]+");
    $router->post('/task/{id}/time/pause', [TaskTimeController::class, "pause"])->where("id", "[0-9]+");
    $router->put('/task/{id}/time', [TaskTimeController::class, "logTime"])->where("id", "[0-9]+");
    $router->put('/task/{id}/time/{tid}', [TaskTimeController::class, "updateLogTime"])->where("id", "[0-9]+")->where("tid", "[0-9]+");
    $router->delete('/task/{id}/time/{tid}', [TaskTimeController::class, "deleteTime"])->where("id", "[0-9]+")->where("tid", "[0-9]+");
    $router->get('/task/{id}/times', [TaskTimeController::class, "getTimes"])->where("id", "[0-9]+");
    
    // ZADANIA - KOMENTARZ
    $router->get('/task/{id}/comments', [TaskCommentController::class, "list"]);
    $router->put('/task/{id}/comment', [TaskCommentController::class, "create"]);
    $router->get('/task/{id}/comment/{cid}', [TaskCommentController::class, "get"])->where("id", "[0-9]+")->where("cid", "[0-9]+");
    $router->put('/task/{id}/comment/{cid}', [TaskCommentController::class, "update"])->where("id", "[0-9]+")->where("cid", "[0-9]+");
    $router->delete('/task/{id}/comment/{cid}', [TaskCommentController::class, "delete"])->where("id", "[0-9]+")->where("cid", "[0-9]+");
    $router->get('/task/{id}/comment/{cid}/attachment/{aid}', [TaskCommentController::class, "getAttachment"])->where("id", "[0-9]+")->where("cid", "[0-9]+")->where("aid", "[0-9]+");
    $router->put('/task/{id}/comment/{cid}/attachment', [TaskCommentController::class, "addAttachment"])->where("id", "[0-9]+")->where("cid", "[0-9]+");
    $router->delete('/task/{id}/comment/{cid}/attachment/{aid}', [TaskCommentController::class, "removeAttachment"])->where("id", "[0-9]+")->where("cid", "[0-9]+")->where("aid", "[0-9]+");
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
Route::get("/reset-password", [UserController::class, "resetPasswordGet"]);
Route::post("/reset-password", [UserController::class, "resetPassword"]);
Route::get("/get-email-firm-ids", [UserController::class, "getUserFirmIds"]);

