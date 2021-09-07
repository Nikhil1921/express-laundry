<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Category extends Admin_Controller {

	protected $redirect = 'category';
    protected $title = 'Category';
	protected $table = 'category';
    protected $name = 'category';
    protected $path = 'assets/images/category/';

	public function index()
	{
		$data['name'] = $this->name;
		$data['title'] = $this->title;
		$data['url'] = $this->redirect;
        $data['dataTable'] = TRUE;

		return $this->template->load('template', "$this->redirect/home", $data);
	}

    public function get()
    {
        check_ajax();
        $this->load->model('category_model', 'data');
        $fetch_data = $this->data->make_datatables();
        $sr = $_POST['start'] + 1;
        $data = [];
        foreach($fetch_data as $row)
        {  
            $sub_array = [];
            $sub_array[] = $sr;
            $sub_array[] = ucwords($row->cat_name);
            $sub_array[] = img(['src' => $this->path.$row->cat_image, 'width' => '100', 'height' => '100']);
            $action = '<div style="display: inline-flex;" class="icon-btn">';

            $action .= form_button(['content' => '<i class="fa fa-pencil" ></i>', 'type'  => 'button', 'data-url' => base_url($this->redirect.'/update/'.e_id($row->id)),
                        'data-title' => "Update $this->title", 'onclick' => "getModalData(this)", 'class' => 'btn btn-primary btn-outline-primary btn-icon mr-2']);

            $action .= form_open($this->redirect.'/delete', 'id="'.e_id($row->id).'"', ['id' => e_id($row->id)]).
            form_button([ 'content' => '<i class="fa fa-trash"></i>',
                'type'  => 'button',
                'class' => 'btn btn-danger btn-outline-danger btn-icon', 
                'onclick' => "script.delete(".e_id($row->id)."); return false;"]).
            form_close();

            $action .= '</div>';
            $sub_array[] = $action;

            $data[] = $sub_array;  
            $sr++;
        }

        $output = [
            "draw"              => intval($_POST["draw"]),  
            "recordsTotal"      => $this->data->count(),
            "recordsFiltered"   => $this->data->get_filtered_data(),
            "data"              => $data
        ];
        
        echo json_encode($output);
    }

    public function add()
    {
        check_ajax();
        if ($this->input->server('REQUEST_METHOD') === 'GET') {
            $data['name'] = $this->name;
            $data['title'] = $this->title;
            $data['operation'] = 'add';
            $data['url'] = $this->redirect;
            
            return $this->load->view("$this->redirect/add", $data);
        }else{
            $this->form_validation->set_rules($this->validate);
            if ($this->form_validation->run() == FALSE)
            $response = [
                    'message' => str_replace("*", "", strip_tags(validation_errors('','<br>'))),
                    'status' => false
                ];
            else{
            	$image = $this->uploadImage('image');
            	if ($image['error'] == TRUE)
			        $response = [
			                'message' => str_replace("*", "", strip_tags($image['message'])),
			                'status' => false
			            ];
			    else{
	                $post = [
	                    'cat_name'  => $this->input->post('cat_name'),
	                    'cat_image' => $image['message']
	                ];

	                if ($this->main->add($post, $this->table))
	                    $response = [
	                        'message' => "$this->title added.",
	                        'status' => true
	                    ];
	                else
	                    $response = [
	                        'message' => "$this->title not added. Try again.",
	                        'status' => false
	                    ];
                }
            }
            echo json_encode($response);
        }
    }

    public function update($id)
    {
        check_ajax();
        if ($this->input->server('REQUEST_METHOD') === 'GET') {
            $data['name'] = $this->name;
            $data['title'] = $this->title;
            $data['operation'] = 'update';
            $data['url'] = $this->redirect;
            $data['id'] = $id;
            $data['data'] = $this->main->get($this->table, 'cat_name, cat_image', ['id' => d_id($id)]);

            return $this->load->view("$this->redirect/update", $data);
        }else{
            $this->form_validation->set_rules($this->validate);
            if ($this->form_validation->run() == FALSE)
            $response = [
                    'message' => str_replace("*", "", strip_tags(validation_errors('','<br>'))),
                    'status' => false
                ];
            else{
            	if (empty($_FILES['image']['name']))
            		$img = $this->input->post('image');
            	else{
            		$image = $this->uploadImage('image');
	            	if ($image['error'] == TRUE)
	            		die(json_encode([
				                'message' => str_replace("*", "", strip_tags($image['message'])),
				                'status' => false
				            ]));
				    else{
                        if (file_exists($this->path.$this->input->post('image')))
                            unlink($this->path.$this->input->post('image'));
            			$img = $image['message'];
                    }
            	}
                $post = [
	                    'cat_name'  => $this->input->post('cat_name'),
	                    'cat_image' => $img
	                ];
                
                if ($this->main->update(['id' => d_id($id)], $post, $this->table))
                    $response = [
                        'message' => "$this->title updated.",
                        'status' => true
                    ];
                else
                    $response = [
                        'message' => "$this->title not updated. Try again.",
                        'status' => false
                    ];
            }
            echo json_encode($response);
        }
    }

    public function delete()
    {
        check_ajax();
        $this->form_validation->set_rules('id', 'id', 'required|numeric');
        if ($this->form_validation->run() == FALSE)
            $response = [
                        'message' => "Some required fields are missing.",
                        'message' => validation_errors(),
                        'status' => false
                    ];
        else
            if ($this->main->update(['id' => d_id($this->input->post('id'))], ['is_deleted' => 1], $this->table))
                $response = [
                    'message' => "$this->title deleted.",
                    'status' => true
                ];
            else
                $response = [
                    'message' => "$this->title not deleted. Try again.",
                    'status' => false
                ];
        echo json_encode($response);
    }

    protected $validate = [
        [
            'field' => 'cat_name',
            'label' => 'Category Name',
            'rules' => 'required',
            'errors' => [
                'required' => "%s is Required"
            ]
        ]
    ];
}