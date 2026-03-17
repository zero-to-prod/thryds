@php use ZeroToProd\Thryds\Routes\Route; @endphp
@extends('base')

@section('title', 'About — Thryds')

@section('body')
    <h1>About Thryds</h1>
    <p>A social media site designed to integrate AI with humanity.</p>
    <p><a href="{{ Route::home->value }}">Home</a></p>
@endsection
