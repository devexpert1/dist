<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

use DB;

class Common extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'mobile',
        'address',
        'picture',
        'gender',
        'latitude',
        'longitude',
        'status',
        'avatar',
        'social_unique_id',
        'license',
        'pvcertificate',
        'vpermit',
        'vinsurance',
        'vregistration',
        'carpic',
		'age',
		'description',
		'verify',
		'friend_email',
		'secrate',
		'friend_id',
		'user_id',
		'friend_id',
		'receive_friend_requests',
		'can_see_profile',
		'buy_trips',
		'email_notifications',
		'w_from',
		'from_id',
		'w_to',
		'to_id',
		'title',
		'description',
		'url',
		'tbl',
		'add_transportation',
		'comment'



    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'updated_at',
    ];
    
	
	public static function insertdata($table,$data){
		DB::table($table)->insert($data);
		return DB::getPdo()->lastInsertId();
	}	
	
	
	public static function updatedata($table,$data,$where){
		return DB::table($table)->where($where)->update($data);
	}	
	
	public static function selectdata($table,$where="",$order="",$offset="",$limit=""){
		if($where!="" && $order=="")
		$result = DB::table($table)->select('*')->where($where);
		else if(!empty($order) && $where!=""){
			if(!empty($order)){
				if (is_array($order)) {
				    foreach ($order as $key => $value) {
				        $order = $order[$key]; // or $v
				        break;
				    }
				}
					// list($key, $value) = each($order);					
					//$this->db->order_by();
				}
		$result = DB::table($table)->select('*')->where($where)->orderby($key,$value);
		}
		else 
		$result = DB::table($table)->select('*');
	
	if(is_numeric($offset)){
		$offset = $offset * 10; //die;
		$result = $result->skip($offset)->take(10);
	}
	
	return $result->get();
	}	

	public static function getSentRequests($table,$id){
		return DB::table($table)->select('to_id')->where('from_id', $id)->pluck('to_id')->toArray();
	}
	
	public static function getfirst($table,$where=""){
		if($where!="")
		return DB::table($table)->select('*')->where($where)->first();
		else 
		return DB::table($table)->select('*')->first();
	}
	
	
	public static function countdata($table,$where){
		return DB::table($table)->select('*')->where($where)->count();
	}		

	public static function deletedata($table,$data){
		return DB::table($table)->where($data)->delete();
	}
	
	public function getJoins($table1,$table2,$where,$fields){		
		 
	$data = DB::table($table1)
    ->join($table2, $where) //'cases.id', '=', 'contacts.id'
    ->selectRaw($fields)
    ->get();
	
	return $data;
	
	}

	public static function getJoins2($table1,$table2,$where){	

			$users = DB::table('tbl_users')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'tbl_users.uid','left')
			    ->where('tbl_users.status', '!=', '2')
			    ->select('tbl_users.*','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.birthDate','tbl_userdetails.img')
            ->get();

	
	return $users;
	
	}

	public static function getJoins2s($table1,$table2,$id){	

		$users = DB::table('tbl_users')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'tbl_users.uid','left')
			    ->where('tbl_users.status', '!=', '2')
			    ->where('tbl_users.uid', '=', $id)
			    ->select('tbl_users.*','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.birthDate','tbl_userdetails.img','tbl_userdetails.about')
            ->get();
	/*$users = DB::table('tbl_users')
            ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'tbl_users.uid','left')
            ->join('tbl_trips', 'tbl_trips.uid', '=', 'tbl_users.uid','left')
			 ->join('tbl_posts', 'tbl_posts.uid', '=', 'tbl_users.uid','left')
             ->where('tbl_users.uid', '=', $id)
           
          //  ->join('orders', 'users.id', '=', 'orders.user_id')
            ->select('tbl_users.*','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.birthDate','tbl_userdetails.img','tbl_userdetails.phone','tbl_userdetails.country','tbl_userdetails.about','tbl_posts.pid as post_id','tbl_posts.ptitle','tbl_posts.pdes','tbl_posts.tags','tbl_posts.photos','tbl_posts.created','tbl_posts.location','tbl_trips.tid as trip_id','tbl_trips.destination','tbl_trips.summary','tbl_trips.cover_letter','tbl_trips.travel_type','tbl_trips.ratings','tbl_trips.pictures','tbl_trips.start_date','tbl_trips.end_date','tbl_trips.created','tbl_trips.sell_price','tbl_trips.budget','tbl_trips.transportation')
            ->get(); */

	
	return $users;
	
	}

	public static function getJoinsIn2($table1,$table2,$id){	

		$users = DB::table('tbl_users')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'tbl_users.uid','left')
			    ->where('tbl_users.status', '!=', '2')
			    ->where('tbl_users.uid', '=', $id)
			    ->select('tbl_users.*','tbl_userdetails.*')
            ->get();
	return $users;
	
	}

	public static function getJoinsInCountry($country_id){	

		$users = DB::table('tbl_countries')
			    ->join('tbl_userdetails', 'tbl_userdetails.country', '=', 'tbl_countries.id','left')
			    ->where('tbl_countries.id', '=', $country_id)
			    ->select('tbl_userdetails.country','tbl_countries.name')
            ->first();
	return $users;
	
	}

	public static function getJoinsInUsers($table1,$table2,$id){	
		$users = DB::table('tbl_users')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'tbl_users.uid','left')
			    ->where('tbl_users.status', '!=', '2')
			    ->where('tbl_users.uid', '=', $id)
			    ->select('tbl_users.uid','tbl_users.username','tbl_users.uemail','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.birthDate','tbl_userdetails.img')
            ->first();
		return $users;
	}

	public static function getJoinsInUsersLike($table1,$table2,$user_ids){	
		$users = DB::table('tbl_users')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'tbl_users.uid','left')
			    ->where('tbl_users.status', '!=', '2')
			    ->whereIn('tbl_users.uid', $user_ids)
			    ->select('tbl_users.uid','tbl_users.username','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.birthDate','tbl_userdetails.img')
            ->get();
		return $users;
	}

	public static function getCommentUsers($table1,$table2,$post_trip_id,$type){	
		$users = DB::table('comment')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'comment.uid','left')
			    ->where('comment.post_trip_id', $post_trip_id)
			    ->where('comment.type', $type)
			    ->select('comment.id','comment.likes','comment.uid','comment.comment','comment.created','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.img')
			    ->orderBy('comment.id', 'desc')
            ->get();
		return $users;
	}

	public static function getReviewUsers($post_trip_id,$type){	
		$users = DB::table('ratings')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'ratings.uid','left')
			    ->where('ratings.post_trip_id', $post_trip_id)
			    ->where('ratings.type', $type)
			    ->select('ratings.id','ratings.uid','ratings.rating','ratings.reviews','ratings.created','ratings.likes','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.img')
			    ->orderBy('ratings.id', 'desc')
            ->get();
		return $users;
	}

	public static function getMyReviewUsers($user_id){	
		$users = DB::table('ratings')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'ratings.uid','left')
			    ->where('ratings.uid', $user_id)
			    ->select('ratings.rating','ratings.reviews','ratings.created','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.img')
			    ->orderBy('ratings.id', 'desc')
            ->get();
		return $users;
	}

	public static function check_my_buyer($trip_owner_id,$user_id){
		$result = DB::table('tbl_buy_trips')
					->where('trip_owner_id', $trip_owner_id)
			    	->where('user_id', $user_id)
                	->count();
        return $result;
	}

	public static function getReviewStars($post_trip_id,$type){
		$avg_stars = DB::table('ratings')
					->where('ratings.post_trip_id', $post_trip_id)
			    	->where('ratings.type', $type)
                	->avg('rating');
        return $avg_stars;
	}

	public static function getfriends($id){	
		$users = DB::table('tbl_users')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'tbl_users.uid','left')
			    ->where('tbl_users.status', '=', '0')
			    ->where('tbl_users.uid', '=', $id)
			    ->select('tbl_users.*','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.birthDate','tbl_userdetails.img')
            ->get();	
		return $users;
	}

	public static function getfriends2($id){	
			$users = DB::table('tbl_users')
				    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'tbl_users.uid','left')
				    ->join('friend_request', 'friend_request.to_id', '=', 'tbl_users.uid','left')
				   
				    ->where('tbl_users.status', '=', '0')
				    
				    //->where('friend_request.status', '!=', '')
				    ->where('tbl_users.uid', '=', $id)
                   //->orWhereNull('friend_request.status')
                   
				    //->whereNotIn('friend_request.status', '=', '2')
					
					//$join->on('friend_request.to_id', '=', 'tbl_users.uid');
				//	->where('friend_request.status','!=','2')
					//->where('friend_request.status','=', NULL)

				
				    
				    ->select('tbl_users.*','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.birthDate','tbl_userdetails.img','friend_request.status as request_status')
	            ->get();	
		return $users;
		
		}

	public static function getsuggested($id)
	{
		$users = DB::table('tbl_users')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'tbl_users.uid','left')
			    ->where('tbl_users.status', '=', '0')
			    ->where('tbl_users.uid', '=', $id)
               ->join('friend_request', 'friend_request.to_id', '!=', 'tbl_users.uid','left')
                ->where('friend_request.from_id', '=', $id)
			    ->select('tbl_users.*','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.birthDate','tbl_userdetails.img','friend_request.from_id')
            ->get();	
	return $users;
	}

	public static function getJoins3($table1,$table2,$where)
	{	

			$users = DB::table('tbl_users')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'tbl_users.uid','left')
			    ->where('tbl_users.status', '!=', '2')
			    ->select('tbl_users.*','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.country','tbl_userdetails.img')
            ->orderBy('tbl_users.uid', 'desc')->limit(5)->get();

	
	  return $users;
	
	}

	public static function getsugestifnotfound($id)
	{	

			$users = DB::table('tbl_users')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'tbl_users.uid','left')
			    ->where('tbl_users.status', '=', '0')
			    ->where('tbl_users.uid','!=',$id)
			    ->select('tbl_users.*','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.country','tbl_userdetails.img')
            ->orderBy('tbl_users.uid', 'desc')->get();

	
	  return $users;
	
	}

	public static function getnotifications($id)
	{	

			$users = DB::table('notification')
			    ->join('tbl_users', 'tbl_users.uid', '=', 'notification.to_id','left')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'tbl_users.uid','left')
               ->where('tbl_users.status', '!=', '2')
			    ->where('notification.status', '=', '0')
			    ->select('notification.*','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.country','tbl_userdetails.img')
            ->orderBy('tbl_users.uid', 'desc')->limit(5)->get();

	
	  return $users;
	
	}


	public static function findinset($id)
	{

		$query = DB::table('fiend_list')
		        ->join('tbl_users', 'tbl_users.uid', '=', 'fiend_list.user_id','left')
		         ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'fiend_list.user_id','left')
	         ->whereRaw('FIND_IN_SET("'.$id.'",fiend_list.friend_id)')
	         ->where('tbl_users.status', '=', '0')
              ->where('fiend_list.user_id', '!=', $id)
	         ->select('tbl_users.*','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.country','tbl_userdetails.img','fiend_list.user_id','fiend_list.friend_id')
	         ->get();
	          return $query;
     }

     public static function getfirendrequest($id)
	{

		$query = DB::table('friend_request')
		        ->join('tbl_users', 'tbl_users.uid', '=', 'friend_request.from_id','left')
		        ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'friend_request.from_id','left')
		         //->join('fiend_list', 'fiend_list.user_id', '=', 'friend_request.from_id','left')
	         ->where('friend_request.status', '!=', '2')
              ->where('friend_request.to_id', '=', $id)
              //->whereRaw('NOT FIND_IN_SET("'.$id.'",fiend_list.friend_id)')
	         ->select('tbl_users.*','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.country','tbl_userdetails.img','friend_request.id as request_id','friend_request.status')
	         ->get();
	          return $query;
     }

    public static function getMyFriendRequest($table,$id)
	{
		return DB::table($table)->select('from_id')->where('to_id', $id)->where('status', '0')->pluck('from_id')->toArray();
     }

    public static function getPostLikes($table,$id,$type)
	{
		return DB::table($table)->select('uid')->where('post_trip_id', $id)->where('type', $type)->pluck('uid')->toArray();
    }

    public static function getReviews($table,$id,$type)
	{
		return DB::table($table)->select('uid')->where('post_trip_id', $id)->where('type', $type)->pluck('uid')->toArray();
    }

	public static function selectdataposts()
	{

			$posts = DB::table('tbl_posts')
			    ->join('tbl_users', 'tbl_users.uid', '=', 'tbl_posts.uid','left')
			    ->where('tbl_users.status', '=', '0')
			    ->select('tbl_posts.*')
            ->get();
            return $posts;
	}

	public static function popular_packages($travel_type,$tid){
		$posts = DB::select("SELECT count(likes.id) as total_likes,trip.summary,trip.destination,trip.title,trip.tid,trip.cover_letter,trip.travel_type,trip.pictures FROM `likes` likes, `tbl_trips` trip  where likes.post_trip_id !=".$tid." and likes.type='trip' and trip.tid=likes.post_trip_id and trip.travel_type='".$travel_type."' and trip.is_draft='0' group by likes.post_trip_id order by total_likes desc limit 3");
        return $posts;
	}

	public static function get_user_rating($user_id){
		$rating = DB::select("SELECT AVG(rating) as rating FROM `ratings` where type = 'trip' and post_trip_id in (select tid from tbl_trips where uid = '".$user_id."')");
		if(isset($rating) && count($rating) > 0){
        	return $rating[0]->rating != null ?  round($rating[0]->rating,1) : 0;
    	}
    	else{
    		return 0;
    	}
	}

	public static function selectdatatrips()
	{

			$posts = DB::table('tbl_trips')
			    ->join('tbl_users', 'tbl_users.uid', '=', 'tbl_trips.uid','left')
			    ->where('tbl_users.status', '=', '0')
			    ->where('tbl_trips.is_draft', '=', '0')
			    ->select('tbl_trips.*')
            ->get();
            return $posts;
	}

	public static function getbycondition($tbl,$conditiion = '')
	{
	return $posts = DB::table($tbl)->where($conditiion)->orderBy('id', 'desc')->get();
	}

	public static function getbycondition2($tbl,$conditiion = '',$where='')
	{
		 $posts = DB::table($tbl)->where($conditiion);
		 
		 
			$posts = $posts->get();
			return $posts;
	}

	public static function getbycondition3($tbl,$conditiion = '',$where='')
	{
		 $posts = DB::table($tbl)->where($conditiion);
		 
		 
			$posts = $posts->get();
			return $posts;
	}

	public static function getdetailsuserret($tbl,$id,$field)
	{
	    $data= DB::table($tbl)->orderBy($id, 'desc')->first();
	    return $data->$field;
	}

	public static function total_earning($user_id)
	{
	    return DB::table('tbl_buy_trips')->where('trip_owner_id', $user_id)->sum('owner_amount');
	}

	public static function tripwise_earning($trip_id,$user_id)
	{
	    return DB::table('tbl_buy_trips')->where('trip_owner_id', $user_id)->where('trip_id', $trip_id)->sum('owner_amount');
	}

	public static function popular_travels()
	{
	    $data = DB::table('tbl_buy_trips')
                 ->select('trip_id', DB::raw('count(trip_id) as total_users'))
                 ->groupBy('trip_id')
                 ->orderBy('total_users','desc')
                 ->limit(4)
                 ->get();
        return $data;
	}

	public static function getdetailsuserret2($tbl,$conditiion,$field)
	{
	    $data= DB::table($tbl)->where($conditiion)->first();
	    return $data->$field;
	}

	public static function purchasedTrips($user_id){	
		$users = DB::table('tbl_buy_trips')
			    ->join('tbl_trips', 'tbl_trips.tid', '=', 'tbl_buy_trips.trip_id','left')
			    ->where('tbl_buy_trips.user_id', '=', $user_id)
			    ->select('tbl_trips.*','tbl_buy_trips.transaction_id','tbl_buy_trips.created_at','tbl_buy_trips.amount')
			    ->orderby('tbl_buy_trips.id','desc')
            ->get();
		return $users;
	}

	public static function my_notis($user_id){	
		$users = DB::table('notifications')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'notifications.from_id','left')
			    ->where('notifications.to_id', '=', $user_id)
			    ->select('notifications.*','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.img')
			    ->orderby('notifications.nid','desc')
            ->get();
		return $users;
	}

	public static function buy_trip_users($user_id,$trip_id){	
		$users = DB::table('tbl_buy_trips')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'tbl_buy_trips.user_id','left')
			    ->where('tbl_buy_trips.trip_owner_id', '=', $user_id)
			    ->where('tbl_buy_trips.trip_id', '=', $trip_id)
			    ->select('tbl_buy_trips.user_id','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.img')
            ->get();
		return $users;
	}

	public static function get_earning($user_id){
		$earning = DB::table('tbl_buy_trips')
                 ->select('trip_id', DB::raw('count(trip_id) as total_users'))
                 ->groupBy('trip_id')
                 ->where('trip_owner_id',$user_id)
                 ->get();
        return $earning;
	}

	public static function get_transactions(){
		$all_trans = DB::table('tbl_buy_trips')
                 ->join('tbl_userdetails as owner_user', 'owner_user.uid', '=', 'tbl_buy_trips.trip_owner_id','left')
                 ->join('tbl_userdetails as buyer_user', 'buyer_user.uid', '=', 'tbl_buy_trips.user_id','left')
                 ->join('tbl_users', 'tbl_users.uid', '=', 'tbl_buy_trips.user_id','left')
                 ->join('tbl_trips', 'tbl_trips.tid', '=', 'tbl_buy_trips.trip_id','left')
                 ->select('tbl_users.uemail as buyer_email','tbl_trips.title','tbl_trips.tid','buyer_user.firstName as buyer_firstName','buyer_user.lastName as buyer_lastName','owner_user.img as owner_img','tbl_buy_trips.amount','tbl_buy_trips.commision','tbl_buy_trips.owner_amount','tbl_buy_trips.transaction_id','tbl_buy_trips.created_at','owner_user.firstName as owner_firstName','owner_user.lastName as owner_lastName')
                 ->orderBy('tbl_buy_trips.id','desc')
                 ->get();
        return $all_trans;
	}

	public static function trip_info($trip_id){	
		$users = DB::table('tbl_trips')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'tbl_trips.uid','left')
			    ->join('tbl_users', 'tbl_users.uid', '=', 'tbl_userdetails.uid','left')
			    ->where('tbl_trips.tid', '=', $trip_id)
			    ->select('tbl_users.uemail','tbl_trips.title','tbl_trips.destination','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.img')
            ->first();
		return $users;
	}

	public static function rating_list(){	
		$ratings = DB::table('ratings')
			    ->join('tbl_userdetails', 'tbl_userdetails.uid', '=', 'ratings.uid','left')
			    ->join('tbl_trips', 'ratings.post_trip_id', '=', 'tbl_trips.tid','left')
			    ->where('ratings.type', '=', 'trip')
			    ->select('ratings.id','ratings.reviews','ratings.rating','tbl_trips.title','tbl_trips.tid','tbl_trips.destination','tbl_userdetails.firstName','tbl_userdetails.lastName','tbl_userdetails.img')
            ->get();
		return $ratings;
	}

	public static function select_total_earning(){
		return DB::table('tbl_buy_trips')->sum('amount');
	}

	public static function get_total_commission(){
		return DB::table('tbl_buy_trips')->sum('commision'); 
	}

	public static function get_graph_data($table){
		// $data= DB::table('tbl_trips')
		//      ->select(DB::raw('count(tid) as total'),DB::raw('month(created) as month'),DB::raw('year(created) as year'))
		//      ->where('created', '>=', 'DATE_SUB(CURDATE(), INTERVAL 6 MONTH)')
		//      ->groupBy('month','year')
		//      ->orderBy('year','desc')
		//      ->orderBy('month','desc')
		//     ->get();
		$posts = DB::select("select count(*) as total, month(created) as month, year(created) as year from ".$table." where `created` >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) group by `month`, `year` order by `year` asc, `month` asc");
        return $posts;
	}
}
