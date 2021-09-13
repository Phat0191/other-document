<?php
 
namespace App\Http\Controllers;
 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\UsersResource;
use App\Models\User;
use App\Models\UserCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UsersController extends Controller
{
     /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['onLogin', 'onRegister','getCode']]);
    }
     /**
     * @SWG\POST(
     *     path="api/users/login/",
     *     description="Return a user's information",
     *     @SWG\Parameter(
     *         name="email",
     *         in="query",
     *         type="string",
     *         description="Your email",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="password",
     *         in="query",
     *         type="string",
     *         description="Your password",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Successfully",
     *         @SWG\Schema(
     *             @SWG\Property(property="id", type="string", description="UUID"),
     *             @SWG\Property(property="name", type="string"),
     *             @SWG\Property(property="email", type="string"),
     *             @SWG\Property(property="email_verified_at", type="string"),
     *             @SWG\Property(property="created_at", type="timestamp"),
     *             @SWG\Property(property="updated_at", type="timestamp"),
     *             @SWG\Property(property="avatar", type="timestamp"),
     *            )
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Login không thành công!"
     *     )
     * )
     */
    public function onLogin(Request $request)
     {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['error'=>"Login không thành công!"], 401);      
        }
 
        $user = User::where("email",$request->email)->get();
        if($user->count()>0){
            if (! $token = auth()->attempt($validator->validated())) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
    
            return $this->createNewToken($token);
        }
        return response()->json(['error'=>"Login không thành công!"], 401);  
    }
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }
 

     /**
     * @SWG\POST(
     *     path="api/users/register/",
     *     description="Return a user's information",
     *     @SWG\Parameter(
     *         name="code",
     *         in="query",
     *         type="string",
     *         description="Your code(length=6)",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Successfully",
     *         @SWG\Schema(
     *             @SWG\Property(property="fullName", type="string"),
     *             @SWG\Property(property="nameAccount", type="string"),
     *             @SWG\Property(property="linkFB", type="string"),
     *             @SWG\Property(property="phone", type="string"),
     *             @SWG\Property(property="birthday", type="string"),
     *             @SWG\Property(property="address", type="string"),
     *             @SWG\Property(property="sex", type="string"),
     *             @SWG\Property(property="email", type="string"),
     *             @SWG\Property(property="password", type="string"),
     *             @SWG\Property(property="remember_token", type="string"),
     *             @SWG\Property(property="created_at", type="timestamp"),
     *             @SWG\Property(property="updated_at", type="timestamp"),
     *             @SWG\Property(property="avatar", type="string"),
     *            )
     *     ),
     *     @SWG\Response(
     *         response=422,
     *         description="Missing Data"
     *     )
     * )
     */
    public function onRegister(Request $request){
        $validator = Validator::make($request->all(), [
            'code' => 'required|size:6',
        ]);
        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);     
        }
        $data = DB::table('user_code')->where('code', $request->code)->first();
        if($data){
            if ($data->sex==="female"){
                $sex="female_avatar.jpg";
            }
            else{
                $sex="male_avatar.jpg";
            }
            $postArray = [
                'fullName'  => $data->fullName,
                'nameAccount'  => $data->nameAccount,
                'linkFB'  => $data->linkFB,
                'phone'  => $data->phone,
                'birthday'  => $data->birthday,
                'address'  => $data->address,
                'sex'  => $data->sex,
                'email'     => $data->email,
                'password'  => $data->password,
                'remember_token' => $request->token,
                'created_at'=> Carbon::now('Asia/Ho_Chi_Minh'),
                'updated_at'=>Carbon::now('Asia/Ho_Chi_Minh'),
                'avatar'=>$sex,
                'status'=>"active",
            ];
            $user = User::create($postArray);
            DB::delete('delete from user_code where id = ?',[$data->id]);
            return Response()->json(array("success"=> 1,"data"=>$postArray ));
        }else{
            return response()->json(['error'=>"No code"], 422);
        }
    }


     /**
     * @SWG\POST(
     *     path="api/users/getCode/",
     *     description="Return a user's information",
     *     @SWG\Parameter(
     *         name="email",
     *         in="query",
     *         type="string",
     *         description="Your email(email)",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="fullName",
     *         in="query",
     *         type="string",
     *         description="Your fullName",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="password",
     *         in="query",
     *         type="string",
     *         description="Your password(length>7)",
     *         required=true,
     *     ),
     *  @SWG\Parameter(
     *         name="confirm_password",
     *         in="query",
     *         type="string",
     *         description="Your confirm_password(same password)",
     *         required=true,
     *     ),
     * @SWG\Parameter(
     *         name="nameAccount",
     *         in="query",
     *         type="string",
     *         description="Your nameAccount",
     *         required=true,
     *     ),
     * * @SWG\Parameter(
     *         name="linkFB",
     *         in="query",
     *         type="string",
     *         description="Your linkFB",
     *         required=true,
     *     ),
     * * @SWG\Parameter(
     *         name="phone",
     *         in="query",
     *         type="string",
     *         description="Your phone(only number,start:0 and 9<length<12)",
     *         required=true,
     *     ),
     * * @SWG\Parameter(
     *         name="birthday",
     *         in="query",
     *         type="datetime",
     *         description="Your birthday(start before today)",
     *         required=true,
     *     ),
     * * @SWG\Parameter(
     *         name="address",
     *         in="query",
     *         type="string",
     *         description="Your address",
     *         required=true,
     *     ),
     * * @SWG\Parameter(
     *         name="sex",
     *         in="query",
     *         type="string",
     *         description="Your sex(male or female)",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Successfully. Please check code your email!",
     *         @SWG\Schema(
     *             @SWG\Property(property="fullName", type="string"),
     *             @SWG\Property(property="nameAccount", type="string"),
     *             @SWG\Property(property="linkFB", type="string"),
     *             @SWG\Property(property="phone", type="string"),
     *             @SWG\Property(property="birthday", type="string"),
     *             @SWG\Property(property="address", type="string"),
     *             @SWG\Property(property="sex", type="string"),
     *             @SWG\Property(property="email", type="string"),
     *             @SWG\Property(property="password", type="string"),
     *             @SWG\Property(property="created_at", type="timestamp"),
     *             @SWG\Property(property="updated_at", type="timestamp"),
     *            )
     *     ),
     *     @SWG\Response(
     *         response=422,
     *         description="Missing Data"
     *     )
     * )
     */
    public function getCode(Request $request){
        $validator = Validator::make($request->all(), [
            'fullName' => 'required|max:255',
            'nameAccount' => 'required|max:255',
            'linkFB'=>'required',
            'phone' => 'required|numeric|starts_with:0|digits_between:10,12',
            'email' => 'required|email|unique:users',
            'birthday' => 'required|before:today',
            'password' => 'required|max:255|min:8',
            'confirm_password' => 'required|same:password',
            'address' => 'required',
            'sex' => 'required|in:male,female',
        ]);
        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);     
        }
        $code=$random = Str::random(6);
        $data = DB::table('user_code')->where('email', $request->email)->first();
        if($data){
        $user = UserCode::find($data->id);
        $user->fullName = $request->fullName;
        $user->nameAccount = $request->nameAccount;
        $user->linkFB = $request->linkFB;
        $user->phone = $request->phone;
        $user->birthday = $request->birthday;
        $user->address = $request->address;
        $user->sex = $request->sex;
        $user->password = Hash::make($request->password);
        $user->created_at = Carbon::now('Asia/Ho_Chi_Minh');
        $user->updated_at = Carbon::now('Asia/Ho_Chi_Minh');
        $user->code = $code;
        $user->save();
        $postArrayRes = [
            'fullName'  => $request->fullName,
            'nameAccount'  => $request->nameAccount,
            'linkFB'  => $request->linkFB,
            'phone'  => $request->phone,
            'birthday'  => $request->birthday,
            'address'  => $request->address,
            'sex'  => $request->sex,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'created_at'=> Carbon::now('Asia/Ho_Chi_Minh'),
            'updated_at'=> Carbon::now('Asia/Ho_Chi_Minh'),
        ];
        return Response()->json(array("Successfully. Please check code your email!"=> 1,"data"=>$postArrayRes ));    }
    else{
        $postArray = [
            'fullName'  => $request->fullName,
            'nameAccount'  => $request->nameAccount,
            'linkFB'  => $request->linkFB,
            'phone'  => $request->phone,
            'birthday'  => $request->birthday,
            'address'  => $request->address,
            'sex'  => $request->sex,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'created_at'=> Carbon::now('Asia/Ho_Chi_Minh'),
            'updated_at'=> Carbon::now('Asia/Ho_Chi_Minh'),
            'code'=>$code,
        ];
        $postArrayRes = [
            'fullName'  => $request->fullName,
            'nameAccount'  => $request->nameAccount,
            'linkFB'  => $request->linkFB,
            'phone'  => $request->phone,
            'birthday'  => $request->birthday,
            'address'  => $request->address,
            'sex'  => $request->sex,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'created_at'=> Carbon::now('Asia/Ho_Chi_Minh'),
            'updated_at'=> Carbon::now('Asia/Ho_Chi_Minh'),
        ];
         $user = UserCode::create($postArray);
       return Response()->json(array("Successfully. Please check code your email!"=> 1,"data"=>$postArrayRes ));
    }
    }

}

