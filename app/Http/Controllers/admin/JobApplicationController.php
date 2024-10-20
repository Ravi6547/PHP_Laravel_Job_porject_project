<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Intervention\Image\Colors\Rgb\Channels\Red;

class JobApplicationController extends Controller
{
    public function index(){
      $jobapplocations =   JobApplication::orderBy('created_at','DESC')
      ->with('job','user','employer')
      ->paginate(10);
      
      return view('admin.jobapplications.list',[
        'jobapplocations' => $jobapplocations
      ]);
    }

    public function destroy(Request $request)
{
    $id = $request->id;
    $jobapplocations = JobApplication::find($id);

    if ($jobapplocations == null) {
        session()->flash('error', 'Either job applications deleted or not found');
        return response()->json([
            'status' => false,
            'message' => session('error') // Return session error message
        ]);
    }

    $jobapplocations->delete();
    session()->flash('success', 'Job applications deleted successfully');

    return response()->json([
        'status' => true,
        'message' => session('success') // Return session success message
    ]);
}

}
