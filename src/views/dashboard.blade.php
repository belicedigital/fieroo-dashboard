@extends('layouts.app')
@section('title', trans('entities.dashboard'))
@section('title_header', trans('entities.dashboard'))
@section('content')
<div class="container-fluid">
    @can('admin')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{$total_payments->amount}}<sup style="font-size: 20px">€</sup></h3>
                    <p>{{trans('dashboard.total_payments')}}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
        </div>
        @if(!is_null($stand_more_purchased))
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{$stand_more_purchased->name}}{{--<sup style="font-size: 20px">{{$stand_more_purchased->times}}</sup>--}}</h3>
                    <p>{{trans('dashboard.stand_more_purchased')}}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-rocket"></i>
                </div>
            </div>
        </div>
        @endif
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{$percentage_exhibitors_completed}}<sup style="font-size: 20px">%</sup></h3>
                    <p>{{trans('dashboard.exhibitors_completed')}}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{$tot_users_incompleted}}</h3>
                    <p>{{trans('dashboard.exhibitors_incompleted')}}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-person-booth"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-6">
            <div class="card card-primary">
                <div class="card-header">{{trans('dashboard.active_events_this_year')}}</div>
                <div class="card-body">
                    <canvas id="eventsYearConfirmedChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card card-primary">
                <div class="card-header">{{trans('dashboard.n_participants_per_event')}}</div>
                <div class="card-body">
                    <canvas id="eventsParticipantsChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card card-primary">
                <div class="card-header">{{trans('dashboard.events_receipts_trend')}}</div>
                <div class="card-body">
                    <canvas id="eventsPayments" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    @endcan
    @if(auth()->user()->roles->first()->name == 'espositore')
    <div class="row">
        <div class="col-12">
            @if ($errors->any())
            @include('admin.partials.errors', ['errors' => $errors])
            @endif
            @if (Session::has('success'))
            @include('admin.partials.success')
            @endif
            <div class="card card-primary">
                <div class="card-header">
                    <h5 class="card-title">{{__('generals.available_events')}}</h5>
                </div>
                <div class="card-body table-responsive p-3">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>{{trans('tables.event_name')}}</th>
                                <th>{{trans('tables.start')}}</th>
                                <th>{{trans('tables.end')}}</th>
                                <th>{{trans('tables.subscription_date_open_until')}}</th>
                                <th class="no-sort">{{trans('tables.actions')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($events as $l)
                            <tr>
                                <td>{{$l->title}}</td>
                                <td>{{\Carbon\Carbon::parse($l->start)->format('d/m/Y')}}</td>
                                <td>{{\Carbon\Carbon::parse($l->end)->format('d/m/Y')}}</td>
                                <td>{{\Carbon\Carbon::parse($l->subscription_date_open_until)->format('d/m/Y')}} (-{{\Carbon\Carbon::createFromFormat('Y-m-d',$l->subscription_date_open_until)->diffInDays(\Carbon\Carbon::now()->format('Y-m-d'))}} {{trans('generals.days')}})</td>
                                <td>
                                    <div class="btn-group btn-group" role="group">
                                        @if(userEventIsNotSubscribed(auth()->user()->id, $l->id))
                                        <a class="btn btn-primary" href="{{url('admin/events/'.$l->id)}}"><i class="far fa-edit"></i> {{trans('generals.subscribe')}}</a>
                                        @elseif(userEventIsNotFurnished(auth()->user()->id, $l->id, auth()->user()->exhibitor->id))
                                        <a class="btn btn-primary" href="{{url('admin/events/'.$l->id.'/furnishes')}}"><i class="fas fa-shapes"></i> {{trans('generals.furnishes')}}</a>
                                        @else
                                        <a class="btn btn-primary" href="{{url('admin/events/'.$l->id.'/exhibitor/'.auth()->user()->exhibitor->id.'/recap-furnishings')}}"><i class="far fa-list-alt"></i> {{trans('generals.recap')}}</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
@section('scripts')
<script>
    const initEventsPayments = () => {
        common_request.post('stats/events-payments')
        .then(response => {
            let data = response.data
            if(data.status) {
                let dataset = []
                let labels = []
                $.each(data.data, function(index, value) {
                    labels.push(value.event)
                    dataset.push(value.amount)
                })
                new Chart($('#eventsPayments'), {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: "{{trans('dashboard.receipts_amount')}}",
                                data: dataset,
                                borderWidth: 3,
                                backgroundColor: 'rgba(40,167,69, .5)'
                                // fill: false
                            },
                        ]
                    },
                    options: {
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        }
                    }
                })
            } else {
                toastr.error(data.message)
            }
        })
        .catch(error => {
            toastr.error(error)
            console.log(error)
        })
    }
    const initEventsYearConfirmedChart = () => {
        common_request.post('stats/events-per-year')
        .then(response => {
            let data = response.data
            if(data.status) {
                let dataset = []
                let labels = moment.monthsShort()
                let eventsForChart = []
                $.each(data.data, function(index, value) {
                    let obj = {}
                    obj.month = value.formatted_start
                    obj.total = value.total
                    dataset.push(obj)
                })
                $.each(labels, function(index, value) {
                    let to_push = dataset.find(el => el.month == value)
                    to_push = to_push === undefined ? 0 : parseInt(to_push.total)
                    eventsForChart.push(to_push)
                })
                new Chart($('#eventsYearConfirmedChart'), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: "{{trans('dashboard.n_events')}}",
                                data: eventsForChart,
                                borderWidth: 1,
                                backgroundColor: 'rgba(255,193,7, .5)'
                            },
                        ]
                    },
                })
            } else {
                toastr.error(data.message)
            }
        })
        .catch(error => {
            toastr.error(error)
            console.log(error)
        })
    }
    const initEventsParticipantsChart = () => {
        common_request.post('stats/events-participants')
        .then(response => {
            let data = response.data
            if(data.status) {
                let dataset = []
                let labels = []
                $.each(data.data, function(index, value) {
                    labels.push(value.event)
                    dataset.push(value.participants)
                })
                new Chart($('#eventsParticipantsChart'), {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                data: dataset,
                                borderWidth: 1,
                                backgroundColor: 'rgba(23,162,184, .5)'
                            }
                        ]
                    }
                })
            } else {
                toastr.error(data.message)
            }
        })
        .catch(error => {
            toastr.error(error)
            console.log(error)
        })
    }
    $(document).ready(function() {
        
        initEventsParticipantsChart()
        initEventsYearConfirmedChart()
        initEventsPayments()

        $('table').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": false,
            "autoWidth": false,
            "responsive": false,
            columnDefs: [{
                orderable: false,
                targets: "no-sort"
            }],
            "oLanguage": {
                "sSearch": "{{trans('generals.search')}}",
                "oPaginate": {
                    "sFirst": "{{trans('generals.start')}}", // This is the link to the first page
                    "sPrevious": "«", // This is the link to the previous page
                    "sNext": "»", // This is the link to the next page
                    "sLast": "{{trans('generals.end')}}" // This is the link to the last page
                }
            }
        });
    });
</script>
@endsection