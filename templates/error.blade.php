@extends('base')

@section('title', $status_code . ' - Thryds')

@section('body')
    <h1>{{ $status_code }}</h1>
    <p>{{ $message }}</p>
@endsection