<?php

namespace DnaWeb\Gridview;

use DnaWeb\Gridview\GridViewAbstract;
use Illuminate\Support\Facades\DB;

class GridViewQuery extends GridViewAbstract{

    protected $source = [];

    protected $debug = [];

    protected $queryLogs = [];

    protected $draw = [];

    protected $recordsTotal = 0;

    protected $recordsFiltered = 0;

    protected $pagingLimit = 0;

    protected $pagingStart = 0;

    public function __construct($source){
        $this->source = $source;
    }

    //Step 4
    public function make(){

        $this->reBuildSource();

        return [
            'draw'              => $this->draw,
			'recordsTotal'      => $this->recordsTotal,
			'recordsFiltered'   => $this->recordsFiltered,
			'data'              => json_decode(json_encode($this->source), FALSE),
			'debug'             => $this->debug,
            'queryLogs'         => $this->queryLogs
        ];
    }

    //Step 5
    public function reBuildSource(){

        $this->queryCondition();

        foreach($this->columnDef['append'] as $append){
            $getColumn = $append['content'];
            foreach($this->source as $k => $source){
                $source[$append['name']] = $getColumn($source);
                $this->source[$k] = $source;
            }
        }

        return $this;
    }

    //Step 6
    public function queryCondition(){

        $query = $this->source;
        //replace default length to custom length
        request()->merge([
            'length' =>  request()->globalEntries
        ]);
        $request = json_decode(json_encode(request()->all()), FALSE);
		$operator = $this->getOperator($request);
		$columns = $this->getColumns($request);
		$defaultOrder = $this->getDefaultOrder($request);
        $multipleOrder = $this->getCustomerMultipleOrder($request);

        if($defaultOrder || $multipleOrder){
            $query->reorder();
        }

		if($columns){
			foreach($columns as $col){
				if(isset($operator[$col['name']])){
                    if($col['value']){
                        $this->searchWithOperator($query, $operator[$col['name']], $col['name'], $col['value']);
                    }
				}else{
					if($col['value'] && ($col['filter']) == true){
						if($this->checkValidDate($col['value'])){
							$query->whereDate($col['name'], $col['value']);
						}else{
							$query->where($col['name'], 'ilike', '%'.$col['value'].'%');
						}
					}
				}

                if(isset($multipleOrder[$col['name']])){
                    $query->orderBy($col['name'], $multipleOrder[$col['name']]);
                }else{
                    if(isset($defaultOrder[$col['name']])){
                        $query->orderBy($col['name'], $defaultOrder[$col['name']]);
                    }
                }
			}

            if($request->globalSearch){
                $this->globalSearch($query, $request->globalSearch, $columns);
            }
		}

        $this->debug = $this->rawQueryTable($query);

        $totalData = $query->count();
        $totalFilter = $totalData;
        $this->draw = isset($request->draw) ? intval($request->draw) : 0;
        $this->recordsTotal = intval($totalData);
        $this->recordsFiltered = intval($totalFilter);
        $this->pagingLimit = $request->length == -1 ? $totalFilter : $request->length;
        $this->pagingStart = isset($request->start) ? $request->start : 0;
        $this->source = $query->skip($this->pagingStart)->take($this->pagingLimit)->get()->toArray();
        $this->queryLogs = DB::getQueryLog();
        $this->debug['query_all'] = $query->toSql();

        return $this;
    }

    //Step 13
    public function globalSearch($query, $search, $arrColumns){

        foreach($arrColumns as $k => $columns){
            $condition = $k == 0 ? 'where' : 'orWhere';
            $query->$condition($columns['name'], 'LIKE', '%'.$search.'%');
        }

        return $query;
    }

    public function addColumn($name, $content, $order = false){

        return parent::addColumn($name, $content, $order);
    }
}
