<?php

namespace DnaWeb\GridView;

use DnaWeb\GridView\DataTableQuery;
use Illuminate\Support\Facades\DB;

class GridView{

    //step 1
    public static function of($source){

        DB::enableQueryLog();

        return self::make($source);
    }

    //step 2
    public static function make($source){

        $args = func_get_args();

        return call_user_func_array([DataTableQuery::class, 'create'], $args);
    }

	public static function filter(){

		$request = request();
		$filter = [];
		foreach($request->columns as $cols){
			$filter[$cols->name] = $cols->search->value;
		}

		return json_decode(json_encode($filter), FALSE);
	}
}
