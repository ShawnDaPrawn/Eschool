<?php

use App\Http\Controllers\Panel\Money\SchoolBusinessPanelController;
use App\Http\Controllers\Panel\Money\SchoolSubscriptionInvoicePanelController;
use App\Http\Controllers\Panel\Money\SponsoredPlacesPanelController;
use App\Http\Middleware\PrivateDomainResponse;
use Illuminate\Support\Facades\Route;

// Included inside the existing authenticated, school-scoped panel route group.
Route::prefix('finance/subscription-invoices')->name('school-subscription-invoices.')->middleware(PrivateDomainResponse::class)->group(function () {
    Route::get('/', [SchoolSubscriptionInvoicePanelController::class, 'index'])->name('index');
    Route::get('{invoice}', [SchoolSubscriptionInvoicePanelController::class, 'show'])->whereNumber('invoice')->name('show');
});
Route::prefix('finance/bursaries')->name('bursary-panel.')->middleware([PrivateDomainResponse::class, 'module:bursaries,read'])->group(function () {
    Route::get('/', [SchoolBusinessPanelController::class, 'bursaries'])->name('index');
    Route::get('{application}', [SchoolBusinessPanelController::class, 'bursary'])->whereUuid('application')->name('show');
    Route::get('{application}/documents/{document}', [SchoolBusinessPanelController::class, 'document'])->whereUuid(['application', 'document'])->name('document');
    foreach (['note', 'decide', 'discount'] as $action) {
        Route::post('{application}/'.$action, [SchoolBusinessPanelController::class, 'bursaryWrite'])->whereUuid('application')->middleware('module:bursaries,write')->name($action);
    }
});
Route::prefix('finance/giving')->name('giving-panel.')->middleware([PrivateDomainResponse::class, 'module:giving,read'])->group(function () {
    Route::get('/', [SchoolBusinessPanelController::class, 'giving'])->name('index');
    Route::post('/', [SchoolBusinessPanelController::class, 'givingWrite'])->middleware('module:giving,write')->name('create');
    Route::get('receipts/{donation}', [SchoolBusinessPanelController::class, 'receipt'])->whereNumber('donation')->name('receipt');
    Route::get('{campaign}', [SchoolBusinessPanelController::class, 'campaign'])->whereNumber('campaign')->name('show');
    foreach (['close', 'pledge', 'donate'] as $action) {
        Route::post('{campaign}/'.$action, [SchoolBusinessPanelController::class, 'givingWrite'])->whereNumber('campaign')->middleware('module:giving,write')->name($action);
    }
});
Route::prefix('finance/sponsored-places')->name('sponsored-places.')->middleware(PrivateDomainResponse::class)->group(function () {
    Route::get('/', [SponsoredPlacesPanelController::class, 'index'])->name('index');
    Route::get('{subscription}', [SponsoredPlacesPanelController::class, 'show'])->whereNumber('subscription')->name('show');
    Route::post('{subscription}/learners', [SponsoredPlacesPanelController::class, 'add'])->whereNumber('subscription')->name('add');
    Route::get('{subscription}/learners/{learner}/release', [SponsoredPlacesPanelController::class, 'release'])->whereNumber(['subscription', 'learner'])->name('release');
    Route::post('{subscription}/learners/{learner}/remove', [SponsoredPlacesPanelController::class, 'remove'])->whereNumber(['subscription', 'learner'])->name('remove');
});
