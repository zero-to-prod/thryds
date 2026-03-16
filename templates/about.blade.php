@extends('base')

@section('title', 'About — Thryds')

@section('body')
    <h1>About Thryds</h1>
    <p>A social media site designed to integrate AI with humanity.</p>
    <p><a href="{{ ZeroToProd\Thryds\Routes\Route::home->value }}">Home</a></p>
@endsection
