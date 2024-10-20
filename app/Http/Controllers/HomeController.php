<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Job;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    //this method will show our home page
    public function index(){
       $categories = Category::where('status',1)->orderBy('name', 'ASC')->take(8)->get();

       $newcategories =  Category::where('status',1)->orderBy('name', 'ASC')->get();

       $featuredjobs = Job::where('status',1)->orderBy('created_at', 'DESC')->with('jobType')->where('isfeatured',1)->take(6)->get();
       $latestjobs = Job::where('status',1)->with('jobType')->orderBy('created_at', 'DESC')->take(6)->get();

            return view('front.home',[
                'categories' => $categories,
                'featuredjobs' => $featuredjobs,
                'latestjobs' => $latestjobs,
                'newcategories' => $newcategories
            ]);
    }


    
}
