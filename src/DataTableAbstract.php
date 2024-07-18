<?php

namespace DnaWeb\GridView;

use DnaWeb\GridView\Utils;
use Illuminate\Support\Facades\Schema;

abstract class DataTableAbstract{

    protected $columnDef = [
        'append' => []
    ];

    protected $extraColumns = [];

    //Step 3
    public static function create($source){

        return new static($source);
    }

    //Step 7
    public function getOperator($request){

        $operator = [];
        if(isset($request->oprFilter)){
			foreach($request->oprFilter as $name => $val){
				if($val->value){
					$val->name = str_replace("opr_", "", $val->name);
					$operator[$val->name] = $val->value;
				}
			}
		}

        return $operator;
    }

    //Step 8
    public function getColumns($request){

        $columns = [];
        if(isset($request->columns)){
            foreach($request->columns as $k => $cols){
                if($cols->data){
                    $filter = true;
                    if(strpos($cols->data, 'raw_') !== false){
                        $filter = false;
                    }
                    $setOrder = null;
                    if(isset($getOrder[$k])){
                        $setOrder = $getOrder[$k];
                    }
                    $columns[] = [
                        'name' => $cols->name,
                        'value' => $cols->search->value,
                        'order' => $setOrder,
                        'filter' => $filter
                    ];
                }
            }
        }

        return $columns;
    }

    //Step 9
    public function getDefaultOrder($request){

        $data = [];
		if(@$request->order){
			foreach($request->order as $order){
                if($order->dir){
                    $data[$order->name] = $order->dir;
                }
			}
		}

        return $data;
    }

    //Step 10
    public function getCustomerMultipleOrder($request){

        $data = [];
        if(isset($request->sortingFilter)){
            foreach($request->sortingFilter as $sorting){
                if($sorting->value){
                    $data[$sorting->name] = $sorting->value;
                }
            }
        }

        return $data;
    }

    //Step 11
	public function searchWithOperator($query, $operator, $column, $value){

        $getPrefixOperator = Utils::prefixOperator();
        foreach($getPrefixOperator as $prefix){
            if($prefix['operator_name'] == $operator){
                $value = $prefix['condition_sql'] ? str_replace('@params', $value, $prefix['condition_sql']) : $value;
                $conditon = $prefix['condition_func'];
                if($operator == 'IN' || $operator == 'NOT IN'){
                    $valueArr = explode(',', $value);
                    $valueArr = array_map('trim', $valueArr);
                    $query->$conditon($column, $valueArr);
                }else{
                    //dd($conditon, $column, $operator, $value);
                    $query->$conditon($column, $operator, $value);
                }
            }
        }

		return $query;
	}

    //Step 12
    public function checkValidDate($date){

		return preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date);
	}

    //Step 14
    public function rawQueryTable($query){

        $tableFrom = $query->getQuery()->from;
        $tableFromAlias = '';
        if (strpos($tableFrom, ' as ') !== false) {
            $tableArr = explode(' as ', $tableFrom);
            $tableFrom = $tableArr[0];
            $tableFromAlias = $tableArr[1];
        }
        if (strpos($tableFrom, '.') !== false) {
            $tableFrom = explode('.', $tableFrom)[1];
        }

        $setTableFrom[$tableFromAlias ? $tableFromAlias : $tableFrom] = $this->getColumnListing($tableFrom, $tableFromAlias);

        $listTableJoin = [];
        foreach($query->getQuery()->joins as $tableJoin){
            $table = $tableJoin->table;
            $alias = '';
            if (strpos($tableJoin->table, ' as ') !== false) {
                $tableArr = explode(' as ', $table);
                $table = $tableArr[0];
                $alias = $tableArr[1];
            }
            $listTableJoin[$alias ? $alias : $table] = $this->getColumnListing($table, $alias);
        }

        $table = array_merge($setTableFrom, $listTableJoin);

        $selectRaw = '';
        $listSelect = $query->getQuery()->columns;
        foreach($listSelect as $selectColumn){
            if (strpos($selectColumn, '.*') !== false) {
                $selectColumn = str_replace('.*', '', $selectColumn);
                if(isset($table[$selectColumn])){
                    $selectColumn = $table[$selectColumn]['select'];
                }
            }

            $selectRaw .= $selectColumn.',';
        }
        $selectRaw = rtrim($selectRaw, ",");

        $selectRawList = [];
        foreach(explode(',', $selectRaw) as $raw){
            $getTablesColumns = explode('.', $raw);
            $selectRawList[] = [
                'column_with_table' => $raw,
                'column' => $getTablesColumns[1],
                'data_type' => $table[$getTablesColumns[0]]['columns'][$getTablesColumns[1]]['data_type'],
                'description' => $table[$getTablesColumns[0]]['columns'][$getTablesColumns[1]]['description'],
            ];
        }

        return [
            'query' => $query->toSql(),
            'select' => $listSelect,
            'selectRaw' => [
                'raw' => $selectRaw,
                'list' => $selectRawList
            ],
            'table' => $table
        ];
    }

    //Step 17
    public function getColumnListing($tableName, $aliasName){

        $getColumns = [];
        $selectColumn = '';
        foreach(Schema::getColumnListing($tableName) as $col){
            $prefixTable = $aliasName ? $aliasName : $tableName;
            $setDescription = str_replace('_', ' ', $col);
            $setDescription = ucwords($setDescription);
            $getColumns[$col] = [
                'name' => $col,
                'description' => $setDescription,
                'data_type' => Schema::getColumnType($tableName, $col)
            ];
            $selectColumn .= $prefixTable.'.'.$col.',';
        }

        $selectColumn = rtrim($selectColumn, ",");

        return [
            'prefixTable' => $prefixTable,
            'table' => $tableName,
            'alias' => $aliasName,
            'columns' => $getColumns,
            'select' => $selectColumn
        ];
    }

    public function addColumn($name, $content, $order = false){

        $this->extraColumns[] = $name;

        $this->columnDef['append'][] = ['name' => $name, 'content' => $content];

        return $this;
    }
}
