<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/keycloak', 'Keycloak@page');


Route::get('/demo', function () {
    return new App\Mail\IntelliFriendInvoice([]);
});

Route::get('send-mail', function () {
   
    $details = [
        'title' => 'IntelliFrind : Invoice for June & July 2020',
        'body' => 'This is for testing email using smtp'
    ];
    
    // rusdid@gmail.com
    \Mail::to('emdad.cuet@gmail.com')->send(new \App\Mail\IntelliFriendInvoice([]));
   
    dd("Email is Sent.");
});
