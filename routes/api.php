<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
$component_path = app_path() . DIRECTORY_SEPARATOR . "Components";

if (\File::isDirectory($component_path)) 
{
    $list = \File::directories($component_path);
    foreach ($list as $module) 
    {
        if (\File::isDirectory($module)) 
        {
            if (\File::isFile($module . DIRECTORY_SEPARATOR . "routes.api.php")) 
            {
                require $module . DIRECTORY_SEPARATOR . "routes.api.php";
            }
        }
    }
}

Route::group(['prefix' => '/v1'], function () {
    $controller = "\App\Http\Controllers\AuthenticationController";
    Route::post('/register', $controller . '@postRegister');
    Route::post('/signin', $controller . '@postLogin');
});