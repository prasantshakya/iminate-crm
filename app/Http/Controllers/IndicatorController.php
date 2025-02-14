<?php

namespace App\Http\Controllers;

use App\Models\Competencies;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Indicator;
use App\Models\Branch;
use App\Models\PerformanceType;
use Illuminate\Http\Request;

class IndicatorController extends Controller
{
    public function index()
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->type == 'employee')
        {
            $user = \Auth::user();
            if($user->type == 'employee')
            {
                $employee = Employee::where('user_id', $user->id)->first();
                $indicators = Indicator::where('created_by', '=', $user->creatorId())
                ->where('department', $employee->department)
                ->where('designation', $employee->designation)
                ->with(['branches','departments','designations','user'])->get();
             }
            else
            {
                $indicators = Indicator::where('created_by', '=', $user->creatorId())
                ->with(['branches','departments','designations','user'])->get();
            }
            return view('indicator.index', compact('indicators'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function create()
    {
        $performance     = PerformanceType::where('created_by', '=', \Auth::user()->creatorId())->get();
        //$brances     = Branch::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'name');
        $branches    = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $branches->prepend('Select Branch','');
        $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $departments->prepend('Select Department', '');
      
         return view('indicator.create', compact('performance', 'branches', 'departments'));
    }


    public function store(Request $request)
    {
        if(\Auth::user()->type == 'company')
        {
            $validator = \Validator::make(
                $request->all(), [

                                   'department' => 'required',
                                   'designation' => 'required',
                                   'rating'      => 'required'
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }


            $indicator                      = new Indicator();
            $indicator->department          = $request->department;
            $indicator->designation         = $request->designation;
            $indicator->branch         = $request->branch;
            $indicator->rating      = json_encode($request->rating, true);
            if(\Auth::user()->type == 'company')
            {
                $indicator->created_user = \Auth::user()->creatorId();
            }
            else
            {
                $indicator->created_user = \Auth::user()->id;
            }

            $indicator->created_by = \Auth::user()->creatorId();
            $indicator->save();

            return redirect()->route('indicator.index')->with('success', __('Indicator successfully created.'));
        }
    }

    public function show(Indicator $indicator)
    {
        $ratings = json_decode($indicator->rating,true);
        $performance     = PerformanceType::where('created_by', '=', \Auth::user()->creatorId())->get();
         return view('indicator.show', compact('ratings','performance','indicator'));
    }
 
    public function edit(Indicator $indicator)
    {
        $performance     = PerformanceType::where('created_by', '=', \Auth::user()->creatorId())->get();
      
        $branches    = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $branches->prepend('Select Branch','');
      
        $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $departments->prepend('Select Department', '');

        $ratings = json_decode($indicator->rating,true);
 
        return view('indicator.edit', compact('performance', 'branches', 'departments', 'ratings','indicator'));
    }


    public function update(Request $request, Indicator $indicator)
    {
        if(\Auth::user()->type == 'company')
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'department' => 'required',
                                   'designation' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }


            $indicator->department          = $request->department;
            $indicator->designation         = $request->designation;
            $indicator->branch         = $request->branch;
            $indicator->rating = json_encode($request->rating, true);
            $indicator->save();

            return redirect()->route('indicator.index')->with('success', __('Indicator successfully updated.'));
        }
    }


    public function destroy(Indicator $indicator)
    {
        if(\Auth::user()->type == 'company')
        {
            $indicator->delete();
            return redirect()->route('indicator.index')->with('success', __('Indicator successfully deleted.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
