<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        }else{
            return ['code'=>1,"data"=>"valid data","msg"=>"success"];
        }
    }
}
 