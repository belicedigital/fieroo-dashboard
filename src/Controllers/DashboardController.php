<?php

namespace Fieroo\Dashboard\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Fieroo\Events\Models\Event;
use Fieroo\Bootstrapper\Models\User;
use Carbon\Carbon;
use Auth;
use DB;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        $data = [];
        dd(Auth::user()->roles->first());
        
        if(Auth::user()->roles->first()->name == 'espositore') {

            $user = User::findOrFail(Auth::user()->id);
            if(is_null($user->exhibitor->detail)) {
                return redirect()->route('compile-data-after-login');
            }

            if(!$user->exhibitor->detail->is_admitted) {
                return redirect()->route('pending-admission');
            }

            $data['events'] = Event::where([
                ['is_published', '=', 1],
                ['subscription_date_open_until', '>=', Carbon::now()->format('Y-m-d')]
            ])->get();

        } else {

            $total_payments = DB::table('payments')
                ->leftJoin('events','payments.event_id','=','events.id')
                ->where([
                    ['events.is_published','=',1],
                    [DB::raw('DATE_FORMAT(events.start,"%Y")'), '=', Carbon::now()->format('Y')]
                ])
                ->select(DB::raw('sum(payments.amount) as amount'))
                ->first();
            $data['total_payments'] = is_object($total_payments) ? $total_payments : 0;

            $n_exhibitors = DB::table('exhibitors')->count();
            $n_completed_exhibitors = DB::table('exhibitors_data')
                ->leftJoin('exhibitors', 'exhibitors_data.exhibitor_id', '=', 'exhibitors.id')
                ->count();
            $data['percentage_exhibitors_completed'] = $n_exhibitors !== 0 ? round(($n_completed_exhibitors/$n_exhibitors)*100) : 0;
            $data['tot_users_incompleted'] = $n_exhibitors-$n_completed_exhibitors;

            $stand_more_purchased = DB::table('payments')
                ->leftJoin('events','payments.event_id','=','events.id')
                ->leftJoin('stands_types_translations','payments.stand_type_id','=','stands_types_translations.stand_type_id')
                ->where([
                    ['events.is_published','=',1],
                    ['stands_types_translations.locale','=',App::getLocale()],
                    [DB::raw('DATE_FORMAT(events.start,"%Y")'), '=', Carbon::now()->format('Y')]
                ])
                ->select('stands_types_translations.name as name', 'payments.stand_type_id as stand', DB::raw('count(payments.id) as times'))
                ->orderBy('times', 'DESC')
                ->groupBy('stand', 'name')
                ->first();
            $data['stand_more_purchased'] = is_object($stand_more_purchased) ? $stand_more_purchased : null;

        }
        // if(auth()->user()->roles->first()->name == 'espositore') {
        //     $data['events'] = Event::where([
        //         ['is_published', '=', 1],
        //         ['subscription_date_open_until', '>=', Carbon::now()->format('Y-m-d')]
        //     ])->get();
        // }

        return view('dashboard::dashboard', $data);
    }

    public function getEventsParticipantsChart(Request $request)
    {
        $response = [
            'status' => false,
            'message' => trans('api.error_general')
        ];

        try {
            $response['status'] = true;
            $response['data'] = DB::table('events')
                ->leftJoin('payments','events.id','=','payments.event_id')
                ->where([
                    ['events.is_published','=',1],
                    ['payments.type_of_payment','=','subscription'],
                    [DB::raw('DATE_FORMAT(events.start,"%Y")'), '=', Carbon::now()->format('Y')]
                ])
                ->select('events.title as event', DB::raw('count(payments.id) as participants'))
                ->groupBy('event')
                ->get()
                ->toArray();
            return response()->json($response);
        } catch(\Exception $e){
            $response['message'] = $e->getMessage();
            return response()->json($response);
        }
    }

    public function getEventsPerYearChart(Request $request)
    {
        $response = [
            'status' => false,
            'message' => trans('api.error_general')
        ];

        try {
            $response['status'] = true;
            $response['data'] = DB::table('events')
                ->where([
                    ['is_published','=',1],
                    [DB::raw('DATE_FORMAT(start,"%Y")'), '=', Carbon::now()->format('Y')]
                ])
                ->select(DB::raw('DATE_FORMAT(start, "%b") as formatted_start'), DB::raw('count(*) as total'))
                ->groupBy('formatted_start')
                ->get()
                ->toArray();
            return response()->json($response);
        } catch(\Exception $e){
            $response['message'] = $e->getMessage();
            return response()->json($response);
        }
    }

    public function getEventsPaymentsChart(Request $request)
    {
        $response = [
            'status' => false,
            'message' => trans('api.error_general')
        ];

        try {
            $response['status'] = true;
            $response['data'] = DB::table('events')
                ->leftJoin('payments','events.id','=','payments.event_id')
                ->where([
                    ['events.is_published','=',1],
                    [DB::raw('DATE_FORMAT(events.start,"%Y")'), '=', Carbon::now()->format('Y')]
                ])
                ->select('events.title as event', DB::raw('sum(payments.amount) as amount'))
                ->groupBy('event')
                ->get()
                ->toArray();
            return response()->json($response);
        } catch(\Exception $e){
            $response['message'] = $e->getMessage();
            return response()->json($response);
        }
    }
}
