<?php
/*
 * 
 */

Route::group(['middleware' => ['APIToken'], 'prefix' => 'v1'], function () {
    $controller = "\App\Components\CoreComponent\Modules\Loan\LoanController";
    Route::post('/loans/create', $controller . '@apiCreateLoan')->name('api.loans.create');
    Route::get('/loans/get/{id?}', $controller . '@apiGetLoan')->name('api.loans.get');
    Route::get('/loans/get_freq_type', $controller . '@apiGetFreqType')->name('api.loans.get_freq_type');
});
