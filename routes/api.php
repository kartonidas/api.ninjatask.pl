<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CustomerInvoicesController;
use App\Http\Controllers\DictionaryController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SaleRegisterController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\StatusController;
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

Route::prefix('v1')->middleware(['auth:sanctum', 'locale'])->group(function () use($router) {
    // PAKIETY I CENY
    $router->get('/packages', [IndexController::class, "packages"]);
    
    $router->get("/is-login", [UserController::class, "isLogin"]);
    $router->get('/logout', [UserController::class, "logout"]);
    
    $router->get('/dashboard', [IndexController::class, "dashboard"]);
    $router->get('/subscription', [IndexController::class, "getActiveSubscription"]);
    $router->get('/current-stats', [IndexController::class, "getCurrentStats"]);
    $router->get('/search', [IndexController::class, "search"]);
    $router->get('/search/{source}', [IndexController::class, "searchIn"]);
    
    // PROJEKTY
    $router->get('/projects', [ProjectController::class, "list"]);
    $router->put('/project', [ProjectController::class, "create"]);
    $router->get('/project/{id}', [ProjectController::class, "get"])->where("id", "[0-9]+");
    $router->put('/project/{id}', [ProjectController::class, "update"])->where("id", "[0-9]+");
    $router->delete('/project/{id}', [ProjectController::class, "delete"])->where("id", "[0-9]+");
    $router->get('/projectsWithTasks', [ProjectController::class, "getProjectsWithOpenedTasks"]);
    $router->get('/projects/simple', [ProjectController::class, "listSimple"]);
    
    // PRACOWNICY
    $router->get('/users', [UserController::class, "list"]);
    $router->put('/user', [UserController::class, "create"]);
    $router->post('/invite', [UserController::class, "invite"]);
    $router->get('/user/{id}', [UserController::class, "get"])->where("id", "[0-9]+");
    $router->put('/user/{id}', [UserController::class, "update"])->where("id", "[0-9]+");
    $router->delete('/user/{id}', [UserController::class, "delete"])->where("id", "[0-9]+");
    $router->get('/user/permission', [UserController::class, "getPermissions"]);
    $router->get('/profile', [UserController::class, "profile"]);
    $router->put('/profile', [UserController::class, "profileUpdate"]);
    $router->post('/profile/avatar', [UserController::class, "profileAvatarUpdate"]);
    $router->get('/settings', [UserController::class, "settings"]);
    $router->put('/settings', [UserController::class, "settingsUpdate"]);
    
    $router->get('/get-firm-id', [UserController::class, "getFirmId"]);
    $router->get('/get-id', [UserController::class, "getId"]);
    $router->get('/user/getActiveTimer', [UserController::class, "getActiveTimer"]);
    
    // UPRAWNIENIA
    $router->get('/permissions', [PermissionController::class, "list"]);
    $router->put('/permission', [PermissionController::class, "create"]);
    $router->get('/permission/{id}', [PermissionController::class, "get"])->where("id", "[0-9]+");
    $router->put('/permission/{id}', [PermissionController::class, "update"])->where("id", "[0-9]+");
    $router->delete('/permission/{id}', [PermissionController::class, "delete"])->where("id", "[0-9]+");
    $router->put('/permission/{id}/add', [PermissionController::class, "addPermission"])->where("id", "[0-9]+");
    $router->delete('/permission/{id}/del', [PermissionController::class, "removePermission"])->where("id", "[0-9]+");
    $router->get('/permission/modules', [PermissionController::class, "permissionModules"]);
    
    // ZADANIA
    $router->get('/tasks/{id}', [TaskController::class, "list"])->where("id", "[0-9]+");
    $router->put('/task', [TaskController::class, "create"]);
    $router->get('/task/{id}', [TaskController::class, "get"])->where("id", "[0-9]+");
    $router->put('/task/{id}', [TaskController::class, "update"])->where("id", "[0-9]+");
    $router->delete('/task/{id}', [TaskController::class, "delete"])->where("id", "[0-9]+");
    $router->post('/task/{id}/assign', [TaskController::class, "assignUser"])->where("id", "[0-9]+");
    $router->post('/task/{id}/deassign', [TaskController::class, "deAssignUser"])->where("id", "[0-9]+");
    $router->get('/task/{id}/attachment/{aid}', [TaskController::class, "getAttachment"])->where("id", "[0-9]+")->where("aid", "[0-9]+");
    $router->post('/task/{id}/attachment', [TaskController::class, "addAttachment"])->where("id", "[0-9]+");
    $router->delete('/task/{id}/attachment/{aid}', [TaskController::class, "removeAttachment"])->where("id", "[0-9]+")->where("aid", "[0-9]+");
    $router->get('/task/users/{id?}', [TaskController::class, "getAllowedUsers"])->where("id", "[0-9]+");
    $router->post('/task/{id}/close', [TaskController::class, "close"])->where("id", "[0-9]+");
    $router->post('/task/{id}/open', [TaskController::class, "open"])->where("id", "[0-9]+");
    $router->get('/tasks/my-work', [TaskController::class, "myWork"]);
    $router->get('/task/{id}/time', [TaskController::class, "time"]);
    
    // ZADANIA - CZAS PRACY
    $router->post('/task/{id}/time/start', [TaskTimeController::class, "start"])->where("id", "[0-9]+");
    $router->post('/task/{id}/time/stop', [TaskTimeController::class, "stop"])->where("id", "[0-9]+");
    $router->post('/task/{id}/time/pause', [TaskTimeController::class, "pause"])->where("id", "[0-9]+");
    $router->put('/task/{id}/time', [TaskTimeController::class, "logTime"])->where("id", "[0-9]+");
    $router->put('/task/{id}/time/{tid}', [TaskTimeController::class, "updateLogTime"])->where("id", "[0-9]+")->where("tid", "[0-9]+");
    $router->delete('/task/{id}/time/{tid}', [TaskTimeController::class, "deleteTime"])->where("id", "[0-9]+")->where("tid", "[0-9]+");
    $router->get('/task/{id}/times', [TaskTimeController::class, "getTimes"])->where("id", "[0-9]+");
    $router->get('/task/{id}/time/{tid}', [TaskTimeController::class, "getTime"])->where("id", "[0-9]+");
    
    // ZADANIA - KOMENTARZ
    $router->get('/task/{id}/comments', [TaskCommentController::class, "list"])->where("id", "[0-9]+");
    $router->get('/task/{id}/comments/load-more/{lid}', [TaskCommentController::class, "loadMore"])->where("id", "[0-9]+")->where("lid", "[0-9]+");
    $router->put('/task/{id}/comment', [TaskCommentController::class, "create"]);
    $router->get('/task/{id}/comment/{cid}', [TaskCommentController::class, "get"])->where("id", "[0-9]+")->where("cid", "[0-9]+");
    $router->put('/task/{id}/comment/{cid}', [TaskCommentController::class, "update"])->where("id", "[0-9]+")->where("cid", "[0-9]+");
    $router->delete('/task/{id}/comment/{cid}', [TaskCommentController::class, "delete"])->where("id", "[0-9]+")->where("cid", "[0-9]+");
    $router->get('/task/{id}/comment/{cid}/attachment/{aid}', [TaskCommentController::class, "getAttachment"])->where("id", "[0-9]+")->where("cid", "[0-9]+")->where("aid", "[0-9]+");
    $router->post('/task/{id}/comment/{cid}/attachment', [TaskCommentController::class, "addAttachment"])->where("id", "[0-9]+")->where("cid", "[0-9]+");
    $router->delete('/task/{id}/comment/{cid}/attachment/{aid}', [TaskCommentController::class, "removeAttachment"])->where("id", "[0-9]+")->where("cid", "[0-9]+")->where("aid", "[0-9]+");
    
    // STATUSY
    $router->get('/statuses', [StatusController::class, "list"]);
    $router->put('/status', [StatusController::class, "create"]);
    $router->get('/status/{id}', [StatusController::class, "get"])->where("id", "[0-9]+");
    $router->put('/status/{id}', [StatusController::class, "update"])->where("id", "[0-9]+");
    $router->delete('/status/{id}', [StatusController::class, "delete"])->where("id", "[0-9]+");
    
    // ZAMÓWIENIA / PŁATNOŚCI
    $router->get('/payment/status/{md5}', [PaymentController::class, "status"]);
    $router->post('/order/create', [OrderController::class, "create"]);
    
    $router->get('/invoices', [OrderController::class, "invoices"]);
    $router->get('/invoice/{id}', [OrderController::class, "invoice"])->where("id", "[0-9]+");
    $router->get('/validate-invoicing-data', [OrderController::class, "validateInvoicingData"]);
    
    $router->get('/firm-data', [UserController::class, "getFirmData"]);
    $router->post('/firm-data', [UserController::class, "firmDataUpdate"]);
    $router->get('/invoice-data', [UserController::class, "getInvoiceData"]);
    $router->post('/invoice-data', [UserController::class, "invoiceDataUpdate"]);
    
    $router->get('/notifications', [NotificationController::class, "list"]);
    $router->get('/notification/{id}', [NotificationController::class, "get"])->where("id", "[0-9]+");
    $router->put('/notification/read/{id}', [NotificationController::class, "setRead"])->where("id", "[0-9]+");
    
    // STATYSTYKI
    $router->get('/stats/user/{id}/daily', [StatsController::class, "userDaily"])->where("id", "[0-9]+");
    $router->get('/stats/user/{id}/monthly', [StatsController::class, "userMonthly"])->where("id", "[0-9]+");
    $router->get('/stats/project/{id}/daily', [StatsController::class, "projectDaily"])->where("id", "[0-9]+");
    $router->get('/stats/project/{id}/monthly', [StatsController::class, "projectMonthly"])->where("id", "[0-9]+");
    $router->get('/stats/task/{id}/daily', [StatsController::class, "taskDaily"])->where("id", "[0-9]+");
    $router->get('/stats/task/{id}/monthly', [StatsController::class, "taskMonthly"])->where("id", "[0-9]+");
    $router->get('/stats/total', [StatsController::class, "total"]);
    
    // SŁOWNIKI
    $router->get('/dictionary/types', [DictionaryController::class, "types"]);
    $router->get('/dictionaries', [DictionaryController::class, "list"]);
    $router->get('/dictionaries/{type}', [DictionaryController::class, "listByType"]);
    $router->put('/dictionary', [DictionaryController::class, "create"]);
    $router->get('/dictionary/{id}', [DictionaryController::class, "get"])->where("id", "[0-9]+");
    $router->put('/dictionary/{id}', [DictionaryController::class, "update"])->where("id", "[0-9]+");
    $router->delete('/dictionary/{id}', [DictionaryController::class, "delete"])->where("id", "[0-9]+");
    
    // REJESTR SPRZEDAŻY
    $router->get('/sale-register', [SaleRegisterController::class, "list"]);
    $router->put('/sale-register', [SaleRegisterController::class, "create"]);
    $router->get('/sale-register/{id}', [SaleRegisterController::class, "get"])->where("id", "[0-9]+");
    $router->put('/sale-register/{id}', [SaleRegisterController::class, "update"])->where("id", "[0-9]+");
    $router->delete('/sale-register/{id}', [SaleRegisterController::class, "delete"])->where("id", "[0-9]+");
    
    // FAKTURY
    $router->get('/customer-invoices', [CustomerInvoicesController::class, "list"]);
    $router->put('/customer-invoice', [CustomerInvoicesController::class, "create"]);
    $router->get('/customer-invoice/{id}', [CustomerInvoicesController::class, "get"])->where("id", "[0-9]+");
    $router->put('/customer-invoice/{id}', [CustomerInvoicesController::class, "update"])->where("id", "[0-9]+");
    $router->delete('/customer-invoice/{id}', [CustomerInvoicesController::class, "delete"])->where("id", "[0-9]+");
    $router->put('/customer-invoice/from-proforma/{pid}', [CustomerInvoicesController::class, "fromProforma"])->where("pid", "[0-9]+");
    $router->get('/customer-invoice/{id}/pdf', [CustomerInvoicesController::class, "getPdf"])->where("id", "[0-9]+");
    $router->get('/customer-invoice/number/{srid}', [CustomerInvoicesController::class, "getInvoiceNextNumber"])->where("srid", "[0-9]+");
    
    $router->get('/customer-invoice/settings', [CustomerInvoicesController::class, "settings"]);
    $router->put('/customer-invoice/settings', [CustomerInvoicesController::class, "settingsUpdate"]);

    // USUNIĘCIE KONTA
    $router->delete('removeAccount', [UserController::class, "removeAccount"]);
});

Route::prefix('v1')->middleware(['locale'])->group(function () use($router) {
    // REJESTRACJA
    $router->post("/register", [RegisterController::class, "register"]);
    $router->get("/register/confirm/{token}", [RegisterController::class, "get"]);
    $router->post("/register/confirm/{token}", [RegisterController::class, "confirm"]);
    
    // REJESTRACJA Z ZAPROSZENIA
    $router->get('/invite/{token}', [UserController::class, "inviteGet"]);
    $router->put('/invite/{token}', [UserController::class, "inviteConfirm"]);
    
    // LOGOWANIE / PRZYPOMNIENIE HASŁA
    $router->post("/login", [UserController::class, "login"]);
    $router->post("/forgot-password", [UserController::class, "forgotPassword"]);
    $router->get("/reset-password", [UserController::class, "resetPasswordGet"]);
    $router->post("/reset-password", [UserController::class, "resetPassword"]);
    $router->get("/get-email-firm-ids", [UserController::class, "getUserFirmIds"]);
    
    $router->get('/countries', [IndexController::class, "countries"]);
    $router->get('/packages-site', [IndexController::class, "packages"]);
});

