<?php
namespace App\Http\Controllers\Api;
use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\ApnsConfig;
use \Kreait\Firebase\Contract\Messaging;
use Error;


class LoginController extends Controller{
    public function login(Request $request){
        $validator = validator::make($request->all(),[
            'avatar'=>'required',
            'name'=>'required',
            'type'=>'required',
            'open_id'=>'required',
            'email'=>'max:50',
            'phone'=>'max:30'
        ]);

        if($validator->fails()){
            return ['code'=>-1,"data"=>"no valid data","msg"=>$validator->errors()->first()];
        }

        try{
        $validated = $validator->validated();
        $map = [];
        $map['type'] = $validated['type'];
        $map['open_id'] = $validated['open_id'];
        $result = DB::table('users')->select('avatar','name','description','type','token','access_token','online')->where($map)->first();

        if(empty($result)){
            $validated["token"] = md5(uniqid().rand(10000,99999));
            $validated["created_at"] = Carbon::now();
            $validated["access_token"] = md5(uniqid().rand(1000000,9999999));
            $validated["expire_date"] = Carbon::now()->addDays(30);
            $user_id = DB::table("users")->insertGetId($validated);
            $user_result = DB::table("users")->select("avatar","name","description","type","access_token","token","online")->where("id","=",$user_id)->first();
            return ['code'=>0,'data'=>$user_result,'msg'=>'user has been created'];
        }else{
            $access_token = md5(uniqid().rand(1000000,9999999));
            $expire_date = Carbon::now()->addDays(30);
            DB::table("users")->where($map)->update(
                [
                    "access_token"=>$access_token,
                    "expire_date"=>$expire_date
                ]
            );
            $result->access_token = $access_token;
            return ['code'=>0,'data'=>$result,'msg'=>'user information updated'];
        }
       }catch (Exception $e) {
        return ["code" => -1, "data" => "no data available", "msg" => $e];
      }

    }
    public function contact(Request $request) {
        try {
            $token = $request->user_token;
            $res = DB::table("users")->select("avatar", "description", "online", "token","name")->where("token", "!=", $token)->get();
            
            return ["code" => 0, "data" => $res, "msg" => "got all the users info"];
        } catch (Exception $e) {
            return ["code" => -1, "error" => $e->getMessage(), "msg" => "An error occurred"];
        }
    }
    public function send_notice(Request $request){
        $user_token = $request->user_token;
        $user_avatar = $request->user_avatar;
        $user_name = $request->user_name;
        $to_token = $request->input("to_token");
        $to_name = $request->input("to_name");
        $to_avatar = $request->input("to_avatar");
        $call_type = $request->input("call_type");
        $doc_id = $request->input("doc_id");
        if(empty($doc_id)){
            $doc_id="";
        }
        ////1. voice 2. video 3. text, 4.cancel
        $res =DB::table("users")->select("avatar","name","token","fcmtoken")->where("token","=",$to_token)->first();
        if(empty($res)){
            return ["code" => -1, "data" => "", "msg" => "user does not exist"];  
        }
        $deviceToken = $res->fcmtoken;
          try {
         
          if(!empty($deviceToken)){
  
          $messaging = app('firebase.messaging');
          if($call_type=="cancel"){
             $message = CloudMessage::fromArray([
           'token' => $deviceToken, // optional
           'data' => [
              'token' => $user_token,
              'avatar' => $user_avatar,
              'name' => $user_name,
              'doc_id'=>$doc_id,
              'call_type' => $call_type,
          ]]);  
          
           $messaging->send($message);
              
          }else if($call_type=="voice"){
             
          $message = CloudMessage::fromArray([
           'token' => $deviceToken, // optional
          'data' => [
              'token' => $user_token,
              'avatar' => $user_avatar,
              'name' => $user_name,
              'doc_id'=>$doc_id,
              'call_type' => $call_type,
          ],
          'android' => [
              "priority" => "high",
              "notification" => [
                  "channel_id"=> "com.dbestech.x_connect.call",
                  'title' => "Voice call made by ".$user_name,
                  'body' => "Please click to answer the voice call",
                  ]
              ],
            //   'apns' => [
            //   // https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages#apnsconfig
            //   'headers' => [
            //       'apns-priority' => '10',
            //   ],
        //       'payload' => [
        //           'aps' => [
        //               'alert' => [
        //                  'title' => "Video call made by ".$user_name,
        //                  'body' => "Please click to answer the video call",
        //               ],
        //               'badge' => 1,
        //               'sound' =>'task_cancel.caf'
        //           ],
        //       ],
        //   ],
          ]);
    
        
         $messaging->send($message);
      
        //   }else if($call_type=="video"){
        //  $message = CloudMessage::fromArray([
        //    'token' => $deviceToken, // optional
        //   'data' => [
        //       'token' => $user_token,
        //       'avatar' => $user_avatar,
        //       'name' => $user_name,
        //       'call_type' => $call_type,
        //   ],
        //   'android' => [
        //       "priority" => "high",
        //       "notification" => [
        //           "channel_id"=> "com.dbestech.chatty.call",
        //           'title' => "Video call made by ".$user_name,
        //           'body' => "Please click to answer the video call",
        //           ]
        //       ],
        //       'apns' => [
        //       // https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages#apnsconfig
        //       'headers' => [
        //           'apns-priority' => '10',
        //       ],
        //       'payload' => [
        //           'aps' => [
        //               'alert' => [
        //                   'title' => "Video call made by ".$user_name,
        //                   'body' => "Please click to answer the video call",
        //               ],
        //               'badge' => 1,
        //               'sound' =>'task_cancel.caf'
        //           ],
        //       ],
        //   ],
        //   ]);
          
        //  $messaging->send($message);
               
        //    }else if($call_type=="text"){
               
        //         $message = CloudMessage::fromArray([
        //    'token' => $deviceToken, // optional
        //   'data' => [
        //       'token' => $user_token,
        //       'avatar' => $user_avatar,
        //       'name' => $user_name,
        //       'call_type' => $call_type,
        //   ],
        //   'android' => [
        //       "priority" => "high",
        //       "notification" => [
        //           "channel_id"=> "com.dbestech.chatty.message",
        //           'title' => "Message made by ".$user_name,
        //           'body' => "Please click to answer the Message",
        //           ]
        //       ],
        //       'apns' => [
        //       // https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages#apnsconfig
        //       'headers' => [
        //           'apns-priority' => '10',
        //       ],
        //       'payload' => [
        //           'aps' => [
        //               'alert' => [
        //                   'title' => "Message made by ".$user_name,
        //                   'body' => "Please click to answer the Message",
        //               ],
        //               'badge' => 1,
        //               'sound' =>'ding.caf'
        //           ],
        //       ],
        //   ],
        //   ]);
          
        //  $messaging->send($message);
               
               
           }

          return ["code" => 0, "data" => $to_token, "msg" => "success"]; 
      
         }else{
           return ["code" => -1, "data" => "", "msg" => "fcmtoken empty"];  
         }
         
         
        }catch (\Exception $exception){
            return ["code" => -1, "data" => "", "msg" => "Exception"];  
          }
    }

    public function bind_fcmtoken(Request $request){
        $token = $request->user_token;
        $fcmtoken = $request->input("fcmtoken");
       
        if(empty($fcmtoken)){
             return ["code" => -1, "data" => "", "msg" => "error getting the token"];
        }
        
        DB::table("users")->where("token","=",$token)->update(["fcmtoken"=>$fcmtoken]);
        
        return ["code" => 0, "data" => "", "msg" => "success"];
    }
}
 