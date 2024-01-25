<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Http\Requests\RequestAttributes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\attributes;
use App\Models\role_assign;
use App\Models\category;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class GenericController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $user = Helper::curren_user();
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function roles()
    {
        $user = Auth::user();
        if ($user->role_id != 1) {
            return redirect()->back()->with('error', "No Link found");
        }
        $attributes = attributes::where('is_active', 1)->get();
        $role_assign = role_assign::where('is_active', 1)->where("role_id", $user->role_id)->first();

        return view('roles/roles')->with(compact('attributes', 'role_assign'));
    }

    public function generic_submit(RequestAttributes $request)
    {
        $token_ignore = ['_token' => '', 'type' => ''];
        $post_feilds = array_diff_key($_POST, $token_ignore);

        try {
            attributes::insert($post_feilds);
            return redirect()->back()->with('message', 'Information updated successfully');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error will saving record');
        }
    }

    public function role_assign_modal()
    {
        $user = Auth::user();
        $role_assign = role_assign::where('is_active', 1)->where("role_id", $_POST['role_id'])->orderBy('id', 'desc')->first();
        $att_tag = attributes::where('is_active', 1)->select('attribute')->distinct()->get();
        $body = "";
        if ($att_tag) {
            $route = route('roleassign_submit');
            $body .= "<input type='hidden' name='role_id' id='fetch-role-id' value='" . $_POST['role_id'] . "'>";
            if ($role_assign && $role_assign->assignee != 'N;') {
                $checker = unserialize($role_assign->assignee);
                $body .= "<input type='hidden' name='record_id' value='" . $role_assign->id . "'>";
            } else {
                $checker = [];
            }
            foreach ($att_tag as $key => $role) {
                $body .= "<tr><td>" . ucwords($role->attribute) . "</td><td><div class='custom-control custom-checkbox'>
                                  <input type='checkbox' name='assignee[]' class='custom-control-input' id='customCheck1" . $key . "' ";
                if (in_array($role->attribute . "_1", $checker)) {
                    $body .= "checked";
                }
                $body .= " value='" . $role->attribute . "_1'>
                                  <label class='custom-control-label' for='customCheck1" . $key . "'>1</label></div></td>
                            
                            <td><div class='custom-control custom-checkbox'>
                                  <input type='checkbox' name='assignee[]' class='custom-control-input' id='customCheck2" . $key . "' ";
                if (in_array($role->attribute . "_2", $checker)) {
                    $body .= "checked";
                }
                $body .= " value='" . $role->attribute . "_2'>
                                  <label class='custom-control-label' for='customCheck2" . $key . "'>2</label></div></td>

                            <td><div class='custom-control custom-checkbox'>
                                  <input type='checkbox' name='assignee[]' class='custom-control-input' id='customCheck3" . $key . "' ";
                if (in_array($role->attribute . "_3", $checker)) {
                    $body .= "checked";
                }
                $body .= " value='" . $role->attribute . "_3'>
                                  <label class='custom-control-label' for='customCheck3" . $key . "'>3</label></div></td>

                            <td><div class='custom-control custom-checkbox'>
                                  <input type='checkbox' name='assignee[]' class='custom-control-input' id='customCheck4" . $key . "' ";
                if (in_array($role->attribute . "_4", $checker)) {
                    $body .= "checked";
                }
                $body .= " value='" . $role->attribute . "_4'>
                                  <label class='custom-control-label' for='customCheck4" . $key . "'>4</label></div></td></tr>";
            }
        }

        $bod['body'] = $body;
        $response = json_encode($bod);
        return $response;
    }

    public function roleassign_submit(Request $request)
    {
        if (isset($request->record_id) && $request->record_id != 0) {
            $role_assign = role_assign::where('is_active', 1)->where("id", $request->record_id)->first();
        } else {
            $role_assign = new role_assign;
            $role_assign->role_id = $request->role_id;
        }

        $role_assign->assignee = serialize($request->assignee);
        $role_assign->save();
        return redirect()->back()->with('message', 'Role has been assigned successfully');
    }

    public function listing($slug = '')
    {
        $user = Auth::user();
        $role_assign = role_assign::where('is_active', 1)->where("role_id", $user->role_id)->first();
        if ($role_assign) {
            $validator = Helper::check_rights($slug);
            if (is_null($validator)) {
                return redirect()->back()->with('error', "Don't have sufficient rights to access this page");
            }
        } else {
            return redirect()->back()->with('error', "Don't have sufficient rights to access this page");
        }

        $form = null;
        $table = null;
        $eloquent = '';
        if ($slug == "roles") {
            $attributes = attributes::where('is_active', 1)->where('attribute', $slug)->get();
        } else {
            $attributes = attributes::where('is_active', 1)->where('attribute', $slug)->get();
            $get_eloquent = attributes::where('is_active', 1)->where('attribute', $slug)->first();
            $eloquent = ($get_eloquent->model != '') ? $get_eloquent->model : '';

            if ($eloquent != '') {
                $form = $this->generated_form($slug);
                $table = $this->generated_table($slug);
            }
        }
        return view('roles/crud')->with(compact('attributes', 'slug', 'eloquent', 'form', 'table'));
    }

    private function generated_form($slug = '')
    {
        $body = '';

        if ($slug == 'brand') {
            $route_url = route('crud_generator', $slug);
            $body = '<form class="" id="generic-form" enctype="multipart/form-data" method="POST" action="' . $route_url . '">
                    <input type="hidden" name="_token" value="' . csrf_token() . '">
                    <input type="hidden" name="record_id" id="record_id" value="">
                    <div class="row">
                        <div id="assignrole"></div>
                        <div class="col-md-12 col-sm-6 col-12" id="rank-label">
                            <div class="form-group start-date">
                                <label for="start-date" class="">Name:</label>
                                <div class="d-flex">
                                    <input id="name" placeholder="Name" name="name" class="form-control" type="text" autocomplete="off" required/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-6 col-12" id="role-label">
                            <div class="form-group start-date">
                                <label for="start-date" class="">Image:</label>
                                <div class="d-flex">
                                    <input type="file" id="image" accept="image/*" name="image" class="form-control" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>';
            return $body;
        } elseif ($slug == 'product') {
            $route_url = route('crud_generator', $slug);
            $body = '<form class="" id="generic-form" enctype="multipart/form-data" method="POST" action="' . $route_url . '">
                    <input type="hidden" name="_token" value="' . csrf_token() . '">
                    <input type="hidden" name="record_id" id="record_id" value="">
                    <div class="row">
                        <div id="assignrole"></div>
                        <div class="col-md-12 col-sm-6 col-12" id="rank-label">
                            <div class="form-group start-date">
                                <label for="start-date" class="">Name:</label>
                                <div class="d-flex">
                                    <input id="name" placeholder="Name" name="name" class="form-control" type="text" autocomplete="off" required/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-6" id="rank-label">
                            <div class="form-group start-date">
                                <label for="start-date" class="">Category:</label>
                                <div class="d-flex">
                                    <select name="category_id" id="category_id" class="form-control profession" required value="">
                                        <option selected="true" disabled="disabled" >Select Category</option>';
            $category = category::where("is_active", 1)->where("is_deleted", 0)->get();
            if ($category) {
                foreach ($category as $k => $val) {
                    $body .= '<option value="' . $val->id . '">' . $val->name . '</option>';
                }
            }
            $body .= '</select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-6" id="rank-label">
                            <div class="form-group start-date">
                                <label for="start-date" class="">Image:</label>
                                <div class="d-flex">
                                    <input type="file" id="image" accept="image/*" name="image" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-sm-6 col-6" id="rank-label">
                        </div>
                        <div class="col-md-6 col-sm-6 col-6" id="rank-label">
                            <div class="form-group start-date">
                                <div class="d-flex">
                                    <td><img id="image-add" style="width:80px;height:80px;display:none;" src=""></td>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 col-sm-6 col-12" id="rank-label">
                            <div class="form-group start-date">
                                <label for="start-date" class="">Price:</label>
                                <div class="d-flex">
                                    <input id="price" placeholder="Price" name="price" class="form-control" type="number" autocomplete="off" required/>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-12 col-sm-6 col-12" id="role-label">
                            <div class="form-group end-date">
                                <label for="end-date" class="">Description:</label>
                                <div class="d-flex">
                                    <textarea id="description" required name="description" class="form-control" ></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-6 col-12" id="role-label">
                            <div class="form-group end-date">
                                <label for="end-date" class="">Specification:</label>
                                <div class="d-flex">
                                    <textarea id="specification" required name="specification" class="form-control" ></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>';
            return $body;
        } elseif ($slug == 'category') {
            $route_url = route('crud_generator', $slug);
            $body = '<form class="" id="generic-form" enctype="multipart/form-data" method="POST" action="' . $route_url . '">
                    <input type="hidden" name="_token" value="' . csrf_token() . '">
                    <input type="hidden" name="record_id" id="record_id" value="">
                    <div class="row">
                        <div id="assignrole"></div>
                        <div class="col-md-12 col-sm-6 col-12" id="rank-label">
                            <div class="form-group start-date">
                                <label for="start-date" class="">Name:</label>
                                <div class="d-flex">
                                    <input id="name" placeholder="Name" name="name" class="form-control" type="text" autocomplete="off" required/>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>';
            return $body;
        } else {
            return $body;
        }
    }

    private function generated_table($slug = '')
    {


        $body = '';
        if ($slug == "brand") {
            $data = 'App\Models\\' . $slug;
            $loop = $data::where("is_active", 1)->where("is_deleted", 0)->get();
            if ($loop) {
                $body = '<thead>
                                       <tr>
                                          <th>S. No</th>
                                          <th>Name</th>
                                          <th>Image</th>
                                          <th>Creation Date</th>';
                if (Helper::can_edit($slug) || Helper::can_delete($slug)) {
                    $body .= '<th>Action</th>';
                }
                $body .= '
                                       </tr>
                                    </thead>
                                    <tbody>';
                if ($loop) {
                    foreach ($loop as $key => $val) {
                        $i = asset($val->image);
                        $body .= '<tr>
                                          <td>' . ++$key . '</td> 
                                          <td>' . $val->name . '</td> 
                                          <td><img style="width:80px;height:80px;" src="' . $i . '"></td>
                                          <td>' . date("M d,Y", strtotime($val->created_at)) . '</td>';
                        if (Helper::can_edit($slug) || Helper::can_delete($slug)) {
                            $body .= '<td>';
                            if (Helper::can_edit($slug)) {
                                $body .= '<button type="button" class="btn btn-primary editor-form" data-edit_id= "' . $val->id . '" data-name= "' . $val->name . '" >Edit</button>';
                            }
                            if (Helper::can_delete($slug)) {
                                $body .= '<button type="button" class="btn btn-danger delete-record" data-model="' . $data . '" data-id= "' . $val->id . '" >Delete</button>';
                            }

                            $body .= '</td>';
                        }
                        $body .= '</tr>';
                    }
                }
                $body .= '</tbody>
                                    <tfoot>
                                        <tr>
                                            <th>S. No</th>
                                            <th>Name</th>
                                            <th>Image</th>
                                            <th>Creation Date</th>';
                if (Helper::can_edit($slug) || Helper::can_delete($slug)) {
                    $body .= '<th>Action</th>';
                }
                $body .= '
                                        </tr>
                                    </tfoot>';
            }
            $script = '$("body").on("click" ,".editor-form",function(){
                                                $("#name").val($(this).data("name"))
                                                $("#record_id").val($(this).data("edit_id"))
                                                $("#addevent").modal("show")
                                            })';
            $resp['body'] = $body;
            $resp['script'] = $script;
            return $resp;
        } elseif ($slug == "product") {
            $data = 'App\Models\\' . $slug;
            $loop = $data::where("is_active", 1)->where("is_deleted", 0)->get();
            if ($loop) {
                $body = '<thead>
                                       <tr>
                                          <th>S. No</th>
                                          <th>Name</th>
                                          <th>Category</th>
                                          <th>Price</th>
                                          <th>Description</th>
                                          <th>Specification</th>
                                          <th>Image</th>
                                          <th>Creation Date</th>
                                          <th>Action</th>
                                       </tr>
                                    </thead>
                                    <tbody>';
                if ($loop) {
                    foreach ($loop as $key => $val) {
                        $i = asset($val->image);
                        $category = category::where('is_active', 1)->where('is_deleted', 0)->where('id', $val->category_id)->first();
                        $body .= '<tr>
                                          <td>' . ++$key . '</td> 
                                          <td>' . $val->name . '</td> 
                                          <td>' . $category->name . '</td> 
                                          <td>$' . $val->price . '</td> 
                                          <td>' . $val->description . '</td> 
                                          <td>' . $val->specification . '</td> 
                                          <td><img style="width:80px;height:80px;" src="' . $i . '"></td>
                                          <td>' . date("M d,Y", strtotime($val->created_at)) . '</td>
                                          <td>
                                             <button type="button" class="btn btn-primary editor-form" data-edit_id= "' . $val->id . '" data-name= "' . $val->name . '" data-description= "' . $val->description . '" data-category_id= "' . $val->category_id . '" data-price= "' . $val->price . '" data-image= "' . $i . '" data-specification= "' . $val->specification . '" >Edit</button>
                                             <button type="button" class="btn btn-danger delete-record" data-model="' . $data . '" data-id= "' . $val->id . '" >Delete</button>
                                          </td>
                                       </tr>';
                    }
                }
                $body .= '</tbody>
                                    <tfoot>
                                       <tr>
                                          <th>S. No</th>
                                          <th>Name</th>
                                          <th>Category</th>
                                          <th>Price</th>
                                          <th>Description</th>
                                          <th>Specification</th>
                                          <th>Image</th>
                                          <th>Creation Date</th>
                                          <th>Action</th>
                                       </tr>
                                    </tfoot>';
            }
            $script = '$("body").on("click" ,".editor-form",function(){
                                                $("#name").val($(this).data("name"))
                                                $("#category_id").val($(this).data("category_id"))
                                                $("#price").val($(this).data("price"))
                                                $("#record_id").val($(this).data("edit_id"))
                                                var description = $(this).data("description");
                                                CKEDITOR.instances.description.setData(description);
                                                var specification = $(this).data("specification");
                                                CKEDITOR.instances.specification.setData(specification);
                                                $("#image").removeAttr("required");
                                                $("#image-add").css("display","");
                                                $("#image-add").attr("src",$(this).data("image"));
                                                $("#addevent").modal("show")
                                            })';
            $resp['body'] = $body;
            $resp['script'] = $script;
            return $resp;
        } elseif ($slug == "category") {
            $data = 'App\Models\\' . $slug;
            $loop = $data::where("is_active", 1)->where("is_deleted", 0)->get();
            if ($loop) {
                $body = '<thead>
                                       <tr>
                                          <th>S. No</th>
                                          <th>Name</th>
                                          <th>Creation Date</th>
                                          <th>Action</th>
                                       </tr>
                                    </thead>
                                    <tbody>';
                if ($loop) {
                    foreach ($loop as $key => $val) {
                        $body .= '<tr>
                                          <td>' . ++$key . '</td> 
                                          <td>' . $val->name . '</td> 
                                          <td>' . date("M d,Y", strtotime($val->created_at)) . '</td>
                                          <td>
                                             <button type="button" class="btn btn-primary editor-form" data-edit_id= "' . $val->id . '" data-name= "' . $val->name . '" >Edit</button>
                                             <button type="button" class="btn btn-danger delete-record" data-model="' . $data . '" data-id= "' . $val->id . '" >Delete</button>
                                          </td>
                                       </tr>';
                    }
                }
                $body .= '</tbody>
                                    <tfoot>
                                       <tr>
                                          <th>S. No</th>
                                          <th>Name</th>
                                          <th>Creation Date</th>
                                          <th>Action</th>
                                       </tr>
                                    </tfoot>';
            }
            $script = '$("body").on("click" ,".editor-form",function(){
                                                $("#name").val($(this).data("name"))
                                                $("#record_id").val($(this).data("edit_id"))
                                                $("#addevent").modal("show")
                                            })';
            $resp['body'] = $body;
            $resp['script'] = $script;
            return $resp;
        } else {
            return $body;
        }
    }


    public function crud_generator($slug = '', Request $request)
    {
        $token_ignore = ['_token' => '', 'record_id' => '', 'image' => ''];
        $post_feilds = array_diff_key($_POST, $token_ignore);
        $data = 'App\Models\\' . $slug;
        if ($slug == 'category' || $slug == 'product') {
            $s = $_POST['name'];
            $s = str_replace(' ', '-', $s);
            $s = strtolower($s);
            $post_feilds['slug'] = $s;
        }
        $extension = array("jpeg", "jpg", "png", "jfif");
        if (isset($request->image)) {
            $file = $request->image;
            $ext = $request->image->getClientOriginalExtension();
            if (in_array($ext, $extension)) {
                $file_name = $request->image->getClientOriginalName();
                $file_name = substr($file_name, 0, strpos($file_name, "."));
                $name = "uploads/product/" . $file_name . "_" . time() . '.' . $file->getClientOriginalExtension();
                $destinationPath = public_path() . '/uploads/product/';
                $share = $request->image->move($destinationPath, $name);
                $post_feilds['image'] = $name;
            } else {
                $msg = "This File type is not Supported!";
                return redirect()->back()->with('error', "Error Code: " . $msg);
            }
        }
        try {
            if (isset($_POST['record_id']) && $_POST['record_id'] != '') {
                $create = $data::where("id", $_POST['record_id'])->update($post_feilds);
                $msg = "Record has been updated";
            } else {
                if ($slug == 'category') {
                    $check_record = category::where('is_active', 1)->where('slug', $post_feilds['slug'])->first();
                    if ($check_record) {
                        $msg = "This Category already exists";
                        return redirect()->back()->with('error', $msg);
                    }
                }
                $create = $data::create($post_feilds);
                $msg = "Record has been created";
            }
            return redirect()->back()->with('message', $msg);
        } catch (Exception $e) {
            $error = $e->getMessage();
            return redirect()->back()->with('error', "Error Code: " . $error);
        }
    }
    public function delete_record(Request $request)
    {
        $token_ignore = ['_token' => '', 'id' => '', 'model' => ''];
        $post_feilds = array_diff_key($_POST, $token_ignore);
        $data = $_POST['model'];
        try {
            $update = $data::where("id", $_POST['id'])->update($post_feilds);
            $status['message'] = "Record has been deleted";
            $status['status'] = 1;
            return json_encode($status);
        } catch (Exception $e) {
            $error = $e->getMessage();
            $status['message'] = $error;
            $status['status'] = 0;
            return json_encode($status);
        }
    }
}