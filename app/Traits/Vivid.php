<?php
namespace BRM\Vivid\app\Traits;

use Validator;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Str;

// use App\Traits\Permissions;
trait Vivid
{
    // use Permissions;

    public $validation = [];
    public $sanitise = [];
    public $hooks = [
    // beforeSave
    // afterSave
    // beforeValidation
    // afterValidation
  ];

    public function merge_validation()
    {
        return array_merge_recursive($this->sanitise, $this->validation);
    }
    public function validate()
    {
        $this->callback('beforeValidation');
        $validator = Validator::make($this->data, $this->merge_validation());
        if ($validator->fails()) {
            return [
        'status'=>'failed',
        'data'=>[
          'errors'=> $validator->errors()->all()
        ]
      ];
        }
        $this->callback('afterValidation');
        return false;
    }
    public function query_builder()
    {
        $builder = QueryBuilder::for($this->model);
        if (isset($this->filters)) {
            $builder->allowedFilters($this->filters);
        }
        if (isset($this->includes)) {
            $builder->allowedIncludes($this->includes);
        }
        return $builder;
    }
    public function vivid($do, $data)
    {
        $this->data = $data;
        $this->table = (new $this->model)->getTable();
        // $can = $this->permit($this->table.'/'.$do); if($can['status'] === 'failed'){ return $can; }
        return $this->{'vivid_'.$do}();
    }

    public function vivid_index()
    {
        if ($f = $this->validate()) {
            return $f;
        };
        $this->records = $this->query_builder();
        if (!isset($this->data['chunk']) || $this->data['chunk'] !== 'none') {
            $this->records = $this->records->paginate(2, ['*'], 'chunk')->toArray();
            $this->response = [
              'status'=>'success',
              'data'=>[
                'pagination'=> [
                  'total' => isset($this->records['total']) ? $this->records['total'] : null,
                  'chunked' => isset($this->records['last_page']) ? $this->records['last_page'] : null,
                  'chunks' => [
                    'current' => isset($this->records['current_page']) ? $this->records['current_page'] : null,
                    'previous'=> (isset($this->records['current_page']) && $this->records['current_page'] > 1) ? ($this->records['current_page'] - 1) : null,
                    'next' => (isset($this->records['current_page']) && $this->records['current_page'] < $this->records['last_page']) ? ($this->records['current_page'] + 1) : null,
                    'last' => isset($this->records['last_page']) ? $this->records['last_page'] : null
                  ]
                ],
                $this->table => $this->records['data']
              ]
            ];
        } else {
            $this->response = [
              'status'=>'success',
              'data'=>[
                $this->table => $this->records->get()->toArray()
              ]
      ];
        }
        return $this->response;
    }


    public function vivid_store()
    {
        if (isset($this->data[$this->table])) {
            /* Handle Multiple inputs */
            $backup = $this->data;
            $resources = $this->data[$this->table];
            $responses = [];
            foreach ($resources as $resource) {
                $this->data = $resource;
                array_push($responses, $this->_vivid_store_record());
            }
            $this->response = [
              'status'=>'success',
              'data'=>[
                'slots'=> $responses
              ]
            ];
        } else {
            /* Handle one input */
            $this->response = $this->_vivid_store_record();
        }
        return $this->response;
    }
    public function _vivid_store_record()
    {
        if ($f = $this->validate()) {
            return $f;
        };
        $this->record = (new $this->model);
        foreach ($this->fields as $field) {
            if (isset($this->data[$field])) {
                $this->record->{$field} = $this->data[$field];
            }
        }

        if ($this->callback('beforeSave') === false) {
            return $this->response;
        }
        $this->record->save();
        $this->callback('afterSave');

        if (isset($this->includes)) {
            foreach ($this->includes as $includes) {
                /* Handles relational updates */
                if (isset($this->data[$includes])) {
                    if ($inc = $this->record->{$includes}()->first()) {
                        foreach ($this->data[$includes] as $parameter=>$value) {
                            $inc->{$parameter} = $value;
                        }
                        $inc->save();
                    }
                }
            }
        }

        $this->response = [
          'status'=>'success',
          'data'=>[
            Str::singular($this->table) => (new $this->model)::find($this->record->id)
          ]
        ];
        return $this->response;
    }

    public function vivid_show()
    {
        // $this->validation = [
        //   Str::singular($this->table) => ['required','numeric','exists:'.$this->table.',id,deletedAt,NULL']
        // ];
        if ($f = $this->validate()) {
            return $f;
        };
        $this->record = $this->query_builder();
        $this->response = [
      'status'=>'success',
      'data'=>[
        Str::singular($this->table) => $this->record->find($this->data[Str::singular($this->table)])->toArray()
      ]
    ];
        return $this->response;
    }

    public function vivid_update()
    {
        // $this->validation = [
        //   Str::singular($this->table) => ['required','numeric','exists:'.$this->table.',id,deletedAt,NULL']
        // ];
        if ($f = $this->validate()) {
            return $f;
        };

        $this->record = (new $this->model)::where([['id','=',$this->data[Str::singular($this->table)]]])->first();
        foreach ($this->fields as $field) {
            if (isset($this->data[$field])) {
                $this->record->{$field} = $this->data[$field];
            }
        }
        if (isset($this->includes)) {
            foreach ($this->includes as $includes) {
                /* Handles relational updates */
                if (isset($this->data[$includes])) {
                    if ($inc = $this->record->{$includes}()->first()) {
                        foreach ($this->data[$includes] as $parameter=>$value) {
                            $inc->{$parameter} = $value;
                        }
                        $inc->save();
                    }
                }
            }
        }

        $this->callback('beforeSave');
        $this->record->save();
        $this->callback('afterSave');
        $this->record = $this->query_builder();
        $this->response = [
        'status'=>'success',
          'data'=>[
            Str::singular($this->table) => $this->record->find($this->data[Str::singular($this->table)])->toArray()
          ]
        ];
        return $this->response;
    }

    public function vivid_destroy()
    {
        //     $this->validation = [
        //   Str::singular($this->table) => ['required','numeric','exists:'.$this->table.',id,deletedAt,NULL']
        // ];
        if ($f = $this->validate()) {
            return $f;
        };
        $this->record = (new $this->model)::where([['id','=',$this->data[Str::singular($this->table)]]])->first();
        $this->callback('beforeDelete');
        $this->record->delete();
        $this->callback('afterDelete');
        $this->response = [
      'status'=>'success',
      'data'=>[
        Str::singular($this->table) => null
      ]
    ];
        return $this->response;
    }

    /* To help with smaller services */
    /**
     * index
     *
     * @param mixed $data
     * @return void
     */
    public function index($data = [])
    {
        return $this->vivid('index', $data);
    }
    /**
     * store
     *
     * @param mixed $data
     * @return void
     */
    public function store($data = [])
    {
        return $this->vivid('store', $data);
    }
    /**
     * show
     *
     * @param mixed $data
     * @return void
     */
    public function show($data = [])
    {
        return $this->vivid('show', $data);
    }
    /**
     * update
     *
     * @param mixed $data
     * @return void
     */
    public function update($data = [])
    {
        return $this->vivid('update', $data);
    }
    /**
     * destroy
     *
     * @param mixed $data
     * @return void
     */
    public function destroy($data = [])
    {
        return $this->vivid('destroy', $data);
    }


    public function callback($when)
    {
      if (isset($this->record)) {
        event('vivid.'.$this->table.'.'.$when, $this->record);
      }
    
        if (isset($this->hooks[$when])) {
            return $this->hooks[$when]();
        }
        return true;
    }
    public function hook($when, $callback)
    {
        $this->hooks = array_merge($this->hooks, [$when => $callback]);
    }
}
