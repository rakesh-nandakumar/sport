<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\ResourceController;
use App\Models\Indoor;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\userController;
use App\Http\Controllers\IndoorController;
use App\Http\Controllers\TournamentController;
use App\Livewire\PostComments;
use Livewire\Component;
use App\Models\Tournament;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });




Route::get('/', [IndoorController::class, 'index']);

//migrate
Route::get('/migrate', function () {
    \Artisan::call('migrate');
    return 'Migration complete';
});
//seed
Route::get('/seed', function () {
    \Artisan::call('db:seed');
    return 'Seeding complete';
});


//create form
Route::get('/home/create', [IndoorController::class, 'create'])->middleware('auth');

Route::post('/home', [IndoorController::class, 'store'])->middleware('auth');

//manage
Route::get('/home/manage', [IndoorController::class, 'manage']);


Route::get('/home/dashboard', [userController::class, 'index']);

Route::get('/home/{indoors}', [IndoorController::class, 'show']);



Route::prefix('admin')->group(function () {

         Route::get('/dashboard', function (){
             return view('dashboard-layout');
         });

         Route::put('/users/update/{users}',[userController::class, 'update']);

         Route::get('/view-users',[userController::class, 'index']);

         Route::get('/user/{users}/edit',[userController::class, 'edit']);

         Route::get('/analysis', [userController::class, 'analysis']);

         Route::get('/indoors/{indoors}', [IndoorController::class, 'adminShow']);
});

Route::post('comments', [CommentController::class, 'store']);

//edit form
Route::get('home/{indoors}/edit', [IndoorController::class, 'edit']);

//edit submit to update
Route::put('/home/{indoors}', [IndoorController::class, 'update']);


//delete
Route::delete('/home/{indoors}', [IndoorController::class, 'destroy']);


//form for booking
Route::middleware('auth')->group(function () {
    Route::get('/home/{indoors}/resources', [ResourceController::class, 'index'])->name('resources.index');
    Route::post('/home/{indoors}/resources', [ResourceController::class, 'store']);
    Route::put('/home/{indoors}/resources/{resource}', [ResourceController::class, 'update']);
    Route::delete('/home/{indoors}/resources/{resource}', [ResourceController::class, 'destroy']);

    Route::post('/home/{indoors}/book', [BookingController::class, 'store']);
    Route::post('/client/{indoors}/book', [BookingController::class, 'storeForOwner']);

    Route::post('/cancel-booking/{booking}', [BookingController::class, 'cancel'])->name('cancel-booking');
});

Route::get('/resources/{resource}/availability', [BookingController::class, 'availability'])->name('resource.availability');



//book from client dashboard


//Register form for customers
Route::get('/register/customer',[userController::class, 'cust'])->middleware('guest');

//store customer credentials
Route::post('/customer',[userController::class, 'custStore']);



//Register form
Route::get('/register',[userController::class, 'create'])->middleware('guest');

Route::post('/users',[userController::class, 'store']);
//php storm

  //login form
Route::get('/login',[userController::class, 'login'])->name('login')->middleware('guest');

//logout
Route::post('/logout',[userController::class, 'logout'])->middleware('auth');



//user update
//Route::put('/users/update/{users}',[userController::class, 'update']);


Route::post('/users/authenticate',[userController::class, 'authenticate']);





//user edit form
//Route::get('/user/{users}/edit',[userController::class, 'edit']);



//user delete
Route::delete('/user/{users}',[userController::class, 'destroy']);


//Route::get('/test', function () {
//    return view('test');
//})->middleware(['role:SuperAdministrator'])->get;

Route::middleware([
    'role:SuperAdministrator',
])->get('test', function (Request $request){
   return view('test');
});

//Route::post('/listings', 'ListingController@store')->middleware('auth'); //


Route::get('/tournament/create', [TournamentController::class, 'create']);

Route::get('/tournament/manage', [TournamentController::class, 'manage']);

//Tournament Edit form
Route::get('/tournament/{tournaments}/edit', [TournamentController::class, 'edit']);

//Tournament update
Route::put('/tournament/{tournaments}', [TournamentController::class, 'update']);

Route::post('/tournament', [TournamentController::class, 'store']);

Route::delete('/tournament/{tournament}', [TournamentController::class, 'destroy'])->name('tournament.destroy');


Route::get('/home/tournament/{tournaments}', [TournamentController::class, 'show']);
//manage

//Route to display customer history page
Route::get('/customer/history', [userController::class, 'history']);

Route::get("/notifications", function(Request $request){
    return view('notification');
});

Route::get('/clients/{indoors}', [IndoorController::class, 'ClientShow']);

Route::get('/client-dashboard', [userController::class, 'clientAnalysis']);


Route::post('/subscribe', [NewsletterController::class, 'subscribe']);

// common resourse routes

// index - show all listings
// show - show single listing
// create - show form to create listings
// store - sh=tore new listing
// edit - show form to edit listing
// update - update listings
// destroy - destro listing



// Route::get('/home', function () {
//     return view('home');
// });
