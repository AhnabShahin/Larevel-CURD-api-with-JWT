<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Image;
class ProductController extends Controller
{
    protected $user;
 
    public function __construct()
    {
        $this->user = JWTAuth::parseToken()->authenticate();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        return $this->user
            ->products()
            ->get();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request);
        // dd($request->title);
        //Validate data
        $data = $request->only('title', 'main_img', 'background_img', 'price');
        $validator = Validator::make($data, [
            'title' => 'required|string',
            'main_img' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'background_img' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'price' => 'required',
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }
        $slug=preg_replace('/\s+/', '-', $request->title);
        $main_image = $request->file('main_img') ;
        $background_image = $request->file('background_img') ;
        
        $destinationPath = 'uploads/Product/';
        
        $main_image_name = date('mdHis') .'-'.$slug.'-main'. "." . $main_image->getClientOriginalExtension();
        $background_image_name = date('mdHis') .'-'.$slug.'-background'.  "." . $background_image->getClientOriginalExtension();
        
        $main_image_file = Image::make($main_image->getRealPath());
        $background_image_file = Image::make($background_image->getRealPath());
        
        $main_image_file->fit(400, 400, function ($constraint) {
		    $constraint->aspectRatio();
		})->save($destinationPath.$main_image_name);
        $background_image_file->fit(400, 400, function ($constraint) {
		    $constraint->aspectRatio();
		})->save($destinationPath.$background_image_name);

        // $main_image->move($destinationPath, $main_image_name);
        // $background_image->move($destinationPath, $background_image_name);
        $request->main_img = "$main_image_name";
        $request->background_img = "$background_image_name";

        // dd($request->title);
        //Request is valid, create new product
        $product = $this->user->products()->create([
            'title' => $request->title,
            'slug' => $slug,
            'main_img' => $request->main_img,
            'background_img' => $request->background_img,
            'price' => $request->price,
        ]);

        //Product created, return success response
        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = $this->user->products()->find($id);
    
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, product not found.'
            ], 400);
        }
    
        return $product;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //Validate data
        $data = $request->only('title', 'price');
        $validator = Validator::make($data, [
            'title' => 'required|string',
            'price' => 'required',
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        //Request is valid, update product
        $product = $product->update([
            'title' => $request->title,
            'price' => $request->price,
        ],
        ['timestamps' => true]);

        //Product updated, return success response
        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $product->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ], Response::HTTP_OK);
    }
    public static function createSlug($str){
        return preg_replace('/\s+/', '-', $str);
    }
}