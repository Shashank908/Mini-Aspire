<?php
/*
 *  
 */

Route::group(['middleware' => ['APIToken'], 'prefix' => 'v1'], function () {
    $controller = "\App\Components\CoreComponent\Modules\Repayment\RepaymentController";
    Route::get('/repayments/get/{id}', $controller . '@apiGetRepayment')->name('api.repayments.get');
    Route::get('/repayments/pay/{id}', $controller . '@apiPay')->name('api.repayments.pay');
});
