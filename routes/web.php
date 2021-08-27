<?php

use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

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

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/test', [\App\Http\Controllers\Scraper::class, 'projects']);
Route::get('/test/submit/{id}', [\App\Http\Controllers\Scraper::class, 'submitInspection']);

Route::get('/auth/{provider}/redirect', function ($provider) {
    $enabledProvdiers = ['google', 'facebook', 'apple'];
    if(in_array($provider, $enabledProvdiers)) {
        return Socialite::driver($provider)->stateless()->redirect();
    }
});

Route::get('/auth/{provider}/callback', function ($provider) {
    $enabledProvdiers = ['google', 'facebook', 'apple'];
    if(in_array($provider, $enabledProvdiers)) {
        try {
            $user = Socialite::driver($provider)->stateless()->user();
        } catch (Exception $e) {
            return response()->json(['error' => 'Invalid credentials']);
        }
        $existingUser = Provider::where('provider_id', $user->getId())->first();
        if($existingUser) {
            $existingUser = $existingUser->user()->first();
        }
        if(! $existingUser) {
            $createUser = User::create([
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'password' => Hash::make(61),
                'settings' => User::DEFAULT_SETTINGS
            ]);
            if($createUser) {
                // Add the provider for this user so we can find them again with this account
                $provider = Provider::create([
                    'provider' => $provider,
                    'provider_id' => $user->getId(),
                    'user_id' => $createUser->id,
                    'avatar' => $user->getAvatar() ?? null
                ]);
                Auth::login($createUser);
                return redirect(env('SPA_URL'));
            }
        }
        // User exists, log them in
        Auth::login($existingUser);
        return redirect(env('SPA_URL'));
    }
    return back()->withErrors([
        'provider' => 'This provider is not supported.'
    ]);
});
