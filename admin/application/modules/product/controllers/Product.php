<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class Product extends Admin_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('product_model');
		$this->load->module('stock');

	}

	public function index() {
		$this->manage();
	}

	public function manage() {
		$this->data['table_page'] = TRUE;
		$this->data['view_module'] = 'product';
		$this->data['view_file'] = 'manage';
		$this->load->module('template');
		$this->template->_shop_admin($this->data);
	}
	/*
	*
	*@create() method used to create/insert
	*	product information
	*
	*/
	public function create($product_id = NULL)
	{
		$this->data['product_page'] = TRUE;
		$this->data['product_page'] = TRUE;
		$this->data['categories'] = $this->product_model->get_all_category('dropdown');
		$this->data['types'] = $this->product_model->get_tax_discount_type('dropdown');
		$this->data['brands'] = $this->product_model->get_all_brands('dropdown');
		$this->data['units'] = $this->product_model->get_all_units('dropdown');
		$this->data['tags'] = $this->product_model->get_all_tags('dropdown');
		$this->data['colors'] = $this->product_model->get_all_colors('dropdown');
		$this->data['sizes'] = $this->product_model->get_all_sizes('dropdown');
		$this->data['product_type'] = $this->product_model->product_type('dropdown');
		$this->data['view_module'] = 'product';
		$this->data['view_file'] = 'create';

		$this->load->module('template');
		$this->template->_shop_admin($this->data);
	}
	/*
	*
	* @show() method used to show product list
	*	list loaded by Datatable ajax
	*
	*/
	public function show()
	{
		$data = array();
		$category_array = $this->product_model->get_all_category();
		$brand_array = $this->product_model->get_all_brands();
		foreach ($this->product_model->show() as $p_key => $product) {
			$sub_array = array();
			foreach ($category_array as $key => $cat) {
				if($product->category_id == $cat->id){
					$category = $cat->category_name;
				}
			}
			foreach ($category_array as $key => $sub_cat) {
				if($product->sub_category_id == $sub_cat->id){
					$sub_category = $sub_cat->category_name;
				}
			}
			foreach ($brand_array as $key => $brands) {
				if($product->brand_id == $brands->id){
					$brand = $brands->name;
				}
			}
			if($product->is_active == 1){
				$is_active = '<div class="switch">
				<div class="onoffswitch">
				<input type="checkbox" onchange="change_product_status('.$product->id.',this)" checked="checked" class="onoffswitch-checkbox status" id="example'.$p_key.'" value="0">
				<label class="onoffswitch-label" for="example'.$p_key.'">
				<span class="onoffswitch-inner"></span>
				<span class="onoffswitch-switch"></span>
				</label>
				</div>
				</div>';
			}else if($product->is_active == 0){
				$is_active = '<div class="switch">
				<div class="onoffswitch">
				<input type="checkbox" onchange="change_product_status('.$product->id.',this)" class="onoffswitch-checkbox status" id="example'.$p_key.'" value="1">
				<label class="onoffswitch-label" for="example'.$p_key.'">
				<span class="onoffswitch-inner"></span>
				<span class="onoffswitch-switch"></span>
				</label>
				</div>
				</div>';
			}
			$sub_array[] = '<input type="checkbox" name="product_id[]" class="product_checkbox" value="'.$product->id.'"/>';
			$sub_array[] = $product->title;
			$sub_array[] = $product->quantity;
			$sub_array[] = ($product->quantity == 0)?'<span class="label label-danger">Out of Stock</span>':'<span class="label label-info">In Stock</span>';
			$sub_array[] = $product->purchase_price;
			$sub_array[] = $product->sale_price;
			$sub_array[] = $product->discount;
			$sub_array[] = $category;
			$sub_array[] = $sub_category;
			$sub_array[] = $brand;
			$sub_array[] = $is_active;
			$sub_array[] = '<img src="'.FILE_UPLOAD_PATH.'/'.$product->feature_image1.'" alt="product image" height="100px" width="100px;">';
			$sub_array[] ='<div class="btn-group">
			<button data-toggle="dropdown" class="btn btn-primary dropdown-toggle" aria-expanded="false">Action</button>
			<ul class="dropdown-menu" x-placement="top-start" style="position: absolute; top: -2px; left: 0px; will-change: top, left;">
			<li><a class="dropdown-item" target="_blank" href="'.base_url('product/view?id='.$product->id).'">View</a></li>
			<li><a class="dropdown-item" onclick="add_product_tostock('.$product->id.','.$product->category_id.')" data-toggle="modal" data-target="#add_product_tostock">Add Stock</a></li>
			<li><a class="dropdown-item" href="'.base_url('product/edit/'.$product->id).'">Edit</a></li>
			<li><a class="dropdown-item" onclick="delete_product('.$product->id.')">Delete</a></li>
			<li><a class="dropdown-item" data-toggle="modal" data-target="#add_discount" onclick="add_discount('.$product->id.')">Discount</a></li>
			</ul>
			</div>';
			$data[] = $sub_array;
		}
		$output = array(
			'draw'    => intval($_POST['draw']),
			'recordsTotal'  => $this->product_model->count_total_row_of_product(),
			'recordsFiltered'  => $this->product_model->count_total_row_of_product(),
			'data'    => $data
		);
		echo json_encode($output);
	}

	/*
	*
	* @store() function used to
	*	store product information
	*
	*/
	public function store()
	{

		// $size = count($_FILES['optional_image']['name']);
		// var_dump($size);exit();
		if(!empty($_FILES)){
			$config = image_config('product_picture');

			$this->load->library('upload', $config);
			if($this->upload->do_upload('feature_image1') == true){
				$data = $this->upload->data();
				$feature_image1 = 'product_picture/'.$data['file_name'];
			}else{
				$image_err = $this->upload->display_errors();
				header("Content-type: application/json");
				echo json_encode($image_err);
				exit();
			}
			if($this->upload->do_upload('feature_image2') == true){
				$data = $this->upload->data();
				$feature_image2 = 'product_picture/'.$data['file_name'];
			}else{
				$image_err = $this->upload->display_errors();
				header("Content-type: application/json");
				echo json_encode($image_err);
				exit();
			}

		}else{
			$feature_image1 = "product_picture/default.png";
			$feature_image2  = "product_picture/default.png";
		}
		$data = array(
			'title'=>$this->input->post('title'),
			'category_id'=>$this->input->post('category_id'),
			'sub_category_id'=>$this->input->post('sub_category_id'),
			'brand_id'=>$this->input->post('brand_id'),
			'unit'=>$this->input->post('unit'),
			'tags'=>($this->input->post('tags') != '')?json_encode($this->input->post('tags')):NULL,
			'purchase_price'=>$this->input->post('purchase_price'),
			'sale_price'=>$this->input->post('sale_price'),
			'shipping_cost'=>$this->input->post('shipping_cost'),
			'discount'=>$this->input->post('discount'),
			'discount_type'=>$this->input->post('discount_type'),
			'tax'=>$this->input->post('tax'),
			'tax_type'=>$this->input->post('tax_type'),
			'color'=>($this->input->post('color') != '')?json_encode($this->input->post('color')):NULL,
			'size'=> ($this->input->post('size') != '')?json_encode($this->input->post('size')):NULL,
			'description'=>$this->input->post('description'),
			'feature_image1'=>$feature_image1,
			'feature_image2'=>$feature_image2,
			'type'=>$this->input->post('type'),
			'special_price'=>$this->input->post('special_price'),
			'start_from'=>$this->input->post('start_from'),
			'end_at'=>$this->input->post('end_at'),
		);
		$id = $this->product_model->store($data);
		$size = count($_FILES['optional_image']['name']);
		if($size > 0 ){
			$files = $_FILES;
			$config = image_config('product_picture');
			$this->load->library('upload', $config);
			for ($i=0; $i < $size; $i++) {

				$_FILES['optional_image']['name']= $files['optional_image']['name'][$i];
				$_FILES['optional_image']['type']= $files['optional_image']['type'][$i];
				$_FILES['optional_image']['tmp_name']= $files['optional_image']['tmp_name'][$i];
				$_FILES['optional_image']['error']= $files['optional_image']['error'][$i];
				$_FILES['optional_image']['size']= $files['optional_image']['size'][$i]; 

				if($this->upload->do_upload('optional_image') == true){
					$data = $this->upload->data();
					$optional_image = 'product_picture/'.$data['file_name'];
				} 
				$this->product_model->upload_optional_image(['product_id' => $id,'picture' => $optional_image]);
			}
		}
		$data = array('status'=>200);
		header("Content-type: application/json");
		echo json_encode($data);
		exit();


	}
	/*
	*
	*@edit() method used to edit a product
	*	by product id
	*
	*/
	public function edit($product_id)
	{
		$this->data['product']=$this->product_model->get_product($product_id);
		$this->data['categories'] = $this->product_model->get_all_category('dropdown');
		$this->data['types'] = $this->product_model->get_tax_discount_type('dropdown');
		$this->data['brands'] = $this->product_model->get_all_brands('dropdown');
		$this->data['units'] = $this->product_model->get_all_units('dropdown');
		$this->data['tags'] = $this->product_model->get_all_tags('dropdown');
		$this->data['colors'] = $this->product_model->get_all_colors('dropdown');
		$this->data['sizes'] = $this->product_model->get_all_sizes('dropdown');
		$this->data['optional_image'] = $this->product_model->get_image_optional_image($product_id);
		$this->data['product_type'] = $this->product_model->product_type('dropdown');
		$this->data['users_page'] = TRUE;
		$this->data['form_page'] = TRUE;
		$this->data['view_module'] = 'product';
		$this->data['view_file'] = 'edit';

		$this->load->module('template');
		$this->template->_shop_admin($this->data);
	}

	/*
	*
	*@update() method used to update
	*	product information after edit
	*
	*/
	public function update()
	{
		$config = image_config('product_picture');
		$this->load->library('upload', $config);
		$data = array();
		$product_id = $this->input->post('product_id');
		$product = $this->product_model->get_product($product_id);
		if(!empty($_FILES['feature_image1']['tmp_name'])){
			$path = $product->feature_image1;
			$this->product_model->unlink_product_image($product_id,$path);
			if($this->upload->do_upload('feature_image1') == true){
				$image_data = $this->upload->data();
				$data['feature_image1'] = 'product_picture/'.$image_data['file_name'];
			}
		}
		if(!empty($_FILES['feature_image2']['tmp_name'])){
			$path = $product->feature_image2;
			$this->product_model->unlink_product_image($product_id,$path);
			if($this->upload->do_upload('feature_image2') == true){
				$image_data = $this->upload->data();
				$data['feature_image2'] = 'product_picture/'.$image_data['file_name'];
			}
		}

		$data['title'] = $this->input->post('title');
		$data['category_id'] = $this->input->post('category_id');
		$data['sub_category_id'] = $this->input->post('sub_category_id');
		$data['brand_id'] = $this->input->post('brand_id');
		$data['unit'] = $this->input->post('unit');
		$data['tags'] = ($this->input->post('tags') != '')?json_encode($this->input->post('tags')):NULL;
		$data['purchase_price'] = $this->input->post('purchase_price');
		$data['sale_price'] = $this->input->post('sale_price');
		$data['shipping_cost'] = $this->input->post('shipping_cost');
		$data['discount'] = $this->input->post('discount');
		$data['discount_type'] = $this->input->post('discount_type');
		$data['tax'] = $this->input->post('tax');
		$data['tax_type'] = $this->input->post('tax_type');
		$data['color']=($this->input->post('color') != '')?json_encode($this->input->post('color')):NULL;
		$data['size']= ($this->input->post('size') != '')?json_encode($this->input->post('size')):NULL;
		$data['description']= $this->input->post('description');
		$data['type'] = $this->input->post('type');
		$data['special_price'] = $this->input->post('special_price');
		$data['start_from'] = $this->input->post('start_from');
		$data['end_at'] = $this->input->post('end_at');
		$updated = $this->product_model->update($product_id,$data);
		if(isset($_FILES['optional_image']['name'])){
			$size = sizeof($_FILES['optional_image']['name']);
			if($size > 0 ){
				$files = $_FILES;
				$config = image_config('product_picture');
				$this->load->library('upload', $config);
				for ($i=0; $i < $size; $i++) {

					$_FILES['optional_image']['name']= $files['optional_image']['name'][$i];
					$_FILES['optional_image']['type']= $files['optional_image']['type'][$i];
					$_FILES['optional_image']['tmp_name']= $files['optional_image']['tmp_name'][$i];
					$_FILES['optional_image']['error']= $files['optional_image']['error'][$i];
					$_FILES['optional_image']['size']= $files['optional_image']['size'][$i]; 

					if($this->upload->do_upload('optional_image') == true){
						$data = $this->upload->data();
						$optional_image = 'product_picture/'.$data['file_name'];
					} 
					$this->product_model->upload_optional_image(['product_id' => $product_id,'picture' => $optional_image]);
				}
			}
		}

		$data = array('status'=>200,'message'=>'succesfully updated');
		header("Content-type: application/json");
		echo json_encode($data);
		exit();

	}



	/*
	* @delete() method has two type
	*	if multiple_delete parameter found the mulitiple data willbe
	*	deleted.
	*	else single data will be delted
	*/
	public function delete()
	{

		$product_id = $this->input->post('product_id');
		if(!is_array($product_id)){
			$unlink_image = $this->product_model->unlink_product_image($product_id);
			if($unlink_image == true){
				$delete_product = $this->product_model->delete($product_id);
				if($delete_product == 200){
					$data = array('status'=>200,'message'=>'product deleted');
					header("Content-type: application/json");
					echo json_encode($data);
					exit();
				}else{
					$data = array('status'=>200,'message'=>'failed to delete product');
					header("Content-type: application/json");
					echo json_encode($data);
					exit();
				}
			}
		}else{
			foreach ($product_id as $key => $p_id) {
				$unlink_image = $this->product_model->unlink_product_image($p_id);
			}
			if($unlink_image == true){
				$delete_product = $this->product_model->delete($product_id);
				if($delete_product == 200){
					$data = array('status'=>200,'message'=>'product deleted');
					header("Content-type: application/json");
					echo json_encode($data);
					exit();
				}else{
					$data = array('status'=>200,'message'=>'failed to delete product');
					header("Content-type: application/json");
					echo json_encode($data);
					exit();
				}
			}
		}
	}


	public function get_image_optional_image()
	{
		$product_id = $this->input->post('product_id');
		if(isset($product_id)){
			$images = $this->product_model->get_image_optional_image($product_id);
			if($images){
				$html = '';
				foreach ($images as $key => $image) {
					$html.='<div class="col-md-3">';
					$html.='<div class="form-group">';
					$html.='<label for="control-label mb-10">Optional Image &nbsp;&nbsp;';
					$html.='<span class="float-right text-danger" style="cursor:pointer" onclick="delete_single_image_optional('.$image->id.')">delete</span>';
					$html.='</label><br><br>';
					$html.='<img src="'.FILE_UPLOAD_PATH.$image->picture.'" alt="default image" height="200px" width="250px">';
					$html.='</div>';
					$html.='</div>';
				}
				echo $html;
			}
		}
	}

	public function delete_single_image_optional()
	{
		$id = $this->input->post('id');
		if(isset($id)){
			if($this->product_model->delete_single_image_optional($id) == true){
				header("Content-type: application/json");
				echo json_encode(array('status' => 200));
				exit();
			}
		}
	}

	/*
	*
	* @get_sub_category() method to fetch subcategory for dropdown
	*	by category id
	*
	*/
	public function get_sub_category()
	{
		$category_id = $this->input->post('category_id');
		$sub_category = $this->product_model->get_sub_category($category_id);
		if(!empty($sub_category)){
			echo json_encode($sub_category);
		}else{
			echo json_encode($sub_category);
		}
	}

	/*
	*
	*@change_status() used to the product is active
	*	or not.(publish or not publish)
	*
	*/
	public function change_status()
	{
		$product_id = $this->input->post('product_id');
		$status = $this->input->post('status');
		$updated_status = $this->product_model->change_status($product_id,$status);
		if($updated_status == true){
			$data = array('status'=>200,'message'=>'status changed');
			header("Content-type: application/json");
			echo json_encode($data);
			exit();
		}else{
			$data = array('status'=>200,'message'=>'status not changed');
			header("Content-type: application/json");
			echo json_encode($data);
			exit();
		}
	}

	/**
	* @update_quantity_in_product_table() method is used to update quantity in product tabel
	* and insert new stock in stock table
	*
	*/
	public function update_quantity_in_product_table()
	{
		$product_id = $this->input->post('product_id_to_stock');
		$quantity = $this->input->post('quantity');
		$type = $this->input->post('type');
		$product = $this->product_model->get_product($product_id);
		$previous_quantity = $this->product_model->get_quantity_by_id($product_id);
		global $new_quantity;
		if(isset($product_id) && isset($quantity) && isset($type)){
			if($type == 1){
				$new_quantity = ($quantity+$previous_quantity->quantity);
			}else if($type == 2 OR $type == 3){
				$new_quantity = ($previous_quantity->quantity-$quantity);
				if($new_quantity < 0){
					$data = array('status'=>300,'message'=>'woops! quantity will be negative');
					header("Content-type: application/json");
					echo json_encode($data);
					exit();
				}
			}
			$stock_data = [
				'type'=>$this->input->post('type'),
				'category_id'=>$product->category_id,
				'product_id'=>$product_id,
				'quantity'=>$this->input->post('quantity'),
				'rate'=>$this->input->post('rate'),
				'total'=>($this->input->post('rate') * $this->input->post('quantity')),
				'remarks'=>$this->input->post('remarks'),
				'created_by'=>$this->session->userdata('role_id')
			];

			if($this->stock_model->add_stock($stock_data) == true){

				if($this->product_model->update_product_quantity($product_id,$new_quantity) ==  true){
					$data = array('status'=>200,'message'=>'quantity upadated');
					header("Content-type: application/json");
					echo json_encode($data);
					exit();
				}
			}
		}
	}

	/*
	*
	*@view() method used to see
	*	details of a product
	*
	*/
	public function view()
	{
		$id = $this->input->get('id');
		$this->data['category'] = $this->product_model->get_all_category();
		$this->data['types'] = $this->product_model->get_tax_discount_type();
		$this->data['brands'] = $this->product_model->get_all_brands();
		$this->data['units'] = $this->product_model->get_all_units();
		$this->data['tags'] = $this->product_model->get_all_tags();
		$this->data['colors'] = $this->product_model->get_all_colors();
		$this->data['sizes'] = $this->product_model->get_all_sizes();
		$this->data['product'] = $this->product_model->get_product($id);
		$this->data['optional_images'] = $this->product_model->get_image_optional_image($id);
		$this->data['view_module'] = 'product';
		$this->data['view_file'] = 'view';
		$this->load->module('template');
		$this->template->_shop_admin($this->data);

	}

	/*
	*
	*@get_product_discount_by_id() used to edit/add
	*	product discount
	*
	*/
	public function get_product_discount_by_id()
	{
		$product_id = $this->input->post('product_id');
		$discount = $this->product_model->get_product_discount_by_id($product_id);
		if(isset($discount)){
			echo json_encode($discount);
		}
	}

	/*
	*
	*@get_selected_discount_type() method used to
	*	get selected discount type for edit/add discount
	*	in product
	*/
	public function get_selected_discount_type()
	{
		$discount_type = $this->product_model->get_tax_discount_type();
		if(isset($discount_type)){
			echo json_encode($discount_type);
		}
	}
}
