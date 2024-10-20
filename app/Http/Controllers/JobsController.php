<?php

namespace App\Http\Controllers;

use App\Mail\JobNotificationMail;
use App\Models\Category;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobType;
use App\Models\saved_job;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\Colors\Rgb\Channels\Red;

class JobsController extends Controller
{
    //this method will show jobs page
          public function index(Request $request){
        $categories =  Category::where('status',1)->get();
        $jobTypes  =  JobType::where('status',1)->get();
         $jobs = Job::where('status',1);

             //search using keywords
           if(!empty($request->keyword)){
            $jobs = $jobs->where(function($query) use($request){
                $query->orWhere('title','like','%'.$request->keyword.'%');
                $query->orWhere('keywords','like','%'.$request->keyword.'%');
            });
           }
                  //search using location
           if(!empty($request->location)){
            $jobs = $jobs->where('location', $request->location);
           }

                   //search using category
                   if(!empty($request->category)){
                    $jobs = $jobs->where('category_id', $request->category);
                   }

                          $jobTypeArray =[];
                          //search using jobtype
                        if(!empty($request->jobType)){
                       $jobTypeArray = explode(',', $request->jobType);
                       $jobs = $jobs->whereIn('job_type_id', $jobTypeArray);
                   }

                      //search using experience
                    if(!empty($request->experience)){
                        $jobs = $jobs->where('experience', $request->experience);
                       }
                   
         $jobs = $jobs->with(['jobType', 'category']);

         if( $request->sort == '0'){
            $jobs = $jobs->orderBy('created_at', 'ASC');
         }else{
            $jobs = $jobs->orderBy('created_at', 'DESC');
         }
        
         $jobs = $jobs->paginate(9);

            return view('front.jobs',[
                'categories' => $categories,
                'jobTypes' => $jobTypes,
                'jobs' =>  $jobs,
                'jobTypeArray' => $jobTypeArray
            ]);
    }

//this method will show jobs detail page
    public function detail($id){

        $job = Job::where([
            'id' =>$id,
            'status' => 1
        ])->with(['jobType','category'])->first();

        if($job == null){
            abort(404);
        }
 
        $count = 0;
        if(Auth::user()){
            $count = saved_job::where([
                'user_id' => Auth::user()->id,
                'job_id' => $id
              ])->count();
        }
       
                 // fetch appplication
             $jobapplications = JobApplication::where('job_id',$id)->with('user')->get();
            //   dd($jobapplications);
            return view('front.jobdetail',[
                'job' => $job,
                'count' =>$count,
                    'jobapplications' => $jobapplications
            ]);
    }

    public function applyJob(Request $request){
        $id = $request->id;
   $job = Job::where('id',$id)->first();

   // if job not found in db
   if($job == null){
    session()->flash('error','job does not exist');
    return response()->json([
          'status' => false,
          'message' => 'job does not exist'
    ]);
   }

   // you cannot apply on your job
   $employer_id = $job->user_id;

   if($employer_id == Auth::user()->id){
    session()->flash('error','you cannot apply on your job');
    return response()->json([
          'status' => false,
          'message' => 'you cannot apply on your job'
    ]);
   }    

   // you cannot appled on  job twice
    $jobApplicationCount = JobApplication::where([
           'user_id' => Auth::user()->id,
           'job_id' => $id
    ])->count();

    if($jobApplicationCount > 0){
        session()->flash('error','you have already applied on this job');
    return response()->json([
          'status' => false,
          'message' => 'you have already applied on this job'
    ]);
    }

        $application = new JobApplication();
        $application->job_id = $id;
        $application->user_id = Auth::user()->id;
        $application->employer_id = $employer_id;
        $application->applied_date = now();
        $application->save();


        

        session()->flash('success','you have successfully applied');
        return response()->json([
              'status' => true,
              'message' => 'you have successfully applied'
        ]);

        // send notification eamail to employer

        $employer = User::where('id', $employer_id)->first();

        $emaildata = [
           'employer' => $employer,
           'user' => Auth::user(),
           'job' => $job,
        ];
        
        Mail::to($employer->email)->send(new JobNotificationMail($emaildata));
    }

    public function saveJob(Request $request){
       $id = $request->id;
       $job = Job::find($id);

       if($job == null){
        session()->flash('error','job not found');

        return response()->json([
           'status' => false,
        ]);
       }
       // check if user already saved the job
       $count = saved_job::where([
         'user_id' => Auth::user()->id,
         'job_id' => $id
       ])->count();

       if($count > 0){
        session()->flash('error','You have already saved  job');

        return response()->json([
           'status' => false,
        ]);
       }

       $savejob = new saved_job();
       $savejob->job_id = $id;
       $savejob->user_id = Auth::user()->id;
       $savejob->save();
       
       if($savejob == true){
        session()->flash('success','You have successfully saved the job');

        return response()->json([
           'status' => true,
        ]);
       }
    }


}
