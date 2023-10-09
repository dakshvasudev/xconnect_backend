<?php
namespace App\Http\Controllers\Api;
use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cardbon;
use Illuminate\Support\Facades\DB;


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
        $validated = $validator->validated();
        $map = [];
        $map['type'] = $validated['type'];
        $map['open_id'] = $validated['open_id'];
        $result = DB::table('users')->select('avatar','name','description','type','token','access_token','online')->where($map)->first();

        if(empty($result)){
            return ['code'=>0,'data'=>$result,'msg'=>'no user found'];
        }

    }
}
 