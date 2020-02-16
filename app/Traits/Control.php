<?php
namespace BRM\Vivid\app\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait Control {
  public $hooks = [

  ];

  public function index(Request $request){
    $response = (new $this->service)->index($request->all());
    return response()->api($response,200);
  }
  public function store(Request $request){
    $response = (new $this->service)->store($request->all());
    return response()->api($response,200);
  }
  public function show(Request $request, $id = null){
    $namespace = explode('\\', __CLASS__);
    $namespace = Str::lower(Str::singular(array_pop($namespace)));
    if($id === 'me'){
      $request->request->add([ $namespace => ($request->user() ? $request->user()->id : null) ]);
    } else if($id!== NULL){
      $request->request->add([$namespace => $id]);
    }
    $response = (new $this->service)->show($request->all());
    return response()->api($response,200);
  }
  public function update(Request $request, $id = null){
    $namespace = explode('\\', __CLASS__);
    $namespace = Str::lower(Str::singular(array_pop($namespace)));
    if($id === 'me'){
      $request->request->add([ $namespace => ($request->user() ? $request->user()->id : null) ]);
    } else if($id!== NULL){
      $request->request->add([$namespace=>$id]);
    }
    $response = (new $this->service)->update($request->all());
    return response()->api($response,200);
  }
  public function destroy(Request $request, $id = null){
    $namespace = explode('\\', __CLASS__);
    $namespace = Str::lower(Str::singular(array_pop($namespace)));
    if($id === 'me'){
      $request->request->add([ $namespace => ($request->user() ? $request->user()->id : null) ]);
    } else if($id!== NULL){
      $request->request->add([$namespace=>$id]);
    }
    $response = (new $this->service)->destroy($request->all());
    return response()->api($response,200);
  }
 
  public function callback($when){
    if(isSet($this->hooks[$when])){
      $this->hooks[$when]();
    }
  }

  public function hook($when,$callback){
    $this->hooks = array_merge($this->hooks,[$when => $callback]);
  }

}