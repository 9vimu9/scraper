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

Route::get('/', function () {
   // echo file_get_html('http://www.google.com/')->plaintext;
    try{
        $brandProduct=new \App\ModernBrandsProduct();
        $product=['name'=>'aadada','sku'=>'DKCM001'];
        $brandProduct->cleanPreviousImport();
        return $brandProduct->updateModernBrandProduct($product);
    }
        catch(Exception $r)
        {
            return "ff";
            return $r->getMessage();
        }

    //return view('welcome');
});
