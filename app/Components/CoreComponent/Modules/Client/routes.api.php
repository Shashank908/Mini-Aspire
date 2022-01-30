<?php
/*
 * 
 */

Route::group(['middleware' => ['APIToken'], 'prefix' => 'v1'], function () {
    $controller = "\App\Components\CoreComponent\Modules\Client\ClientController";
    Route::post('/clients/create', $controller . '@apiCreateClient')->name('api.clients.create');
    Route::get('/clients/get/{id?}', $controller . '@apiGetClient')->name('api.clients.get');
});
