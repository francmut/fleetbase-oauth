<?php

use Fleetbase\FleetOps\Models\Contact;
use Fleetbase\Storefront\Http\Resources\Customer;
use Fleetbase\Storefront\Models\Network;
use Fleetbase\Storefront\Models\Store;
use Fleetbase\Storefront\Support\Storefront;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

Route::prefix(config('storefront.api.routing.prefix', 'storefront'))->group(
    function () {

        Route::prefix('v1')
            ->middleware('web')
            ->namespace('v1')
            ->group(function ($router) {

                $router->get('/auth/facebook', function() {
                    return Socialite::driver('facebook')->redirect();
                });

                $router->get('/auth/google', function() {
                    return Socialite::driver('google')->redirect();
                });

                $router->get('/auth/facebook/callback', function(Request $request) {

                    $error = $request->query('error');
                    if ($error) {
                        $error_url = trim(config('services.fleetbase.app_redirect')) . '/error/' . $error;
                        return redirect($error_url);
                    }

                    $facebookUser = Socialite::driver('facebook')->stateless()->user();

                    if (!$facebookUser ||  
                        !array_key_exists('email', $facebookUser->user) ||  
                        !array_key_exists('name', $facebookUser->user)) {
                            $error_url = trim(config('services.fleetbase.app_redirect')) . '/error/' . $error;
                            return redirect($error_url);
                    }

                    $key = config('services.fleetbase.storefront_key');

                    //
                    $user = Fleetbase\Models\User::firstOrCreate(
                        [
                            'email' => $facebookUser->user['email'],
                        ],
                        [
                            'name' => $facebookUser->user['name']
                        ]
                    );

                    // get the storefront or network login info
                    if (\Str::startsWith($key, 'store')) {
                        $about = $about = Store::select(['company_uuid'])->where('key', $key)->first();
                    } else {
                        $about = Network::select(['company_uuid'])->where('key', $key)->first();
                    }

                    // get contact record
                    $contact = Contact::firstOrCreate(
                        [
                            'user_uuid'    => $user->uuid,
                            'company_uuid' => $about->company_uuid,
                            'type'         => 'customer',
                        ],
                        [
                            'user_uuid'    => $user->uuid,
                            'company_uuid' => $about->company_uuid,
                            'name'         => $facebookUser->user['name'],
                            'phone'        => null,
                            'email'        => $facebookUser->user['email'],
                            'type'         => 'customer',
                        ]
                    );

                    // get auth token
                    try {
                        $token = $user->createToken($contact->uuid);
                    } catch (Exception $e) {
                        return response()->errors($e->getMessage());
                    }

                    $customer_id = Str::replaceFirst('contact', 'customer', $contact->public_id);

                    $app_url = trim(config('services.fleetbase.app_redirect')) . '/' . $token->plainTextToken . '/' . $customer_id;

                    return redirect($app_url);

                });

                $router->get('/auth/google/callback', function(Request $request) {

                    $error = $request->query('error');
                    if ($error) {
                        $error_url = trim(config('services.fleetbase.app_redirect')) . '/error/' . $error;
                        return redirect($error_url);
                    }

                    $googleUser = Socialite::driver('google')->stateless()->user();

                    if (!$googleUser ||  
                        !array_key_exists('email', $googleUser->user) ||  
                        !array_key_exists('name', $googleUser->user)) {
                            $error_url = trim(config('services.fleetbase.app_redirect')) . '/error/' . $error;
                            return redirect($error_url);
                    }

                    $key = config('services.fleetbase.storefront_key');

                    //
                    $user = Fleetbase\Models\User::firstOrCreate(
                        [
                            'email' => $googleUser->user['email'],
                        ],
                        [
                            'name' => $googleUser->user['name']
                        ]
                    );

                    // get the storefront or network login info
                    if (\Str::startsWith($key, 'store')) {
                        $about = $about = Store::select(['company_uuid'])->where('key', $key)->first();
                    } else {
                        $about = Network::select(['company_uuid'])->where('key', $key)->first();
                    }

                    // get contact record
                    $contact = Contact::firstOrCreate(
                        [
                            'user_uuid'    => $user->uuid,
                            'company_uuid' => $about->company_uuid,
                            'type'         => 'customer',
                        ],
                        [
                            'user_uuid'    => $user->uuid,
                            'company_uuid' => $about->company_uuid,
                            'name'         => $googleUser->user['name'],
                            'phone'        => null,
                            'email'        => $googleUser->user['email'],
                            'type'         => 'customer',
                        ]
                    );

                    // get auth token
                    try {
                        $token = $user->createToken($contact->uuid);
                    } catch (Exception $e) {
                        return response()->errors($e->getMessage());
                    }

                    $customer_id = Str::replaceFirst('contact', 'customer', $contact->public_id);

                    $app_url = trim(config('services.fleetbase.app_redirect')) . '/' . $token->plainTextToken . '/' . $customer_id;

                    return redirect($app_url);

                });

            });
    }
);

