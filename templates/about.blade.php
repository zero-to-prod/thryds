@php use ZeroToProd\Thryds\Routes\RouteList; @endphp
@extends('base')

@section('title', 'About — Thryds')

@section('body')
    <h1>About Thryds</h1>
    <p>A social media site designed to integrate AI with humanity.</p>
    <p><a href="@route(RouteList::home)">Home</a></p>
@endsection
