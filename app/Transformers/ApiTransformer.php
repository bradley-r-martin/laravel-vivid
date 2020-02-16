<?php
 
namespace BRM\Vivid\app\Transformers;

use Illuminate\Http\Request;

class ApiTransformer{
  public static function response($response = null,$status = null){
    return response()->json($response, $status);
  }
}