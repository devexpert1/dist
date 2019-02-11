<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Http\Response;
use App\Http\Requests;


use App\Common;


use Setting;
use Exception;
use Session;
use DB;

use View;
use Mail;

use Carbon\Carbon;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Password;


use Illuminate\Support\Facades\Input;
use Hash;
use Crypt;

use Validator;
use URL;
use Redirect;
use Stripe\Error\Card;
use Cartalyst\Stripe\Stripe;

class WebServices extends Controller
{	

	//use SendsPasswordResetEmails;

	public function __construct(){
			header("access-control-allow-origin: *");
	}

	public function index(){
		echo 'Not accessable!!';	
	}

	public function usersignup(Request $request){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if(!empty($request)){
		$check = \App\Common::countdata('tbl_users',array('uemail'=>$request['email']));
		if($check>0){
			$data['msg'] = 'Email already exist!!';
			$data['status'] = 2;
			$data['data'] = 'false';
		}else{
			$enter['username']=$request['username'];
			$enter['uemail']=$request['email'];
			$enter['upassword']= bcrypt($request['password1']);
			$enter['verify']=rand(0,999999);
			$last_id = \App\Common::insertdata('tbl_users',$enter);
			$user_details = array('uid' => $last_id,'firstName' => $request['username'],'dob' => $request['dob']);
			\App\Common::insertdata('tbl_userdetails',$user_details);
			$user_settings = array('uid' => $last_id, 'receive_friend_requests' => '3', 'can_see_profile' => '3', 'buy_trips' => '3', 'email_notifications' => '1' );
			\App\Common::insertdata('tbl_user_settings',$user_settings);

			$link = $request['API_URL'].'/#/Verify/'.$enter['verify'];
			$to = $request['email'];
			$subject = 'Registration';
			$view = 'emails.registration';
			$params = array(
				'to_name' => $request['username'],
				'link' => $link
			);
			try{
				$this->sendMail($view,['data' => $params],$to,$subject,$last_id);
				$data['msg'] = 'User registered successfully!!';
				$data['status'] = 1;
				$data['udata'] = 'true';
			}
			catch(Exception $e){
				$data['msg'] = $e->getMessage();
				$data['error'] = 'true';
				$data['status'] = 1;
				$data['udata'] = 'true';
			}
		}

		}else {
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}


	public function userlogin(Request $request){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
	
		$condition = [['uemail','=',$request['email']],['status','!=','2']];
		$check = \App\Common::getfirst('tbl_users',$condition);
		if(isset($check) && (!empty($check))){
			$pwd = $request['password'];
			if(Hash::check($pwd,$check->upassword)){
				if($check->status == '0'){
					$data['msg'] = 'Login successfully!!';
					$data['status'] = 1;
					$data['udata'] = $check;
				}
				else if($check->status == '1'){
					$data['msg'] = 'Your account hase been blocked';
					$data['status'] = 2;
					$data['udata'] = 'null';
				}
				else if($check->status == '3'){
					$data['msg']='account not confirmed.';
					$data['status'] = 3;
					$data['udata'] = 'null';
				}
				else{
					$data['msg']='';
					$data['status'] = 0;
					$data['udata'] = 'null';
				}
			}
			else{
				$data['msg'] = 'Invalid email or password!!';
				$data['status'] = 0;
				$data['udata'] = 'null';
			}
		}
		else{
			$data['msg']='Invalid email or password!!';
			$data['status'] = 0;
			$data['udata'] = 'null';
		}
		return json_encode($data);
	}
	
	public function userprofile(Request $request,$id){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if($id!="")
		{
			$check = \App\Common::getfirst('tbl_users',array('uid'=>$id));

			if(\App\Common::countdata('tbl_users',array('uid'=>$id))>0)
			{
				$check2 = \App\Common::getfirst('tbl_userdetails',array('uid'=>$id));
				$cards = \App\Common::selectdata('tbl_cards',array('uid'=>$id));
				$countries = \App\Common::selectdata('tbl_countries');
				$data['msg'] = 'Profile data';
				$data['status'] = 1;
				$data['udata'] = $check;
				$data['udata2'] = $check2;
				if(empty($data['udata2']))
				{
					$data['img'] = '';
				}else
				{
					$data['username'] = $check2->firstName.' '.$check2->lastName;
				}
				$data['cards'] = $cards;
				$data['countries'] = $countries;
			}
			else
			{
				$data['msg'] = 'Invalid UserId!!';
				$data['status'] = 0;
				$data['udata'] = 'null';
			}
			
		}
		else
		{
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}

	public function verify(Request $request){
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		$check = \App\Common::countdata('tbl_users',array('verify'=>$request['token']));
		if($check == 1){
			$update = \App\Common::updatedata('tbl_users',array('verify'=>null, 'status'=>'0'),array('verify'=>$request['token']));
			$data['msg']='verified';
			$data['status'] = 1;
			$data['data'] = 'null';
		}
		else{
			$data['msg']='invalid url';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}

	public function userCards(Request $request,$id){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if($id!="")
		{
			$check = \App\Common::getfirst('tbl_userdetails',array('uid'=>$id));
			$cards = \App\Common::selectdata('tbl_cards',array('uid'=>$id),array('cid'=>'desc'));

			$data['msg'] = 'Profile data';
			$data['username'] = $check->firstName.' '.$check->lastName;
			$data['routing_number'] = $check->routing_number;
			$data['account_number'] = $check->account_number;
			$data['account_country'] = $check->account_country;
			$data['account_currency'] = $check->account_currency;
			$data['bank_acc_id'] = $check->bank_acc_id;
			$data['about'] = $check->about;
			$data['status'] = 1;
			$data['cards'] = $cards;			
		}
		else
		{
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}
	
	public function forget_password(Request $request){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if(!empty($request['email'])){
			$check = \App\Common::getfirst('tbl_users',array('uemail' => $request['email'], 'status' => '0'));
			if(isset($check) && (!empty($check))){
				$verify = rand(0,999999);
				$link = $request['API_URL'];
				$to = $request['email'];
				$subject = 'Forgot password';
				$view = 'emails.password';
				$params = array(
					'to_name' => $check->username,
					'link' => $link.'#/ResetPassword/'.$verify
				);
				$this->sendMail($view,['data' => $params],$to,$subject,$check->uid);
				\App\Common::updatedata('tbl_users',array('verify' => $verify), array('uid' => $check->uid));
				$data['msg']='email sent';
				$data['status'] = 1;
				$data['data'] = 'null';
			}else{
				$data['msg']='Request data not found';
				$data['status'] = 0;
				$data['data'] = 'null';
			}
		}
		else{
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
    	return json_encode($data);
	}
	
	public function updateprofile(Request $request,$id)
	{
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if(!empty($request)){
			if(\App\Common::countdata('tbl_users',array('uid'=>$id))>0){

				if(\App\Common::countdata('tbl_users',array(array('uemail',$request['email']) , array('uid' ,'!=',$id)))==0){
				
				if(\App\Common::countdata('tbl_userdetails',array('uid'=>$id))>0){
			\App\Common::updatedata('tbl_userdetails',array('firstName'=>$request['firstName'],'lastName'=>$request['lastName'],'phone'=>$request['phone'],'dob'=>$request['birthDate'],'country'=>$request['country'],'about'=>$request['about'],'address'=>$request['address'],'facebook'=>$request['facebook'],'twitter'=>$request['twitter'],'google'=>$request['google'],'pinterest'=>$request['pinterest'],'instagram'=>$request['instagram'],'youtube'=>$request['youtube']),array('uid'=>$id));
				}else{
					
				\App\Common::insertdata('tbl_userdetails',array('uid'=>$id,'firstName'=>$request['firstName'],'lastName'=>$request['lastName'],'phone'=>$request['phone'],'dob'=>$request['birthDate'],'country'=>$request['country'],'about'=>$request['about'],'address'=>$request['address'],'facebook'=>$request['facebook'],'twitter'=>$request['twitter'],'google'=>$request['google'],'pinterest'=>$request['pinterest'],'instagram'=>$request['instagram'],'youtube'=>$request['youtube']));
							
				}
			$data['msg'] = 'Profile updated successfully!!';
			$data['status'] = 1;
			$check = \App\Common::getfirst('tbl_users',array('uid'=>$id));
			$check1 = \App\Common::getfirst('tbl_userdetails',array('uid'=>$id));
			$data['data'] = $check;
			$data['udata'] = $check1;
		}else{
			$data['msg'] = 'Email address already exist!!';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		}else{
			$data['msg'] = 'Invalid UserId!!';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
			
		}else{
				$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
		
	}

	public function updateProfilePic(Request $request,$id)
	{
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if(!empty($request)){
			\App\Common::updatedata('tbl_userdetails',array('img'=>$request['img']),array('uid'=>$id));
			$data['msg']='success';
			$data['status'] = 1;
			$data['data'] = 'null';
		}else{
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
		
	}
	
		public function change_password(Request $request,$id){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
	
		if(!empty($request['opassword']) && !empty($request['password1'])){
		$check = \App\Common::getfirst('tbl_users',array('uid'=>$id));
		$pwd = Hash::check($request['opassword'], $check->upassword);
		if($pwd==1){
			\App\Common::updatedata('tbl_users',array('upassword'=>bcrypt($request['password1'])),array('uid'=>$id));
			$data['msg'] = 'Password changed successfully!!';
			$data['status'] = 1;
			$data['data'] = $check;
		}else{
			$data['msg'] = 'Invalid old password!!';
			$data['status'] = 2;
			$data['data'] = 'null';
		}
		}else {
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}
	
	public function ResetPassword(Request $request){
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if(!empty($request['password']) && !empty($request['cpassword'])){
			$check = \App\Common::countdata('tbl_users',array('verify'=>$request['token']));
			if($check > 0){
				\App\Common::updatedata('tbl_users',array('upassword'=>bcrypt($request['password']), 'verify' => null),array('verify'=>$request['token']));
				$data['msg'] = 'Password changed successfully!!';
				$data['status'] = 1;
				$data['data'] = null;
			}
			else{
				$data['msg']='invalid url';
				$data['status'] = 2;
				$data['data'] = 'null';
			}
		}
		else{
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}
	
	public function addcard(Request $request,$id){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
	
		if(!empty($request['cardNumber']) && !empty($request['cvv'])){
		//$check = \App\Common::countdata('tbl_cards',array('cardNumber'=>$request['cardNumber']));
		if(\App\Common::countdata('tbl_cards',array('uid'=>$id,'cardNumber'=>$request['cardNumber']))==0){
			
			$enter['cardHolder']=$request['cardHolder'];
			$enter['cardNumber']=$request['cardNumber'];
			$enter['cDate']=$request['cDate'];
			$enter['cvv']= $request['cvv'];
			$enter['uid']= $id;
			\App\Common::insertdata('tbl_cards',$enter);			
			
			$data['msg'] = 'card added successfully!!';
			$data['status'] = 1;
			//$data['data'] = $check;
		}else{
			$data['msg'] = 'card already exist!!';
			$data['status'] = 2;
			$data['data'] = 'null';
		}
		}else {
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}

	public function addcardwithtype(Request $request,$id,$cardtype){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
	
		if(!empty($request['cardNumber']) && !empty($request['cvv'])){
		//$check = \App\Common::countdata('tbl_cards',array('cardNumber'=>$request['cardNumber']));
		if(\App\Common::countdata('tbl_cards',array('uid'=>$id,'cardNumber'=>$request['cardNumber']))==0){
			
			$enter['cardHolder']=$request['cardHolder'];
			$enter['cardNumber']=$request['cardNumber'];
			$enter['cDate']=$request['cDate'];
			$enter['cvv']= $request['cvv'];
			$enter['uid']= $id;
			$enter['cardType']= $cardtype;
			\App\Common::insertdata('tbl_cards',$enter);			
			
			$data['msg'] = 'card added successfully!!';
			$data['status'] = 1;
			//$data['data'] = $check;
		}else{
			$data['msg'] = 'card already exist!!';
			$data['status'] = 2;
			$data['data'] = 'null';
		}
		}else {
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}
	
	
	
	public function addpost(Request $request,$id){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
	
		if(!empty($request['ptitle']) && !empty($request['pdes'])){
		
			if(!empty($request['post_id']))
			{
				
					$enter['ptitle']= $request['ptitle'];
				
				
					$enter['pdes']= $request['pdes'];
				

				if(!empty($request['tags']))
				{
					$enter['tags']= serialize($request['tags']);
				}
				
					$enter['photos']= serialize($request['photos']);
				
				/*$enter['ptitle']=$request['ptitle'];
				$enter['pdes']=$request['pdes'];
				$enter['tags']= serialize($request['tags']);
				$enter['photos']= serialize($request['photos']);*/
				if(!empty($request['location']))
				{
					$enter['location']= $request['location'];
					$enter['lat']= $request['lat'];
					$enter['lng']= $request['lng'];
				}
		        \App\Common::updatedata('tbl_posts',$enter,array('pid'=>$request['post_id']));
				$data['msg'] = 'Post updated successfully!!';
				$data['status'] = 1;
			}else
			{
				
					$enter['ptitle']= $request['ptitle'];
				
				
					$enter['pdes']= $request['pdes'];
				

				if(!empty($request['tags']))
				{
					$enter['tags']= serialize($request['tags']);
				}
				
					$enter['photos']= serialize($request['photos']);
				
				/*$enter['ptitle']=$request['ptitle'];
				$enter['pdes']=$request['pdes'];
				$enter['tags']= serialize($request['tags']);
				$enter['photos']= serialize($request['photos']);*/

				$enter['uid']= $id;
				if(!empty($request['location']))
				{
					$enter['location']= $request['location'];
					$enter['lat']= $request['lat'];
					$enter['lng']= $request['lng'];
				}
				\App\Common::insertdata('tbl_posts',$enter);			

				$data['msg'] = 'Post added successfully!!';
				$data['status'] = 1;
			}
			
		
		}else {
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}
	
	public function FileUpload(Request $request){
		if(!empty($_FILES)){
			$data = []; 
			$path = (!empty($_POST['path'])?$_POST['path']:"");
			$target_dir = "uploads/".$path;
			$total = count($_FILES['fileKey']['name']);
			if(isset($_POST['random']) && $_POST['random'] != ''){
				$random = $_POST['random'];
			}
			else{
				$random = time();
			}
			
			// Loop through each file
			for( $i=0 ; $i < $total ; $i++ ) {
				$target_file = $target_dir . basename(str_replace(" ","_",$random.$_FILES["fileKey"]["name"][$i]));

				if (move_uploaded_file($_FILES["fileKey"]["tmp_name"][$i], $target_file)) {
					$data['imgs'][]  = basename(str_replace(" ","_",$random.$_FILES["fileKey"]["name"][$i]));
				} 
				
			}
			if(empty($data['imgs'])) {
				$data['imgs'] = "";
				$data['msg']  = "Sorry, there was an error uploading your file.";
				$data['status']  = 0;
			}else $data['status'] =1;
		
			return json_encode($data);
		}
		else{
			$data['imgs'] = "";
			$data['msg']  = "Sorry, there was an error uploading your file.";
			$data['status']  = 0;
			return json_encode($data);
		}		
	}
	

public function GetPosts(Request $request,$id){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if($id!=""){

			if(\App\Common::countdata('tbl_posts',array('uid'=>$id))>0){
			$posts = \App\Common::selectdata('tbl_posts',array('uid'=>$id),array('pid'=>'desc'));
			$user_info = \App\Common::getfirst('tbl_userdetails',array('uid'=>$id));
			$i=0;
			if(count($posts)>0){
			foreach($posts as $post){
				$narray[$i]['post_id'] = $post->pid;
				$narray[$i]['ptitle'] = $post->ptitle;
				$narray[$i]['pdes'] = $post->pdes;
				$narray[$i]['tags'] = unserialize($post->tags);
				$narray[$i]['photos'] = unserialize($post->photos);
				$narray[$i]['created'] = $post->created;
				$narray[$i]['location'] = $post->location;
				$likes = \App\Common::selectdata('likes',array('post_trip_id'=>$post->pid, 'type'=>'post'));
				if(count($likes) > 0)
				{ 
					$like_array = \App\Common::getPostLikes('likes',$post->pid,'post');
					$narray[$i]['like_array'] = $like_array;
                    $narray[$i]['like_users'] =  \App\Common::getJoinsInUsersLike('tbl_users','tbl_userdetails',$like_array);
				}
				else{
					$narray[$i]['like_users'] = [];	
					$narray[$i]['like_array'] = [];
				}
				$i++;
			}
			}
			$data['msg'] = 'posts data';
			$data['status'] = 1;
			$data['posts'] = $narray;
			$data['profile'] = $user_info;

		}else{
			$data['msg'] = 'Post not found!!';
			$data['status'] = 0;
			$data['udata'] = 'null';
			$data['profile'] = 'null';
		}
			
		}else{
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
			$data['profile'] = 'null';
		}
		return json_encode($data);
	}
	
	
public function ContactUs(Request $request){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if($request['email']!=""){

			$enter['email']=$request['email'];
			$enter['subject']=$request['subject'];
			$enter['message']=$request['message'];
			\App\Common::insertdata('tbl_contacts',$enter);			
			$data['msg'] = 'Data entered';
			$data['status'] = 1;
			
			
			$to = "rahuldindiit@gmail.com";
			$subject = "Contact Email";

			$message = "	<h2>The contact information is as following:</h2>
			<table>
			<tr>
			<th>Email</th>
			<th>".$enter['email']."</th>
			</tr>
			<tr>
			<td>Subject</td>
			<td>".$enter['subject']."</td>
			</tr>		
			<tr>
			<td>Message</td>
			<td>".$enter['message']."</td>
			</tr>
			</table>";

			// Always set content-type when sending HTML email
			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

			// More headers
			$headers .= 'From: <webmaster@example.com>' . "\r\n";
			//$headers .= 'Cc: myboss@example.com' . "\r\n";

			mail($to,$subject,$message,$headers);




		}else{
			$data['msg'] = 'Data not found!!';
			$data['status'] = 0;
			$data['udata'] = 'null';
		}
			
	
		return json_encode($data);
	}
	
	
	public function InviteFrnds(Request $request){
		$segments = explode('/',url(''));
		array_pop($segments);
		$segments = implode('/', $segments).'/#/Login/';
		
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		
		if(isset($request['id']) && !empty($request['id']))
		{
           $id = $request['id']; 
		}else
		{
			$id = '2'; 
		}



		if($request['emails']!=""){

			foreach($request['emails'] as $email){
				$were = [['uemail','=',$email['email']],['status','!=','2']];
				$users = \App\Common::selectdata('tbl_users',$were);
				if(count($users) > 0)
				{
					$were1 = [['user_id','=',$id]];
					$users2 = \App\Common::selectdata('fiend_list',$were1);
					$friend_id=array();
					foreach($users2 as $d)
					{
                       $friend_id = explode(',',$d->friend_id);
					}

					if (in_array($users[0]->uid, $friend_id))
					{
						$data['msg'] = 'User with '.$email["email"].' email is already your friend';
						$data['status'] = 0;
						$data['udata'] = 'null';

						return json_encode($data);
					
					}
					else
					{
				     $name = '';
					$hash    = $email['email'];
					$string  = $id."&".$hash;
					$iv = md5($string);
					$htmls = 'Friend Invitation from Worldvybe!, Please visit the following link given below:';
					$header = 'Friend Invitation from Worldvybe!';
					$buttonhtml = 'Accept';
					$buttonhtml2 = 'Decline';
					$pass_url  = $segments.'inviteget/'.$iv.'/login';
					$pass_url2  = $segments.'/inviteget2/'.$iv.'/login'; 
					$path = url('resources/views/email2.html');
					$email_path    = file_get_contents($path);
					$email_content = array('[name]','[pass_url]','[pass_url2]','[htmls]','[buttonhtml]','[buttonhtml2]','[header]');
					$replace  = array($name,$pass_url,$pass_url2,$htmls,$buttonhtml,$buttonhtml2,$header);
					$message = str_replace($email_content,$replace,$email_path);
					$subject = "Friend Invitation";
					$header = 'From: <webmaster@example.com>' . "\r\n";
					$header = "MIME-Version: 1.0\r\n";
					$header = "Content-type: text/html\r\n";
					$retval = mail($email['email'],$subject,$message,$header);
						$enter = array(
	                     'uid'=>$id,
	                     'friend_email'=>$email['email'],
	                     'friend_id'=>$users[0]->uid,
	                     'secrate'=>$iv,
						);
						\App\Common::insertdata('invite_friends',$enter);

						$enter2 = array(
	                     'from_id'=>$id,
	                     'to_id'=>$users[0]->uid,
	                     'status'=>'0'
						);
						$enter3 = array(
	                     'from_id'=>$id,
	                     'to_id'=>$users[0]->uid,
						);
						$check = \App\Common::selectdata('friend_request',$enter3);
						if(count($check) > 0)
						{
                          \App\Common::updatedata('friend_request',$enter2,$enter3);
						}else
						{
						 \App\Common::insertdata('friend_request',$enter2);	
						}
						
					}
					 
				 }else
				 {
				 	 $name = '';
					$hash    = $email['email'];
					$string  = $id."&".$hash."&".rand();
					$iv = md5($string);
					$htmls = 'Friend Invitation from Worldvybe!, Please visit the following link given below:';
					$header = 'Friend Invitation from Worldvybe!';
					$buttonhtml = 'Accept';
					$buttonhtml2 = 'Decline';
					$pass_url  = $segments.'inviteget3/'.$iv.'/login';
					$pass_url2  = $segments.'inviteget2/'.$iv.'/login';
					$path = url('resources/views/email2.html');
					$email_path    = file_get_contents($path);
					$email_content = array('[name]','[pass_url]','[pass_url2]','[htmls]','[buttonhtml]','[buttonhtml2]','[header]');
					$replace  = array($name,$pass_url,$pass_url2,$htmls,$buttonhtml,$buttonhtml2,$header);
					$message = str_replace($email_content,$replace,$email_path);
					$subject = "Friend Invitation";
					$header = 'From: <webmaster@example.com>' . "\r\n";
					$header = "MIME-Version: 1.0\r\n";
					$header = "Content-type: text/html\r\n";
					$retval = mail($email['email'],$subject,$message,$header);

					$enter = array(
                     'uid'=>$id,
                     'friend_email'=>$email['email'],
                     'secrate'=>$iv,
					);
					\App\Common::insertdata('invite_friends',$enter);
					// Code of signup amd friend inviation will be here
				 }


			

			 }
			 
			 
			 $data['msg'] = 'Emails sent';
			$data['data'] =$request['emails'];
			$data['status'] = 1;
			
			

		}else{
			$data['msg'] = 'Data not found!!';
			$data['status'] = 0;
			$data['udata'] = 'null';
		}
			
	
		return json_encode($data);
	}

	public function inviteget(Request $request,$id)
	{
		$check = \App\Common::selectdata('invite_friends',array('secrate'=>$id));
      if(count($check) > 0)
      {

           \App\Common::deletedata('invite_friends',array('secrate'=>$id));
            $enter3 = array(
			'user_id'=>$check[0]->uid,
			'friend_id'=>$check[0]->friend_id,
			);
		$enterdelte = array(
			'from_id'=>$check[0]->uid,
			'to_id'=>$check[0]->friend_id,
			);
			$check1 = \App\Common::selectdata('fiend_list',array('user_id'=>$check[0]->uid));

			if(count($check1) > 0)
			{
              $friends = explode(',',$check1[0]->friend_id);

				if (!in_array($check[0]->friend_id, $friends))
				{
				 array_push($friends,$check[0]->friend_id);
				 
				\App\Common::updatedata('fiend_list',array('friend_id'=>implode(',',$friends)),array('user_id'=>$check[0]->uid));
					$exists = \App\Common::selectdata('friend_request',$enterdelte);
					if(count($exists) > 0)
					{
						\App\Common::deletedata('friend_request',$enterdelte);
					}
				}
              
			}else
			{
              \App\Common::insertdata('fiend_list',$enter3);
				$exists = \App\Common::selectdata('friend_request',$enterdelte);
				if(count($exists) > 0)
				{
					\App\Common::deletedata('friend_request',$enterdelte);
				}
			}
            $enter4 = array(
			'friend_id'=>$check[0]->uid,
			'user_id'=>$check[0]->friend_id,
			);
            $check2 = \App\Common::selectdata('fiend_list',array('user_id'=>$check[0]->friend_id));
			if(count($check2) > 0)
			{
				$friends = explode(',',$check2[0]->friend_id);
				if (!in_array($check[0]->uid, $friends))
				{
				array_push($friends,$check[0]->uid);
				\App\Common::updatedata('fiend_list',array('friend_id'=>implode(',',$friends)),array('user_id'=>$check[0]->friend_id));	
				    $exists = \App\Common::selectdata('friend_request',$enterdelte);
					if(count($exists) > 0)
					{
						\App\Common::deletedata('friend_request',$enterdelte);
					}
				}
             
			}else
			{
              \App\Common::insertdata('fiend_list',$enter4);
                  $exists = \App\Common::selectdata('friend_request',$enterdelte);
					if(count($exists) > 0)
					{
						\App\Common::deletedata('friend_request',$enterdelte);
					}
			}

			$data['msg'] = 'Request accepted successfully!!';
			$data['status'] = 1;
			$data['udata'] = 'null';
      }else
      {
      	   $data['msg']='Error Link has been expierd';
			$data['status'] = 0;
			$data['data'] = 'null';
      }
		return json_encode($data); 
     }
	public function inviteget3(Request $request,$id)
	{
		$check = \App\Common::selectdata('invite_friends',array('secrate'=>$id));
      if(count($check) > 0)
      {
            $data['email'] = $check[0]->friend_email;
			$data['msg'] = 'signup form';
			$data['status'] = 1;
			$data['udata'] = 'null';
      }else
      {
      	   $data['msg']='Error Link has been expierd';
			$data['status'] = 0;
			$data['data'] = 'null';
      }
		return json_encode($data); 

	}

	public function inviteget2(Request $request,$id)
	{
		$data = [];
      $check = \App\Common::selectdata('invite_friends',array('secrate'=>$id));
      if(count($check) > 0)
      {
         \App\Common::deletedata('invite_friends',array('secrate'=>$id));
			$enter2 = array(
			'from_id'=>$check[0]->uid,
			'to_id'=>$check[0]->friend_id,
			'status'=>'2'
			);
			$enter3 = array(
			'from_id'=>$check[0]->uid,
			'to_id'=>$check[0]->friend_id,
			);
			$check = \App\Common::selectdata('friend_request',$enter3);
			if(count($check) > 0)
			{
			   \App\Common::updatedata('friend_request',$enter2,$enter3);
			}
			$data['msg']='Request delcine successfully!!';
			$data['status'] = 1;
			$data['data'] = 'null';
      }else
      {
      	   $data['msg']='Error Link has been expierd';
			$data['status'] = 0;
			$data['data'] = 'null';
      }
		return json_encode($data);

	}	
	
	public function shareTrip(Request $request,$id){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
	
		if(!empty($request['title'])){
		
			$enter['title']=$request['title'];
			$enter['destination']= isset($request['destination']) ? $request['destination'] : '';
			$enter['budget']=isset($request['budget']) ? $request['budget'] : '';
			$enter['people']=isset($request['people']) ? $request['people'] : 0;
			$enter['sell_price']=isset($request['sell_price']) ? $request['sell_price'] : '';
			$enter['cover_letter']=isset($request['cover_letter']) ? $request['cover_letter'] : '';
			$enter['summary']=isset($request['summary']) ? $request['summary'] : '';
			$enter['lat']=isset($request['lat']) ? $request['lat'] : '';
			$enter['lng']=isset($request['lng']) ? $request['lng'] : '';
			$enter['end_date']=isset($request['end_date']) ? $request['end_date'] : null;
			$enter['start_date']=isset($request['start_date']) ? $request['start_date'] : null;
			$enter['travel_type']=isset($request['travel_type']) ? $request['travel_type'] : '';
			$enter['ratings']= isset($request['ratings']) ? serialize($request['ratings']) : '';
			$enter['pictures']= isset($request['pictures']) ?  serialize(isset($request['pictures']) ? $request['pictures'] : '') : '';
			$enter['transportation']= isset($request['transportation']) ? serialize($request['transportation']) : '';
			$enter['is_draft']= isset($request['is_draft']) ? $request['is_draft'] : '0';
			if($enter['is_draft'] == '0'){
			$check_user_acc = \App\Common::getfirst('tbl_userdetails',array('uid'=>$id));

				if(isset($check_user_acc->bank_acc_id) && $check_user_acc->bank_acc_id != null && $check_user_acc->bank_acc_id != '' && $check_user_acc->bank_acc_id != undefined){
					$is_added_account = '1';
				}
				else{
					$is_added_account = '0';
				}
			}
			else{
				$is_added_account = '1';
			}

			if($is_added_account == '0'){
				$enter['is_draft'] = '1';	
			}
			
			$enter['uid']= $id;		
			$tid = \App\Common::insertdata('tbl_trips',$enter);		
			if($tid !='' && isset($request['day']))
			{
				foreach($request['day'] as $key=>$day)
				{
					$dayc = $key+1;
					$enter2['start_place']= isset($day['start_place']) ? $day['start_place'] : '';
					$enter2['destination_place']= isset($day['destination_place']) ? $day['destination_place'] : '';
					if(isset($day['hotel']))
					{
						$enter2['hotel']= serialize($day['hotel']);
					}
					if(isset($day['restautrant']))
					{
						$enter2['restautrant']= serialize($day['restautrant']);
					}
					if(isset($day['add_transportation']))
					{
						$enter2['add_transportation']= serialize($day['add_transportation']);
					}
					if(isset($day['images']))
					{
						$enter2['images']= serialize($day['images']);
					}
					
					
					$enter2['comment']= isset($day['comment']) ? $day['comment'] : '';
					$enter2['day']=$dayc;
					$enter2['trip_id']= $tid;
					\App\Common::insertdata('tbl_trip_days',$enter2);
				}
				
			}	
			if($is_added_account == '1'){
				$data['msg'] = 'Trip added successfully!!';
				$data['status'] = 1;
			}
			else{
				$data['msg'] = 'Trip saved to draft successfully!!';
				$data['status'] = 2;
			}
		
		}else {
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}

	public function editshareTrip(Request $request,$id)
	{
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
	
		if(!empty($request['title'])){
		
			
			$enter['title']=$request['title'];
			$enter['destination']=isset($request['destination']) ? $request['destination'] : '';
			$enter['budget']=isset($request['budget']) ? $request['budget'] : '';
			$enter['people']=isset($request['people']) ? $request['people'] : 0;
			$enter['sell_price']=isset($request['sell_price']) ? $request['sell_price'] : '';
			$enter['cover_letter']=isset($request['cover_letter']) ? $request['cover_letter'] : '';
			$enter['summary']=isset($request['summary']) ? $request['summary'] : '';
			$enter['lat']=isset($request['lat']) ? $request['lat'] : '';
			$enter['lng']=isset($request['lng']) ? $request['lng'] : '';
			$enter['end_date']= (!empty($request['end_date'])) ? $request['end_date'] : null;
			$enter['start_date']=(!empty($request['start_date'])) ? $request['start_date'] : null;
			$enter['travel_type']=isset($request['travel_type']) ? $request['travel_type'] : '';
			$enter['ratings']= isset($request['ratings']) ? serialize($request['ratings']) : '';
			$enter['pictures']= serialize(isset($request['pictures']) ? $request['pictures'] : '');
			$enter['transportation']= isset($request['transportation']) ? serialize($request['transportation']) : '';
			$enter['is_draft']= $request['is_draft'];
			
			//$enter['uid']= $id;	
			if($enter['is_draft'] == '0'){
			$check_user_acc = \App\Common::getfirst('tbl_userdetails',array('uid'=>$request['uid']));

				if(isset($check_user_acc->bank_acc_id) && $check_user_acc->bank_acc_id != null && $check_user_acc->bank_acc_id != ''){
					$is_added_account = '1';
				}
				else{
					$is_added_account = '0';
				}
			}
			else{
				$is_added_account = '1';
			}	

			if($is_added_account == '0'){
				$enter['is_draft'] = '1';	
			}
			
			\App\Common::updatedata('tbl_trips',$enter,array('tid'=>$id));
			$posttrip = \App\Common::selectdata('tbl_trip_days',array('trip_id'=>$id));	
			\App\Common::deletedata('tbl_trip_days',array('trip_id'=>$id));
				
			if($id !='' && isset($request['day']))
			{
				foreach($request['day'] as $key=>$day)
				{
					$dayc = $key+1;
					$enter2['start_place']=isset($day['start_place']) ? $day['start_place'] : '';
					$enter2['destination_place']=isset($day['destination_place']) ? $day['destination_place'] : '';
					$enter2['hotel']= serialize($day['hotel']);
					$enter2['restautrant']= serialize($day['restautrant']);
					$enter2['add_transportation']= serialize($day['add_transportation']);
					$enter2['images']= serialize($day['images']);
					$enter2['comment']=$day['comment'];
					$enter2['day']=$dayc;
					$enter2['trip_id']= $id;
					\App\Common::insertdata('tbl_trip_days',$enter2);
				}
				
			}	
			if($is_added_account == '1'){
				$data['msg'] = 'Trip updated successfully!!';
				$data['status'] = 1;
			}
			else{
				$data['msg'] = 'Trip saved to draft successfully!!';
				$data['status'] = 2;
			}
		
		}else {
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}

	
	
public function GetTrips(Request $request,$id){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if($id!=""){

			if(\App\Common::countdata('tbl_trips',array('uid'=>$id,'is_draft'=>'0'))>0){
			$posts = \App\Common::selectdata('tbl_trips',array('uid'=>$id, 'is_draft'=>'0'));
			$user_info = \App\Common::getfirst('tbl_userdetails',array('uid'=>$id));
			$i=0;
			if(count($posts)>0){
			foreach($posts as $post){
				$narray[$i]['tid'] = $post->tid;
				$narray[$i]['title'] = $post->title;
				$narray[$i]['destination'] = $post->destination;
				$narray[$i]['summary'] = $post->summary;
				$narray[$i]['ratings'] = unserialize($post->ratings);
				$narray[$i]['pictures'] = (unserialize($post->pictures) == false) ? [] : unserialize($post->pictures);
				$narray[$i]['transportation'] = unserialize($post->transportation);
				$narray[$i]['travel_type'] = $post->travel_type;
				$narray[$i]['created'] = \Carbon\Carbon::createFromTimeStamp(strtotime($post->created))->diffForHumans();;
				$narray[$i]['budget'] = $post->budget;
				$narray[$i]['sell_price'] = $post->sell_price;
				$narray[$i]['start_date'] = $post->start_date;
				$narray[$i]['end_date'] = $post->end_date;
				$narray[$i]['cover_letter'] = $post->cover_letter;
				$reviews = \App\Common::selectdata('ratings',array('post_trip_id'=>$post->tid, 'type'=>'trip'));
				if(count($reviews) > 0)
				{ 
		            $narray[$i]['reviews'] =  \App\Common::getReviewUsers($post->tid,'trip');
		            $review_stars =  \App\Common::getReviewStars($post->tid,'trip');
		            $narray[$i]['review_stars'] =  round($review_stars,1); 
		            $narray[$i]['review_array'] =  \App\Common::getReviews('ratings',$post->tid,'trip');
				}
				else{
					$narray[$i]['reviews'] = [];	
					$narray[$i]['review_stars'] = 0;
					$narray[$i]['review_array'] =  [];	
				}
				$i++;
			}
			}
			$data['msg'] = 'Trips data';
			$data['status'] = 1;
			$data['trips'] = $narray;
			$data['profile'] = $user_info;

		}else{
			$data['msg'] = 'Trip not found!!';
			$data['status'] = 0;
			$data['udata'] = 'null';
			$data['trips'] = [];
			$data['profile'] = 'null';
		}
			
		}else{
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
			$data['trips'] = [];
			$data['profile'] = 'null';
		}
		return json_encode($data);
	}
	
	
	
	
	
	/********Admin Panel **************/
	
	public function adminLogin(Request $request){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		 //$request['email']='priyankaindiit@gmail.com';
		 //$request['password']='12345678';		
		
		$check = \App\Common::countdata('tbl_admin',array('username'=>$request['username']));
		if(!empty($request) && $check>0){
			
		$check = \App\Common::getfirst('tbl_admin',array('username'=>$request['username']));

		$pwd = $request['password'];
			
		if(md5($pwd)==$check->password){
		//print_r(json_decode(json_encode($check),true)); die;
			$data['msg'] = 'Login successfully!!';
			$data['status'] = 1;
			$data['udata'] = $check;
		}else{
			$data['msg'] = 'Invalid email or password!!';
			$data['status'] = 0;
			$data['udata'] = 'null';
		}
		}else {
			$data['msg']='Invalid email or password!!';
			$data['status'] = 0;
			$data['udata'] = 'null';
		}
		return json_encode($data);
	}
	
	
	public function adminProfile(Request $request)
	{
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if(!empty($request)){
			if(\App\Common::countdata('tbl_admin',array('id'=>1))>0){

			\App\Common::updatedata('tbl_admin',array('fname'=>$request['fname'],'lname'=>$request['lname'],'phone'=>$request['phone'],'email'=>$request['email']),array('id'=>1));
			
			$data['msg'] = 'Admin Profile updated successfully!!';
			$data['status'] = 1;
			$check = \App\Common::getfirst('tbl_admin',array('id'=>1));
			$data['settings'] = \App\Common::selectdata('tbl_settings');

			$data['admin'] = $check;

		}else{
			$data['msg'] = 'Invalid UserId!!';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
			
		}else{
			$data['admin'] = \App\Common::getfirst('tbl_admin',array('id'=>1));
			$data['settings'] = \App\Common::selectdata('tbl_settings');
				$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
		
	}
	
	public function ChgAdminPwd(Request $request){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
	
		if(!empty($request['opassword']) && !empty($request['password1'])){
		$check = \App\Common::getfirst('tbl_admin',array('id'=>1));
		$pwd = md5($request['opassword']);
		if($pwd==$check->password){
			\App\Common::updatedata('tbl_admin',array('password'=>md5($request['password1'])),array('id'=>1));
			$data['msg'] = 'Password changed successfully!!';
			$data['status'] = 1;
			$data['data'] = $check;
		}else{
			$data['msg'] = 'Invalid old password!!';
			$data['status'] = 2;
			$data['data'] = 'null';
		}
		}else {
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}
	
	
	public function UpdateAdminSetting(Request $request)
	{
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if(!empty($request)){		

			foreach($request as $key=>$val)	
			{
				\App\Common::updatedata('tbl_settings',array('meta_value'=>$val),array('meta_name'=>$key));
			}
			
			$data['msg'] = 'Admin settings updated successfully!!';
			$data['status'] = 1;
			//$check = \App\Common::getfirst('tbl_settings',array('id'=>1));
			$data['data'] = $request;
			
			
		}else{
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
		
	}
	
	public function GetContacts(Request $request){
		
		$data['contacts'] = \App\Common::selectdata('tbl_contacts');
		$data['msg']='Contacts';
		$data['status'] = 1;
		$data['data'] = 'null';

		return json_encode($data);
		
		
	}
	
	public function addCategory(Request $request){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
	
		if(!empty($request['catname'])){
		//$check = \App\Common::countdata('tbl_cards',array('cardNumber'=>$request['cardNumber']));
		if(\App\Common::countdata('tbl_categories',array('catname'=>$request['catname']))==0){
			
			$enter['catname']=$request['catname'];
		
		if(empty($request['catid'])){
			$data['dtype'] = 'in';
			\App\Common::insertdata('tbl_categories',$enter);	
		}else{
			$data['dtype'] = 'up';
			\App\Common::updatedata('tbl_categories',$enter,array('id'=>$request['catid']));			
		}
			
			$data['msg'] = 'Category updated successfully!!';
			$data['status'] = 1;
			//$data['data'] = $check;
		}else{
			$data['msg'] = 'Category already exist!!';
			$data['status'] = 2;
			$data['data'] = 'null';
		}
		}else {
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}
	
	
	public function AddFaq(Request $request){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
	
		if(!empty($request['question'])){
		//$check = \App\Common::countdata('tbl_cards',array('cardNumber'=>$request['cardNumber']));
		if(\App\Common::countdata('tbl_faqs',array('question'=>$request['question']))==0){
			
			$enter['question']=$request['question'];
			$enter['answer']=$request['answer'];
		
		if(empty($request['catid'])){
			$data['dtype'] = 'in';
			\App\Common::insertdata('tbl_faqs',$enter);	
		}else{
			$data['dtype'] = 'up';
			\App\Common::updatedata('tbl_faqs',$enter,array('id'=>$request['catid']));			
		}
			
			$data['msg'] = 'Faq updated successfully!!';
			$data['status'] = 1;
			//$data['data'] = $check;
		}else{
			$data['msg'] = 'Faq already exist!!';
			$data['status'] = 2;
			$data['data'] = 'null';
		}
		}else {
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}
	
	
	public function AddTesti(Request $request){
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
	
		if(!empty($request['clientName'])){
		//$check = \App\Common::countdata('tbl_cards',array('cardNumber'=>$request['cardNumber']));
		if(\App\Common::countdata('tbl_testimonials',array('testimonial'=>$request['testimonial']))==0){
			
			$enter['testimonial']=$request['testimonial'];
			$enter['clientName']=$request['clientName'];
			$enter['img']=$request['img'];
		
		if(empty($request['catid'])){
			$data['dtype'] = 'in';
			\App\Common::insertdata('tbl_testimonials',$enter);	
		}else{
			$data['dtype'] = 'up';
			\App\Common::updatedata('tbl_testimonials',$enter,array('id'=>$request['catid']));			
		}
			
			$data['msg'] = 'testimonial updated successfully!!';
			$data['status'] = 1;
			//$data['data'] = $check;
		}else{
			$data['msg'] = 'testimonial already exist!!';
			$data['status'] = 2;
			$data['data'] = 'null';
		}
		}else {
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}
	
		public function GetCat(Request $request){
		
		$data['cats'] = \App\Common::selectdata('tbl_categories');
		$data['msg']='Categories';
		$data['status'] = 1;
		$data['data'] = 'null';

		return json_encode($data);
		
		
	}			
	
	
	public function GetFaqs(Request $request){
		
		$data['faqs'] = \App\Common::selectdata('tbl_faqs');
		$data['msg']='Faqs';
		$data['status'] = 1;
		$data['data'] = 'null';

		return json_encode($data);
		
		
	}		
	
	public function GetTesti(Request $request){
		
		$data['testi'] = \App\Common::selectdata('tbl_testimonials');
		$data['msg']='Faqs';
		$data['status'] = 1;
		$data['data'] = 'null';

		return json_encode($data);
		
		
	}		
	
	public function DelCat(Request $request,$id){
		
		$data['cats'] = \App\Common::deletedata('tbl_categories',array('id'=>$id));
		$data['msg']='catgory deleted!';
		$data['status'] = 1;
		$data['data'] = 'null';

		return json_encode($data);
		
		
	}		
	
	public function DelFaq(Request $request,$id){
		
		$data['cats'] = \App\Common::deletedata('tbl_faqs',array('id'=>$id));
		$data['msg']='Faq deleted!';
		$data['status'] = 1;
		$data['data'] = 'null';

		return json_encode($data);
		
		
	}

	public function delete_rating(Request $request){
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		$data['cats'] = \App\Common::deletedata('ratings',array('id'=>$request['id']));
		$data['msg']='review deleted!';
		$data['status'] = 1;
		$data['data'] = 'null';

		return json_encode($data);
		
		
	}	
	
	public function DelTesti(Request $request,$id){
		
		$data['cats'] = \App\Common::deletedata('tbl_testimonials',array('id'=>$id));
		$data['msg']='testimonial deleted!';
		$data['status'] = 1;
		$data['data'] = 'null';

		return json_encode($data);
		
		
	}	
	
	public function GetPage(Request $request,$id){
		
		$data['page'] = \App\Common::getfirst('tbl_pages',array('id'=>$id));
		$data['msg']='catgory deleted!';
		$data['status'] = 1;
		$data['data'] = 'null';

		return json_encode($data);
		
		
	}
	
	
	public function updatePage(Request $request,$id)
	{
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if(!empty($request)){
			if(\App\Common::countdata('tbl_pages',array('id'=>$id))>0){

			\App\Common::updatedata('tbl_pages',array('title'=>$request['title'],'description'=>$request['description'],'img'=>$request['img']),array('id'=>$id));
			
			$data['msg'] = 'page updated successfully!!';
			$data['status'] = 1;
			
			}
			
		}else{
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
		
	}
	
	
	public function GetUsers(Request $request){
		//$data['users'] = \App\Common::selectdata('tbl_users');
		$were = [['tbl_users.uid','=','tbl_userdetails.uid']];
		$array = array('tbl_userdetails.*','tbl_users.*');
		$data['users'] = \App\Common::getJoins2('tbl_users','tbl_userdetails',$were);
		foreach($data['users'] as $key=>$get)
		{

			$count = \App\Common::selectdata('tbl_trips',array('uid'=>$get->uid, 'is_draft'=>'0'));
			$count2 = \App\Common::selectdata('tbl_posts',array('uid'=>$get->uid));
			$data['trips']= count($count);
			$data['posts']= count($count2);
			if($data['users'][$key]->uid == $get->uid)
			{    $data['users'][$key]->trips='';
			     $data['users'][$key]->trips=$data['trips'];
			     $data['users'][$key]->posts='';
			     $data['users'][$key]->posts=$data['posts'];
		    }
 
		}
		$data['msg']='All Users!';
		$data['status'] = 1;
		$data['data'] = 'null';
      
		return json_encode($data);
	}

	public function GetUsersdetial(Request $request,$id,$user_id){
		//$data['users'] = \App\Common::selectdata('tbl_users');
		
		$data['users'] = \App\Common::getJoinsIn2('tbl_users','tbl_userdetails',$id);
		$data['user_rating'] = \App\Common::get_user_rating($id);
		$data['country'] = \App\Common::getJoinsInCountry($data['users'][0]->country);
		$trips = \App\Common::selectdata('tbl_trips',array('uid'=>$id,'is_draft'=>'0'), array('tid'=> 'desc'));
		$posts = \App\Common::selectdata('tbl_posts',array('uid'=>$id),array('pid'=> 'desc'));
		$i=0;
		$narray = array();
			if(count($posts)>0){
			foreach($posts as $post){
				$narray[$i]['user'] =  \App\Common::getJoinsInUsers('tbl_users','tbl_userdetails',$post->uid);
				$narray[$i]['pid'] = $post->pid;
				$narray[$i]['ptitle'] = $post->ptitle;
				$narray[$i]['pdes'] = $post->pdes;
				$narray[$i]['uid'] = $post->uid;
				$narray[$i]['tags'] = unserialize($post->tags);
				if(is_array($post->tags))
				{
				$narray[$i]['tagscount'] = count(unserialize($post->tags));	
				}
				$narray[$i]['photos'] = unserialize($post->photos);
				$narray[$i]['created'] =  \Carbon\Carbon::createFromTimeStamp(strtotime($post->created))->diffForHumans(); 
				$narray[$i]['location'] = $post->location;
				// $likes = \App\Common::selectdata('likes',array('post_trip_id'=>$post->pid, 'type'=> 'post'));
				// if(count($likes) > 0)
				// { 
				// 	foreach($likes as $like)
				// 	{
    //                     $narray[$i]['like'] =  \App\Common::getJoins2s('tbl_users','tbl_userdetails',$like->uid); 
				// 	}
				// }

				// $comments = \App\Common::selectdata('comment',array('post_trip_id'=>$post->pid, 'type'=>'post'));
				// if(count($comments) > 0)
				// { 
				// 	foreach($comments as $comment)
				// 	{
    //                     $narray[$i]['comment'] =  \App\Common::getJoins2s('tbl_users','tbl_userdetails',$comment->uid); 
				// 	}
				// }

				$likes = \App\Common::selectdata('likes',array('post_trip_id'=>$post->pid, 'type'=>'post'));
					if(count($likes) > 0)
					{ 
						$like_array = \App\Common::getPostLikes('likes',$post->pid,'post');
						$narray[$i]['like_array'] = $like_array;
                        $narray[$i]['like_users'] =  \App\Common::getJoinsInUsersLike('tbl_users','tbl_userdetails',$like_array);
					}
					else{
						$narray[$i]['like_users'] = [];	
						$narray[$i]['like_array'] = [];
					}

				$comments = \App\Common::selectdata('comment',array('post_trip_id'=>$post->pid, 'type'=>'post'));
					if(count($comments) > 0)
					{ 
                        $narray[$i]['comments'] =  \App\Common::getCommentUsers('comment','tbl_userdetails',$post->pid,'post');
					}
					else{
						$narray[$i]['comments'] = [];	
					}

				$i++;
			}
			}

			$j=0;
			$narray2 = array();
			if(count($trips)>0){
			foreach($trips as $trip){
				$narray2[$j]['tid'] = $trip->tid;
				$narray2[$j]['title'] = $trip->title;
				$narray2[$j]['destination'] = $trip->destination;
				$narray2[$j]['summary'] = $trip->summary;
				$narray2[$j]['cover_letter'] = $trip->cover_letter;
				$narray2[$j]['travel_type'] = $trip->travel_type;
				$narray2[$j]['rating'] = unserialize($trip->ratings);
				$ratings = unserialize($trip->ratings);
				$total = array_sum($ratings);
                $rates = $total/count($ratings);
                $narray2[$j]['ratings'] = round($rates);
				$narray2[$j]['pictures'] = unserialize($trip->pictures);
				$narray2[$j]['start_date'] = $trip->start_date;
				$narray2[$j]['end_date'] = $trip->end_date;
				$narray2[$j]['created'] = \Carbon\Carbon::createFromTimeStamp(strtotime($trip->created))->diffForHumans(); 
				$narray2[$j]['sell_price'] = $trip->sell_price;
				$narray2[$j]['budget'] = $trip->budget;
				$narray2[$j]['transportation'] = unserialize($trip->transportation);
				$trips_days = \App\Common::selectdata('tbl_trip_days',array('trip_id'=>$trip->tid));
				if(count($trips_days) > 0)
				{ 
					foreach($trips_days as $tripday)
					{
						$enter2[$j]['day'] = $tripday->day;
						$enter2[$j]['trip_id'] = $tripday->trip_id;
						$enter2[$j]['start_place'] = $tripday->start_place;
						$enter2[$j]['destination_place'] = $tripday->destination_place;
						$enter2[$j]['comment'] = $tripday->comment;
						$enter2[$j]['hotel'] = unserialize($tripday->hotel);
						$enter2[$j]['restautrant'] = unserialize($tripday->restautrant);
						$enter2[$j]['restautrant'] = unserialize($tripday->add_transportation);
                        $enter2[$j]['images'] = unserialize($tripday->images);

					}
                     $narray2[$j]['days'] = $enter2;
				}
				$likes = \App\Common::selectdata('likes',array('post_trip_id'=>$trip->tid, 'type'=> 'trip'));
				if(count($likes) > 0)
				{ 
					foreach($likes as $like)
					{
                        $narray2[$j]['like'] =  \App\Common::getJoins2s('tbl_users','tbl_userdetails',$like->uid); 
					}
				}
				else{
					$narray2[$j]['like'] = [];
				}

				$comments = \App\Common::selectdata('comment',array('post_trip_id'=>$trip->tid, 'type'=>'trip'));
				if(count($comments) > 0)
				{ 
					foreach($comments as $comment)
					{
                        $narray2[$j]['comment'] =  \App\Common::getJoins2s('tbl_users','tbl_userdetails',$comment->uid); 
					}
				}
				else{
					$narray2[$j]['comment'] = [];
				}

				$reviews = \App\Common::selectdata('ratings',array('post_trip_id'=>$trip->tid, 'type'=>'trip'));
				if(count($reviews) > 0)
				{ 
		            $narray2[$j]['reviews'] =  \App\Common::getReviewUsers($trip->tid,'trip');
				}
				else{
					$narray2[$j]['reviews'] = [];	
				}
				$j++;
			}
			} 
		$data['post']= $narray;
		$data['trip']= $narray2;
		$data['msg']='Users!';
		$data['status'] = 1;
		$data['data'] = 'null';
		$data['can_see'] = $this->check_user_settings($id,$user_id,'1');
    //  echo '<pre>'; print_r($data); die; 
		return json_encode($data);
	}

	public function purchasedTrips($user_id){
		$all_trips = [];
		$trips = \App\Common::purchasedTrips($user_id);
		if(count($trips) > 0){
			foreach($trips as $trip){
				$narray['user'] =  \App\Common::getJoinsInUsers('tbl_users','tbl_userdetails',$trip->uid);
				$narray['type'] = 'trip';
				$narray['tid'] = $trip->tid;
				$narray['amount'] = $trip->amount;
				$narray['uid'] = $trip->uid;
				$narray['user'] = \App\Common::getJoinsInUsers('tbl_users','tbl_userdetails',$trip->uid);
				$narray['title'] = $trip->title;
				$narray['destination'] = $trip->destination;
				$narray['summary'] = $trip->summary;
				$narray['cover_letter'] = $trip->cover_letter;
				$narray['travel_type'] = $trip->travel_type;
	            $narray['ratings'] = unserialize($trip->ratings);
				$narray['pictures'] = unserialize($trip->pictures);
				$narray['start_date'] = $trip->start_date;
				$narray['end_date'] = $trip->end_date;
				$narray['created_at'] = $trip->created_at;
				$narray['transaction_id'] = $trip->transaction_id;
				$narray['sell_price'] = $trip->sell_price;
				$narray['budget'] = $trip->budget;
				$narray['transportation'] = unserialize($trip->transportation);
				$narray['search_terms'] = $trip->cover_letter.' '.$trip->destination.' '.$trip->summary.' '.$trip->travel_type.' '.$trip->sell_price;
				$likes = \App\Common::selectdata('likes',array('post_trip_id'=>$trip->tid, 'type'=>'trip'));
					if(count($likes) > 0)
					{ 
						$like_array = \App\Common::getPostLikes('likes',$trip->tid,'trip');
						$narray['like_array'] = $like_array;
	                    $narray['like_users'] =  \App\Common::getJoinsInUsersLike('tbl_users','tbl_userdetails',$like_array);
					}
					else{
						$narray['like_users'] = [];	
						$narray['like_array'] = [];
					}

				$comments = \App\Common::selectdata('comment',array('post_trip_id'=>$trip->tid, 'type'=>'trip'));
					if(count($comments) > 0)
					{ 
	                    $narray['comments'] =  \App\Common::getCommentUsers('comment','tbl_userdetails',$trip->tid,'trip');
					}
					else{
						$narray['comments'] = [];	
					}
				$reviews = \App\Common::selectdata('ratings',array('post_trip_id'=>$trip->tid, 'type'=>'trip'));
				if(count($reviews) > 0)
				{ 
		            $narray['reviews'] =  \App\Common::getReviewUsers($trip->tid,'trip');
		            $review_stars =  \App\Common::getReviewStars($trip->tid,'trip');
		            $narray['review_stars'] =  round($review_stars,1); 
		            $narray['review_array'] =  \App\Common::getReviews('ratings',$trip->tid,'trip');
				}
				else{
					$narray['reviews'] = [];	
					$narray['review_stars'] = 0;
					$narray['review_array'] =  [];	
				}
				array_push($all_trips, $narray);
				$narray = [];
			}
		}
		$data['status'] = 1;
		$data['data'] = $all_trips;
		return json_encode($data);
	}

	public function getPostDetail(Request $request,$id){
		$data['post'] = \App\Common::getfirst('tbl_posts',array('pid'=>$id));
		if(isset($data['post']->tags)){
			$data['post']->tags = (unserialize($data['post']->tags) == false) ? [] : unserialize($data['post']->tags);
		}

		if(isset($data['post']->photos)){
			$data['post']->photos = (unserialize($data['post']->photos) == false) ? [] : unserialize($data['post']->photos);
		}

		// get comments
		$comments = \App\Common::selectdata('comment',array('post_trip_id'=>$id, 'type'=>'post'));
		if(count($comments) > 0)
		{ 
            $data['post']->comments =  \App\Common::getCommentUsers('comment','tbl_userdetails',$id,'post');
		}
		else{
			$data['post']->comments = [];	
		}

		// get rating
		$reviews = \App\Common::selectdata('ratings',array('post_trip_id'=>$id, 'type'=>'post'));
		if(count($reviews) > 0)
		{ 
            $data['post']->reviews =  \App\Common::getReviewUsers($id,'post');
            $review_stars =  \App\Common::getReviewStars($id,'post');
            $data['post']->review_stars =  round($review_stars,1); 
            $data['post']->review_array =  \App\Common::getReviews('ratings',$id,'post');
		}
		else{
			$data['post']->reviews = [];	
			$data['post']->review_stars = 0;
			$data['post']->review_array =  [];	
		}

		$data['msg']='success';
		$data['status'] = 1;

		return json_encode($data);
	}

	public function getTripCheckOutDetails(Request $request,$id,$uid){
		$data['trip'] = \App\Common::getfirst('tbl_trips',array('tid'=>$id));
		if(isset($data['trip']->pictures)){
			$data['trip']->pictures = (unserialize($data['trip']->pictures) == false) ? [] : unserialize($data['trip']->pictures);
		}		
		$data['cards'] = \App\Common::selectdata('tbl_cards',array('uid'=>$uid));
		$data['countries'] = \App\Common::selectdata('tbl_countries');
		$data['msg']='success';
		$data['status'] = 1;
		return json_encode($data);
	}

	public function getTripDetail(Request $request,$id){
		$data['trip'] = \App\Common::getfirst('tbl_trips',array('tid'=>$id));
		$data['user'] = \App\Common::getJoinsInUsers('','',$data['trip']->uid);
		if(isset($data['trip']->pictures)){
			$data['trip']->pictures = (unserialize($data['trip']->pictures) == false) ? [] : unserialize($data['trip']->pictures);
		}
		if(isset($data['trip']->ratings)){
			$data['trip']->ratings = unserialize($data['trip']->ratings);
		}
		if(isset($data['trip']->transportation)){
			$data['trip']->transportation = unserialize($data['trip']->transportation);
		}
		// get rating
		$reviews = \App\Common::selectdata('ratings',array('post_trip_id'=>$id, 'type'=>'trip'));
		if(count($reviews) > 0)
		{ 
            $data['trip']->reviews =  \App\Common::getReviewUsers($id,'trip');
            $review_stars =  \App\Common::getReviewStars($id,'trip');
            $data['trip']->review_stars =  round($review_stars,1); 
            $data['trip']->review_array =  \App\Common::getReviews('ratings',$id,'trip');
		}
		else{
			$data['trip']->reviews = [];	
			$data['trip']->review_stars = 0;
			$data['trip']->review_array =  [];	
		}
		// get comments
		$comments = \App\Common::selectdata('comment',array('post_trip_id'=>$id, 'type'=>'trip'));
		if(count($comments) > 0)
		{ 
            $data['trip']->comments =  \App\Common::getCommentUsers('comment','tbl_userdetails',$id,'trip');
		}
		else{
			$data['trip']->comments = [];	
		}
		// get lat lng from address
     //    $prepAddr = str_replace(' ','+',$data['trip']->destination);
     //    $geocode=file_get_contents('https://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false&key=AIzaSyAdm5clS8knKWYs5eD56YNIof6QuKfJ1ac');
     //    $output= json_decode($geocode);
     //    if(isset($output->results) && count($output->results) > 0){
	    //     $data['trip']->lat = $output->results[0]->geometry->location->lat;
	    //     $data['trip']->lng = $output->results[0]->geometry->location->lng;
    	// }
    	//end 
		$data['trip']->duration = $this->getDateDiff($data['trip']->start_date,$data['trip']->end_date);

		$trip_days = \App\Common::selectdata('tbl_trip_days',array('trip_id'=>$id));

		$popular_packages = \App\Common::popular_packages($data['trip']->travel_type,$id);
		if(count($popular_packages)){
			$all_popular_packages = [];
			foreach ($popular_packages as $package) {
				$a['summary'] = $package->summary;
				$a['title'] = $package->title;
				$a['destination'] = $package->destination;
				$a['cover_letter'] = $package->cover_letter;
				$a['tid'] = $package->tid;
				$a['travel_type'] = $package->travel_type;
				$a['pictures'] = unserialize($package->pictures);
				array_push($all_popular_packages, $a);
				$a = [];
			}
			$data['popular_packages'] = $all_popular_packages;
		}
		else{
			$data['popular_packages'] = [];
		}

		if(count($trip_days) > 0){
			$all_days = [];
			$day = [];
			foreach ($trip_days as $single) {
				$day['day'] = $single->day;
				$day['start_place'] = $single->start_place;
				$day['destination_place'] = $single->destination_place;
				$day['comment'] = $single->comment;
				$day['trip_id'] = $single->trip_id;
				$day['images'] = (unserialize($single->images) == false) ? [] : unserialize($single->images);
				$day['hotel'] = (unserialize($single->hotel) == false) ? [] : unserialize($single->hotel);
				$day['restautrant'] = (unserialize($single->restautrant) == false) ? [] : unserialize($single->restautrant);
				$day['add_transportation'] = (unserialize($single->add_transportation) == false) ? [] : unserialize($single->add_transportation);
				$day['created'] = $single->created;
				// get lat lng from address
				if($day['destination_place'] != ''){
			        $prepAddr = str_replace(' ','+',$day['destination_place']);
			        $geocode=file_get_contents('https://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false&key=AIzaSyAdm5clS8knKWYs5eD56YNIof6QuKfJ1ac');
			        $output= json_decode($geocode);
			        if(isset($output->results) && count($output->results) > 0){
				        $day['lat'] = $output->results[0]->geometry->location->lat;
				        $day['lng'] = $output->results[0]->geometry->location->lng;
			    	}
		    	}
		    	//end
				array_push($all_days, $day);
				$day = [];
			}
			$data['trip_days'] = $all_days;
		}
		else{
			$data['trip_days'] = [];
		}

		$data['msg']='success';
		$data['status'] = 1;

		return json_encode($data);
	}

	public function getDateDiff($date1,$date2){
		$diff = abs(strtotime($date2) - strtotime($date1));
		$years = floor($diff / (365*60*60*24));
		$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
		$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
		$final_diff = (($years != 0) ? $years.' years' : '').(($months != 0) ? $months.' months' : '').(($days != 0) ? $days.' days' : '');
		return $final_diff;
	}

	public function Blockeduser(Request $request,$id){

		//$data['users'] = \App\Common::selectdata('tbl_users');	
		$user1 = \App\Common::selectdata('tbl_users',array('status'=>'1','uid'=>$id));
		$user2 = \App\Common::selectdata('tbl_users',array('status'=>'0','uid'=>$id));
		if(count($user1) > 0 )
		{
				if(\App\Common::updatedata('tbl_users',array('status'=>'0'),array('uid'=>$id)))
				{
					$data['msg']='User Unblocked Sccuessfully!';
					$data['status'] = 1;
					$data['data'] = 'null';
				}else
				{
					$data['msg']='Error to Unblocked user';
					$data['status'] = 0;
					$data['data'] = 'null';
				}
		}elseif(count($user2) > 0 )
		{
			if(\App\Common::updatedata('tbl_users',array('status'=>'1'),array('uid'=>$id)))
			{
				$data['msg']='User Blocked Sccuessfully!';
				$data['status'] = 1;
				$data['data'] = 'null';
			}else
			{
				$data['msg']='Error to Blocked user';
				$data['status'] = 0;
				$data['data'] = 'null';
			}
		}
		else
		{
		$data['msg']="Error user doesn't exits";
		$data['status'] = 0;
		$data['data'] = 'null';
		}
		
      
		return json_encode($data);
	}

	public function Deleteusers(Request $request,$id){
		//$data['users'] = \App\Common::selectdata('tbl_users');		
		if(\App\Common::updatedata('tbl_users',array('status'=>'2'),array('uid'=>$id)))
			{
				$data['msg']='User Deleted Sccuessfully!';
				$data['status'] = 1;
				$data['data'] = 'null';
			}else
			{
				$data['msg']='Error to Delete user';
				$data['status'] = 0;
				$data['data'] = 'null';
			}
		
      
		return json_encode($data);
	}

	public function Getcounttrip(Request $request,$id)
	{
	  $count = \App\Common::selectdata('tbl_trips',array('uid'=>$id,'is_draft'=>'0'));
				$data['trips']= count($count);
				$data['msg']='Trips counts!';
				$data['status'] = 1;
				$data['data'] = 'null';

		
      
		return json_encode($data);	
	}

	public function Getcountposts(Request $request,$id)
	{
	  $count = \App\Common::selectdata('tbl_posts',array('uid'=>$id));
				$data['posts']= count($count);
				$data['msg']='posts counts!';
				$data['status'] = 1;
				$data['data'] = 'null';

		
      
		return json_encode($data);	
	}

	public function Getdashboard(Request $request)
	{
		$were = [['tbl_users.uid','=','tbl_userdetails.uid']];
		$array = array('tbl_userdetails.*','tbl_users.*');
		$data['users'] = \App\Common::getJoins3('tbl_users','tbl_userdetails',$were);
		foreach($data['users'] as $key=>$get)
		{
             if(!empty($get->country))
             {
				$country = \App\Common::selectdata('tbl_countries',array('id'=>$get->country));
				$data['country']= $country[0]->nicename;
				if($data['users'][$key]->uid == $get->uid)
				{    $data['users'][$key]->country='';
				     $data['users'][$key]->country=$data['country'];
			    }
		   }
 
		}
        $were1 = [['status','!=','2']];
		$totalusers = \App\Common::selectdata('tbl_users',$were1);
		$totalposts = \App\Common::selectdataposts();
		$totaltrips = \App\Common::selectdatatrips();
		$data['totalearning'] = \App\Common::select_total_earning();
		$data['totalusers']= count($totalusers);
		$data['totalposts']= count($totalposts);
		$data['totaltrips']= count($totaltrips);
		$data['msg']='posts counts!';
		$data['status'] = 1;
		$data['data'] = 'null';

		
      
		return json_encode($data);
	}

	public function myfirends($id='')
	{
	     $check1 = \App\Common::selectdata('fiend_list',array('user_id'=>$id));
		if(count($check1) > 0)
		{
			$friends = explode(',',$check1[0]->friend_id);
			$data['myfriends'] = array();
			foreach($friends as $key=> $friend)
			{
	            $friends = \App\Common::getfriends($friend);
	            if(count($friends) > 0){
	            	$friends[0]->is_my_buyer = \App\Common::check_my_buyer($id,$friend);
	            	array_push($data['myfriends'], $friends[0]);
	        	}
			}
			if(count($data['myfriends']) > 0)
			{
              $data['totalfriends']= count($data['myfriends']);
			}else
			{
				$data['totalfriends']= '0';
			}
			
			$data['msg']='Friends List';
			$data['status'] = 1;
			$data['data'] = 'null';
	    }else
	    {
	    	$data['totalfriends']= '0';
	    	$data['myfriends'] = [];
	    	$data['msg']='No Friend Found';
			$data['status'] = 0;
			$data['data'] = 'null';
	    }
	      
			return json_encode($data);
	}

	public function friendrequest($id='')
	{
      $data['friendrequest']= \App\Common::getfirendrequest($id);
      $userss = \App\Common::getbycondition3('tbl_users',[['status','=','0'],['uid','!=',$id]]);
      if(count($data['friendrequest']) > 0)
      {
       $data['countfriendrequest']= count($data['friendrequest']);
      }else
      {
      	$data['countfriendrequest']='0';
      }
         
         $check1 = \App\Common::selectdata('fiend_list',array('user_id'=>$id));
 
		if(count($check1) > 0)
		{
			
			$friends = explode(',',$check1[0]->friend_id);
            $suggest = array();
            $i=0;
			foreach($userss as $user)
			{ 
				if($i < 6)
				{
					if(!in_array($user->uid, $friends))
					{
						$suggest[] = $user->uid;
						$i++;
					}	
				}
				
				
			}

			$data['suggested'] = array();
             if(count($suggest) > 0 )
             {
				foreach($suggest as $ids)
				{ 
			    	$sugst = \App\Common::getfriends2($ids);
			    	if($this->check_user_settings($ids,$id,'1') == 1){
			    		$sugst[0]->can_send = $this->check_user_settings_request($ids,$id);
			    		array_push($data['suggested'], $sugst[0]);
			    	}
				}
             }
			
			
		}else
		{
			if(count($data['friendrequest']) > 0)
			{
				  $sgustid = array();
					foreach($data['friendrequest'] as $getid)
					{
					  $sgustid[] = $getid->uid;
					}

						$suggest = array();
						$i=0;
						foreach($userss as $user)
						{ 
							if($i < 6)
							{
								if(!in_array($user->uid, $sgustid))
								{
									$suggest[] = $user->uid;
									$i++;
								}	
							}

						}

						$data['suggested'] = array();
						if(count($suggest) > 0 )
						{
						foreach($suggest as $ids)
						{ 
							$sugst = \App\Common::getfriends2($ids);
							if($this->check_user_settings($ids,$id,'1') == 1){
								$sugst[0]->can_send = $this->check_user_settings_request($ids,$id);
								array_push($data['suggested'], $sugst[0]);
							}
						}
					}
			}else
			{
			 $suggested_users = \App\Common::getsugestifnotfound($id);
			 $all_sugg = [];
			 if(count($suggested_users) > 0){
			 	foreach ($suggested_users as $suggest) {
			 		if($this->check_user_settings($suggest->uid,$id,'1') == 1){
			 			$suggest->can_send = $this->check_user_settings_request($suggest->uid,$id);
			 			array_push($all_sugg, $suggest);
			 		}
			 	}
			 }
			 $data['suggested'] = $all_sugg;
			}

		}

        if(count($data['suggested']) > 0)
        {
          $data['totalsuggested'] = count($data['suggested']);
        }else
        {
        	$data['totalsuggested'] ='0';
        }
        $data['coming_requests'] = \App\Common::getMyFriendRequest('friend_request',$id);
        $data['sent_requests']= \App\Common::getSentRequests('friend_request',$id);
		$data['msg']='friends request and suggested friends';
		$data['status'] = 1;
		$data['data'] = 'null';
		//echo '<pre>'; print_r($data); die; 
      	return json_encode($data);
	}

	public function mygallery(Request $request,$id='')
	{
     $data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if($id!=""){

			$posts = \App\Common::selectdata('tbl_posts',array('uid'=>$id));
			$i=0;
			$data['posts_pics']= array();
			if(count($posts)>0){
				$narray = array();
			    foreach($posts as $post){
				if(!empty($post->photos))
				{  
					$data['posts_pics'][$i]['photos'] = unserialize($post->photos);
				}
				$i++;
			}
			}

			$trips = \App\Common::selectdata('tbl_trips',array('uid'=>$id, 'is_draft'=>'0'));
			$j=0;
			if(count($trips)>0){
			foreach($trips as $trip){
				$narray2[$j]['pictures'] = unserialize($trip->pictures);
				$trips_days = \App\Common::selectdata('tbl_trip_days',array('trip_id'=>$trip->tid));
				if(count($trips_days) > 0)
				{ 
					foreach($trips_days as $tripday)
					{
                     $narray2[$j]['dayspictures'] = unserialize($tripday->images);
					}
				}else
				{
					$narray2[$j]['dayspictures']='';
				}
				$j++;
			}
			$data['trips_pics']=$narray2;
			}
		 else
		 {
		 	$data['trips_pics'] = array();
		 }
			$data['msg'] = 'Gallery data';
			$data['status'] = 1;
			$data['data'] = null;

		
			
		}else{
				$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}

	public function usersettings(Request $request,$id)
	{
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if(!empty($id)){
			if($request['receive_friend_requests']!=''){
			$enter['receive_friend_requests'] = $request['receive_friend_requests'];
			}if($request['can_see_profile']!=''){
			$enter['can_see_profile'] =$request['can_see_profile'];
			}if($request['buy_trips']!=''){
			$enter['buy_trips'] =$request['buy_trips'];
			}if($request['email_notifications']!=''){
			$enter['email_notifications'] = $request['email_notifications'];
			}
			$enter['uid'] = $id;			
			
			$settings = \App\Common::selectdata('tbl_user_settings',array('uid'=>$id));
			if(count($settings) > 0)
			{
               \App\Common::updatedata('tbl_user_settings',$enter,array('uid'=>$id));
			}else
			{
              \App\Common::insertdata('tbl_user_settings',$enter);
			}
			$data['msg']='Privacy Settings Updated Successfully!!';
			$data['status'] = 1;
			$data['data'] = 'null';
		}
		else{
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
		
	}

	public function getUserSettings(Request $request,$id)
	{
		$data = [];
		$postdata = file_get_contents("php://input");
		$request=json_decode($postdata,true);
		if(!empty($id)){
			$settings = \App\Common::getfirst('tbl_user_settings',array('uid'=>$id));
			$data['status'] = 1;
			$data['data'] = 'null';
			$data['settings'] = $settings;
		}
		else{
			$data['msg']='Request data not found';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		return json_encode($data);
	}

	public function myactivity($id='')
	{
       $data['post_trips'] = \App\Common::countdata('tbl_trips',array('uid'=>$id, 'is_draft'=>'0'));
	   $data['post_count'] = \App\Common::countdata('tbl_posts',array('uid'=>$id));
	   $data['total_earning'] = \App\Common::total_earning($id);
	   $data['reviews'] = \App\Common::getMyReviewUsers($id);

		$data['msg']='myactivity';
		$data['status'] = 1;
		$data['data'] = 'null';
		return json_encode($data);
	}

	public function friends_request_connect($id,$id2)
	{
     
			$check1 = \App\Common::selectdata('fiend_list',array('user_id'=>$id));
             $enter3 = array(
			'user_id'=>$id,
			'friend_id'=>$id2,
			);
			$enterdelte = array(
				'from_id'=>$id2,
				'to_id'=>$id,
				);
			$check46 = \App\Common::selectdata('fiend_list',$enter3);
			if(count($check46) == 0)
            {
			if(count($check1) > 0)
			{
              $friends = explode(',',$check1[0]->friend_id);

				if (!in_array($id2, $friends))
				{
				 array_push($friends,$id2);
				 
				\App\Common::updatedata('fiend_list',array('friend_id'=>implode(',',$friends)),array('user_id'=>$id));
					$exists = \App\Common::selectdata('friend_request',$enterdelte);
					if(count($exists) > 0)
					{
						\App\Common::deletedata('friend_request',$enterdelte);
					}
				}
              
			}else
			{
              \App\Common::insertdata('fiend_list',$enter3);
				$exists = \App\Common::selectdata('friend_request',$enterdelte);
				if(count($exists) > 0)
				{
					\App\Common::deletedata('friend_request',$enterdelte);
				}
			}
			// notification
			$notis = array(
		 		'from_id' => $id, 
		 		'to_id' => $id2, 
		 		'type' => '7', 
		 		'others' => ''
		 	);
		 	$this->notify($notis);
			$data['msg'] = 'Request accepted successfully!!';
			$data['status'] = 1;
			$data['udata'] = 'null';
		}else
		{
			$data['msg'] = 'Your are already a friend';
			$data['status'] = 1;
			$data['udata'] = 'null';
		}
            $enter4 = array(
			'friend_id'=>$id,
			'user_id'=>$id2,
			);
			 $check45 = \App\Common::selectdata('fiend_list',$enter4);
			if(count($check45) == 0)
            {
            $check2 = \App\Common::selectdata('fiend_list',array('user_id'=>$id2));
			if(count($check2) > 0)
			{
				$friends = explode(',',$check2[0]->friend_id);
				if (!in_array($id, $friends))
				{
				array_push($friends,$id);
				\App\Common::updatedata('fiend_list',array('friend_id'=>implode(',',$friends)),array('user_id'=>$id2));	
				    $exists = \App\Common::selectdata('friend_request',$enterdelte);
					if(count($exists) > 0)
					{
						\App\Common::deletedata('friend_request',$enterdelte);
					}
				}
             
			}else
			{
              \App\Common::insertdata('fiend_list',$enter4);
                  $exists = \App\Common::selectdata('friend_request',$enterdelte);
					if(count($exists) > 0)
					{
						\App\Common::deletedata('friend_request',$enterdelte);
					}
			}
		}

			
      
		return json_encode($data); 
	}

	public function suggested_friends_connect(Request $request,$id,$id2)
	{
		$segments = explode('/',url(''));
		array_pop($segments);
		$segments = implode('/', $segments).'/#/Login/';
		$were = [['uid','=',$id2],['status','!=','2']];
				$users = \App\Common::selectdata('tbl_users',$were);
				if(count($users) > 0)
				{
					$were1 = [['user_id','=',$id]];
					$users2 = \App\Common::selectdata('fiend_list',$were1);
					$friend_id=array();
					foreach($users2 as $d)
					{
                       $friend_id = explode(',',$d->friend_id);
					}

					if (in_array($users[0]->uid, $friend_id))
					{
						$data['msg'] = 'User with '.$users[0]->username.' email is already your friend';
						$data['status'] = 0;
						$data['udata'] = 'null';

						return json_encode($data);
					
					}
					else
					{
				     $name = '';
					$hash    = $users[0]->uemail;

					$link = $request['API_URL'];
					$to = $users[0]->uemail;
					$subject = 'Invite a friend';
					$view = 'emails.invite';
					$params = array(
						'to_name' => $users[0]->username,
						'from_name' => $request['from_user'],
						'link' => $link
					);

					$this->sendMail($view,['data' => $params],$to,$subject,$users[0]->uid);



					$string  = $id."&".$hash;
					$iv = md5($string);
					$htmls = 'Friend Invitation from Worldvybe!, Please visit the following link given below:';
					$header = 'Friend Invitation from Worldvybe!';
					$buttonhtml = 'Accept';
					$buttonhtml2 = 'Decline';
					$pass_url  = $segments.'inviteget/'.$iv.'/login';
					$pass_url2  = $segments.'/inviteget2/'.$iv.'/login'; 
					$path = url('resources/views/email2.html');
					$email_path    = file_get_contents($path);
					$email_content = array('[name]','[pass_url]','[pass_url2]','[htmls]','[buttonhtml]','[buttonhtml2]','[header]');
					$replace  = array($name,$pass_url,$pass_url2,$htmls,$buttonhtml,$buttonhtml2,$header);
					$message = str_replace($email_content,$replace,$email_path);
					$subject = "Friend Invitation";
					$header = 'From: <webmaster@example.com>' . "\r\n";
					$header = "MIME-Version: 1.0\r\n";
					$header = "Content-type: text/html\r\n";
					$retval = mail($users[0]->uemail,$subject,$message,$header);



						$enter = array(
	                     'uid'=>$id,
	                     'friend_email'=>$users[0]->uemail,
	                     'friend_id'=>$users[0]->uid,
	                     'secrate'=>$iv,
						);
						\App\Common::insertdata('invite_friends',$enter);

						$enter2 = array(
	                     'from_id'=>$id,
	                     'to_id'=>$users[0]->uid,
	                     'status'=>'0'
						);
						$enter3 = array(
	                     'from_id'=>$id,
	                     'to_id'=>$users[0]->uid,
						);
						$check = \App\Common::selectdata('friend_request',$enter3);
						if(count($check) > 0)
						{
                          \App\Common::updatedata('friend_request',$enter2,$enter3);
						}else
						{
						 \App\Common::insertdata('friend_request',$enter2);	
						}
						// notification
						$notis = array(
					 		'from_id' => $id, 
					 		'to_id' => $id2, 
					 		'type' => '6', 
					 		'others' => ''
					 	);
					 	$this->notify($notis);
					}
				$data['msg'] = 'Firend request sent successfully!!';
				$data['status'] = 1;
				$data['udata'] = 'null';
	}else
	{
      
			$data['msg'] = 'Error to send request';
			$data['status'] = 0;
			$data['udata'] = 'null';
	}
	return json_encode($data); 
 }

 public function deletecard($uid,$cid)
 {
     $check = \App\Common::selectdata('tbl_cards',array('uid'=>$uid,'cid'=>$cid));
     if(count($check) > 0)
     {
     	\App\Common::deletedata('tbl_cards',array('uid'=>$uid,'cid'=>$cid));
          $data['msg'] = 'Card Deleted Sccuessfully!!';
			$data['status'] = 1;
			$data['udata'] = 'null';
     }else
     {
     	    $data['msg'] = 'Error not card found';
			$data['status'] = 0;
			$data['udata'] = 'null';
     }
     return json_encode($data);
 }

 public function deletepost($uid,$postid)
 {
     $check = \App\Common::selectdata('tbl_posts',array('pid'=>$postid,'uid'=>$uid));
     if(count($check) > 0)
     {
     	\App\Common::deletedata('tbl_posts',array('pid'=>$postid,'uid'=>$uid));
          $data['msg'] = 'Post Deleted Sccuessfully!!';
			$data['status'] = 1;
			$data['udata'] = 'null';
     }else
     {
     	    $data['msg'] = 'Error not post found';
			$data['status'] = 0;
			$data['udata'] = 'null';
     }
     return json_encode($data);
 }

 public function deletetrip($uid,$triptid)
 {
     $check = \App\Common::selectdata('tbl_trips',array('tid'=>$triptid,'uid'=>$uid));
     if(count($check) > 0)
     {
     	\App\Common::deletedata('tbl_trips',array('tid'=>$triptid,'uid'=>$uid));
     	$check2 = \App\Common::selectdata('tbl_trip_days',array('trip_id'=>$triptid));
			if(count($check2) > 0)
			{
				\App\Common::deletedata('tbl_trip_days',array('trip_id'=>$triptid));
			}
          $data['msg'] = 'Trip Deleted Sccuessfully!!';
			$data['status'] = 1;
			$data['udata'] = 'null';
     }else
     {
     	    $data['msg'] = 'trip not found';
			$data['status'] = 0;
			$data['udata'] = 'null';
     }
     return json_encode($data);
 }

 public function deletefriend($uid,$friend_id)
 {
     $check1 = \App\Common::selectdata('fiend_list',array('user_id'=>$uid));
		if(count($check1) > 0)
		{
			$friends = explode(',',$check1[0]->friend_id);
			$myfirends = array();
			foreach($friends as $friend)
			{
             if($friend != $friend_id)
             {
              $myfirends[] = $friend; 
             }
			}
            \App\Common::updatedata('fiend_list',array('friend_id'=>implode(',',$myfirends)),array('user_id'=>$uid));
			 $check2 = \App\Common::selectdata('fiend_list',array('user_id'=>$friend_id));
			if(count($check2) > 0)
			{
				$friends2 = explode(',',$check2[0]->friend_id);
				$myfirends2 = array();
				foreach($friends2 as $friendk)
				{
					if($friendk != $uid)
					{
					  $myfirends2[] = $friendk; 
					}
				}
				\App\Common::updatedata('fiend_list',array('friend_id'=>implode(',',$myfirends2)),array('user_id'=>$friend_id));
				 
			}
               $data['msg'] = 'Friend deleted successfully';
				$data['status'] = 1;
				$data['udata'] = 'null';
		}else
	     {
	     	    $data['msg'] = 'Error no friend found';
				$data['status'] = 0;
				$data['udata'] = 'null';
	     }
	   return json_encode($data);
 }

 public function notify($data){
 	$notis = array(
 		'from_id' => $data['from_id'], 
 		'to_id' => $data['to_id'], 
 		'type' => $data['type'], 
 		'others' => $data['others']
 	);
 	\App\Common::insertdata('notifications',$notis);
 	$data['msg'] = null;
	$data['status'] = 1;
	$data['data'] = 'inserted';
 	return json_encode($data);
 }

 public function get_notis($user_id){
 	\App\Common::updatedata('notifications',array('isRead' =>'1'),array('to_id' => $user_id));
 	$notis = \App\Common::my_notis($user_id);
	return json_encode($notis);
 }

 public function getNotisCount($user_id){
 	$data['count'] = \App\Common::countdata('notifications',array('to_id'=>$user_id, 'isRead'=>'0'));
 	$data['user_rating'] = \App\Common::get_user_rating($user_id);
	return json_encode($data);
 }

 public function del_notis($nid){
 	$notis = \App\Common::deletedata('notifications',array('nid' => $nid));
 	$data['msg'] = null;
	$data['status'] = 1;
	$data['data'] = 'deleted';
	return json_encode($data);
 }

 public function delete_friend_request($uid,$friendid,$requestid)
 {
   $were = [['id','=',$requestid],['to_id','=',$uid],['from_id','=',$friendid]];
 	$request = \App\Common::selectdata('friend_request',$were);
 	if(count($request) > 0)
     {
       \App\Common::deletedata('friend_request',$were);
       // notification
		$notis = array(
	 		'from_id' => $uid, 
	 		'to_id' => $friendid, 
	 		'type' => '8', 
	 		'others' => ''
	 	);
	 	$this->notify($notis);
        $data['msg'] = 'Request deleted successfully';
		$data['status'] = 1;
		$data['udata'] = 'null';
     }else
     {
     	$data['msg'] = 'No request found';
		$data['status'] = 0;
		$data['udata'] = 'null';
		
     }
     return json_encode($data);
 }

 public function getimg($id)
 {
    $were = [['uid','=',$id]];
 	$request = \App\Common::selectdata('tbl_users',$were);
 	if(count($request) > 0)
     { 
     	$img = \App\Common::getbycondition3('tbl_userdetails',$were);
     	if(count($img) > 0)
     	{  
           if(!empty($img[0]->img))
           {
           	$data['img'] = $img[0]->img;
           }else
           {
           	$data['img'] ='';
           }
          
     	}else
     	{
          $data['img'] = '';
     	}
         $data['msg'] = 'image found';
		$data['status'] = 1;
		$data['udata'] = 'null';
     }else
     {
     	$data['msg'] = 'No request found';
		$data['status'] = 0;
		$data['udata'] = 'null';
		
     }
     return json_encode($data);
 }

 public function getfrendspost(Request $request,$id){
 	$data = [];
	$postdata = file_get_contents("php://input");
	$request=json_decode($postdata,true);
 	if($id!='')
 	{
 		$all_posts = [];
 		$all_trips = [];
 		$all_popular_trips = [];
 		$popular_travels = \App\Common::popular_travels();
 		if(count($popular_travels)>0){
			foreach($popular_travels as $single_trip){
				$trip =  \App\Common::getfirst('tbl_trips',array('tid' => $single_trip->trip_id));
				if(isset($trip) && $trip != ''){
					$pl_array['user'] =  \App\Common::getJoinsInUsers('tbl_users','tbl_userdetails',$trip->uid);
					if(!empty($pl_array['user'])){
						$pl_array['tid'] = $trip->tid;
						$pl_array['uid'] = $trip->uid;
						$pl_array['title'] = $trip->title;
						$pl_array['destination'] = $trip->destination;
						$pl_array['summary'] = $trip->summary;
						$pl_array['cover_letter'] = $trip->cover_letter;
						$pl_array['travel_type'] = $trip->travel_type;
		                $pl_array['ratings'] = unserialize($trip->ratings);
						$pl_array['pictures'] = unserialize($trip->pictures);
						$pl_array['start_date'] = $trip->start_date;
						$pl_array['end_date'] = $trip->end_date;
						$pl_array['created_at'] = $trip->created;
						$pl_array['created'] = \Carbon\Carbon::createFromTimeStamp(strtotime($trip->created))->diffForHumans(); 
						$pl_array['sell_price'] = $trip->sell_price;
						array_push($all_popular_trips, $pl_array);
						$pl_array = [];
					}	
				}
			}
		}
		
		$where = [['is_draft','=','0']];
 		$trips = \App\Common::selectdata('tbl_trips',$where);
 		if(count($trips)>0){
			foreach($trips as $trip){
				$narray['user'] =  \App\Common::getJoinsInUsers('tbl_users','tbl_userdetails',$trip->uid);
				if(!empty($narray['user'])){
					$narray['user_rating'] = \App\Common::get_user_rating($trip->uid);
					$narray['type'] = 'trip';
					$narray['tid'] = $trip->tid;
					$narray['can_buy'] = $this->check_user_settings($trip->uid,$id,'2');
					$narray['uid'] = $trip->uid;
					$narray['title'] = $trip->title;
					$narray['destination'] = $trip->destination;
					$narray['summary'] = $trip->summary;
					$narray['cover_letter'] = $trip->cover_letter;
					$narray['travel_type'] = $trip->travel_type;
	                $narray['ratings'] = unserialize($trip->ratings);
					$narray['pictures'] = unserialize($trip->pictures);
					$narray['start_date'] = $trip->start_date;
					$narray['end_date'] = $trip->end_date;
					$narray['created_at'] = $trip->created;
					$narray['created'] = \Carbon\Carbon::createFromTimeStamp(strtotime($trip->created))->diffForHumans(); 
					$narray['sell_price'] = $trip->sell_price;
					$narray['budget'] = $trip->budget;
					$narray['transportation'] = unserialize($trip->transportation);
					$narray['search_terms'] = $trip->cover_letter.' '.$trip->destination.' '.$trip->summary.' '.$trip->travel_type.' '.$trip->sell_price;
					$likes = \App\Common::selectdata('likes',array('post_trip_id'=>$trip->tid, 'type'=>'trip'));
						if(count($likes) > 0)
						{ 
							$like_array = \App\Common::getPostLikes('likes',$trip->tid,'trip');
							$narray['like_array'] = $like_array;
							$narray['total_likes'] = count($like_array);
	                        $narray['like_users'] =  \App\Common::getJoinsInUsersLike('tbl_users','tbl_userdetails',$like_array);
						}
						else{
							$narray['like_users'] = [];	
							$narray['like_array'] = [];
							$narray['total_likes'] = 0;
						}

					$comments = \App\Common::selectdata('comment',array('post_trip_id'=>$trip->tid, 'type'=>'trip'));
						if(count($comments) > 0)
						{ 
	                        $narray['comments'] =  \App\Common::getCommentUsers('comment','tbl_userdetails',$trip->tid,'trip');
						}
						else{
							$narray['comments'] = [];	
						}

					// get rating
					$reviews = \App\Common::selectdata('ratings',array('post_trip_id'=>$trip->tid, 'type'=>'trip'));
					if(count($reviews) > 0)
					{ 
			            $narray['reviews'] =  \App\Common::getReviewUsers($trip->tid,'trip');
			            $review_stars =  \App\Common::getReviewStars($trip->tid,'trip');
			            $narray['review_stars'] =  round($review_stars,1); 
			            $narray['review_array'] =  \App\Common::getReviews('ratings',$trip->tid,'trip');
					}
					else{
						$narray['reviews'] = [];	
						$narray['review_stars'] = 0;
						$narray['review_array'] =  [];	
					}
					array_push($all_trips, $narray);
					$narray = [];
				}
			}
		}
		$posts = \App\Common::selectdata('tbl_posts');
		if(count($posts)>0){
			foreach($posts as $post){
				$p_array['user'] =  \App\Common::getJoinsInUsers('tbl_users','tbl_userdetails',$post->uid);
				if(!empty($p_array['user'])){
					$p_array['user_rating'] = \App\Common::get_user_rating($post->uid);
					$p_array['type'] = 'post';
					$p_array['pid'] = $post->pid;
					$p_array['uid'] = $post->uid;
					$p_array['ptitle'] = $post->ptitle;
					$p_array['pdes'] = $post->pdes;
					$p_array['tags'] = unserialize($post->tags);
					$p_array['tag_users'] = [];
					if(is_array(unserialize($post->tags))){
						$p_array['tagscount'] = count(unserialize($post->tags));
						$tag_array = array_column($p_array['tags'], 'value');	
						$p_array['tag_users'] = \App\Common::getJoinsInUsersLike('tbl_users','tbl_userdetails',$tag_array);
					}
					$p_array['photos'] = unserialize($post->photos);
					$p_array['created_at'] = $post->created;
					$p_array['created'] =  \Carbon\Carbon::createFromTimeStamp(strtotime($post->created))->diffForHumans(); 
					$p_array['location'] = $post->location;
					$p_array['search_terms'] = $post->ptitle.' '.$post->pdes.' '.$post->location;
					$likes = \App\Common::selectdata('likes',array('post_trip_id'=>$post->pid, 'type'=>'post'));
						if(count($likes) > 0)
						{ 
							$like_array = \App\Common::getPostLikes('likes',$post->pid,'post');
							$p_array['like_array'] = $like_array;
							$p_array['total_likes'] = count($like_array);
	                        $p_array['like_users'] =  \App\Common::getJoinsInUsersLike('tbl_users','tbl_userdetails',$like_array);
						}
						else{
							$p_array['like_users'] = [];	
							$p_array['like_array'] = [];
							$p_array['total_likes'] = 0;
						}

					$comments = \App\Common::selectdata('comment',array('post_trip_id'=>$post->pid, 'type'=>'post'));
						if(count($comments) > 0)
						{ 
	                        $p_array['comments'] =  \App\Common::getCommentUsers('comment','tbl_userdetails',$post->pid,'post');
						}
						else{
							$p_array['comments'] = [];	
						}

					// get rating
					$reviews = \App\Common::selectdata('ratings',array('post_trip_id'=>$post->pid, 'type'=>'post'));
						if(count($reviews) > 0)
						{ 
				            $p_array['reviews'] =  \App\Common::getReviewUsers($post->pid,'post');
				            $review_stars =  \App\Common::getReviewStars($post->pid,'post');
				            $p_array['review_stars'] =  round($review_stars,1); 
				            $p_array['review_array'] =  \App\Common::getReviews('ratings',$post->pid,'post');
						}
						else{
							$p_array['reviews'] = [];	
							$p_array['review_stars'] = 0;
							$p_array['review_array'] =  [];	
						}
					array_push($all_posts, $p_array);
					$p_array = [];
				}
			}
		}
		// merge both array
		$all_trips_posts = array_merge($all_posts,$all_trips);
		// sort array by created date
		array_multisort(array_column($all_trips_posts, 'created_at'), SORT_DESC, $all_trips_posts);

		$data['all_posts'] = $all_trips_posts;
		$data['popular_travels'] = $all_popular_trips;
		$data['status'] = 1;
 	}
 	else{
 		$data['msg'] = 'No request found';
		$data['status'] = 0;
		$data['udata'] = 'null';
 	}
 	return json_encode($data);
 }

 public function check_user_settings($user_id,$login_id,$type){
 	$get_setting = \App\Common::getfirst('tbl_user_settings',array('uid' => $user_id)); 
 	if($type == '1'){
 		$setting = $get_setting->can_see_profile;
 	}
 	else if($type == '2'){
 		$setting = $get_setting->buy_trips;
 	}

 	if($setting == '3'){
 		return 1;
 	}
 	else if($setting == '1'){
 		$all_friends = \App\Common::selectdata('fiend_list',array('user_id'=>$user_id));
 		if(count($all_friends) > 0){
	 		$all_friends_array = explode(',', $all_friends[0]->friend_id);
	 		if(in_array($login_id, $all_friends_array)){
	 			return 1;
	 		}
	 		else{
	 			return 0;
	 		}
 		}
 		else{
 			return 0;
 		}
 	}
 	else if($setting == '2'){
 		$all_friends = \App\Common::selectdata('fiend_list',array('user_id'=>$user_id));
 		if(count($all_friends) > 0){
 			$return = 0;
 			$all_friends_array = explode(',', $all_friends[0]->friend_id);
 			foreach ($all_friends_array as $friend) {
 				$his_all_friends = \App\Common::selectdata('fiend_list',array('user_id'=>$friend));
 				if(count($his_all_friends) > 0){
 					$his_friends_array = explode(',', $his_all_friends[0]->friend_id);
 					if(in_array($login_id, $his_friends_array)){
			 			$return = 1;
			 		}
 				}
 			}
 			return $return;
 		}
 		else{
 			return 0;
 		}

 	}
 	else{
 		return 0;
 	}
 } 

 public function check_user_settings_request($user_id,$login_id){
 	$get_setting = \App\Common::getfirst('tbl_user_settings',array('uid' => $user_id)); 
 	$setting = $get_setting->receive_friend_requests;

 	if($setting == '3'){
 		return 1;
 	}
 	else if($setting == '2'){
 		return 0;
 	}
 	else if($setting == '1'){
 		$all_friends = \App\Common::selectdata('fiend_list',array('user_id'=>$user_id));
 		if(count($all_friends) > 0){
 			$return = 0;
 			$all_friends_array = explode(',', $all_friends[0]->friend_id);
 			foreach ($all_friends_array as $friend) {
 				$his_all_friends = \App\Common::selectdata('fiend_list',array('user_id'=>$friend));
 				if(count($his_all_friends) > 0){
 					$his_friends_array = explode(',', $his_all_friends[0]->friend_id);
 					if(in_array($login_id, $his_friends_array)){
			 			$return = 1;
			 		}
 				}
 			}
 			return $return;
 		}
 		else{
 			return 0;
 		}

 	}
 	else{
 		return 0;
 	}
 }

 public function buy_trip_users($user_id,$trip_id){
 	$all_users = \App\Common::buy_trip_users($user_id,$trip_id);
 	return json_encode($all_users);
 }

 public function earning($user_id){
 	$all_data = [];
 	$data['user_rating'] = \App\Common::get_user_rating($user_id);
 	$earning = \App\Common::get_earning($user_id);
 	if(count($earning) > 0){
 		foreach ($earning as $earn) {
 			$trip_details = \App\Common::getfirst('tbl_trips',array('tid' => $earn->trip_id, 'is_draft'=>'0'));
 			$trip['user_earn'] = \App\Common::tripwise_earning($earn->trip_id,$user_id);
 			$trip['trip_id'] = $earn->trip_id;
 			$trip['total_users'] = $earn->total_users;
 			$trip['title'] = $trip_details->title;
 			$trip['sell_price'] = $trip_details->sell_price;
 			array_push($all_data, $trip);
 			$trip = [];
 		}
 	}
 	$data['earning'] = $all_data;
 	return json_encode($data);
 }

 public function get_transactions(){
 	$data['transactions'] = \App\Common::get_transactions();
 	$data['commission'] = \App\Common::get_total_commission();
 	return json_encode($data);
 }

 public function my_draft($user_id){
 	$my_draft = \App\Common::selectdata('tbl_trips',array('is_draft' => '1', 'uid' => $user_id),array('tid' => 'desc' ));
 	return json_encode($my_draft);
 }

  public function getFeed(){
		$all_posts = [];
		$all_trips = [];
		$trips = \App\Common::selectdata('tbl_trips',array('is_draft'=>'0'), array('tid'=>'desc'));
		if(count($trips)>0){
		foreach($trips as $trip){
			$narray['user'] =  \App\Common::getJoinsInUsers('tbl_users','tbl_userdetails',$trip->uid);
			if(!empty($narray['user'])){
				$narray['type'] = 'trip';
				$narray['tid'] = $trip->tid;
				$narray['uid'] = $trip->uid;
				$narray['title'] = $trip->title;
				$narray['destination'] = $trip->destination;
				$narray['summary'] = $trip->summary;
				$narray['cover_letter'] = $trip->cover_letter;
				$narray['travel_type'] = $trip->travel_type;
	            $narray['ratings'] = unserialize($trip->ratings);
				$narray['pictures'] = unserialize($trip->pictures);
				$narray['start_date'] = $trip->start_date;
				$narray['end_date'] = $trip->end_date;
				$narray['created_at'] = $trip->created;
				$narray['created'] = \Carbon\Carbon::createFromTimeStamp(strtotime($trip->created))->diffForHumans(); 
				$narray['sell_price'] = $trip->sell_price;
				$narray['budget'] = $trip->budget;
				$narray['transportation'] = unserialize($trip->transportation);
				$narray['search_terms'] = $trip->cover_letter.' '.$trip->destination.' '.$trip->summary.' '.$trip->travel_type.' '.$trip->sell_price;
				$likes = \App\Common::selectdata('likes',array('post_trip_id'=>$trip->tid, 'type'=>'trip'));
					if(count($likes) > 0)
					{ 
						$like_array = \App\Common::getPostLikes('likes',$trip->tid,'trip');
						$narray['like_array'] = $like_array;
	                    $narray['like_users'] =  \App\Common::getJoinsInUsersLike('tbl_users','tbl_userdetails',$like_array);
					}
					else{
						$narray['like_users'] = [];	
						$narray['like_array'] = [];
					}

				$comments = \App\Common::selectdata('comment',array('post_trip_id'=>$trip->tid, 'type'=>'trip'));
					if(count($comments) > 0)
					{ 
	                    $narray['comments'] =  \App\Common::getCommentUsers('comment','tbl_userdetails',$trip->tid,'trip');
					}
					else{
						$narray['comments'] = [];	
					}

				$reviews = \App\Common::selectdata('ratings',array('post_trip_id'=>$trip->tid, 'type'=>'trip'));
				if(count($reviews) > 0)
				{ 
		            $narray['reviews'] =  \App\Common::getReviewUsers($trip->tid,'trip');
				}
				else{
					$narray['reviews'] = [];		
				}
				array_push($all_trips, $narray);
				$narray = [];
			}
		}
	}
	$posts = \App\Common::selectdata('tbl_posts',[],array('pid'=>'desc'));
	if(count($posts)>0){
		foreach($posts as $post){
			$p_array['user'] =  \App\Common::getJoinsInUsers('tbl_users','tbl_userdetails',$post->uid);
			if(!empty($p_array['user'])){
				$p_array['type'] = 'post';
				$p_array['pid'] = $post->pid;
				$p_array['uid'] = $post->uid;
				$p_array['ptitle'] = $post->ptitle;
				$p_array['pdes'] = $post->pdes;
				$p_array['tags'] = unserialize($post->tags);
				$p_array['tag_users'] = [];
				$p_array['tagscount'] = 0;
				if(is_array(unserialize($post->tags))){
					$p_array['tagscount'] = count(unserialize($post->tags));
					$tag_array = array_column($p_array['tags'], 'value');	
					$p_array['tag_users'] = \App\Common::getJoinsInUsersLike('tbl_users','tbl_userdetails',$tag_array);
				}
				$p_array['photos'] = unserialize($post->photos);
				$p_array['created_at'] = $post->created;
				$p_array['created'] =  \Carbon\Carbon::createFromTimeStamp(strtotime($post->created))->diffForHumans(); 
				$p_array['location'] = $post->location;
				$p_array['search_terms'] = $post->ptitle.' '.$post->pdes.' '.$post->location;
				$likes = \App\Common::selectdata('likes',array('post_trip_id'=>$post->pid, 'type'=>'post'));
					if(count($likes) > 0)
					{ 
						$like_array = \App\Common::getPostLikes('likes',$post->pid,'post');
						$p_array['like_array'] = $like_array;
	                    $p_array['like_users'] =  \App\Common::getJoinsInUsersLike('tbl_users','tbl_userdetails',$like_array);
					}
					else{
						$p_array['like_users'] = [];	
						$p_array['like_array'] = [];
					}

				$comments = \App\Common::selectdata('comment',array('post_trip_id'=>$post->pid, 'type'=>'post'));
					if(count($comments) > 0)
					{ 
	                    $p_array['comments'] =  \App\Common::getCommentUsers('comment','tbl_userdetails',$post->pid,'post');
					}
					else{
						$p_array['comments'] = [];	
					}
				array_push($all_posts, $p_array);
				$p_array = [];
			}
		}
	}

	$data['all_posts'] = $all_posts;
	$data['all_trips'] = $all_trips;
	$data['status'] = 1;

 	return json_encode($data);
 }

 // public function getfrendspost(Request $request,$id)
 // {
	// 		$data = [];
	// 		$postdata = file_get_contents("php://input");
	// 		$request=json_decode($postdata,true);
	// 	 	if($id!='')
	// 	 	{
	// 	 		$check1 = \App\Common::selectdata('fiend_list',array('user_id'=>$id));
	// 			if(count($check1) > 0)
	// 			{  
	// 				 $data['post'] = array();
	// 				  $data['trip'] = array();
	// 			   		$friends = explode(',',$check1[0]->friend_id);
				   
	// 					if(isset($request['orderby']) && $request['orderby']=='1')
	// 					{
	// 	                  rsort($friends);
	// 					}else
	// 					{
	// 						sort($friends);
	// 					}
	// 				 foreach($friends as $get)
	// 				 {
	// 				 	$trips = \App\Common::selectdata('tbl_trips',array('uid'=>$get));
	// 					$posts = \App\Common::selectdata('tbl_posts',array('uid'=>$get));
	// 					$i=0;
	// 					$narray = array();
	// 				if(count($posts)>0){
	// 				foreach($posts as $post){
	// 					$narray[$i]['user'] =  \App\Common::getJoins2s('tbl_users','tbl_userdetails',$post->uid);
	// 					$narray[$i]['pid'] = $post->pid;
	// 					$narray[$i]['uid'] = $post->uid;
	// 					$narray[$i]['ptitle'] = $post->ptitle;
	// 					$narray[$i]['pdes'] = $post->pdes;
	// 					$narray[$i]['tags'] = unserialize($post->tags);
	// 					if(is_array($post->tags))
	// 					{
	// 					$narray[$i]['tagscount'] = count(unserialize($post->tags));	
	// 					}
	// 					$narray[$i]['photos'] = unserialize($post->photos);
	// 					$narray[$i]['created'] =  \Carbon\Carbon::createFromTimeStamp(strtotime($post->created))->diffForHumans(); 
	// 					$narray[$i]['location'] = $post->location;
	// 					$likes = \App\Common::selectdata('likes',array('post_id'=>$post->pid));
	// 					if(count($likes) > 0)
	// 					{ 
	// 						foreach($likes as $like)
	// 						{
	// 	                        $narray[$i]['like'] =  \App\Common::getJoins2s('tbl_users','tbl_userdetails',$like->uid); 
	// 	                        $narray[$i]['likecount'] = count($narray[$i]['like']);
	// 						}
	// 					}

	// 					$comments = \App\Common::selectdata('comment',array('post_id'=>$post->pid));
	// 					if(count($comments) > 0)
	// 					{ 
	// 						foreach($comments as $comment)
	// 						{
	// 	                        $narray[$i]['comment'] =  \App\Common::getJoins2s('tbl_users','tbl_userdetails',$comment->uid); 
	// 	                        $narray[$i]['commentcount'] = count($narray[$i]['comment']);
	// 						}
	// 					}
	// 					$i++;
	// 				}
	// 				 if(count($narray) > 0)
	// 				  {
	// 				  	 array_push($data['post'],$narray);
							
	// 				  }
	// 				}
	// 				$j=0;
	// 				$narray2 = array();
	// 				if(count($trips)>0){
	// 				foreach($trips as $trip){
	// 					$narray2[$j]['user'] =  \App\Common::getJoins2s('tbl_users','tbl_userdetails',$trip->uid);
	// 					$narray2[$j]['tid'] = $trip->tid;
	// 					$narray2[$j]['uid'] = $trip->uid;
	// 					$narray2[$j]['destination'] = $trip->destination;
	// 					$narray2[$j]['summary'] = $trip->summary;
	// 					$narray2[$j]['cover_letter'] = $trip->cover_letter;
	// 					$narray2[$j]['travel_type'] = $trip->travel_type;
	// 					$ratings = unserialize($trip->ratings);
	// 					$total = array_sum($ratings);
	// 	                $rates = $total/count($ratings);
	// 	                $narray2[$j]['ratings'] = round($rates);
	// 					$narray2[$j]['pictures'] = unserialize($trip->pictures);
	// 					$narray2[$j]['start_date'] = $trip->start_date;
	// 					$narray2[$j]['end_date'] = $trip->end_date;
	// 					$narray2[$j]['created'] = \Carbon\Carbon::createFromTimeStamp(strtotime($trip->created))->diffForHumans(); 
	// 					$narray2[$j]['sell_price'] = $trip->sell_price;
	// 					$narray2[$j]['budget'] = $trip->budget;
	// 					$narray2[$j]['transportation'] = unserialize($trip->transportation);
	// 					$trips_days = \App\Common::selectdata('tbl_trip_days',array('trip_id'=>$trip->tid));
	// 					if(count($trips_days) > 0)
	// 					{ 
	// 						foreach($trips_days as $tripday)
	// 						{
	// 							$enter2[$j]['day'] = $tripday->day;
	// 							$enter2[$j]['trip_id'] = $tripday->trip_id;
	// 							$enter2[$j]['start_place'] = $tripday->start_place;
	// 							$enter2[$j]['destination_place'] = $tripday->destination_place;
	// 							$enter2[$j]['comment'] = $tripday->comment;
	// 							$enter2[$j]['hotel'] = unserialize($tripday->hotel);
	// 							$enter2[$j]['restautrant'] = unserialize($tripday->restautrant);
	// 							$enter2[$j]['restautrant'] = unserialize($tripday->add_transportation);
	// 	                        $enter2[$j]['images'] = unserialize($tripday->images);

	// 						}
	// 	                     $narray2[$j]['days'] = $enter2;
	// 					}
	// 					$likes = \App\Common::selectdata('likes',array('trip_id'=>$trip->tid));
	// 					if(count($likes) > 0)
	// 					{ 
	// 						foreach($likes as $like)
	// 						{
	// 	                        $narray2[$j]['like'] =  \App\Common::getJoins2s('tbl_users','tbl_userdetails',$like->uid); 
	// 	                        $narray2[$j]['likecount'] = count($narray2[$j]['like']);
	// 						}
	// 					}

	// 					$comments = \App\Common::selectdata('comment',array('trip_id'=>$trip->tid));
	// 					if(count($comments) > 0)
	// 					{ 
	// 						foreach($comments as $comment)
	// 						{
	// 	                        $narray2[$j]['comment'] =  \App\Common::getJoins2s('tbl_users','tbl_userdetails',$comment->uid); 
	// 	                        $narray2[$j]['commentcount'] = count($narray2[$j]['comment']);
	// 						}
	// 					}
	// 					$j++;
	// 				}
	// 					if(count($narray2) > 0)
	// 					  {
	// 					  	  array_push($data['trip'],$narray2);
	// 					  }
	// 				} 
	// 				 }

	// 			}else
	// 			{
	// 				$data['msg'] = 'No friend found';
	// 				$data['status'] = 0;
	// 				$data['udata'] = 'null';
	// 			}
	// 	 	}else
	// 	 	{
	// 			$data['msg'] = 'No request found';
	// 			$data['status'] = 0;
	// 			$data['udata'] = 'null';
	// 	 	}
	// 			//echo '<pre>'; print_r($data); die; 
	// 			return json_encode($data);
 // }

	// public function comma_separated_to_array($string, $separator = ',')
	// {
	// 	$vals = explode($separator, $string);
	// 	foreach($vals as $key => $val) {
	// 		$vals[$key] = trim($val);
	// 	}
	// 	return array_diff($vals, array(""));
	// }

 public function like_unlike_review(Request $request)
 {
	$data = [];
	$postdata = file_get_contents("php://input");
	$request=json_decode($postdata,true);
	$id = $request['id'];
	$user_id = $request['user_id'];
	$type = $request['type'];
	$likes = $request['likes'];
	if($id!='' && $type!='')
	{
		\App\Common::updatedata('ratings',array('likes' => $likes),array('id' => $id ));
		// create receiver notification
		if($user_id != $request['to_id']){
			$title = 'trip '.$request['title'];
			$notis = array(
		 		'from_id' => $user_id, 
		 		'to_id' => $request['to_id'], 
		 		'type' => $type == 'like' ? '9' : '10', 
		 		'others' => $title
		 	);
		 	$this->notify($notis);
	 	}
        
        $data['msg'] = 'success';
		$data['status'] = 1;
		$data['udata'] = 'null';
        
	}else
	{
		$data['msg'] = 'No request found';
		$data['status'] = 0;
		$data['udata'] = 'null';
	}
	return json_encode($data);
 }

 public function like_unlike_comment(Request $request)
 {
	$data = [];
	$postdata = file_get_contents("php://input");
	$request=json_decode($postdata,true);
	$id = $request['id'];
	$user_id = $request['user_id'];
	$type = $request['type'];
	$likes = $request['likes'];
	if($id!='' && $type!='')
	{
		\App\Common::updatedata('comment',array('likes' => $likes),array('id' => $id ));
		// create receiver notification
		if($user_id != $request['to_id']){
			$title = 'post '.$request['title'];
			$notis = array(
		 		'from_id' => $user_id, 
		 		'to_id' => $request['to_id'], 
		 		'type' => $type == 'like' ? '9' : '10', 
		 		'others' => $title
		 	);
		 	$this->notify($notis);
	 	}
        
        $data['msg'] = 'success';
		$data['status'] = 1;
		$data['udata'] = 'null';
        
	}else
	{
		$data['msg'] = 'No request found';
		$data['status'] = 0;
		$data['udata'] = 'null';
	}
	return json_encode($data);
 }

 public function like(Request $request)
 {
	$data = [];
	$postdata = file_get_contents("php://input");
	$request=json_decode($postdata,true);
	$id = $request['id'];
	$user_id = $request['user_id'];
	$type = $request['type'];
	if($id!='' && $user_id!='' && $type!='')
	{
		$enter['post_trip_id']=$id; 
		$enter['uid']=$user_id;
		$enter['type']= $type;
		\App\Common::insertdata('likes',$enter);
		// create receiver notification
		if($user_id != $request['to_id']){
			$title = $request['type'].' '.$request['title'];
			$notis = array(
		 		'from_id' => $user_id, 
		 		'to_id' => $request['to_id'], 
		 		'type' => '1', 
		 		'others' => $title
		 	);
		 	$this->notify($notis);
	 	}
        
        $data['msg'] = $type.' liked successfully';
		$data['status'] = 1;
		$data['udata'] = 'null';
        
	}else
	{
		$data['msg'] = 'No request found';
		$data['status'] = 0;
		$data['udata'] = 'null';
	}
	return json_encode($data);
 }

 public function dislike(Request $request)
 {
	$data = [];
	$postdata = file_get_contents("php://input");
	$request=json_decode($postdata,true);
	$id = $request['id'];
	$user_id = $request['user_id'];
	$type = $request['type'];
	if($id!='' && $user_id!='' && $type!='')
	{
		$enter['post_trip_id']=$id; 
		$enter['uid']=$user_id;
		$enter['type']= $type;
		\App\Common::deletedata('likes',$enter);
		// create receiver notification
		if($user_id != $request['to_id']){
			$title = $request['type'].' '.$request['title'];
			$notis = array(
		 		'from_id' => $user_id, 
		 		'to_id' => $request['to_id'], 
		 		'type' => '2', 
		 		'others' => $title
		 	);
		 	$this->notify($notis);
	 	}
        
        $data['msg'] = $type.' disliked successfully';
		$data['status'] = 1;
		$data['udata'] = 'null';
	}else
	{
		$data['msg'] = 'No request found';
		$data['status'] = 0;
		$data['udata'] = 'null';
	}
	return json_encode($data);
 }


 public function comment(Request $request)
 {
	$data = [];
	$postdata = file_get_contents("php://input");
	$request=json_decode($postdata,true);
	$id = $request['id'];
	$user_id = $request['user_id'];
	$type = $request['type'];
	$comment = $request['comment'];
	$created = $request['created'];
	if($id!='' && $user_id!='' && $type!='' && $comment!='')
	{
		$enter['post_trip_id']=$id; 
		$enter['uid']=$user_id;
		$enter['type']= $type;
		$enter['comment']= $comment;
		$enter['created']= $created;
		$enter['likes']= '';
		$last_id = \App\Common::insertdata('comment',$enter);
		// create receiver notification
		if($user_id != $request['to_id']){
			$title = $request['type'].' '.$request['title'];
			$notis = array(
		 		'from_id' => $user_id, 
		 		'to_id' => $request['to_id'], 
		 		'type' => '3', 
		 		'others' => $title
		 	);
		 	$this->notify($notis);
	 	}
        
        $data['msg'] = $type.' commented successfully';
		$data['status'] = 1;
		$data['data'] = $last_id;
        
	}else
	{
		$data['msg'] = 'No request found';
		$data['status'] = 0;
		$data['data'] = 'null';
	}
	return json_encode($data);
 } 

 public function rating(Request $request)
 {
	$data = [];
	$postdata = file_get_contents("php://input");
	$request=json_decode($postdata,true);
	$id = $request['id'];
	$user_id = $request['user_id'];
	$type = $request['type'];
	$rating = $request['rating'];
	$reviews = $request['reviews'];
	$created = $request['created'];
	if($id!='' && $user_id!='' && $type!='' && $reviews!='' && $rating!='')
	{
		$enter['post_trip_id']=$id; 
		$enter['uid']=$user_id;
		$enter['type']= $type;
		$enter['rating']= $rating;
		$enter['reviews']= $reviews;
		$enter['likes']= '';
		$enter['created']= $created;
		$last_id = \App\Common::insertdata('ratings',$enter);
		// create receiver notification
		if($user_id != $request['to_id']){
			$title = $request['type'].' '.$request['title'];
			$notis = array(
		 		'from_id' => $user_id, 
		 		'to_id' => $request['to_id'], 
		 		'type' => '4', 
		 		'others' => $title
		 	);
		 	$this->notify($notis);
	 	}
	 	//send mail
	 	$user_info = \App\Common::getfirst('tbl_users',array('uid' => $request['to_id']));
	 	$link = $request['API_URL'];
		$to = $user_info->uemail;
		$subject = 'Rating received';
		$view = 'emails.rating';
		$params = array(
			'to_name' => $user_info->username,
			'link' => $link
		);
		$this->sendMail($view,['data' => $params],$to,$subject,$request['to_id']);
        
        $data['msg'] = $type.' rated successfully';
		$data['status'] = 1;
		$data['data'] = $last_id;
        
	}else
	{
		$data['msg'] = 'No request found';
		$data['status'] = 0;
		$data['data'] = 'null';
	}
	return json_encode($data);
 }

 public function getComments($type,$id)
 {
	if($id!='' && $type!='')
	{
		$comments = \App\Common::getCommentUsers('comment','tbl_userdetails',$id,$type);        
        $data['comments'] = $comments;
		$data['status'] = 1;
		$data['udata'] = 'null';
        
	}else
	{
		$data['msg'] = 'No request found';
		$data['status'] = 0;
		$data['udata'] = 'null';
	}
	return json_encode($data);
 }

 public function askQuestion(){
 	$postdata = file_get_contents("php://input");
	$request=json_decode($postdata,true);
	$user_id = $request['user_id'];
	$trip_id = $request['tripId'];
	$question = $request['question'];
	if($user_id!='' && $trip_id!='' && $question!=''){
		$trip_info = \App\Common::trip_info($trip_id); 
		$from_user = \App\Common::getJoinsInUsers('','',$user_id);
		$to = $trip_info->uemail;
		$subject = 'Questions asked regarding a trip';
		$view = 'emails.question';
		$params = array(
			'to_name' => $trip_info->firstName,
			'from_name' => $from_user->firstName.' '.$from_user->lastName, 
			'trip_title' => $trip_info->title,
			'trip_destination' => $trip_info->destination,
			'question' => $question,
			'from_email' => $from_user->uemail,
		);
		$to_id = $trip_info->uid;
		$this->sendMail($view,['data' => $params],$to,$subject,$to_id);
		$data['msg'] = 'mail sent';
		$data['status'] = 1;
		$data['data'] = 'null';
	}
	else{
		$data['msg'] = 'No request found';
		$data['status'] = 0;
		$data['data'] = 'null';
	}
	return json_encode($data);
 }

 public function sendMail($view,$params,$to,$subject,$to_id){
 	$can_send = \App\Common::countdata('tbl_user_settings',array('email_notifications' => '1', 'uid' => $to_id)); 
 	if($can_send == '1'){
 		Mail::send($view, $params, function($message) use ($to,$subject)
		{
			$message->subject($subject);
			$message->from('no-reply@worldvybe.com', 'Worldvybe');
			$message->to($to);
		});
 	}
 }

 public function rating_list(){
 	$rating = \App\Common::rating_list(); 
 	echo json_encode($rating);
 }

 public function graph_data(){
 	$data['users'] = \App\Common::get_graph_data('tbl_users'); 
 	$data['trips'] = \App\Common::get_graph_data('tbl_trips'); 
 	$data['posts'] = \App\Common::get_graph_data('tbl_posts'); 
 	echo json_encode($data);
 }

 public function buyTrip(Request $request,$user_id,$trip_id){
 	$postdata = file_get_contents("php://input");
	$request=json_decode($postdata,true);
	$stripe = Stripe::make(env('STRIPE_SECRET'));
	try {
		$token = $stripe->tokens()->create([
			'card' => [
			'number' => $request['cardNumber'],
			'exp_month' => explode("/",$request['cDate'])[0],
			'exp_year' => explode("/",$request['cDate'])[1],
			'cvc' => $request['cvv'],
			],
		]);
		if (!isset($token['id'])) {
			$data['msg'] = 'Token not created';
			$data['status'] = 0;
			$data['data'] = 'null';
		}
		$platform_commission = \App\Common::getfirst('tbl_settings',array('meta_name'=>'commision'));
		$trip_owner_user = \App\Common::getfirst('tbl_userdetails',array('uid'=>$request['to_id']));
		$trip_owner_u = \App\Common::getfirst('tbl_users',array('uid'=>$request['to_id']));
		if(isset($platform_commission) && $platform_commission != ''){
			$commision_value = $platform_commission->meta_value;
			if($commision_value > 0){
				$commision_value = $commision_value;
			}
			else{
				$commision_value = 1;
			}
		}

		$admin_commision = ($commision_value/100)*$request['amount'];
		$trip_owner_amount = $request['amount'] - $admin_commision;
		$customerId = $this->registerUserOnStripe($request['email'], $token['id']);
		$charge = $stripe->charges()->create([
			// 'card' => $token['id'],
			'currency' => 'EUR',
			'amount' => $admin_commision,
			'description' => 'Trip commission to admin',
			'customer' => $customerId,
		]);

		if($charge['status'] == 'succeeded') { 
			$stripe = new \Stripe\Stripe();
			$stripe->setApiKey(env('STRIPE_SECRET'));
			$chargeDetails = \Stripe\Charge::create(array(
				"amount" => $trip_owner_amount*100, // $10 amount in cents
				"currency" => 'EUR',
				"description" => "Trip amount to user",
				"source" => $charge['source']['id'], //card token of customer
				"customer" => $charge['source']['customer'], // customer who is paying
				"application_fee" => 0, // amount in cents
				'destination' => $trip_owner_user->bank_acc_id, // account id given by stripe after register bank details
				'capture' => TRUE
			));
			if($chargeDetails['status'] == 'succeeded') {
				$trip_details = array(
					'user_id' => $user_id, 
					'trip_id' => $trip_id, 
					'trip_owner_id' => $request['to_id'], 
					'amount' => $request['amount'], 
					'owner_amount' => $trip_owner_amount, 
					'commision' => $admin_commision, 
					'transaction_id' => $chargeDetails['id'],
					'biller_name' => $request['first_name'].' '.$request['last_name'],
					'biller_email' => $request['email'],
					'biller_phone' => $request['phone'],
					'biller_address' => $request['address1'].' '.$request['address2'],
					'biller_city' => $request['city'],
					'biller_country' => $request['country'],
					'biller_zip' => $request['zip'],
					'order_notes' => $request['notes']
				);
				\App\Common::insertdata('tbl_buy_trips',$trip_details);
				// create receiver notification
				$title = 'trip '.$request['title'];
				$notis = array(
			 		'from_id' => $user_id, 
			 		'to_id' => $request['to_id'], 
			 		'type' => '5', 
			 		'others' => $title
			 	);
			 	$this->notify($notis);
			 	//send mail to trip owner
			 	$link = $request['API_URL'];
				$to = $trip_owner_u->uemail;
				$subject = 'Payment received';
				$view = 'emails.payment';
				$params = array(
					'to_name' => $trip_owner_user->firstName,
					'link' => $link
				);
				$this->sendMail($view,['data' => $params],$to,$subject,$request['to_id']);

				//send mail to buyer
				$trip_buyer = \App\Common::getfirst('tbl_users',array('uid'=>$user_id));
			 	$link = $request['API_URL'];
				$to = $trip_buyer->uemail;
				$subject = 'Rate your guide';
				$view = 'emails.rate_it';
				$params = array(
					'to_name' => $trip_buyer->username,
					'trip_title' => $request['title'],
					'link' => $link.'/#/User/TripDetails/'.$trip_id
				);
				$this->sendMail($view,['data' => $params],$to,$subject,$user_id);
				$data['msg'] = 'charge success';
				$data['status'] = 1;
				$data['data'] = $charge;
			}
			else{
				$data['msg'] = 'transfer failure';
				$data['status'] = 0;
				$data['data'] = '';
			}	
		} 
		else {
			$data['msg'] = 'charge failure';
			$data['status'] = 0;
			$data['data'] = '';
		}
	} 
	catch (Exception $e) {
		$data['msg'] = $e->getMessage();
		$data['status'] = 0;
		$data['data'] = '';
	} catch(\Cartalyst\Stripe\Exception\CardErrorException $e) {
		$data['msg'] = $e->getMessage();
		$data['status'] = 0;
		$data['data'] = '';
	} catch(\Cartalyst\Stripe\Exception\MissingParameterException $e) {
		$data['msg'] = $e->getMessage();
		$data['status'] = 0;
		$data['data'] = '';
	}
	return json_encode($data);
 }

 public function registerUserOnStripe($email, $stripeToken) {
	try {
		$stripe = new \Stripe\Stripe();
		$stripe->setApiKey(env('STRIPE_SECRET')); // secret key provided by stripe
		$customer = \Stripe\Customer::create(array(
		'email' => $email, // customer email id
		'source' => $stripeToken // stripe token generated by stripe.js
		));
		if (!empty($customer) && !empty($customer["id"])) {
		return $customer->id;
		} else {
		return $this->response->responseServerError();
		}
		} catch (\Exception $e) {
		Log::error("PaymentContractImpl::registerUserOnStripe() there is some exception " . $e->getMessage());
		return false;
		}
	}

 public function DeleteBankDetails(Request $request){
 	\App\Common::updatedata('tbl_userdetails',array('routing_number'=>null,'account_number'=>null,'account_country'=>null,'account_currency'=>null,'bank_acc_id'=>null),array('uid'=>$request['user_id']));

		$data['error'] = '';
		$data['status'] = 1;
		$data['data'] = 'bank account removed.';
		return json_encode($data);
 }

 public function AddBankDetails(Request $request,$user_id){
	\Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
	try {
		$acct = \Stripe\Account::create([
		  "type" => "custom",
		  "country" => $request['account_country'],
		  "external_account" => [
		    "object" => "bank_account",
		    "country" => $request['account_country'],
		    "currency" => $request['account_currency'],
		    "routing_number" => $request['routing_number'],
		    "account_number" => $request['account_number'],
		  ],
		  "tos_acceptance" => [
		    "date" => time(),
		    "ip" => \Request::ip(),
		  ],
		]);

		if (!empty($acct->id)) {
			\App\Common::updatedata('tbl_userdetails',array('routing_number'=>$request['routing_number'],'account_number'=>$request['account_number'],'account_country'=>$request['account_country'],'account_currency'=>$request['account_currency'],'bank_acc_id'=>$acct->id),array('uid'=>$user_id));

			$acct_id = $acct->id;
			$data['error'] = '';
			$data['status'] = 1;
			$data['data'] = $acct_id;
		} else { 
			$data['error'] = $this->response->responseServerError();
			$data['status'] = 0;
			$data['data'] = '';
		}
	}
	catch (\Exception $e) {
		$data['error'] = $e->getMessage();
		$data['status'] = 0;
		$data['data'] = '';
	}
	return json_encode($data);
 }

 public function new(Request $request){
 // 	$postdata = file_get_contents("php://input");
	// $request=json_decode($postdata,true);
	\App\Common::insertdata('new',array('name'=>'cfcfcf'));
 }
	
}
?>