<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

// admin login
Route::get('admin/login', 'Admin\IndexController@index');
Route::post('admin/login', 'Admin\IndexController@login');

Route::group(['middleware' => 'admin', 'prefix' => 'admin'], function () {
    // admin dashboard
    Route::get('dashboard', 'Admin\IndexController@dashboard');

    // admin client
    Route::get('client', 'Admin\ClientController@index');
    Route::post('client', 'Admin\ClientController@add');
    Route::delete('client/{id}', 'Admin\ClientController@destroy');

    // admin user
    Route::get('user', 'Admin\UserController@index');
    Route::post('user', 'Admin\UserController@add');
});

// user login
// Route::get('/login', 'IndexController@login');
// Route::post('/login', 'IndexController@verify');

Route::group(['middleware' => 'user'], function () {
    // user profile
    // Route::get('/logout', 'IndexController@logout');
    Route::get('/profile', 'UserController@profile');
    Route::post('/profile', 'UserController@attribute');

});

// home
Route::get('/', function () {
    return view('welcome');
});

// OAuth 2
Route::post('oauth/access_token', function() {
    return Response::json(Authorizer::issueAccessToken());
});

// Route::get('oauth/authorize', ['as' => 'oauth.authorize.get', 'middleware' => ['check-authorization-params', 'auth'], function() {
Route::get('oauth/authorize', ['as' => 'oauth.authorize.get', 'middleware' => ['check-authorization-params'], function() {
    $authParams = Authorizer::getAuthCodeRequestParams();

    $formParams = array_except($authParams,'client');

    $formParams['client_id'] = $authParams['client']->getId();

    $formParams['scope'] = implode(config('oauth2.scope_delimiter'), array_map(function ($scope) {
        return $scope->getId();
    }, $authParams['scopes']));

    return View::make('oauth.authorization-form', ['params' => $formParams, 'client' => $authParams['client']]);
}]);

// Route::post('oauth/authorize', ['as' => 'oauth.authorize.post', 'middleware' => ['csrf', 'check-authorization-params', 'auth'], function() {
Route::post('oauth/authorize', ['as' => 'oauth.authorize.post', 'middleware' => ['csrf', 'check-authorization-params'], function() {

    $mobile = Request::get('mobile');
    $passwd = Request::get('passwd');
    $mobile = intval($mobile);
    $password = md5($passwd);

    $user = DB::table('users')->where('mobile', '=', $mobile)->where('password', '=', $password)->first();
    if (!$user)
    {
        return Response::json(array('error' => 'Invalid mobile or password input.'));
    }

    $params = Authorizer::getAuthCodeRequestParams();
    $params['user_id'] = 0;//Auth::user()->id;
    $redirectUri = 'http://gift.radision.biz';

    // If the user has allowed the client to access its data, redirect back to the client with an auth code.
    if (Request::has('approve')) {
        $redirectUri = Authorizer::issueAuthCode('user', $params['user_id'], $params);
    }

    // If the user has denied the client to access its data, redirect back to the client with an error message.
    if (Request::has('deny')) {
        $redirectUri = Authorizer::authCodeRequestDeniedRedirectUri();
    }
    return Redirect::to($redirectUri);
}]);

Route::auth();

Route::get('/home', 'HomeController@index');

Route::auth();

Route::get('/home', 'HomeController@index');
