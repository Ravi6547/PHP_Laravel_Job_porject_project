<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobType;
use App\Models\saved_job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;


class AccountController extends Controller
{
    // Show the user registration page
    public function registration()
    {
        return view('front.account.registration');
    }

    // Process user registration
    public function processRegistration(Request $request)
    {
        // Define validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:5|same:confirm_password',
            'confirm_password' => 'required',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        // Proceed with registration
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        // Return JSON response
        return response()->json([
            'status' => true,
            'message' => 'You have registered successfully'
        ]);
    }

    // Show the user login page
    public function login()
    {
        return view('front.account.login');
    }

    // this function will authenticate user
    public function authenticate(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',

        ]);
        if ($validator->passes()) {
            if (Auth::attempt(['email' => $request->email, 'password' =>  $request->password])) {
                return redirect()->route('account.profile');
            } else {
                return redirect()->route('account.login')->with('error', 'Either Email/Password is incorrect');
            }
        } else {
            return redirect()->route('account.login')
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }
    }

    public function profile()
    {
         

        $id = Auth::user()->id;
        $user = User::where('id', $id)->first();

        return view('front.account.profile', [
            'user' => $user
        ]);
    }

    public function updateProfile(Request $request)
    {

        $id = Auth::user()->id;

        $validator = validator::make($request->all(), [
            'name' => 'required|min:5|max:20',
            'email' => 'required|email|unique:users,email,' . $id . ',id'
        ]);

        if ($validator->passes()) {

            $user = User::find($id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->designation = $request->designation;
            $user->save();

            session()->flash('success', 'Profile updated successfully');

            return response()->json([
                'status' => true,
                'errors' => []

            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()

            ]);
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('account.login');
    }

    public function updateProfilePic(Request $request)
    {
        // dd($request->all());
        $id = Auth::user()->id;
        $validator = validator::make($request->all(), [
            'image' => 'required|image'

        ]);

        if ($validator->passes()) {
            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = $id . '-' . time() . '-' . $ext;
            $image->move(public_path('/profilepic/'), $imageName);

            //create a small thumbnail
            $sourcePath = public_path('/profilepic/' . $imageName);
            $manager = new ImageManager(Driver::class);
            $image = $manager->read($sourcePath);

            //crop the best fitting 5:3 (600*360) create and reseze to  600*360 pixel
            $image->cover(150, 150);
            $image->toPng()->save(public_path('/profilepic/thumb/' . $imageName));

            //delete old profile picture

            File::delete(public_path('/profilepic/thumb/' . Auth::user()->image));
            File::delete(public_path('/profilepic/' . Auth::user()->image));

            User::where('id', $id)->update(['image' => $imageName]);
            session()->flash('success', 'Profile picture updated successfully');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function createJob()
    {

        $categories = Category::orderBy('name', 'ASC')->where('status', 1)->get();
        $jobTypes =   JobType::orderBy('name', 'ASC')->where('status', 1)->get();

        return view('front.account.job.create', [

            'categories' => $categories,
            'jobTypes' => $jobTypes

        ]);
    }

    // this function will store jobs in database
    public function saveJob(Request $request)
    {
        // Define validation rules
        $rules = [
            'title' => 'required|min:5|max:200',
            'category' => 'required', // Make sure this matches your input
            'jobType' => 'required', // Make sure this matches your input
            'vacancy' => 'required',
            'location' => 'required|max:50',
            'description' => 'required',
            'company_name' => 'required|min:3|max:75',
        ];

        // Validate the request
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes()) {
            // Create a new Job instance
            $job = new Job();
            $job->title = $request->title;
            $job->category_id = $request->input('category'); // Use 'category' from the request
            $job->job_type_id = $request->input('jobType'); // Use 'jobType' from the request
            $job->user_id = Auth::user()->id;
            $job->vacancy = $request->vacancy;
            $job->salary = $request->salary;
            $job->location = $request->location;
            $job->description = $request->description;
            $job->benefits = $request->benefits;
            $job->responsibility = $request->responsibility;
            $job->qualifications = $request->qualifications;
            $job->keywords = $request->keywords;
            $job->experience = $request->experience;
            $job->company_name = $request->company_name;
            $job->company_location = $request->company_location;
            $job->company_website = $request->company_website;
            $job->save();

            // Flash success message and return response
            session()->flash('success', 'Job added successfully');
            return response()->json([
                'status' => true,
                'errors' => []
            ]);
        } else {
            // Return validation errors
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }


    public function myJobs()
    {

        $jobs = Job::where('user_id', Auth::user()->id)->with('JobType')->orderBy('created_At', 'DESC')->paginate(10);

        return view('front.account.job.my-jobs', [
            'jobs' => $jobs
        ]);
    }

    public function editJob(Request $request, $id)
    {

        $categories = Category::orderBy('name', 'ASC')->where('status', 1)->get();
        $jobTypes = JobType::orderBy('name', 'ASC')->where('status', 1)->get();

        $job = Job::where([
            'user_id' => Auth::user()->id,
            'id' => $id
        ])->first();

        if ($job == null) {
            abort(404);
        }

        return view('front.account.job.edit', [
            'categories' => $categories,
            'jobTypes' => $jobTypes,
            'job' => $job
        ]);
    }

    public function updateJob(Request $request, $id)
    {
        // Define validation rules
        $rules = [
            'title' => 'required|min:5|max:200',
            'category' => 'required', // Make sure this matches your input
            'jobType' => 'required', // Make sure this matches your input
            'vacancy' => 'required',
            'location' => 'required|max:50',
            'description' => 'required',
            'company_name' => 'required|min:3|max:75',
        ];

        // Validate the request
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes()) {
            // update a new Job instance
            $job = Job::find($id);
            $job->title = $request->title;
            $job->category_id = $request->input('category'); // Use 'category' from the request
            $job->job_type_id = $request->input('jobType'); // Use 'jobType' from the request
            $job->user_id = Auth::user()->id;
            $job->vacancy = $request->vacancy;
            $job->salary = $request->salary;
            $job->location = $request->location;
            $job->description = $request->description;
            $job->benefits = $request->benefits;
            $job->responsibility = $request->responsibility;
            $job->qualifications = $request->qualifications;
            $job->keywords = $request->keywords;
            $job->experience = $request->experience;
            $job->company_name = $request->company_name;
            $job->company_location = $request->company_location;
            $job->company_website = $request->company_website;
            $job->save();

            // Flash success message and return response
            session()->flash('success', 'Job updated successfully');
            return response()->json([
                'status' => true,
                'errors' => []
            ]);
        } else {
            // Return validation errors
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function deleteJob(Request $request)
    {
        $job =    Job::where([
            'user_id' => Auth::user()->id,
            'id' =>  $request->jobId
        ])->first();

        if ($job == null) {
            session()->flash('error', 'Either job deleted or not found');
            return response()->json([
                'status' => true
            ]);
        }

        Job::where('id',  $request->jobId)->delete();
        session()->flash('success', ' Job deleted successfully');

        return response()->json([
            'status' => true
        ]);
    }

    public function myJobApplications()
    {

        $jobapplications =   JobApplication::where('user_id', Auth::user()->id)
            ->with(['job', 'job.jobType', 'job.applications'])->orderby('created_at', 'DESC')->paginate(10);

        return view('front.account.job.my-job-application', [
            'jobapplications' => $jobapplications,
        ]);
    }

    public function removeJobs(Request $request)
    {

        $jobapplicaton =    JobApplication::where(
            [
                'id' => $request->id,
                'user_id' => Auth::user()->id
            ]
        )->first();

        if ($jobapplicaton == null) {
            session()->flash('error', 'Job application not found');
            return response()->json([
                'status' => false
            ]);
        }

        JobApplication::find($request->id)->delete();
        session()->flash('success', 'Job application removed successfully');
        return response()->json([
            'status' => true
        ]);
    }
    public function savedJob()
    {
        //         $jobapplications =   JobApplication::where('user_id',Auth::user()->id)
        //   ->with(['job','job.jobType','job.applications'])->paginate(10);

        $savedjobs = saved_job::where([
            'user_id' => Auth::user()->id
        ])->with(['job', 'job.jobType', 'job.applications'])->orderby('created_at', 'DESC')->paginate(10);

        return view('front.account.job.saved-jobs', [

            'savedjobs' => $savedjobs
        ]);
    }


    public function removeSavedJobs(Request $request)
    {

        $savedjobs =    saved_job::where(
            [
                'id' => $request->id,
                'user_id' => Auth::user()->id
            ]
        )->first();

        if ($savedjobs == null) {
            session()->flash('error', ' Saved Job  not found');
            return response()->json([
                'status' => false
            ]);
        }

        saved_job::find($request->id)->delete();
        session()->flash('success', ' Saved Job  removed successfully');
        return response()->json([
            'status' => true
        ]);
    }

    //update password
    public function updatepassword(Request $request) {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:5',
            'confirm_password' => 'required|same:new_password',
        ]);
    
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    
        // Check if the old password is correct
        if (!Hash::check($request->old_password, Auth::user()->password)) {
            session()->flash('errors', 'Your old password is incorrect');
            return response()->json([
                'status' => false,
                'errors' => ['old_password' => ['Your old password is incorrect']]
            ]);
        }
    
        // Update the user's password
        $user = User::find(Auth::user()->id);
        
        $user->password = Hash::make($request->new_password);
        $user->save();
    
        // Flash success message
        session()->flash('success', 'Your password has been updated successfully.');
    
        // Return a success response
        return response()->json([
            'status' => true,
            'message' => 'Your password has been updated successfully.'
        ]);
    }
    
}
