<?php

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
Auth::routes();

Route::group(['prefix' => 'WebServices'], function () {
    Route::match(['get','post'],'Signup', 'WebServices@usersignup');   
    Route::match(['get','post'],'Login', 'WebServices@userlogin');   	
    Route::match(['get','post'],'Forgot', 'WebServices@forget_password');   	
    Route::match(['get','post'],'UserProfile/{id}', 'WebServices@userprofile');   	
    Route::match(['get','post'],'userCards/{id}', 'WebServices@userCards');   	
    Route::match(['get','post'],'UpdateProfile/{id}', 'WebServices@updateprofile');   	
    Route::match(['post'],'updateProfilePic/{id}', 'WebServices@updateProfilePic');   	
    Route::match(['post'],'ResetPassword', 'WebServices@ResetPassword');   	
    Route::match(['get','post'],'ChgPwd/{id}', 'WebServices@change_password');   	
    Route::match(['get','post'],'AddCard/{id}', 'WebServices@addcard');
    Route::match(['get','post'],'AddCardWithType/{id}/{cardtype}', 'WebServices@addcardwithtype');   	
    Route::match(['get','post'],'AddPost/{id}', 'WebServices@addpost');   	
    Route::match(['get','post'],'GetPosts/{id}', 'WebServices@GetPosts');   	
    Route::match(['get','post'],'FileUpload', 'WebServices@FileUpload');   	
    Route::match(['get','post'],'ContactUs', 'WebServices@ContactUs');   	
    Route::match(['get','post'],'InviteFrnds', 'WebServices@InviteFrnds');   	
    Route::match(['get','post'],'shareTrip/{id}', 'WebServices@shareTrip');   
    Route::match(['get','post'],'editshareTrip/{id}', 'WebServices@editshareTrip');   	
	Route::match(['get','post'],'GetTrips/{id}', 'WebServices@GetTrips');  
	Route::match(['get','post'],'getTripDetail/{id}', 'WebServices@getTripDetail');  
	Route::match(['get','post'],'getTripCheckOutDetails/{id}/{uid}', 'WebServices@getTripCheckOutDetails');  
	Route::match(['get','post'],'getPostDetail/{id}', 'WebServices@getPostDetail');  
	Route::match(['post'],'buyTrip/{user_id}/{trip_id}', 'WebServices@buyTrip');  
	Route::match(['get'],'purchasedTrips/{user_id}', 'WebServices@purchasedTrips');  

/***** Admin Panel *************/
	Route::match(['get','post'],'adminLogin', 'WebServices@adminLogin'); 
	Route::match(['get','post'],'adminProfile', 'WebServices@adminProfile'); 
	Route::match(['get','post'],'UpdateAdminProfile', 'WebServices@adminProfile');   	
	Route::match(['get','post'],'UpdateAdminSetting', 'WebServices@UpdateAdminSetting');   	
	Route::match(['get','post'],'ChgAdminPwd', 'WebServices@ChgAdminPwd');  
	Route::match(['get','post'],'GetContacts', 'WebServices@GetContacts');  
	Route::match(['get','post'],'AddCat', 'WebServices@addCategory');  
	Route::match(['get','post'],'AddFaq', 'WebServices@AddFaq');  
	Route::match(['get','post'],'AddTesti', 'WebServices@AddTesti');  
	Route::match(['get','post'],'GetCats', 'WebServices@GetCat');  
	Route::match(['get','post'],'GetFaqs', 'WebServices@GetFaqs');  
	Route::match(['get','post'],'GetTesti', 'WebServices@GetTesti');  
	Route::match(['get','post'],'DelCat/{id}', 'WebServices@DelCat');  
	Route::match(['get','post'],'DelFaq/{id}', 'WebServices@DelFaq');  
	Route::match(['get','post'],'DelTesti/{id}', 'WebServices@DelTesti');  
	Route::match(['get','post'],'GetPage/{id}', 'WebServices@GetPage');  
	Route::match(['get','post'],'UpdatePage/{id}', 'WebServices@updatePage');  
	Route::match(['get','post'],'GetUsers', 'WebServices@GetUsers'); 
	Route::match(['get','post'],'GetUsersdetial/{id}/{user_id}', 'WebServices@GetUsersdetial');
	Route::match(['get','post'],'Blockeduser/{id}', 'WebServices@Blockeduser');
	Route::match(['get','post'],'Deleteusers/{id}', 'WebServices@Deleteusers');  
	Route::match(['get','post'],'Getcounttrip/{id}', 'WebServices@Getcounttrip');  
	Route::match(['get','post'],'Getcountposts/{id}', 'WebServices@Getcountposts');
	Route::match(['get','post'],'Getdashboard/', 'WebServices@Getdashboard');
	Route::match(['get','post'],'inviteget/{id}', 'WebServices@inviteget');
	Route::match(['get','post'],'inviteget2/{id}', 'WebServices@inviteget2');
	Route::match(['get','post'],'inviteget3/{id}', 'WebServices@inviteget3');
	Route::match(['get','post'],'myfirends/{id}', 'WebServices@myfirends');
	Route::match(['get','post'],'friendrequest/{id}', 'WebServices@friendrequest');
	Route::match(['get','post'],'mygallery/{id}', 'WebServices@mygallery');
	Route::match(['get','post'],'usersettings/{id}', 'WebServices@usersettings');
	Route::match(['get','post'],'getUserSettings/{id}', 'WebServices@getUserSettings');
	Route::match(['get','post'],'myactivity/{id}', 'WebServices@myactivity');
	Route::match(['get','post'],'friends_request_connect/{id}/{id2}', 'WebServices@friends_request_connect');
	Route::match(['get','post'],'suggested_friends_connect/{id}/{id2}', 'WebServices@suggested_friends_connect');
	Route::match(['get','post'],'deletecard/{uid}/{cid}', 'WebServices@deletecard');
	Route::match(['get','post'],'deletepost/{id}/{id2}', 'WebServices@deletepost');
	Route::match(['get','post'],'deletetrip/{id}/{id2}', 'WebServices@deletetrip');
	Route::match(['get','post'],'deletefriend/{id}/{id2}', 'WebServices@deletefriend');
	Route::match(['get','post'],'delete_friend_request/{id}/{id2}/{id3}', 'WebServices@delete_friend_request');
    Route::match(['get','post'],'getimg/{id}', 'WebServices@getimg');
    Route::match(['get','post'],'getfrendspost/{id}', 'WebServices@getfrendspost');
    Route::match(['get','post'],'like', 'WebServices@like');
    Route::match(['post'],'dislike', 'WebServices@dislike');
    Route::match(['post'],'comment', 'WebServices@comment');
    Route::match(['post'],'rating', 'WebServices@rating');
    Route::match(['post'],'like_unlike_review', 'WebServices@like_unlike_review');
    Route::match(['post'],'like_unlike_comment', 'WebServices@like_unlike_comment');
    Route::match(['get'],'getComments/{type}/{id}', 'WebServices@getComments');
    Route::match(['get'],'getFeed', 'WebServices@getFeed');
    Route::match(['get','post'],'buytrip/{id}', 'WebServices@buytrip');
    Route::match(['get'],'getNotisCount/{user_id}', 'WebServices@getNotisCount');
    Route::match(['get'],'get_notis/{user_id}', 'WebServices@get_notis');
    Route::match(['get'],'del_notis/{nid}', 'WebServices@del_notis');
    Route::match(['get'],'earning/{user_id}', 'WebServices@earning');
    Route::match(['get'],'my_draft/{user_id}', 'WebServices@my_draft');
    Route::match(['get'],'rating_list', 'WebServices@rating_list');
    Route::match(['get'],'buy_trip_users/{user_id}/{trip_id}', 'WebServices@buy_trip_users');
    Route::match(['post'],'askQuestion', 'WebServices@askQuestion');
    Route::match(['post'],'AddBankDetails/{user_id}', 'WebServices@AddBankDetails');
    Route::match(['post'],'delete_rating', 'WebServices@delete_rating');
    Route::match(['post'],'DeleteBankDetails', 'WebServices@DeleteBankDetails');
    Route::match(['get'],'graph_data', 'WebServices@graph_data');
    Route::match(['get'],'transactions', 'WebServices@get_transactions');
    Route::match(['post'],'verify', 'WebServices@verify');
    Route::match(['post'],'new', 'WebServices@new');
    
});

Route::get('/', function () {
    return view('welcome');
});
